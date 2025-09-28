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

namespace tool_muprog\reportbuilder\local\systemreports;

use tool_muprog\reportbuilder\local\entities\program;
use tool_muprog\reportbuilder\local\entities\allocation;
use tool_muprog\reportbuilder\local\entities\source;
use core_reportbuilder\local\entities\user;
use core_reportbuilder\system_report;
use core_reportbuilder\local\helpers\database;
use core_reportbuilder\local\helpers\user_profile_fields;
use lang_string;
use moodle_url;

/**
 * Embedded My programs report.
 *
 * @package     tool_muprog
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class my_allocations extends system_report {
    /** @var user */
    protected $userentity;
    /** @var allocation */
    protected $allocationentity;
    /** @var source */
    protected $sourceentity;
    /** @var program */
    protected $programentity;

    #[\Override]
    protected function initialise(): void {
        global $USER;

        $this->allocationentity = new allocation();
        $allocationalias = $this->allocationentity->get_table_alias('tool_muprog_allocation');
        $this->set_main_table('tool_muprog_allocation', $allocationalias);
        $this->add_entity($this->allocationentity);

        $this->sourceentity = new source();
        $sourcealias = $this->sourceentity->get_table_alias('tool_muprog_source');
        $this->add_entity($this->sourceentity);
        $this->add_join("JOIN {tool_muprog_source} {$sourcealias} ON {$sourcealias}.id = {$allocationalias}.sourceid");

        $this->programentity = new program();
        $programalias = $this->programentity->get_table_alias('tool_muprog_program');
        $this->add_entity($this->programentity);
        $this->add_join("JOIN {tool_muprog_program} {$programalias} ON {$programalias}.id = {$allocationalias}.programid");

        $param = database::generate_param_name();
        $this->add_base_condition_sql(
            "{$allocationalias}.userid = :$param AND {$allocationalias}.archived = 0 AND {$programalias}.archived = 0",
            [$param => $USER->id]
        );

        $this->add_columns();
        $this->add_filters();

        $this->set_downloadable(false);
        $this->set_default_no_results_notice(new lang_string('errornomyprograms', 'tool_muprog'));
    }

    #[\Override]
    protected function can_view(): bool {
        global $USER;

        // Everybody may view own programs.
        if (!\tool_muprog\local\util::is_muprog_active()) {
            return false;
        }
        if (isguestuser() || !isloggedin()) {
            return false;
        }
        $usercontext = $this->get_context();
        if ($usercontext->contextlevel != CONTEXT_USER || $usercontext->instanceid != $USER->id) {
            return false;
        }
        return true;
    }

    /**
     * Adds the columns we want to display in the report.
     */
    public function add_columns(): void {
        $allocationalias = $this->allocationentity->get_table_alias('tool_muprog_allocation');
        $programalias = $this->programentity->get_table_alias('tool_muprog_program');
        $sourcealias = $this->sourceentity->get_table_alias('tool_muprog_source');

        $column = $this->programentity->get_column('fullname')
            ->add_field("{$programalias}.id")
            ->add_callback(static function ($value, \stdClass $row): string {
                if (!$value) {
                    return '';
                }
                $value = format_string($value);
                $url = new \moodle_url('/admin/tool/muprog/my/program.php', ['id' => $row->id]);
                return \html_writer::link($url, $value);
            });
        $this->add_column($column);

        $this->add_column_from_entity('program:idnumber');
        $this->add_column_from_entity('allocation:timestart');
        $this->add_column_from_entity('allocation:timedue');
        $this->add_column_from_entity('allocation:timeend');

        $column = $this->sourceentity->get_column('type')
            ->add_fields("{$allocationalias}.id, {$sourcealias}.type, {$allocationalias}.programid")
            ->add_callback(static function ($value, \stdClass $row): string {
                global $DB;
                if (!$row->type) {
                    return '';
                }
                $sourceclass = \tool_muprog\local\allocation::get_source_classname($row->type);
                if (!$sourceclass) {
                    return '';
                }
                $allocation = $DB->get_record('tool_muprog_allocation', ['id' => $row->id]);
                $program = $DB->get_record('tool_muprog_program', ['id' => $row->programid]);
                $source = $DB->get_record('tool_muprog_source', ['type' => $row->type, 'programid' => $program->id]);

                return $sourceclass::render_allocation_source($program, $source, $allocation);
            });
        $this->add_column($column);

        $this->add_column_from_entity('allocation:status');

        $this->set_initial_sort_column('program:fullname', SORT_ASC);
    }

    /**
     * Adds the filters we want to display in the report.
     */
    protected function add_filters(): void {
        $this->add_filter_from_entity('allocation:status');
    }
}
