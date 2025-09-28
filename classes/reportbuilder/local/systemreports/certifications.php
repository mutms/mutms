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

namespace tool_mucertify\reportbuilder\local\systemreports;

use tool_mucertify\reportbuilder\local\entities\certification;
use core_reportbuilder\system_report;
use core_reportbuilder\local\helpers\database;
use core_reportbuilder\local\report\filter;
use core_reportbuilder\local\filters\boolean_select;
use lang_string;

/**
 * Embedded certifications report.
 *
 * @package     tool_mucertify
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class certifications extends system_report {
    /** @var certification */
    protected $certificationentity;
    /** @var string */
    protected $certificationalias;

    #[\Override]
    protected function initialise(): void {
        $this->certificationentity = new certification();
        $this->certificationalias = $this->certificationentity->get_table_alias('tool_mucertify_certification');

        $this->set_main_table('tool_mucertify_certification', $this->certificationalias);
        $this->add_entity($this->certificationentity);

        $this->add_base_fields("{$this->certificationalias}.id, {$this->certificationalias}.archived");

        $contextalias = $this->certificationentity->get_table_alias('context');
        $this->add_join($this->certificationentity->get_context_join());

        // Make sure only certifications from context and its subcontexts are shown.
        $context = $this->get_context();
        $paramlike = database::generate_param_name();
        $this->add_base_condition_sql("({$contextalias}.id = {$context->id} OR {$contextalias}.path LIKE :$paramlike)", [$paramlike => $context->path . '/%']);

        $this->add_columns();
        $this->add_filters();

        $this->set_downloadable(true);
        $this->set_default_no_results_notice(new lang_string('errornocertifications', 'tool_mucertify'));
    }

    #[\Override]
    protected function can_view(): bool {
        return has_capability('tool/mucertify:view', $this->get_context());
    }

    /**
     * Adds the columns we want to display in the report.
     */
    public function add_columns(): void {
        $columns = [
            'certification:fullname',
            'certification:idnumber',
            'certification:context',
            'certification:assignmentcount',
            'certification:publicaccess',
            'certification:archived',
        ];
        $this->add_columns_from_entities($columns);

        $this->set_initial_sort_column('certification:fullname', SORT_ASC);
    }

    /**
     * Adds the filters we want to display in the report.
     */
    protected function add_filters(): void {
        $filters = [
            'certification:fullname',
            'certification:idnumber',
            'certification:publicaccess',
            'certification:archived',
        ];
        $this->add_filters_from_entities($filters);
        $context = $this->get_context();

        $filter = new filter(
            boolean_select::class,
            'currentcontextonly',
            new lang_string('currentcontextonly', 'tool_mucertify'),
            $this->certificationentity->get_entity_name(),
            "CASE WHEN {$this->certificationalias}.contextid = {$context->id} THEN 1 ELSE 0 END"
        );
        $this->add_filter($filter);
    }

    /**
     * Row class
     *
     * @param \stdClass $row
     * @return string
     */
    public function get_row_class(\stdClass $row): string {
        return $row->archived ? 'text-muted' : '';
    }
}
