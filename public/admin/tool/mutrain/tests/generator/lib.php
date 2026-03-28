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

use tool_mutrain\local\framework;

/**
 * Training generator.
 *
 * @package    tool_mutrain
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_mutrain_generator extends component_generator_base {
    /**
     * @var int keeps track of how many frameworks have been created.
     */
    protected $frameworkcount = 0;

    /**
     * To be called from data reset code only,
     * do not use in tests.
     * @return void
     */
    public function reset() {
        $this->frameworkcount = 0;
        parent::reset();
    }

    /**
     * Create a new framework.
     *
     * @param mixed $record
     * @return stdClass framework record
     */
    public function create_framework($record = null): stdClass {
        global $DB;

        $record = (object)(array)$record;

        $this->frameworkcount++;

        if (!isset($record->name)) {
            $record->name = 'Framework ' . $this->frameworkcount;
        }
        if (!isset($record->idnumber)) {
            $record->idnumber = null;
        }
        if (!isset($record->description)) {
            $record->description = '';
        }
        if (!isset($record->descriptionformat)) {
            $record->descriptionformat = FORMAT_HTML;
        }
        if (!isset($record->contextid)) {
            if (!empty($record->category)) {
                $category = $DB->get_record('course_categories', ['name' => $record->category], '*', MUST_EXIST);
                $context = context_coursecat::instance($category->id);
                $record->contextid = $context->id;
            } else if (!empty($record->context)) {
                $record->contextid = $record->context->id;
            } else {
                $syscontext = \context_system::instance();
                $record->contextid = $syscontext->id;
            }
        }
        unset($record->category);
        unset($record->context);

        if (!isset($record->requiredcredits)) {
            $record->requiredcredits = 100;
        }

        if (!isset($record->publicaccess)) {
            $record->publicaccess = 1;
        }

        if (!empty($record->fields)) {
            $fields = $record->fields;
        } else {
            $fields = [];
        }
        unset($record->fields);

        $framework = framework::create((array)$record);

        if ($fields) {
            if (!is_array($fields)) {
                $fields = explode(',', $fields);
            }
            foreach ($fields as $field) {
                $field = trim($field);
                if (is_number($field)) {
                    $fieldid = $field;
                } else {
                    $record = $DB->get_record('customfield_field', ['shortname' => $field], '*', MUST_EXIST);
                    $fieldid = $record->id;
                }
                framework::field_add($framework->id, $fieldid);
            }
        }

        return $framework;
    }
}
