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

namespace tool_muprog\customfield;

use core_customfield\field_controller;
use moodle_url, context;
use MoodleQuickForm;

/**
 * Custom fields handler for program allocations.
 *
 * @package    tool_muprog
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class allocation_handler extends \core_customfield\handler {
    /** @var context|null context for creation of new items */
    protected $newitemcontext;

    /**
     * Set context of program for creation of new allocations.
     *
     * @param context|null $context program context
     * @return void
     */
    public function set_new_item_context(?context $context): void {
        $this->newitemcontext = $context;
    }

    /**
     * URL for configuration of the fields on this handler.
     *
     * @return moodle_url
     */
    public function get_configuration_url(): moodle_url {
        return new moodle_url('/admin/tool/muprog/management/customfield_allocation.php', []);
    }

    /**
     * Returns context for management of fields and categories.
     *
     * @return context
     */
    public function get_configuration_context(): context {
        return \context_system::instance();
    }

    /**
     * Returns the context for instance.
     *
     * @param int $instanceid
     * @return context
     */
    public function get_instance_context(int $instanceid = 0): context {
        global $DB;

        if ($instanceid) {
            $allocation = $DB->get_record('tool_muprog_allocation', ['id' => $instanceid], '*', MUST_EXIST);
            $program = $DB->get_record('tool_muprog_program', ['id' => $allocation->programid], '*', MUST_EXIST);
            return context::instance_by_id($program->contextid);
        } else if ($this->newitemcontext) {
            return $this->newitemcontext;
        } else {
            return \context_system::instance();
        }
    }

    /**
     * Can current user configure custom fields?
     *
     * @return bool
     */
    public function can_configure(): bool {
        return has_capability('tool/muprog:configurecustomfields', $this->get_configuration_context());
    }

    /**
     * Can current user edit custom field value?
     *
     * @param field_controller $field
     * @param int $instanceid
     * @return bool
     */
    public function can_edit(field_controller $field, int $instanceid = 0): bool {
        $context = $this->get_instance_context($instanceid);
        return has_capability('tool/muprog:allocate', $context) || has_capability('tool/muprog:admin', $context);
    }

    /**
     * Can current user view custom field?
     *
     * @param field_controller $field
     * @param int $instanceid
     * @return bool
     */
    public function can_view(field_controller $field, int $instanceid): bool {
        global $USER, $DB;

        if ($field->get_configdata_property('visibilityeveryone')) {
            // Anyone who gets to place that displays custom fields can see them.
            return true;
        }

        $context = $this->get_instance_context($instanceid);

        if ($field->get_configdata_property('visibilitymanagers')) {
            if (has_capability('tool/muprog:view', $context)) {
                return true;
            }
        }

        if ($field->get_configdata_property('visibilityallocatee')) {
            $allocation = $DB->get_record('tool_muprog_allocation', ['id' => $instanceid]);
            if ($allocation && $USER->id == $allocation->userid && !$allocation->archived) {
                return true;
            }
        }

        // Fall back to editing capabilities in case the visibility is not configured.
        return has_capability('tool/muprog:allocate', $context) || has_capability('tool/muprog:admin', $context);
    }

    /**
     * Add custom visibility settings.
     *
     * @param MoodleQuickForm $mform
     */
    public function config_form_definition(MoodleQuickForm $mform): void {
        $mform->addElement('header', 'customfields_muprog', get_string('customfieldsettings', 'tool_muprog'));
        $mform->setExpanded('customfields_muprog', true);
        $mform->addElement('html', get_string('customfieldvisibleto', 'tool_muprog'));

        $mform->addElement(
            'advcheckbox',
            'configdata[visibilitymanagers]',
            '',
            get_string('customfieldvisible:viewcapability', 'tool_muprog'),
            ['group' => 1]
        );

        $mform->addElement(
            'advcheckbox',
            'configdata[visibilityallocatee]',
            '',
            get_string('customfieldvisible:allocatee', 'tool_muprog'),
            ['group' => 1]
        );

        $mform->addElement(
            'advcheckbox',
            'configdata[visibilityeveryone]',
            '',
            get_string('customfieldvisible:everyone', 'tool_muprog'),
            ['group' => 1]
        );
    }
}
