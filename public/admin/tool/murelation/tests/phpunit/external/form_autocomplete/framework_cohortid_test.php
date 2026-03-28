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

namespace tool_murelation\phpunit\external\form_autocomplete;

use tool_murelation\external\form_autocomplete\framework_cohortid;

/**
 * Relation framework cohort selection external function tests.
 *
 * @group       MuTMS
 * @package     tool_murelation
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \tool_murelation\external\form_autocomplete\framework_cohortid
 */
final class framework_cohortid_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers ::execute
     */
    public function test_execute(): void {
        $syscontext = \context_system::instance();

        $roleid = create_role('man', 'man', 'man');
        assign_capability('tool/murelation:manageframeworks', CAP_ALLOW, $roleid, $syscontext->id);

        $user0 = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user([
            'firstname' => 'Global',
            'lastname' => 'Manager',
            'email' => 'manager@example.com',
        ]);
        role_assign($roleid, $manager->id, $syscontext->id);

        $cohort1 = $this->getDataGenerator()->create_cohort([
            'name' => 'First kohort',
            'idnumber' => 'koh1',
        ]);
        $cohort2 = $this->getDataGenerator()->create_cohort([
            'name' => 'Second kohort',
            'idnumber' => 'koh2',
        ]);
        $cohort3 = $this->getDataGenerator()->create_cohort([
            'name' => 'Third kohort',
            'idnumber' => 'koh3',
            'visible' => 0,
        ]);

        $this->setUser($manager);

        $result = framework_cohortid::execute('');
        $this->assertfalse($result['overflow']);
        $expected = [
            ['value' => $cohort1->id, 'label' => $cohort1->name],
            ['value' => $cohort2->id, 'label' => $cohort2->name],
        ];
        $this->assertSame($expected, $result['list']);

        $result = framework_cohortid::execute('First');
        $this->assertfalse($result['overflow']);
        $expected = [
            ['value' => $cohort1->id, 'label' => $cohort1->name],
        ];
        $this->assertSame($expected, $result['list']);

        $result = framework_cohortid::execute('koh2');
        $this->assertfalse($result['overflow']);
        $expected = [
            ['value' => $cohort2->id, 'label' => $cohort2->name],
        ];
        $this->assertSame($expected, $result['list']);

        assign_capability('moodle/cohort:view', CAP_ALLOW, $roleid, $syscontext->id);
        $this->setUser($manager);

        $result = framework_cohortid::execute('');
        $this->assertfalse($result['overflow']);
        $expected = [
            ['value' => $cohort1->id, 'label' => $cohort1->name],
            ['value' => $cohort2->id, 'label' => $cohort2->name],
            ['value' => $cohort3->id, 'label' => $cohort3->name],
        ];
        $this->assertSame($expected, $result['list']);

        $this->setUser($user0);
        try {
            framework_cohortid::execute('');
            $this->fail('exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
            $this->assertSame('Sorry, but you do not currently have permissions to do that (Manage user relation frameworks).', $ex->getMessage());
        }
    }

    /**
     * @covers ::validate_value
     */
    public function test_validate_value(): void {
        $syscontext = \context_system::instance();

        $roleid = create_role('man', 'man', 'man');
        assign_capability('tool/murelation:manageframeworks', CAP_ALLOW, $roleid, $syscontext->id);

        $manager = $this->getDataGenerator()->create_user([
            'firstname' => 'Global',
            'lastname' => 'Manager',
            'email' => 'manager@example.com',
        ]);
        role_assign($roleid, $manager->id, $syscontext->id);

        $cohort1 = $this->getDataGenerator()->create_cohort([
            'name' => 'First kohort',
            'idnumber' => 'koh1',
        ]);
        $cohort2 = $this->getDataGenerator()->create_cohort([
            'name' => 'Second kohort',
            'idnumber' => 'koh2',
        ]);
        $cohort3 = $this->getDataGenerator()->create_cohort([
            'name' => 'Third kohort',
            'idnumber' => 'koh3',
            'visible' => 0,
        ]);

        $this->setUser($manager);
        $this->assertSame(null, framework_cohortid::validate_value($cohort1->id, [], $syscontext));
        $this->assertSame(null, framework_cohortid::validate_value($cohort2->id, [], $syscontext));
        $this->assertSame('Error', framework_cohortid::validate_value($cohort3->id, [], $syscontext));
        $this->assertSame(null, framework_cohortid::validate_value($cohort3->id, ['currentValue' => $cohort3->id], $syscontext));
        $this->assertSame('Error', framework_cohortid::validate_value(-10, [], $syscontext));

        assign_capability('moodle/cohort:view', CAP_ALLOW, $roleid, $syscontext->id);
        $this->setUser($manager);
        $this->assertSame(null, framework_cohortid::validate_value($cohort3->id, [], $syscontext));
    }
}
