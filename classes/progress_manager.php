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
 * Consolidates watched video segments.
 *
 * @package   mod_videoobserve
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class progress_manager {
    /**
     * Merge a watched segment into stored progress.
     *
     * @param int $activityid Activity id.
     * @param int $userid User id.
     * @param float $start Segment start.
     * @param float $end Segment end.
     * @param float $position Current position.
     * @param float $duration Video duration.
     * @return \stdClass
     */
    public function update(int $activityid, int $userid, float $start, float $end,
                           float $position, float $duration): \stdClass {
        global $DB;

        $duration = max(0.0, min(86400.0, $duration));
        $position = max(0.0, $position);
        if ($duration > 0) {
            $position = min($duration, $position);
        }

        $start = max(0.0, $start);
        $end = max($start, $end);
        if ($duration > 0) {
            $start = min($duration, $start);
            $end = min($duration, $end);
        }
        if (($end - $start) > 60.0) {
            $start = max(0.0, $end - 60.0);
        }

        $record = $DB->get_record('videoobserve_progress', [
            'videoobserveid' => $activityid,
            'userid' => $userid,
        ]);
        $segments = [];
        if ($record && !empty($record->segments)) {
            $decoded = json_decode($record->segments, true);
            if (is_array($decoded)) {
                $segments = $decoded;
            }
        }
        if ($end > $start) {
            $segments[] = [$start, $end];
        }
        $segments = $this->merge_segments($segments);
        $unique = 0.0;
        foreach ($segments as $segment) {
            $unique += max(0.0, $segment[1] - $segment[0]);
        }
        $knownDuration = max($duration, $record ? (float)$record->duration : 0.0);
        $percent = $knownDuration > 0 ? min(100.0, round(($unique / $knownDuration) * 100, 2)) : 0.0;
        $now = time();

        if (!$record) {
            $record = (object)[
                'videoobserveid' => $activityid,
                'userid' => $userid,
                'timecreated' => $now,
            ];
        }
        $record->duration = $knownDuration;
        $record->lastposition = $position;
        $record->segments = json_encode($segments);
        $record->uniquewatched = round($unique, 3);
        $record->percent = $percent;
        $record->timemodified = $now;

        if (empty($record->id)) {
            $record->id = $DB->insert_record('videoobserve_progress', $record);
        } else {
            $DB->update_record('videoobserve_progress', $record);
        }
        return $record;
    }

    /**
     * Merge overlapping or adjacent intervals.
     *
     * @param array $segments Segment pairs.
     * @return array
     */
    public function merge_segments(array $segments): array {
        $normalised = [];
        foreach ($segments as $segment) {
            if (!is_array($segment) || count($segment) < 2) {
                continue;
            }
            $start = max(0.0, (float)$segment[0]);
            $end = max($start, (float)$segment[1]);
            if ($end > $start) {
                $normalised[] = [$start, $end];
            }
        }
        usort($normalised, static fn(array $a, array $b): int => $a[0] <=> $b[0]);
        $merged = [];
        foreach ($normalised as $segment) {
            if (!$merged) {
                $merged[] = $segment;
                continue;
            }
            $last = count($merged) - 1;
            if ($segment[0] <= ($merged[$last][1] + 0.75)) {
                $merged[$last][1] = max($merged[$last][1], $segment[1]);
            } else {
                $merged[] = $segment;
            }
        }
        return $merged;
    }
}
