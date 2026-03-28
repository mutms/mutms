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

use tool_mutenancy\local\tenancy;

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

        if (has_capability('tool/mutenancy:admin', \context_system::instance())) {
            $info = '<div class="alert alert-info">' . markdown_to_html(get_string('tenant_switch_info', 'tool_mutenancy')) . '</div>';
            $mform->addElement('html', $info);
        }

        $options = self::get_options();
        $mform->addElement('selectgroups', 'tenantid', tenancy::get_tenant_string('tenant'), $options);
        $mform->setDefault('tenantid', (int)\tool_mutenancy\local\tenancy::get_current_tenantid());

        $this->add_action_buttons(true, tenancy::get_tenant_string('tenant_switch'));
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

        $notenant = tenancy::get_tenant_string('tenant_switch_notenant');
        $mytenants = tenancy::get_tenants_string('tenant_switch_my');

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

        if (isset($options[$mytenants])) {
            $othertenants = tenancy::get_tenants_string('tenant_switch_other');
        } else {
            $othertenants = tenancy::get_tenants_string('tenants');
        }

        // Cheat here a bit to make this faster,
        // also keep this consistent with tenancy::can_switch().
        $syscontext = \context_system::instance();

        if (has_capability('tool/mutenancy:switch', $syscontext)) {
            $sql = "SELECT t.id, t.name
                      FROM {tool_mutenancy_tenant} t
                 LEFT JOIN {cohort_members} cm ON cm.cohortid = t.assoccohortid AND cm.userid = :me
                     WHERE t.archived = 0 AND cm.id IS NULL";
            $params = ['me' => $USER->id];
        } else {
            [$needed, $forbidden] = get_roles_with_cap_in_context($syscontext, 'tool/mutenancy:switch');
            if (!$needed) {
                return $options;
            }
            $needed = implode(',', $needed);
            $sql = "SELECT t.id, t.name
                      FROM {role_assignments} ra
                      JOIN {context} c ON c.id = ra.contextid AND c.contextlevel = :tenantlevel
                      JOIN {tool_mutenancy_tenant} t ON t.id = c.instanceid AND t.archived = 0
                 LEFT JOIN {cohort_members} cm ON cm.cohortid = t.assoccohortid AND cm.userid = ra.userid
                     WHERE ra.userid = :me AND ra.roleid IN ($needed) AND cm.id IS NULL";
            $params = ['tenantlevel' => \context_tenant::LEVEL, 'me' => $USER->id];
        }

        $tenants = $DB->get_records_sql_menu($sql, $params);
        $tenants = array_map('format_string', $tenants);
        \core_collator::asort($tenants);
        foreach ($tenants as $tid => $tname) {
            // Use real capability check here, no more guessing!
            $tenantcontext = \context_tenant::instance($tid);
            if (!has_capability('tool/mutenancy:switch', $tenantcontext)) {
                continue;
            }
            $options[$othertenants][$tid] = $tname;
        }

        return $options;
    }
}
