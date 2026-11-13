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
// phpcs:disable moodle.PHPUnit.TestCaseProvider.dataProviderSyntaxMethodNotFound

namespace tool_mulib\phpunit;

use tool_mulib\local\mulib;

/**
 * Language pack tests of all MuTMS plugins.
 *
 * @group       MuTMS
 * @package     tool_mulib
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class lang_test extends \core\tests\plugin_checks_testcase {
    /**
     * Every translation must have exactly the same strings as English.
     *
     * @dataProvider all_plugins_provider
     * @coversNothing
     *
     * @param string $component
     * @param string $plugintype
     * @param string $pluginname
     * @param string $dir
     */
    public function test_translations(string $component, string $plugintype, string $pluginname, string $dir): void {
        if (!mulib::is_mutms_plugin($component)) {
            $this->expectNotToPerformAssertions();
            return;
        }

        $file = ($plugintype === 'mod' ? $pluginname : $component) . '.php';
        $en = array_keys($this->load_strings("$dir/lang/en/$file"));

        foreach (mulib::TRANSLATIONS as $lang) {
            $this->assertFileExists("$dir/lang/$lang/$file");
            $strings = array_keys($this->load_strings("$dir/lang/$lang/$file"));
            $this->assertSame([], array_values(array_diff($en, $strings)), "Missing $lang strings in $component");
            $this->assertSame([], array_values(array_diff($strings, $en)), "Extra $lang strings in $component");
        }
    }

    /**
     * Load strings from one language file.
     *
     * @param string $file
     * @return array
     */
    private function load_strings(string $file): array {
        $string = [];
        include($file);
        return $string;
    }
}
