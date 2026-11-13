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
    /** @var string[] components of all MuTMS plugins, tool_certificate is not a MuTMS plugin */
    public const MUTMS_PLUGINS = [
        'block_mucertify_my',
        'block_muprog_my',
        'block_muprogmyoverview',
        'certificateelement_mucertify',
        'certificateelement_muprog',
        'customfield_mutrain',
        'enrol_muprog',
        'mod_mubook',
        'tool_mucertify',
        'tool_muhome',
        'tool_mulib',
        'tool_muloginas',
        'tool_muprog',
        'tool_mupwned',
        'tool_murelation',
        'tool_musudo',
        'tool_mutenancy',
        'tool_mutrain',
    ];

    /** @var string[] full translations shipped in lang/ of all MuTMS plugins, tested by tool_mulib */
    public const TRANSLATIONS = [
        'cs',
        'de',
        'es',
        'fr',
        'it',
        'pt',
    ];

    /**
     * Is the component a MuTMS plugin?
     *
     * NOTE: the plugin does not need to be installed.
     *
     * @param string $component frankenstyle component name
     * @return bool
     */
    public static function is_mutms_plugin(string $component): bool {
        return in_array($component, self::MUTMS_PLUGINS, true);
    }

    /**
     * Delete translations of MuTMS plugins from downloaded language packs,
     * translations shipped with the plugins must be used instead.
     *
     * NOTE: this can be disabled via tool_mulib/purgedownloadedlang setting.
     *
     * @return int number of deleted files
     */
    public static function purge_downloaded_translations(): int {
        if (!get_config('tool_mulib', 'purgedownloadedlang')) {
            return 0;
        }
        $deleted = 0;
        foreach (self::MUTMS_PLUGINS as $component) {
            $deleted += lang_util::purge_downloaded_translations($component, self::TRANSLATIONS);
        }
        return $deleted;
    }

    /**
     * Language pack installed or updated observer.
     *
     * @param \core\event\base $event
     */
    public static function callback_langpack_installed(\core\event\base $event): void {
        self::purge_downloaded_translations();
    }

    /**
     * Is catalogue plugin available?
     *
     * @return bool
     */
    public static function is_mucatalog_available(): bool {
        return class_exists(\tool_mucatalog\local\util::class);
    }

    /**
     * Are any sections present in catalogue?
     *
     * @return bool
     */
    public static function is_mucatalog_active(): bool {
        if (!self::is_mucatalog_available()) {
            return false;
        }
        return (bool)get_config('tool_mucatalog', 'active');
    }

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
     * Use mudb::upsert_record() in new code.
     *
     * @param string $table
     * @param stdClass|array $dataobject
     * @param string[] $uniqueindexcolumns list of all columns in unique index
     * @param array $insertonlyfields additional fields with values to be used only for inserts
     */
    public static function upsert_record(string $table, stdClass|array $dataobject, array $uniqueindexcolumns, array $insertonlyfields = []): void {
        mudb::upsert_record($table, $dataobject, $uniqueindexcolumns, $insertonlyfields);
    }

    /**
     * PostgreSQL UPSERT.
     *
     * Use mudb::upsert_record() in new code.
     *
     * @param string $table
     * @param stdClass $dataobject
     * @param string[] $uniqueindexcolumns list of all columns in unique index
     * @param array $insertonlyfields additional fields with values to be used only for inserts
     */
    public static function upsert_record_pgsql(string $table, stdClass $dataobject, array $uniqueindexcolumns, array $insertonlyfields): void {
        mudb::upsert_record_pgsql($table, $dataobject, $uniqueindexcolumns, $insertonlyfields);
    }
}
