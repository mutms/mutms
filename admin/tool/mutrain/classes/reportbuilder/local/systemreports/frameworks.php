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

namespace tool_mutrain\reportbuilder\local\systemreports;

use tool_mutrain\reportbuilder\local\entities\framework;
use core_reportbuilder\system_report;
use core_reportbuilder\local\report\filter;
use core_reportbuilder\local\filters\boolean_select;
use lang_string;

/**
 * Embedded credit frameworks report.
 *
 * @package     tool_mutrain
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class frameworks extends system_report {
    /** @var framework */
    protected $frameworkentity;
    /** @var string */
    protected $frameworkalias;

    #[\Override]
    protected function initialise(): void {
        $this->frameworkentity = new framework();
        $this->frameworkalias = $this->frameworkentity->get_table_alias('tool_mutrain_framework');

        $this->set_main_table('tool_mutrain_framework', $this->frameworkalias);
        $this->add_entity($this->frameworkentity);

        $this->add_base_fields("{$this->frameworkalias}.id, {$this->frameworkalias}.archived");

        $this->add_join($this->frameworkentity->get_context_join());

        // Make sure only frameworks from context and its subcontexts are shown.
        $context = $this->get_context();
        if ($context->contextlevel != CONTEXT_SYSTEM) {
            $this->add_join($this->frameworkentity->get_context_map_join($context));
        }

        $this->add_columns();
        $this->add_filters();

        $this->set_initial_sort_column('framework:namewithlink', SORT_ASC);
        $this->set_downloadable(true);
        $this->set_default_no_results_notice(new lang_string('error_noframeworks', 'tool_mutrain'));
    }

    #[\Override]
    protected function can_view(): bool {
        if (isguestuser() || !isloggedin()) {
            return false;
        }
        return has_capability('tool/mutrain:viewframeworks', $this->get_context());
    }

    /**
     * Adds the columns we want to display in the report.
     */
    public function add_columns(): void {
        $columns = [
            'framework:namewithlink',
            'framework:idnumber',
            'framework:context',
            'framework:publicaccess',
            'framework:fieldcount',
            'framework:requiredcredits',
            'framework:restrictcontext',
            'framework:restrictafter',
            'framework:archived',
        ];
        $this->add_columns_from_entities($columns);
    }

    /**
     * Adds the filters we want to display in the report.
     */
    protected function add_filters(): void {
        $filters = [
            'framework:name',
            'framework:idnumber',
            'framework:publicaccess',
            'framework:archived',
        ];
        $this->add_filters_from_entities($filters);
        $context = $this->get_context();

        $filter = new filter(
            boolean_select::class,
            'currentcontextonly',
            new lang_string('currentcontextonly', 'tool_mutrain'),
            $this->frameworkentity->get_entity_name(),
            "CASE WHEN {$this->frameworkalias}.contextid = {$context->id} THEN 1 ELSE 0 END"
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
