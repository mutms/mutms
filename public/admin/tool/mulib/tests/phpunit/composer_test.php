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
// phpcs:disable moodle.Commenting.DocblockDescription.Missing
// phpcs:disable moodle.PHPUnit.TestCaseProvider.dataProviderSyntaxMethodNotFound

namespace tool_mulib\phpunit;

/**
 * Composer tests of all MuTMS plugins.
 *
 * @group       MuTMS
 * @package     tool_mulib
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class composer_test extends \core\tests\plugin_checks_testcase {
    /**
     * Validate composer.json content.
     *
     * @dataProvider all_plugins_provider
     * @coversNothing
     *
     * @param string $component
     * @param string $plugintype
     * @param string $pluginname
     * @param string $dir
     */
    public function test_composer_json(string $component, string $plugintype, string $pluginname, string $dir): void {
        if (!file_exists("$dir/composer.json")) {
            return;
        }

        $composer = json_decode(file_get_contents("$dir/composer.json"), true);
        if (!str_starts_with($composer['name'], 'mutms/')) {
            return;
        }

        $plugin = new \stdClass();
        require("$dir/version.php");

        $readme = file_get_contents("$dir/README.md");
        preg_match('/^# (.*)$/m', $readme, $matches);
        $description = str_replace('™', '', $matches[1]);

        $this->assertSame("mutms/moodle-{$component}", $composer['name']);
        $this->assertSame("moodle-{$plugintype}", $composer['type']);
        $this->assertSame($description, $composer['description']);
        $this->assertSame("https://github.com/mutms/moodle-{$component}", $composer['homepage']);
        $this->assertSame('GPL-3.0-or-later', $composer['license']);
        $this->assertArrayNotHasKey('installers', $composer);
        if (!empty($plugin->dependencies)) {
            foreach ($plugin->dependencies as $dependency => $version) {
                if ($dependency === 'tool_mfa' || $dependency === 'tool_certificate') {
                    continue;
                }
                $this->assertSame('*', $composer['require']["mutms/moodle-$dependency"]);
            }
        }
        foreach ($composer['require'] as $dependency => $version) {
            if ($dependency === 'moodle/moodle') {
                if ($component !== 'tool_mutenancy') {
                    $this->assertSame('5.1.*||5.2.*', $version);
                }
                continue;
            }
            if (!str_starts_with($dependency, 'mutms/moodle-')) {
                continue;
            }
            $depname = substr($dependency, strlen('mutms/moodle-'));
            $this->assertArrayHasKey($depname, $plugin->dependencies);
        }
        $this->assertArrayNotHasKey('extra', $composer);
        $this->assertSame(
            [
                "issues" => "https://github.com/mutms/moodle-{$component}/issues",
                "source" => "https://github.com/mutms/moodle-{$component}",
            ],
            $composer['support']
        );
    }
}
