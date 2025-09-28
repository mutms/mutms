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

namespace tool_murelation\reportbuilder\local\systemreports;

use tool_murelation\reportbuilder\local\entities\subordinate;
use tool_murelation\reportbuilder\local\entities\supervisor;
use tool_murelation\reportbuilder\local\entities\framework;
use core_reportbuilder\local\entities\user;
use core_reportbuilder\system_report;
use core_reportbuilder\local\helpers\database;
use lang_string;
use moodle_url;

/**
 * Embedded user subordinates report for Supervisors mode only.
 *
 * @package     tool_murelation
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class user_subordinates extends system_report {
    /** @var \stdClass */
    protected $user;
    /** @var framework */
    protected $frameworkentity;
    /** @var supervisor */
    protected $supervisorentity;
    /** @var subordinate */
    protected $subordinateentity;
    /** @var user */
    protected $subuserentity;

    #[\Override]
    protected function initialise(): void {
        global $DB;

        $this->user = $DB->get_record('user', ['id' => $this->get_context()->instanceid, 'deleted' => 0, 'confirmed' => 1], '*', MUST_EXIST);

        $this->subordinateentity = new subordinate();
        $subordinatealias = $this->subordinateentity->get_table_alias('tool_murelation_subordinate');
        $this->add_entity($this->subordinateentity);
        $this->set_main_table('tool_murelation_subordinate', $subordinatealias);

        $this->supervisorentity = new supervisor();
        $supervisoralias = $this->supervisorentity->get_table_alias('tool_murelation_supervisor');
        $this->add_entity($this->supervisorentity);
        $this->add_join("JOIN {tool_murelation_supervisor} {$supervisoralias} ON {$supervisoralias}.id = {$subordinatealias}.supervisorid");

        $this->frameworkentity = new framework();
        $frameworkalias = $this->frameworkentity->get_table_alias('tool_murelation_framework');
        $this->add_entity($this->frameworkentity);
        $this->add_join("JOIN {tool_murelation_framework} {$frameworkalias} ON {$frameworkalias}.id = {$supervisoralias}.frameworkid");

        $this->subuserentity = new user();
        $subuseralias = $this->subuserentity->get_table_alias('user');
        $this->add_entity($this->subuserentity);
        $this->add_join("JOIN {user} {$subuseralias} ON {$subuseralias}.id = {$subordinatealias}.userid");

        $this->add_base_fields("{$subordinatealias}.id, {$subordinatealias}.frameworkid, {$supervisoralias}.tenantid, "
            . "{$frameworkalias}.managecohortid, {$supervisoralias}.id AS supervisorid, {$subordinatealias}.userid");

        $param1 = database::generate_param_name();
        $param2 = database::generate_param_name();
        $this->add_base_condition_sql(
            "{$supervisoralias}.userid = :$param1 AND {$frameworkalias}.uimode = :$param2",
            [$param1 => $this->user->id, $param2 => \tool_murelation\local\framework::UIMODE_SUPERVISORS]
        );

        $this->add_columns();
        $this->add_filters();
        $this->add_actions();

        $this->set_downloadable(false);

        $this->set_default_no_results_notice(new lang_string('error_nosubordinates', 'tool_murelation'));
    }

    #[\Override]
    protected function can_view(): bool {
        if (\isguestuser() || !\isloggedin()) {
            return false;
        }
        $context = $this->get_context();
        if ($context->contextlevel != CONTEXT_USER) {
            return false;
        }
        if (isguestuser($context->instanceid)) {
            return false;
        }
        return has_capability('tool/murelation:viewpositions', $context);
    }

    /**
     * Adds the columns we want to display in the report.
     */
    public function add_columns(): void {
        $this->add_column_from_entity('user:fullnamewithpicturelink');
        $column = $this->frameworkentity->get_column('name');
        $column->set_title(new lang_string('framework', 'tool_murelation'));
        $this->add_column($column);
        $this->add_column_from_entity('framework:subordinatetitle');

        $this->set_initial_sort_column('user:fullnamewithpicturelink', SORT_ASC);
    }

    /**
     * Adds the filters we want to display in the report.
     */
    protected function add_filters(): void {
        $filters = [
            'framework:name',
            'framework:idnumber',
        ];
        $this->add_filters_from_entities($filters);
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

        $url = new moodle_url('/admin/tool/murelation/management/supervisor_edit.php', ['subuserid' => ':userid', 'frameworkid' => ':frameworkid']);
        $link = new \tool_mulib\output\ajax_form\link($url, get_string('supervisor_update', 'tool_murelation'), 'i/settings');
        $this->add_action($link->create_report_action()
            ->add_callback(static function (\stdclass $row): bool {
                global $DB, $USER;

                if (!$row->id) {
                    return false;
                }

                $managecontext = \context_user::instance($row->userid);
                if (!has_capability('tool/murelation:managepositions', $managecontext)) {
                    return false;
                }

                if (
                    !is_siteadmin() && $row->managecohortid
                    && !$DB->record_exists('cohort_members', ['userid' => $USER->id, 'cohortid' => $row->managecohortid])
                ) {
                    return false;
                }

                return true;
            }));

        $url = new moodle_url('/admin/tool/murelation/management/supervisor_delete.php', ['subuserid' => ':userid', 'frameworkid' => ':frameworkid']);
        $link = new \tool_mulib\output\ajax_form\link($url, get_string('supervisor_delete', 'tool_murelation'), 'i/delete');
        $this->add_action($link->create_report_action(['class' => 'text-danger'])
            ->add_callback(static function (\stdclass $row): bool {
                global $DB, $USER;

                if (!$row->id) {
                    return false;
                }

                $managecontext = \context_user::instance($row->userid);
                if (!has_capability('tool/murelation:managepositions', $managecontext)) {
                    return false;
                }

                if (
                    !is_siteadmin() && $row->managecohortid
                    && !$DB->record_exists('cohort_members', ['userid' => $USER->id, 'cohortid' => $row->managecohortid])
                ) {
                    return false;
                }

                return true;
            }));
    }
}
