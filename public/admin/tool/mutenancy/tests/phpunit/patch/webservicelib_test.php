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
 * Multi-tenancy tests for webservice/lib.php modifications.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class webservicelib_test extends \advanced_testcase {
    public function setUp(): void {
        global $CFG;
        parent::setUp();

        require_once($CFG->dirroot . '/webservice/lib.php');

        $this->resetAfterTest();

        // We always need enabled WS for this testcase.
        set_config('enablewebservices', '1');
    }

    /**
     * @covers \webservice::authenticate_user
     */
    public function test_init_service_class(): void {
        global $DB, $USER;
        tenancy::activate();

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $webservice = new \stdClass();
        $webservice->name = 'Test web service';
        $webservice->enabled = true;
        $webservice->restrictedusers = false;
        $webservice->component = 'moodle';
        $webservice->timecreated = time();
        $webservice->downloadfiles = true;
        $webservice->uploadfiles = true;
        $externalserviceid = $DB->insert_record('external_services', $webservice);

        $wsmethod = new \stdClass();
        $wsmethod->externalserviceid = $externalserviceid;
        $wsmethod->functionname = 'core_course_get_contents';
        $DB->insert_record('external_services_functions', $wsmethod);

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant(['archived' => 1]);

        $createtoken = function (\stdClass $user, $externalserviceid): string {
            global $DB;

            $externaltoken = new \stdClass();
            $externaltoken->token = 'testtoken' . $user->id;
            $externaltoken->tokentype = 0;
            $externaltoken->userid = $user->id;
            $externaltoken->externalserviceid = $externalserviceid;
            $externaltoken->contextid = 1;
            $externaltoken->creatorid = $user->id;
            $externaltoken->timecreated = time();
            $externaltoken->name = \core_external\util::generate_token_name();
            $DB->insert_record('external_tokens', $externaltoken);

            return $externaltoken->token;
        };

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $token0 = $createtoken($user0, $externalserviceid);
        $token1 = $createtoken($user1, $externalserviceid);
        $token2 = $createtoken($user2, $externalserviceid);

        $this->setUser();
        $webservice = new \webservice();
        $result = $webservice->authenticate_user($token0);
        $this->assertSame($user0->id, $result['user']->id);
        $this->assertSame((string)$externalserviceid, $result['service']->id);
        $this->assertSame($user0->id, $USER->id);

        $this->setUser();
        $webservice = new \webservice();
        $result = $webservice->authenticate_user($token1);
        $this->assertSame($user1->id, $result['user']->id);
        $this->assertSame((string)$externalserviceid, $result['service']->id);
        $this->assertSame($user1->id, $USER->id);

        $this->setUser();
        $webservice = new \webservice();
        $result = $webservice->authenticate_user($token1);
        $this->assertSame($user1->id, $result['user']->id);
        $this->assertSame((string)$externalserviceid, $result['service']->id);
        $this->assertSame($user1->id, $USER->id);

        $this->setUser();
        $webservice = new \webservice();
        try {
            $webservice->authenticate_user($token2);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertStringContainsString('error/wsaccessusersuspended', $ex->getMessage());
        }
        $this->assertSame($user2->id, $USER->id); // This is just wrong!
    }
}
