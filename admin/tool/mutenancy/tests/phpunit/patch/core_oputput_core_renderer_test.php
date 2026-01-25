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
 * Multi-tenancy upstream patch test.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \core\output\core_renderer
 */
final class core_oputput_core_renderer_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers ::favicon
     */
    public function test_favicon(): void {
        global $PAGE;

        if (tenancy::is_active()) {
            tenancy::deactivate();
        }

        $syscontext = \context_system::instance();
        $themerev = theme_get_revision();

        /** @var \core\output\core_renderer $renderer */
        $renderer = $PAGE->get_renderer('core');

        $result = $renderer->favicon();
        $this->assertInstanceOf(\core\url::class, $result);
        $this->assertSame(
            "https://www.example.com/moodle/theme/image.php/boost/theme/$themerev/favicon",
            $result->out(false)
        );

        set_config('favicon', '/default.jpg', 'core_admin');

        $result = $renderer->favicon();
        $this->assertInstanceOf(\core\url::class, $result);
        $this->assertSame(
            "https://www.example.com/moodle/pluginfile.php/$syscontext->id/core_admin/favicon/64x64/$themerev/default.jpg",
            $result->out(false)
        );

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();

        $tenantcontext2 = \context_tenant::instance($tenant2->id);

        config::override($tenant2->id, 'favicon', '/myfavicon.gif', 'core_admin');

        $result = $renderer->favicon();
        $this->assertInstanceOf(\core\url::class, $result);
        $this->assertSame(
            "https://www.example.com/moodle/pluginfile.php/$syscontext->id/core_admin/favicon/64x64/$themerev/default.jpg",
            $result->out(false)
        );

        tenancy::switch($tenant2->id);

        $result = $renderer->favicon();
        $this->assertInstanceOf(\core\url::class, $result);
        $this->assertSame(
            "https://www.example.com/moodle/pluginfile.php/$tenantcontext2->id/core_admin/favicon/64x64/$themerev/myfavicon.gif",
            $result->out(false)
        );

        config::override($tenant2->id, 'favicon', '', 'core_admin');
        $result = $renderer->favicon();
        $this->assertInstanceOf(\core\url::class, $result);
        $this->assertSame(
            "https://www.example.com/moodle/theme/image.php/boost/theme/$themerev/favicon",
            $result->out(false)
        );
    }
}
