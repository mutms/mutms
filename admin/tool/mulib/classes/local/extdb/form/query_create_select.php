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

namespace tool_mulib\local\extdb\form;

use tool_mulib\external\form_autocomplete\extdb_query_contextid;

/**
 * Create a new query form.
 *
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class query_create_select extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition(): void {
        $mform = $this->_form;

        $types = self::get_type_optmenu();
        $mform->addElement('selectgroups', 'type', get_string('extdb_query_type', 'tool_mulib'), $types);
        $mform->addRule('type', get_string('required'), 'required', null, 'client');

        $this->add_action_buttons(true, get_string('continue'));
    }

    /**
     * Returns menu for query type selection.
     *
     * @return array
     */
    public static function get_type_optmenu(): array {
        $qman = \core\di::get(\tool_mulib\local\extdb\query_manager::class);

        $result = ['' => ['' => get_string('choosedots')]];
        foreach ($qman->get_classes() as $component => $types) {
            foreach ($types as $type => $classname) {
                $pluginname = get_string('pluginname', $component);
                $result[$pluginname][$component . '-' . $type] = $classname::get_name();
            }
        }

        return $result;
    }

    /**
     * Decode types optmenu submission value.
     *
     * @param string $component
     * @param string $type
     * @return string[]
     */
    public static function decode_type_optmenu(string $component, string $type): array {
        if (!$type) {
            return ['component' => '', 'type' => ''];
        }

        if (str_contains($type, '-')) {
            [$component, $type] = explode('-', $type, 2);
        }

        $qman = \core\di::get(\tool_mulib\local\extdb\query_manager::class);
        $classes = $qman->get_classes();

        if (isset($classes[$component][$type])) {
            return ['component' => $component, 'type' => $type];
        }

        return ['component' => '', 'type' => ''];
    }
}
