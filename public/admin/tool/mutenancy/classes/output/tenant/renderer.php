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

namespace tool_mutenancy\output\tenant;

use tool_mutenancy\local\user;

/**
 * Tenant renderer.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class renderer extends \tool_mutenancy\output\tenant_renderer_base {
    #[\Override]
    public function render_section(\stdClass $tenant): string {
        global $DB, $USER;

        $result = '';

        $yesno = [
            0 => get_string('no'),
            1 => get_string('yes'),
        ];

        $details = new \tool_mulib\output\entity_details();

        $details->add(get_string('tenant_name', 'tool_mutenancy'), format_string($tenant->name));
        $details->add(get_string('tenant_idnumber', 'tool_mutenancy'), s($tenant->idnumber));

        if (!$tenant->archived) {
            $loginurl = new \tool_mutenancy\output\loginurl($tenant->id);
            $loginurl = $this->render($loginurl);
            $details->add(get_string('tenant_loginurl', 'tool_mutenancy'), $loginurl);
            $details->add(get_string('tenant_loginshow', 'tool_mutenancy'), $yesno[$tenant->loginshow]);
        }

        if ($tenant->memberlimit) {
            $count = user::count_members($tenant->id);
            $count = "$count / $tenant->memberlimit";
            $details->add(get_string('tenant_memberlimit', 'tool_mutenancy'), $count);
        }

        $context = \context_coursecat::instance($tenant->categoryid, IGNORE_MISSING);
        if ($context) {
            $url = null;
            if (has_capability('moodle/category:manage', $context)) {
                $url = new \moodle_url('/course/management.php', ['categoryid' => $tenant->categoryid]);
            } else if (has_capability('moodle/category:viewcourselist', $context)) {
                $url = new \moodle_url('/course/index.php', ['categoryid' => $tenant->categoryid]);
            }
            $name = $context->get_context_name(false);
            if ($url) {
                $name = \html_writer::link($url, $name);
            }
        } else {
            $name = get_string('error');
        }
        $details->add(get_string('tenant_category', 'tool_mutenancy'), $name);

        $cohort = $DB->get_record('cohort', ['id' => $tenant->cohortid]);
        if ($cohort) {
            $context = \context::instance_by_id($cohort->contextid);
            $name = format_string($cohort->name);
            $url = null;
            if (has_capability('moodle/cohort:view', $context)) {
                $url = new \moodle_url('/cohort/index.php', ['contextid' => $context->id]);
            }
            if ($url) {
                $name = \html_writer::link($url, $name);
            }
        } else {
            $name = get_string('error');
        }
        $details->add(get_string('tenant_cohort', 'tool_mutenancy'), $name);

        if ($tenant->assoccohortid) {
            $cohort = $DB->get_record('cohort', ['id' => $tenant->assoccohortid]);
            if ($cohort) {
                $context = \context::instance_by_id($cohort->contextid);
                $name = format_string($cohort->name);
                $url = null;
                if (has_capability('moodle/cohort:view', $context)) {
                    $url = new \moodle_url('/cohort/index.php', ['contextid' => $context->id]);
                }
                if ($url) {
                    $name = \html_writer::link($url, $name);
                }
            } else {
                $name = get_string('error');
            }
            $details->add(get_string('associate_cohort', 'tool_mutenancy'), $name);
        } else {
            $details->add(get_string('associate_cohort', 'tool_mutenancy'), get_string('notset', 'tool_mulib'));
        }

        $details->add(get_string('tenant_sitefullname', 'tool_mutenancy'), format_string($tenant->sitefullname ?? $tenant->name));
        $details->add(get_string('tenant_siteshortname', 'tool_mutenancy'), format_string($tenant->siteshortname ?? $tenant->idnumber));

        $count = user::count_users($tenant->id);
        $url = new \moodle_url('/admin/tool/mutenancy/tenant_users.php', ['id' => $tenant->id]);
        $count = \html_writer::link($url, $count);
        $details->add(get_string('tenant_users', 'tool_mutenancy'), $count);

        $managers = \tool_mutenancy\local\manager::get_manager_users($tenant->id);
        foreach ($managers as $uid => $fullname) {
            $url = new \moodle_url('/user/profile.php', ['id' => $uid]);
            $managers[$uid] = \html_writer::link($url, $fullname);
        }
        $managers = implode(', ', $managers);
        if ($managers === '') {
            $managers = '&nbsp;';
        }
        $action = '';
        $context = \context_tenant::instance($tenant->id);
        if (has_capability('tool/mutenancy:admin', $context)) {
            $url = new \moodle_url('/admin/tool/mutenancy/management/tenant_managers.php', ['id' => $tenant->id]);
            $action = new \tool_mulib\output\ajax_form\icon($url, get_string('tenant_managers', 'tool_mutenancy'), 'i/users');
            $action->set_form_size('sm');
            $action = $this->render($action);
        }
        $details->add(get_string('tenant_managers', 'tool_mutenancy'), $managers . $action);

        $action = '';
        if (has_capability('tool/mutenancy:admin', $context)) {
            if ($tenant->archived) {
                $url = new \moodle_url('/admin/tool/mutenancy/management/tenant_restore.php', ['id' => $tenant->id]);
                $action = new \tool_mulib\output\ajax_form\icon($url, get_string('tenant_restore', 'tool_mutenancy'), 't/edit');
                $action->set_form_size('sm');
            } else if ($USER->tenantid != $tenant->id) {
                $url = new \moodle_url('/admin/tool/mutenancy/management/tenant_archive.php', ['id' => $tenant->id]);
                $action = new \tool_mulib\output\ajax_form\icon($url, get_string('tenant_archive', 'tool_mutenancy'), 'i/settings');
                $action->set_form_size('sm');
            }
            $action = $this->render($action);
        }

        $details->add(get_string('tenant_archived', 'tool_mutenancy'), $yesno[$tenant->archived] . $action);

        $result .= $this->output->render($details);

        return $result;
    }
}
