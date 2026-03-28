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

namespace tool_mucertify\reportbuilder\local\entities;

use lang_string;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\report\{column, filter};
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\filters\select;

/**
 * Certification assignment request entity.
 *
 * @package     tool_mucertify
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class request extends base {
    #[\Override]
    protected function get_default_tables(): array {
        return [
            'tool_mucertify_request',
        ];
    }

    #[\Override]
    protected function get_default_entity_title(): lang_string {
        return new lang_string('source_approval_request', 'tool_mucertify');
    }

    #[\Override]
    public function initialise(): base {
        $columns = $this->get_all_columns();
        foreach ($columns as $column) {
            $this->add_column($column);
        }

        // All the filters defined by the entity can also be used as conditions.
        $filters = $this->get_all_filters();
        foreach ($filters as $filter) {
            $this
                ->add_filter($filter)
                ->add_condition($filter);
        }

        return $this;
    }

    /**
     * Returns list of all available columns.
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        $requestalias = $this->get_table_alias('tool_mucertify_request');

        $dateformat = get_string('strftimedatetimeshort');

        $columns[] = (new column(
            'timerequested',
            new lang_string('source_approval_daterequested', 'tool_mucertify'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_field("{$requestalias}.timerequested")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate'], $dateformat);

        $columns[] = (new column(
            'timerejected',
            new lang_string('source_approval_daterejected', 'tool_mucertify'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_field("{$requestalias}.timerejected")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate'], $dateformat);

        return $columns;
    }

    /**
     * Return list of all available filters.
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $requestalias = $this->get_table_alias('tool_mucertify_request');

        $filters[] = (new filter(
            select::class,
            'status',
            new lang_string('status'),
            $this->get_entity_name(),
            "CASE WHEN {$requestalias}.timerejected IS NOT NULL THEN 1 ELSE 0 END"
        ))
            ->add_joins($this->get_joins())
            ->set_options(
                [
                    0 => get_string('source_approval_requestpending', 'tool_mucertify'),
                    1 => get_string('source_approval_requestrejected', 'tool_mucertify'),
                ]
            );

        return $filters;
    }
}
