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

namespace tool_mulib\external\form_autocomplete;

use core_external\external_api;
use core_external\external_description;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use stdClass;
use tool_mulib\local\sql;

/**
 * Base class for autocomplete form elements.
 *
 * @package    tool_mulib
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base extends external_api {
    /** @var int SQL parameter counter for unique param name creation */
    protected static $paramcounter = 0;

    /** @var int default max results, override if necessary */
    public const MAX_RESULTS = 50;
    /** @var string|null override with name of item database table */
    protected const ITEM_TABLE = null;
    /** @var string|null override with name of item field used for label */
    protected const ITEM_FIELD = null;

    /**
     * Does the element support multiple selections?
     *
     * @return bool
     */
    public static function get_multiple(): bool {
        // Override to return true if multiple values allowed.
        return false;
    }

    /**
     * No selection srtring.
     *
     * @return string
     */
    public static function get_noselectionstring(): string {
        return get_string('noselection', 'form');
    }

    /**
     * Autocomplete field placeholder.
     *
     * @return string
     */
    public static function get_placeholder(): string {
        return '';
    }

    /**
     * Describes the external function arguments.
     *
     * NOTE: override to include all args
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        // NOTE: override to include more arguments.
        return new external_function_parameters([
            'query' => new external_value(PARAM_RAW, 'User specified query', VALUE_REQUIRED),
        ]);
    }

    /**
     * Validate value to make sure user is allowed to use and see it.
     *
     * This is necessary because unlike simple select box we do not know all
     * allowed values.
     *
     * @param int $value
     * @param array $args
     * @param \context $context context matching the $args
     * @return string|null
     */
    public static function validate_value(int $value, array $args, \context $context): ?string {
        debugging(static::class . '::validate_value() must be overridden', DEBUG_DEVELOPER);
        return get_string('error');
    }

    /**
     * Describes the external function result value.
     *
     * @return external_description
     */
    final public static function execute_returns(): external_description {
        return new external_single_structure([
            'list' => new external_multiple_structure(
                new external_single_structure([
                    'value' => new external_value(PARAM_INT, 'Option value - usually some record id.'),
                    'label' => new external_value(PARAM_RAW, 'Option label - using HTML format'),
                ]),
                'List of options, not used if overflown',
                VALUE_REQUIRED,
                null,
                NULL_ALLOWED
            ),
            'overflow' => new external_value(PARAM_BOOL, 'Number of found items is over the limit.'),
            'maxitems' => new external_value(PARAM_INT, 'Maximum number of items that can be returned.'),
        ]);
    }

    /**
     * Return overflow resutl,
     * @return array
     */
    final public static function get_overflow_result(): array {
        return [
            'list' => null,
            'overflow' => true,
            'maxitems' => static::MAX_RESULTS,
        ];
    }

    /**
     * Returns list result.
     *
     * @param stdClass[] $items
     * @param \context $context
     * @return array
     */
    final public static function get_list_result(array $items, \context $context): array {
        return [
            'list' => static::format_list($items, $context),
            'overflow' => false,
            'maxitems' => static::MAX_RESULTS,
        ];
    }

    /**
     * Prepare ws result.
     *
     * @param stdClass[] $items
     * @param \context $context
     * @return array
     */
    final public static function prepare_result(array $items, \context $context): array {
        if (count($items) > static::MAX_RESULTS) {
            return static::get_overflow_result();
        } else {
            return static::get_list_result($items, $context);
        }
    }

    /**
     * Returns external method name for ajax request.
     *
     * @return string
     */
    final public static function get_methodname(): string {
        $method = str_replace('\\external\\', '\\', static::class);
        return str_replace('\\', '_', $method);
    }

    /**
     * Returns general search query in given fields.
     *
     * @param string $search
     * @param array $fields database fields
     * @param string $tablealias
     * @return sql
     */
    public static function get_search_query(string $search, array $fields, string $tablealias = ''): sql {
        global $DB;

        if ($tablealias !== '' && !str_ends_with($tablealias, '.')) {
            $tablealias .= '.';
        }

        $conditions = [];
        $params = [];

        if (trim($search) !== '') {
            $searchparam = '%' . $DB->sql_like_escape($search) . '%';

            foreach ($fields as $field) {
                $param = 'fieldsearch' . self::$paramcounter++;
                $conditions[] = $DB->sql_like($tablealias . $field, ':' . $param, false);
                $params[$param] = $searchparam;
                self::$paramcounter++;
            }
        }

        if ($conditions) {
            $sql = ' (' . implode(' OR ', $conditions) . ') ';
            return new sql($sql, $params);
        } else {
            return new sql(' 1=1 ', $params);
        }
    }

    /**
     * Get option label for given value.
     *
     * @param int $value option value, usually some record id
     * @param array $args initial element creation arguments
     * @param \context $context
     * @return string html fragment
     */
    public static function get_label(int $value, array $args, \context $context): string {
        global $DB;

        if (static::ITEM_TABLE === null) {
            throw new \core\exception\coding_exception('either define ITEM_TABLE, or override get_label');
        }

        // Validation must check the target user can be seen by form filler.
        $error = static::validate_value($value, $args, $context);
        if ($error !== null) {
            return $error;
        }

        $item = $DB->get_record(static::ITEM_TABLE, ['id' => $value]);
        if (!$item) {
            return get_string('error');
        }

        return static::format_label($item, $context);
    }

    /**
     * Format user label for display.
     *
     * @param stdClass $item
     * @param \context $context
     * @return string HTML fragment
     */
    public static function format_label(stdClass $item, \context $context): string {
        if (static::ITEM_FIELD === null) {
            throw new \core\exception\coding_exception('either define ITEM_FIELD, or override format_label');
        }

        $field = static::ITEM_FIELD;
        return format_string($item->$field);
    }

    /**
     * Format user label for display.
     *
     * @param stdClass[] $items
     * @param \context $context
     * @return array result list
     */
    public static function format_list(array $items, \context $context): array {
        $list = [];
        foreach ($items as $item) {
            $list[] = ['value' => $item->id, 'label' => static::format_label($item, $context)];
        }
        return $list;
    }

    /**
     * Create autocomplete element.
     *
     * @param \MoodleQuickForm $mform
     * @param array $args
     * @param string $name
     * @param string $label
     * @param \context $context
     * @return \MoodleQuickForm_autocomplete
     */
    final public static function create_element(
        \MoodleQuickForm $mform,
        array $args,
        string $name,
        string $label,
        \context $context
    ): \MoodleQuickForm_autocomplete {
        $valuehtmlcallback = function ($value) use ($args, $context) {
            if (!$value) {
                return static::get_noselectionstring();
            }
            if (!is_number($value)) {
                return get_string('error');
            }
            return static::get_label($value, $args, $context);
        };
        // Do not pass special 'currentValue' or 'currentValue' argument to web services,
        // it is used for data validation in PHP only.
        $wsargs = $args;
        unset($wsargs['currentValue']);
        unset($wsargs['currentValues']);
        $wsargs = json_encode($wsargs);
        $attributes = [
            'ajax' => 'tool_mulib/form_autocomplete/ajax_handler',
            'data-methodname' => static::get_methodname(),
            'data-args' => $wsargs,
            'multiple' => static::get_multiple(),
            'valuehtmlcallback' => $valuehtmlcallback,
            'noselectionstring' => static::get_noselectionstring(),
            'showsuggestions' => true,
            'tags' => false,
        ];

        $placeholder = static::get_placeholder();
        if ($placeholder !== '') {
            $attributes['placeholder'] = $placeholder;
        }

        /** @var \MoodleQuickForm_autocomplete $element */
        $element = $mform->createElement('autocomplete', $name, $label, null, $attributes);
        return $element;
    }

    /**
     * Create autocomplete element and add it to a form.
     *
     * @param \MoodleQuickForm $mform
     * @param array $args
     * @param string $name
     * @param string $label
     * @param \context $context
     * @return \MoodleQuickForm_autocomplete
     */
    final public static function add_element(
        \MoodleQuickForm $mform,
        array $args,
        string $name,
        string $label,
        \context $context
    ): \MoodleQuickForm_autocomplete {
        $element = static::create_element($mform, $args, $name, $label, $context);
        $mform->addElement($element);
        return $element;
    }
}
