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

use stdClass;
use tool_mulib\local\mulib;
use tool_mulib\local\sql;

/**
 * Base class for user auto-completion fields.
 *
 * @package    tool_mulib
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class user extends base {
    /** @var string|null user table */
    protected const ITEM_TABLE = 'user';
    /** @var string|null not used, there is custom format_label() method */
    protected const ITEM_FIELD = null;

    #[\Override]
    public static function format_label(stdClass $item, \context $context): string {
        global $OUTPUT;

        if ($item->deleted) {
            return get_string('deleted');
        }

        $fields = \core_user\fields::for_name()->with_identity($context, false);

        $data = (object)[
            'id' => $item->id,
            'fullname' => fullname($item, has_capability('moodle/site:viewfullnames', $context)),
            'extrafields' => [],
        ];

        foreach ($fields->get_required_fields([\core_user\fields::PURPOSE_IDENTITY]) as $extrafield) {
            $data->extrafields[] = (object)[
                'name' => $extrafield,
                'value' => s($item->$extrafield),
            ];
        }

        return clean_text($OUTPUT->render_from_template('core_user/form_user_selector_suggestion', $data));
    }

    /**
     * If tenant context add limit query to tenant and related users.
     *
     * @param string $useridfield for example usr.id
     * @param \context $context
     * @param string $glue
     * @return string
     */
    public static function get_tenant_related_users_where(string $useridfield, \context $context, string $glue = "AND"): string {
        if (!mulib::is_mutenancy_active()) {
            if ($glue === '') {
                // Return something always true as WHERE condition.
                return "1=1";
            } else {
                // This is expected to be appended to existing WHERE conditions.
                return "";
            }
        }

        return \tool_mutenancy\local\tenancy::get_related_users_exists($useridfield, $context, $glue);
    }

    /**
     * User search where sql.
     *
     * @param string $search
     * @param string $tablealias
     * @param \context $context
     * @return sql
     */
    public static function get_user_search_query(string $search, string $tablealias, \context $context): sql {
        $fields = \core_user\fields::for_name()->with_identity($context, false);
        $extrafields = $fields->get_required_fields([\core_user\fields::PURPOSE_IDENTITY]);
        [$sql, $params] = users_search_sql($search, $tablealias, USER_SEARCH_CONTAINS, $extrafields);
        return new sql($sql, $params);
    }

    /**
     * User search ORDER BY sql.
     *
     * @param string $search
     * @param string $tablealias
     * @param \context $context
     * @return sql
     */
    public static function get_user_search_orderby(string $search, string $tablealias, \context $context): sql {
        [$sql, $params] = users_order_by_sql($tablealias, $search, $context);
        return new sql($sql, $params);
    }

    /**
     * Returns string if context is from tenant and user is not related to it.
     *
     * @param stdClass $user
     * @param \context $context
     * @return string|null error string or NULL if ok
     */
    public static function validate_tenant_relation(stdClass $user, \context $context): ?string {
        global $DB;

        if (!mulib::is_mutenancy_active()) {
            return null;
        }

        $select = self::get_tenant_related_users_where('u.id', $context, 'AND');

        if ($select === "") {
            return null;
        }

        $sql = "SELECT 'x'
                  FROM {user} u
                 WHERE u.id = :userid $select";
        $params = ['userid' => $user->id];
        if ($DB->record_exists_sql($sql, $params)) {
            return null;
        }

        return get_string('error');
    }
}
