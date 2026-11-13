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

namespace tool_mulib\phpunit\local;

use tool_mulib\local\lang_util;

/**
 * Language pack helper tests.
 *
 * @group       MuTMS
 * @package     tool_mulib
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mulib\local\lang_util
 */
final class lang_util_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Create fake downloaded language file.
     *
     * @param string $lang
     * @param string $file
     * @return string full path
     */
    private function create_downloaded_file(string $lang, string $file): string {
        global $CFG;
        $dir = "$CFG->langotherroot/$lang";
        if (!is_dir($dir)) {
            mkdir($dir, $CFG->directorypermissions, true);
        }
        file_put_contents("$dir/$file", "<?php\n\$string['pluginname'] = 'Moldy AMOS leftovers';\n");
        return "$dir/$file";
    }

    public function test_is_known_language(): void {
        $this->assertTrue(lang_util::is_known_language('en'));
        $this->assertTrue(lang_util::is_known_language('en_us'));
        $this->assertTrue(lang_util::is_known_language('cs'));
        $this->assertTrue(lang_util::is_known_language('de_du'));
        // Not installed packs are fine.
        $this->assertTrue(lang_util::is_known_language('is'));

        $this->assertFalse(lang_util::is_known_language(''));
        $this->assertFalse(lang_util::is_known_language('xx'));
        $this->assertFalse(lang_util::is_known_language('en_local'));
        $this->assertFalse(lang_util::is_known_language('.'));
        $this->assertFalse(lang_util::is_known_language('..'));
        $this->assertFalse(lang_util::is_known_language('../en'));
        $this->assertFalse(lang_util::is_known_language('en/'));
    }

    public function test_purge_downloaded_translations(): void {
        $cs = $this->create_downloaded_file('cs', 'tool_mulib.php');
        $de = $this->create_downloaded_file('de', 'tool_mulib.php');
        $is = $this->create_downloaded_file('is', 'tool_mulib.php');
        $en = $this->create_downloaded_file('en', 'tool_mulib.php');
        $other = $this->create_downloaded_file('cs', 'tool_dataprivacy.php');

        $this->assertSame(0, lang_util::purge_downloaded_translations('tool_mulib', ['en', '', '../cs', 'xx']));
        $this->assertFileExists($en);
        $this->assertFileExists($cs);

        // Explicit languages do not need to be shipped with the plugin.
        $this->assertSame(1, lang_util::purge_downloaded_translations('tool_mulib', ['is']));
        $this->assertFileDoesNotExist($is);
        $is = $this->create_downloaded_file('is', 'tool_mulib.php');

        $this->assertSame(1, lang_util::purge_downloaded_translations('tool_mulib', ['cs']));
        $this->assertFileDoesNotExist($cs);
        $this->assertFileExists($de);

        $cs = $this->create_downloaded_file('cs', 'tool_mulib.php');
        $this->assertSame(2, lang_util::purge_downloaded_translations('tool_mulib'));
        $this->assertFileDoesNotExist($cs);
        $this->assertFileDoesNotExist($de);
        $this->assertFileExists($en);
        $this->assertFileExists($is);
        $this->assertFileExists($other);

        $this->assertSame(0, lang_util::purge_downloaded_translations('tool_mulib'));
        $this->assertSame(0, lang_util::purge_downloaded_translations('local_grgrgrgr'));
    }

    public function test_purge_downloaded_translations_readonly(): void {
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            $this->markTestSkipped('Permissions are ignored for root');
        }
        $cs = $this->create_downloaded_file('cs', 'tool_mulib.php');
        chmod($cs, 0444);
        $this->assertSame(1, lang_util::purge_downloaded_translations('tool_mulib', ['cs']));
        $this->assertFileDoesNotExist($cs);

        $cs = $this->create_downloaded_file('cs', 'tool_mulib.php');
        chmod(dirname($cs), 0555);
        try {
            $this->assertSame(0, lang_util::purge_downloaded_translations('tool_mulib', ['cs']));
            $this->assertDebuggingCalled("Cannot delete downloaded translation file $cs");
            $this->assertFileExists($cs);
        } finally {
            chmod(dirname($cs), 0777);
        }
    }

    public function test_purge_downloaded_translations_mod(): void {
        if (!\core_component::get_component_directory('mod_mubook')) {
            $this->markTestSkipped('mod_mubook is not available');
        }
        $de = $this->create_downloaded_file('de', 'mubook.php');
        $wrong = $this->create_downloaded_file('de', 'mod_mubook.php');

        $this->assertSame(1, lang_util::purge_downloaded_translations('mod_mubook', ['de']));
        $this->assertFileDoesNotExist($de);
        $this->assertFileExists($wrong);
    }
}
