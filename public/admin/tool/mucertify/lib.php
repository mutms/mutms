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
 * Certification plugin lib functions.
 *
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Certifications file serving support.
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
function tool_mucertify_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB;

    if ($context->contextlevel != CONTEXT_SYSTEM) {
        send_file_not_found();
    }

    if ($filearea !== 'description' && $filearea !== 'image') {
        send_file_not_found();
    }

    $certificationid = (int)array_shift($args);
    $filename = array_pop($args);
    $filepath = implode('/', $args) . '/';

    if ($context->contextlevel == CONTEXT_SYSTEM && $filename === 'geopattern.svg' && $filepath === '/') {
        $geopattern = \tool_mucertify\local\certification::get_image_geopattern($certificationid);
        send_file($geopattern->toSVG(), $filename, 60 * 60 * 24 * 7, 0, true, false);
    }

    $certification = $DB->get_record('tool_mucertify_certification', ['id' => $certificationid]);
    if (!$certification) {
        send_file_not_found();
    }
    $certificationcontext = context::instance_by_id($certification->contextid);
    if (
        !has_capability('tool/mucertify:view', $certificationcontext)
        && !\tool_mucertify\local\catalogue::is_certification_visible($certification)
    ) {
        send_file_not_found();
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'tool_mucertify', $filearea, $certificationid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        send_file_not_found();
    }

    send_stored_file($file, 60 * 60, 0, $forcedownload, $options);
}

/**
 * Add nodes to myprofile page.
 *
 * @param \core_user\output\myprofile\tree $tree Tree object
 * @param stdClass $user user object
 * @param bool $iscurrentuser
 * @param stdClass $course Course object
 */
function tool_mucertify_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    global $USER;

    if (!\tool_mulib\local\mulib::is_mucertify_active()) {
        return;
    }

    $usercontext = context_user::instance($user->id);

    if ($USER->id == $user->id || has_capability('tool/mucertify:viewusercertifications', $usercontext)) {
        $url = new \core\url('/admin/tool/mucertify/my/index.php');
        if ($USER->id == $user->id) {
            $link = get_string('mycertifications', 'tool_mucertify');
        } else {
            $link = get_string('certifications', 'tool_mucertify');
            $url->param('userid', $user->id);
        }
        $node = new core_user\output\myprofile\node('miscellaneous', 'toolcertify_certifications', $link, null, $url);
        $tree->add_node($node);
    }
}

/**
 * Hook called before a course category is deleted.
 *
 * @param \stdClass $category The category record.
 */
function tool_mucertify_pre_course_category_delete(\stdClass $category) {
    \tool_mucertify\local\certification::pre_course_category_delete($category);
}

/**
 * Map icons for font-awesome themes.
 */
function tool_mucertify_get_fontawesome_icon_map() {
    return [
        'tool_mucertify:catalogue' => 'fa-cubes',
        'tool_mucertify:certification' => 'fa-certificate',
        'tool_mucertify:mycertifications' => 'fa-certificate',
        'tool_mucertify:requestapprove' => 'fa-check-square-o',
        'tool_mucertify:requestreject' => 'fa-times-rectangle-o',
    ];
}

/**
 * Returns certifications tagged with a specified tag.
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
function tool_mucertify_get_tagged_certifications($tag, $exclusivemode = false, $fromctx = 0, $ctx = 0, $rec = 1, $page = 0) {
    // NOTE: When learners browse certifications we ignore the contexts, certifications have a flat structure,
    // then only complication here may be multi-tenancy.

    $perpage = $exclusivemode ? 20 : 5;

    $result = \tool_mucertify\local\catalogue::get_tagged_certifications($tag->id, $exclusivemode, $page * $perpage, $perpage);

    $content = $result['content'];
    $totalpages = ceil($result['totalcount'] / $perpage);

    return new core_tag\output\tagindex(
        $tag,
        'tool_mucertify',
        'tool_mucertify_certification',
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
 * This function extends the category navigation with certifications.
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param context $coursecategorycontext The context of the course category
 */
function tool_mucertify_extend_navigation_category_settings($navigation, $coursecategorycontext): void {
    if (!has_capability('tool/mucertify:view', $coursecategorycontext)) {
        return;
    }

    // NOTE: catnav is added to unbreak breadcrums on management pages.
    $settingsnode = navigation_node::create(
        get_string('certifications', 'tool_mucertify'),
        new \core\url('/admin/tool/mucertify/management/index.php', ['contextid' => $coursecategorycontext->id, 'catnav' => 1]),
        navigation_node::TYPE_CUSTOM,
        null,
        'tool_mucertify_certifications'
    );
    $settingsnode->set_force_into_more_menu(true);
    $navigation->add_node($settingsnode);
}
