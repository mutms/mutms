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

namespace tool_mutenancy\output;

use stdClass;
use tool_mutenancy\local\tenancy;

/**
 * Tenant selector for login page.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class logintenantselector implements \core\output\named_templatable, \renderable {
    /** @var \action_menu|null  */
    protected $menu = null;

    /**
     * Constructor.
     */
    public function __construct() {
        global $DB;

        $currenttenantid = (int)\tool_mutenancy\local\tenancy::get_current_tenantid();

        $sql = "SELECT t.id, t.name, t.idnumber, t.sitefullname
                  FROM {tool_mutenancy_tenant} t
                 WHERE t.archived = 0 AND t.loginshow = 1 AND t.id <> :tenantid
              ORDER BY name ASC";
        $tenants = $DB->get_records_sql($sql, ['tenantid' => $currenttenantid]);

        if (!$currenttenantid && !$tenants) {
            return;
        }

        $this->menu = new \action_menu();
        $this->menu->set_menu_trigger(tenancy::get_tenant_string('login_tenant_select'));

        if ($currenttenantid) {
            $site = $DB->get_record('course', ['category' => 0], 'id, fullname');
            $this->menu->add(
                new \action_menu_link_secondary(
                    new \core\url('/login/', ['tenant' => 0]),
                    null,
                    format_string($site->fullname)
                )
            );
        }

        foreach ($tenants as $tenant) {
            $this->menu->add(
                new \action_menu_link_secondary(
                    new \core\url('/login/', ['tenant' => $tenant->idnumber]),
                    null,
                    format_string($tenant->sitefullname ?? $tenant->name)
                )
            );
        }
    }

    /**
     * Does menu have items?
     *
     * @return bool
     */
    public function has_items(): bool {
        return isset($this->menu);
    }

    /**
     * Export data for action menu template.
     *
     * @param \renderer_base $output
     * @return stdClass|null
     */
    public function export_for_template(\renderer_base $output): ?stdClass {
        return $this->menu->export_for_template($output);
    }

    /**
     * Get the name of the template to use for this templatable.
     *
     * @param \renderer_base $renderer The renderer requesting the template name
     * @return string
     */
    public function get_template_name(\renderer_base $renderer): string {
        return 'core/action_menu';
    }
}
