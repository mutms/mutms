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

namespace tool_mutenancy\local\form;

use tool_mutenancy\external\form_autocomplete\tenant_managers_userids;

/**
 * Tenant managers form.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tenant_managers extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition(): void {
        $mform = $this->_form;
        $tenant = $this->_customdata['tenant'];
        $context = $this->_customdata['context'];
        $userids = $this->_customdata['userids'];

        $info = '<div class="alert alert-info">' . markdown_to_html(get_string('member_managers_info', 'tool_mutenancy')) . '</div>';
        $mform->addElement('html', $info);

        tenant_managers_userids::add_element(
            $mform,
            ['tenantid' => $tenant->id],
            'userids',
            get_string('tenant_managers', 'tool_mutenancy'),
            $context
        );
        $mform->setDefault('userids', $userids);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setConstant('id', $tenant->id);

        $this->add_action_buttons(true, get_string('update'));
    }

    #[\Override]
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $tenant = $this->_customdata['tenant'];
        $context = $this->_customdata['context'];
        foreach ($data['userids'] as $userid) {
            $error = tenant_managers_userids::validate_value($userid, ['tenantid' => $tenant->id], $context);
            if ($error !== null) {
                $errors['userids'] = $error;
                break;
            }
        }

        return $errors;
    }
}
