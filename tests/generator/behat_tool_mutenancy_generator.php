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
 * Tenancy behat generator.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_tool_mutenancy_generator extends \behat_generator_base {
    /**
     * Get a list of the entities that Behat can create using the generator step.
     *
     * @return array
     */
    protected function get_creatable_entities(): array {
        return [
            'tenants' => [
                'singular' => 'tenant',
                'datagenerator' => 'tenant',
                'required' => ['name', 'idnumber'],
            ],
            'tenant managers' => [
                'singular' => 'tenant manager',
                'datagenerator' => 'tenant_manager',
                'required' => ['tenant', 'user'],
                'switchids' => ['tenant' => 'tenantid', 'user' => 'userid'],
            ],
        ];
    }

    /**
     * Gets the tenant id from idnumber.
     *
     * @param string $idnumber
     * @return int
     */
    protected function get_tenant_id(string $idnumber): int {
        global $DB;

        if (!$id = $DB->get_field('tool_mutenancy_tenant', 'id', ['idnumber' => $idnumber])) {
            throw new Exception('The specified tenant with idnumber "' . $idnumber . '" does not exist');
        }
        return $id;
    }
}
