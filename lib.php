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
 * Core callbacks for Video Observation.
 *
 * @package   mod_videoobserve
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Declare supported Moodle features.
 *
 * @param string $feature Feature constant.
 * @return bool|null
 */
function videoobserve_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_OTHER;
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_ASSESSMENT;
        default:
            return null;
    }
}

/**
 * Add a Video Observation instance.
 *
 * @param stdClass $data Form data.
 * @param mod_videoobserve_mod_form|null $mform Form instance.
 * @return int
 */
function videoobserve_add_instance(stdClass $data, ?mod_videoobserve_mod_form $mform = null): int {
    global $DB;

    $now = time();
    $data->timecreated = $now;
    $data->timemodified = $now;
    videoobserve_normalise_record($data);
    $id = $DB->insert_record('videoobserve', $data);
    $data->id = $id;
    videoobserve_save_files($data);
    return $id;
}

/**
 * Update a Video Observation instance.
 *
 * @param stdClass $data Form data.
 * @param mod_videoobserve_mod_form|null $mform Form instance.
 * @return bool
 */
function videoobserve_update_instance(stdClass $data, ?mod_videoobserve_mod_form $mform = null): bool {
    global $DB;

    $data->id = $data->instance;
    $data->timemodified = time();
    videoobserve_normalise_record($data);
    $result = $DB->update_record('videoobserve', $data);
    videoobserve_save_files($data);
    return $result;
}

/**
 * Normalise values saved from the activity form.
 *
 * @param stdClass $data Form data.
 * @return void
 */
function videoobserve_normalise_record(stdClass $data): void {
    $sources = ['upload', 'url', 'youtube', 'vimeo'];
    if (!in_array($data->videosource ?? '', $sources, true)) {
        $data->videosource = 'upload';
    }
    if ($data->videosource === 'upload') {
        $data->videourl = '';
    } else {
        $data->videourl = trim((string)($data->videourl ?? ''));
    }
    $data->resumeplayback = empty($data->resumeplayback) ? 0 : 1;
    $data->allowintervals = empty($data->allowintervals) ? 0 : 1;
    $data->allowcomments = empty($data->allowcomments) ? 0 : 1;
    $data->showreference = empty($data->showreference) ? 0 : 1;
    $data->referencetolerance = max(0, min(300, (int)($data->referencetolerance ?? 5)));
    $data->completionpercent = max(0, min(100, (int)($data->completionpercent ?? 0)));
    $data->completionoccurrences = max(0, (int)($data->completionoccurrences ?? 0));
}

/**
 * Save uploaded video and poster files from draft areas.
 *
 * @param stdClass $data Activity record plus draft item ids.
 * @return void
 */
function videoobserve_save_files(stdClass $data): void {
    if (empty($data->coursemodule)) {
        $cm = get_coursemodule_from_instance('videoobserve', $data->id, $data->course, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }
        $data->coursemodule = $cm->id;
    }

    $context = context_module::instance((int)$data->coursemodule);
    if (isset($data->video)) {
        file_save_draft_area_files((int)$data->video, $context->id, 'mod_videoobserve', 'video', 0, [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['video'],
        ]);
    }
    if (isset($data->poster)) {
        file_save_draft_area_files((int)$data->poster, $context->id, 'mod_videoobserve', 'poster', 0, [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['image'],
        ]);
    }
}

/**
 * Delete an activity and all related data.
 *
 * @param int $id Activity id.
 * @return bool
 */
function videoobserve_delete_instance(int $id): bool {
    global $DB;

    $activity = $DB->get_record('videoobserve', ['id' => $id]);
    if (!$activity) {
        return false;
    }

    $cm = get_coursemodule_from_instance('videoobserve', $id, $activity->course, false, IGNORE_MISSING);
    if ($cm) {
        $context = context_module::instance($cm->id);
        get_file_storage()->delete_area_files($context->id, 'mod_videoobserve');
    }

    $transaction = $DB->start_delegated_transaction();
    $DB->delete_records('videoobserve_occurrences', ['videoobserveid' => $id]);
    $DB->delete_records('videoobserve_references', ['videoobserveid' => $id]);
    $DB->delete_records('videoobserve_progress', ['videoobserveid' => $id]);
    $DB->delete_records('videoobserve_eventtypes', ['videoobserveid' => $id]);
    $DB->delete_records('videoobserve', ['id' => $id]);
    $transaction->allow_commit();
    return true;
}

