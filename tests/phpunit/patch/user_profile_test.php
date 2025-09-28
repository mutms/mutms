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

namespace tool_mutenancy\phpunit\patch;

/**
 * Multi-tenancy tests for upstream modifications.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class user_profile_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers \profile_field_base::is_visible
     */
    public function test_profile_field_base_is_visible(): void {
        global $CFG;
        require_once("$CFG->dirroot/user/profile/lib.php");

        $field1 = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'text',
            'shortname' => 'a',
            'name' => 'A',
            'visible' => PROFILE_VISIBLE_PRIVATE,
        ]);
        $field2 = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'text',
            'shortname' => 'b',
            'name' => 'B',
            'visible' => PROFILE_VISIBLE_NONE,
        ]);

        $user0 = $this->getDataGenerator()->create_user();

        $this->setAdminUser();
        $text1 = profile_get_user_field('text', $field1->id, $user0->id);
        $text2 = profile_get_user_field('text', $field2->id, $user0->id);
        $this->assertTrue($text1->is_visible());
        $this->assertTrue($text2->is_visible());

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();

        $manager1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        \tool_mutenancy\local\manager::add($tenant1->id, $manager1->id);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        $this->setAdminUser();
        $text1 = profile_get_user_field('text', $field1->id, $user0->id);
        $text2 = profile_get_user_field('text', $field2->id, $user0->id);
        $this->assertTrue($text1->is_visible());
        $this->assertTrue($text2->is_visible());
        $text1 = profile_get_user_field('text', $field1->id, $user1->id);
        $text2 = profile_get_user_field('text', $field2->id, $user1->id);
        $this->assertTrue($text1->is_visible());
        $this->assertTrue($text2->is_visible());
        $text1 = profile_get_user_field('text', $field1->id, $user2->id);
        $text2 = profile_get_user_field('text', $field2->id, $user2->id);
        $this->assertTrue($text1->is_visible());
        $this->assertTrue($text2->is_visible());

        $this->setUser($manager1);
        $text1 = profile_get_user_field('text', $field1->id, $user0->id);
        $text2 = profile_get_user_field('text', $field2->id, $user0->id);
        $this->assertFalse($text1->is_visible());
        $this->assertFalse($text2->is_visible());
        $text1 = profile_get_user_field('text', $field1->id, $user1->id);
        $text2 = profile_get_user_field('text', $field2->id, $user1->id);
        $this->assertTrue($text1->is_visible());
        $this->assertTrue($text2->is_visible());
        $text1 = profile_get_user_field('text', $field1->id, $user2->id);
        $text2 = profile_get_user_field('text', $field2->id, $user2->id);
        $this->assertFalse($text1->is_visible());
        $this->assertFalse($text2->is_visible());
    }

    /**
     * @covers \profile_field_base::is_editable
     */
    public function test_profile_field_base_is_editable(): void {
        global $CFG;
        require_once("$CFG->dirroot/user/profile/lib.php");

        $field1 = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'text',
            'shortname' => 'a',
            'name' => 'A',
            'visible' => PROFILE_VISIBLE_PRIVATE,
        ]);
        $field2 = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'text',
            'shortname' => 'b',
            'name' => 'B',
            'visible' => PROFILE_VISIBLE_NONE,
        ]);

        $user0 = $this->getDataGenerator()->create_user();

        $this->setAdminUser();
        $text1 = profile_get_user_field('text', $field1->id, $user0->id);
        $text2 = profile_get_user_field('text', $field2->id, $user0->id);
        $this->assertTrue($text1->is_editable());
        $this->assertTrue($text2->is_editable());

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();

        $manager1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        \tool_mutenancy\local\manager::add($tenant1->id, $manager1->id);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        $this->setAdminUser();
        $text1 = profile_get_user_field('text', $field1->id, $user0->id);
        $text2 = profile_get_user_field('text', $field2->id, $user0->id);
        $this->assertTrue($text1->is_editable());
        $this->assertTrue($text2->is_editable());
        $text1 = profile_get_user_field('text', $field1->id, $user1->id);
        $text2 = profile_get_user_field('text', $field2->id, $user1->id);
        $this->assertTrue($text1->is_editable());
        $this->assertTrue($text2->is_editable());
        $text1 = profile_get_user_field('text', $field1->id, $user2->id);
        $text2 = profile_get_user_field('text', $field2->id, $user2->id);
        $this->assertTrue($text1->is_editable());
        $this->assertTrue($text2->is_editable());

        $this->setUser($manager1);
        $text1 = profile_get_user_field('text', $field1->id, $user0->id);
        $text2 = profile_get_user_field('text', $field2->id, $user0->id);
        $this->assertFalse($text1->is_editable());
        $this->assertFalse($text2->is_editable());
        $text1 = profile_get_user_field('text', $field1->id, $user1->id);
        $text2 = profile_get_user_field('text', $field2->id, $user1->id);
        $this->assertTrue($text1->is_editable());
        $this->assertTrue($text2->is_editable());
        $text1 = profile_get_user_field('text', $field1->id, $user2->id);
        $text2 = profile_get_user_field('text', $field2->id, $user2->id);
        $this->assertFalse($text1->is_editable());
        $this->assertFalse($text2->is_editable());
    }
}
