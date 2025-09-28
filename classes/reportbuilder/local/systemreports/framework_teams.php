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
use tool_mutenancy\reportbuilder\local\entities\tenant;
use core_reportbuilder\local\entities\user;
use core_reportbuilder\system_report;
use core_reportbuilder\local\helpers\database;
use lang_string;
use moodle_url;

/**
 * Embedded framework supervisors report for Teams mode only.
 *
 * @package     tool_murelation
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class framework_teams extends system_report {
    /** @var \stdClass */
    protected $framework;
    /** @var framework */
    protected $frameworkentity;
    /** @var supervisor */
    protected $supervisorentity;
    /** @var user */
    protected $userentity;
    /** @var tenant */
    protected $tenantentity;

    #[\Override]
    protected function initialise(): void {
        global $DB, $PAGE;

        // RB does not set PAGE->context properly in ajax requests, oh well...
        if (defined('AJAX_SCRIPT') && AJAX_SCRIPT) {
            $PAGE->set_context($this->get_context());
        }

        $this->framework = $DB->get_record(
            'tool_murelation_framework',
            ['id' => $this->get_parameters()['frameworkid'], 'uimode' => \tool_murelation\local\framework::UIMODE_TEAMS],
            '*',
            MUST_EXIST
        );
        $supervisorstitle = format_string($this->framework->supervisorstitle);

        $this->supervisorentity = new supervisor();
        $this->add_entity($this->supervisorentity);
        $supervisoralias = $this->supervisorentity->get_table_alias('tool_murelation_supervisor');
        $this->set_main_table('tool_murelation_supervisor', $supervisoralias);

        $this->frameworkentity = new framework();
        $frameworkalias = $this->frameworkentity->get_table_alias('tool_murelation_framework');
        $this->add_entity($this->frameworkentity);
        $this->add_join("JOIN {tool_murelation_framework} {$frameworkalias} ON {$frameworkalias}.id = {$supervisoralias}.frameworkid");

        $this->userentity = new user();
        $useralias = $this->userentity->get_table_alias('user');
        $this->add_entity($this->userentity);
        $this->add_join("LEFT JOIN {user} {$useralias} ON {$useralias}.id = {$supervisoralias}.userid");

        if (\tool_mulib\local\mulib::is_mutenancy_active()) {
            $this->tenantentity = new tenant();
            $tenantalias = $this->tenantentity->get_table_alias('tool_mutenancy_tenant');
            $this->tenantentity
                ->add_joins($this->tenantentity->get_joins())
                ->add_join("LEFT JOIN {tool_mutenancy_tenant} {$tenantalias} ON {$tenantalias}.id = {$supervisoralias}.tenantid");
            $this->add_entity($this->tenantentity);
        }

        $this->add_base_fields("{$supervisoralias}.id, {$supervisoralias}.frameworkid, {$supervisoralias}.tenantid");

        $param = database::generate_param_name();
        $this->add_base_condition_sql("{$frameworkalias}.id = :$param", [$param => $this->framework->id]);

        $this->add_columns();
        $this->add_filters();
        $this->add_actions();

        $this->set_downloadable(false);

        $this->set_default_no_results_notice(new lang_string('error_noteams', 'tool_murelation', $supervisorstitle));
    }

    #[\Override]
    protected function can_view(): bool {
        if (\isguestuser() || !\isloggedin()) {
            return false;
        }
        $context = \context_system::instance();
        if ($this->get_context()->id != $context->id) {
            return false;
        }
        return has_capability('tool/murelation:viewframeworks', $context);
    }

    /**
     * Adds the columns we want to display in the report.
     */
    public function add_columns(): void {
        $framework = $this->framework;
        $teamname = $this->supervisorentity->get_column('teamname');
        $supervisoralias = $this->supervisorentity->get_table_alias('tool_murelation_supervisor');
        $teamname->add_field('frameworkid')
            ->add_field("{$supervisoralias}.tenantid", 'tenantid')
            ->add_callback(static function (?string $value, \stdClass $row) use ($framework): string {
                if (!$row->id) {
                    return '';
                }
                    $context = \tool_murelation\local\uimode_teams::get_team_context($framework, $row);
                if (has_capability('tool/murelation:viewpositions', $context)) {
                    $url = new \moodle_url('/admin/tool/murelation/management/team.php', ['id' => $row->id]);
                    $value = \core\output\html_writer::link($url, $value);
                }

                    return $value;
            });
        $this->add_column($teamname);

        $this->add_column_from_entity('supervisor:teamidnumber');

        $supervisor = $this->userentity->get_column('fullnamewithlink');
        $supervisor->set_title(
            new lang_string('lang_string_a', 'tool_mulib', format_string($this->framework->supervisortitle))
        );
        $this->add_column($supervisor);

        $this->add_column_from_entity('supervisor:supmanaged');

        if ($this->tenantentity) {
            $tenant = $this->tenantentity->get_column('name');
            $tenant->set_title(new lang_string('tenant', 'tool_mutenancy'));
            $this->add_column($tenant);
        }

        $column = $this->supervisorentity->get_column('subordinates');
        $column->set_title(
            new lang_string('lang_string_a', 'tool_mulib', format_string($this->framework->subordinatestitle))
        );
        $this->add_column($column);

        $this->set_initial_sort_column('supervisor:teamname', SORT_ASC);
    }

    /**
     * Adds the filters we want to display in the report.
     */
    protected function add_filters(): void {
        $filters = [
            'supervisor:teamname',
            'supervisor:teamidnumber',
        ];
        $this->add_filters_from_entities($filters);

        $supervisor = $this->userentity->get_filter('fullname');
        $supervisor->set_header(
            new lang_string('lang_string_a', 'tool_mulib', format_string($this->framework->supervisortitle))
        );
        $this->add_filter($supervisor);

        $filters = [
            'supervisor:vacant',
            'supervisor:supmanaged',
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

        $framework = $this->framework;
        $supervisor = null;

        $url = new moodle_url('/admin/tool/murelation/management/team_update.php', ['id' => ':id']);
        $link = new \tool_mulib\output\ajax_form\link($url, get_string('team_update', 'tool_murelation'), 'i/settings');
        $this->add_action($link->create_report_action()
            ->add_callback(static function (\stdclass $row) use ($framework, $supervisor): bool {
                global $DB;

                if (!$row->id) {
                    return false;
                }
                if (!$supervisor || $supervisor->id != $row->id) {
                    $supervisor = $DB->get_record('tool_murelation_supervisor', ['id' => $row->id]);
                    if (!$supervisor) {
                        return false;
                    }
                }
                return \tool_murelation\local\uimode_teams::can_update_team($framework, $supervisor);
            }));

        $url = new moodle_url('/admin/tool/murelation/management/team_delete.php', ['id' => ':id']);
        $link = new \tool_mulib\output\ajax_form\link($url, get_string('team_delete', 'tool_murelation'), 'i/delete');
        $this->add_action($link->create_report_action(['class' => 'text-danger'])
            ->add_callback(static function (\stdclass $row) use ($framework, $supervisor): bool {
                global $DB;

                if (!$row->id) {
                    return false;
                }
                if (!$supervisor || $supervisor->id != $row->id) {
                    $supervisor = $DB->get_record('tool_murelation_supervisor', ['id' => $row->id]);
                    if (!$supervisor) {
                        return false;
                    }
                }
                return \tool_murelation\local\uimode_teams::can_update_team($framework, $supervisor);
            }));
    }
}