/**
 * Serve uploaded video and poster files.
 *
 * @param stdClass $course Course record.
 * @param stdClass $cm Course module record.
 * @param context $context Context.
 * @param string $filearea File area.
 * @param array $args File path args.
 * @param bool $forcedownload Force download.
 * @param array $options File options.
 * @return bool
 */
function mod_videoobserve_pluginfile($course, $cm, $context, string $filearea, array $args,
                                     bool $forcedownload, array $options = []): bool {
    if ($context->contextlevel !== CONTEXT_MODULE || !in_array($filearea, ['video', 'poster'], true)) {
        return false;
    }
    require_login($course, true, $cm);
    require_capability('mod/videoobserve:view', $context);

    $itemid = (int)array_shift($args);
    if ($itemid !== 0) {
        return false;
    }
    $filename = array_pop($args);
    $filepath = '/' . ($args ? implode('/', $args) . '/' : '');
    $file = get_file_storage()->get_file($context->id, 'mod_videoobserve', $filearea, 0, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Return file areas.
 *
 * @param stdClass $course Course record.
 * @param stdClass $cm Course module record.
 * @param context $context Context.
 * @return array
 */
function videoobserve_get_file_areas($course, $cm, $context): array {
    return [
        'video' => get_string('videofile', 'videoobserve'),
        'poster' => get_string('poster', 'videoobserve'),
    ];
}

/**
 * Build cached course module information.
 *
 * @param stdClass $cm Course module record.
 * @return cached_cm_info|null
 */
function videoobserve_get_coursemodule_info(stdClass $cm): ?cached_cm_info {
    global $DB;

    $activity = $DB->get_record('videoobserve', ['id' => $cm->instance],
        'id,name,intro,introformat,completionpercent,completionoccurrences');
    if (!$activity) {
        return null;
    }
    $info = new cached_cm_info();
    $info->name = $activity->name;
    if ($cm->showdescription) {
        $info->content = format_module_intro('videoobserve', $activity, $cm->id, false);
    }
    if ((int)$cm->completion === COMPLETION_TRACKING_AUTOMATIC) {
        $info->customdata['customcompletionrules'] = [
            'completionpercent' => (int)$activity->completionpercent,
            'completionoccurrences' => (int)$activity->completionoccurrences,
        ];
    }
    return $info;
}

/**
 * Active completion rule descriptions.
 *
 * @param cached_cm_info $cm Course module info.
 * @return array
 */
function videoobserve_get_completion_active_rule_descriptions(cached_cm_info $cm): array {
    if ((int)$cm->completion !== COMPLETION_TRACKING_AUTOMATIC || empty($cm->customdata['customcompletionrules'])) {
        return [];
    }
    $rules = $cm->customdata['customcompletionrules'];
    $descriptions = [];
    if (!empty($rules['completionpercent'])) {
        $descriptions[] = get_string('completiondetail:percent', 'videoobserve', $rules['completionpercent']);
    }
    if (!empty($rules['completionoccurrences'])) {
        $descriptions[] = get_string('completiondetail:occurrences', 'videoobserve', $rules['completionoccurrences']);
    }
    return $descriptions;
}

/**
 * Legacy completion callback.
 *
 * @param stdClass $course Course record.
 * @param stdClass $cm Course module record.
 * @param int $userid User id.
 * @param bool $type Expected state.
 * @return bool
 */
function videoobserve_get_completion_state($course, $cm, int $userid, bool $type): bool {
    global $DB;

    $activity = $DB->get_record('videoobserve', ['id' => $cm->instance], '*', MUST_EXIST);
    $progress = $DB->get_record('videoobserve_progress', [
        'videoobserveid' => $activity->id,
        'userid' => $userid,
    ]);
    $percentok = (int)$activity->completionpercent <= 0 ||
        ($progress && (float)$progress->percent >= (int)$activity->completionpercent);
    $occurrences = $DB->count_records('videoobserve_occurrences', [
        'videoobserveid' => $activity->id,
        'userid' => $userid,
    ]);
    $occurrenceok = (int)$activity->completionoccurrences <= 0 ||
        $occurrences >= (int)$activity->completionoccurrences;
    return $percentok && $occurrenceok;
}
