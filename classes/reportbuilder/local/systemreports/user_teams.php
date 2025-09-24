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

use tool_murelation\reportbuilder\local\entities\supervisor;
use tool_murelation\reportbuilder\local\entities\framework;
use core_reportbuilder\local\entities\user;
use core_reportbuilder\system_report;
use core_reportbuilder\local\helpers\database;
use lang_string;
use moodle_url;

/**
 * Embedded supervised teams report.
 *
 * @package     tool_murelation
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class user_teams extends system_report {
    /** @var \stdClass */
    protected $user;
    /** @var framework */
    protected $frameworkentity;
    /** @var supervisor */
    protected $supervisorentity;
    /** @var user */
    protected $supuserentity;

    #[\Override]
    protected function initialise(): void {
        global $DB;

        $this->user = $DB->get_record('user', ['id' => $this->get_context()->instanceid, 'deleted' => 0, 'confirmed' => 1], '*', MUST_EXIST);

        $this->supervisorentity = new supervisor();
        $supervisoralias = $this->supervisorentity->get_table_alias('tool_murelation_supervisor');
        $this->add_entity($this->supervisorentity);
        $this->set_main_table('tool_murelation_supervisor', $supervisoralias);

        $this->frameworkentity = new framework();
        $frameworkalias = $this->frameworkentity->get_table_alias('tool_murelation_framework');
        $this->add_entity($this->frameworkentity);
        $this->add_join("JOIN {tool_murelation_framework} {$frameworkalias} ON {$frameworkalias}.id = {$supervisoralias}.frameworkid");

        $this->supuserentity = new user();
        $supuseralias = $this->supuserentity->get_table_alias('user');
        $this->add_entity($this->supuserentity);
        $this->add_join("LEFT JOIN {user} {$supuseralias} ON {$supuseralias}.id = {$supervisoralias}.userid");

        $this->add_base_fields("{$supervisoralias}.id, {$supervisoralias}.frameworkid, {$supervisoralias}.tenantid, "
            . "{$frameworkalias}.managecohortid");

        $param1 = database::generate_param_name();
        $param2 = database::generate_param_name();
        $this->add_base_condition_sql(
            "{$supervisoralias}.userid = :$param1 AND {$frameworkalias}.uimode = :$param2 AND {$frameworkalias}.visibility <> 0",
            [$param1 => $this->user->id, $param2 => \tool_murelation\local\framework::UIMODE_TEAMS]
        );

        $this->add_columns();
        $this->add_filters();
        $this->add_actions();

        $this->set_downloadable(false);

        $this->set_default_no_results_notice(new lang_string('error_nosupervisors', 'tool_murelation'));
    }

    #[\Override]
    protected function can_view(): bool {
        global $USER;
        if (\isguestuser() || !\isloggedin()) {
            return false;
        }
        $usercontext = $this->get_context();
        if ($usercontext->contextlevel != CONTEXT_USER) {
            return false;
        }
        if (isguestuser($usercontext->instanceid)) {
            return false;
        }
        if ($USER->id == $usercontext->instanceid) {
            return true;
        }
        $context = \context_system::instance();
        if (\tool_mulib\local\mulib::is_mutenancy_active() && $usercontext->tenantid) {
            $context = \context_tenant::instance($usercontext->tenantid);
        }

        return has_capability('tool/murelation:viewpositions', $context);
    }

    /**
     * Adds the columns we want to display in the report.
     */
    public function add_columns(): void {
        $column = $this->frameworkentity->get_column('name');
        $column->set_title(new lang_string('framework', 'tool_murelation'));
        $this->add_column($column);

        $column = $this->supervisorentity->get_column('teamname');
        $supervisoralias = $this->supervisorentity->get_table_alias('tool_murelation_supervisor');
        $column->add_field("{$supervisoralias}.frameworkid", 'frameworkid')
            ->add_field("{$supervisoralias}.tenantid", 'tenantid')
            ->add_callback(static function (?string $value, \stdClass $row): string {
                global $DB;
                if (!$row->id) {
                    return '';
                }
                $framework = $DB->get_record('tool_murelation_framework', ['id' => $row->frameworkid]);
                if ($framework) {
                    $context = \tool_murelation\local\uimode_teams::get_team_context($framework, $row);
                    if (has_capability('tool/murelation:viewpositions', $context)) {
                        $url = new moodle_url('/admin/tool/murelation/management/team.php', ['id' => $row->id]);
                        $value = \core\output\html_writer::link($url, $value);
                    }
                }
                return $value;
            });
        $this->add_column($column);

        $this->add_column_from_entity('supervisor:teamidnumber');
        $this->add_column_from_entity('framework:supervisortitle');
        $this->add_column_from_entity('supervisor:supmanaged');
        $this->add_column_from_entity('supervisor:subordinates');

        $this->set_initial_sort_column('supervisor:teamname', SORT_ASC);
    }

    /**
     * Adds the filters we want to display in the report.
     */
    protected function add_filters(): void {
        $filters = [
            'framework:name',
            'supervisor:teamname',
            'supervisor:teamidnumber',
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
    }
}
