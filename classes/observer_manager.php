<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_videoobserve;

/**
 * Observation display and comparison helpers.
 *
 * @package   mod_videoobserve
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer_manager {
    /**
     * Format seconds as HH:MM:SS or MM:SS.
     *
     * @param float $seconds Seconds.
     * @return string
     */
    public static function timecode(float $seconds): string {
        $seconds = max(0, (int)round($seconds));
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;
        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
        }
        return sprintf('%02d:%02d', $minutes, $secs);
    }

    /**
     * Format an occurrence range.
     *
     * @param float $start Start time.
     * @param float|null $end End time.
     * @return string
     */
    public static function range(float $start, ?float $end): string {
        if ($end !== null && $end > $start) {
            return self::timecode($start) . '–' . self::timecode($end);
        }
        return self::timecode($start);
    }

    /**
     * Return a playable source configuration.
     *
     * @param \stdClass $activity Activity.
     * @param \context_module $context Module context.
     * @return array
     */
    public function video_config(\stdClass $activity, \context_module $context): array {
        $config = [
            'source' => $activity->videosource,
            'url' => '',
            'videoid' => '',
            'poster' => $this->file_url($context, 'poster'),
        ];
        if ($activity->videosource === 'upload') {
            $config['url'] = $this->file_url($context, 'video');
        } else if ($activity->videosource === 'youtube') {
            $config['videoid'] = $this->youtube_id((string)$activity->videourl);
        } else if ($activity->videosource === 'vimeo') {
            $config['videoid'] = $this->vimeo_id((string)$activity->videourl);
        } else {
            $config['url'] = clean_param((string)$activity->videourl, PARAM_URL);
        }
        return $config;
    }

    /**
     * Resolve the first file in an activity file area.
     *
     * @param \context_module $context Context.
     * @param string $filearea File area.
     * @return string
     */
    private function file_url(\context_module $context, string $filearea): string {
        $files = get_file_storage()->get_area_files($context->id, 'mod_videoobserve', $filearea, 0,
            'itemid, filepath, filename', false);
        if (!$files) {
            return '';
        }
        $file = reset($files);
        return \moodle_url::make_pluginfile_url($context->id, 'mod_videoobserve', $filearea, 0,
            $file->get_filepath(), $file->get_filename())->out(false);
    }

    /**
     * Extract a YouTube video id from an id or URL.
     *
     * @param string $value Value.
     * @return string
     */
    private function youtube_id(string $value): string {
        $value = trim($value);
        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $value)) {
            return $value;
        }
        if (preg_match('~(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:watch\?v=|embed/|shorts/))([A-Za-z0-9_-]{11})~i',
            $value, $matches)) {
            return $matches[1];
        }
        $query = parse_url($value, PHP_URL_QUERY);
        if ($query) {
            parse_str($query, $params);
            if (!empty($params['v']) && preg_match('/^[A-Za-z0-9_-]{11}$/', $params['v'])) {
                return $params['v'];
            }
        }
        return '';
    }

    /**
     * Extract a Vimeo video id from an id or URL.
     *
     * @param string $value Value.
     * @return string
     */
    private function vimeo_id(string $value): string {
        $value = trim($value);
        if (preg_match('/^\d+$/', $value)) {
            return $value;
        }
        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~i', $value, $matches)) {
            return $matches[1];
        }
        return '';
    }

    /**
     * Compare one student's observations with teacher references.
     *
     * Each reference can match at most one student occurrence of the same event type.
     *
     * @param array $occurrences Student records.
     * @param array $references Reference records.
     * @param int $tolerance Tolerance in seconds.
     * @return array
     */
    public function compare_reference(array $occurrences, array $references, int $tolerance): array {
        $byeventocc = [];
        $byeventref = [];
        foreach ($occurrences as $occurrence) {
            $byeventocc[$occurrence->eventtypeid][] = $occurrence;
        }
        foreach ($references as $reference) {
            $byeventref[$reference->eventtypeid][] = $reference;
        }

        $matched = 0;
        $deviations = [];
        $totalrefs = count($references);
        foreach ($byeventref as $eventid => $refs) {
            $student = $byeventocc[$eventid] ?? [];
            $used = [];
            foreach ($refs as $reference) {
                $best = null;
                $bestdistance = PHP_FLOAT_MAX;
                foreach ($student as $index => $occurrence) {
                    if (isset($used[$index])) {
                        continue;
                    }
                    $distance = abs((float)$occurrence->starttime - (float)$reference->starttime);
                    if ($distance < $bestdistance) {
                        $bestdistance = $distance;
                        $best = $index;
                    }
                }
                if ($best !== null && $bestdistance <= $tolerance) {
                    $used[$best] = true;
                    $matched++;
                    $deviations[] = $bestdistance;
                }
            }
        }
        $extras = max(0, count($occurrences) - $matched);
        $missed = max(0, $totalrefs - $matched);
        $mean = $deviations ? array_sum($deviations) / count($deviations) : null;
        return [
            'matched' => $matched,
            'missed' => $missed,
            'extras' => $extras,
            'mean_deviation' => $mean,
            'reference_total' => $totalrefs,
        ];
    }
}
