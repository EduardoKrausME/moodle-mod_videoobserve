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
 * AJAX web service functions.
 *
 * @package   mod_videoobserve
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_videoobserve_save_occurrence' => [
        'classname' => 'mod_videoobserve\\external\\save_occurrence',
        'methodname' => 'execute',
        'description' => 'Save an observation occurrence for the current user.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/videoobserve:view',
    ],
    'mod_videoobserve_delete_occurrence' => [
        'classname' => 'mod_videoobserve\\external\\delete_occurrence',
        'methodname' => 'execute',
        'description' => 'Delete an observation occurrence owned by the current user.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/videoobserve:view',
    ],
    'mod_videoobserve_save_reference' => [
        'classname' => 'mod_videoobserve\\external\\save_reference',
        'methodname' => 'execute',
        'description' => 'Save a teacher reference occurrence.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/videoobserve:managereference',
    ],
    'mod_videoobserve_delete_reference' => [
        'classname' => 'mod_videoobserve\\external\\delete_reference',
        'methodname' => 'execute',
        'description' => 'Delete a teacher reference occurrence.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/videoobserve:managereference',
    ],
    'mod_videoobserve_update_progress' => [
        'classname' => 'mod_videoobserve\\external\\update_progress',
        'methodname' => 'execute',
        'description' => 'Update watched segments, duration and last video position.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/videoobserve:view',
    ],
];
