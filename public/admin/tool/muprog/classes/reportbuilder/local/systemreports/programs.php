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
use core_reportbuilder\system_report;
use core_reportbuilder\local\helpers\database;
use core_reportbuilder\local\report\filter;
use core_reportbuilder\local\filters\boolean_select;
use lang_string;

/**
 * Embedded programs report.
 *
 * @package     tool_muprog
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class programs extends system_report {
    /** @var program */
    protected $programentity;
    /** @var string */
    protected $programalias;

    #[\Override]
    protected function initialise(): void {
        $this->programentity = new program();
        $this->programalias = $this->programentity->get_table_alias('tool_muprog_program');

        $this->set_main_table('tool_muprog_program', $this->programalias);
        $this->add_entity($this->programentity);

        $this->add_base_fields("{$this->programalias}.id, {$this->programalias}.archived");

        $contextalias = $this->programentity->get_table_alias('context');
        $this->add_join($this->programentity->get_context_join());

        // Make sure only programs from context and its subcontexts are shown.
        $context = $this->get_context();
        $paramlike = database::generate_param_name();
        $this->add_base_condition_sql("({$contextalias}.id = {$context->id} OR {$contextalias}.path LIKE :$paramlike)", [$paramlike => $context->path . '/%']);

        $this->add_columns();
        $this->add_filters();

        $this->set_downloadable(true);
        $this->set_default_no_results_notice(new lang_string('errornoprograms', 'tool_muprog'));
    }

    #[\Override]
    protected function can_view(): bool {
        return has_capability('tool/muprog:view', $this->get_context());
    }

    /**
     * Adds the columns we want to display in the report.
     */
    public function add_columns(): void {
        $columns = [
            'program:fullname',
            'program:idnumber',
            'program:context',
            'program:coursecount',
            'program:allocationcount',
            'program:publicaccess',
            'program:archived',
        ];
        $this->add_columns_from_entities($columns);

        $this->set_initial_sort_column('program:fullname', SORT_ASC);
    }

    /**
     * Adds the filters we want to display in the report.
     */
    protected function add_filters(): void {
        $filters = [
            'program:fullname',
            'program:idnumber',
            'program:publicaccess',
            'program:archived',
        ];
        $this->add_filters_from_entities($filters);
        $context = $this->get_context();

        $filter = new filter(
            boolean_select::class,
            'currentcontextonly',
            new lang_string('currentcontextonly', 'tool_muprog'),
            $this->programentity->get_entity_name(),
            "CASE WHEN {$this->programalias}.contextid = {$context->id} THEN 1 ELSE 0 END"
        );
        $this->add_filter($filter);
    }

    /**
     * Row class.
     *
     * @param \stdClass $row
     * @return string
     */
    public function get_row_class(\stdClass $row): string {
        return $row->archived ? 'text-muted' : '';
    }
}
