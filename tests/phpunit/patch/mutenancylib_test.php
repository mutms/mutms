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

use tool_mutenancy\local\tenancy;
use tool_mutenancy\local\config;

/**
 * Multi-tenancy tests for lib/mutenancylib.php core additions.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class mutenancylib_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers ::mutenancy_is_active()
     */
    public function test_mutenancy_is_active(): void {
        tenancy::deactivate();
        $this->assertFalse(tenancy::is_active());
        tenancy::activate();
        $this->assertTrue(tenancy::is_active());
    }

    /**
     * @covers ::mutenancy_get_config()
     */
    public function test_mutenancy_get_config(): void {
        global $CFG;

        if (tenancy::is_active()) {
            tenancy::deactivate();
        }

        set_config('c1', '123', null);
        set_config('c2', '234', null);
        $CFG->config_php_settings['c2'] = 'ijk';
        set_config('c3', '345', null);
        $CFG->config_php_settings['c3'] = 'efg';
        set_config('c4', '456', null);
        set_config('c5', '567', null);

        // Simulate bad test stuff.
        $this->setUser(null);
        $CFG->c4 = 'oops';
        $this->assertSame('456', get_config('core', 'c4'));
        $this->assertSame('oops', mutenancy_get_config('core', 'c4'));

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        tenancy::activate();

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        config::override($tenant1->id, 'c3', 'x3', 'core');
        config::override($tenant1->id, 'c4', 'x4', 'core');
        config::override($tenant1->id, 'c6', 'x6', 'core');
        config::override($tenant2->id, 'c5', 'z5', 'core');

        $this->assertSame('123', get_config('core', 'c1'));
        $this->assertSame('ijk', get_config('core', 'c2'));
        $this->assertSame('efg', get_config('core', 'c3'));
        $this->assertSame('456', get_config('core', 'c4'));
        $this->assertSame('567', get_config('core', 'c5'));
        $this->assertSame(false, get_config('core', 'c6'));

        $this->setUser($user0);
        $this->assertSame('123', mutenancy_get_config('core', 'c1'));
        $this->assertSame('ijk', mutenancy_get_config('core', 'c2'));
        $this->assertSame('efg', mutenancy_get_config('core', 'c3'));
        $this->assertSame('oops', mutenancy_get_config('core', 'c4'));
        $this->assertSame('567', mutenancy_get_config('core', 'c5'));
        $this->assertSame(false, mutenancy_get_config('core', 'c6'));

        tenancy::force_current_tenantid(null);
        $this->assertSame('123', mutenancy_get_config('core', 'c1'));
        $this->assertSame('ijk', mutenancy_get_config('core', 'c2'));
        $this->assertSame('efg', mutenancy_get_config('core', 'c3'));
        $this->assertSame('oops', mutenancy_get_config('core', 'c4'));
        $this->assertSame('567', mutenancy_get_config('core', 'c5'));
        $this->assertSame(false, mutenancy_get_config('core', 'c6'));
        tenancy::unforce_current_tenantid();

        $this->setUser($user1);
        $this->assertSame('123', mutenancy_get_config('core', 'c1'));
        $this->assertSame('ijk', mutenancy_get_config('core', 'c2'));
        $this->assertSame('efg', mutenancy_get_config('core', 'c3'));
        $this->assertSame('x4', mutenancy_get_config('core', 'c4'));
        $this->assertSame('567', mutenancy_get_config('core', 'c5'));
        $this->assertSame('x6', mutenancy_get_config('core', 'c6'));

        $this->setUser($user0);
        tenancy::force_current_tenantid($tenant1->id);
        $this->assertSame('123', mutenancy_get_config('core', 'c1'));
        $this->assertSame('ijk', mutenancy_get_config('core', 'c2'));
        $this->assertSame('efg', mutenancy_get_config('core', 'c3'));
        $this->assertSame('x4', mutenancy_get_config('core', 'c4'));
        $this->assertSame('567', mutenancy_get_config('core', 'c5'));
        $this->assertSame('x6', mutenancy_get_config('core', 'c6'));
        tenancy::unforce_current_tenantid();

        $this->setUser($user2);
        $this->assertSame('123', mutenancy_get_config('core', 'c1'));
        $this->assertSame('ijk', mutenancy_get_config('core', 'c2'));
        $this->assertSame('efg', mutenancy_get_config('core', 'c3'));
        $this->assertSame('456', mutenancy_get_config('core', 'c4'));
        $this->assertSame('z5', mutenancy_get_config('core', 'c5'));
        $this->assertSame(false, mutenancy_get_config('core', 'c6'));

        $this->setUser($user0);
        tenancy::force_current_tenantid($tenant2->id);
        $this->assertSame('123', mutenancy_get_config('core', 'c1'));
        $this->assertSame('ijk', mutenancy_get_config('core', 'c2'));
        $this->assertSame('efg', mutenancy_get_config('core', 'c3'));
        $this->assertSame('456', mutenancy_get_config('core', 'c4'));
        $this->assertSame('z5', mutenancy_get_config('core', 'c5'));
        $this->assertSame(false, mutenancy_get_config('core', 'c6'));
        tenancy::unforce_current_tenantid();

        // Make sure the bad test stuff fails if multitenancy enabled.
        $this->setUser(null);
        $CFG->c4 = 'oops';
        $this->assertSame('456', get_config('core', 'c4'));

        tenancy::force_current_tenantid(null);
        $this->assertSame('oops', mutenancy_get_config('core', 'c4'));
        tenancy::unforce_current_tenantid();

        tenancy::force_current_tenantid($tenant1->id);
        $this->assertSame('x4', mutenancy_get_config('core', 'c4'));
        tenancy::unforce_current_tenantid();

        tenancy::force_current_tenantid($tenant2->id);
        $this->assertSame('456', mutenancy_get_config('core', 'c4'));
        tenancy::unforce_current_tenantid();

        // Plugin settings.

        set_config('p1', 'v1', 'tool_xnothing');
        set_config('p2', 'v2', 'tool_xnothing');
        $CFG->forced_plugin_settings['tool_xnothing']['p2'] = 'w2';
        set_config('p3', 'v3', 'tool_xnothing');
        $CFG->forced_plugin_settings['tool_xnothing']['p3'] = 'x3';
        set_config('p4', 'v4', 'tool_xnothing');
        set_config('p5', 'v5', 'tool_xnothing');
        config::override($tenant1->id, 'p3', 'o3', 'tool_xnothing');
        config::override($tenant1->id, 'p4', 'o4', 'tool_xnothing');
        config::override($tenant1->id, 'p6', 'o6', 'tool_xnothing');
        config::override($tenant2->id, 'p5', 'o5', 'tool_xnothing');

        $this->assertSame('v1', get_config('tool_xnothing', 'p1'));
        $this->assertSame('w2', get_config('tool_xnothing', 'p2'));
        $this->assertSame('x3', get_config('tool_xnothing', 'p3'));
        $this->assertSame('v4', get_config('tool_xnothing', 'p4'));
        $this->assertSame('v5', get_config('tool_xnothing', 'p5'));
        $this->assertSame(false, get_config('tool_xnothing', 'p6'));

        tenancy::force_current_tenantid(null);
        $this->assertSame('v1', mutenancy_get_config('tool_xnothing', 'p1'));
        $this->assertSame('w2', mutenancy_get_config('tool_xnothing', 'p2'));
        $this->assertSame('x3', mutenancy_get_config('tool_xnothing', 'p3'));
        $this->assertSame('v4', mutenancy_get_config('tool_xnothing', 'p4'));
        $this->assertSame('v5', mutenancy_get_config('tool_xnothing', 'p5'));
        $this->assertSame(false, mutenancy_get_config('tool_xnothing', 'p6'));
        tenancy::unforce_current_tenantid();

        tenancy::force_current_tenantid($tenant1->id);
        $this->assertSame('v1', mutenancy_get_config('tool_xnothing', 'p1'));
        $this->assertSame('w2', mutenancy_get_config('tool_xnothing', 'p2'));
        $this->assertSame('x3', mutenancy_get_config('tool_xnothing', 'p3'));
        $this->assertSame('o4', mutenancy_get_config('tool_xnothing', 'p4'));
        $this->assertSame('v5', mutenancy_get_config('tool_xnothing', 'p5'));
        $this->assertSame('o6', mutenancy_get_config('tool_xnothing', 'p6'));
        tenancy::unforce_current_tenantid();

        $expected0 = [
            'p1' => 'v1',
            'p2' => 'w2',
            'p3' => 'x3',
            'p4' => 'v4',
            'p5' => 'v5',
        ];
        $expected1 = [
            'p1' => 'v1',
            'p2' => 'w2',
            'p3' => 'x3',
            'p4' => 'o4',
            'p5' => 'v5',
            'p6' => 'o6',
        ];
        $expected2 = [
            'p1' => 'v1',
            'p2' => 'w2',
            'p3' => 'x3',
            'p4' => 'v4',
            'p5' => 'o5',
        ];

        $result = get_config('tool_xnothing');
        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertSame($expected0, (array)$result);

        tenancy::force_current_tenantid(null);
        $result = mutenancy_get_config('tool_xnothing');
        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertSame($expected0, (array)$result);
        tenancy::unforce_current_tenantid();

        tenancy::force_current_tenantid($tenant1->id);
        $result = mutenancy_get_config('tool_xnothing');
        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertSame($expected1, (array)$result);
        tenancy::unforce_current_tenantid();

        tenancy::force_current_tenantid($tenant2->id);
        $result = mutenancy_get_config('tool_xnothing');
        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertSame($expected2, (array)$result);
        tenancy::unforce_current_tenantid();
    }

    /**
     * @covers ::mutenancy_is_user_archived()
     */
    public function test_mutenancy_is_user_archived(): void {
        if (tenancy::is_active()) {
            tenancy::deactivate();
        }

        $user0 = $this->getDataGenerator()->create_user();
        $guest = guest_user();

        $this->assertFalse(mutenancy_is_user_archived($guest));
        $this->assertFalse(mutenancy_is_user_archived(0));
        $this->assertFalse(mutenancy_is_user_archived($user0));
        $this->assertFalse(mutenancy_is_user_archived($user0->id));

        tenancy::activate();

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant(['archived' => 1]);

        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $user3 = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $user4 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id, 'suspended' => 1]);

        $this->assertFalse(mutenancy_is_user_archived($guest));
        $this->assertFalse(mutenancy_is_user_archived($user0->id));
        $this->assertFalse(mutenancy_is_user_archived($user0));
        $this->assertFalse(mutenancy_is_user_archived($user1));
        $this->assertFalse(mutenancy_is_user_archived($user3));
        $this->assertFalse(mutenancy_is_user_archived($user4));
        $this->assertTrue(mutenancy_is_user_archived($user2));
        $this->assertTrue(mutenancy_is_user_archived($user2->id));
        unset($user1->tenantid);
        $this->assertFalse(mutenancy_is_user_archived($user1));
        unset($user2->tenantid);
        $this->assertTrue(mutenancy_is_user_archived($user2));
    }
}
