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

namespace certificateelement_muprog\phpunit;

use certificateelement_muprog\element;

/**
 * Unit tests for programs element.
 *
 * @group      openlms
 * @package    certificateelement_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \certificateelement_muprog\element
 */
final class element_test extends \advanced_testcase {
    /**
     * Test set up.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers ::get_program_fields
     */
    public function test_get_program_fields(): void {
        $fields = element::get_program_fields();
        foreach ($fields as $field => $name) {
            $this->assertIsString($name);
            $this->assertSame($field, clean_param($field, PARAM_ALPHANUM));
        }
        $this->assertArrayNotHasKey('customfield', $fields);

        $this->setAdminUser();

        $fieldcategory = $this->getDataGenerator()->create_custom_field_category([
            'component' => 'tool_muprog',
            'area' => 'program',
            'name' => 'Program custom fields',
        ]);
        $field1 = $this->getDataGenerator()->create_custom_field([
            'shortname' => 'testfield1',
            'name' => 'Extra text field',
            'type' => 'text',
            'categoryid' => $fieldcategory->get('id'),
        ]);
        $field2 = $this->getDataGenerator()->create_custom_field([
            'shortname' => 'testfield2',
            'name' => 'Extra checkbox field',
            'type' => 'checkbox',
            'categoryid' => $fieldcategory->get('id'),
            'configdata' => ['visibilitymanagers' => true],
        ]);

        $fields2 = element::get_program_fields();
        $this->assertArrayHasKey('customfield', $fields2);
        $this->assertSame('Custom field', $fields2['customfield']);
        unset($fields2['customfield']);
        $this->assertSame($fields, $fields2);
    }

    /**
     * @covers ::get_date_fields
     */
    public function test_get_date_fields(): void {
        $fields = element::get_date_fields();
        $this->assertSame(['timecompleted'], $fields);
    }

    /**
     * @covers ::get_date_formats
     */
    public function test_get_date_formats(): void {
        $formats = element::get_date_formats();
        foreach ($formats as $format => $example) {
            $this->assertIsString($example);
            $this->assertSame($format, clean_param($format, PARAM_STRINGID));
        }
    }

    /**
     * @covers ::format_date
     */
    public function test_format_date(): void {
        $now = time();
        $formats = element::get_date_formats();
        foreach ($formats as $format => $example) {
            $result = element::format_date($now, $format);
            $this->assertGreaterThan(0, strlen($result));
        }
    }

    /**
     * @covers ::decode_programfield_data
     */
    public function test_decode_programfield_data(): void {
        $result = element::decode_programfield_data(json_encode((object)['programfield' => 'fullname']));
        $this->assertIsObject($result);
        $this->assertSame(['programfield' => 'fullname'], (array)$result);

        $result = element::decode_programfield_data(json_encode((object)['programfield' => 'idnumber']));
        $this->assertIsObject($result);
        $this->assertSame(['programfield' => 'idnumber'], (array)$result);

        $result = element::decode_programfield_data(json_encode((object)['programfield' => 'timecompleted', 'dateformat' => 'strftimedatefullshort']));
        $this->assertIsObject($result);
        $this->assertSame(['programfield' => 'timecompleted', 'dateformat' => 'strftimedatefullshort'], (array)$result);

        $result = element::decode_programfield_data(json_encode(
            (object)['programfield' => 'customfield', 'customfieldid' => '111']
        ));
        $this->assertIsObject($result);
        $this->assertSame(['programfield' => 'customfield', 'customfieldid' => '111'], (array)$result);

        $result = element::decode_programfield_data(json_encode((object)['programfield' => 'timecompleted']));
        $this->assertIsObject($result);
        $this->assertSame(['programfield' => 'timecompleted', 'dateformat' => 'strftimedate'], (array)$result);

        $result = element::decode_programfield_data('');
        $this->assertIsObject($result);
        $this->assertSame(['programfield' => null], (array)$result);

        $result = element::decode_programfield_data(null);
        $this->assertIsObject($result);
        $this->assertSame(['programfield' => null], (array)$result);

        // Historic date formats.

        $result = element::decode_programfield_data('fullname');
        $this->assertIsObject($result);
        $this->assertSame(['programfield' => 'fullname'], (array)$result);

        $result = element::decode_programfield_data('idnumber');
        $this->assertIsObject($result);
        $this->assertSame(['programfield' => 'idnumber'], (array)$result);

        $result = element::decode_programfield_data(json_encode((object)['dateitem' => 'timecompleted', 'dateformat' => 'strftimedatefullshort']));
        $this->assertIsObject($result);
        $this->assertSame(['programfield' => 'timecompleted', 'dateformat' => 'strftimedatefullshort'], (array)$result);

        $result = element::decode_programfield_data('timecompleted');
        $this->assertIsObject($result);
        $this->assertSame(['programfield' => 'timecompleted', 'dateformat' => 'strftimedate'], (array)$result);
    }

