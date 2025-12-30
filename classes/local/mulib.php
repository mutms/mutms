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

namespace tool_mulib\local;

use dml_write_exception;
use stdClass;

/**
 * Utility class for MuTMS plugins.
 *
 * NOTE: this is not called util to help with IDE autocompletion.
 *
 * @package    tool_mulib
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class mulib {
    /**
     * Is programs plugin available?
     *
     * @return bool
     */
    public static function is_muprog_available(): bool {
        return class_exists(\tool_muprog\local\util::class);
    }

    /**
     * Are any programs present?
     *
     * @return bool
     */
    public static function is_muprog_active(): bool {
        if (!self::is_muprog_available()) {
            return false;
        }
        return (bool)get_config('tool_muprog', 'active');
    }

    /**
     * Is Certifications plugin available?
     *
     * @return bool
     */
    public static function is_mucertify_available(): bool {
        return class_exists(\tool_mucertify\local\util::class);
    }

    /**
     * Are any certifications present?
     *
     * @return bool
     */
    public static function is_mucertify_active(): bool {
        if (!self::is_mucertify_available()) {
            return false;
        }
        return (bool)get_config('tool_mucertify', 'active');
    }

    /**
     * Is training credits plugin available?
     *
     * @return bool
     */
    public static function is_mutrain_available(): bool {
        return class_exists(\tool_mutrain\local\util::class);
    }

    /**
     * Are any credit frameworks active/present?
     *
     * @return bool
     */
    public static function is_mutrain_active(): bool {
        if (!self::is_mutrain_available()) {
            return false;
        }
        return (bool)get_config('tool_mutrain', 'active');
    }

    /**
     * Are teams and supervisors available?
     *
     * @return bool
     */
    public static function is_murelatio_available(): bool {
        return class_exists(\tool_murelation\local\util::class);
    }

    /**
     * Are teams and supervisors active?
     *
     * @return bool
     */
    public static function is_murelatio_active(): bool {
        if (!self::is_murelatio_available()) {
            return false;
        }
        return \tool_murelation\local\util::is_murelation_active();
    }

    /**
     * Is cusotm homepages plugin available?
     *
     * @return bool
     */
    public static function is_muhome_available(): bool {
        return class_exists(\tool_muhome\local\page::class);
    }

    /**
     * Are any home pages present?
     *
     * @return bool
     */
    public static function is_muhome_active(): bool {
        if (!self::is_muhome_available()) {
            return false;
        }
        return (bool)get_config('tool_muhome', 'active');
    }

    /**
     * Is multi-tenancy available?
     *
     * @return bool
     */
    public static function is_mutenancy_available(): bool {
        return class_exists(\tool_mutenancy\local\tenancy::class);
    }

    /**
     * Is multi-tenancy active?
     *
     * @return bool
     */
    public static function is_mutenancy_active(): bool {
        if (!self::is_mutenancy_available()) {
            return false;
        }
        return \tool_mutenancy\local\tenancy::is_active();
    }

    /**
     * Encode all dangerous characters and named html entities as
     * numeric html entities.
     *
     * The result of this function can be used safely in both {{ }} and {{{ }}} tags in Mustache templates
     * because it is not modified by s() function and it is equivalent to htmlentities() escaping.
     *
     * @param string|null $string
     * @return string|null html string without any tags or dangerous characters
     */
    public static function clean_string(?string $string): ?string {
        if ($string === null || $string === '') {
            return $string;
        }

        $replace = [
            '"' => '&#34;',
            '\'' => '&#39;',
            '<' => '&#60;',
            '>' => '&#62;',
        ];
        $string = strtr($string, $replace);
        $string = preg_replace('/&(?![a-zA-Z0-9#]{1,8};)/', '&#38;', $string);

        static $translationtable = null;

        if (!isset($translationtable)) {
            $translationtable = [];
            // NOTE: do not use ENT_HTML5 here because it adds way too many items.
            $entities = get_html_translation_table(HTML_ENTITIES, ENT_COMPAT | ENT_HTML401, 'UTF-8');
            foreach ($entities as $char => $entity) {
                $translationtable[$entity] = '&#' . \IntlChar::ord($char) . ';';
            }
        }

        return strtr($string, $translationtable);
    }

    /**
     * A fast way to insert or update record that has EXACTLY ONE unique index.
     *
     * If there is already an existing record with unique constraint then
     * record is updated, if not new record is inserted.
     *
     * This method solves problems with highly concurrent inserts into tables with unique constraints.
     *
     * @param string $table
     * @param stdClass|array $dataobject
     * @param string[] $uniqueindexcolumns list of all columns in unique index
     * @param array $insertonlyfields additional fields with values to be used only for inserts
     */
    public static function upsert_record(string $table, stdClass|array $dataobject, array $uniqueindexcolumns, array $insertonlyfields = []): void {
        global $DB;

        $dataobject = (object)(array)$dataobject;

        self::validate_upsert_record_arguments($table, $dataobject, $uniqueindexcolumns, $insertonlyfields);

        if ($DB->get_dbfamily() === 'pgsql') {
            self::upsert_record_pgsql($table, $dataobject, $uniqueindexcolumns, $insertonlyfields);
            return;
        } else if ($DB->get_dbfamily() === 'mysql') {
            self::upsert_record_mysql($table, $dataobject, $uniqueindexcolumns, $insertonlyfields);
            return;
        }

        // NOTE: this is a fallback implementation for unsupported MS SQL Server.

        $conditions = [];
        foreach ($uniqueindexcolumns as $column) {
            $conditions[$column] = $dataobject->$column;
        }

        $record = $DB->get_record($table, $conditions);
        if ($record) {
            $dataobject->id = $record->id;
            $DB->update_record($table, $dataobject);
        } else {
            try {
                if ($insertonlyfields) {
                    $insertdata = (object)((array)$dataobject + $insertonlyfields);
                } else {
                    $insertdata = $dataobject;
                }
                $DB->insert_record($table, $insertdata);
            } catch (dml_write_exception $e) {
                // Could be a concurrent insert trouble.
                $record = $DB->get_record($table, $conditions);
                if (!$record) {
                    throw $e;
                }
                $dataobject->id = $record->id;
                $DB->update_record($table, $dataobject);
            }
        }
    }

    /**
     * PostgreSQL UPSERT.
     *
     * @param string $table
     * @param stdClass $dataobject
     * @param string[] $uniqueindexcolumns list of all columns in unique index
     * @param array $insertonlyfields additional fields with values to be used only for inserts
     */
    public static function upsert_record_pgsql(string $table, stdClass $dataobject, array $uniqueindexcolumns, array $insertonlyfields): void {
        global $DB;

        $values = [];
        $fields = [];
        $params = [];
        $updates = [];
        foreach ((array)$dataobject as $field => $value) {
            $params[] = $value;
            $values[] = '?';
            $fields[] = $field;
            if (!in_array($field, $uniqueindexcolumns)) {
                $updates[] = "$field = EXCLUDED.$field";
            }
        }
        foreach ($insertonlyfields as $field => $value) {
            $params[] = $value;
            $values[] = '?';
            $fields[] = $field;
        }
        $values = implode(',', $values);
        $fields = implode(',', $fields);
        $constraint = implode(',', $uniqueindexcolumns);
        $updates = implode(', ', $updates);

        $sql = "INSERT INTO {{$table}} ($fields) VALUES ($values)
                            ON CONFLICT ($constraint) DO UPDATE SET $updates";
        $DB->execute($sql, $params);
    }

    /**
     * MySQL UPSERT.
     *
     * @param string $table
     * @param stdClass $dataobject
     * @param string[] $uniqueindexcolumns list of all columns in unique index
     * @param array $insertonlyfields additional fields with values to be used only for inserts
     */
    protected static function upsert_record_mysql(string $table, stdClass $dataobject, array $uniqueindexcolumns, array $insertonlyfields): void {
        global $DB;

        $values = [];
        $fields = [];
        $params = [];
        $updates = [];
        foreach ((array)$dataobject as $field => $value) {
            $params[] = $value;
            $values[] = '?';
            $fields[] = $field;
            if (!in_array($field, $uniqueindexcolumns)) {
                $updates[] = "$field = VALUES($field)";
            }
        }
        foreach ($insertonlyfields as $field => $value) {
            $params[] = $value;
            $values[] = '?';
            $fields[] = $field;
        }
        $values = implode(',', $values);
        $fields = implode(',', $fields);
        $updates = implode(', ', $updates);

        $sql = "INSERT INTO {{$table}} ($fields) VALUES ($values)
                            ON DUPLICATE KEY UPDATE $updates";
        $DB->execute($sql, $params);
    }

    /**
     * Validate all upsert parameters.
     *
     * @param string $table
     * @param stdClass $dataobject
     * @param array $uniqueindexcolumns
     * @param array $insertonlyfields
     * @return void
     */
    protected static function validate_upsert_record_arguments(
        string $table,
        stdClass $dataobject,
        array $uniqueindexcolumns,
        array $insertonlyfields
    ): void {
        global $DB;

        if (!$uniqueindexcolumns) {
            throw new \core\exception\coding_exception(
                'moodle_database::upsert_record() requires list of unique constraint columns'
            );
        }

        if (property_exists($dataobject, 'id')) {
            throw new \core\exception\coding_exception(
                'moodle_database::upsert_record() dataobject must not have id property'
            );
        }

        foreach ($uniqueindexcolumns as $column) {
            if (!isset($dataobject->$column)) {
                throw new \core\exception\coding_exception(
                    'moodle_database::upsert_record() dataobject must have all unique columns set'
                );
            }
        }

        $columns = $DB->get_columns($table);

        foreach ($insertonlyfields as $k => $v) {
            if (!isset($columns[$k])) {
                throw new \core\exception\coding_exception(
                    'moodle_database::upsert_record() insertonlyfields contains unknown column'
                );
            }
            if (in_array($k, $uniqueindexcolumns)) {
                throw new \core\exception\coding_exception(
                    'moodle_database::upsert_record() insertonlyfields cannot contain unique columns'
                );
            }
            if (property_exists($dataobject, $k)) {
                throw new \core\exception\coding_exception(
                    'moodle_database::upsert_record() insertonlyfields must not share columns with dataobject'
                );
            }
        }

        $foundnonunique = false;
        foreach ((array)$dataobject as $field => $value) {
            if (!isset($columns[$field])) {
                throw new \core\exception\coding_exception(
                    'moodle_database::upsert_record() dataobject contains unknown column'
                );
            }
            if (!$foundnonunique && !in_array($field, $uniqueindexcolumns)) {
                $foundnonunique = true;
            }
        }

        if (!$foundnonunique) {
            throw new \core\exception\coding_exception(
                'moodle_database::upsert_record() dataobject must contain at least one non-unique column'
            );
        }
    }
}
