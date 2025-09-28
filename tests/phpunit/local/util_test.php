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

namespace tool_muprog\phpunit\local;

use tool_muprog\local\util;

/**
 * Program helper test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\util
 */
final class util_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_fix_muprog_active(): void {
        global $DB;
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $this->assertFalse(get_config('tool_muprog', 'active'));

        util::fix_muprog_active();
        $this->assertSame('0', get_config('tool_muprog', 'active'));

        $program1 = $generator->create_program(['archived' => 1]);
        util::fix_muprog_active();
        $this->assertSame('0', get_config('tool_muprog', 'active'));

        $DB->set_field('tool_muprog_program', 'archived', 0, ['id' => $program1->id]);
        util::fix_muprog_active();
        $this->assertSame('1', get_config('tool_muprog', 'active'));

        $DB->set_field('tool_muprog_program', 'archived', 1, ['id' => $program1->id]);
        util::fix_muprog_active();
        $this->assertSame('0', get_config('tool_muprog', 'active'));
    }

    public function test_is_muprog_active(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $this->assertFalse(util::is_muprog_active());

        $program1 = $generator->create_program(['archived' => 1]);
        $this->assertFalse(util::is_muprog_active());

        $program2 = $generator->create_program(['archived' => 0]);
        $this->assertTrue(util::is_muprog_active());

        $program2 = \tool_muprog\local\program::archive($program2->id);
        $this->assertFalse(util::is_muprog_active());

        $program2 = \tool_muprog\local\program::restore($program2->id);
        $this->assertTrue(util::is_muprog_active());

        \tool_muprog\local\program::delete($program2->id);
        $this->assertFalse(util::is_muprog_active());

        \tool_muprog\local\program::delete($program1->id);
        $this->assertFalse(util::is_muprog_active());
    }

    public function test_is_mutenancy_available(): void {
        $this->assertSame(
            file_exists(__DIR__ . '/../../../../../tool/mutenancy/version.php'),
            util::is_mutenancy_available()
        );
    }

    public function test_is_mutenancy_active(): void {
        if (!util::is_mutenancy_available()) {
            $this->assertFalse(util::is_mutenancy_active());
            return;
        }

        \tool_mutenancy\local\tenancy::deactivate();
        $this->assertFalse(util::is_mutenancy_active());

        \tool_mutenancy\local\tenancy::activate();
        $this->assertTrue(util::is_mutenancy_active());
    }

    public function test_json_encode(): void {
        $this->assertSame('{"abc":"\\\\šk\"\'"}', util::json_encode(['abc' => '\šk"\'']));
    }

    public function test_normalise_delay(): void {
        $this->assertSame('P1M', util::normalise_delay('P1M'));
        $this->assertSame('P99D', util::normalise_delay('P99D'));
        $this->assertSame('PT9H', util::normalise_delay('PT9H'));
        $this->assertDebuggingNotCalled();
        $this->assertSame(null, util::normalise_delay(''));
        $this->assertSame(null, util::normalise_delay(null));
        $this->assertSame(null, util::normalise_delay('P0M'));
        $this->assertDebuggingNotCalled();

        $this->assertSame(null, util::normalise_delay('P9X'));
        $this->assertDebuggingCalled();
        $this->assertSame(null, util::normalise_delay('P1M1D'));
        $this->assertDebuggingCalled();
    }

    public function test_format_delay(): void {
        $this->assertSame('2 months', util::format_delay('P2M'));
        $this->assertSame('2 days', util::format_delay('P2D'));
        $this->assertSame('2 hours', util::format_delay('PT2H'));
        $this->assertSame('1 month, 2 days, 3 hours', util::format_delay('P1M2DT3H'));
        $this->assertSame('', util::format_delay(''));
        $this->assertSame('', util::format_delay(null));
    }

    public function test_format_duration(): void {
        $this->assertSame('2 days', util::format_duration(DAYSECS * 2));
        $this->assertSame('38 days, 4 hours, 35 seconds', util::format_duration(DAYSECS * 3 + HOURSECS * 4 + WEEKSECS * 5 + 35));
        $this->assertSame('Not set', util::format_duration(null));
        $this->assertSame('Not set', util::format_duration(0));
        $this->assertSame('Error', util::format_duration(DAYSECS * -1));
    }

    public function test_convert_to_count_sql(): void {
        $sql = 'SELECT *
                  FROM {user}
              ORDER BY id';
        $expected = 'SELECT COUNT(\'x\') FROM {user}';
        $this->assertSame($expected, util::convert_to_count_sql($sql));
    }

    public function test_store_uploaded_data(): void {
        global $CFG;
        require_once("$CFG->libdir/filelib.php");

        $admin = get_admin();
        $this->setUser($admin);
        $draftid = \file_get_unused_draft_itemid();
        $fs = get_file_storage();
        $context = \context_user::instance($admin->id);
        $record = [
            'contextid' => $context->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftid,
            'filepath' => '/',
            'filename' => 'somefile.csv',
        ];
        $fs->create_file_from_string($record, 'content is irrelevant');

        $csvdata = [
            ['username', 'firstname', 'lastname'],
            ['user1', 'First', 'User'],
            ['user2', 'Second', 'User'],
        ];
        util::store_uploaded_data($draftid, $csvdata);

        $files = $fs->get_area_files($context->id, 'tool_muprog', 'upload', $draftid, 'id ASC', false);
        $this->assertCount(1, $files);
        $file = reset($files);
        $this->assertSame('/', $file->get_filepath());
        $this->assertSame('data.json', $file->get_filename());
        $this->assertEquals($csvdata, json_decode($file->get_content()));
    }

    public function test_get_uploaded_data(): void {
        global $CFG;
        require_once("$CFG->libdir/filelib.php");

        $admin = get_admin();
        $this->setUser($admin);
        $draftid = \file_get_unused_draft_itemid();

        $this->assertNull(util::get_uploaded_data($draftid));
        $this->assertNull(util::get_uploaded_data(-1));
        $this->assertNull(util::get_uploaded_data(0));

        $fs = get_file_storage();
        $context = \context_user::instance($admin->id);
        $record = [
            'contextid' => $context->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftid,
            'filepath' => '/',
            'filename' => 'somefile.csv',
        ];
        $fs->create_file_from_string($record, 'content is irrelevant');

        $this->assertNull(util::get_uploaded_data($draftid));

        $csvdata = [
            ['username', 'firstname', 'lastname'],
            ['user1', 'First', 'User'],
            ['user2', 'Second', 'User'],
        ];
        util::store_uploaded_data($draftid, $csvdata);

        $this->assertEquals($csvdata, util::get_uploaded_data($draftid));
    }

    public function test_cleanup_uploaded_data(): void {
        global $CFG, $DB;
        require_once("$CFG->libdir/filelib.php");

        $admin = get_admin();
        $this->setUser($admin);
        $draftid = \file_get_unused_draft_itemid();
        $fs = get_file_storage();
        $context = \context_user::instance($admin->id);
        $csvdata = [
            ['username', 'firstname', 'lastname'],
            ['user1', 'First', 'User'],
            ['user2', 'Second', 'User'],
        ];
        util::store_uploaded_data($draftid, $csvdata);
        $files = $fs->get_area_files($context->id, 'tool_muprog', 'upload', $draftid, 'id ASC', false);
        $this->assertCount(1, $files);

        util::cleanup_uploaded_data();
        $files = $fs->get_area_files($context->id, 'tool_muprog', 'upload', $draftid, 'id ASC', false);
        $this->assertCount(1, $files);

        $old = time() - 60 * 60 * 24 * 1;
        $DB->set_field('files', 'timecreated', $old, ['component' => 'tool_muprog']);
        util::cleanup_uploaded_data();
        $files = $fs->get_area_files($context->id, 'tool_muprog', 'upload', $draftid, 'id ASC', false);
        $this->assertCount(1, $files);

        $old = time() - 60 * 60 * 24 * 2 - 10;
        $DB->set_field('files', 'timecreated', $old, ['component' => 'tool_muprog']);
        util::cleanup_uploaded_data();
        $files = $fs->get_area_files($context->id, 'tool_muprog', 'upload', $draftid, 'id ASC', false);
        $this->assertCount(0, $files);
    }
}
