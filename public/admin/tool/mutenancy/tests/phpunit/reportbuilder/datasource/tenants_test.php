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

namespace tool_mutenancy\phpunit\reportbuilder\datasource;

use tool_mutenancy\reportbuilder\datasource\tenants as templatesource;

/**
 * Multi-tenancy tenants datasource tests.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mutenancy\reportbuilder\datasource\tenants
 */
final class tenants_test extends \core_reportbuilder\tests\core_reportbuilder_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_datasource(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        /** @var \core_reportbuilder_generator $rbgenerator */
        $rbgenerator = self::getDataGenerator()->get_plugin_generator('core_reportbuilder');

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();
        $tenant3 = $generator->create_tenant(['archived' => 1]);

        $report = $rbgenerator->create_report([
            'name' => 'RB tenants',
            'source' => templatesource::class,
            'default' => false,
        ]);

        $rbgenerator->create_column(['reportid' => $report->get('id'), 'uniqueidentifier' => 'tenant:name']);
        $rbgenerator->create_column(['reportid' => $report->get('id'), 'uniqueidentifier' => 'tenant:idnumber']);
        $rbgenerator->create_column(['reportid' => $report->get('id'), 'uniqueidentifier' => 'tenant:archived']);

        $content = $this->get_custom_report_content($report->get('id'));
        $this->assertCount(3, $content);

        $contentcerts = [
            [$tenant1->name, $tenant1->idnumber, 'No'],
            [$tenant2->name, $tenant2->idnumber, 'No'],
            [$tenant3->name, $tenant3->idnumber, 'Yes'],
        ];
        $this->assertEqualsCanonicalizing($contentcerts, $content);
    }

    public function test_stress_datasource(): void {
        global $DB;

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();
        $tenant3 = $generator->create_tenant(['archived' => 1]);

        $this->datasource_stress_test_columns(templatesource::class);
        if ($DB->get_dbfamily() !== 'mssql') {
            $this->datasource_stress_test_columns_aggregation(templatesource::class);
        }
        $this->datasource_stress_test_conditions(templatesource::class, 'tenant:name');
        $this->datasource_stress_test_conditions(templatesource::class, 'tenant:idnumber');
        $this->datasource_stress_test_conditions(templatesource::class, 'tenant:archived');
    }
}
