<?php
// This file is part of MuTMS suite of plugins for Moodle™ LMS.
//
// This tenant is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This tenant is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this tenant.  If not, see <https://www.gnu.org/licenses/>.

// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon
// phpcs:disable moodle.Files.LineLength.TooLong

namespace tool_mutenancy\phpunit\event;

use tool_mutenancy\event\tenant_restored;
use tool_mutenancy\local\tenancy;
use tool_mutenancy\local\tenant;

/**
 * Tenant restored event tests.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mutenancy\event\tenant_restored
 */
final class tenant_restored_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_event(): void {
        tenancy::activate();

        $data = (object)[
            'name' => 'Some tenant 1',
            'idnumber' => 't1',
            'archived' => '1',
        ];
        $tenant = tenant::create($data);
        $tenantcontext = \context_tenant::instance($tenant->id);

        $sink = $this->redirectEvents();
        $tenant = tenant::restore($tenant->id);
        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(2, $events);
        $this->assertInstanceOf(\core\event\course_category_updated::class, $events[0]);
        $this->assertInstanceOf(tenant_restored::class, $events[1]);

        $event = $events[1];
        $this->assertEquals($tenantcontext->id, $event->contextid);
        $this->assertSame($tenant->id, $event->objectid);
        $this->assertSame('u', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame('tool_mutenancy_tenant', $event->objecttable);
        $this->assertSame('Tenant restored', $event::get_name());
        $description = $event->get_description();
        $tenanturl = new \moodle_url('/admin/tool/mutenancy/management/tenant.php', ['id' => $tenant->id]);
        $this->assertSame($tenanturl->out(false), $event->get_url()->out(false));
    }
}
