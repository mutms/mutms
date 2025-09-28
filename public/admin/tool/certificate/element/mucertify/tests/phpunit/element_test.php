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

namespace certificateelement_mucertify\phpunit;

use certificateelement_mucertify\element;

/**
 * Unit tests for certify element.
 *
 * @group      openlms
 * @package    certificateelement_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \certificateelement_mucertify\element
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
     * @covers ::get_certification_fields
     */
    public function test_get_certification_fields(): void {
        $fields = element::get_certification_fields();
        foreach ($fields as $field => $name) {
            $this->assertIsString($name);
            $this->assertSame($field, clean_param($field, PARAM_ALPHANUM));
        }
        $this->assertArrayNotHasKey('customfield', $fields);

        $this->setAdminUser();

        $fieldcategory = $this->getDataGenerator()->create_custom_field_category([
            'component' => 'tool_mucertify',
            'area' => 'certification',
            'name' => 'Certification custom fields',
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

        $fields2 = element::get_certification_fields();
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
        $this->assertSame(['timecertified', 'timefrom', 'timeuntil'], $fields);
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
     * @covers ::decode_certificationfield_data
     */
    public function test_decode_certificationfield_data(): void {
        $result = element::decode_certificationfield_data(json_encode((object)['certificationfield' => 'fullname']));
        $this->assertIsObject($result);
        $this->assertSame(['certificationfield' => 'fullname'], (array)$result);

        $result = element::decode_certificationfield_data(json_encode((object)['certificationfield' => 'idnumber']));
        $this->assertIsObject($result);
        $this->assertSame(['certificationfield' => 'idnumber'], (array)$result);

        $result = element::decode_certificationfield_data(json_encode((object)['certificationfield' => 'timecertified', 'dateformat' => 'strftimedatefullshort']));
        $this->assertIsObject($result);
        $this->assertSame(['certificationfield' => 'timecertified', 'dateformat' => 'strftimedatefullshort'], (array)$result);

        $result = element::decode_certificationfield_data(json_encode(
            (object)['certificationfield' => 'customfield', 'customfieldid' => '111']
        ));
        $this->assertIsObject($result);
        $this->assertSame(['certificationfield' => 'customfield', 'customfieldid' => '111'], (array)$result);

        $result = element::decode_certificationfield_data(json_encode((object)['certificationfield' => 'timecertified']));
        $this->assertIsObject($result);
        $this->assertSame(['certificationfield' => 'timecertified', 'dateformat' => 'strftimedate'], (array)$result);

        $result = element::decode_certificationfield_data(json_encode((object)['certificationfield' => 'timefrom', 'dateformat' => 'strftimedatefullshort']));
        $this->assertIsObject($result);
        $this->assertSame(['certificationfield' => 'timefrom', 'dateformat' => 'strftimedatefullshort'], (array)$result);

        $result = element::decode_certificationfield_data(json_encode((object)['certificationfield' => 'timefrom']));
        $this->assertIsObject($result);
        $this->assertSame(['certificationfield' => 'timefrom', 'dateformat' => 'strftimedate'], (array)$result);

        $result = element::decode_certificationfield_data(json_encode((object)['certificationfield' => 'timeuntil', 'dateformat' => 'strftimedatefullshort']));
        $this->assertIsObject($result);
        $this->assertSame(['certificationfield' => 'timeuntil', 'dateformat' => 'strftimedatefullshort'], (array)$result);

        $result = element::decode_certificationfield_data(json_encode((object)['certificationfield' => 'timeuntil']));
        $this->assertIsObject($result);
        $this->assertSame(['certificationfield' => 'timeuntil', 'dateformat' => 'strftimedate'], (array)$result);

        $result = element::decode_certificationfield_data('');
        $this->assertIsObject($result);
        $this->assertSame(['certificationfield' => null], (array)$result);

        $result = element::decode_certificationfield_data(null);
        $this->assertIsObject($result);
        $this->assertSame(['certificationfield' => null], (array)$result);

        // Historic date formats.

        $result = element::decode_certificationfield_data('fullname');
        $this->assertIsObject($result);
        $this->assertSame(['certificationfield' => 'fullname'], (array)$result);

        $result = element::decode_certificationfield_data('idnumber');
        $this->assertIsObject($result);
        $this->assertSame(['certificationfield' => 'idnumber'], (array)$result);

        $result = element::decode_certificationfield_data(json_encode((object)['dateitem' => 'timecertified', 'dateformat' => 'strftimedatefullshort']));
        $this->assertIsObject($result);
        $this->assertSame(['certificationfield' => 'timecertified', 'dateformat' => 'strftimedatefullshort'], (array)$result);

        $result = element::decode_certificationfield_data('timecertified');
        $this->assertIsObject($result);
        $this->assertSame(['certificationfield' => 'timecertified', 'dateformat' => 'strftimedate'], (array)$result);

        $result = element::decode_certificationfield_data(json_encode((object)['dateitem' => 'timefrom', 'dateformat' => 'strftimedatefullshort']));
        $this->assertIsObject($result);
        $this->assertSame(['certificationfield' => 'timefrom', 'dateformat' => 'strftimedatefullshort'], (array)$result);

        $result = element::decode_certificationfield_data('timefrom');
        $this->assertIsObject($result);
        $this->assertSame(['certificationfield' => 'timefrom', 'dateformat' => 'strftimedate'], (array)$result);

        $result = element::decode_certificationfield_data(json_encode((object)['dateitem' => 'timeuntil', 'dateformat' => 'strftimedatefullshort']));
        $this->assertIsObject($result);
        $this->assertSame(['certificationfield' => 'timeuntil', 'dateformat' => 'strftimedatefullshort'], (array)$result);

        $result = element::decode_certificationfield_data('timeuntil');
        $this->assertIsObject($result);
        $this->assertSame(['certificationfield' => 'timeuntil', 'dateformat' => 'strftimedate'], (array)$result);
    }

    /**
     * @covers ::get_certificationfield
     */
    public function test_get_certificationfield(): void {
        /** @var \tool_certificate_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_certificate');

        $this->setAdminUser();

        $certificate1 = $generator->create_template((object)['name' => 'Certificate 1']);
        $pageid = $generator->create_page($certificate1)->get_id();

        /** @var element $element */
        $element = $generator->create_element($pageid, 'mucertify', ['name' => 'Nazev', 'certificationfield' => 'fullname']);
        $this->assertSame(['certificationfield' => 'fullname'], (array)$element->get_certificationfield());

        /** @var element $element */
        $element = $generator->create_element($pageid, 'mucertify', ['name' => 'ID certifikace', 'certificationfield' => 'idnumber']);
        $this->assertSame(['certificationfield' => 'idnumber'], (array)$element->get_certificationfield());

        /** @var element $element */
        $element = $generator->create_element($pageid, 'mucertify', ['name' => 'Odkaz', 'certificationfield' => 'url']);
        $this->assertSame(['certificationfield' => 'url'], (array)$element->get_certificationfield());

        /** @var element $element */
        $element = $generator->create_element($pageid, 'mucertify', ['name' => 'Dokonceno', 'certificationfield' => 'timecertified', 'dateformat' => 'strftimedate']);
        $this->assertSame(['certificationfield' => 'timecertified', 'dateformat' => 'strftimedate'], (array)$element->get_certificationfield());

        /** @var element $element */
        $element = $generator->create_element($pageid, 'mucertify', ['name' => 'Od', 'certificationfield' => 'timefrom', 'dateformat' => 'strftimedate']);
        $this->assertSame(['certificationfield' => 'timefrom', 'dateformat' => 'strftimedate'], (array)$element->get_certificationfield());

        /** @var element $element */
        $element = $generator->create_element($pageid, 'mucertify', ['name' => 'Do', 'certificationfield' => 'timeuntil', 'dateformat' => 'strftimedate']);
        $this->assertSame(['certificationfield' => 'timeuntil', 'dateformat' => 'strftimedate'], (array)$element->get_certificationfield());

        $fieldcategory = $this->getDataGenerator()->create_custom_field_category([
            'component' => 'tool_mucertify',
            'area' => 'certification',
            'name' => 'Certification custom fields',
        ]);
        $field1 = $this->getDataGenerator()->create_custom_field([
            'shortname' => 'testfield1',
            'name' => 'Extra text field',
            'type' => 'text',
            'categoryid' => $fieldcategory->get('id'),
        ]);
        /** @var element $element */
        $element = $generator->create_element($pageid, 'mucertify', ['name' => 'Some text', 'certificationfield' => 'customfield', 'customfieldid' => $field1->get('id')]);
        $this->assertSame(['certificationfield' => 'customfield', 'customfieldid' => $field1->get('id')], (array)$element->get_certificationfield());
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

        $element = $generator->create_element($pageid, 'mucertify', ['name' => 'Nazev', 'certificationfield' => 'fullname']);
        $result = $element->prepare_data_for_form();
        $this->assertSame('fullname', $result->certificationfield);
        $this->assertSame('Nazev', $result->name);

        $element = $generator->create_element($pageid, 'mucertify', ['name' => 'ID certifikace', 'certificationfield' => 'idnumber']);
        $result = $element->prepare_data_for_form();
        $this->assertSame('idnumber', $result->certificationfield);
        $this->assertSame('ID certifikace', $result->name);

        $element = $generator->create_element($pageid, 'mucertify', ['name' => 'Odkaz', 'certificationfield' => 'url']);
        $result = $element->prepare_data_for_form();
        $this->assertSame('url', $result->certificationfield);
        $this->assertSame('Odkaz', $result->name);

        $element = $generator->create_element($pageid, 'mucertify', ['name' => 'Dokonceno', 'certificationfield' => 'timecertified', 'dateformat' => 'strftimedate']);
        $result = $element->prepare_data_for_form();
        $this->assertSame('timecertified', $result->certificationfield);
        $this->assertSame('strftimedate', $result->dateformat);
        $this->assertSame('Dokonceno', $result->name);

        $element = $generator->create_element($pageid, 'mucertify', ['name' => 'Od', 'certificationfield' => 'timefrom', 'dateformat' => 'strftimedate']);
        $result = $element->prepare_data_for_form();
        $this->assertSame('timefrom', $result->certificationfield);
        $this->assertSame('strftimedate', $result->dateformat);
        $this->assertSame('Od', $result->name);

        $element = $generator->create_element($pageid, 'mucertify', ['name' => 'Do', 'certificationfield' => 'timeuntil', 'dateformat' => 'strftimedate']);
        $result = $element->prepare_data_for_form();
        $this->assertSame('timeuntil', $result->certificationfield);
        $this->assertSame('strftimedate', $result->dateformat);
        $this->assertSame('Do', $result->name);

        $element = element::instance(0, (object)['pageid' => $pageid, 'element' => 'mucertify']);
        $result = $element->prepare_data_for_form();
        $this->assertSame(null, $result->certificationfield);

        $fieldcategory = $this->getDataGenerator()->create_custom_field_category([
            'component' => 'tool_mucertify',
            'area' => 'certification',
            'name' => 'Certification custom fields',
        ]);
        $field1 = $this->getDataGenerator()->create_custom_field([
            'shortname' => 'testfield1',
            'name' => 'Extra text field',
            'type' => 'text',
            'categoryid' => $fieldcategory->get('id'),
        ]);
        $element = $generator->create_element($pageid, 'mucertify', ['name' => 'Some text', 'certificationfield' => 'customfield', 'customfieldid' => $field1->get('id')]);
        $result = $element->prepare_data_for_form();
        $this->assertSame('customfield', $result->certificationfield);
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

        $element = $generator->create_element($pageid, 'mucertify', ['certificationfield' => 'fullname']);
        $this->assertStringContainsString('Certification 001', $element->render_html());

        $formdata = (object)['name' => 'Certification id', 'certificationfield' => 'idnumber'];
        $element = $generator->create_element($pageid, 'mucertify', $formdata);
        $this->assertStringContainsString('C001', $element->render_html());

        $element = $generator->create_element($pageid, 'mucertify', ['certificationfield' => 'url']);
        $this->assertStringContainsString('https://www.example.com/moodle/admin/tool/mucertify/catalogue/certification?id=1', $element->render_html());

        $element = $generator->create_element($pageid, 'mucertify', ['certificationfield' => 'timecertified', 'dateformat' => 'strftimedate']);
        $date = userdate(time(), '%d %B %Y');
        $this->assertStringContainsString($date, $element->render_html());

        $element = $generator->create_element($pageid, 'mucertify', ['certificationfield' => 'timefrom', 'dateformat' => 'strftimedate']);
        $date = userdate(time() - WEEKSECS, '%d %B %Y');
        $this->assertStringContainsString($date, $element->render_html());

        $element = $generator->create_element($pageid, 'mucertify', ['certificationfield' => 'timeuntil', 'dateformat' => 'strftimedate']);
        $date = userdate(time() + YEARSECS, '%d %B %Y');
        $this->assertStringContainsString($date, $element->render_html());

        $fieldcategory = $this->getDataGenerator()->create_custom_field_category([
            'component' => 'tool_mucertify',
            'area' => 'certification',
            'name' => 'Certification custom fields',
        ]);
        $field1 = $this->getDataGenerator()->create_custom_field([
            'shortname' => 'testfield1',
            'name' => 'Extra text field',
            'type' => 'text',
            'categoryid' => $fieldcategory->get('id'),
        ]);
        $element = $generator->create_element($pageid, 'mucertify', ['name' => 'Some text', 'certificationfield' => 'customfield', 'customfieldid' => $field1->get('id')]);
        $this->assertStringContainsString('[Extra text field]', $element->render_html());
    }

    /**
     * @covers ::render
     */
    public function test_render(): void {
        /** @var \tool_certificate_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_certificate');

        /** @var \tool_mucertify_generator $certificationgenerator */
        $certificationgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $this->setAdminUser();

        $certification1 = $certificationgenerator->create_certification();
        $certificate1 = $generator->create_template((object)['name' => 'Certificate 1']);
        $pageid = $generator->create_page($certificate1)->get_id();
        $generator->create_element($pageid, 'mucertify', ['certificationfield' => 'fullname']);
        $generator->create_element($pageid, 'mucertify', ['name' => 'Certification id', 'certificationfield' => 'idnumber']);
        $generator->create_element($pageid, 'mucertify', ['certificationfield' => 'url']);
        $generator->create_element($pageid, 'mucertify', ['certificationfield' => 'timecertified', 'dateformat' => 'strftimedate']);
        $generator->create_element($pageid, 'mucertify', ['certificationfield' => 'timefrom', 'dateformat' => 'strftimedate']);
        $generator->create_element($pageid, 'mucertify', ['certificationfield' => 'timeuntil', 'dateformat' => 'strftimedate']);

        // Generate PDF for preview.
        $filecontents = $generator->generate_pdf($certificate1, true);
        $filesize = \core_text::strlen($filecontents);
        $this->assertTrue($filesize > 30000 && $filesize < 120000);

        // Generate PDF for issue with expiration.
        $user1 = $this->getDataGenerator()->create_user();
        $issuedata = [
            'certificationid' => $certification1->id,
            'certificationfullname' => 'Certification 001',
            'certificationidnumber' => 'C001',
            'certificationassignmentid' => '2',
            'certificationperiodid' => '3',
            'certificationtimecertified' => time(),
            'certificationtimefrom' => time() - WEEKSECS,
            'certificationtimeuntil' => time() + YEARSECS,
            'certificationfirst' => true,
        ];
        $issue = $generator->issue($certificate1, $user1, null, $issuedata, 'tool_mucertify');
        $filecontents = $generator->generate_pdf($certificate1, false, $issue);
        $filesize = \core_text::strlen($filecontents);
        $this->assertTrue($filesize > 30000 && $filesize < 120000);

        // Incorrectly manually generated cert.
        $issue = $generator->issue($certificate1, $user1);
        $filecontents = $generator->generate_pdf($certificate1, false, $issue);
        $filesize = \core_text::strlen($filecontents);
        $this->assertTrue($filesize > 30000 && $filesize < 120000);

        // Generate PDF for issue without expiration.
        $user2 = $this->getDataGenerator()->create_user();
        $issuedata = [
            'certificationid' => '1',
            'certificationfullname' => 'Certification 001',
            'certificationidnumber' => 'C001',
            'certificationassignmentid' => '11',
            'certificationperiodid' => '13',
            'certificationtimecertified' => time(),
            'certificationtimefrom' => time() - WEEKSECS,
            'certificationtimeuntil' => null,
            'certificationfirst' => true,
        ];
        $issue = $generator->issue($certificate1, $user2, null, $issuedata, 'tool_mucertify');
        $filecontents = $generator->generate_pdf($certificate1, false, $issue);
        $filesize = \core_text::strlen($filecontents);
        $this->assertTrue($filesize > 30000 && $filesize < 120000);

        // Generate PDF with certification custom field.
        $user2 = $this->getDataGenerator()->create_user();
        $fieldcategory = $this->getDataGenerator()->create_custom_field_category([
            'component' => 'tool_mucertify',
            'area' => 'certification',
            'name' => 'Certification custom fields',
        ]);
        $field1 = $this->getDataGenerator()->create_custom_field([
            'shortname' => 'testfield1',
            'name' => 'Extra text field',
            'type' => 'text',
            'categoryid' => $fieldcategory->get('id'),
        ]);
        $element = $generator->create_element($pageid, 'mucertify', ['name' => 'Some text', 'certificationfield' => 'customfield', 'customfieldid' => $field1->get('id')]);
        $certification2 = $certificationgenerator->create_certification(['customfield_testfield1' => 'abc']);
        $issuedata = [
            'certificationid' => $certification2->id,
            'certificationfullname' => $certification2->fullname,
            'certificationidnumber' => $certification2->idnumber,
            'certificationtimecompleted' => time(),
            'certificationallocationid' => '111',
        ];
        $issue = $generator->issue($certificate1, $user1, null, $issuedata, 'tool_mucertify');
        $filecontents = $generator->generate_pdf($certificate1, false, $issue);
        $filesize = \core_text::strlen($filecontents);
        $this->assertTrue($filesize > 30000 && $filesize < 120000);

        // Deleted certification.
        \tool_mucertify\local\certification::delete($certification2->id);
        $issuedata = [
            'certificationid' => $certification2->id,
            'certificationfullname' => $certification2->fullname,
            'certificationidnumber' => $certification2->idnumber,
            'certificationtimecompleted' => time(),
            'certificationallocationid' => '111',
        ];
        $issue = $generator->issue($certificate1, $user1, null, $issuedata, 'tool_mucertify');
        $filecontents = $generator->generate_pdf($certificate1, false, $issue);
        $filesize = \core_text::strlen($filecontents);
        $this->assertTrue($filesize > 30000 && $filesize < 120000);

        // Incorrectly manually generated cert.
        $issue = $generator->issue($certificate1, $user1);
        $filecontents = $generator->generate_pdf($certificate1, false, $issue);
        $filesize = \core_text::strlen($filecontents);
        $this->assertTrue($filesize > 30000 && $filesize < 120000);
    }
}
