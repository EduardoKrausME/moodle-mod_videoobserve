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
 * Activity configuration form.
 *
 * @package   mod_videoobserve
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Video Observation module form.
 */
class mod_videoobserve_mod_form extends moodleform_mod {
    /**
     * Define activity fields.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('videoobservename', 'videoobserve'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $this->standard_intro_elements();

        $mform->addElement('html', '<h3>' . get_string('videoheader', 'videoobserve') . '</h3>');
        $mform->addElement('select', 'videosource', get_string('videosource', 'videoobserve'), [
            'upload' => get_string('sourceupload', 'videoobserve'),
            'url' => get_string('sourceurl', 'videoobserve'),
            'youtube' => get_string('sourceyoutube', 'videoobserve'),
            'vimeo' => get_string('sourcevimeo', 'videoobserve'),
        ]);
        $mform->setDefault('videosource', 'upload');

        $mform->addElement('filemanager', 'video', get_string('videofile', 'videoobserve'), null, [
            'subdirs' => 0,
            'accepted_types' => ['video'],
        ]);
        $mform->hideIf('video', 'videosource', 'neq', 'upload');

        $mform->addElement('text', 'videourl', get_string('videourl', 'videoobserve'), ['size' => 80]);
        $mform->setType('videourl', PARAM_RAW_TRIMMED);
        $mform->hideIf('videourl', 'videosource', 'eq', 'upload');

        $mform->addElement('filemanager', 'poster', get_string('poster', 'videoobserve'), null, [
            'subdirs' => 0,
            'accepted_types' => ['image'],
        ]);

        $mform->addElement('advcheckbox', 'resumeplayback', get_string('resumeplayback', 'videoobserve'));
        $mform->setDefault('resumeplayback', 1);

        $mform->addElement('html', '<h3>' . get_string('observationsettings', 'videoobserve') . '</h3>');
        $mform->addElement('advcheckbox', 'allowintervals', get_string('allowintervals', 'videoobserve'));
        $mform->setDefault('allowintervals', 1);
        $mform->addElement('advcheckbox', 'allowcomments', get_string('allowcomments', 'videoobserve'));
        $mform->setDefault('allowcomments', 1);
        $mform->addElement('text', 'referencetolerance', get_string('referencetolerance', 'videoobserve'), ['size' => 8]);
        $mform->setType('referencetolerance', PARAM_INT);
        $mform->setDefault('referencetolerance', 5);
        $mform->addHelpButton('referencetolerance', 'referencetolerance', 'videoobserve');
        $mform->addElement('advcheckbox', 'showreference', get_string('showreference', 'videoobserve'));
        $mform->setDefault('showreference', 0);

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Prepare file draft areas while editing.
     *
     * @param array $defaultvalues Default values.
     * @return void
     */
    public function data_preprocessing(&$defaultvalues): void {
        if (empty($this->current->id) || empty($this->_cm)) {
            return;
        }
        $context = context_module::instance($this->_cm->id);
        $draftvideo = file_get_submitted_draft_itemid('video');
        file_prepare_draft_area($draftvideo, $context->id, 'mod_videoobserve', 'video', 0, [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['video'],
        ]);
        $defaultvalues['video'] = $draftvideo;

        $draftposter = file_get_submitted_draft_itemid('poster');
        file_prepare_draft_area($draftposter, $context->id, 'mod_videoobserve', 'poster', 0, [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['image'],
        ]);
        $defaultvalues['poster'] = $draftposter;
    }

    /**
     * Validate source-specific fields.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $source = $data['videosource'] ?? 'upload';
        if ($source === 'upload') {
            $draftid = (int)($data['video'] ?? 0);
            $draftfiles = $draftid ? file_get_drafarea_files($draftid) : null;
            if (!$draftfiles || empty($draftfiles->filecount)) {
                $errors['video'] = get_string('requiredvideofile', 'videoobserve');
            }
        } else if (trim((string)($data['videourl'] ?? '')) === '') {
            $errors['videourl'] = get_string('requiredsourceurl', 'videoobserve');
        }
        if ($source === 'url' && !filter_var((string)$data['videourl'], FILTER_VALIDATE_URL)) {
            $errors['videourl'] = get_string('invalidurl', 'videoobserve');
        }
        if ($source === 'youtube') {
            $value = trim((string)($data['videourl'] ?? ''));
            if ($value !== '' && !preg_match('/^[A-Za-z0-9_-]{11}$/', $value)
                && !preg_match('~(?:youtu\.be/|youtube(?:-nocookie)?\.com/)~i', $value)) {
                $errors['videourl'] = get_string('invalidyoutube', 'videoobserve');
            }
        }
        if ($source === 'vimeo') {
            $value = trim((string)($data['videourl'] ?? ''));
            if ($value !== '' && !preg_match('/^\d+$/', $value) && !preg_match('~vimeo\.com/~i', $value)) {
                $errors['videourl'] = get_string('invalidvimeo', 'videoobserve');
            }
        }
        if (isset($data['referencetolerance']) &&
            ((int)$data['referencetolerance'] < 0 ||
                (int)$data['referencetolerance'] > 300)) {
            $errors['referencetolerance'] = get_string('invalidtolerance', 'videoobserve');
        }
        foreach (['video', 'poster'] as $field) {
            $draftid = (int)($data[$field] ?? 0);
            if ($draftid > 0) {
                $draftinfo = file_get_draft_area_info($draftid);
                if ((int)$draftinfo['filecount'] > 1) {
                    $errors[$field] = get_string('errormaxfiles', 'videoobserve');
                }
            }
        }
        return $errors;
    }

    /**
     * Add custom completion rules.
     *
     * @return array
     */
    public function add_completion_rules(): array {
        $mform = $this->_form;
        $mform->addElement('text', 'completionpercent', get_string('completionpercent', 'videoobserve'), ['size' => 5]);
        $mform->setType('completionpercent', PARAM_INT);
        $mform->setDefault('completionpercent', 80);
        $mform->addHelpButton('completionpercent', 'completionpercent', 'videoobserve');

        $mform->addElement('text', 'completionoccurrences', get_string('completionoccurrences', 'videoobserve'), ['size' => 5]);
        $mform->setType('completionoccurrences', PARAM_INT);
        $mform->setDefault('completionoccurrences', 1);
        $mform->addHelpButton('completionoccurrences', 'completionoccurrences', 'videoobserve');
        return ['completionpercent', 'completionoccurrences'];
    }

    /**
     * Whether custom completion rules have values.
     *
     * @param array $data Form data.
     * @return bool
     */
    public function completion_rule_enabled($data): bool {
        return !empty($data['completionpercent']) || !empty($data['completionoccurrences']);
    }
}
