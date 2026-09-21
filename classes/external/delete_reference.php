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
 * delete_reference.php
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

/**
 * Delete a teacher reference occurrence.
 */
class delete_reference extends external_api {

    /**
     * Method execute_parameters.
     *
     * @return external_function_parameters Return value.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'referenceid' => new external_value(PARAM_INT, 'Reference id'),
        ]);
    }

    /**
     * Method execute.
     *
     * @param int $cmid Parameter cmid.
     * @param int $referenceid Parameter referenceid.
     * @return array Return value.
     */
    public static function execute(int $cmid, int $referenceid): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), compact('cmid', 'referenceid'));
        $cm = get_coursemodule_from_id('videoobserve', $params['cmid'], 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/videoobserve:managereference', $context);
        $record = $DB->get_record('videoobserve_references', [
            'id' => $params['referenceid'],
            'videoobserveid' => $cm->instance,
        ], '*', MUST_EXIST);
        $eventtypeid = (int)$record->eventtypeid;
        $DB->delete_records('videoobserve_references', ['id' => $record->id]);
        $eventcount = $DB->count_records('videoobserve_references', ['eventtypeid' => $eventtypeid]);
        return ['success' => true, 'eventtypeid' => $eventtypeid, 'eventcount' => $eventcount];
    }

    /**
     * Method execute_returns.
     *
     * @return external_single_structure Return value.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Deletion succeeded'),
            'eventtypeid' => new external_value(PARAM_INT, 'Event type id'),
            'eventcount' => new external_value(PARAM_INT, 'Remaining event count'),
        ]);
    }
}
