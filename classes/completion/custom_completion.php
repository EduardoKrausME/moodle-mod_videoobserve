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

namespace mod_videoobserve\completion;

use core_completion\activity_custom_completion;

/**
 * Custom completion rules for Video Observation.
 *
 * @package mod_videoobserve
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {
    /**
     * Evaluate one custom completion rule.
     *
     * @param string $rule Rule name.
     * @return int
     */
    public function get_state(string $rule): int {
        global $DB;

        $activity = $DB->get_record('videoobserve', ['id' => $this->cm->instance], '*', MUST_EXIST);
        if ($rule === 'completionpercent') {
            if ((int)$activity->completionpercent <= 0) {
                return COMPLETION_COMPLETE;
            }
            $progress = $DB->get_record('videoobserve_progress', [
                'videoobserveid' => $activity->id,
                'userid' => $this->userid,
            ]);
            return $progress && (float)$progress->percent >= (int)$activity->completionpercent
                ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
        }
        if ($rule === 'completionoccurrences') {
            if ((int)$activity->completionoccurrences <= 0) {
                return COMPLETION_COMPLETE;
            }
            $count = $DB->count_records('videoobserve_occurrences', [
                'videoobserveid' => $activity->id,
                'userid' => $this->userid,
            ]);
            return $count >= (int)$activity->completionoccurrences
                ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
        }
        return COMPLETION_INCOMPLETE;
    }

    /**
     * Method get_defined_custom_rules.
     *
     * @return array Return value.
     */
    public static function get_defined_custom_rules(): array {
        return ['completionpercent', 'completionoccurrences'];
    }

    /**
     * Method get_custom_rule_descriptions.
     *
     * @return array Return value.
     */
    public function get_custom_rule_descriptions(): array {
        global $DB;

        $activity = $DB->get_record('videoobserve', ['id' => $this->cm->instance], '*', MUST_EXIST);
        return [
            'completionpercent' => get_string('completiondetail:percent', 'videoobserve', $activity->completionpercent),
            'completionoccurrences' => get_string('completiondetail:occurrences', 'videoobserve', $activity->completionoccurrences),
        ];
    }

    /**
     * Method get_sort_order.
     *
     * @return array Return value.
     */
    public function get_sort_order(): array {
        return ['completionpercent', 'completionoccurrences'];
    }
}
