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

namespace tool_mucertify\phpunit\local\notification;

use tool_mucertify\local\source\manual;

/**
 * Certification notifications base test.
 *
 * @group      MuTMS
 * @package    tool_mucertify
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucertify\local\notification\base
 */
final class base_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_assignment_placeholders(): void {
        global $DB, $CFG;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $certification1 = $generator->create_certification(['sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification1->id, 'type' => 'manual'], '*', MUST_EXIST);

        $certification2 = $generator->create_certification(['sources' => ['manual' => []]]);
        $source2 = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification2->id, 'type' => 'manual'], '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $related1 = $this->getDataGenerator()->create_user();

        manual::assign_users($certification1->id, $source1->id, [$user1->id]);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['certificationid' => $certification1->id, 'userid' => $user1->id], '*', MUST_EXIST);

        $strnotset = get_string('notset', 'tool_mucertify');

        $result = \tool_mucertify\local\notification\base::get_assignment_placeholders($certification1, $source1, $assignment, $user1);
        $this->assertIsArray($result);
        $this->assertSame(\fullname($user1), $result['user_fullname']);
        $this->assertSame($user1->firstname, $result['user_firstname']);
        $this->assertSame($user1->lastname, $result['user_lastname']);
        $this->assertSame($certification1->fullname, $result['certification_fullname']);
        $this->assertSame($certification1->idnumber, $result['certification_idnumber']);
        $this->assertSame("$CFG->wwwroot/admin/tool/mucertify/my/certification.php?id=$certification1->id", $result['certification_url']);
        $this->assertSame('Manual assignment', $result['certification_sourcename']);
        $this->assertStringContainsString('Not certified', $result['certification_status']);

        $result = \tool_mucertify\local\notification\base::get_assignment_placeholders($certification1, $source1, $assignment, $user1, $related1);
        $this->assertIsArray($result);
        $this->assertSame(\fullname($user1), $result['user_fullname']);
        $this->assertSame($user1->firstname, $result['user_firstname']);
        $this->assertSame($user1->lastname, $result['user_lastname']);
        $this->assertSame($certification1->fullname, $result['certification_fullname']);
        $this->assertSame($certification1->idnumber, $result['certification_idnumber']);
        $this->assertSame("$CFG->wwwroot/admin/tool/mucertify/my/certification.php?id=$certification1->id", $result['certification_url']);
        $this->assertSame('Manual assignment', $result['certification_sourcename']);
        $this->assertStringContainsString('Not certified', $result['certification_status']);
    }

    public function test_get_notifier(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $certification1 = $generator->create_certification(['sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification1->id, 'type' => 'manual'], '*', MUST_EXIST);

        $certification2 = $generator->create_certification(['sources' => ['manual' => []]]);
        $source2 = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification2->id, 'type' => 'manual'], '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        manual::assign_users($certification1->id, $source1->id, [$user1->id]);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['certificationid' => $certification1->id, 'userid' => $user1->id], '*', MUST_EXIST);

        $this->setUser(null);
        $result = \tool_mucertify\local\notification\base::get_notifier($certification1, $assignment1);
        $this->assertSame(-10, $result->id);

        $this->setUser($user2);
        $result = \tool_mucertify\local\notification\base::get_notifier($certification1, $assignment1);
        $this->assertSame(-10, $result->id);
    }
}
