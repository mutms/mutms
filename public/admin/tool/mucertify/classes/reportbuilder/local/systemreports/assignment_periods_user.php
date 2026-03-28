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
// phpcs:disable moodle.Files.LineLength.MaxExceeded

namespace tool_mucertify\reportbuilder\local\systemreports;

use tool_mucertify\reportbuilder\local\entities\period;
use tool_mucertify\reportbuilder\local\entities\certification;
use tool_mucertify\reportbuilder\local\entities\assignment;
use tool_muprog\reportbuilder\local\entities\program;
use tool_muprog\reportbuilder\local\entities\allocation;
use core_reportbuilder\system_report;
use core_reportbuilder\local\helpers\database;
use lang_string;

/**
 * Embedded User certification periods report.
 *
 * @package     tool_mucertify
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class assignment_periods_user extends system_report {
    /** @var \stdClass */
    protected $certification;
    /** @var \stdClass */
    protected $assignment;

    /** @var period */
    protected $periodentity;
    /** @var assignment */
    protected $assignmententity;
    /** @var program */
    protected $allocationentity;
    /** @var program */
    protected $programentity;

    #[\Override]
    protected function initialise(): void {
        global $DB;

        $usercontext = $this->get_context();

        $this->assignment = $DB->get_record(
            'tool_mucertify_assignment',
            ['id' => $this->get_parameters()['assignmentid'], 'userid' => $usercontext->instanceid, 'archived' => 0],
            '*',
            MUST_EXIST
        );
        $this->certification = $DB->get_record(
            'tool_mucertify_certification',
            ['id' => $this->assignment->certificationid, 'archived' => 0],
            '*',
            MUST_EXIST
        );

        $this->periodentity = new period();
        $periodentityalias = $this->periodentity->get_table_alias('tool_mucertify_period');
        $this->set_main_table('tool_mucertify_period', $periodentityalias);
        $this->add_entity($this->periodentity);

        $this->assignmententity = new assignment();
        $assignmentalias = $this->assignmententity->get_table_alias('tool_mucertify_assignment');
        $this->add_entity($this->assignmententity);
        $this->add_join("JOIN {tool_mucertify_assignment} {$assignmentalias} ON
            {$assignmentalias}.userid = {$periodentityalias}.userid AND {$assignmentalias}.certificationid = {$periodentityalias}.certificationid");

        $this->allocationentity = new allocation();
        $allocationalias = $this->allocationentity->get_table_alias('tool_muprog_allocation');
        $this->add_entity($this->allocationentity);
        $this->add_join("LEFT JOIN {tool_muprog_allocation} {$allocationalias} ON {$allocationalias}.programid = {$periodentityalias}.programid AND {$allocationalias}.userid = {$periodentityalias}.userid");

        // Link program to allocation instad of period, we want only assigned programs here.
        $this->programentity = new program();
        $programalias = $this->programentity->get_table_alias('tool_muprog_program');
        $this->add_entity($this->programentity);
        $this->add_join("LEFT JOIN {tool_muprog_program} {$programalias} ON {$programalias}.id = {$allocationalias}.programid");

        $certificationentity = new certification();
        $certificationalias = $certificationentity->get_table_alias('tool_mucertify_certification');
        $this->add_entity($certificationentity);
        $this->add_join("JOIN {tool_mucertify_certification} {$certificationalias} ON {$certificationalias}.id = {$assignmentalias}.certificationid");

        $param = database::generate_param_name();
        $this->add_base_condition_sql("{$assignmentalias}.id = :$param", [$param => $this->assignment->id]);

        $this->add_base_fields("{$periodentityalias}.id, {$periodentityalias}.timerevoked");

        $this->add_columns();
        $this->add_filters();

        $this->set_downloadable(false);
        $this->set_default_no_results_notice(new lang_string('nothingtodisplay'));
    }

    #[\Override]
    protected function can_view(): bool {
        global $USER;
        if (isguestuser() || !isloggedin()) {
            return false;
        }
        if (!\tool_mulib\local\mulib::is_mucertify_active()) {
            return false;
        }

        $usercontext = $this->get_context();
        if (!$usercontext instanceof \context_user) {
            return false;
        }

        if ($usercontext->instanceid == $USER->id) {
            // Everybody may see own certifications.
            return true;
        }

        return has_capability('tool/mucertify:viewusercertifications', $usercontext);
    }

    /**
     * Adds the columns we want to display in the report.
     */
    public function add_columns(): void {
        $periodalias = $this->periodentity->get_table_alias('tool_mucertify_period');
        $allocationalias = $this->allocationentity->get_table_alias('tool_muprog_allocation');
        $programalias = $this->programentity->get_table_alias('tool_muprog_program');

        $this->add_column_from_entity('period:timewindowstart');
        $this->add_column_from_entity('period:timewindowdue');
        $this->add_column_from_entity('period:timewindowend');

        $column = $this->programentity->get_column('fullname')
            ->set_title(new lang_string('program', 'tool_muprog'))
            ->add_field("{$programalias}.fullname")
            ->add_field("{$allocationalias}.id", 'allocationid')
            ->add_field("{$programalias}.id", 'programid')
            ->set_callback(static function (?string $value, \stdClass $row): string {
                if (!$row->allocationid) {
                    return '';
                }
                $fullname = format_string($row->fullname);
                $url = new \core\url('/admin/tool/muprog/my/program.php', ['id' => $row->programid]);
                return \html_writer::link($url, $fullname);
            });
        $this->add_column($column);

        $this->add_column_from_entity('period:timefrom');
        $this->add_column_from_entity('period:timeuntil');

        if ($this->certification->recertify) {
            $this->add_column_from_entity('period:recertify');
        }

        $this->add_column_from_entity('period:status');

        $this->set_initial_sort_column('period:timewindowstart', SORT_ASC);
    }

    /**
     * Adds the filters we want to display in the report.
     */
    protected function add_filters(): void {
    }
}
