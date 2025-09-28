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

namespace tool_muprog\reportbuilder\local\entities;

use lang_string;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\report\{column, filter};
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\filters\boolean_select;
use core_reportbuilder\local\filters\select;
use core_reportbuilder\local\filters\date;

/**
 * Program allocation entity.
 *
 * @package     tool_muprog
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class allocation extends base {
    #[\Override]
    protected function get_default_tables(): array {
        return [
            'tool_muprog_allocation',
            'tool_muprog_program',
        ];
    }

    #[\Override]
    protected function get_default_entity_title(): lang_string {
        return new lang_string('allocation', 'tool_muprog');
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
     * Return syntax for joining on the program table
     *
     * @return string
     */
    public function get_program_join(): string {
        $allocationalias = $this->get_table_alias('tool_muprog_allocation');
        $programalias = $this->get_table_alias('tool_muprog_program');

        return "LEFT JOIN {tool_muprog_program} {$programalias} ON {$programalias}.id = {$allocationalias}.programid";
    }

    /**
     * Returns list of all available columns.
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        $allocationalias = $this->get_table_alias('tool_muprog_allocation');
        $programalias = $this->get_table_alias('tool_muprog_program');

        $dateformat = get_string('strftimedatetimeshort');

        $columns[] = (new column(
            'timestart',
            new lang_string('programstart', 'tool_muprog'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_field("{$allocationalias}.timestart")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate'], $dateformat);

        $columns[] = (new column(
            'timedue',
            new lang_string('duedate', 'tool_muprog'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_field("{$allocationalias}.timedue")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate'], $dateformat);

        $columns[] = (new column(
            'timeend',
            new lang_string('programend', 'tool_muprog'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_field("{$allocationalias}.timeend")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate'], $dateformat);

        $columns[] = (new column(
            'timecompleted',
            new lang_string('programcompletion', 'tool_muprog'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_field("{$allocationalias}.timecompleted")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate'], $dateformat);

        $columns[] = (new column(
            'status',
            new lang_string('programstatus', 'tool_muprog'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_join($this->get_program_join())
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$allocationalias}.id, {$allocationalias}.programid, {$allocationalias}.timestart, {$allocationalias}.timedue, "
                . "{$allocationalias}.timeend, {$allocationalias}.timecompleted, {$allocationalias}.archived")
            ->add_field("{$programalias}.archived", 'programarchived')
            ->set_is_sortable(false)
            ->add_callback(static function ($value, \stdClass $row): string {
                if (!$row->id) {
                    return '';
                }
                $program = (object)['id' => $row->programid, 'archived' => $row->programarchived];
                return \tool_muprog\local\allocation::get_completion_status_html($program, $row);
            });

        $columns[] = (new column(
            'archived',
            new lang_string('archived', 'tool_muprog'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->add_fields("{$allocationalias}.archived")
            ->set_is_sortable(true)
            ->set_callback([format::class, 'boolean_as_text']);

        return $columns;
    }

    /**
     * Return list of all available filters.
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $allocationalias = $this->get_table_alias('tool_muprog_allocation');
        $now = time();

        $filters[] = (new filter(
            select::class,
            'status',
            new lang_string('programstatus', 'tool_muprog'),
            $this->get_entity_name(),
            "CASE
                WHEN {$allocationalias}.timecompleted IS NOT NULL THEN 1
                WHEN {$allocationalias}.timecompleted IS NULL AND {$allocationalias}.timestart > $now THEN 2
                WHEN {$allocationalias}.timecompleted IS NULL AND {$allocationalias}.timeend < $now THEN 3
                WHEN {$allocationalias}.timecompleted IS NULL AND ({$allocationalias}.timeend > $now OR {$allocationalias}.timeend IS NULL)
                    AND {$allocationalias}.timedue < $now THEN 4
                WHEN {$allocationalias}.timecompleted IS NULL AND {$allocationalias}.timestart < $now
                    AND ({$allocationalias}.timeend > $now OR {$allocationalias}.timeend IS NULL)
                    AND ({$allocationalias}.timedue > $now OR {$allocationalias}.timedue IS NULL) THEN 5
                ELSE 0
            END"
        ))
            ->add_joins($this->get_joins())
            ->set_options(
                [
                    1 => get_string('programstatus_completed', 'tool_muprog'),
                    2 => get_string('programstatus_future', 'tool_muprog'),
                    3 => get_string('programstatus_failed', 'tool_muprog'),
                    4 => get_string('programstatus_overdue', 'tool_muprog'),
                    5 => get_string('programstatus_open', 'tool_muprog'),
                ]
            );

        $filters[] = (new filter(
            boolean_select::class,
            'archived',
            new lang_string('archived', 'tool_muprog'),
            $this->get_entity_name(),
            "{$allocationalias}.archived"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            date::class,
            'timestart',
            new lang_string('programstart', 'tool_muprog'),
            $this->get_entity_name(),
            "{$allocationalias}.timestart"
        ))
            ->add_joins($this->get_joins())
            ->set_limited_operators([
                date::DATE_ANY,
                date::DATE_NOT_EMPTY,
                date::DATE_EMPTY,
                date::DATE_RANGE,
                date::DATE_LAST,
                date::DATE_CURRENT,
            ]);

        $filters[] = (new filter(
            date::class,
            'timedue',
            new lang_string('duedate', 'tool_muprog'),
            $this->get_entity_name(),
            "{$allocationalias}.timedue"
        ))
            ->add_joins($this->get_joins())
            ->set_limited_operators([
                date::DATE_ANY,
                date::DATE_NOT_EMPTY,
                date::DATE_EMPTY,
                date::DATE_RANGE,
                date::DATE_LAST,
                date::DATE_CURRENT,
            ]);

        $filters[] = (new filter(
            date::class,
            'timeend',
            new lang_string('programend', 'tool_muprog'),
            $this->get_entity_name(),
            "{$allocationalias}.timeend"
        ))
            ->add_joins($this->get_joins())
            ->set_limited_operators([
                date::DATE_ANY,
                date::DATE_NOT_EMPTY,
                date::DATE_EMPTY,
                date::DATE_RANGE,
                date::DATE_LAST,
                date::DATE_CURRENT,
            ]);

        $filters[] = (new filter(
            date::class,
            'timecompleted',
            new lang_string('programcompletion', 'tool_muprog'),
            $this->get_entity_name(),
            "{$allocationalias}.timecompleted"
        ))
            ->add_joins($this->get_joins())
            ->set_limited_operators([
                date::DATE_ANY,
                date::DATE_NOT_EMPTY,
                date::DATE_EMPTY,
                date::DATE_RANGE,
                date::DATE_LAST,
                date::DATE_CURRENT,
            ]);

        return $filters;
    }
}
