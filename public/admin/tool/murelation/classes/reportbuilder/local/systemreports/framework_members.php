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
 * Embedded framework teams members report for Teams mode only.
 *
 * @package     tool_murelation
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class framework_members extends system_report {
    /** @var \stdClass */
    protected $framework;
    /** @var framework */
    protected $frameworkentity;
    /** @var supervisor */
    protected $supervisorentity;
    /** @var subordinate */
    protected $subordinateentity;
    /** @var user */
    protected $subuserentity;
    /** @var user */
    protected $supuserentity;

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

        $subordinatestitle = format_string($this->framework->subordinatestitle);

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
        $this->subuserentity->set_entity_name('user');
        $subuseralias = $this->subuserentity->get_table_alias('user');
        $this->add_entity($this->subuserentity);
        $this->add_join("LEFT JOIN {user} {$subuseralias} ON {$subuseralias}.id = {$subordinatealias}.userid");

        $this->supuserentity = new user();
        $this->supuserentity->set_entity_name('supuser');
        $supuseralias = $this->supuserentity->get_table_alias('user');
        $this->add_entity($this->supuserentity);
        $this->add_join("LEFT JOIN {user} {$supuseralias} ON {$supuseralias}.id = {$supervisoralias}.userid");

        $this->add_base_fields("{$subordinatealias}.id, {$supervisoralias}.id AS supid");

        $param = database::generate_param_name();
        $this->add_base_condition_sql("{$frameworkalias}.id = :$param", [$param => $this->framework->id]);

        $this->add_columns();
        $this->add_filters();
        $this->add_actions();

        $this->set_downloadable(false);

        $this->set_default_no_results_notice(new lang_string('error_nosubordinates_a', 'tool_murelation', $subordinatestitle));
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
        $supervisoralias = $this->supervisorentity->get_table_alias('tool_murelation_supervisor');

        $this->add_column_from_entity('user:fullnamewithpicturelink');

        // Include identity field columns.
        $identitycolumns = $this->subuserentity->get_identity_columns($this->get_context());
        foreach ($identitycolumns as $identitycolumn) {
            $this->add_column($identitycolumn);
        }

        $this->add_column_from_entity('subordinate:teamposition');

        $column = $this->supuserentity->get_column('fullnamewithlink');
        $column->set_title(new lang_string('lang_string_a', 'tool_mulib', format_string($this->framework->supervisortitle)));
        $this->add_column($column);

        $column = $this->supervisorentity->get_column('teamname');
        $column->add_field("{$supervisoralias}.id", 'supid');
        $column->add_callback(static function (?string $value, \stdClass $row) use ($framework): string {
            global $DB;

            if ($value === null || !$row->supid) {
                return $value;
            }

            $supervisor = $DB->get_record('tool_murelation_supervisor', ['id' => $row->id]);
            if (!$supervisor) {
                return $value;
            }

            $context = \tool_murelation\local\uimode_teams::get_team_context($framework, $supervisor);
            if (!has_capability('tool/murelation:viewpositions', $context)) {
                return $value;
            }
            $url = new moodle_url('/admin/tool/murelation/management/team.php', ['id' => $supervisor->id]);
            return \html_writer::link($url, $value);
        });

        $this->add_column_from_entity('supervisor:teamname');
        $this->add_column_from_entity('supervisor:teamidnumber');

        $this->set_initial_sort_column('user:fullnamewithpicturelink', SORT_ASC);
    }

    /**
     * Adds the filters we want to display in the report.
     */
    protected function add_filters(): void {
        $filters = [
            'user:fullname',
            'subordinate:teamposition',
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

        $framework = $this->framework;
        $supervisor = null;
        $subordinatetitle = format_string($framework->subordinatetitle);

        $url = new moodle_url('/admin/tool/murelation/management/member_delete.php', ['id' => ':id']);
        $link = new \tool_mulib\output\ajax_form\link($url, get_string('member_delete_a', 'tool_murelation', $subordinatetitle), 'i/delete');
        $this->add_action($link->create_report_action(['class' => 'text-danger'])
            ->add_callback(static function (\stdclass $row) use ($framework, $supervisor): bool {
                global $DB;

                if (!$row->id) {
                    return false;
                }

                if (!$supervisor || $supervisor->id != $row->supid) {
                    $supervisor = $DB->get_record('tool_murelation_supervisor', ['id' => $row->id]);
                    if (!$supervisor) {
                        return false;
                    }
                }

                return \tool_murelation\local\uimode_teams::can_manage_members($framework, $supervisor);
            }));
    }
}
