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
 * manage.php
 *
 * @package   mod_videoobserve
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$eventid = optional_param('eventid', 0, PARAM_INT);
$cm = get_coursemodule_from_id('videoobserve', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videoobserve', ['id' => $cm->instance], '*', MUST_EXIST);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/videoobserve:manageevents', $context);

$PAGE->set_url('/mod/videoobserve/manage.php', ['id' => $cm->id]);
$PAGE->set_title(get_string('manageobservationsheet', 'videoobserve'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->navbar->add(format_string($activity->name), new moodle_url('/mod/videoobserve/view.php', ['id' => $cm->id]));
$PAGE->navbar->add(get_string('manageobservationsheet', 'videoobserve'));

$returnurl = new moodle_url('/mod/videoobserve/manage.php', ['id' => $cm->id]);

if ($action === 'delete' && $eventid && confirm_sesskey()) {
    $definition = $DB->get_record('videoobserve_eventtypes', [
        'id' => $eventid,
        'videoobserveid' => $activity->id,
    ], '*', MUST_EXIST);
    $used = $DB->record_exists('videoobserve_occurrences', ['eventtypeid' => $definition->id]);
    if ($used) {
        redirect($returnurl, get_string('eventinuse', 'videoobserve'), null, \core\output\notification::NOTIFY_ERROR);
    }
    $DB->delete_records('videoobserve_references', ['eventtypeid' => $definition->id]);
    $DB->delete_records('videoobserve_eventtypes', ['id' => $definition->id]);
    redirect($returnurl, get_string('eventdeleted', 'videoobserve'), null, \core\output\notification::NOTIFY_SUCCESS);
}

if ($action === 'new' || $action === 'edit') {
    $definition = (object)['id' => 0, 'cmid' => $cm->id, 'sortorder' => 0, 'required' => 0];
    if ($action === 'edit') {
        $definition = $DB->get_record('videoobserve_eventtypes', [
            'id' => $eventid,
            'videoobserveid' => $activity->id,
        ], '*', MUST_EXIST);
        $definition->cmid = $cm->id;
    } else {
        $max = $DB->get_field_sql('SELECT MAX(sortorder) FROM {videoobserve_eventtypes} WHERE videoobserveid = ?', [$activity->id]);
        $definition->sortorder = ((int)$max) + 10;
    }
    $form = new \mod_videoobserve\form\event_form(null, ['cmid' => $cm->id]);
    $form->set_data($definition);
    if ($form->is_cancelled()) {
        redirect($returnurl);
    }
    if ($data = $form->get_data()) {
        $now = time();
        if (!empty($data->id)) {
            $record = $DB->get_record('videoobserve_eventtypes', [
                'id' => $data->id,
                'videoobserveid' => $activity->id,
            ], '*', MUST_EXIST);
            $record->name = $data->name;
            $record->description = $data->description;
            $record->required = empty($data->required) ? 0 : 1;
            $record->sortorder = (int)$data->sortorder;
            $record->timemodified = $now;
            $DB->update_record('videoobserve_eventtypes', $record);
        } else {
            $DB->insert_record('videoobserve_eventtypes', (object)[
                'videoobserveid' => $activity->id,
                'name' => $data->name,
                'description' => $data->description,
                'required' => empty($data->required) ? 0 : 1,
                'sortorder' => (int)$data->sortorder,
                'timecreated' => $now,
                'timemodified' => $now,
            ]);
        }
        redirect($returnurl, get_string('eventsaved', 'videoobserve'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string($action === 'new' ? 'addevent' : 'editevent', 'videoobserve'));
    $form->display();
    echo $OUTPUT->footer();
    exit;
}

$definitions = $DB->get_records('videoobserve_eventtypes', ['videoobserveid' => $activity->id], 'sortorder ASC, id ASC');
$table = new html_table();
$table->head = [
    get_string('eventname', 'videoobserve'),
    get_string('eventdescription', 'videoobserve'),
    get_string('eventrequired', 'videoobserve'),
    get_string('sortorder', 'videoobserve'),
    get_string('actions'),
];
foreach ($definitions as $definition) {
    $editurl = new moodle_url('/mod/videoobserve/manage.php', [
        'id' => $cm->id, 'action' => 'edit', 'eventid' => $definition->id,
    ]);
    $deleteurl = new moodle_url('/mod/videoobserve/manage.php', [
        'id' => $cm->id, 'action' => 'delete', 'eventid' => $definition->id, 'sesskey' => sesskey(),
    ]);
    $actions = $OUTPUT->action_icon($editurl, new pix_icon('t/edit', get_string('edit'))) . ' ' .
        $OUTPUT->action_icon($deleteurl, new pix_icon('t/delete', get_string('delete')),
            new confirm_action(get_string('deleteeventconfirm', 'videoobserve')));
    $table->data[] = [
        format_string($definition->name, true, ['context' => $context]),
        format_text((string)$definition->description, FORMAT_PLAIN, ['context' => $context]),
        empty($definition->required) ? get_string('no') : get_string('yes'),
        (int)$definition->sortorder,
        $actions,
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manageobservationsheet', 'videoobserve'));
echo html_writer::div(get_string('manageobservationsheet_help', 'videoobserve'), 'alert alert-info');
echo $OUTPUT->single_button(new moodle_url('/mod/videoobserve/manage.php', ['id' => $cm->id, 'action' => 'new']),
    get_string('addevent', 'videoobserve'), 'get');
echo html_writer::link(new moodle_url('/mod/videoobserve/reference.php', ['id' => $cm->id]),
    get_string('managereference', 'videoobserve'), ['class' => 'btn btn-secondary ms-2']);
if ($definitions) {
    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification(get_string('noevents', 'videoobserve'), \core\output\notification::NOTIFY_INFO);
}
echo $OUTPUT->footer();
