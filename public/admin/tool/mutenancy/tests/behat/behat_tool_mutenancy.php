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

require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');

/**
 * Library mulib behat steps.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_tool_mutenancy extends behat_base {
    /**
     * Activate multi-tenancy.
     *
     * @Given the multi-tenancy is activated
     */
    public function activate_multitenancy() {
        \tool_mutenancy\local\tenancy::activate();
    }

    /**
     * Skip tests if multi-tenancy was already activated.
     *
     * @Given I skip tests if multi-tenancy is activated
     */
    public function skip_if_multitenancy_activated(): void {
        if (mutenancy_is_active()) {
            throw new \Moodle\BehatExtension\Exception\SkippedException("Tests were skipped because multi-tenancy is activated");
        }
    }

    /**
     * Convert page names to URLs for steps like 'When I am on the "[page name]" page'.
     *
     * @param string $page name of the page, with the component name removed e.g. 'Admin notification'.
     * @return moodle_url the corresponding URL.
     */
    protected function resolve_page_url(string $page): moodle_url {
        switch (strtolower($page)) {
            case 'tenants':
                return new moodle_url('/admin/tool/mutenancy/index.php');

            default:
                throw new Exception('Unrecognised tool_mutenancy page "' . $page . '."');
        }
    }

    /**
     * Convert page names to URLs for steps like 'When I am on the "[identifier]" "[page type]" page'.
     *
     * @param string $type identifies which type of page this is, e.g. 'Preview'.
     * @param string $identifier identifies the particular page, e.g. 'My question'.
     * @return moodle_url the corresponding URL.
     */
    protected function resolve_page_instance_url(string $type, string $identifier): moodle_url {
        global $DB;
        switch (strtolower($type)) {
            case 'tenant':
                $tenant = $DB->get_record('tool_mutenancy_tenant', ['idnumber' => $identifier]);
                if (!$tenant) {
                    throw new Exception('Invalid tenant idnumber "' . $identifier . '."');
                }
                return new moodle_url('/admin/tool/mutenancy/tenant.php', ['id' => $tenant->id]);
            case 'tenant users':
                $tenant = $DB->get_record('tool_mutenancy_tenant', ['idnumber' => $identifier]);
                if (!$tenant) {
                    throw new Exception('Invalid tenant idnumber "' . $identifier . '."');
                }
                return new moodle_url('/admin/tool/mutenancy/tenant_users.php', ['id' => $tenant->id]);
            case 'tenant authentication':
                $tenant = $DB->get_record('tool_mutenancy_tenant', ['idnumber' => $identifier]);
                if (!$tenant) {
                    throw new Exception('Invalid tenant idnumber "' . $identifier . '."');
                }
                return new moodle_url('/admin/tool/mutenancy/tenant_auth.php', ['id' => $tenant->id]);
            case 'tenant appearance':
                $tenant = $DB->get_record('tool_mutenancy_tenant', ['idnumber' => $identifier]);
                if (!$tenant) {
                    throw new Exception('Invalid tenant idnumber "' . $identifier . '."');
                }
                return new moodle_url('/admin/tool/mutenancy/tenant_appearance.php', ['id' => $tenant->id]);
            case 'tenant login':
                if (!$identifier) {
                    $identifier = '0';
                }
                return new moodle_url('/login/', ['tenant' => $identifier]);

            default:
                throw new Exception('Unrecognised tool_mutenancy page type "' . $type . '."');
        }
    }
}
