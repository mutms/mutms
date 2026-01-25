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

namespace tool_muhome\phpunit\callback;

use tool_muhome\callback\core;
use tool_muhome\local\page;
use core\exception\moodle_exception;

/**
 * Core callbacks class tests.
 *
 * @group       MuTMS
 * @package     tool_muhome
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muhome\callback\core
 */
final class core_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_hook_after_config(): void {
        global $CFG;

        $hook = new \core\hook\after_config();
        core::hook_after_config($hook);

        /** @var \tool_muhome_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muhome');

        $user1 = $this->getDataGenerator()->create_user();
        $page1 = $generator->create_page([
            'status' => page::STATUS_ACTIVE,
            'guestvisible' => 1,
            'uservisible' => 1,
        ]);

        set_config('replacehome', 1, 'tool_muhome');

        $this->assertSame((string)HOMEPAGE_MY, $CFG->defaulthomepage);
        $this->assertObjectNotHasProperty('tool_muhome_real_defaulthomepage', $CFG);

        $this->setUser(null);

        core::hook_after_config($hook);
        $this->assertSame('/admin/tool/muhome/', $CFG->defaulthomepage);
        $this->assertSame((string)HOMEPAGE_MY, $CFG->tool_muhome_real_defaulthomepage);

        unset($CFG->tool_muhome_real_defaulthomepage);
        $CFG->defaulthomepage = (string)HOMEPAGE_MY;
        $CFG->allowguestmymoodle = 1;
        core::hook_after_config($hook);
        $this->assertSame('/admin/tool/muhome/', $CFG->defaulthomepage);
        $this->assertSame((string)HOMEPAGE_MY, $CFG->tool_muhome_real_defaulthomepage);

        $this->setGuestUser();

        unset($CFG->tool_muhome_real_defaulthomepage);
        $CFG->defaulthomepage = (string)HOMEPAGE_MY;
        $CFG->allowguestmymoodle = 0;
        core::hook_after_config($hook);
        $this->assertSame('/admin/tool/muhome/', $CFG->defaulthomepage);
        $this->assertSame((string)HOMEPAGE_MY, $CFG->tool_muhome_real_defaulthomepage);

        unset($CFG->tool_muhome_real_defaulthomepage);
        $CFG->defaulthomepage = (string)HOMEPAGE_MY;
        $CFG->allowguestmymoodle = 1;
        core::hook_after_config($hook);
        $this->assertSame((string)HOMEPAGE_MY, $CFG->defaulthomepage);
        $this->assertSame((string)HOMEPAGE_MY, $CFG->tool_muhome_real_defaulthomepage);

        $CFG->allowguestmymoodle = 0;

        $this->setUser($user1);

        unset($CFG->tool_muhome_real_defaulthomepage);
        $CFG->defaulthomepage = (string)HOMEPAGE_MY;
        core::hook_after_config($hook);
        $this->assertSame((string)HOMEPAGE_MY, $CFG->defaulthomepage);
        $this->assertSame((string)HOMEPAGE_MY, $CFG->tool_muhome_real_defaulthomepage);

        unset($CFG->tool_muhome_real_defaulthomepage);
        $CFG->defaulthomepage = (string)HOMEPAGE_MYCOURSES;
        core::hook_after_config($hook);
        $this->assertSame((string)HOMEPAGE_MYCOURSES, $CFG->defaulthomepage);
        $this->assertSame((string)HOMEPAGE_MYCOURSES, $CFG->tool_muhome_real_defaulthomepage);

        unset($CFG->tool_muhome_real_defaulthomepage);
        $CFG->defaulthomepage = (string)HOMEPAGE_SITE;
        core::hook_after_config($hook);
        $this->assertSame('/admin/tool/muhome/', $CFG->defaulthomepage);
        $this->assertSame((string)HOMEPAGE_SITE, $CFG->tool_muhome_real_defaulthomepage);

        unset($CFG->tool_muhome_real_defaulthomepage);
        $CFG->defaulthomepage = (string)HOMEPAGE_USER;
        set_user_preference('user_home_page_preference', HOMEPAGE_MY);
        core::hook_after_config($hook);
        $this->assertSame((string)HOMEPAGE_USER, $CFG->defaulthomepage);
        $this->assertSame((string)HOMEPAGE_USER, $CFG->tool_muhome_real_defaulthomepage);

        unset($CFG->tool_muhome_real_defaulthomepage);
        $CFG->defaulthomepage = (string)HOMEPAGE_USER;
        set_user_preference('user_home_page_preference', HOMEPAGE_MYCOURSES);
        core::hook_after_config($hook);
        $this->assertSame((string)HOMEPAGE_USER, $CFG->defaulthomepage);
        $this->assertSame((string)HOMEPAGE_USER, $CFG->tool_muhome_real_defaulthomepage);

        unset($CFG->tool_muhome_real_defaulthomepage);
        $CFG->defaulthomepage = (string)HOMEPAGE_USER;
        set_user_preference('user_home_page_preference', HOMEPAGE_SITE);
        core::hook_after_config($hook);
        $this->assertSame('/admin/tool/muhome/', $CFG->defaulthomepage);
        $this->assertSame((string)HOMEPAGE_USER, $CFG->tool_muhome_real_defaulthomepage);
    }

    public function test_hook_primary_extend(): void {
        global $PAGE;

        /** @var \tool_muhome_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muhome');

        $user1 = $this->getDataGenerator()->create_user();
        $page1 = $generator->create_page([
            'status' => page::STATUS_ACTIVE,
            'guestvisible' => 1,
            'uservisible' => 1,
        ]);
        $page2 = $generator->create_page([
            'status' => page::STATUS_ACTIVE,
            'guestvisible' => 1,
            'uservisible' => 1,
        ]);
        $page3 = $generator->create_page([
            'status' => page::STATUS_ACTIVE,
            'guestvisible' => 0,
            'uservisible' => 1,
        ]);

        $PAGE = new \moodle_page();
        $PAGE->set_url('/');
        $primarynav = new \core\navigation\views\primary($PAGE);
        $primarynav->initialise();

        $home = $primarynav->find('home', null);
        $this->assertSame('Home', $home->text);
        $this->assertSame('/', $home->action->out_as_local_url(false));
        $this->assertFalse($primarynav->find('tool_muhome_home', null));
        $this->assertFalse($primarynav->find('tool_muhome_pages', null));
        $this->assertFalse($primarynav->find('tool_muhome_page_' . $page2->id, null));

        set_config('replacehome', 1, 'tool_muhome');

        $PAGE = new \moodle_page();
        $PAGE->set_url('/');
        $primarynav = new \core\navigation\views\primary($PAGE);
        $primarynav->initialise();

        $home = $primarynav->find('tool_muhome_home', null);
        $this->assertSame('Home', $home->text);
        $this->assertSame('/admin/tool/muhome/', $home->action->out_as_local_url(false));
        $this->assertFalse($primarynav->find('home', null));
        $this->assertFalse($primarynav->find('tool_muhome_pages', null));
        $this->assertFalse($primarynav->find('tool_muhome_page_' . $page2->id, null));

        set_config('addmenu', 'Fancy stuff', 'tool_muhome');

        $PAGE = new \moodle_page();
        $PAGE->set_url('/');
        $primarynav = new \core\navigation\views\primary($PAGE);
        $primarynav->initialise();

        $home = $primarynav->find('tool_muhome_home', null);
        $this->assertSame('Home', $home->text);
        $this->assertSame('/admin/tool/muhome/', $home->action->out_as_local_url(false));
        $pages = $primarynav->find('tool_muhome_pages', null);
        $this->assertSame('Fancy stuff', $pages->text);
        $p2 = $primarynav->find('tool_muhome_page_' . $page2->id, null);
        $this->assertSame($page2->name, $p2->text);
        $this->assertSame('/admin/tool/muhome/?pageid=' . $page2->id, $p2->action->out_as_local_url(false));
        $this->assertFalse($primarynav->find('home', null));
        $this->assertFalse($primarynav->find('tool_muhome_page_' . $page1->id, null));
        $this->assertFalse($primarynav->find('tool_muhome_page_' . $page3->id, null));

        $this->setUser($user1);

        $PAGE = new \moodle_page();
        $PAGE->set_url('/');
        $primarynav = new \core\navigation\views\primary($PAGE);
        $primarynav->initialise();

        $home = $primarynav->find('tool_muhome_home', null);
        $this->assertSame('Home', $home->text);
        $this->assertSame('/admin/tool/muhome/', $home->action->out_as_local_url(false));
        $pages = $primarynav->find('tool_muhome_pages', null);
        $this->assertSame('Fancy stuff', $pages->text);
        $p2 = $primarynav->find('tool_muhome_page_' . $page2->id, null);
        $this->assertSame($page2->name, $p2->text);
        $this->assertSame('/admin/tool/muhome/?pageid=' . $page2->id, $p2->action->out_as_local_url(false));
        $p3 = $primarynav->find('tool_muhome_page_' . $page3->id, null);
        $this->assertSame($page3->name, $p3->text);
        $this->assertSame('/admin/tool/muhome/?pageid=' . $page3->id, $p3->action->out_as_local_url(false));
        $this->assertFalse($primarynav->find('home', null));
        $this->assertFalse($primarynav->find('tool_muhome_page_' . $page1->id, null));

        set_config('replacehome', 0, 'tool_muhome');

        $PAGE = new \moodle_page();
        $PAGE->set_url('/');
        $primarynav = new \core\navigation\views\primary($PAGE);
        $primarynav->initialise();

        $home = $primarynav->find('home', null);
        $this->assertSame('Home', $home->text);
        $this->assertSame('/?redirect=0', $home->action->out_as_local_url(false));
        $pages = $primarynav->find('tool_muhome_pages', null);
        $this->assertSame('Fancy stuff', $pages->text);
        $p1 = $primarynav->find('tool_muhome_page_' . $page1->id, null);
        $this->assertSame($page1->name, $p1->text);
        $this->assertSame('/admin/tool/muhome/?pageid=' . $page1->id, $p1->action->out_as_local_url(false));
        $p2 = $primarynav->find('tool_muhome_page_' . $page2->id, null);
        $this->assertSame($page2->name, $p2->text);
        $this->assertSame('/admin/tool/muhome/?pageid=' . $page2->id, $p2->action->out_as_local_url(false));
        $p3 = $primarynav->find('tool_muhome_page_' . $page3->id, null);
        $this->assertSame($page3->name, $p3->text);
        $this->assertSame('/admin/tool/muhome/?pageid=' . $page3->id, $p3->action->out_as_local_url(false));
        $this->assertFalse($primarynav->find('tool_muhome_home', null));
    }

    public function test_event_user_logged_in(): void {
        global $CFG;

        $hook = new \core\hook\after_config();
        core::hook_after_config($hook);

        /** @var \tool_muhome_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muhome');

        $user1 = $this->getDataGenerator()->create_user();
        $page1 = $generator->create_page([
            'status' => page::STATUS_ACTIVE,
            'guestvisible' => 1,
            'uservisible' => 1,
        ]);

        set_config('replacehome', 1, 'tool_muhome');
        $this->assertSame((string)HOMEPAGE_MY, $CFG->defaulthomepage);
        $this->assertObjectNotHasProperty('tool_muhome_real_defaulthomepage', $CFG);

        core::hook_after_config($hook);
        $this->assertSame((string)HOMEPAGE_MY, $CFG->tool_muhome_real_defaulthomepage);

        $this->setUser($user1);
        $event = \core\event\user_loggedin::create(
            [
                'userid' => $user1->id,
                'objectid' => $user1->id,
                'other' => [
                    'username' => $user1->username,
                    'extrauserinfo' => [],
                ],
            ]
        );
        $event->trigger();
        $this->assertObjectNotHasProperty('tool_muhome_real_defaulthomepage', $CFG);
    }
}
