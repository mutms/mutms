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

namespace tool_musudo\reportbuilder\local\systemreports;

use tool_musudo\reportbuilder\local\entities\sudoer;
use tool_mutenancy\reportbuilder\local\entities\tenant;
use core_reportbuilder\local\entities\user;
use core_reportbuilder\system_report;
use lang_string;
use moodle_url;

/**
 * Sudoers report.
 *
 * @package     tool_musudo
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class sudoers extends system_report {
    /** @var sudoer */
    protected $sudoerentity;
    /** @var user */
    protected $userentity;
    /** @var tenant */
    protected $tenantentity;

    #[\Override]
    protected function initialise(): void {
        global $PAGE;

        // RB does not set PAGE->context properly in ajax requests, oh well...
        if (defined('AJAX_SCRIPT') && AJAX_SCRIPT) {
            $PAGE->set_context($this->get_context());
        }

        $this->sudoerentity = new sudoer();
        $this->add_entity($this->sudoerentity);
        $sudoeralias = $this->sudoerentity->get_table_alias('tool_musudo_sudoer');
        $this->set_main_table('tool_musudo_sudoer', $sudoeralias);

        $this->userentity = new user();
        $useralias = $this->userentity->get_table_alias('user');
        $this->add_entity($this->userentity);
        $this->add_join("JOIN {user} {$useralias} ON {$useralias}.id = {$sudoeralias}.userid");

        if (\tool_musudo\local\util::is_mutenancy_active()) {
            $this->tenantentity = new tenant();
            $tenantalias = $this->tenantentity->get_table_alias('tool_mutenancy_tenant');
            $this->tenantentity
                ->add_joins($this->tenantentity->get_joins())
                ->add_join("LEFT JOIN {tool_mutenancy_tenant} {$tenantalias} ON {$tenantalias}.id = {$useralias}.tenantid");
            $this->add_entity($this->tenantentity);
        }

        $this->add_base_fields("{$sudoeralias}.id");

        $this->add_columns();
        $this->add_filters();
        $this->add_actions();

        $this->set_downloadable(false);

        $this->set_default_no_results_notice(new lang_string('error_nosudoers', 'tool_musudo'));
    }

    #[\Override]
    protected function can_view(): bool {
        $context = \context_system::instance();
        if ($this->get_context()->id != $context->id) {
            return false;
        }
        return has_capability('moodle/site:config', $context);
    }

    /**
     * Adds the columns we want to display in the report.
     */
    public function add_columns(): void {
        $this->add_column_from_entity('user:fullnamewithpicturelink');

        // Include identity field columns.
        $identitycolumns = $this->userentity->get_identity_columns($this->get_context());
        foreach ($identitycolumns as $identitycolumn) {
            $this->add_column($identitycolumn);
        }

        if ($this->tenantentity) {
            $tenant = $this->tenantentity->get_column('name');
            $tenant->set_title(new lang_string('tenant', 'tool_mutenancy'));
            $this->add_column($tenant);
        }
        $this->add_column_from_entity('sudoer:note');

        if (\tool_musudo\local\mfa::is_mfa_enabled()) {
            $this->add_column_from_entity('sudoer:mfarequired');
        }

        $this->add_column_from_entity('sudoer:privileges');

        $this->set_initial_sort_column('user:fullnamewithpicturelink', SORT_ASC);
    }

    /**
     * Adds the filters we want to display in the report.
     */
    protected function add_filters(): void {
        $filters = [
            'user:fullname',
        ];
        $this->add_filters_from_entities($filters);

        if ($this->tenantentity) {
            $this->add_filter_from_entity('tenant:name');
            $this->add_filter_from_entity('tenant:idnumber');
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

        if (!is_siteadmin()) {
            return;
        }

        $url = new moodle_url('/admin/tool/musudo/management/sudoer_update.php', ['id' => ':id']);
        $link = new \tool_mulib\output\ajax_form\link($url, get_string('sudoer_update', 'tool_musudo'), 'i/settings');
        $this->add_action($link->create_report_action());

        $url = new moodle_url('/admin/tool/musudo/management/sudoer_delete.php', ['id' => ':id']);
        $link = new \tool_mulib\output\ajax_form\link($url, get_string('sudoer_delete', 'tool_musudo'), 'i/delete');
        $this->add_action($link->create_report_action(['class' => 'text-danger']));
    }
}
