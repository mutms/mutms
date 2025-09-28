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

/**
 * Multi-tenancy upstream patch test.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \core\context_helper
 */
final class core_context_helper_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers ::get_preload_record_columns
     */
    public function test_get_preload_record_columns(): void {
        if (tenancy::is_active()) {
            tenancy::deactivate();
        }

        $columns = \core\context_helper::get_preload_record_columns('x');
        $expected = [
            'x.id' => 'ctxid',
            'x.path' => 'ctxpath',
            'x.depth' => 'ctxdepth',
            'x.contextlevel' => 'ctxlevel',
            'x.instanceid' => 'ctxinstance',
            'x.locked' => 'ctxlocked',
        ];
        $this->assertSame($expected, $columns);

        tenancy::activate();

        $columns = \core\context_helper::get_preload_record_columns('x');
        $expected = [
            'x.id' => 'ctxid',
            'x.path' => 'ctxpath',
            'x.depth' => 'ctxdepth',
            'x.contextlevel' => 'ctxlevel',
            'x.instanceid' => 'ctxinstance',
            'x.locked' => 'ctxlocked',
            'x.tenantid' => 'ctxtenantid',
        ];
        $this->assertSame($expected, $columns);
    }

    /**
     * @covers ::get_preload_record_columns_sql
     */
    public function test_get_preload_record_columns_sql(): void {
        if (tenancy::is_active()) {
            tenancy::deactivate();
        }

        $sql = \core\context_helper::get_preload_record_columns_sql('x');
        $expected = 'x.id AS ctxid, x.path AS ctxpath, x.depth AS ctxdepth, x.contextlevel AS ctxlevel, x.instanceid AS ctxinstance, x.locked AS ctxlocked';
        $this->assertSame($expected, $sql);

        tenancy::activate();

        $sql = \core\context_helper::get_preload_record_columns_sql('x');
        $expected = 'x.id AS ctxid, x.path AS ctxpath, x.depth AS ctxdepth, x.contextlevel AS ctxlevel,'
            . ' x.instanceid AS ctxinstance, x.locked AS ctxlocked, x.tenantid AS ctxtenantid';
        $this->assertSame($expected, $sql);
    }
}
