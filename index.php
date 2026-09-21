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
 * index.php
 *
 * @package   mod_videoobserve
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$course = get_course($id);
require_course_login($course);
$coursecontext = context_course::instance($course->id);

$PAGE->set_url('/mod/videoobserve/index.php', ['id' => $course->id]);
$PAGE->set_title(get_string('modulenameplural', 'videoobserve'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($coursecontext);

$modinfo = get_fast_modinfo($course);
$instances = $modinfo->get_instances_of('videoobserve');
$table = new html_table();
$table->head = [get_string('name'), get_string('description')];
foreach ($instances as $cm) {
    if (!$cm->uservisible) {
        continue;
    }
    $activity = $DB->get_record('videoobserve', ['id' => $cm->instance]);
    if (!$activity) {
        continue;
    }
    $table->data[] = [
        html_writer::link(new moodle_url('/mod/videoobserve/view.php', ['id' => $cm->id]), format_string($activity->name)),
        format_module_intro('videoobserve', $activity, $cm->id),
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'videoobserve'));
if ($table->data) {
    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification(get_string('noactivities', 'videoobserve'), \core\output\notification::NOTIFY_INFO);
}
echo $OUTPUT->footer();
