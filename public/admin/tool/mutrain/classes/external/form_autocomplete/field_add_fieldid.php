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

namespace tool_mutrain\external\form_autocomplete;

use tool_mutrain\local\framework;
use core_external\external_function_parameters;
use core_external\external_value;
use stdClass;

/**
 * Provides list of candidates for adding fields to framework.
 *
 * @package    tool_mutrain
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class field_add_fieldid extends \tool_mulib\external\form_autocomplete\base {
    /** @var int training field db table */
    public const ITEM_TABLE = 'customfield_field';

    #[\Override]
    public static function get_multiple(): bool {
        return false;
    }

    #[\Override]
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_RAW, 'The search query', VALUE_REQUIRED),
            'frameworkid' => new external_value(PARAM_INT, 'Framework id', VALUE_REQUIRED),
        ]);
    }

    /**
     * Finds users with the identity matching the given query.
     *
     * @param string $query The search request.
     * @param int $frameworkid The framework.
     * @return array
     */
    public static function execute(string $query, int $frameworkid): array {
        global $DB;

        [
            'query' => $query,
            'frameworkid' => $frameworkid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'query' => $query,
            'frameworkid' => $frameworkid,
        ]);

        $framework = $DB->get_record('tool_mutrain_framework', ['id' => $frameworkid], '*', MUST_EXIST);

        // Validate context.
        $context = \context::instance_by_id($framework->contextid);
        self::validate_context($context);
        require_capability('tool/mutrain:manageframeworks', $context);

        $allfields = framework::get_all_training_fields();
        $current = $DB->get_records_menu('tool_mutrain_field', ['frameworkid' => $framework->id], '', 'fieldid, id');

        $fields = [];
        foreach ($allfields as $field) {
            if (isset($current[$field->id])) {
                continue;
            }

            if ($query) {
                if (!str_contains($field->name, $query) && !str_contains($field->shortname, $query)) {
                    continue;
                }
            }

            $fields[$field->id] = $field;
        }

        if (count($fields) > self::MAX_RESULTS) {
            return self::get_overflow_result();
        }

        return self::get_list_result($fields, $context);
    }

    #[\Override]
    public static function format_label(stdClass $item, \context $context): string {
        $name = format_string($item->name);
        return "$name <small>($item->component/$item->area)</small>";
    }

    #[\Override]
    public static function validate_value(int $value, array $args, \context $context): ?string {
        global $DB;

        $allfields = framework::get_all_training_fields();

        if (!isset($allfields[$value])) {
            return get_string('error');
        }

        if ($DB->record_exists('tool_mutrain_field', ['frameworkid' => $args['frameworkid'], 'fieldid' => $value])) {
            return get_string('error');
        }

        return null;
    }
}
