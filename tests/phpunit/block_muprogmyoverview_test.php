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
// phpcs:disable moodle.PHPUnit.TestCaseCovers.Missing
// phpcs:disable moodle.PHP.ForbiddenGlobalUse.BadGlobal

namespace block_muprogmyoverview\phpunit;

/**
 * My programs overview page block test.
 *
 * @group      MuTMS
 * @package    block_muprogmyoverview
 * @category   test
 * @copyright  2019 Juan Leyva <juan@moodle.com>
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class block_muprogmyoverview_test extends \advanced_testcase {
    /**
     * Test getting block configuration
     */
    public function test_get_block_config_for_external(): void {
        global $PAGE, $CFG, $OUTPUT;
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();

        $this->setUser($user);
        $context = \context_system::instance();

        $PAGE->set_context($context);
        $PAGE->set_url('/blocks/muprogmyoverview/');
        $PAGE->add_body_classes(['limitedwidth']);
        $PAGE->set_pagelayout('mycourses');
        $PAGE->set_pagetype('block-muprogmyoverview-index');
        $PAGE->blocks->add_region('content');

        // Load the block instances for all the regions.
        $PAGE->blocks->load_blocks();
        $PAGE->blocks->create_all_block_instances();

        $blocks = $PAGE->blocks->get_content_for_all_regions($OUTPUT);
        $configs = null;
        foreach ($blocks as $region => $regionblocks) {
            $regioninstances = $PAGE->blocks->get_blocks_for_region($region);

            foreach ($regioninstances as $ri) {
                // Look for muprogmyoverview block only.
                if ($ri->instance->blockname === 'muprogmyoverview') {
                    $configs = $ri->get_config_for_external();
                    break 2;
                }
            }
        }

        // Test we receive all we expect (exact number and values of settings).
        $this->assertNotEmpty($configs);
        $this->assertEmpty((array) $configs->instance);
        $this->assertCount(10, (array) $configs->plugin);
        // Test default values.
        $this->assertEquals(0, $configs->plugin->displaycategories);
        $this->assertEquals(1, $configs->plugin->displaygroupingall);
        $this->assertEquals(0, $configs->plugin->displaygroupingallincludinghidden);
        $this->assertEquals(1, $configs->plugin->displaygroupingfuture);
        $this->assertEquals(1, $configs->plugin->displaygroupinghidden);
        $this->assertEquals(1, $configs->plugin->displaygroupinginprogress);
        $this->assertEquals(1, $configs->plugin->displaygroupingpast);
        $this->assertEquals(1, $configs->plugin->displaygroupingfavourites);
        $this->assertEquals('card,list,description', $configs->plugin->layouts);
        $this->assertEquals(get_config('block_muprogmyoverview', 'version'), $configs->plugin->version);
    }
}
