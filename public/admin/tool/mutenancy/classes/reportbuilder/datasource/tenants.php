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

namespace tool_mutenancy\reportbuilder\datasource;

use core_reportbuilder\datasource;

/**
 * Tenant data source.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tenants extends datasource {
    /**
     * Return user-friendly name of the datasource
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('tenants', 'tool_mutenancy');
    }

    /**
     * Initialise report
     */
    protected function initialise(): void {
        $tenantentity = new \tool_mutenancy\reportbuilder\local\entities\tenant();
        $tenanttablealias = $tenantentity->get_table_alias('tool_mutenancy_tenant');

        $this->set_main_table('tool_mutenancy_tenant', $tenanttablealias);

        $this->add_entity($tenantentity);

        // Add all columns/filters/conditions from entities to be available in custom reports.
        $this->add_all_from_entities();
    }

    /**
     * Return the columns that will be added to the report as part of default setup
     *
     * @return string[]
     */
    public function get_default_columns(): array {
        return [
            'tenant:name',
            'tenant:idnumber',
            'tenant:archived',
        ];
    }

    /**
     * Return the filters that will be added to the report once is created
     *
     * @return string[]
     */
    public function get_default_filters(): array {
        return [];
    }

    /**
     * Return the conditions that will be added to the report once is created
     *
     * @return string[]
     */
    public function get_default_conditions(): array {
        return [];
    }

    /**
     * Return the default sorting that will be added to the report once it is created
     *
     * @return array|int[]
     */
    public function get_default_column_sorting(): array {
        return [
            'tenant:name' => SORT_ASC,
        ];
    }
}
