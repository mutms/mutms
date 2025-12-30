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

namespace tool_mulib\phpunit\local;

use tool_mulib\local\mulib;

/**
 * MuTMS helper tests.
 *
 * @group       MuTMS
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mulib\local\mulib
 */
final class mulib_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_is_muprog_available(): void {
        $this->assertSame(
            file_exists(__DIR__ . '/../../../../../tool/muprog/version.php'),
            mulib::is_muprog_available()
        );
    }

    public function test_is_muprog_active(): void {
        $this->assertFalse(mulib::is_muprog_active());

        set_config('active', '1', 'tool_muprog');

        if (mulib::is_muprog_available()) {
            $this->assertTrue(mulib::is_muprog_active());
        } else {
            $this->assertFalse(mulib::is_muprog_active());
        }
    }

    public function test_is_mucertify_available(): void {
        $this->assertSame(
            file_exists(__DIR__ . '/../../../../../tool/mucertify/version.php'),
            mulib::is_mucertify_available()
        );
    }

    public function test_is_mucertify_active(): void {
        $this->assertFalse(mulib::is_mucertify_active());

        set_config('active', '1', 'tool_mucertify');

        if (mulib::is_mucertify_available()) {
            $this->assertTrue(mulib::is_mucertify_active());
        } else {
            $this->assertFalse(mulib::is_mucertify_active());
        }
    }

    public function test_is_mutrain_available(): void {
        $this->assertSame(
            file_exists(__DIR__ . '/../../../../../tool/mutrain/version.php'),
            mulib::is_mutrain_available()
        );
    }

    public function test_is_mutrain_active(): void {
        $this->assertFalse(mulib::is_mutrain_active());

        set_config('active', '1', 'tool_mutrain');

        if (mulib::is_mutrain_available()) {
            $this->assertTrue(mulib::is_mutrain_active());
        } else {
            $this->assertFalse(mulib::is_mutrain_active());
        }
    }

    public function test_is_muhome_available(): void {
        $this->assertSame(
            file_exists(__DIR__ . '/../../../../../tool/muhome/version.php'),
            mulib::is_muhome_available()
        );
    }

    public function test_is_muhome_active(): void {
        $this->assertFalse(mulib::is_muhome_active());

        set_config('active', '1', 'tool_muhome');

        if (mulib::is_muhome_available()) {
            $this->assertTrue(mulib::is_muhome_active());
        } else {
            $this->assertFalse(mulib::is_muhome_active());
        }
    }

    public function test_is_mutenancy_available(): void {
        $this->assertSame(
            file_exists(__DIR__ . '/../../../../../tool/mutenancy/version.php'),
            mulib::is_mutenancy_available()
        );
    }

    public function test_is_mutenancy_active(): void {
        if (!mulib::is_mutenancy_available()) {
            $this->assertFalse(mulib::is_mutenancy_active());
            return;
        }

        \tool_mutenancy\local\tenancy::deactivate();
        $this->assertFalse(mulib::is_mutenancy_active());

        \tool_mutenancy\local\tenancy::activate();
        $this->assertTrue(mulib::is_mutenancy_active());
    }

    /**
     * Test conversion of dangerous characters and named entities to numeric entities.
     */
    public function test_clean_string(): void {
        $string = 'Žluťoučký koníček <tag> "test" \'example\' & escaped &amp; &lt; &gt; &quot; ';
        $cleaned = mulib::clean_string($string);

        $this->assertSame(
            'Žluťoučký koníček &#60;tag&#62; &#34;test&#34; &#39;example&#39; &#38; escaped &#38; &#60; &#62; &#34; ',
            $cleaned
        );

        // Repeated cleaning does not change result.
        $this->assertSame($cleaned, mulib::clean_string($cleaned));

        // Function s() does not modify it.
        $this->assertSame($cleaned, s($cleaned));

        // Function format_string() does not modify it.
        $this->assertSame($cleaned, format_string($cleaned));

        // Function clean_text() does not remove data.
        $this->assertSame($cleaned, mulib::clean_string(clean_text($cleaned)));

        // It can be converted back to raw UTF-8 characters.
        $this->assertSame(\core_text::entities_to_utf8($string), \core_text::entities_to_utf8($cleaned));
    }
}
