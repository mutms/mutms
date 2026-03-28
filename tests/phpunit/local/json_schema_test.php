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

namespace tool_mulib\phpunit\local;

/**
 * JSON schema helper tests.
 *
 * @group       MuTMS
 * @package     tool_mulib
 * @copyright   2024 Open LMS
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \tool_mulib\local\json_schema
 */
final class json_schema_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers ::validate
     */
    public function test_validate(): void {
        $schema = <<<'JSON'
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "title": "Test",
  "description": "A product in the catalog",
  "type": "object",
    "properties": {
        "somename": {
            "type": ["string", "null"],
            "minLength": 1,
            "maxLength": 20
        },
        "someint": {
            "type": "integer",
            "minimum": 10,
            "maximum": 20
        },
        "somedate": {
            "type": "string",
            "format": "date-time"
        }
    },
    "required": ["somename"]  
}
JSON;

        $data = (object)[
            'somename' => 'abc',
            'someint' => 15,
            'somedate' => '2020-11-13T23:10:05+02:00',
        ];
        [$valid, $errors] = \tool_mulib\local\json_schema::validate($data, $schema);
        $this->assertTrue($valid);
        $this->assertSame([], $errors);

        $data = (object)[
            'somename' => null,
            'someint' => 15,
            'somedate' => '2020-11-13T23:10:05+02:00',
        ];
        [$valid, $errors] = \tool_mulib\local\json_schema::validate($data, $schema);
        $this->assertTrue($valid);
        $this->assertSame([], $errors);

        $data = (object)[
            'somename' => 'abc',
        ];
        [$valid, $errors] = \tool_mulib\local\json_schema::validate($data, $schema);
        $this->assertTrue($valid);
        $this->assertSame([], $errors);

        $data = (object)[
            'noname' => 'abc',
        ];
        [$valid, $errors] = \tool_mulib\local\json_schema::validate($data, $schema);
        $this->assertFalse($valid);
        $this->assertSame(['/' => ['The required properties (somename) are missing']], $errors);

        $data = (object)[
            'somename' => '',
            'someint' => 15,
            'somedate' => '2020-11-13T23:10:05+02:00',
        ];
        [$valid, $errors] = \tool_mulib\local\json_schema::validate($data, $schema);
        $this->assertFalse($valid);
        $this->assertSame(['/somename' => ['Minimum string length is 1, found 0']], $errors);

        $data = (object)[
            'somename' => 'abc',
            'someint' => 100,
            'somedate' => '2020-11-13T23:10:05+02:00',
        ];
        [$valid, $errors] = \tool_mulib\local\json_schema::validate($data, $schema);
        $this->assertFalse($valid);
        $this->assertSame(['/someint' => ['Number must be lower than or equal to 20']], $errors);

        $data = (object)[
            'somename' => 'abc',
            'someint' => 15,
            'somedate' => '2020-02-30T23:10:05+02:00',
        ];
        [$valid, $errors] = \tool_mulib\local\json_schema::validate($data, $schema);
        $this->assertFalse($valid);
        $this->assertSame(['/somedate' => ['The data must match the \'date-time\' format']], $errors);

        $data = (object)[
            'somename' => 'abc',
            'someint' => 15,
            'somedate' => '2020/02/02 23:10:05',
        ];
        [$valid, $errors] = \tool_mulib\local\json_schema::validate($data, $schema);
        $this->assertFalse($valid);
        $this->assertSame(['/somedate' => ['The data must match the \'date-time\' format']], $errors);
    }

    /**
     * @covers ::normalise_data
     */
    public function test_normalise_data(): void {
        $data = ['a', 1, false];
        $this->assertSame($data, \tool_mulib\local\json_schema::normalise_data($data));

        $data = 'a';
        $this->assertSame($data, \tool_mulib\local\json_schema::normalise_data($data));

        $data = 1;
        $this->assertSame($data, \tool_mulib\local\json_schema::normalise_data($data));

        $data = null;
        $this->assertSame($data, \tool_mulib\local\json_schema::normalise_data($data));

        $data = ['x' => 'a', 'Y' => 1];
        $result = \tool_mulib\local\json_schema::normalise_data($data);
        $this->assertIsObject($result);
        $this->assertSame($data, (array)$result);

        $data = (object)['x' => 'a', 'Y' => 1];
        $result = \tool_mulib\local\json_schema::normalise_data($data);
        $this->assertIsObject($result);
        $this->assertSame((array)$data, (array)$result);
    }
}
