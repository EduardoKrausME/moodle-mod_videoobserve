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
 * backup_videoobserve_activity_task.class.php
 *
 * @package   mod_videoobserve
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/videoobserve/backup/moodle2/backup_videoobserve_stepslib.php');

/**
 * Backup task for Video Observation.
 */
class backup_videoobserve_activity_task extends backup_activity_task {

    /**
     * Method define_my_settings.
     *
     * @return mixed Return value.
     */
    protected function define_my_settings() {
    }

    /**
     * Method define_my_steps.
     *
     * @return mixed Return value.
     */
    protected function define_my_steps() {
        $this->add_step(new backup_videoobserve_activity_structure_step(
            'videoobserve_structure',
            'videoobserve.xml'
        ));
    }

    /**
     * Method encode_content_links.
     *
     * @param mixed $content Parameter content.
     * @return mixed Return value.
     */
    public static function encode_content_links($content) {
        global $CFG;
        $base = preg_quote($CFG->wwwroot, '#');
        $content = preg_replace('#' . $base . '/mod/videoobserve/view\.php\?id=([0-9]+)#',
            '$@VIDEOOBSERVEVIEWBYID*$1@$', $content);
        return $content;
    }
}
