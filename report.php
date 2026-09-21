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
 * report.php
 *
 * @package   mod_videoobserve
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use mod_videoobserve\observer_manager;

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('videoobserve', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videoobserve', ['id' => $cm->instance], '*', MUST_EXIST);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/videoobserve:viewreport', $context);

$PAGE->set_url('/mod/videoobserve/report.php', ['id' => $cm->id]);
$PAGE->set_title(get_string('aggregatereport', 'videoobserve'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->navbar->add(format_string($activity->name), new moodle_url('/mod/videoobserve/view.php', ['id' => $cm->id]));
$PAGE->navbar->add(get_string('aggregatereport', 'videoobserve'));

$groupid = 0;
if (groups_get_activity_groupmode($cm)) {
    $groupid = groups_get_activity_group($cm, true);
}
$users = get_enrolled_users($context, 'mod/videoobserve:view', $groupid,
    "u.id,u.firstname,u.lastname,u.email,u.picture,u.imagealt,u.firstnamephonetic,u.lastnamephonetic,u.middlename,u.alternatename",
    "u.lastname,u.firstname");
foreach ($users as $userid => $user) {
    if (has_capability('mod/videoobserve:viewreport', $context, $userid)) {
        unset($users[$userid]);
    }
}
$userids = array_keys($users);
$events = $DB->get_records('videoobserve_eventtypes', ['videoobserveid' => $activity->id], 'sortorder ASC, id ASC');
$references = $DB->get_records('videoobserve_references', ['videoobserveid' => $activity->id], 'starttime ASC, id ASC');
$occurrences = [];
$progressrows = [];
if ($userids) {
    [$insql, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'usr');
    $params = ['activityid' => $activity->id] + $inparams;
    $occurrences = $DB->get_records_select('videoobserve_occurrences',
        "videoobserveid = :activityid AND userid $insql", $params, 'starttime ASC, id ASC');
    $progressrows = $DB->get_records_select('videoobserve_progress',
        "videoobserveid = :activityid AND userid $insql", $params, '',
        'userid,id,duration,lastposition,uniquewatched,percent,timemodified');
}
$progressbyuser = [];
foreach ($progressrows as $progress) {
    $progressbyuser[$progress->userid] = $progress;
}
$occbyuser = [];
$occbyevent = [];
foreach ($occurrences as $occurrence) {
    $occbyuser[$occurrence->userid][] = $occurrence;
    $occbyevent[$occurrence->eventtypeid][] = $occurrence;
}

$totalmarks = count($occurrences);
$studentcount = count($users);
$average = $studentcount ? $totalmarks / $studentcount : 0;

$eventtable = new html_table();
$eventtable->head = [
    get_string('event', 'videoobserve'),
    get_string('totalmarkings', 'videoobserve'),
    get_string('studentsidentified', 'videoobserve'),
    get_string('averageperstudent', 'videoobserve'),
];
$eventstats = [];
foreach ($events as $definition) {
    $items = $occbyevent[$definition->id] ?? [];
    $uniqueusers = [];
    foreach ($items as $item) {
        $uniqueusers[$item->userid] = true;
    }
    $eventstats[] = [
        'name' => format_string($definition->name, true, ['context' => $context]),
        'count' => count($items),
        'students' => count($uniqueusers),
        'average' => $studentcount ? count($items) / $studentcount : 0,
    ];
}
usort($eventstats, static fn(array $a, array $b): int => $b['count'] <=> $a['count']);
foreach ($eventstats as $stat) {
    $eventtable->data[] = [
        $stat['name'],
        $stat['count'],
        $stat['students'],
        format_float($stat['average'], 2),
    ];
}

$buckets = [];
foreach ($occurrences as $occurrence) {
    $bucket = ((int)floor((float)$occurrence->starttime / 10)) * 10;
    $key = $occurrence->eventtypeid . ':' . $bucket;
    if (!isset($buckets[$key])) {
        $buckets[$key] = ['eventid' => $occurrence->eventtypeid, 'bucket' => $bucket, 'count' => 0, 'users' => []];
    }
    $buckets[$key]['count']++;
    $buckets[$key]['users'][$occurrence->userid] = true;
}
usort($buckets, static fn(array $a, array $b): int => $b['count'] <=> $a['count']);
$momenttable = new html_table();
$momenttable->head = [
    get_string('event', 'videoobserve'),
    get_string('timewindow', 'videoobserve'),
    get_string('markings', 'videoobserve'),
    get_string('students', 'videoobserve'),
];
foreach (array_slice($buckets, 0, 20) as $bucket) {
    if (!isset($events[$bucket['eventid']])) {
        continue;
    }
    $momenttable->data[] = [
        format_string($events[$bucket['eventid']]->name, true, ['context' => $context]),
        observer_manager::timecode((float)$bucket['bucket']) . '–' . observer_manager::timecode((float)$bucket['bucket'] + 9.999),
        $bucket['count'],
        count($bucket['users']),
    ];
}

$studenttable = new html_table();
$studenttable->head = [get_string('student', 'videoobserve'), get_string('watchedpercent', 'videoobserve'),
    get_string('totalmarkings', 'videoobserve')];
foreach ($events as $definition) {
    $studenttable->head[] = format_string($definition->name, true, ['context' => $context]);
}
if ($references) {
    $studenttable->head[] = get_string('referencematches', 'videoobserve');
    $studenttable->head[] = get_string('referencemissed', 'videoobserve');
    $studenttable->head[] = get_string('referenceextras', 'videoobserve');
    $studenttable->head[] = get_string('meandeviation', 'videoobserve');
}
$manager = new observer_manager();
foreach ($users as $user) {
    $useroccurrences = $occbyuser[$user->id] ?? [];
    $counts = [];
    foreach ($useroccurrences as $occurrence) {
        $counts[$occurrence->eventtypeid] = ($counts[$occurrence->eventtypeid] ?? 0) + 1;
    }
    $progress = $progressbyuser[$user->id] ?? null;
    $row = [
        fullname($user),
        $progress ? format_float((float)$progress->percent, 1) . '%' : '0%',
        count($useroccurrences),
    ];
    foreach ($events as $definition) {
        $row[] = $counts[$definition->id] ?? 0;
    }
    if ($references) {
        $comparison = $manager->compare_reference($useroccurrences, $references, (int)$activity->referencetolerance);
        $row[] = $comparison['matched'];
        $row[] = $comparison['missed'];
        $row[] = $comparison['extras'];
        $row[] = $comparison['mean_deviation'] === null
            ? get_string('notavailable', 'videoobserve')
            : get_string('secondsvalue', 'videoobserve', format_float($comparison['mean_deviation'], 1));
    }
    $studenttable->data[] = $row;
}

$summary = [
    get_string('students', 'videoobserve') => $studentcount,
    get_string('totalmarkings', 'videoobserve') => $totalmarks,
    get_string('averageperstudent', 'videoobserve') => format_float($average, 2),
    get_string('referencecount', 'videoobserve') => count($references),
];

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('aggregatereport', 'videoobserve'));
if (groups_get_activity_groupmode($cm)) {
    groups_print_activity_menu($cm, $PAGE->url);
}
$buttons = [];
$buttons[] = html_writer::link(new moodle_url('/mod/videoobserve/view.php', ['id' => $cm->id]),
    get_string('backtoactivity', 'videoobserve'), ['class' => 'btn btn-secondary']);
if (has_capability('mod/videoobserve:exportreport', $context)) {
    $buttons[] = html_writer::link(new moodle_url('/mod/videoobserve/export.php', ['id' => $cm->id, 'group' => $groupid]),
        get_string('exportcsv', 'videoobserve'), ['class' => 'btn btn-secondary']);
}
echo html_writer::div(implode(' ', $buttons), 'mb-3');

echo html_writer::start_div('row g-3 mb-4');
foreach ($summary as $label => $value) {
    echo html_writer::start_div('col-sm-6 col-lg-3');
    echo html_writer::start_div('card h-100');
    echo html_writer::div(s($label), 'card-header');
    echo html_writer::div(html_writer::tag('strong', s((string)$value), ['class' => 'h3']), 'card-body');
    echo html_writer::end_div();
    echo html_writer::end_div();
}
echo html_writer::end_div();

echo $OUTPUT->heading(get_string('eventfrequency', 'videoobserve'), 3);
if ($events) {
    echo html_writer::table($eventtable);
} else {
    echo $OUTPUT->notification(get_string('noevents', 'videoobserve'), \core\output\notification::NOTIFY_INFO);
}

echo $OUTPUT->heading(get_string('mostidentifiedmoments', 'videoobserve'), 3);
if ($momenttable->data) {
    echo html_writer::table($momenttable);
} else {
    echo $OUTPUT->notification(get_string('nomarkingsyet', 'videoobserve'), \core\output\notification::NOTIFY_INFO);
}

echo $OUTPUT->heading(get_string('studentdifferences', 'videoobserve'), 3);
if ($users) {
    echo html_writer::table($studenttable);
} else {
    echo $OUTPUT->notification(get_string('nostudents', 'videoobserve'), \core\output\notification::NOTIFY_INFO);
}
echo $OUTPUT->footer();
