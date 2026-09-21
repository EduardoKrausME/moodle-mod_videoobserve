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

/**
 * update_progress.php
 *
 * @package   mod_videoobserve
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videoobserve\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_videoobserve\progress_manager;

/**
 * Update watched video progress.
 */
class update_progress extends external_api {

    /**
     * Method execute_parameters.
     *
     * @return external_function_parameters Return value.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'segmentstart' => new external_value(PARAM_FLOAT, 'Watched segment start'),
            'segmentend' => new external_value(PARAM_FLOAT, 'Watched segment end'),
            'position' => new external_value(PARAM_FLOAT, 'Current position'),
            'duration' => new external_value(PARAM_FLOAT, 'Video duration'),
        ]);
    }

    /**
     * Method execute.
     *
     * @param int $cmid Parameter cmid.
     * @param float $segmentstart Parameter segmentstart.
     * @param float $segmentend Parameter segmentend.
     * @param float $position Parameter position.
     * @param float $duration Parameter duration.
     * @return array Return value.
     */
    public static function execute(int $cmid, float $segmentstart, float $segmentend,
                                   float $position, float $duration): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), compact(
            'cmid', 'segmentstart', 'segmentend', 'position', 'duration'
        ));
        $cm = get_coursemodule_from_id('videoobserve', $params['cmid'], 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/videoobserve:view', $context);
        $activity = $DB->get_record('videoobserve', ['id' => $cm->instance], '*', MUST_EXIST);
        $record = (new progress_manager())->update(
            (int)$activity->id,
            (int)$USER->id,
            (float)$params['segmentstart'],
            (float)$params['segmentend'],
            (float)$params['position'],
            (float)$params['duration']
        );
        $completion = new \completion_info(get_course($activity->course));
        if ($completion->is_enabled($cm)) {
            $completion->update_state($cm, COMPLETION_UNKNOWN, $USER->id);
        }
        return [
            'percent' => (float)$record->percent,
            'uniquewatched' => (float)$record->uniquewatched,
            'lastposition' => (float)$record->lastposition,
            'duration' => (float)$record->duration,
        ];
    }

    /**
     * Method execute_returns.
     *
     * @return external_single_structure Return value.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'percent' => new external_value(PARAM_FLOAT, 'Watched percent'),
            'uniquewatched' => new external_value(PARAM_FLOAT, 'Unique watched seconds'),
            'lastposition' => new external_value(PARAM_FLOAT, 'Resume position'),
            'duration' => new external_value(PARAM_FLOAT, 'Known duration'),
        ]);
    }
}
