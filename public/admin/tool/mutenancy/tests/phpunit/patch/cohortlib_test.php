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
 * Multi-tenancy tests for cohort/lib.php modifications.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class cohortlib_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers ::cohort_delete_cohort()
     */
    public function test_cohort_delete_cohort(): void {
        global $DB;

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $this->assertTrue($DB->record_exists('cohort', ['id' => $tenant1->cohortid]));

        $cohort = $DB->get_record('cohort', ['id' => $tenant1->cohortid], '*', MUST_EXIST);
        cohort_delete_cohort($cohort);
        $this->assertTrue($DB->record_exists('cohort', ['id' => $tenant1->cohortid]));

        $tenant1 = \tool_mutenancy\local\tenant::archive($tenant1->id);
        \tool_mutenancy\local\tenant::delete($tenant1->id);
        $this->assertTrue($DB->record_exists('cohort', ['id' => $tenant1->cohortid]));

        cohort_delete_cohort($cohort);
        $this->assertFalse($DB->record_exists('cohort', ['id' => $tenant1->cohortid]));
    }
}
