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
 * view.php
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
require_capability('mod/videoobserve:view', $context);

$PAGE->set_url('/mod/videoobserve/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($activity->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->add_body_class('mod-videoobserve');

$event = \mod_videoobserve\event\course_module_viewed::create([
    'objectid' => $activity->id,
    'context' => $context,
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('videoobserve', $activity);
$event->trigger();

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$events = $DB->get_records('videoobserve_eventtypes', ['videoobserveid' => $activity->id], 'sortorder ASC, id ASC');
$occurrences = $DB->get_records('videoobserve_occurrences', [
    'videoobserveid' => $activity->id,
    'userid' => $USER->id,
], 'starttime ASC, id ASC');
$progress = $DB->get_record('videoobserve_progress', [
    'videoobserveid' => $activity->id,
    'userid' => $USER->id,
]);
$manager = new observer_manager();
$videoconfig = $manager->video_config($activity, $context);

$occbyevent = [];
foreach ($occurrences as $occurrence) {
    $occbyevent[$occurrence->eventtypeid][] = $occurrence;
}
$eventdata = [];
foreach ($events as $definition) {
    $items = [];
    foreach ($occbyevent[$definition->id] ?? [] as $occurrence) {
        $items[] = [
            'id' => (int)$occurrence->id,
            'eventtypeid' => (int)$definition->id,
            'timecode' => observer_manager::range((float)$occurrence->starttime,
                $occurrence->endtime === null ? null : (float)$occurrence->endtime),
            'starttime' => (float)$occurrence->starttime,
            'endtime' => $occurrence->endtime === null ? 0 : (float)$occurrence->endtime,
            'note' => format_text((string)$occurrence->note, FORMAT_PLAIN, ['context' => $context]),
            'hasnote' => trim((string)$occurrence->note) !== '',
        ];
    }
    $eventdata[] = [
        'id' => (int)$definition->id,
        'name' => format_string($definition->name, true, ['context' => $context]),
        'description' => format_text((string)$definition->description, FORMAT_PLAIN, ['context' => $context]),
        'hasdescription' => trim((string)$definition->description) !== '',
        'required' => !empty($definition->required),
        'count' => count($items),
        'occurrences' => $items,
    ];
}

$references = [];
if (!empty($activity->showreference)) {
    $referenceRecords = $DB->get_records('videoobserve_references', ['videoobserveid' => $activity->id],
        'starttime ASC, id ASC');
    foreach ($referenceRecords as $reference) {
        if (!isset($events[$reference->eventtypeid])) {
            continue;
        }
        $references[] = [
            'eventname' => format_string($events[$reference->eventtypeid]->name, true, ['context' => $context]),
            'timecode' => observer_manager::range((float)$reference->starttime,
                $reference->endtime === null ? null : (float)$reference->endtime),
            'starttime' => (float)$reference->starttime,
            'note' => format_text((string)$reference->note, FORMAT_PLAIN, ['context' => $context]),
            'hasnote' => trim((string)$reference->note) !== '',
        ];
    }
}

$data = [
    'cmid' => (int)$cm->id,
    'name' => format_string($activity->name),
    'intro' => format_module_intro('videoobserve', $activity, $cm->id),
    'hasintro' => trim((string)$activity->intro) !== '',
    'events' => $eventdata,
    'hasevents' => !empty($eventdata),
    'allowintervals' => !empty($activity->allowintervals),
    'allowcomments' => !empty($activity->allowcomments),
    'totalcount' => count($occurrences),
    'percent' => $progress ? round((float)$progress->percent, 1) : 0,
    'hasreferences' => !empty($references),
    'references' => $references,
    'showreference' => !empty($activity->showreference),
    'canmanage' => has_capability('mod/videoobserve:manageevents', $context),
    'canreference' => has_capability('mod/videoobserve:managereference', $context),
    'canreport' => has_capability('mod/videoobserve:viewreport', $context),
    'manageurl' => (new moodle_url('/mod/videoobserve/manage.php', ['id' => $cm->id]))->out(false),
    'referenceurl' => (new moodle_url('/mod/videoobserve/reference.php', ['id' => $cm->id]))->out(false),
    'reporturl' => (new moodle_url('/mod/videoobserve/report.php', ['id' => $cm->id]))->out(false),
    'referencevisiblelabel' => get_string('referencevisible', 'videoobserve'),
];

$jsconfig = [
    'cmid' => (int)$cm->id,
    'mode' => 'student',
    'source' => $videoconfig,
    'lastposition' => $progress ? (float)$progress->lastposition : 0,
    'resumeplayback' => !empty($activity->resumeplayback),
    'tracking' => true,
    'allowintervals' => !empty($activity->allowintervals),
    'allowcomments' => !empty($activity->allowcomments),
    'strings' => [
        'deleteconfirm' => get_string('deleteoccurrenceconfirm', 'videoobserve'),
        'trackingerror' => get_string('trackingerror', 'videoobserve'),
        'saveerror' => get_string('saveerror', 'videoobserve'),
        'intervalstarted' => get_string('intervalstarted', 'videoobserve'),
        'finishinterval' => get_string('finishinterval', 'videoobserve'),
        'startinterval' => get_string('startinterval', 'videoobserve'),
        'delete' => get_string('delete'),
    ],
];
$PAGE->requires->js_call_amd('mod_videoobserve/observer', 'init', [$jsconfig]);

$PAGE->navbar->add(format_string($activity->name));
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_videoobserve/view', $data);
echo $OUTPUT->footer();
