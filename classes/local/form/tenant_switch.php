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

namespace tool_mutenancy\local\form;

/**
 * Switch tenant form.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tenant_switch extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition(): void {
        $mform = $this->_form;

        $info = '<div class="alert alert-info">' . markdown_to_html(get_string('tenant_switch_info', 'tool_mutenancy')) . '</div>';
        $mform->addElement('html', $info);

        $options = self::get_options();
        $mform->addElement('selectgroups', 'tenantid', get_string('tenant', 'tool_mutenancy'), $options);
        $mform->setDefault('tenantid', (int)\tool_mutenancy\local\tenancy::get_current_tenantid());

        $this->add_action_buttons(true, get_string('tenant_switch', 'tool_mutenancy'));
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $tenantid = (int)\tool_mutenancy\local\tenancy::get_current_tenantid();

        if ($data['tenantid'] == $tenantid) {
            $errors['tenantid'] = get_string('error:changerequired', 'tool_mutenancy');
        }

        return $errors;
    }

    /**
     * Returns list of tenant switching targets.
     * @return array
     */
    public static function get_options(): array {
        global $DB, $USER;

        $notenant = get_string('tenant_switch_notenant', 'tool_mutenancy');
        $mytenants = get_string('tenant_switch_my', 'tool_mutenancy');

        $options = [];
        $options[''][0] = $notenant;

        $sql = "SELECT t.id, t.name
                  FROM {tool_mutenancy_tenant} t
                  JOIN {cohort_members} cm ON cm.cohortid = t.assoccohortid AND cm.userid = :me
                 WHERE t.archived = 0";
        $tenants = $DB->get_records_sql_menu($sql, ['me' => $USER->id]);
        $tenants = array_map('format_string', $tenants);
        \core_collator::asort($tenants);
        foreach ($tenants as $k => $v) {
            $options[$mytenants][$k] = $v;
        }

        $syscontext = \context_system::instance();
        if (!has_capability('tool/mutenancy:view', $syscontext)) {
            return $options;
        }

        if (isset($options[$mytenants])) {
            $othertenants = get_string('tenant_switch_other', 'tool_mutenancy');
        } else {
            $othertenants = get_string('tenants', 'tool_mutenancy');
        }

        $sql = "SELECT t.id, t.name
                  FROM {tool_mutenancy_tenant} t
             LEFT JOIN {cohort_members} cm ON cm.cohortid = t.assoccohortid AND cm.userid = :me
                 WHERE t.archived = 0 AND cm.id IS NULL";
        $tenants = $DB->get_records_sql_menu($sql, ['me' => $USER->id]);
        $tenants = array_map('format_string', $tenants);
        \core_collator::asort($tenants);
        foreach ($tenants as $k => $v) {
            $options[$othertenants][$k] = $v;
        }

        return $options;
    }
}
