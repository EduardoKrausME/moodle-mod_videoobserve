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
 * reference.php
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
require_capability('mod/videoobserve:managereference', $context);

$PAGE->set_url('/mod/videoobserve/reference.php', ['id' => $cm->id]);
$PAGE->set_title(get_string('managereference', 'videoobserve'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->add_body_class('mod-videoobserve');
$PAGE->navbar->add(format_string($activity->name), new moodle_url('/mod/videoobserve/view.php', ['id' => $cm->id]));
$PAGE->navbar->add(get_string('managereference', 'videoobserve'));

$events = $DB->get_records('videoobserve_eventtypes', ['videoobserveid' => $activity->id], 'sortorder ASC, id ASC');
$references = $DB->get_records('videoobserve_references', ['videoobserveid' => $activity->id], 'starttime ASC, id ASC');
$byevent = [];
foreach ($references as $reference) {
    $byevent[$reference->eventtypeid][] = $reference;
}
$eventdata = [];
foreach ($events as $definition) {
    $items = [];
    foreach ($byevent[$definition->id] ?? [] as $reference) {
        $items[] = [
            'id' => (int)$reference->id,
            'eventtypeid' => (int)$definition->id,
            'timecode' => observer_manager::range((float)$reference->starttime,
                $reference->endtime === null ? null : (float)$reference->endtime),
            'starttime' => (float)$reference->starttime,
            'endtime' => $reference->endtime === null ? 0 : (float)$reference->endtime,
            'note' => format_text((string)$reference->note, FORMAT_PLAIN, ['context' => $context]),
            'hasnote' => trim((string)$reference->note) !== '',
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
$manager = new observer_manager();
$videoconfig = $manager->video_config($activity, $context);
$data = [
    'cmid' => (int)$cm->id,
    'name' => get_string('referenceanswer', 'videoobserve'),
    'intro' => get_string('referenceinstructions', 'videoobserve'),
    'hasintro' => true,
    'events' => $eventdata,
    'hasevents' => !empty($eventdata),
    'allowintervals' => !empty($activity->allowintervals),
    'allowcomments' => true,
    'totalcount' => count($references),
    'percent' => 0,
    'isreference' => true,
    'canmanage' => has_capability('mod/videoobserve:manageevents', $context),
    'manageurl' => (new moodle_url('/mod/videoobserve/manage.php', ['id' => $cm->id]))->out(false),
    'canreport' => has_capability('mod/videoobserve:viewreport', $context),
    'reporturl' => (new moodle_url('/mod/videoobserve/report.php', ['id' => $cm->id]))->out(false),
];
$jsconfig = [
    'cmid' => (int)$cm->id,
    'mode' => 'reference',
    'source' => $videoconfig,
    'lastposition' => 0,
    'resumeplayback' => false,
    'tracking' => false,
    'allowintervals' => !empty($activity->allowintervals),
    'allowcomments' => true,
    'strings' => [
        'deleteconfirm' => get_string('deletereferenceconfirm', 'videoobserve'),
        'trackingerror' => get_string('trackingerror', 'videoobserve'),
        'saveerror' => get_string('saveerror', 'videoobserve'),
        'intervalstarted' => get_string('intervalstarted', 'videoobserve'),
        'finishinterval' => get_string('finishinterval', 'videoobserve'),
        'startinterval' => get_string('startinterval', 'videoobserve'),
        'delete' => get_string('delete'),
    ],
];
$PAGE->requires->js_call_amd('mod_videoobserve/observer', 'init', [$jsconfig]);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_videoobserve/view', $data);
echo $OUTPUT->footer();
