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
 * export.php
 *
 * @package   mod_videoobserve
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/csvlib.class.php');

use mod_videoobserve\observer_manager;

$id = required_param('id', PARAM_INT);
$groupid = optional_param('group', 0, PARAM_INT);
$cm = get_coursemodule_from_id('videoobserve', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videoobserve', ['id' => $cm->instance], '*', MUST_EXIST);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/videoobserve:exportreport', $context);

$users = get_enrolled_users($context, 'mod/videoobserve:view', $groupid,
    "u.id,u.firstname,u.lastname,u.email,u.picture,u.imagealt,u.firstnamephonetic,u.lastnamephonetic,u.middlename,u.alternatename",
    "u.lastname,u.firstname");
foreach ($users as $userid => $user) {
    if (has_capability('mod/videoobserve:viewreport', $context, $userid)) {
        unset($users[$userid]);
    }
}
$events = $DB->get_records('videoobserve_eventtypes', ['videoobserveid' => $activity->id], 'sortorder ASC, id ASC');
$references = $DB->get_records('videoobserve_references', ['videoobserveid' => $activity->id], 'starttime ASC, id ASC');
$occurrences = [];
$progress = [];
$userids = array_keys($users);
if ($userids) {
    [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'u');
    $params['activity'] = $activity->id;
    $occurrences = $DB->get_records_select('videoobserve_occurrences',
        "videoobserveid = :activity AND userid $insql", $params, 'userid,starttime');
    $progressrecords = $DB->get_records_select('videoobserve_progress',
        "videoobserveid = :activity AND userid $insql", $params, '', 'userid,id,percent');
    foreach ($progressrecords as $record) {
        $progress[$record->userid] = $record;
    }
}
$occbyuser = [];
foreach ($occurrences as $record) {
    $occbyuser[$record->userid][] = $record;
}

$csv = new csv_export_writer();
$filename = clean_filename('video-observation-' . $activity->name . '-' . userdate(time(), '%Y%m%d'));
$csv->set_filename($filename);
$headers = [get_string('student', 'videoobserve'), get_string('email'), get_string('watchedpercent', 'videoobserve'),
    get_string('totalmarkings', 'videoobserve')];
foreach ($events as $event) {
    $headers[] = format_string($event->name, true, ['context' => $context]);
}
if ($references) {
    $headers = array_merge($headers, [
        get_string('referencematches', 'videoobserve'),
        get_string('referencemissed', 'videoobserve'),
        get_string('referenceextras', 'videoobserve'),
        get_string('meandeviation', 'videoobserve'),
    ]);
}
$csv->add_data($headers);
$manager = new observer_manager();
foreach ($users as $user) {
    $userocc = $occbyuser[$user->id] ?? [];
    $counts = [];
    foreach ($userocc as $occurrence) {
        $counts[$occurrence->eventtypeid] = ($counts[$occurrence->eventtypeid] ?? 0) + 1;
    }
    $row = [fullname($user), $user->email, isset($progress[$user->id]) ? $progress[$user->id]->percent : 0, count($userocc)];
    foreach ($events as $event) {
        $row[] = $counts[$event->id] ?? 0;
    }
    if ($references) {
        $comparison = $manager->compare_reference($userocc, $references, (int)$activity->referencetolerance);
        $row[] = $comparison['matched'];
        $row[] = $comparison['missed'];
        $row[] = $comparison['extras'];
        $row[] = $comparison['mean_deviation'] === null ? '' : round($comparison['mean_deviation'], 2);
    }
    $csv->add_data($row);
}
$csv->download_file();
die;
