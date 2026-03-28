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
 * Additional tools library behat generators.
 *
 * @package    tool_mulib
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_tool_mulib_generator extends behat_generator_base {
    /**
     * Get a list of the entities that Behat can create using the generator step.
     *
     * @return array
     */
    protected function get_creatable_entities(): array {
        return [
            'extdb_servers' => [
                'singular' => 'extdb_server',
                'datagenerator' => 'extdb_server',
                'required' => ['name'],
            ],
            'extdb_queries' => [
                'singular' => 'extdb_query',
                'datagenerator' => 'extdb_query',
                'required' => ['name', 'server', 'component', 'type', 'sqlquery'],
            ],
        ];
    }

    /**
     * Pre-process query to populate context property.
     *
     * @param array $query
     * @return array
     */
    protected function preprocess_extdb_query(array $query): array {
        if (!empty($query['contextlevel']) && !empty($query['reference'])) {
            $query['context'] = $this->get_context($query['contextlevel'], $query['reference']);
        }
        unset($query['contextlevel'], $query['reference']);

        // Use the same table name hackery as in regular database driver
        // to help with testing in current database.
        $query['sqlquery'] = preg_replace_callback(
            '/\{([a-z][a-z0-9_]*)}/',
            function ($matches) {
                global $CFG;
                return $CFG->prefix . $matches[1];
            },
            $query['sqlquery']
        );

        return $query;
    }
}
