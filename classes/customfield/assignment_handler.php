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

namespace tool_mucertify\customfield;

use core_customfield\field_controller;
use moodle_url, context;
use MoodleQuickForm;

/**
 * Custom fields handler for certification assignments.
 *
 * @package    tool_mucertify
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class assignment_handler extends \core_customfield\handler {
    /** @var context|null context for creation of new items */
    protected $newitemcontext;

    /**
     * Set context of certification for creation of new assignments.
     *
     * @param context|null $context certification context
     * @return void
     */
    public function set_new_item_context(?context $context): void {
        $this->newitemcontext = $context;
    }

    /**
     * Configuration of custom fields for assignments.
     *
     * @return moodle_url
     */
    public function get_configuration_url(): moodle_url {
        return new moodle_url('/admin/tool/mucertify/management/customfield_assignment.php');
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
            $assignment = $DB->get_record('tool_mucertify_assignment', ['id' => $instanceid], '*', MUST_EXIST);
            $certification = $DB->get_record('tool_mucertify_certification', ['id' => $assignment->certificationid], '*', MUST_EXIST);
            return context::instance_by_id($certification->contextid);
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
        return has_capability('tool/mucertify:configurecustomfields', $this->get_configuration_context());
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
        return has_capability('tool/mucertify:assign', $context) || has_capability('tool/mucertify:admin', $context);
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
            if (has_capability('tool/mucertify:view', $context)) {
                return true;
            }
        }

        if ($field->get_configdata_property('visibilityassignee')) {
            $assignment = $DB->get_record('tool_mucertify_assignment', ['id' => $instanceid]);
            if ($assignment && $USER->id == $assignment->userid && !$assignment->archived) {
                return true;
            }
        }

        // Fall back to editing capabilities in case the visibility is not configured.
        return has_capability('tool/mucertify:assign', $context) || has_capability('tool/mucertify:admin', $context);
    }

    /**
     * Add custom visibility settings.
     *
     * @param MoodleQuickForm $mform
     */
    public function config_form_definition(MoodleQuickForm $mform): void {
        $mform->addElement('header', 'customfield_mucertify', get_string('customfieldsettings', 'tool_mucertify'));
        $mform->setExpanded('customfield_mucertify', true);
        $mform->addElement('html', get_string('customfieldvisibleto', 'tool_mucertify'));

        $mform->addElement(
            'advcheckbox',
            'configdata[visibilitymanagers]',
            '',
            get_string('customfieldvisible:viewcapability', 'tool_mucertify'),
            ['group' => 1]
        );

        $mform->addElement(
            'advcheckbox',
            'configdata[visibilityassignee]',
            '',
            get_string('customfieldvisible:assignee', 'tool_mucertify'),
            ['group' => 1]
        );

        $mform->addElement(
            'advcheckbox',
            'configdata[visibilityeveryone]',
            '',
            get_string('customfieldvisible:everyone', 'tool_mucertify'),
            ['group' => 1]
        );
    }
}
