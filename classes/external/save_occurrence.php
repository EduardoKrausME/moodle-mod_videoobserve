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

namespace mod_videoobserve\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_videoobserve\observer_manager;

/**
 * Save a student observation occurrence.
 *
 * @package mod_videoobserve
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_occurrence extends external_api {

    /**
     * Method execute_parameters.
     *
     * @return external_function_parameters Return value.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'eventtypeid' => new external_value(PARAM_INT, 'Observation event type id'),
            'starttime' => new external_value(PARAM_FLOAT, 'Start time in seconds'),
            'endtime' => new external_value(PARAM_FLOAT, 'End time in seconds, zero for a point'),
            'note' => new external_value(PARAM_TEXT, 'Optional observation note', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $cmid Course module id.
     * @param int $eventtypeid Event type id.
     * @param float $starttime Start time.
     * @param float $endtime End time or zero.
     * @param string $note Note.
     * @return array
     */
    public static function execute(int $cmid, int $eventtypeid, float $starttime, float $endtime, string $note = ''): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), compact(
            'cmid', 'eventtypeid', 'starttime', 'endtime', 'note'
        ));
        $cm = get_coursemodule_from_id('videoobserve', $params['cmid'], 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/videoobserve:view', $context);
        $activity = $DB->get_record('videoobserve', ['id' => $cm->instance], '*', MUST_EXIST);
        $event = $DB->get_record('videoobserve_eventtypes', [
            'id' => $params['eventtypeid'],
            'videoobserveid' => $activity->id,
        ], '*', MUST_EXIST);

        $start = min(86400.0, max(0.0, (float)$params['starttime']));
        $end = min(86400.0, max(0.0, (float)$params['endtime']));
        if ($end > 0 && empty($activity->allowintervals)) {
            throw new \invalid_parameter_exception('Intervals are disabled for this activity.');
        }
        if ($end > 0 && $end < $start) {
            [$start, $end] = [$end, $start];
        }
        $endvalue = $end > $start ? $end : null;
        $notevalue = !empty($activity->allowcomments) ? trim($params['note']) : '';
        $now = time();
        $record = (object)[
            'videoobserveid' => $activity->id,
            'eventtypeid' => $event->id,
            'userid' => $USER->id,
            'starttime' => $start,
            'endtime' => $endvalue,
            'note' => $notevalue,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $record->id = $DB->insert_record('videoobserve_occurrences', $record);

        $total = $DB->count_records('videoobserve_occurrences', [
            'videoobserveid' => $activity->id,
            'userid' => $USER->id,
        ]);
        $eventcount = $DB->count_records('videoobserve_occurrences', [
            'eventtypeid' => $event->id,
            'userid' => $USER->id,
        ]);
        $completion = new \completion_info(get_course($activity->course));
        if ($completion->is_enabled($cm)) {
            $completion->update_state($cm, COMPLETION_UNKNOWN, $USER->id);
        }

        return [
            'id' => (int)$record->id,
            'eventtypeid' => (int)$event->id,
            'eventname' => format_string($event->name, true, ['context' => $context]),
            'starttime' => $start,
            'endtime' => $endvalue ?? 0.0,
            'timecode' => observer_manager::range($start, $endvalue),
            'note' => $notevalue,
            'eventcount' => $eventcount,
            'totalcount' => $total,
        ];
    }

    /**
     * Method execute_returns.
     *
     * @return external_single_structure Return value.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Occurrence id'),
            'eventtypeid' => new external_value(PARAM_INT, 'Event type id'),
            'eventname' => new external_value(PARAM_TEXT, 'Event name'),
            'starttime' => new external_value(PARAM_FLOAT, 'Start time'),
            'endtime' => new external_value(PARAM_FLOAT, 'End time or zero'),
            'timecode' => new external_value(PARAM_TEXT, 'Formatted range'),
            'note' => new external_value(PARAM_TEXT, 'Note'),
            'eventcount' => new external_value(PARAM_INT, 'Count for event'),
            'totalcount' => new external_value(PARAM_INT, 'Total observations'),
        ]);
    }
}
