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

namespace certificateelement_mucertify;

/**
 * The certificate element for certifications fields.
 *
 * @package    certificateelement_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class element extends \tool_certificate\element {
    /** @var string[] */
    private $fields = null;

    /**
     * Returns list of available certification fields.
     *
     * @return array
     */
    public static function get_certification_fields(): array {
        $fields = [
            'fullname' => get_string('certificationname', 'tool_mucertify'),
            'idnumber' => get_string('certificationidnumber', 'tool_mucertify'),
            'url' => get_string('certificationurl', 'tool_mucertify'),
            'timecertified' => get_string('certifieddate', 'tool_mucertify'),
            'timefrom' => get_string('fromdate', 'tool_mucertify'),
            'timeuntil' => get_string('untildate', 'tool_mucertify'),
        ];

        $handler = \tool_mucertify\customfield\certification_handler::create();
        if ($handler->get_fields()) {
            $fields['customfield'] = get_string('customfield', 'core_customfield');
        }

        return $fields;
    }

    /**
     * Returns fields that have date format.
     * @return string[]
     */
    public static function get_date_fields(): array {
        return ['timecertified', 'timefrom', 'timeuntil'];
    }

    /**
     * Helper function to return all the date and time formats.
     *
     * @return array the list of date format string names with examples
     */
    public static function get_date_formats(): array {
        // Hard-code date so users can see the difference between short dates with and without the leading zero.
        // Eg. 06/07/18 vs 6/07/18.
        $date = 1530849658;

        $strdateformats = [
            'strftimedate',
            'strftimedatefullshort',
            'strftimedatefullshortwleadingzero',
            'strftimedateshort',
            'strftimedaydate',
            'strftimedayshort',
            'strftimemonthyear',
            // On sites with timezones the actual time may be also important.
            'strftimedatetime',
            'strftimedatemonthtimeshort',
            'strftimedaydatetime',
            'strftimedatetimeshort',
        ];

        $dateformats = [];
        foreach ($strdateformats as $strdateformat) {
            $dateformats[$strdateformat] = self::format_date($date, $strdateformat);
        }

        return $dateformats;
    }

    /**
     * Returns the date in a selected readable format.
     *
     * @param int|null $timestamp
     * @param string $dateformat
     * @return string
     */
    public static function format_date(?int $timestamp, string $dateformat): string {
        if ($timestamp <= 0) {
            return '';
        }
        if (strpos($dateformat, 'wleadingzero') !== false) {
            $dateformat = str_replace('wleadingzero', '', $dateformat);
            return userdate($timestamp, get_string($dateformat, 'langconfig'), 99, false);
        } else {
            return userdate($timestamp, get_string($dateformat, 'langconfig'));
        }
    }

    /**
     * Decode database field tool_certificate_elements.data value.
     *
     * @param string|null $data
     * @return \stdClass
     */
    public static function decode_certificationfield_data(?string $data): \stdClass {
        if ($data === null || $data === '') {
            $fd = (object)['certificationfield' => null];
        } else if (substr($data, 0, 1) !== '{') {
            // Original field value is certification field.
            $fd = (object)['certificationfield' => $data];
        } else {
            $fd = json_decode($data);
            if (!is_object($fd)) {
                $fd = (object)['certificationfield' => null];
            } else if (isset($fd->dateitem)) {
                // Problematic optional json.
                $fd = (object)['certificationfield' => $fd->dateitem, 'dateformat' => $fd->dateformat];
            } else {
                if (empty($fd->certificationfield)) {
                    // Error indication.
                    $fd = (object)['certificationfield' => null];
                }
            }
        }

        $datefields = self::get_date_fields();
        if (in_array($fd->certificationfield, $datefields, true)) {
            if (empty($fd->dateformat)) {
                // Use default - first value from self::get_date_formats().
                $fd->dateformat = 'strftimedate';
            }
        }

        if ($fd->certificationfield === 'customfield') {
            if (empty($fd->customfieldid)) {
                $fd->customfieldid = null;
            }
        }

        return $fd;
    }

    /**
     * Returns certification field info.
     * @return \stdClass
     */
    public function get_certificationfield(): \stdClass {
        $data = $this->get_data();
        return self::decode_certificationfield_data($data);
    }

    /**
     * Returns certification custom fields.
     *
     * @return \core_customfield\field_controller[]
     */
    public function get_customfields(): array {
        if ($this->fields === null) {
            $handler = \tool_mucertify\customfield\certification_handler::create();
            $this->fields = $handler->get_fields();
        }
        return $this->fields;
    }

    /**
     * Prepare data to pass to moodleform::set_data()
     *
     * @return \stdClass|array
     */
    public function prepare_data_for_form() {
        $record = parent::prepare_data_for_form();
        $pf = $this->get_certificationfield();
        $record->certificationfield = $pf->certificationfield;
        foreach ((array)$pf as $k => $v) {
            $record->{$k} = $v;
        }
        return $record;
    }

    /**
     * This function renders the form elements when adding a certificate element.
     *
     * @param \MoodleQuickForm $mform the edit_form instance
     */
    public function render_form_elements($mform) {

        // Get the certification fields.
        $fields = self::get_certification_fields();
        $dateformats = self::get_date_formats();

        // Create the select box where the user field is selected.
        $mform->addElement(
            'select',
            'certificationfield',
            get_string('certificationfield', 'certificateelement_mucertify'),
            $fields
        );
        $mform->addHelpButton('certificationfield', 'certificationfield', 'certificateelement_mucertify');

        $mform->addElement('select', 'dateformat', get_string('dateformat', 'certificateelement_mucertify'), $dateformats);
        $mform->addHelpButton('dateformat', 'dateformat', 'certificateelement_mucertify');

        $nondates = $fields;
        foreach (self::get_date_fields() as $field) {
            unset($nondates[$field]);
        }
        $mform->hideIf('dateformat', 'certificationfield', 'in', array_keys($nondates));

        if (isset($fields['customfield'])) {
            $customfieldids = ['' => get_string('choosedots')];
            foreach ($this->get_customfields() as $cf) {
                $customfieldids[$cf->get('id')] = $cf->get_formatted_name();
            }
            $mform->addElement('select', 'customfieldid', get_string('customfield', 'core_customfield'), $customfieldids);
            $mform->hideIf('customfieldid', 'certificationfield', 'noteq', 'customfield');
        }

        parent::render_form_elements($mform);
    }

    /**
     * Handles saving the form elements created by this element.
     * Can be overridden if more functionality is needed.
     *
     * @param \stdClass $data the form data or partial data to be updated
     */
    public function save_form_data(\stdClass $data) {
        // If name is empty then use field type name.
        if (property_exists($data, 'name') && $data->name === '') {
            if ($data->certificationfield === 'customfield') {
                $cfs = $this->get_customfields();
                $data->name = $cfs[$data->customfieldid]->get_formatted_name();
            } else {
                $fields = self::get_certification_fields();
                $data->name = $fields[$data->certificationfield];
            }
        }

        // Encode database field tool_certificate_elements.data value.
        $fd = new \stdClass();
        $fd->certificationfield = $data->certificationfield;
        $datefields = self::get_date_fields();
        if (in_array($fd->certificationfield, $datefields, true)) {
            $fd->dateformat = $data->dateformat;
        } else if ($fd->certificationfield === 'customfield') {
            $fd->customfieldid = $data->customfieldid;
        }
        unset($data->certificationfield);
        unset($data->dateformat);
        unset($data->customfieldid);

        $data->data = json_encode($fd);
        parent::save_form_data($data);
    }

    /**
     * Get preview text for this field.
     *
     * @return string
     */
    protected function get_preview(): string {
        $pf = $this->get_certificationfield();
        if ($pf->certificationfield === 'fullname') {
            $value = 'Certification 001';
        } else if ($pf->certificationfield === 'idnumber') {
            $value = 'C001';
        } else if ($pf->certificationfield === 'url') {
            $url = new \moodle_url('/admin/tool/mucertify/catalogue/certification', ['id' => 1]);
            $value = \html_writer::link($url, $url->out(false));
        } else if ($pf->certificationfield === 'timecertified') {
            $value = $this->format_date(time(), $pf->dateformat);
        } else if ($pf->certificationfield === 'timefrom') {
            $value = $this->format_date(time() - WEEKSECS, $pf->dateformat);
        } else if ($pf->certificationfield === 'timeuntil') {
            $value = $this->format_date(time() + YEARSECS, $pf->dateformat);
        } else if ($pf->certificationfield === 'customfield') {
            $value = null;
            foreach ($this->get_customfields() as $cf) {
                if ($cf->get('id') == $pf->customfieldid) {
                    // It is not easy to guess what it would look like, so just use placeholder like value.
                    $value = '[' . $cf->get_formatted_name() . ']';
                    break;
                }
            }
            if ($value === null) {
                $value = get_string('error');
            }
        } else {
            $value = get_string('error');
        }
        return $value;
    }

    /**
     * Render the element in html.
     *
     * This function is used to render the element when we are using the
     * drag and drop interface to position it.
     */
    public function render_html() {
        $value = $this->get_preview();
        return \tool_certificate\element_helper::render_html_content($this, $value);
    }

    /**
     * Handles rendering the element on the pdf.
     *
     * @param \pdf $pdf the pdf object
     * @param bool $preview true if it is a preview, false otherwise
     * @param \stdClass $user the user we are rendering this for
     * @param \stdClass $issue the issue we are rendering
     */
    public function render($pdf, $preview, $user, $issue) {
        if ($preview) {
            $value = $this->get_preview();
        } else {
            $pf = $this->get_certificationfield();
            $data = (object)json_decode($issue->data);
            $value = get_string('error');
            if ($pf->certificationfield === 'fullname') {
                if (isset($data->certificationfullname)) {
                    $value = $data->certificationfullname;
                    $value = format_string($value, true, ['context' => \context_system::instance()]);
                }
            } else if ($pf->certificationfield === 'idnumber') {
                if (isset($data->certificationidnumber)) {
                    $value = $data->certificationidnumber;
                    $value = s($value);
                }
            } else if ($pf->certificationfield === 'url') {
                if (isset($data->certificationid)) {
                    $url = new \moodle_url('/admin/tool/mucertify/catalogue/certification.php', ['id' => $data->certificationid]);
                    $value = \html_writer::link($url, $url->out(false));
                }
            } else if ($pf->certificationfield === 'timecertified') {
                if (isset($data->certificationtimecertified)) {
                    $value = $this->format_date($data->certificationtimecertified, $pf->dateformat);
                }
            } else if ($pf->certificationfield === 'timefrom') {
                if (isset($data->certificationtimefrom)) {
                    $value = $this->format_date($data->certificationtimefrom, $pf->dateformat);
                }
            } else if ($pf->certificationfield === 'timeuntil') {
                if (property_exists($data, 'certificationtimeuntil')) {
                    if ($data->certificationtimeuntil === null) {
                        $value = get_string('notset', 'tool_mucertify');
                    } else {
                        $value = $this->format_date($data->certificationtimeuntil, $pf->dateformat);
                    }
                }
            } else if ($pf->certificationfield === 'customfield') {
                $cfs = $this->get_customfields();
                if (isset($cfs[$pf->customfieldid]) && isset($data->certificationid)) {
                    // Ignore the visibility here and use lower level API.
                    $cfdata = \core_customfield\api::get_instance_fields_data(
                        [$pf->customfieldid => $cfs[$pf->customfieldid]],
                        $data->certificationid
                    );
                    if (count($cfdata) === 1) {
                        $cfdata = reset($cfdata);
                        $value = (string)$cfdata->export_value();
                    }
                }
            }
        }

        \tool_certificate\element_helper::render_content($pdf, $this, $value);
    }
}
