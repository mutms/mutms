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

/**
 * Multi-tenancy config overrides helper.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class config {
    /**
     * Is the setting value forced from config.php?
     *
     * @param string $plugin
     * @param string $name
     * @return bool
     */
    public static function is_value_forced(string $plugin, string $name): bool {
        global $CFG;

        if ($plugin === 'moodle' || $plugin === 'core' || empty($plugin)) {
            if (empty($CFG->config_php_settings)) {
                return false;
            }
            return array_key_exists($name, $CFG->config_php_settings);
        } else {
            if (empty($CFG->forced_plugin_settings[$plugin])) {
                return false;
            }
            return array_key_exists($name, $CFG->forced_plugin_settings[$plugin]);
        }
    }


    /**
     * Is the setting value overridden in tenant configuration?
     *
     * @param int $tenantid
     * @param string $plugin
     * @param string $name
     * @return bool
     */
    public static function is_overridden(int $tenantid, string $plugin, string $name): bool {
        if ($tenantid <= 0) {
            debugging('Invalid tenantid', DEBUG_DEVELOPER);
            return false;
        }

        if (self::is_value_forced($plugin, $name)) {
            return false;
        }

        $overrides = self::fetch_overrides($tenantid, $plugin);
        return isset($overrides[$name]);
    }

    /**
     * Override tenant setting.
     *
     * @param int $tenantid
     * @param string $name
     * @param mixed $value
     * @param string $plugin
     */
    public static function override(int $tenantid, string $name, $value, string $plugin): void {
        global $DB;

        $tenant = tenant::fetch($tenantid);

        if (!$tenant) {
            throw new \core\exception\invalid_parameter_exception('tenantid invalid');
        }

        if ($plugin === 'moodle' || $plugin === 'core' || empty($plugin)) {
            $plugin = 'core';
        }

        $record = $DB->get_record('tool_mutenancy_config', ['tenantid' => $tenant->id, 'plugin' => $plugin, 'name' => $name]);

        $cache = \cache::make('tool_mutenancy', 'config');
        $key = $tenantid . '-' . $plugin;

        if ($value === null) {
            if ($record) {
                $DB->delete_records('tool_mutenancy_config', ['id' => $record->id]);
            }
            $cache->delete($key);
            return;
        }

        if (is_bool($value)) {
            $value = (int)$value;
        }
        $value = (string)$value;

        if ($record) {
            $DB->set_field('tool_mutenancy_config', 'value', $value, ['id' => $record->id]);
            $cache->delete($key);
            return;
        }

        $record = (object)[
            'tenantid' => $tenant->id,
            'plugin' => $plugin,
            'name' => $name,
            'value' => $value,
        ];
        $DB->insert_record('tool_mutenancy_config', $record);
        $cache->delete($key);
    }

    /**
     * Cached version of plugin setting overrides.
     *
     * @param int $tenantid
     * @param string $plugin
     * @return array
     */
    public static function fetch_overrides(int $tenantid, string $plugin): array {
        global $DB;

        if ($tenantid <= 0) {
            debugging('Invalid tenantid', DEBUG_DEVELOPER);
            return [];
        }

        if ($plugin === 'moodle' || $plugin === 'core' || empty($plugin)) {
            $plugin = 'core';
        }

        $cache = \cache::make('tool_mutenancy', 'config');
        $key = $tenantid . '-' . $plugin;

        $result = $cache->get($key);
        if ($result !== false) {
            return $result;
        }

        $result = $DB->get_records_menu(
            'tool_mutenancy_config',
            ['tenantid' => $tenantid, 'plugin' => $plugin],
            'name ASC',
            'name, value'
        );
        $cache->set($key, $result);

        return $result;
    }

    /**
     * Get config value for given tenant.
     *
     * @param int|null $tenantid -1 means use current tenant, null/0 means real global config value
     * @param string $plugin name of plugin
     * @param string|null $name name of setting
     * @return mixed
     */
    public static function get(?int $tenantid, string $plugin, ?string $name = null) {
        if ($plugin === 'moodle' || $plugin === 'core' || empty($plugin)) {
            $plugin = 'core';
        }

        if ($tenantid < 0) {
            $tenantid = tenancy::get_current_tenantid();
        }

        if (!$tenantid) {
            return get_config($plugin, $name);
        }

        $overrides = self::fetch_overrides($tenantid, $plugin);

        if ($name === null) {
            $config = get_config($plugin);
            foreach ($overrides as $k => $v) {
                if (self::is_value_forced($plugin, $k)) {
                    continue;
                }
                $config->$k = $v;
            }

            return $config;
        }

        if (!self::is_value_forced($plugin, $name)) {
            if (isset($overrides[$name])) {
                return $overrides[$name];
            }
        }

        return get_config($plugin, $name);
    }

    /**
     * Purge all tenant setting overrides for given plugin.
     *
     * This is meant to be called only from unset_all_config_for_plugin().
     *
     * @param string $plugin
     */
    public static function purge_plugin_overrides(string $plugin): void {
        global $DB;

        $DB->delete_records('tool_mutenancy_config', ['plugin' => $plugin]);

        $params = [$DB->sql_like_escape($plugin . '_') . '%'];
        $like = $DB->sql_like('name', '?');

        $DB->delete_records_select('tool_mutenancy_config', "plugin = 'core' AND $like", $params);

        \cache_helper::purge_by_event('tool_mutenancy_invalidatecaches');
    }
}
