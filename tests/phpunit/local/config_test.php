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

namespace tool_mutenancy\phpunit\local;

use tool_mutenancy\local\config;
use tool_mutenancy\local\tenancy;

/**
 * Multi-tenancy config tests.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \tool_mutenancy\local\config
 */
final class config_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers ::is_value_forced
     */
    public function test_is_value_forced(): void {
        global $CFG;

        // Core.

        $this->assertFalse(config::is_value_forced('core', 'xyz'));
        $this->assertSame(false, get_config('core', 'xyz'));

        set_config('xyz', '1234', null);
        $this->assertFalse(config::is_value_forced('core', 'xyz'));
        $this->assertSame('1234', get_config('core', 'xyz'));

        $CFG->config_php_settings['xyz'] = 'abc';
        $this->assertTrue(config::is_value_forced('core', 'xyz'));
        $this->assertSame('abc', get_config('core', 'xyz'));

        // Plugins.

        $this->assertFalse(config::is_value_forced('tool_mutenancy', 'xyz'));
        $this->assertSame(false, get_config('tool_mutenancy', 'xyz'));

        set_config('xyz', '1234', 'tool_mutenancy');
        $this->assertFalse(config::is_value_forced('tool_mutenancy', 'xyz'));
        $this->assertSame('1234', get_config('tool_mutenancy', 'xyz'));

        $CFG->forced_plugin_settings['tool_mutenancy']['xyz'] = 'abc';
        $this->assertTrue(config::is_value_forced('tool_mutenancy', 'xyz'));
        $this->assertSame('abc', get_config('tool_mutenancy', 'xyz'));
    }

    /**
     * @covers ::override
     */
    public function test_override(): void {
        global $DB;

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();

        config::override($tenant1->id, 'xyz', '123', 'moodle');
        $record = $DB->get_record(
            'tool_mutenancy_config',
            ['tenantid' => $tenant1->id, 'plugin' => 'core', 'name' => 'xyz'],
            '*',
            MUST_EXIST
        );
        $this->assertSame('123', $record->value);

        config::override($tenant1->id, 'xyz', 'abc', 'core');
        $record = $DB->get_record(
            'tool_mutenancy_config',
            ['tenantid' => $tenant1->id, 'plugin' => 'core', 'name' => 'xyz'],
            '*',
            MUST_EXIST
        );
        $this->assertSame('abc', $record->value);

        config::override($tenant1->id, 'xyz', false, '');
        $record = $DB->get_record(
            'tool_mutenancy_config',
            ['tenantid' => $tenant1->id, 'plugin' => 'core', 'name' => 'xyz'],
            '*',
            MUST_EXIST
        );
        $this->assertSame('0', $record->value);

        config::override($tenant1->id, 'xyz', null, 'core');
        $record = $DB->get_record(
            'tool_mutenancy_config',
            ['tenantid' => $tenant1->id, 'plugin' => 'core', 'name' => 'xyz']
        );
        $this->assertSame(false, $record);

        config::override($tenant1->id, 'xyz', '123', 'tool_mutenancy');
        $record = $DB->get_record(
            'tool_mutenancy_config',
            ['tenantid' => $tenant1->id, 'plugin' => 'tool_mutenancy', 'name' => 'xyz'],
            '*',
            MUST_EXIST
        );
        $this->assertSame('123', $record->value);
    }

    /**
     * @covers ::is_overridden
     */
    public function test_is_overridden(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();

        $this->assertFalse(config::is_overridden($tenant1->id, 'moodle', 'xyz'));
        $this->assertFalse(config::is_overridden($tenant1->id, 'core', 'xyz'));
        $this->assertFalse(config::is_overridden($tenant1->id, '', 'xyz'));

        config::override($tenant1->id, 'xyz', '123', 'moodle');
        $this->assertTrue(config::is_overridden($tenant1->id, 'moodle', 'xyz'));
        $this->assertTrue(config::is_overridden($tenant1->id, 'core', 'xyz'));
        $this->assertTrue(config::is_overridden($tenant1->id, '', 'xyz'));

        config::override($tenant2->id, 'xyz', '123', 'tool_mutenancy');
        $this->assertTrue(config::is_overridden($tenant2->id, 'tool_mutenancy', 'xyz'));

        config::override($tenant2->id, 'xyz', null, 'tool_mutenancy');
        $this->assertFalse(config::is_overridden($tenant2->id, 'tool_mutenancy', 'xyz'));
    }

    /**
     * @covers ::fetch_overrides
     */
    public function test_fetch_overrides(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();

        $this->assertSame([], config::fetch_overrides($tenant1->id, 'core'));

        config::override($tenant1->id, 'xyz', '123', 'moodle');
        $this->assertSame(['xyz' => '123'], config::fetch_overrides($tenant1->id, 'core'));

        config::override($tenant1->id, 'abc', '345', 'core');
        $this->assertSame(['abc' => '345', 'xyz' => '123'], config::fetch_overrides($tenant1->id, 'moodle'));
        $this->assertSame(['abc' => '345', 'xyz' => '123'], config::fetch_overrides($tenant1->id, ''));

        $this->assertSame([], config::fetch_overrides($tenant2->id, 'tool_mutenancy'));
        config::override($tenant2->id, 'xyz', '123', 'tool_mutenancy');
        $this->assertSame(['xyz' => '123'], config::fetch_overrides($tenant2->id, 'tool_mutenancy'));
    }

    /**
     * @covers ::get
     */
    public function test_get(): void {
        global $CFG;

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();

        set_config('c1', '123', null);
        set_config('c2', '234', null);
        $CFG->config_php_settings['c2'] = 'ijk';
        set_config('c3', '345', null);
        $CFG->config_php_settings['c3'] = 'efg';
        set_config('c4', '456', null);
        set_config('c5', '567', null);
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
        $this->assertSame('123', config::get(null, 'core', 'c1'));
        $this->assertSame('ijk', config::get(null, 'core', 'c2'));
        $this->assertSame('efg', config::get(null, 'core', 'c3'));
        $this->assertSame('456', config::get(null, 'core', 'c4'));
        $this->assertSame('567', config::get(null, 'core', 'c5'));
        $this->assertSame(false, config::get(null, 'core', 'c6'));
        $this->assertSame('123', config::get($tenant1->id, 'core', 'c1'));
        $this->assertSame('ijk', config::get($tenant1->id, 'core', 'c2'));
        $this->assertSame('efg', config::get($tenant1->id, 'core', 'c3'));
        $this->assertSame('x4', config::get($tenant1->id, 'core', 'c4'));
        $this->assertSame('567', config::get($tenant1->id, 'core', 'c5'));
        $this->assertSame('x6', config::get($tenant1->id, 'core', 'c6'));
        $this->assertSame('123', config::get(-1, 'core', 'c1'));
        $this->assertSame('ijk', config::get(-1, 'core', 'c2'));
        $this->assertSame('efg', config::get(-1, 'core', 'c3'));
        $this->assertSame('456', config::get(-1, 'core', 'c4'));
        $this->assertSame('567', config::get(-1, 'core', 'c5'));
        $this->assertSame(false, config::get(-1, 'core', 'c6'));

        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $this->setUser($user1);
        $this->assertSame('123', config::get(-1, 'core', 'c1'));
        $this->assertSame('ijk', config::get(-1, 'core', 'c2'));
        $this->assertSame('efg', config::get(-1, 'core', 'c3'));
        $this->assertSame('x4', config::get(-1, 'core', 'c4'));
        $this->assertSame('567', config::get(-1, 'core', 'c5'));
        $this->assertSame('x6', config::get(-1, 'core', 'c6'));

        tenancy::force_current_tenantid(null);
        $this->assertSame('123', config::get(-1, 'core', 'c1'));
        $this->assertSame('ijk', config::get(-1, 'core', 'c2'));
        $this->assertSame('efg', config::get(-1, 'core', 'c3'));
        $this->assertSame('456', config::get(-1, 'core', 'c4'));
        $this->assertSame('567', config::get(-1, 'core', 'c5'));
        $this->assertSame(false, config::get(-1, 'core', 'c6'));
        tenancy::unforce_current_tenantid();

        tenancy::force_current_tenantid($tenant2->id);
        $this->assertSame('123', config::get(-1, 'core', 'c1'));
        $this->assertSame('ijk', config::get(-1, 'core', 'c2'));
        $this->assertSame('efg', config::get(-1, 'core', 'c3'));
        $this->assertSame('456', config::get(-1, 'core', 'c4'));
        $this->assertSame('z5', config::get(-1, 'core', 'c5'));
        $this->assertSame(false, config::get(-1, 'core', 'c6'));
        tenancy::unforce_current_tenantid();

        $this->assertSame('123', config::get(-1, 'core', 'c1'));
        $this->assertSame('ijk', config::get(-1, 'core', 'c2'));
        $this->assertSame('efg', config::get(-1, 'core', 'c3'));
        $this->assertSame('x4', config::get(-1, 'core', 'c4'));
        $this->assertSame('567', config::get(-1, 'core', 'c5'));
        $this->assertSame('x6', config::get(-1, 'core', 'c6'));

        // Simulate bad test stuff is ignored.
        $this->setUser(null);
        $CFG->c4 = 'oops';
        $this->assertSame('456', get_config('core', 'c4'));
        $this->assertSame('456', config::get(null, 'core', 'c4'));
        $this->assertSame('x4', config::get($tenant1->id, 'core', 'c4'));
        $this->assertSame('456', config::get($tenant2->id, 'core', 'c4'));

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
        $this->assertSame('v1', config::get(null, 'tool_xnothing', 'p1'));
        $this->assertSame('w2', config::get(null, 'tool_xnothing', 'p2'));
        $this->assertSame('x3', config::get(null, 'tool_xnothing', 'p3'));
        $this->assertSame('v4', config::get(null, 'tool_xnothing', 'p4'));
        $this->assertSame('v5', config::get(null, 'tool_xnothing', 'p5'));
        $this->assertSame(false, config::get(null, 'tool_xnothing', 'p6'));
        $this->assertSame('v1', config::get($tenant1->id, 'tool_xnothing', 'p1'));
        $this->assertSame('w2', config::get($tenant1->id, 'tool_xnothing', 'p2'));
        $this->assertSame('x3', config::get($tenant1->id, 'tool_xnothing', 'p3'));
        $this->assertSame('o4', config::get($tenant1->id, 'tool_xnothing', 'p4'));
        $this->assertSame('v5', config::get($tenant1->id, 'tool_xnothing', 'p5'));
        $this->assertSame('o6', config::get($tenant1->id, 'tool_xnothing', 'p6'));
        $this->assertSame('v1', config::get(-1, 'tool_xnothing', 'p1'));
        $this->assertSame('w2', config::get(-1, 'tool_xnothing', 'p2'));
        $this->assertSame('x3', config::get(-1, 'tool_xnothing', 'p3'));
        $this->assertSame('v4', config::get(-1, 'tool_xnothing', 'p4'));
        $this->assertSame('v5', config::get(-1, 'tool_xnothing', 'p5'));
        $this->assertSame(false, config::get(-1, 'tool_xnothing', 'p6'));

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

        $result = config::get(null, 'tool_xnothing');
        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertSame($expected0, (array)$result);

        $result = config::get($tenant1->id, 'tool_xnothing');
        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertSame($expected1, (array)$result);

        tenancy::force_current_tenantid($tenant2->id);
        $this->assertSame('v1', config::get(-1, 'tool_xnothing', 'p1'));
        $this->assertSame('w2', config::get(-1, 'tool_xnothing', 'p2'));
        $this->assertSame('x3', config::get(-1, 'tool_xnothing', 'p3'));
        $this->assertSame('v4', config::get(-1, 'tool_xnothing', 'p4'));
        $this->assertSame('o5', config::get(-1, 'tool_xnothing', 'p5'));
        $this->assertSame(false, config::get(-1, 'tool_xnothing', 'p6'));
        $result = config::get(-1, 'tool_xnothing');
        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertSame($expected2, (array)$result);
        tenancy::unforce_current_tenantid();

        $this->setUser($user1);
        $this->assertSame('v1', config::get(null, 'tool_xnothing', 'p1'));
        $this->assertSame('w2', config::get(null, 'tool_xnothing', 'p2'));
        $this->assertSame('x3', config::get(null, 'tool_xnothing', 'p3'));
        $this->assertSame('v4', config::get(null, 'tool_xnothing', 'p4'));
        $this->assertSame('v5', config::get(null, 'tool_xnothing', 'p5'));
        $this->assertSame(false, config::get(null, 'tool_xnothing', 'p6'));
        $this->assertSame($expected0, (array)config::get(null, 'tool_xnothing'));
        $this->assertSame('v1', config::get($tenant1->id, 'tool_xnothing', 'p1'));
        $this->assertSame('w2', config::get($tenant1->id, 'tool_xnothing', 'p2'));
        $this->assertSame('x3', config::get($tenant1->id, 'tool_xnothing', 'p3'));
        $this->assertSame('o4', config::get($tenant1->id, 'tool_xnothing', 'p4'));
        $this->assertSame('v5', config::get($tenant1->id, 'tool_xnothing', 'p5'));
        $this->assertSame('o6', config::get($tenant1->id, 'tool_xnothing', 'p6'));
        $this->assertSame($expected1, (array)config::get($tenant1->id, 'tool_xnothing'));
        $this->assertSame('v1', config::get(-1, 'tool_xnothing', 'p1'));
        $this->assertSame('w2', config::get(-1, 'tool_xnothing', 'p2'));
        $this->assertSame('x3', config::get(-1, 'tool_xnothing', 'p3'));
        $this->assertSame('o4', config::get(-1, 'tool_xnothing', 'p4'));
        $this->assertSame('v5', config::get(-1, 'tool_xnothing', 'p5'));
        $this->assertSame('o6', config::get(-1, 'tool_xnothing', 'p6'));
        $this->assertSame($expected1, (array)config::get(-1, 'tool_xnothing'));
    }

    /**
     * @covers ::purge_plugin_overrides
     */
    public function test_purge_plugin_overrides(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();

        config::override($tenant1->id, 'tool_xyz_abc', 'x1', 'core');
        config::override($tenant2->id, 'tool_xyz_abc', 'y1', 'core');
        config::override($tenant1->id, 'opr', 'x2', 'tool_xyz');
        config::override($tenant1->id, 'tool_mulib_def', 'x3', 'core');
        config::override($tenant1->id, 'opr', 'x4', 'tool_mulib');

        $this->assertTrue(config::is_overridden($tenant1->id, 'core', 'tool_xyz_abc'));
        $this->assertTrue(config::is_overridden($tenant2->id, 'core', 'tool_xyz_abc'));
        $this->assertTrue(config::is_overridden($tenant1->id, 'tool_xyz', 'opr'));
        $this->assertTrue(config::is_overridden($tenant1->id, 'core', 'tool_mulib_def'));
        $this->assertTrue(config::is_overridden($tenant1->id, 'tool_mulib', 'opr'));

        config::purge_plugin_overrides('tool_xyz');
        $this->assertFalse(config::is_overridden($tenant1->id, 'core', 'tool_xyz_abc'));
        $this->assertFalse(config::is_overridden($tenant2->id, 'core', 'tool_xyz_abc'));
        $this->assertFalse(config::is_overridden($tenant1->id, 'tool_xyz', 'opr'));
        $this->assertTrue(config::is_overridden($tenant1->id, 'core', 'tool_mulib_def'));
        $this->assertTrue(config::is_overridden($tenant1->id, 'tool_mulib', 'opr'));

        $this->assertSame('x3', config::get($tenant1->id, 'core', 'tool_mulib_def'));
        $this->assertSame('x4', config::get($tenant1->id, 'tool_mulib', 'opr'));
    }
}
