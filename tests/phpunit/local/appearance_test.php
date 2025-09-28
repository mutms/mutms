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

use tool_mutenancy\local\appearance;
use tool_mutenancy\local\config;

/**
 * Multi-tenancy appearance tests.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \tool_mutenancy\local\appearance
 */
final class appearance_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers ::has_custom_css
     */
    public function test_has_custom_css(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();

        config::override($tenant1->id, 'anything', 1, 'theme_boost');
        config::override($tenant2->id, 'anything', 1, 'theme_classic');

        $this->assertTrue(appearance::has_custom_css($tenant1->id, 'boost'));
        $this->assertFalse(appearance::has_custom_css($tenant2->id, 'boost'));

        $this->assertFalse(appearance::has_custom_css($tenant1->id, 'classic'));
        $this->assertTrue(appearance::has_custom_css($tenant2->id, 'classic'));

        $this->assertFalse(appearance::has_custom_css($tenant1->id, 'xyz'));
        $this->assertFalse(appearance::has_custom_css($tenant2->id, 'xyz'));
    }

    /**
     * @covers ::get_logo_url
     */
    public function test_get_logo_url(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();

        $syscontext = \context_system::instance();
        $tenantcontext2 = \context_tenant::instance($tenant2->id);

        $themerev = theme_get_revision();

        config::override($tenant2->id, 'logo', '/mylogo.gif', 'core_admin');

        $result = appearance::get_logo_url(100, 50, null);
        $this->assertFalse($result);

        $result = appearance::get_logo_url(100, 50, $tenant1->id);
        $this->assertFalse($result);

        $result = appearance::get_logo_url(100, 50, $tenant2->id);
        $this->assertInstanceOf(\moodle_url::class, $result);
        $this->assertSame(
            "https://www.example.com/moodle/pluginfile.php/$tenantcontext2->id/core_admin/logo/100x50/$themerev/mylogo.gif",
            $result->out(false)
        );

        set_config('logo', '/default.jpg', 'core_admin');

        $result = appearance::get_logo_url(100, 50, null);
        $this->assertInstanceOf(\moodle_url::class, $result);
        $this->assertSame(
            "https://www.example.com/moodle/pluginfile.php/$syscontext->id/core_admin/logo/100x50/$themerev/default.jpg",
            $result->out(false)
        );

        $result = appearance::get_logo_url(100, 50, $tenant1->id);
        $this->assertInstanceOf(\moodle_url::class, $result);
        $this->assertSame(
            "https://www.example.com/moodle/pluginfile.php/$syscontext->id/core_admin/logo/100x50/$themerev/default.jpg",
            $result->out(false)
        );

        $result = appearance::get_logo_url(100, 50, $tenant2->id);
        $this->assertInstanceOf(\moodle_url::class, $result);
        $this->assertSame(
            "https://www.example.com/moodle/pluginfile.php/$tenantcontext2->id/core_admin/logo/100x50/$themerev/mylogo.gif",
            $result->out(false)
        );

        config::override($tenant2->id, 'logo', '', 'core_admin');
        $result = appearance::get_logo_url(100, 50, $tenant2->id);
        $this->assertFalse($result);
    }

    /**
     * @covers ::get_compact_logo_url
     */
    public function test_get_compact_logo_url(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();

        $syscontext = \context_system::instance();
        $tenantcontext2 = \context_tenant::instance($tenant2->id);

        $themerev = theme_get_revision();

        config::override($tenant2->id, 'logocompact', '/mylogocompact.gif', 'core_admin');

        $result = appearance::get_compact_logo_url(100, 50, null);
        $this->assertFalse($result);

        $result = appearance::get_compact_logo_url(100, 50, $tenant1->id);
        $this->assertFalse($result);

        $result = appearance::get_compact_logo_url(100, 50, $tenant2->id);
        $this->assertInstanceOf(\moodle_url::class, $result);
        $this->assertSame(
            "https://www.example.com/moodle/pluginfile.php/$tenantcontext2->id/core_admin/logocompact/100x50/$themerev/mylogocompact.gif",
            $result->out(false)
        );

        set_config('logocompact', '/default.jpg', 'core_admin');

        $result = appearance::get_compact_logo_url(100, 50, null);
        $this->assertInstanceOf(\moodle_url::class, $result);
        $this->assertSame(
            "https://www.example.com/moodle/pluginfile.php/$syscontext->id/core_admin/logocompact/100x50/$themerev/default.jpg",
            $result->out(false)
        );

        $result = appearance::get_compact_logo_url(100, 50, $tenant1->id);
        $this->assertInstanceOf(\moodle_url::class, $result);
        $this->assertSame(
            "https://www.example.com/moodle/pluginfile.php/$syscontext->id/core_admin/logocompact/100x50/$themerev/default.jpg",
            $result->out(false)
        );

        $result = appearance::get_compact_logo_url(100, 50, $tenant2->id);
        $this->assertInstanceOf(\moodle_url::class, $result);
        $this->assertSame(
            "https://www.example.com/moodle/pluginfile.php/$tenantcontext2->id/core_admin/logocompact/100x50/$themerev/mylogocompact.gif",
            $result->out(false)
        );

        config::override($tenant2->id, 'logocompact', '', 'core_admin');
        $result = appearance::get_compact_logo_url(100, 50, $tenant2->id);
        $this->assertFalse($result);
    }

    /**
     * @covers ::get_favicon_url
     */
    public function test_get_favicon_url(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();

        $syscontext = \context_system::instance();
        $tenantcontext2 = \context_tenant::instance($tenant2->id);

        $themerev = theme_get_revision();

        config::override($tenant2->id, 'favicon', '/myfavicon.gif', 'core_admin');

        $result = appearance::get_favicon_url(null);
        $this->assertFalse($result);

        $result = appearance::get_favicon_url($tenant1->id);
        $this->assertFalse($result);

        $result = appearance::get_favicon_url($tenant2->id);
        $this->assertInstanceOf(\moodle_url::class, $result);
        $this->assertSame(
            "https://www.example.com/moodle/pluginfile.php/$tenantcontext2->id/core_admin/favicon/64x64/$themerev/myfavicon.gif",
            $result->out(false)
        );

        set_config('favicon', '/default.jpg', 'core_admin');

        $result = appearance::get_favicon_url(null);
        $this->assertInstanceOf(\moodle_url::class, $result);
        $this->assertSame(
            "https://www.example.com/moodle/pluginfile.php/$syscontext->id/core_admin/favicon/64x64/$themerev/default.jpg",
            $result->out(false)
        );

        $result = appearance::get_favicon_url($tenant1->id);
        $this->assertInstanceOf(\moodle_url::class, $result);
        $this->assertSame(
            "https://www.example.com/moodle/pluginfile.php/$syscontext->id/core_admin/favicon/64x64/$themerev/default.jpg",
            $result->out(false)
        );

        $result = appearance::get_favicon_url($tenant2->id);
        $this->assertInstanceOf(\moodle_url::class, $result);
        $this->assertSame(
            "https://www.example.com/moodle/pluginfile.php/$tenantcontext2->id/core_admin/favicon/64x64/$themerev/myfavicon.gif",
            $result->out(false)
        );

        config::override($tenant2->id, 'favicon', '', 'core_admin');
        $result = appearance::get_favicon_url($tenant2->id);
        $this->assertFalse($result);
    }

    /**
     * @covers ::get_boost_setting_image_url
     */
    public function test_get_boost_setting_image_url(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();

        $syscontext = \context_system::instance();
        $tenantcontext2 = \context_tenant::instance($tenant2->id);

        $themerev = theme_get_revision();

        config::override($tenant2->id, 'logo', '/mylogo.gif', 'theme_boost');

        $result = appearance::get_boost_setting_image_url('logo', null);
        $this->assertFalse($result);

        $result = appearance::get_boost_setting_image_url('logo', $tenant1->id);
        $this->assertFalse($result);

        $result = appearance::get_boost_setting_image_url('logo', $tenant2->id);
        $this->assertInstanceOf(\moodle_url::class, $result);
        $this->assertSame(
            "https://www.example.com/moodle/pluginfile.php/$tenantcontext2->id/theme_boost/logo/$themerev/mylogo.gif",
            $result->out(false)
        );

        set_config('logo', '/default.jpg', 'theme_boost');

        $result = appearance::get_boost_setting_image_url('logo', null);
        $this->assertInstanceOf(\moodle_url::class, $result);
        $this->assertSame(
            "https://www.example.com/moodle/pluginfile.php/$syscontext->id/theme_boost/logo/$themerev/default.jpg",
            $result->out(false)
        );

        $result = appearance::get_boost_setting_image_url('logo', $tenant1->id);
        $this->assertInstanceOf(\moodle_url::class, $result);
        $this->assertSame(
            "https://www.example.com/moodle/pluginfile.php/$syscontext->id/theme_boost/logo/$themerev/default.jpg",
            $result->out(false)
        );

        $result = appearance::get_boost_setting_image_url('logo', $tenant2->id);
        $this->assertInstanceOf(\moodle_url::class, $result);
        $this->assertSame(
            "https://www.example.com/moodle/pluginfile.php/$tenantcontext2->id/theme_boost/logo/$themerev/mylogo.gif",
            $result->out(false)
        );

        config::override($tenant2->id, 'logo', '', 'theme_boost');
        $result = appearance::get_boost_setting_image_url('logo', $tenant2->id);
        $this->assertFalse($result);
    }

    /**
     * @covers ::is_valid_color
     */
    public function test_is_valid_color(): void {
        $this->assertTrue(appearance::is_valid_color('#abc'));
        $this->assertTrue(appearance::is_valid_color('#11aaFF'));
        $this->assertTrue(appearance::is_valid_color('red'));
        $this->assertTrue(appearance::is_valid_color('rgb(255,0,0)'));
        $this->assertTrue(appearance::is_valid_color('RGB(255, 0, 0)'));
        $this->assertTrue(appearance::is_valid_color('hsl(0, 100%, 50%)'));
        $this->assertTrue(appearance::is_valid_color('HSL(0,100%,50%)'));

        $this->assertFalse(appearance::is_valid_color(''));
        $this->assertFalse(appearance::is_valid_color(' red'));
        $this->assertFalse(appearance::is_valid_color('xxxxxx'));
        $this->assertFalse(appearance::is_valid_color('#abx'));
        $this->assertFalse(appearance::is_valid_color('rgb(1000,0,0)'));
        $this->assertFalse(appearance::is_valid_color('rgb(255  ,0,0)'));
    }
}
