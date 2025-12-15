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

namespace customfield_mutrain;

use tool_mutrain\local\framework;

/**
 * Data class for training credits custom field
 *
 * @package    customfield_mutrain
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class data_controller extends \core_customfield\data_controller {
    /**
     * Return the name of the field where the information is stored
     * @return string
     */
    public function datafield(): string {
        return 'decvalue';
    }

    /**
     * Add fields for editing a text field.
     *
     * @param \MoodleQuickForm $mform
     */
    public function instance_form_definition(\MoodleQuickForm $mform): void {
        $elementname = $this->get_form_element_name();
        $required = $this->get_field()->get_configdata_property('required');

        $mform->addElement('text', $elementname, $this->get_field()->get_formatted_name(), 'size=5');
        $mform->setType($elementname, PARAM_RAW);

        if (class_exists(framework::class)) {
            $category = $this->get_field()->get_category();
            if (!framework::is_area_compatible($category->get('component'), $category->get('area'))) {
                $warning = get_string('error_incompatiblearea', 'tool_mutrain');
                $warning = '<div class="alert alert-warning">' . $warning . '</div>';
                $mform->addElement('static', $elementname . 'warning', '', $warning);
            }
        }

        if ($required) {
            $mform->addRule($elementname, null, 'required', null, 'client');
        }
    }

    /**
     * Validates data for this field.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function instance_form_validation(array $data, array $files): array {
        $errors = parent::instance_form_validation($data, $files);
        $elementname = $this->get_form_element_name();
        $required = $this->get_field()->get_configdata_property('required');

        if (!array_key_exists($elementname, $data)) {
            // This should not happen.
            return $errors;
        }

        if ($data[$elementname] !== '' && $data[$elementname] !== null) {
            $value = str_replace(',', '.', $data[$elementname]);
            if (!is_numeric($value) || $value < 0) {
                $errors[$elementname] = get_string('error');
            }
        }

        if ($required && empty($data[$elementname])) {
            $errors[$elementname] = get_string('required');
        }

        return $errors;
    }

    /**
     * Called before setting the data, will format float.
     *
     * @param \stdClass $instance custom field instance.
     * @return void
     */
    public function instance_form_before_set_data(\stdClass $instance): void {
        $value = $this->get_value();
        $value = format_float($value, 2, true, true);
        $instance->{$this->get_form_element_name()} = $value;
    }

    /**
     * Returns the value as it is stored in the database.
     *
     * @return mixed
     */
    public function get_value() {
        // Do NOT return default value here, we want the real database value used in fast DB queries.

        if (!$this->get('id')) {
            return null;
        }

        return $this->get($this->datafield());
    }

    /**
     * There is intentionally no support for default values.
     *
     * @return mixed
     */
    public function get_default_value() {
        return null;
    }

    /**
     * Saves the data coming from form
     *
     * @param \stdClass $datanew data coming from the form
     */
    public function instance_form_save(\stdClass $datanew): void {
        $elementname = $this->get_form_element_name();

        if (!property_exists($datanew, $elementname)) {
            return;
        }

        $value = $datanew->$elementname;
        if (empty($value) || trim($value) === '') {
            $value = null;
        } else {
            $value = str_replace(',', '.', $value);
            if (!is_numeric($value) || $value < 0) {
                throw new \invalid_parameter_exception('if provided training credits must be a positive number');
            }
            $value = format_float($value, 2, false, false);
        }

        $this->data->set($this->datafield(), $value);
        $this->data->set('value', (string)$value);
        $this->save();
    }

    /**
     * Returns value in a human-readable format
     *
     * @return string|null value or null if empty
     */
    public function export_value() {
        // Do not use parent::export_value() here.
        $value = $this->get_value();
        if ($value === null) {
            return null;
        }
        return format_float($value, 2, true, true);
    }
}
