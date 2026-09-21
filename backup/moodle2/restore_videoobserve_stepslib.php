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
 * restore_videoobserve_stepslib.php
 *
 * @package   mod_videoobserve
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Restore structure step for Video Observation.
 */
class restore_videoobserve_activity_structure_step extends restore_activity_structure_step {

    /**
     * Method define_structure.
     *
     * @return mixed Return value.
     */
    protected function define_structure() {
        $paths = [];
        $paths[] = new restore_path_element('videoobserve', '/activity/videoobserve');
        $paths[] = new restore_path_element('videoobserve_eventtype', '/activity/videoobserve/eventtypes/eventtype');
        $paths[] = new restore_path_element('videoobserve_reference', '/activity/videoobserve/references/reference');
        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element('videoobserve_occurrence', '/activity/videoobserve/occurrences/occurrence');
            $paths[] = new restore_path_element('videoobserve_progress', '/activity/videoobserve/progresses/progress');
        }
        return $this->prepare_activity_structure($paths);
    }

    /**
     * process_videoobserve
     *
     * @param $data
     * @return void
     * @throws dml_exception
     */
    protected function process_videoobserve($data) {
        global $DB;
        $record = (object)$data;
        $oldid = $record->id;
        $record->course = $this->get_courseid();
        $record->timecreated = $this->apply_date_offset($record->timecreated);
        $record->timemodified = $this->apply_date_offset($record->timemodified);
        $newid = $DB->insert_record('videoobserve', $record);
        $this->apply_activity_instance($newid);
        $this->set_mapping('videoobserve', $oldid, $newid, true);
    }

    /**
     * process_videoobserve_eventtype
     *
     * @param $data
     * @return void
     * @throws dml_exception
     */
    protected function process_videoobserve_eventtype($data) {
        global $DB;
        $record = (object)$data;
        $oldid = $record->id;
        $record->videoobserveid = $this->get_new_parentid('videoobserve');
        $record->timecreated = $this->apply_date_offset($record->timecreated);
        $record->timemodified = $this->apply_date_offset($record->timemodified);
        $newid = $DB->insert_record('videoobserve_eventtypes', $record);
        $this->set_mapping('videoobserve_eventtype', $oldid, $newid);
    }

    /**
     * process_videoobserve_reference
     *
     * @param $data
     * @return void
     * @throws dml_exception
     */
    protected function process_videoobserve_reference($data) {
        global $DB;
        $record = (object)$data;
        $record->videoobserveid = $this->get_new_parentid('videoobserve');
        $record->eventtypeid = $this->get_mappingid('videoobserve_eventtype', $record->eventtypeid);
        $record->createdby = $this->get_mappingid('user', $record->createdby, 0);
        $record->timecreated = $this->apply_date_offset($record->timecreated);
        $record->timemodified = $this->apply_date_offset($record->timemodified);
        $DB->insert_record('videoobserve_references', $record);
    }

    /**
     * process_videoobserve_occurrence
     *
     * @param $data
     * @return void
     * @throws dml_exception
     */
    protected function process_videoobserve_occurrence($data) {
        global $DB;
        $record = (object)$data;
        $record->videoobserveid = $this->get_new_parentid('videoobserve');
        $record->eventtypeid = $this->get_mappingid('videoobserve_eventtype', $record->eventtypeid);
        $record->userid = $this->get_mappingid('user', $record->userid);
        $record->timecreated = $this->apply_date_offset($record->timecreated);
        $record->timemodified = $this->apply_date_offset($record->timemodified);
        $DB->insert_record('videoobserve_occurrences', $record);
    }

    /**
     * process_videoobserve_progress
     *
     * @param $data
     * @return void
     * @throws dml_exception
     */
    protected function process_videoobserve_progress($data) {
        global $DB;
        $record = (object)$data;
        $record->videoobserveid = $this->get_new_parentid('videoobserve');
        $record->userid = $this->get_mappingid('user', $record->userid);
        $record->timecreated = $this->apply_date_offset($record->timecreated);
        $record->timemodified = $this->apply_date_offset($record->timemodified);
        $DB->insert_record('videoobserve_progress', $record);
    }

    /**
     * Method after_execute.
     *
     * @return mixed Return value.
     */
    protected function after_execute() {
        $this->add_related_files('mod_videoobserve', 'video', null);
        $this->add_related_files('mod_videoobserve', 'poster', null);
    }
}
