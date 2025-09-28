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

namespace tool_mutenancy\reportbuilder\local\systemreports;

use core_reportbuilder\local\entities\user;
use core_reportbuilder\local\helpers\user_profile_fields;
use core_reportbuilder\system_report;
use core_reportbuilder\local\helpers\database;
use core_user\fields;
use lang_string;
use moodle_url;

/**
 * Embedded tenant users report.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class users extends system_report {
    /** @var \stdClass */
    protected $tenant;
    /** @var \stdClass|false */
    protected $cohort = false;

    /**
     * Initialise report, we need to set the main table, load our entities and set columns/filters
     */
    protected function initialise(): void {
        global $DB, $CFG;

        $this->tenant = $DB->get_record('tool_mutenancy_tenant', ['id' => $this->get_context()->instanceid], '*', MUST_EXIST);
        if ($this->tenant->assoccohortid) {
            $this->cohort = $DB->get_record('cohort', ['id' => $this->tenant->assoccohortid]);
        }

        $entityuser = new user();
        $entityuseralias = $entityuser->get_table_alias('user');

        $this->set_main_table('user', $entityuseralias);
        $this->add_entity($entityuser);

        // All columns required by actions.
        $fullnamefields = array_map(fn($field) => "{$entityuseralias}.{$field}", fields::get_name_fields());
        $this->add_base_fields("{$entityuseralias}.id, {$entityuseralias}.confirmed, {$entityuseralias}.tenantid,
            {$entityuseralias}.suspended, {$entityuseralias}.username,
            {$entityuseralias}.mnethostid, {$entityuseralias}.auth," . implode(', ', $fullnamefields));

        $paramguest = database::generate_param_name();
        $this->add_base_condition_sql(
            "{$entityuseralias}.deleted <> 1 AND {$entityuseralias}.id <> :{$paramguest}",
            [$paramguest => $CFG->siteguest]
        );

        if ($this->tenant->assoccohortid) {
            $this->add_base_condition_sql("{$entityuseralias}.id IN (
                SELECT tuser.id
                  FROM {user} tuser
             LEFT JOIN {cohort_members} cm ON cm.cohortid = {$this->tenant->assoccohortid} AND cm.userid = tuser.id
                 WHERE (tuser.tenantid IS NULL AND cm.id IS NOT NULL)
                       OR tuser.tenantid = {$this->tenant->id}
               )");
        } else {
            $this->add_base_condition_sql("{$entityuseralias}.id IN (
                SELECT tuser.id
                  FROM {user} tuser
                 WHERE tuser.deleted = 0 AND tuser.tenantid = {$this->tenant->id})");
        }

        // Now we can call our helper methods to add the content we want to include in the report.
        $this->add_columns();
        $this->add_filters();
        $this->add_actions();

        // Set if report can be downloaded.
        $this->set_downloadable(true);
    }

    /**
     * Validates access to view this report
     *
     * @return bool
     */
    protected function can_view(): bool {
        if ($this->get_context()->contextlevel != CONTEXT_TENANT) {
            return false;
        }
        if (isguestuser() || !isloggedin()) {
            return false;
        }
        return has_capability('tool/mutenancy:view', $this->get_context());
    }

    /**
     * Adds the columns we want to display in the report
     *
     * They are all provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier
     */
    public function add_columns(): void {
        $entityuser = $this->get_entity('user');
        $entityuseralias = $entityuser->get_table_alias('user');

        $this->add_column($entityuser->get_column('fullnamewithpicturelink'));

        // Include identity field columns.
        $identitycolumns = $entityuser->get_identity_columns($this->get_context());
        foreach ($identitycolumns as $identitycolumn) {
            $this->add_column($identitycolumn);
        }

        // Add "Last access" column.
        $this->add_column(($entityuser->get_column('lastaccess'))
            ->set_callback(static function ($value, \stdClass $row): string {
                if ($row->lastaccess) {
                    return format_time(time() - $row->lastaccess);
                }
                return get_string('never');
            }));

        if ($column = $this->get_column('user:fullnamewithpicturelink')) {
            $column
                ->add_fields("{$entityuseralias}.suspended, {$entityuseralias}.confirmed")
                ->add_callback(static function (string $fullname, \stdClass $row): string {
                    if ($row->suspended) {
                        $fullname .= ' ' . \html_writer::tag(
                            'span',
                            get_string('suspended', 'moodle'),
                            ['class' => 'badge bg-secondary ms-1']
                        );
                    }
                    if (!$row->confirmed) {
                        $fullname .= ' ' . \html_writer::tag(
                            'span',
                            get_string('confirmationpending', 'admin'),
                            ['class' => 'badge bg-danger ms-1']
                        );
                    }
                    return $fullname;
                });
        }

        $this->add_column_from_entity('user:tenantmember');

        $this->set_initial_sort_column('user:fullnamewithpicturelink', SORT_ASC);
        $this->set_default_no_results_notice(new lang_string('nousersfound', 'moodle'));
    }

    /**
     * Adds the filters we want to display in the report
     *
     * They are all provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier
     */
    protected function add_filters(): void {
        $entityuser = $this->get_entity('user');
        $entityuseralias = $entityuser->get_table_alias('user');

        $filters = [
            'user:fullname',
            'user:firstname',
            'user:lastname',
            'user:username',
            'user:idnumber',
            'user:email',
            'user:department',
            'user:institution',
            'user:city',
            'user:country',
            'user:confirmed',
            'user:suspended',
            'user:timecreated',
            'user:lastaccess',
            'user:timemodified',
            'user:auth',
            'user:tenantmember',
        ];
        $this->add_filters_from_entities($filters);

        // Add user profile fields filters.
        $userprofilefields = new user_profile_fields($entityuseralias . '.id', $entityuser->get_entity_name());
        foreach ($userprofilefields->get_filters() as $filter) {
            $this->add_filter($filter);
        }
    }

    /**
     * Add the system report actions. An extra column will be appended to each row, containing all actions added here
     *
     * Note the use of ":id" placeholder which will be substituted according to actual values in the row
     */
    protected function add_actions(): void {
        global $SCRIPT;

        // Report builder download script is missing NO_DEBUG_DISPLAY
        // and template rendering is changing session after it is closed,
        // add a hacky workaround for now.
        if ($SCRIPT === '/reportbuilder/download.php') {
            return;
        }

        $contextsystem = \context_system::instance();
        $tenant = $this->tenant;
        $cohort = $this->cohort;

        $url = new moodle_url('/admin/tool/mutenancy/management/member_update.php', ['id' => ':id']);
        $link = new \tool_mulib\output\ajax_form\link($url, new lang_string('edit', 'moodle'), 't/edit');
        $link->set_modal_title(get_string('member_update', 'tool_mutenancy'));
        $link->set_form_size('xl');
        $this->add_action($link->create_report_action()
            ->add_callback(static function (\stdclass $row): bool {
                global $USER;
                if (!$row->tenantid) {
                    return false;
                }
                if ($row->id == $USER->id) {
                    return false;
                }
                if (is_siteadmin($row->id)) {
                    return false;
                }
                $userauth = get_auth_plugin($row->auth);
                if (!$userauth->can_edit_profile()) {
                    return false;
                }
                if ($userauth->edit_profile_url()) {
                    return false;
                }
                return has_capability('tool/mutenancy:memberupdate', \context_user::instance($row->id));
            }));

        $url = new moodle_url('/admin/tool/mutenancy/management/member_suspend.php', ['id' => ':id']);
        $link = new \tool_mulib\output\ajax_form\link($url, new lang_string('suspenduser', 'admin'), 't/show');
        $link->set_form_size('sm');
        $this->add_action($link->create_report_action()
            ->add_callback(static function (\stdclass $row): bool {
                global $USER;
                if (!$row->tenantid) {
                    return false;
                }
                if ($row->suspended) {
                    return false;
                }
                if (is_siteadmin($row->id) || $row->id == $USER->id) {
                    return false;
                }
                return has_capability('tool/mutenancy:memberupdate', \context_user::instance($row->id));
            }));

        $url = new moodle_url('/admin/tool/mutenancy/management/member_unsuspend.php', ['id' => ':id']);
        $link = new \tool_mulib\output\ajax_form\link($url, new lang_string('unsuspenduser', 'admin'), 't/hide');
        $link->set_form_size('sm');
        $this->add_action($link->create_report_action()
            ->add_callback(static function (\stdclass $row) use ($tenant): bool {
                if (!$row->tenantid) {
                    return false;
                }
                if (!$row->suspended) {
                    return false;
                }
                return has_capability('tool/mutenancy:memberupdate', \context_user::instance($row->id));
            }));

        $url = new moodle_url('/admin/tool/mutenancy/management/member_unlock.php', ['id' => ':id']);
        $link = new \tool_mulib\output\ajax_form\link($url, new lang_string('unlockaccount', 'admin'), 't/unlock');
        $link->set_form_size('sm');
        $this->add_action($link->create_report_action()
            ->add_callback(static function (\stdclass $row) use ($tenant): bool {
                if (!$row->tenantid) {
                    return false;
                }
                if ($row->suspended || $tenant->archived) {
                    return false;
                }
                return has_capability('tool/mutenancy:memberupdate', \context_user::instance($row->id))
                    && login_is_lockedout($row);
            }));

        $url = new moodle_url('/admin/tool/mutenancy/management/member_delete.php', ['id' => ':id']);
        $link = new \tool_mulib\output\ajax_form\link($url, new lang_string('delete', 'core'), 't/delete');
        $link->set_form_size('sm');
        $this->add_action($link->create_report_action(['class' => 'text-danger'])
            ->add_callback(static function (\stdclass $row): bool {
                global $USER;
                if (!$row->tenantid) {
                    return false;
                }
                if (is_siteadmin($row->id) || $row->id == $USER->id) {
                    return false;
                }
                return has_capability('tool/mutenancy:memberdelete', \context_user::instance($row->id));
            }));

        $this->add_action_divider();

        $url = new moodle_url('/admin/tool/mutenancy/management/associate_remove.php', ['id' => ':id', 'tenantid' => $this->tenant->id]);
        $link = new \tool_mulib\output\ajax_form\link($url, new lang_string('associate_remove', 'tool_mutenancy'), 'e/cancel');
        $this->add_action($link->create_report_action()
            ->add_callback(static function (\stdclass $row) use ($cohort): bool {
                global $DB;
                if ($row->tenantid) {
                    return false;
                }
                if ($cohort->component) {
                    return false;
                }
                $cohortcontext = \context::instance_by_id($cohort->contextid);
                if (!has_capability('moodle/cohort:assign', $cohortcontext)) {
                    return false;
                }
                if (!$DB->record_exists('cohort_members', ['cohortid' => $cohort->id, 'userid' => $row->id])) {
                    // This should not happen.
                    return false;
                }
                return true;
            }));

        $url = new moodle_url('/admin/tool/mutenancy/management/user_allocate.php', ['id' => ':id']);
        $link = new \tool_mulib\output\ajax_form\link($url, new lang_string('user_allocate', 'tool_mutenancy'), 'i/switch');
        $this->add_action($link->create_report_action()
            ->add_callback(static function (\stdclass $row) use ($contextsystem): bool {
                global $USER;
                if (is_siteadmin($row->id)) {
                    return false;
                }
                if ($row->id == $USER->id) {
                    return false;
                }
                return has_capability('tool/mutenancy:allocate', $contextsystem);
            }));

        $url = new moodle_url('/admin/tool/mutenancy/management/member_confirm.php', ['id' => ':id']);
        $link = new \tool_mulib\output\ajax_form\link($url, new lang_string('confirmaccount', 'core'), 't/check');
        $link->set_form_size('sm');
        $this->add_action($link->create_report_action()
            ->add_callback(static function (\stdclass $row): bool {
                if (!$row->tenantid) {
                    return false;
                }
                if ($row->confirmed) {
                    return false;
                }
                return has_capability('tool/mutenancy:memberupdate', \context_user::instance($row->id));
            }));

        $url = new moodle_url('/admin/tool/mutenancy/management/member_resend.php', ['id' => ':id']);
        $link = new \tool_mulib\output\ajax_form\link($url, new lang_string('resendemail', 'core'), 't/email');
        $link->set_form_size('sm');
        $this->add_action($link->create_report_action()
            ->add_callback(static function (\stdclass $row) use ($tenant): bool {
                if (!$row->tenantid) {
                    return false;
                }
                if ($row->suspended || $tenant->archived) {
                    return false;
                }
                if ($row->confirmed) {
                    return false;
                }
                return has_capability('tool/mutenancy:memberupdate', \context_user::instance($row->id));
            }));
    }

    /**
     * Row class
     *
     * @param \stdClass $row
     * @return string
     */
    public function get_row_class(\stdClass $row): string {
        return $row->suspended ? 'text-muted' : '';
    }
}
