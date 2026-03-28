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

use tool_murelation\reportbuilder\local\entities\framework;
use core_reportbuilder\system_report;
use lang_string;

/**
 * Embedded relation frameworks report.
 *
 * @package     tool_murelation
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class frameworks extends system_report {
    /** @var framework */
    protected $frameworkentity;

    #[\Override]
    protected function initialise(): void {
        $this->frameworkentity = new framework();
        $frameworkalias = $this->frameworkentity->get_table_alias('tool_murelation_framework');

        $this->set_main_table('tool_murelation_framework', $frameworkalias);
        $this->add_entity($this->frameworkentity);

        $this->add_base_fields("{$frameworkalias}.id");

        $this->add_columns();
        $this->add_filters();

        $this->set_downloadable(false);
        $this->set_default_no_results_notice(new lang_string('error_noframeworks', 'tool_murelation'));
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
        $columns = [
            'framework:name',
            'framework:idnumber',
            'framework:uimode',
            'framework:supervisortitle',
            'framework:supervisorrole',
            'framework:subordinatetitle',
        ];
        $this->add_columns_from_entities($columns);

        $this->set_initial_sort_column('framework:name', SORT_ASC);
    }

    /**
     * Adds the filters we want to display in the report.
     */
    protected function add_filters(): void {
        $filters = [
            'framework:uimode',
            'framework:name',
            'framework:idnumber',
        ];
        $this->add_filters_from_entities($filters);
    }
}
