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

use tool_mutenancy\local\tenancy;
use tool_mutenancy\local\tenant;

/**
 * Multi-tenancy lib functions.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * This function extends the category navigation.
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param context $coursecategorycontext The context of the course category
 */
function tool_mutenancy_extend_navigation_category_settings($navigation, $coursecategorycontext): void {
    if (!tenancy::is_active()) {
        return;
    }

    if (!$coursecategorycontext->tenantid) {
        return;
    }

    $tenantcontext = context_tenant::instance($coursecategorycontext->tenantid);
    if (!has_capability('tool/mutenancy:view', $tenantcontext)) {
        return;
    }

    // NOTE: catnav is added to unbreak explicit breadcrums.
    $settingsnode = navigation_node::create(
        get_string('tenant', 'tool_mutenancy'),
        new \core\url('/admin/tool/mutenancy/tenant.php', ['id' => $tenantcontext->instanceid, 'catnav' => 1]),
        navigation_node::TYPE_CUSTOM,
        null,
        'tool_mutenancy_tenant'
    );
    $settingsnode->set_force_into_more_menu(true);
    $navigation->add_node($settingsnode);
}

/**
 * Show tenant membership and association on user profile pages.
 *
 * @param \core_user\output\myprofile\tree $tree
 * @param stdClass $user
 * @param bool $iscurrentuser
 * @param stdClass|null $course
 */
function tool_mutenancy_myprofile_navigation(\core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course): void {
    global $DB, $OUTPUT, $USER;

    if (!tenancy::is_active()) {
        return;
    }

    $syscontext = context_system::instance();
    $usercontext = context_user::instance($user->id);
    $canview = false;
    if (has_capability('moodle/user:viewalldetails', $usercontext)) {
        $canview = true;
    } else if ($course) {
        $coursecontext = context_course::instance($course->id);
        if (has_capability('moodle/user:viewhiddendetails', $coursecontext)) {
            $canview = true;
        }
    }

    if (!$canview) {
        return;
    }

    if ($user->tenantid) {
        $tenant = $DB->get_record('tool_mutenancy_tenant', ['id' => $user->tenantid]);
        if ($tenant) {
            $name = format_string($tenant->name);
            $tenantcontext = context_tenant::instance($tenant->id);
            if (has_capability('tool/mutenancy:view', $tenantcontext)) {
                $url = new \core\url('/admin/tool/mutenancy/tenant.php', ['id' => $tenant->id]);
                $name = html_writer::link($url, $name);
            }
        } else {
            $name = get_string('error');
        }
    } else {
        $name = get_string('no');
    }

    $allocate = '';
    if (!is_siteadmin($user->id) && $USER->id != $user->id) {
        $tcount = $DB->count_records('tool_mutenancy_tenant', []);
        if ($tcount && has_capability('tool/mutenancy:allocate', $syscontext)) {
            $url = new \core\url('/admin/tool/mutenancy/management/user_allocate.php', ['id' => $user->id]);
            $link = new \tool_mulib\output\ajax_form\icon($url, get_string('user_allocate', 'tool_mutenancy'), 'i/switch');
            $allocate = $OUTPUT->render($link);
        }
    }
    $tree->add_node(new core_user\output\myprofile\node(
        'contact',
        'tenant',
        tenancy::get_tenant_string('tenant_member'),
        null,
        null,
        $name . $allocate
    ));

    if (!$user->tenantid) {
        $tenants = \tool_mutenancy\local\user::get_associated_tenants($user->id);
        if ($tenants) {
            $list = [];
            foreach ($tenants as $tenant) {
                $name = format_string($tenant->name);
                $tenantcontext = context_tenant::instance($tenant->id);
                if (has_capability('tool/mutenancy:view', $tenantcontext)) {
                    $url = new \core\url('/admin/tool/mutenancy/tenant.php', ['id' => $tenant->id]);
                    $name = html_writer::link($url, $name);
                }
                $list[] = $name;
            }
            $tree->add_node(new core_user\output\myprofile\node(
                'contact',
                'associatedtenants',
                tenancy::get_tenants_string('user_tenants'),
                null,
                null,
                implode(', ', $list)
            ));
        }
    }
}

/**
 * Allow users with switch capability to change current tenant.
 *
 * @param renderer_base $renderer
 * @return string
 */
function tool_mutenancy_render_navbar_output(renderer_base $renderer): string {
    if (!tenancy::can_switch()) {
        return '';
    }

    $url = new \core\url('/admin/tool/mutenancy/tenant_switch.php');
    $icon = new \tool_mulib\output\ajax_form\icon(
        $url,
        tenancy::get_tenant_string('tenant_switch'),
        'switch',
        'tool_mutenancy'
    );
    $icon->set_form_size('sm');
    $icon->set_classes(['nav-link', 'icon-no-margin']); // Use the same styling as notification.

    return $renderer->render($icon);
}

/**
 * Icons definition.
 */
function tool_mutenancy_get_fontawesome_icon_map() {
    return [
        'tool_mutenancy:switch' => 'fa-sitemap',
    ];
}
