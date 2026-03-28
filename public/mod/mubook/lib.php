<?php
// This file is part of MuTMS suite of plugins for Moodle™ LMS.
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

/**
 * Interactive book plugin core API.
 *
 * @package    mod_mubook
 * @copyright  2004 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_mubook\local\toc;
use mod_mubook\local\chapter;
use mod_mubook\local\markdown_formatter;

/**
 * Add book instance.
 *
 * @param stdClass $data
 * @param stdClass $mform
 * @return int new book instance id
 */
function mubook_add_instance($data, $mform) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;

    $numbering = toc::get_numbering_menu();
    if (!isset($data->numbering) || !isset($numbering[$data->numbering])) {
        $data->numbering = 1;
    }

    $menu = markdown_formatter::get_html_options();
    if (!isset($data->markdownhtml) || !isset($menu[$data->markdownhtml])) {
        $data->markdownhtml = markdown_formatter::HTML_STRIP;
    }

    $cman = \core\di::get(\mod_mubook\local\content_manager::class);
    $types = $cman->get_types_menu(true);
    if (!isset($data->contentdefault) || !isset($types[$data->contentdefault])) {
        $data->contentdefault = 'html';
    }

    $id = $DB->insert_record('mubook', $data);

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($data->coursemodule, 'mubook', $id, $completiontimeexpected);

    return $id;
}

/**
 * Update book instance.
 *
 * @param stdClass $data
 * @param stdClass $mform
 * @return bool true
 */
function mubook_update_instance($data, $mform) {
    global $DB;

    $data->id = $data->instance;
    $data->timemodified = time();

    if (property_exists($data, 'numbering')) {
        $numbering = toc::get_numbering_menu();
        if (!isset($numbering[$data->numbering])) {
            unset($data->numbering);
        }
    }

    if (property_exists($data, 'markdownhtml')) {
        $menu = markdown_formatter::get_html_options();
        if (!isset($menu[$data->markdownhtml])) {
            unset($data->markdownhtml);
        }
    }

    if (property_exists($data, 'contentdefault')) {
        $cman = \core\di::get(\mod_mubook\local\content_manager::class);
        $types = $cman->get_types_menu(true);
        if (!isset($types[$data->contentdefault])) {
            unset($data->contentdefault);
        }
    }

    $DB->update_record('mubook', $data);

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($data->coursemodule, 'mubook', $data->id, $completiontimeexpected);

    return true;
}

/**
 * Delete book instance by activity id.
 *
 * @param int $id
 * @return bool success
 */
function mubook_delete_instance($id) {
    global $DB;

    $mubook = $DB->get_record('mubook', ['id' => $id]);
    if (!$mubook) {
        return false;
    }
    $cm = get_coursemodule_from_instance('mubook', $mubook->id);
    $context = context_module::instance($cm->id);
    $cman = \core\di::get(\mod_mubook\local\content_manager::class);

    $chapterrecords = $DB->get_records('mubook_chapter', ['mubookid' => $mubook->id], 'sortorder DESC, id DESC');
    foreach ($chapterrecords as $chapterrecord) {
        $chapter = new \mod_mubook\local\chapter($chapterrecord, $mubook, $context);
        $contents = $DB->get_records('mubook_content', ['chapterid' => $chapterrecord->id], 'sortorder DESC, id DESC');
        foreach ($contents as $content) {
            $c = $cman->create_instance($content, $chapter);
            $c->delete();
        }
        \mod_mubook\local\chapter::delete($chapter->id, true);
    }

    \core_completion\api::update_completion_date_event($cm->id, 'mubook', $id, null);

    $DB->delete_records('mubook', ['id' => $mubook->id]);

    return true;
}

/**
 * Supported features.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return bool|int True if module supports feature, false if not, null if doesn't know or string for the module purpose.
 */
function mubook_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_CONTENT;

        default:
            return null;
    }
}

/**
 * Return a list of page types
 *
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 * @return array
 */
function mubook_page_type_list($pagetype, $parentcontext, $currentcontext) {
    return [
        'mod-mubook-*' => get_string('page-mod-mubook-x', 'mod_mubook'),
        'mod-mubook-viewchapter' => get_string('page-mod-mubook-viewchapter', 'mod_mubook'),
        'mod-mubook-viewall' => get_string('page-mod-mubook-viewall', 'mod_mubook'),
    ];
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @param int $userid User id to use for all capability checks, etc. Set to 0 for current user (default).
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_mubook_core_calendar_provide_event_action(
    calendar_event $event,
    \core_calendar\action_factory $factory,
    int $userid = 0
) {
    global $USER;
    if (!$userid) {
        $userid = $USER->id;
    }

    $cm = get_fast_modinfo($event->courseid, $userid)->instances['mubook'][$event->instance];
    if (!$cm->uservisible) {
        return null;
    }

    $context = context_module::instance($cm->id);
    if (!has_capability('mod/mubook:view', $context, $userid)) {
        return null;
    }

    $completion = new \completion_info($cm->get_course());
    $completiondata = $completion->get_data($cm, false, $userid);

    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    return $factory->create_instance(
        get_string('view'),
        new \core\url('/mod/mubook/view.php', ['id' => $cm->id]),
        1,
        true
    );
}

/**
 * Get icon mapping for font-awesome.
 */
function mod_mubook_get_fontawesome_icon_map() {
    return [
        'mod_mubook:chapter' => 'fa-regular fa-file-lines',
        'mod_mubook:content_create' => 'fa-solid fa-plus',
        'mod_mubook:subchapter' => 'fa-regular fa-file',
        'mod_mubook:toc' => 'fa-regular fa-map',
        'mod_mubook:viewall' => 'fa-solid fa-print',
    ];
}

/**
 * Serves the mubook attachments.
 *
 * @param stdClass $course course object
 * @param cm_info $cm course module object
 * @param context $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload hint to force download
 * @param array $options additional options affecting the file serving
 * @return void|bool false if file not found, does not return if found - just send the file
 */
function mubook_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB;

    require_course_login($course, true, $cm);

    if (!$context instanceof context_module) {
        return false;
    }

    $contentid = (int)array_shift($args);

    $cman = \core\di::get(\mod_mubook\local\content_manager::class);

    $contentrecord = $DB->get_record('mubook_content', ['id' => $contentid]);
    if (!$contentrecord) {
        return false;
    }

    $chapterrecord = $DB->get_record('mubook_chapter', ['id' => $contentrecord->chapterid]);
    if (!$chapterrecord) {
        return false;
    }

    $mubook = $DB->get_record('mubook', ['id' => $chapterrecord->mubookid]);
    if (!$mubook || $mubook->id != $cm->instance) {
        return false;
    }

    $contentclass = $cman->get_class($contentrecord->type);
    if (!$contentclass) {
        return false;
    }
    if (!in_array($filearea, $contentclass::get_file_areas())) {
        return false;
    }

    $chapter = new chapter($chapterrecord, $mubook, $context);
    $content = $cman->create_instance($contentrecord, $chapter);

    if (!$content->can_view()) {
        return false;
    }

    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/mod_mubook/$filearea/$contentid/$relativepath";

    // Let the content send the file.
    $content->send_file($fullpath, $forcedownload, $options);
}