    /**
     * @covers ::get_programfield
     */
    public function test_get_programfield(): void {
        /** @var \tool_certificate_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_certificate');

        $this->setAdminUser();

        $certificate1 = $generator->create_template((object)['name' => 'Certificate 1']);
        $pageid = $generator->create_page($certificate1)->get_id();

        /** @var element $element */
        $element = $generator->create_element($pageid, 'muprog', ['name' => 'Nazev', 'programfield' => 'fullname']);
        $this->assertSame(['programfield' => 'fullname'], (array)$element->get_programfield());

        /** @var element $element */
        $element = $generator->create_element($pageid, 'muprog', ['name' => 'ID programu', 'programfield' => 'idnumber']);
        $this->assertSame(['programfield' => 'idnumber'], (array)$element->get_programfield());

        /** @var element $element */
        $element = $generator->create_element($pageid, 'muprog', ['name' => 'Odkaz', 'programfield' => 'url']);
        $this->assertSame(['programfield' => 'url'], (array)$element->get_programfield());

        /** @var element $element */
        $element = $generator->create_element($pageid, 'muprog', ['name' => 'Dokonceno', 'programfield' => 'timecompleted', 'dateformat' => 'strftimedate']);
        $this->assertSame(['programfield' => 'timecompleted', 'dateformat' => 'strftimedate'], (array)$element->get_programfield());

        $fieldcategory = $this->getDataGenerator()->create_custom_field_category([
            'component' => 'tool_muprog',
            'area' => 'program',
            'name' => 'Program custom fields',
        ]);
        $field1 = $this->getDataGenerator()->create_custom_field([
            'shortname' => 'testfield1',
            'name' => 'Extra text field',
            'type' => 'text',
            'categoryid' => $fieldcategory->get('id'),
        ]);
        /** @var element $element */
        $element = $generator->create_element($pageid, 'muprog', ['name' => 'Some text', 'programfield' => 'customfield', 'customfieldid' => $field1->get('id')]);
        $this->assertSame(['programfield' => 'customfield', 'customfieldid' => $field1->get('id')], (array)$element->get_programfield());
    }

