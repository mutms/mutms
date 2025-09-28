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
// phpcs:disable moodle.Files.LineLength.TooLong

namespace tool_mutenancy\navigation\views;

/**
 * Tenant page secondary menu.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tenant_secondary extends \core\navigation\views\secondary {
    /**
     * Init secondary menu.
     */
    public function initialise(): void {
        global $DB;

        $this->id = 'secondary_navigation';
        $context = $this->context;
        $this->headertitle = get_string('menu');

        $tenant = $DB->get_record('tool_mutenancy_tenant', ['id' => $context->instanceid], '*', MUST_EXIST);

        $url = new \moodle_url('/admin/tool/mutenancy/tenant.php', ['id' => $context->instanceid]);
        $this->add(get_string('secondary_tenant_details', 'tool_mutenancy'), $url, \navigation_node::TYPE_SETTING, null, 'tenant_details');

        $url = new \moodle_url('/admin/tool/mutenancy/tenant_users.php', ['id' => $context->instanceid]);
        $this->add(get_string('secondary_tenant_users', 'tool_mutenancy'), $url, \navigation_node::TYPE_SETTING, null, 'tenant_users');

        $url = new \moodle_url('/admin/tool/mutenancy/tenant_auth.php', ['id' => $context->instanceid]);
        $this->add(get_string('secondary_tenant_auth', 'tool_mutenancy'), $url, \navigation_node::TYPE_SETTING, null, 'tenant_auth');

        $url = new \moodle_url('/admin/tool/mutenancy/tenant_appearance.php', ['id' => $context->instanceid]);
        $this->add(get_string('secondary_tenant_appearance', 'tool_mutenancy'), $url, \navigation_node::TYPE_SETTING, null, 'tenant_appearance');

        $this->scan_for_active_node($this);
        $this->initialised = true;
    }
}
