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

use tool_mutenancy\event\user_allocated;
use tool_mutenancy\local\tenancy;
use tool_mutenancy\local\tenant;

/**
 * User tenant allocation changed event tests.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mutenancy\event\tenant_updated
 */
final class user_allocated_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_event(): void {
        tenancy::activate();

        $data = (object)[
            'name' => 'Some tenant 1',
            'idnumber' => 't1',
        ];
        $tenant1 = tenant::create($data);

        $data = (object)[
            'name' => 'Some tenant 2',
            'idnumber' => 't2',
        ];
        $tenant2 = tenant::create($data);

        $user = $this->getDataGenerator()->create_user();
        $usercontext = \context_user::instance($user->id);

        $sink = $this->redirectEvents();

        $user = \tool_mutenancy\local\user::allocate($user->id, $tenant1->id);
        $events = $sink->get_events();
        $sink->clear();
        $this->assertCount(3, $events);
        $this->assertInstanceOf(user_allocated::class, $events[2]);
        $event = $events[2];
        $this->assertEquals($usercontext->id, $event->contextid);
        $this->assertEquals((int)$tenant1->id, $event->other['tenantid']);
        $this->assertSame($user->id, $event->objectid);
        $this->assertSame('u', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame('user', $event->objecttable);
        $this->assertSame('User tenant allocation changed', $event::get_name());
        $description = $event->get_description();
        $tenanturl = new \moodle_url('/user/profile.php', ['id' => $user->id]);
        $this->assertSame($tenanturl->out(false), $event->get_url()->out(false));

        $user = \tool_mutenancy\local\user::allocate($user->id, $tenant2->id);
        $events = $sink->get_events();
        $sink->clear();
        $this->assertCount(5, $events);
        $this->assertInstanceOf(user_allocated::class, $events[4]);
        $event = $events[4];
        $this->assertEquals($usercontext->id, $event->contextid);
        $this->assertEquals((int)$tenant2->id, $event->other['tenantid']);
        $this->assertSame($user->id, $event->objectid);
        $this->assertSame('u', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame('user', $event->objecttable);
        $this->assertSame('User tenant allocation changed', $event::get_name());
        $description = $event->get_description();
        $tenanturl = new \moodle_url('/user/profile.php', ['id' => $user->id]);
        $this->assertSame($tenanturl->out(false), $event->get_url()->out(false));

        $user = \tool_mutenancy\local\user::allocate($user->id, null);
        $events = $sink->get_events();
        $sink->clear();
        $this->assertCount(3, $events);
        $this->assertInstanceOf(user_allocated::class, $events[2]);
        $event = $events[2];
        $this->assertEquals($usercontext->id, $event->contextid);
        $this->assertEquals(0, $event->other['tenantid']);
        $this->assertSame($user->id, $event->objectid);
        $this->assertSame('u', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame('user', $event->objecttable);
        $this->assertSame('User tenant allocation changed', $event::get_name());
        $description = $event->get_description();
        $tenanturl = new \moodle_url('/user/profile.php', ['id' => $user->id]);
        $this->assertSame($tenanturl->out(false), $event->get_url()->out(false));

        $sink->close();
    }
}
