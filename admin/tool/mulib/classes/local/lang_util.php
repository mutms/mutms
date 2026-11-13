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

namespace tool_mulib\local;

/**
 * Language pack helpers.
 *
 * @package     tool_mulib
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class lang_util {
    /**
     * Is this a valid language code known to Moodle?
     *
     * NOTE: the language pack does not need to be installed,
     * every language from AMOS has installer strings in install/lang/.
     *
     * @param string $lang
     * @return bool
     */
    public static function is_known_language(string $lang): bool {
        global $CFG;
        if ($lang === 'en') {
            return true;
        }
        if ($lang === '' || clean_param($lang, PARAM_SAFEDIR) !== $lang) {
            return false;
        }
        return is_dir("$CFG->dirroot/install/lang/$lang");
    }

    /**
     * Delete translations of a plugin from downloaded language packs,
     * so that translations shipped with the plugin are used instead.
     *
     * Moodle loads strings from downloaded language packs after the plugin's own lang directory,
     * which means that outdated AMOS translations override translations shipped with plugins.
     * Local customisations (xx_local packs) are never touched.
     *
     * @param string $component frankenstyle component name
     * @param string[]|null $languages language codes, null means all languages shipped with the plugin
     * @return int number of deleted files
     */
    public static function purge_downloaded_translations(string $component, ?array $languages = null): int {
        global $CFG;

        if (empty($CFG->langotherroot) || !is_dir($CFG->langotherroot)) {
            return 0;
        }

        [$type, $name] = \core_component::normalize_component($component);
        $dir = \core_component::get_component_directory($component);
        if (!$dir || !is_dir("$dir/lang")) {
            return 0;
        }
        $file = ($type === 'mod' ? $name : "{$type}_{$name}") . '.php';

        if ($languages === null) {
            $languages = [];
            foreach (new \DirectoryIterator("$dir/lang") as $item) {
                if (!$item->isDot() && $item->isDir() && file_exists($item->getPathname() . '/' . $file)) {
                    $languages[] = $item->getFilename();
                }
            }
        }

        $deleted = 0;
        foreach ($languages as $lang) {
            // English strings are always loaded from plugins, downloaded en packs are ignored.
            if ($lang === 'en' || !self::is_known_language($lang)) {
                continue;
            }
            $target = "$CFG->langotherroot/$lang/$file";
            if (!is_file($target)) {
                continue;
            }
            // Language packs may be deployed read-only on purpose, do not trigger PHP warnings.
            if (@unlink($target)) {
                $deleted++;
            } else {
                debugging("Cannot delete downloaded translation file $target", DEBUG_DEVELOPER);
            }
        }

        if ($deleted) {
            get_string_manager()->reset_caches();
        }

        return $deleted;
    }
}
