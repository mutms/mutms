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

namespace tool_mutenancy\local;

use stdClass;

/**
 * Primary navigation helper.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class navigation {
    /**
     * Primary navigation hook callback.
     *
     * @param \core\hook\navigation\primary_extend $hook
     */
    public static function primary_extend(\core\hook\navigation\primary_extend $hook): void {
        if (!tenancy::is_active()) {
            return;
        }

        if (!get_config('tool_mutenancy', 'tenantprimarynav')) {
            return;
        }
        $tenantid = tenancy::get_current_tenantid();
        if (!$tenantid) {
            // Use normal site administration for now.
            return;
        }

        /** @var \context_tenant $tenantcontext */
        $tenantcontext = \context_tenant::instance($tenantid);
        if (!has_capability('tool/mutenancy:view', $tenantcontext)) {
            return;
        }
        $tenant = tenant::fetch($tenantid);

        $primary = $hook->get_primaryview();

        $tenantnode = $primary->add(
            get_string('navigation_top', 'tool_mutenancy'),
            null,
            $primary::TYPE_CUSTOM,
            null,
            'tool_mutenancy'
        );
        $tenantnode->add(
            format_string($tenant->name),
            new \core\url('/admin/tool/mutenancy/tenant.php', ['id' => $tenantid])
        );

        $catcontext = \context_coursecat::instance($tenant->categoryid);
        if (has_capability('moodle/category:manage', $catcontext)) {
            $url = new \core\url('/course/management.php', ['categoryid' => $tenant->categoryid]);
        } else {
            $url = new \core\url('/course/index.php', ['categoryid' => $tenant->categoryid]);
        }
        $tenantnode->add(
            get_string('navigation_category', 'tool_mutenancy'),
            $url
        );

        // Let other plugins add more items to "Tenant management" menu.
        $hook = new \tool_mutenancy\hook\tenant_management_menu($tenantnode, $tenant, $tenantcontext, $catcontext);
        \core\di::get(\core\hook\manager::class)->dispatch($hook);
    }
}