    /**
     * @covers ::prepare_data_for_form
     */
    public function test_prepare_data_for_form(): void {
        /** @var \tool_certificate_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_certificate');

        $this->setAdminUser();

        $certificate1 = $generator->create_template((object)['name' => 'Certificate 1']);
        $pageid = $generator->create_page($certificate1)->get_id();

        $element = $generator->create_element($pageid, 'muprog', ['name' => 'Nazev', 'programfield' => 'fullname']);
        $result = $element->prepare_data_for_form();
        $this->assertSame('fullname', $result->programfield);
        $this->assertSame('Nazev', $result->name);

        $element = $generator->create_element($pageid, 'muprog', ['name' => 'ID programu', 'programfield' => 'idnumber']);
        $result = $element->prepare_data_for_form();
        $this->assertSame('idnumber', $result->programfield);
        $this->assertSame('ID programu', $result->name);

        $element = $generator->create_element($pageid, 'muprog', ['name' => 'Odkaz', 'programfield' => 'url']);
        $result = $element->prepare_data_for_form();
        $this->assertSame('url', $result->programfield);
        $this->assertSame('Odkaz', $result->name);

        $element = $generator->create_element($pageid, 'muprog', ['name' => 'Dokonceno', 'programfield' => 'timecompleted', 'dateformat' => 'strftimedate']);
        $result = $element->prepare_data_for_form();
        $this->assertSame('timecompleted', $result->programfield);
        $this->assertSame('strftimedate', $result->dateformat);
        $this->assertSame('Dokonceno', $result->name);

        $element = element::instance(0, (object)['pageid' => $pageid, 'element' => 'muprog']);
        $result = $element->prepare_data_for_form();
        $this->assertSame(null, $result->programfield);

        $fieldcategory = $this->getDataGenerator()->create_custom_field_category([
            'component' => 'tool_muprog',
            'area' => 'program',
            'name' => 'Program custom fields',
        ]);
        $field1 = $this->getDataGenerator()->create_custom_field([
            'shortname' => 'testfield1',
            'name' => 'Extra text field',
            'type' => 'text',
            'categoryid' => $fieldcategory->get('id'),
        ]);
        $element = $generator->create_element($pageid, 'muprog', ['name' => 'Some text', 'programfield' => 'customfield', 'customfieldid' => $field1->get('id')]);
        $result = $element->prepare_data_for_form();
        $this->assertSame('customfield', $result->programfield);
        $this->assertSame($field1->get('id'), $result->customfieldid);
        $this->assertSame('Some text', $result->name);
    }

    /**
     * @covers ::render_html
     * @covers ::get_preview
     */
    public function test_render_html(): void {
        /** @var \tool_certificate_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_certificate');

        $this->setAdminUser();

        $certificate1 = $generator->create_template((object)['name' => 'Certificate 1']);
        $pageid = $generator->create_page($certificate1)->get_id();

        $element = $generator->create_element($pageid, 'muprog', ['programfield' => 'fullname']);
        $this->assertStringContainsString('Program 001', $element->render_html());

        $formdata = (object)['name' => 'Program id', 'programfield' => 'idnumber'];
        $element = $generator->create_element($pageid, 'muprog', $formdata);
        $this->assertStringContainsString('P001', $element->render_html());

        $element = $generator->create_element($pageid, 'muprog', ['programfield' => 'url']);
        $this->assertStringContainsString('https://www.example.com/moodle/admin/tool/muprog/catalogue/program?id=1', $element->render_html());

        $element = $generator->create_element($pageid, 'muprog', ['programfield' => 'timecompleted', 'dateformat' => 'strftimedate']);
        $date = userdate(time(), '%d %B %Y');
        $this->assertStringContainsString($date, $element->render_html());

        $fieldcategory = $this->getDataGenerator()->create_custom_field_category([
            'component' => 'tool_muprog',
            'area' => 'program',
            'name' => 'Program custom fields',
        ]);
        $field1 = $this->getDataGenerator()->create_custom_field([
            'shortname' => 'testfield1',
            'name' => 'Extra text field',
            'type' => 'text',
            'categoryid' => $fieldcategory->get('id'),
        ]);
        $element = $generator->create_element($pageid, 'muprog', ['name' => 'Some text', 'programfield' => 'customfield', 'customfieldid' => $field1->get('id')]);
        $this->assertStringContainsString('[Extra text field]', $element->render_html());
    }

    /**
     * @covers ::render
     */
    public function test_render(): void {
        /** @var \tool_certificate_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_certificate');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $this->setAdminUser();

        $program1 = $programgenerator->create_program();
        $certificate1 = $generator->create_template((object)['name' => 'Certificate 1']);
        $pageid = $generator->create_page($certificate1)->get_id();
        $generator->create_element($pageid, 'muprog', ['programfield' => 'fullname']);
        $generator->create_element($pageid, 'muprog', ['name' => 'Program id', 'programfield' => 'idnumber']);
        $generator->create_element($pageid, 'muprog', ['programfield' => 'url']);
        $generator->create_element($pageid, 'muprog', ['programfield' => 'timecompleted', 'dateformat' => 'strftimedate']);

        // Generate PDF for preview.
        $filecontents = $generator->generate_pdf($certificate1, true);
        $filesize = \core_text::strlen($filecontents);
        $this->assertTrue($filesize > 30000 && $filesize < 120000);

        // Generate PDF for issue.
        $user = $this->getDataGenerator()->create_user();
        $issuedata = [
            'programid' => $program1->id,
            'programfullname' => 'Program 001',
            'programidnumber' => 'P001',
            'programtimecompleted' => time(),
            'programallocationid' => '10',
        ];
        $issue = $generator->issue($certificate1, $user, null, $issuedata, 'tool_muprog');
        $filecontents = $generator->generate_pdf($certificate1, false, $issue);
        $filesize = \core_text::strlen($filecontents);
        $this->assertTrue($filesize > 30000 && $filesize < 120000);

        // Incorrectly manually generated cert.
        $issue = $generator->issue($certificate1, $user);
        $filecontents = $generator->generate_pdf($certificate1, false, $issue);
        $filesize = \core_text::strlen($filecontents);
        $this->assertTrue($filesize > 30000 && $filesize < 120000);

        // Generate PDF with program custom field.
        $user2 = $this->getDataGenerator()->create_user();
        $fieldcategory = $this->getDataGenerator()->create_custom_field_category([
            'component' => 'tool_muprog',
            'area' => 'program',
            'name' => 'Program custom fields',
        ]);
        $field1 = $this->getDataGenerator()->create_custom_field([
            'shortname' => 'testfield1',
            'name' => 'Extra text field',
            'type' => 'text',
            'categoryid' => $fieldcategory->get('id'),
        ]);
        $element = $generator->create_element($pageid, 'muprog', ['name' => 'Some text', 'programfield' => 'customfield', 'customfieldid' => $field1->get('id')]);
        $program2 = $programgenerator->create_program(['customfield_testfield1' => 'abc']);
        $issuedata = [
            'programid' => $program2->id,
            'programfullname' => $program2->fullname,
            'programidnumber' => $program2->idnumber,
            'programtimecompleted' => time(),
            'programallocationid' => '111',
        ];
        $issue = $generator->issue($certificate1, $user, null, $issuedata, 'tool_muprog');
        $filecontents = $generator->generate_pdf($certificate1, false, $issue);
        $filesize = \core_text::strlen($filecontents);
        $this->assertTrue($filesize > 30000 && $filesize < 120000);

        // Deleted program.
        \tool_muprog\local\program::delete($program2->id);
        $issuedata = [
            'programid' => $program2->id,
            'programfullname' => $program2->fullname,
            'programidnumber' => $program2->idnumber,
            'programtimecompleted' => time(),
            'programallocationid' => '111',
        ];
        $issue = $generator->issue($certificate1, $user, null, $issuedata, 'tool_muprog');
        $filecontents = $generator->generate_pdf($certificate1, false, $issue);
        $filesize = \core_text::strlen($filecontents);
        $this->assertTrue($filesize > 30000 && $filesize < 120000);

        // Incorrectly manually generated cert.
        $issue = $generator->issue($certificate1, $user);
        $filecontents = $generator->generate_pdf($certificate1, false, $issue);
        $filesize = \core_text::strlen($filecontents);
        $this->assertTrue($filesize > 30000 && $filesize < 120000);
    }
}
