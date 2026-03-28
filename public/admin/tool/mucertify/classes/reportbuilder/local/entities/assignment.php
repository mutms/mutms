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
use core_reportbuilder\local\filters\boolean_select;
use core_reportbuilder\local\filters\select;
use core_reportbuilder\local\filters\date;

/**
 * Certification assignment entity.
 *
 * @package     tool_mucertify
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class assignment extends base {
    #[\Override]
    protected function get_default_tables(): array {
        return [
            'tool_mucertify_assignment',
        ];
    }

    #[\Override]
    protected function get_default_entity_title(): lang_string {
        return new lang_string('assignment', 'tool_mucertify');
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
        $assignmentalias = $this->get_table_alias('tool_mucertify_assignment');

        $dateformat = get_string('strftimedatetimeshort');
        $now = time();

        $columns[] = (new column(
            'timecertifiedfrom',
            new lang_string('fromdate', 'tool_mucertify'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_field("{$assignmentalias}.timecertifiedfrom")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate'], $dateformat);

        $columns[] = (new column(
            'timecertifieduntil',
            new lang_string('untildate', 'tool_mucertify'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_field("COALESCE({$assignmentalias}.timecertifiedtemp, {$assignmentalias}.timecertifieduntil)", 'timecertifieduntil')
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate'], $dateformat)
            ->add_callback(static function ($value, \stdClass $row): string {
                if ($row->timecertifieduntil != \tool_mulib\local\date_util::TIMESTAMP_FOREVER) {
                    return $value;
                }
                return get_string('noexpiration', 'tool_mucertify');
            });

        $columns[] = (new column(
            'status',
            new lang_string('certificationstatus', 'tool_mucertify'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_field(
                "CASE
                     WHEN {$assignmentalias}.archived = 1 THEN 5
                     WHEN {$assignmentalias}.timecertifiedfrom IS NULL OR {$assignmentalias}.timecertifiedfrom > $now THEN 4
                     WHEN COALESCE({$assignmentalias}.timecertifiedtemp, {$assignmentalias}.timecertifieduntil) < $now THEN 3
                     WHEN {$assignmentalias}.timecertifiedtemp > $now THEN 2
                     WHEN {$assignmentalias}.timecertifieduntil > $now THEN 1
                     ELSE 0
                 END",
                'status'
            )
            ->add_field('(' . "SELECT p.archived FROM {tool_mucertify_certification} p WHERE p.id = {$assignmentalias}.certificationid" . ')', 'certificationarchived')
            ->set_is_sortable(true)
            ->add_callback(static function ($value, \stdClass $row): string {
                switch ($row->status) {
                    case 5:
                        return '<span class="badge bg-dark">' . get_string('certificationstatus_archived', 'tool_mucertify') . '</span>';
                    case 4:
                        return '<span class="badge bg-light text-dark">' . get_string('certificationstatus_notcertified', 'tool_mucertify') . '</span>';
                    case 3:
                        return '<span class="badge bg-light text-dark">' . get_string('certificationstatus_expired', 'tool_mucertify') . '</span>';
                    case 2:
                        return '<span class="badge bg-success">' . get_string('certificationstatus_temporary', 'tool_mucertify') . '</span>';
                    case 1:
                        return '<span class="badge bg-success">' . get_string('certificationstatus_valid', 'tool_mucertify') . '</span>';
                    default:
                        return '';
                }
            });

        $columns[] = (new column(
            'archived',
            new lang_string('archived', 'tool_mucertify'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->add_fields("{$assignmentalias}.archived")
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
        $assignmentalias = $this->get_table_alias('tool_mucertify_assignment');
        $now = time();

        $filters[] = (new filter(
            select::class,
            'status',
            new lang_string('certificationstatus', 'tool_mucertify'),
            $this->get_entity_name(),
            "CASE
                     WHEN {$assignmentalias}.archived = 1 THEN 5
                     WHEN {$assignmentalias}.timecertifiedfrom IS NULL OR {$assignmentalias}.timecertifiedfrom > $now THEN 4
                     WHEN COALESCE({$assignmentalias}.timecertifiedtemp, {$assignmentalias}.timecertifieduntil) < $now THEN 3
                     WHEN {$assignmentalias}.timecertifiedtemp > $now THEN 2
                     WHEN {$assignmentalias}.timecertifieduntil > $now THEN 1
                     ELSE 0
                 END"
        ))
            ->add_joins($this->get_joins())
            ->set_options(
                [
                    5 => get_string('certificationstatus_archived', 'tool_mucertify'),
                    4 => get_string('certificationstatus_notcertified', 'tool_mucertify'),
                    3 => get_string('certificationstatus_expired', 'tool_mucertify'),
                    2 => get_string('certificationstatus_temporary', 'tool_mucertify'),
                    1 => get_string('certificationstatus_valid', 'tool_mucertify'),
                ]
            );

        $filters[] = (new filter(
            boolean_select::class,
            'archived',
            new lang_string('archived', 'tool_mucertify'),
            $this->get_entity_name(),
            "{$assignmentalias}.archived"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            date::class,
            'timecertifiedfrom',
            new lang_string('fromdate', 'tool_mucertify'),
            $this->get_entity_name(),
            "{$assignmentalias}.timecertifiedfrom"
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
            'timecertifieduntil',
            new lang_string('untildate', 'tool_mucertify'),
            $this->get_entity_name(),
            "COALESCE({$assignmentalias}.timecertifiedtemp, {$assignmentalias}.timecertifieduntil)"
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
