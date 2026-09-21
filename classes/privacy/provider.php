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

namespace mod_videoobserve\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy API provider.
 *
 * @package mod_videoobserve
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Method get_metadata.
     *
     * @param collection $collection Parameter collection.
     * @return collection Return value.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('videoobserve_occurrences', [
            'userid' => 'privacy:metadata:occurrences:userid',
            'eventtypeid' => 'privacy:metadata:occurrences:eventtypeid',
            'starttime' => 'privacy:metadata:occurrences:starttime',
            'endtime' => 'privacy:metadata:occurrences:endtime',
            'note' => 'privacy:metadata:occurrences:note',
            'timecreated' => 'privacy:metadata:occurrences:timecreated',
            'timemodified' => 'privacy:metadata:occurrences:timemodified',
        ], 'privacy:metadata:occurrences');
        $collection->add_database_table('videoobserve_progress', [
            'userid' => 'privacy:metadata:progress:userid',
            'duration' => 'privacy:metadata:progress:duration',
            'lastposition' => 'privacy:metadata:progress:lastposition',
            'segments' => 'privacy:metadata:progress:segments',
            'uniquewatched' => 'privacy:metadata:progress:uniquewatched',
            'percent' => 'privacy:metadata:progress:percent',
            'timemodified' => 'privacy:metadata:progress:timemodified',
        ], 'privacy:metadata:progress');
        $collection->add_database_table('videoobserve_references', [
            'createdby' => 'privacy:metadata:references:createdby',
            'eventtypeid' => 'privacy:metadata:references:eventtypeid',
            'starttime' => 'privacy:metadata:references:starttime',
            'endtime' => 'privacy:metadata:references:endtime',
            'note' => 'privacy:metadata:references:note',
            'timecreated' => 'privacy:metadata:references:timecreated',
        ], 'privacy:metadata:references');
        return $collection;
    }

    /**
     * Method get_contexts_for_userid.
     *
     * @param int $userid Parameter userid.
     * @return contextlist Return value.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {videoobserve} vo ON vo.id = cm.instance
             LEFT JOIN {videoobserve_occurrences} o ON o.videoobserveid = vo.id AND o.userid = :ouserid
             LEFT JOIN {videoobserve_progress} p ON p.videoobserveid = vo.id AND p.userid = :puserid
             LEFT JOIN {videoobserve_references} r ON r.videoobserveid = vo.id AND r.createdby = :ruserid
                 WHERE o.id IS NOT NULL OR p.id IS NOT NULL OR r.id IS NOT NULL";
        $params = [
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'videoobserve',
            'ouserid' => $userid,
            'puserid' => $userid,
            'ruserid' => $userid,
        ];
        return (new contextlist())->add_from_sql($sql, $params);
    }

    /**
     * Method export_user_data.
     *
     * @param approved_contextlist $contextlist Parameter contextlist.
     * @return void Return value.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('videoobserve', $context->instanceid, 0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }
            $events = $DB->get_records_menu('videoobserve_eventtypes', ['videoobserveid' => $cm->instance], '', 'id,name');
            $occurrences = [];
            foreach ($DB->get_records('videoobserve_occurrences', [
                'videoobserveid' => $cm->instance,
                'userid' => $userid,
            ], 'starttime ASC') as $record) {
                $occurrences[] = (object)[
                    'event' => $events[$record->eventtypeid] ?? (string)$record->eventtypeid,
                    'starttime' => $record->starttime,
                    'endtime' => $record->endtime,
                    'note' => $record->note,
                    'timecreated' => transform::datetime($record->timecreated),
                ];
            }
            $progress = $DB->get_record('videoobserve_progress', [
                'videoobserveid' => $cm->instance,
                'userid' => $userid,
            ]);
            $data = (object)[
                'occurrences' => $occurrences,
                'progress' => $progress ?: null,
            ];
            writer::with_context($context)->export_data([get_string('privacy:exportpath', 'videoobserve')], $data);
        }
    }

    /**
     * Method delete_data_for_all_users_in_context.
     *
     * @param \context $context Parameter context.
     * @return void Return value.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('videoobserve', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }
        $DB->delete_records('videoobserve_occurrences', ['videoobserveid' => $cm->instance]);
        $DB->delete_records('videoobserve_progress', ['videoobserveid' => $cm->instance]);
        $DB->delete_records('videoobserve_references', ['videoobserveid' => $cm->instance]);
    }

    /**
     * Method delete_data_for_user.
     *
     * @param approved_contextlist $contextlist Parameter contextlist.
     * @return void Return value.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('videoobserve', $context->instanceid, 0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }
            $DB->delete_records('videoobserve_occurrences', ['videoobserveid' => $cm->instance, 'userid' => $userid]);
            $DB->delete_records('videoobserve_progress', ['videoobserveid' => $cm->instance, 'userid' => $userid]);
            $DB->delete_records('videoobserve_references', ['videoobserveid' => $cm->instance, 'createdby' => $userid]);
        }
    }

    /**
     * Method get_users_in_context.
     *
     * @param userlist $userlist Parameter userlist.
     * @return void Return value.
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('videoobserve', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }
        $userlist->add_from_sql('userid',
            'SELECT userid FROM {videoobserve_occurrences} WHERE videoobserveid = :id', ['id' => $cm->instance]);
        $userlist->add_from_sql('userid',
            'SELECT userid FROM {videoobserve_progress} WHERE videoobserveid = :id', ['id' => $cm->instance]);
        $userlist->add_from_sql('createdby',
            'SELECT createdby FROM {videoobserve_references} WHERE videoobserveid = :id', ['id' => $cm->instance]);
    }

    /**
     * Method delete_data_for_users.
     *
     * @param approved_userlist $userlist Parameter userlist.
     * @return void Return value.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('videoobserve', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }
        $userids = $userlist->get_userids();
        if (!$userids) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'u');
        $params['activity'] = $cm->instance;
        $DB->delete_records_select('videoobserve_occurrences', "videoobserveid = :activity AND userid $insql", $params);
        $DB->delete_records_select('videoobserve_progress', "videoobserveid = :activity AND userid $insql", $params);
        $DB->delete_records_select('videoobserve_references', "videoobserveid = :activity AND createdby $insql", $params);
    }
}
