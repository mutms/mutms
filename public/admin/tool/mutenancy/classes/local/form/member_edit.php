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

use core\exception\coding_exception;
use context_user;
use core_component, core_text, core_user;
use webservice;

/**
 * Tenant member creation and updating form.
 *
 * See original code in /user/editadvanced_form.php file.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class member_edit extends \tool_mulib\local\ajax_form {
    #[\Override]
    public function definition() {
        global $CFG;

        // phpcs:disable

        $mform = $this->_form;

        $editoroptions = $this->_customdata['editoroptions'];
        $filemanageroptions = $this->_customdata['filemanageroptions'];
        $user = $this->_customdata['user'];
        $userid = $user->id;

        if ($userid == -1) {
            $info = '<div class="alert alert-info">' . markdown_to_html(get_string('member_create_info', 'tool_mutenancy')) . '</div>';
            $mform->addElement('html', $info);
        }

        $strgeneral  = get_string('general');
        $strrequired = get_string('required');

        // Add some extra hidden fields.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'tenantid');
        $mform->setType('tenantid', PARAM_INT);

        // Print the required moodle fields first.
        $mform->addElement('header', 'moodle', $strgeneral);

        $auths = core_component::get_plugin_list('auth');
        $cannotchangepass = [];
        $cannotchangeusername = [];
        foreach ($auths as $auth => $unused) {
            $authinst = get_auth_plugin($auth);

            if (!$authinst->is_internal()) {
                $cannotchangeusername[] = $auth;
            }

            $passwordurl = $authinst->change_password_url();
            if (!($authinst->can_change_password() && empty($passwordurl))) {
                if ($userid < 1 and $authinst->is_internal()) {
                    // This is unlikely but we can not create account without password
                    // when plugin uses passwords, we need to set it initially at least.
                } else {
                    $cannotchangepass[] = $auth;
                }
            }
        }

        $purpose = user_edit_map_field_purpose($userid, 'username');
        $mform->addElement('text', 'username', get_string('username'), 'size="20"' . $purpose);
        $mform->addHelpButton('username', 'username', 'auth');
        $mform->setType('username', PARAM_RAW);

        if ($userid !== -1) {
            $mform->disabledIf('username', 'auth', 'in', $cannotchangeusername);
        } else {
            $mform->addRule('username', $strrequired, 'required', null, 'client');
        }

        $mform->addElement('static', 'staticauth', get_string('type_auth', 'core_plugin'), get_string('pluginname', 'auth_' . $user->auth));

        $mform->addElement('advcheckbox', 'suspended', get_string('suspended', 'auth'));
        $mform->addHelpButton('suspended', 'suspended', 'auth');

        $mform->addElement('checkbox', 'createpassword', get_string('createpassword', 'auth'));
        $mform->disabledIf('createpassword', 'auth', 'in', $cannotchangepass);

        if (!empty($CFG->passwordpolicy)) {
            $mform->addElement('static', 'passwordpolicyinfo', '', print_password_policy());
        }

        $purpose = user_edit_map_field_purpose($userid, 'password');
        $mform->addElement('passwordunmask', 'newpassword', get_string('newpassword'),
            'maxlength="'.MAX_PASSWORD_CHARACTERS.'" size="20"' . $purpose);
        $mform->addRule('newpassword', get_string('maximumchars', '', MAX_PASSWORD_CHARACTERS),
            'maxlength', MAX_PASSWORD_CHARACTERS, 'client');
        $mform->addHelpButton('newpassword', 'newpassword');
        $mform->setType('newpassword', core_user::get_property_type('password'));
        $mform->disabledIf('newpassword', 'createpassword', 'checked');

        $mform->disabledIf('newpassword', 'auth', 'in', $cannotchangepass);

        // Check if the user has active external tokens.
        if ($userid and empty($CFG->passwordchangetokendeletion)) {
            if ($tokens = webservice::get_active_tokens($userid)) {
                $services = '';
                foreach ($tokens as $token) {
                    $services .= format_string($token->servicename) . ',';
                }
                $services = get_string('userservices', 'webservice', rtrim($services, ','));
                $mform->addElement('advcheckbox', 'signoutofotherservices', get_string('signoutofotherservices'), $services);
                $mform->addHelpButton('signoutofotherservices', 'signoutofotherservices');
                $mform->disabledIf('signoutofotherservices', 'newpassword', 'eq', '');
                $mform->setDefault('signoutofotherservices', 1);
            }
        }

        $mform->addElement('advcheckbox', 'preference_auth_forcepasswordchange', get_string('forcepasswordchange'));
        $mform->addHelpButton('preference_auth_forcepasswordchange', 'forcepasswordchange');
        $mform->disabledIf('preference_auth_forcepasswordchange', 'createpassword', 'checked');

        // Shared fields.
        useredit_shared_definition($mform, $editoroptions, $filemanageroptions, $user);

        // Extra settigs.
        if (!empty($CFG->disableuserimages) || $userid == -1) {
            $mform->removeElement('currentpicture');
            $mform->removeElement('deletepicture');
            $mform->removeElement('imagefile');
            $mform->removeElement('imagealt');
            $mform->removeElement('moodle_picture');
        } else {
            $mform->setExpanded('moodle_picture', false); // Do not expand anything to fit ajax form.
        }

        // Next the customisable profile fields.
        profile_definition($mform, $userid);

        if ($user->id > 0) {
            $this->add_action_buttons(true, get_string('member_update', 'tool_mutenancy'));
        } else {
            $this->add_action_buttons(true, get_string('member_create', 'tool_mutenancy'));
        }

        $this->set_data($user);
    }

    #[\Override]
    public function definition_after_data() {
        global $CFG, $DB, $OUTPUT;

        $mform = $this->_form;
        $userid = $mform->getElementValue('id');

        // Trim required name fields.
        foreach (useredit_get_required_name_fields() as $field) {
            $mform->applyFilter($field, 'trim');
        }

        if ($user = $DB->get_record('user', array('id' => $userid))) {

            // Remove description.
            if (empty($user->description) && !empty($CFG->profilesforenrolledusersonly) && !$DB->record_exists('role_assignments', array('userid' => $userid))) {
                $mform->removeElement('description_editor');
            }

            // Print picture.
            $context = context_user::instance($user->id, MUST_EXIST);
            $fs = get_file_storage();
            $hasuploadedpicture = ($fs->file_exists($context->id, 'user', 'icon', 0, '/', 'f2.png') || $fs->file_exists($context->id, 'user', 'icon', 0, '/', 'f2.jpg'));
            if (!empty($user->picture) && $hasuploadedpicture) {
                $imagevalue = $OUTPUT->user_picture($user, array('courseid' => SITEID, 'size' => 64));
            } else {
                $imagevalue = get_string('none');
            }
            $imageelement = $mform->getElement('currentpicture');
            $imageelement->setValue($imagevalue);

            if ($mform->elementExists('deletepicture') && !$hasuploadedpicture) {
                $mform->removeElement('deletepicture');
            }

            // Disable fields that are locked by auth plugins.
            $fields = get_user_fieldnames();
            $authplugin = get_auth_plugin($user->auth);
            $customfields = $authplugin->get_custom_user_profile_fields();
            $customfieldsdata = profile_user_record($userid, false);
            $fields = array_merge($fields, $customfields);
            foreach ($fields as $field) {
                if ($field === 'description') {
                    // Hard coded hack for description field. See MDL-37704 for details.
                    $formfield = 'description_editor';
                } else {
                    $formfield = $field;
                }
                if (!$mform->elementExists($formfield)) {
                    continue;
                }

                // Get the original value for the field.
                if (in_array($field, $customfields)) {
                    $key = str_replace('profile_field_', '', $field);
                    $value = isset($customfieldsdata->{$key}) ? $customfieldsdata->{$key} : '';
                } else {
                    $value = $user->{$field};
                }

                $configvariable = 'field_lock_' . $field;
                if (isset($authplugin->config->{$configvariable})) {
                    if ($authplugin->config->{$configvariable} === 'locked') {
                        $mform->hardFreeze($formfield);
                        $mform->setConstant($formfield, $value);
                    } else if ($authplugin->config->{$configvariable} === 'unlockedifempty' and $value != '') {
                        $mform->hardFreeze($formfield);
                        $mform->setConstant($formfield, $value);
                    }
                }
            }

            // Next the customisable profile fields.
            profile_definition_after_data($mform, $user->id);

        } else {
            profile_definition_after_data($mform, 0);
        }
    }

    #[\Override]
    public function validation($data, $files) {
        global $CFG, $DB;

        $errors = parent::validation($data, $files);

        $usernew = (object)$data;
        $usernew->username = trim($usernew->username);
        $user = $DB->get_record('user', array('id' => $usernew->id));

        if (!$user and !empty($usernew->createpassword)) {
            if ($usernew->suspended) {
                // Show some error because we can not mail suspended users.
                $errors['suspended'] = get_string('error');
            }
        } else {
            if (!empty($usernew->newpassword)) {
                $errmsg = ''; // Prevent eclipse warning.
                if (!check_password_policy($usernew->newpassword, $errmsg, $usernew)) {
                    $errors['newpassword'] = $errmsg;
                }
            }
        }

        if (empty($usernew->username)) {
            // Might be only whitespace.
            $errors['username'] = get_string('required');
        } else if (!$user or $user->username !== $usernew->username) {
            // Check new username does not exist.
            if ($DB->record_exists('user', array('username' => $usernew->username, 'mnethostid' => $CFG->mnet_localhost_id))) {
                $errors['username'] = get_string('usernameexists');
            }
            // Check allowed characters.
            if ($usernew->username !== core_text::strtolower($usernew->username)) {
                $errors['username'] = get_string('usernamelowercase');
            } else {
                if ($usernew->username !== core_user::clean_field($usernew->username, 'username')) {
                    $errors['username'] = get_string('invalidusername');
                }
            }
        }

        // Validate email.
        if (!$user or (isset($usernew->email) && $user->email !== $usernew->email)) {
            if (!validate_email($usernew->email)) {
                $errors['email'] = get_string('invalidemail');
            } else if (empty($CFG->allowaccountssameemail)) {
                // Make a case-insensitive query for the given email address.
                $select = $DB->sql_equal('email', ':email', false) . ' AND mnethostid = :mnethostid AND id <> :userid';
                $params = array(
                    'email' => $usernew->email,
                    'mnethostid' => $CFG->mnet_localhost_id,
                    'userid' => $usernew->id
                );
                // If there are other user(s) that already have the same email, show an error.
                if ($DB->record_exists_select('user', $select, $params)) {
                    $errors['email'] = get_string('emailexists');
                }
            }
        }

        // Next the customisable profile fields.
        $errors += profile_validation($usernew, $files);

        return $errors;
    }
}
