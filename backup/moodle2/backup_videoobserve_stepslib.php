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
 * backup_videoobserve_stepslib.php
 *
 * @package   mod_videoobserve
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Backup structure step for Video Observation.
 */
class backup_videoobserve_activity_structure_step extends backup_activity_structure_step {

    /**
     * Method define_structure.
     *
     * @return mixed Return value.
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');
        $activity = new backup_nested_element('videoobserve', ['id'], [
            'course', 'name', 'intro', 'introformat', 'videosource', 'videourl', 'resumeplayback',
            'allowintervals', 'allowcomments', 'referencetolerance', 'showreference',
            'completionpercent', 'completionoccurrences', 'timecreated', 'timemodified',
        ]);
        $eventtypes = new backup_nested_element('eventtypes');
        $eventtype = new backup_nested_element('eventtype', ['id'], [
            'name', 'description', 'required', 'sortorder', 'timecreated', 'timemodified',
        ]);
        $references = new backup_nested_element('references');
        $reference = new backup_nested_element('reference', ['id'], [
            'eventtypeid', 'starttime', 'endtime', 'note', 'createdby', 'timecreated', 'timemodified',
        ]);
        $occurrences = new backup_nested_element('occurrences');
        $occurrence = new backup_nested_element('occurrence', ['id'], [
            'eventtypeid', 'userid', 'starttime', 'endtime', 'note', 'timecreated', 'timemodified',
        ]);
        $progresses = new backup_nested_element('progresses');
        $progress = new backup_nested_element('progress', ['id'], [
            'userid', 'duration', 'lastposition', 'segments', 'uniquewatched', 'percent', 'timecreated', 'timemodified',
        ]);

        $activity->add_child($eventtypes);
        $eventtypes->add_child($eventtype);
        $activity->add_child($references);
        $references->add_child($reference);
        $activity->add_child($occurrences);
        $occurrences->add_child($occurrence);
        $activity->add_child($progresses);
        $progresses->add_child($progress);

        $activity->set_source_table('videoobserve', ['id' => backup::VAR_ACTIVITYID]);
        $eventtype->set_source_table('videoobserve_eventtypes', ['videoobserveid' => backup::VAR_PARENTID]);
        $reference->set_source_table('videoobserve_references', ['videoobserveid' => backup::VAR_PARENTID]);
        if ($userinfo) {
            $occurrence->set_source_table('videoobserve_occurrences', ['videoobserveid' => backup::VAR_PARENTID]);
            $progress->set_source_table('videoobserve_progress', ['videoobserveid' => backup::VAR_PARENTID]);
        }

        $reference->annotate_ids('user', 'createdby');
        $occurrence->annotate_ids('user', 'userid');
        $progress->annotate_ids('user', 'userid');
        $activity->annotate_files('mod_videoobserve', 'video', null);
        $activity->annotate_files('mod_videoobserve', 'poster', null);
        return $this->prepare_activity_structure($activity);
    }
}
