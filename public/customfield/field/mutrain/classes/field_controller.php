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
 * Data class for training custom field
 *
 * @package    customfield_mutrain
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class field_controller extends \core_customfield\field_controller {
    /**
     * Plugin type text
     */
    const TYPE = 'mutrain';

    /**
     * Add fields for editing a text field.
     *
     * @param \MoodleQuickForm $mform
     */
    public function config_form_definition(\MoodleQuickForm $mform): void {
        $category = $this->get_category();
        if (class_exists(framework::class)) {
            if (!framework::is_area_compatible($category->get('component'), $category->get('area'))) {
                $warning = get_string('error_incompatiblearea', 'tool_mutrain');
                $warning = '<div class="alert alert-warning">' . $warning . '</div>';
                $mform->addElement('static', 'warningtraining', '', $warning);
            }
        }
    }

    /**
     * Delete a field and all associated data.
     *
     * @return bool
     */
    public function delete(): bool {
        global $DB;

        $fieldid = $this->get('id');

        if (class_exists(framework::class)) {
            $DB->delete_records('tool_mutrain_completion', ['fieldid' => $fieldid]);
            $DB->delete_records('tool_mutrain_field', ['fieldid' => $fieldid]);
        }

        return parent::delete();
    }
}
