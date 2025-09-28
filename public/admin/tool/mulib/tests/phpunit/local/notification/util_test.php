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

namespace tool_mulib\phpunit\local\notification;

use tool_mulib\local\notification\util;

/**
 * Notification util tests.
 *
 * @group       MuTMS
 * @package     tool_mulib
 * @copyright   2023 Open LMS
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \tool_mulib\local\notification\util
 */
final class util_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers ::get_manager_classname
     */
    public function test_get_manager_classname(): void {
        $result = util::get_manager_classname('tool_muprog');
        if (class_exists(\tool_muprog\local\notification_manager::class)) {
            $this->assertSame(\tool_muprog\local\notification_manager::class, $result);
        } else {
            $this->assertNull($result);
        }
    }

    /**
     * @covers ::notification_create
     */
    public function test_notification_create(): void {
        if (!get_config('tool_muprog', 'version')) {
            $this->markTestSkipped('Test requires tool_muprog plugin');
        }
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program = $generator->create_program();

        $data = [
            'component' => 'tool_muprog',
            'notificationtype' => 'allocation',
            'instanceid' => $program->id,
            'enabled' => '1',
        ];
        $notification = util::notification_create($data);
        $this->assertSame($data['component'], $notification->component);
        $this->assertSame($data['notificationtype'], $notification->notificationtype);
        $this->assertSame($data['instanceid'], $notification->instanceid);
        $this->assertSame($data['enabled'], $notification->enabled);
        $this->assertSame('0', $notification->custom);
        $this->assertSame(null, $notification->customjson);
        $this->assertSame(null, $notification->auxjson);
        $this->assertSame(null, $notification->auxint1);
        $this->assertSame(null, $notification->auxint2);

        $data = [
            'component' => 'tool_muprog',
            'notificationtype' => 'due',
            'instanceid' => $program->id,
            'enabled' => '0',
            'custom' => '1',
            'subject' => 'abc',
            'body' => 'def',
        ];
        $notification = util::notification_create($data);
        $this->assertSame($data['component'], $notification->component);
        $this->assertSame($data['notificationtype'], $notification->notificationtype);
        $this->assertSame($data['instanceid'], $notification->instanceid);
        $this->assertSame($data['enabled'], $notification->enabled);
        $this->assertSame('1', $notification->custom);
        $this->assertSame('{"subject":"abc","body":"def"}', $notification->customjson);
        $this->assertSame(null, $notification->auxjson);
        $this->assertSame(null, $notification->auxint1);
        $this->assertSame(null, $notification->auxint2);

        $data = [
            'component' => 'tool_muprog',
            'notificationtype' => 'endfailed',
            'instanceid' => $program->id,
            'enabled' => '1',
            'custom' => '1',
            'subject' => 'abc',
            'body' => ['text' => 'def', 'format' => FORMAT_MARKDOWN],
        ];
        $notification = util::notification_create($data);
        $this->assertSame($data['component'], $notification->component);
        $this->assertSame($data['notificationtype'], $notification->notificationtype);
        $this->assertSame($data['instanceid'], $notification->instanceid);
        $this->assertSame($data['enabled'], $notification->enabled);
        $this->assertSame('1', $notification->custom);
        $this->assertSame('{"subject":"abc","body":"def"}', $notification->customjson);
        $this->assertSame(null, $notification->auxjson);
        $this->assertSame(null, $notification->auxint1);
        $this->assertSame(null, $notification->auxint2);
    }

    /**
     * @covers ::notification_update
     */
    public function test_notification_update(): void {
        if (!get_config('tool_muprog', 'version')) {
            $this->markTestSkipped('Test requires tool_muprog plugin');
        }
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program = $generator->create_program();

        $data = [
            'component' => 'tool_muprog',
            'notificationtype' => 'allocation',
            'instanceid' => $program->id,
            'enabled' => '1',
        ];
        $notification = util::notification_create($data);

        $data2 = [
            'id' => $notification->id,
            'enabled' => '0',
            'custom' => '1',
            'subject' => 'abc',
            'body' => 'def',
        ];
        $notification = util::notification_update($data2);
        $this->assertSame($data['component'], $notification->component);
        $this->assertSame($data['notificationtype'], $notification->notificationtype);
        $this->assertSame($data['instanceid'], $notification->instanceid);
        $this->assertSame($data2['enabled'], $notification->enabled);
        $this->assertSame('1', $notification->custom);
        $this->assertSame('{"subject":"abc","body":"def"}', $notification->customjson);
        $this->assertSame(null, $notification->auxjson);
        $this->assertSame(null, $notification->auxint1);
        $this->assertSame(null, $notification->auxint2);

        $data3 = [
            'id' => $notification->id,
            'custom' => '1',
            'body' => ['text' => 'ijk', 'format' => FORMAT_MARKDOWN],
        ];
        $notification = util::notification_update($data3);
        $this->assertSame($data['component'], $notification->component);
        $this->assertSame($data['notificationtype'], $notification->notificationtype);
        $this->assertSame($data['instanceid'], $notification->instanceid);
        $this->assertSame($data2['enabled'], $notification->enabled);
        $this->assertSame('1', $notification->custom);
        $this->assertSame('{"subject":"","body":"ijk"}', $notification->customjson);
        $this->assertSame(null, $notification->auxjson);
        $this->assertSame(null, $notification->auxint1);
        $this->assertSame(null, $notification->auxint2);

        $data4 = [
            'id' => $notification->id,
            'custom' => '0',
        ];
        $notification = util::notification_update($data4);
        $this->assertSame($data['component'], $notification->component);
        $this->assertSame($data['notificationtype'], $notification->notificationtype);
        $this->assertSame($data['instanceid'], $notification->instanceid);
        $this->assertSame($data2['enabled'], $notification->enabled);
        $this->assertSame('0', $notification->custom);
        $this->assertSame(null, $notification->customjson);
        $this->assertSame(null, $notification->auxjson);
        $this->assertSame(null, $notification->auxint1);
        $this->assertSame(null, $notification->auxint2);

        $data5 = [
            'id' => $notification->id,
            'custom' => '1',
        ];
        $notification = util::notification_update($data5);
        $this->assertSame($data['component'], $notification->component);
        $this->assertSame($data['notificationtype'], $notification->notificationtype);
        $this->assertSame($data['instanceid'], $notification->instanceid);
        $this->assertSame($data2['enabled'], $notification->enabled);
        $this->assertSame('1', $notification->custom);
        $this->assertSame('{"subject":"","body":""}', $notification->customjson);
        $this->assertSame(null, $notification->auxjson);
        $this->assertSame(null, $notification->auxint1);
        $this->assertSame(null, $notification->auxint2);
    }

    /**
     * @covers ::notification_delete
     */
    public function test_notification_delete(): void {
        global $DB;

        if (!get_config('tool_muprog', 'version')) {
            $this->markTestSkipped('Test requires tool_muprog plugin');
        }
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program = $generator->create_program();

        $data = [
            'component' => 'tool_muprog',
            'notificationtype' => 'allocation',
            'instanceid' => $program->id,
            'enabled' => '1',
        ];
        $notification = util::notification_create($data);

        $admin = get_admin();
        $data = [
            'notificationid' => $notification->id,
            'userid' => $admin->id,
            'timenotified' => time(),
            'messageid' => null,
        ];
        $DB->insert_record('tool_mulib_notification_user', $data);

        util::notification_delete($notification->id);
        $this->assertFalse($DB->record_exists('tool_mulib_notification_user', ['notificationid' => $notification->id]));
        $this->assertFalse($DB->record_exists('tool_mulib_notification', ['id' => $notification->id]));
    }

    /**
     * @covers ::replace_placeholders
     */
    public function test_replace_placeholders(): void {
        $this->assertSame('abc', \tool_mulib\local\notification\util::replace_placeholders('abc', ['opr' => 'OPR']));

        $def = function () {
            return 'DEF';
        };
        $return = \tool_mulib\local\notification\util::replace_placeholders('abc {$a->opr} ({$a-&gt;def}) {$a}', ['opr' => 'OPR', 'abc' => 'ABC', 'def' => $def]);
        $this->assertSame('abc OPR (DEF) {$a}', $return);
    }

    /**
     * @covers ::filter_multilang
     */
    public function test_filter_multilang(): void {
        $text = '<span lang="en" class="multilang">your_content_in English</span>
                <span lang="de" class="multilang">your_content_in_German_here</span>';
        $onelang = 'your_content_in English';

        $this->assertSame($text, \tool_mulib\local\notification\util::filter_multilang($text, false));

        // There does not seem to be a better way to purge the ad-hoc cache from filter_get_globally_enabled().
        $cache = \cache::make_from_params(\cache_store::MODE_REQUEST, 'core_filter', 'global_filters');

        \filter_set_global_state('multilang', TEXTFILTER_ON);
        $cache->purge();
        $this->assertSame($onelang, \tool_mulib\local\notification\util::filter_multilang($text, false));

        \filter_set_global_state('multilang', TEXTFILTER_OFF);
        $cache->purge();
        $this->assertSame($onelang, \tool_mulib\local\notification\util::filter_multilang($text, false));

        \filter_set_global_state('multilang', TEXTFILTER_DISABLED);
        $cache->purge();
        $this->assertSame($text, \tool_mulib\local\notification\util::filter_multilang($text, false));
    }

    /**
     * @covers ::filter_multilang
     */
    public function test_filter_multilang2(): void {
        if (!\get_config('filter_multilang2', 'version')) {
            $this->markTestSkipped('Test requires filter_multilang2 plugin');
        }

        $text = '{mlang en}your_content_in English{mlang}
{mlang other}your_content_in_German_here{mlang}';
        $onelang = 'your_content_in English
';

        $this->assertSame($text, \tool_mulib\local\notification\util::filter_multilang($text, false));

        // There does not seem to be a better way to purge the ad-hoc cache from filter_get_globally_enabled().
        $cache = \cache::make_from_params(\cache_store::MODE_REQUEST, 'core_filter', 'global_filters');

        \filter_set_global_state('multilang2', TEXTFILTER_ON);
        $cache->purge();
        $this->assertSame($onelang, \tool_mulib\local\notification\util::filter_multilang($text, false));

        \filter_set_global_state('multilang2', TEXTFILTER_OFF);
        $cache->purge();
        $this->assertSame($onelang, \tool_mulib\local\notification\util::filter_multilang($text, false));

        \filter_set_global_state('multilang2', TEXTFILTER_DISABLED);
        $cache->purge();
        $this->assertSame($text, \tool_mulib\local\notification\util::filter_multilang($text, false));
    }

    /**
     * @covers ::notification_import
     */
    public function test_notification_import(): void {
        // Invalid data tests only here, real data tests in:
        // \tool_muprog\local\notification_manager_test::test_notification_util_notification_import().

        try {
            \tool_mulib\local\notification\util::notification_import((object)['component' => 'fdjhkjsdhkfds', 'instanceid' => 2, 'frominstance' => 3], []);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
        }
    }
}
