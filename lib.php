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
 * Programs plugin lib functions.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Program file serving support.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return void
 */
function tool_muprog_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB;

    if ($context->contextlevel != CONTEXT_SYSTEM && $context->contextlevel != CONTEXT_COURSECAT) {
        send_file_not_found();
    }

    if ($filearea !== 'description' && $filearea !== 'image') {
        send_file_not_found();
    }

    $programid = (int)array_shift($args);

    $program = $DB->get_record('tool_muprog_program', ['id' => $programid]);
    if (!$program) {
        send_file_not_found();
    }
    if (
        !has_capability('tool/muprog:view', $context)
        && !\tool_muprog\local\catalogue::is_program_visible($program)
    ) {
        send_file_not_found();
    }

    $filename = array_pop($args);
    $filepath = implode('/', $args) . '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'tool_muprog', $filearea, $programid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        send_file_not_found();
    }

    send_stored_file($file, 60 * 60, 0, $forcedownload, $options);
}

/**
 * Hook called before a course category is deleted.
 *
 * @param \stdClass $category The category record.
 */
function tool_muprog_pre_course_category_delete(\stdClass $category) {
    \tool_muprog\local\program::pre_course_category_delete($category);
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @param int $userid ID override for calendar events
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function tool_muprog_core_calendar_provide_event_action(
    calendar_event $event,
    \core_calendar\action_factory $factory,
    int $userid = 0
) {

    global $DB;

    // The event object (core_calendar\local\event\entities\event)
    // passed does not include an instance property so we need to pull the DB record.
    $event = $DB->get_record('event', ['id' => $event->id], '*', MUST_EXIST);
    $allocation = $DB->get_record('tool_muprog_allocation', ['id' => $event->instance]);
    if (!$allocation) {
        return null;
    }

    return $factory->create_instance(
        get_string('view'),
        new \moodle_url('/admin/tool/muprog/my/program.php', ['id' => $allocation->programid]),
        1,
        true
    );
}

/**
 * Add nodes to myprofile page.
 *
 * @param \core_user\output\myprofile\tree $tree Tree object
 * @param stdClass $user user object
 * @param bool $iscurrentuser
 * @param stdClass $course Course object
 */
function tool_muprog_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    global $USER;

    if (!\tool_muprog\local\util::is_muprog_active()) {
        return;
    }

    if ($USER->id == $user->id) {
        $link = get_string('myprograms', 'tool_muprog');
        $url = new moodle_url('/admin/tool/muprog/my/index.php');
        $node = new core_user\output\myprofile\node('miscellaneous', 'enrolprograms_programs', $link, null, $url);
        $tree->add_node($node);
    }
}

/**
 * Map icons for font-awesome themes.
 */
function tool_muprog_get_fontawesome_icon_map() {
    return [
        'tool_muprog:appenditem' => 'fa-plus-square',
        'tool_muprog:catalogue' => 'fa-cubes',
        'tool_muprog:deleteitem' => 'fa-trash-o',
        'tool_muprog:import' => 'fa-copy',
        'tool_muprog:itemcourse' => 'fa-graduation-cap',
        'tool_muprog:itemset' => 'fa-list',
        'tool_muprog:itemtop' => 'fa-cubes',
        'tool_muprog:itemtraining' => 'fa-ellipsis-h',
        'tool_muprog:move' => 'fa-arrows',
        'tool_muprog:program' => 'fa-cubes',
        'tool_muprog:myprograms' => 'fa-cubes',
        'tool_muprog:requestapprove' => 'fa-check-square-o',
        'tool_muprog:requestreject' => 'fa-times-rectangle-o',
    ];
}

/**
 * Returns programs tagged with a specified tag.
 *
 * @param core_tag_tag $tag
 * @param bool $exclusivemode if set to true it means that no other entities tagged
 *      with this tag are displayed on the page and the per-page limit may be bigger
 * @param int $fromctx context id where the link was displayed, may be used by callbacks
 *      to display items in the same context first
 * @param int $ctx context id where to search for records
 * @param bool $rec search in subcontexts as well
 * @param int $page 0-based number of page being displayed
 * @return core_tag\output\tagindex
 */
function tool_muprog_get_tagged_programs($tag, $exclusivemode = false, $fromctx = 0, $ctx = 0, $rec = 1, $page = 0) {
    // NOTE: When learners browse programs we ignore the contexts, programs have a flat structure,
    // then only complication here may be multi-tenancy.

    $perpage = $exclusivemode ? 20 : 5;

    $result = \tool_muprog\local\catalogue::get_tagged_programs($tag->id, $exclusivemode, $page * $perpage, $perpage);

    $content = $result['content'];
    $totalpages = ceil($result['totalcount'] / $perpage);

    return new core_tag\output\tagindex(
        $tag,
        'tool_muprog',
        'tool_muprog_program',
        $content,
        $exclusivemode,
        0,
        0,
        1,
        $page,
        $totalpages
    );
}

/**
 * This function extends the category navigation with programs.
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param context $coursecategorycontext The context of the course category
 */
function tool_muprog_extend_navigation_category_settings($navigation, $coursecategorycontext): void {
    if (!has_capability('tool/muprog:view', $coursecategorycontext)) {
        return;
    }

    // NOTE: catnav is added to unbreak breadcrums on management pages.
    $settingsnode = navigation_node::create(
        get_string('programs', 'tool_muprog'),
        new moodle_url('/admin/tool/muprog/management/index.php', ['contextid' => $coursecategorycontext->id, 'catnav' => 1]),
        navigation_node::TYPE_CUSTOM,
        null,
        'tool_muprog_programs'
    );
    $settingsnode->set_force_into_more_menu(true);
    $navigation->add_node($settingsnode);
}
