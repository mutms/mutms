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
 * Embedded supervisor subordinates report.
 *
 * @package     tool_murelation
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class team_members extends system_report {
    /** @var \stdClass */
    protected $supervisor;
    /** @var \stdClass */
    protected $framework;
    /** @var framework */
    protected $frameworkentity;
    /** @var supervisor */
    protected $supervisorentity;
    /** @var subordinate */
    protected $subordinateentity;
    /** @var user */
    protected $userentity;

    #[\Override]
    protected function initialise(): void {
        global $DB, $PAGE;

        // RB does not set PAGE->context properly in ajax requests, oh well...
        if (defined('AJAX_SCRIPT') && AJAX_SCRIPT) {
            $PAGE->set_context($this->get_context());
        }

        $this->supervisor = $DB->get_record(
            'tool_murelation_supervisor',
            ['id' => $this->get_parameters()['supervisorid']],
            '*',
            MUST_EXIST
        );
        $this->framework = $DB->get_record(
            'tool_murelation_framework',
            ['id' => $this->supervisor->frameworkid],
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

        $this->userentity = new user();
        $useralias = $this->userentity->get_table_alias('user');
        $this->add_entity($this->userentity);
        $this->add_join("LEFT JOIN {user} {$useralias} ON {$useralias}.id = {$subordinatealias}.userid");

        $this->add_base_fields("{$subordinatealias}.id, {$supervisoralias}.tenantid");

        $param = database::generate_param_name();
        $this->add_base_condition_sql("{$supervisoralias}.id = :$param", [$param => $this->supervisor->id]);

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
        return has_capability('tool/murelation:viewpositions', $context);
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

        $this->add_column_from_entity('subordinate:teamposition');

        $this->set_initial_sort_column('user:fullnamewithpicturelink', SORT_ASC);
    }

    /**
     * Adds the filters we want to display in the report.
     */
    protected function add_filters(): void {
        $filters = [
            'user:fullname',
            'subordinate:teamposition',
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
        $supervisor = $this->supervisor;
        $subordinatetitle = format_string($framework->subordinatetitle);

        $url = new moodle_url('/admin/tool/murelation/management/member_update.php', ['id' => ':id']);
        $link = new \tool_mulib\output\ajax_form\link($url, get_string('member_update_a', 'tool_murelation', $subordinatetitle), 'i/delete');
        $this->add_action($link->create_report_action()
            ->add_callback(static function (\stdclass $row) use ($framework, $supervisor): bool {
                if (!$row->id) {
                    return false;
                }

                return \tool_murelation\local\uimode_teams::can_manage_members($framework, $supervisor);
            }));

        $url = new moodle_url('/admin/tool/murelation/management/member_delete.php', ['id' => ':id']);
        $link = new \tool_mulib\output\ajax_form\link($url, get_string('member_delete_a', 'tool_murelation', $subordinatetitle), 'i/delete');
        $this->add_action($link->create_report_action(['class' => 'text-danger'])
            ->add_callback(static function (\stdclass $row) use ($framework, $supervisor): bool {
                if (!$row->id) {
                    return false;
                }

                return \tool_murelation\local\uimode_teams::can_manage_members($framework, $supervisor);
            }));
    }
}
