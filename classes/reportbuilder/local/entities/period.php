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
 * Certification period entity.
 *
 * @package     tool_mucertify
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class period extends base {
    #[\Override]
    protected function get_default_tables(): array {
        return [
            'tool_mucertify_period',
            'tool_mucertify_assignment',
            'tool_mucertify_certification',
        ];
    }

    #[\Override]
    protected function get_default_entity_title(): lang_string {
        return new lang_string('period', 'tool_mucertify');
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
     * Return syntax for joining on the assignment table
     *
     * @return string
     */
    public function get_assignment_join(): string {
        $periodalias = $this->get_table_alias('tool_mucertify_period');
        $assignmentalias = $this->get_table_alias('tool_mucertify_assignment');

        return "LEFT JOIN {tool_mucertify_assignment} {$assignmentalias}
            ON {$assignmentalias}.userid = {$periodalias}.userid AND {$assignmentalias}.certificationid = {$periodalias}.certificationid";
    }

    /**
     * Return syntax for joining on the certification table
     *
     * @return string
     */
    public function get_certification_join(): string {
        $periodalias = $this->get_table_alias('tool_mucertify_period');
        $certificationalias = $this->get_table_alias('tool_mucertify_certification');

        return "LEFT JOIN {tool_mucertify_certification} {$certificationalias} ON {$certificationalias}.id = {$periodalias}.certificationid";
    }

    /**
     * Returns list of all available columns.
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        $periodalias = $this->get_table_alias('tool_mucertify_period');
        $assignmentalias = $this->get_table_alias('tool_mucertify_assignment');
        $certificationalias = $this->get_table_alias('tool_mucertify_certification');
        $now = time();

        $dateformat = get_string('strftimedatetimeshort');

        $columns[] = (new column(
            'timewindowstart',
            new lang_string('windowstartdate', 'tool_mucertify'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_field("{$periodalias}.timewindowstart")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate'], $dateformat)
            ->add_callback(static function ($value, \stdClass $row): string {
                if (!$row->timewindowstart) {
                    return get_string('notset', 'tool_mucertify');
                }
                return $value;
            });

        $columns[] = (new column(
            'timewindowdue',
            new lang_string('windowduedate', 'tool_mucertify'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_field("{$periodalias}.timewindowdue")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate'], $dateformat)
            ->add_callback(static function ($value, \stdClass $row): string {
                if (!$row->timewindowdue) {
                    return get_string('notset', 'tool_mucertify');
                }
                return $value;
            });

        $columns[] = (new column(
            'timewindowend',
            new lang_string('windowenddate', 'tool_mucertify'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_field("{$periodalias}.timewindowend")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate'], $dateformat)
            ->add_callback(static function ($value, \stdClass $row): string {
                if (!$row->timewindowend) {
                    return get_string('notset', 'tool_mucertify');
                }
                return $value;
            });

        $columns[] = (new column(
            'timefrom',
            new lang_string('fromdate', 'tool_mucertify'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_fields("{$periodalias}.timefrom")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate'], $dateformat)
            ->add_callback(static function ($value, \stdClass $row): string {
                if ($row->timefrom) {
                    return $value;
                }
                return get_string('notset', 'tool_mucertify');
            });

        $columns[] = (new column(
            'timeuntil',
            new lang_string('untildate', 'tool_mucertify'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_fields("{$periodalias}.timeuntil, {$periodalias}.timecertified")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate'], $dateformat)
            ->add_callback(static function ($value, \stdClass $row): string {
                if ($row->timeuntil) {
                    return $value;
                }
                if ($row->timecertified) {
                    return get_string('noexpiration', 'tool_mucertify');
                }
                return get_string('notset', 'tool_mucertify');
            });

        $columns[] = (new column(
            'recertify',
            new lang_string('recertify', 'tool_mucertify'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_join($this->get_assignment_join())
            ->add_join($this->get_certification_join())
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$certificationalias}.recertify, {$periodalias}.recertifiable, {$assignmentalias}.archived, {$periodalias}.timeuntil")
            ->add_field("{$certificationalias}.archived", 'certificationarchived')
            ->add_field("{$assignmentalias}.id", 'assignmentid')
            ->set_is_sortable(false)
            ->add_callback(static function ($value, \stdClass $row) use ($dateformat): string {
                if (!$row->assignmentid || !$row->recertify || !$row->recertifiable || $row->archived || $row->certificationarchived) {
                    return get_string('no');
                }
                if ($row->timeuntil) {
                    return userdate($row->timeuntil - $row->recertify, $dateformat);
                } else {
                    return get_string('recertifyifexpired', 'tool_mucertify');
                }
            });

        $columns[] = (new column(
            'status',
            new lang_string('periodstatus', 'tool_mucertify'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_join($this->get_assignment_join())
            ->add_join($this->get_certification_join())
            ->set_type(column::TYPE_INTEGER)
            ->add_field(
                "CASE
                     WHEN {$certificationalias}.archived = 1 OR {$assignmentalias}.id IS NULL OR {$assignmentalias}.archived = 1 THEN 7
                     WHEN {$periodalias}.timerevoked IS NOT NULL THEN 6
                     WHEN {$periodalias}.timecertified IS NOT NULL AND ({$periodalias}.timeuntil IS NULL OR {$periodalias}.timeuntil > $now) THEN 5
                     WHEN {$periodalias}.timecertified IS NOT NULL THEN 4
                     WHEN {$periodalias}.timewindowend IS NOT NULL AND {$periodalias}.timewindowend < $now THEN 3
                     WHEN {$periodalias}.timewindowdue IS NOT NULL AND {$periodalias}.timewindowdue < $now THEN 2
                     WHEN {$periodalias}.timewindowstart > $now THEN 1
                     ELSE 0
                 END",
                'status'
            )
            ->set_is_sortable(true)
            ->add_callback(static function ($value, \stdClass $row): string {
                switch ($row->status) {
                    case 7:
                        return '<span class="badge bg-dark">' . get_string('periodstatus_archived', 'tool_mucertify') . '</span>';
                    case 6:
                        return '<span class="badge bg-danger">' . get_string('periodstatus_revoked', 'tool_mucertify') . '</span>';
                    case 5:
                        return '<span class="badge bg-success">' . get_string('periodstatus_certified', 'tool_mucertify') . '</span>';
                    case 4:
                        return '<span class="badge bg-light text-dark">' . get_string('periodstatus_expired', 'tool_mucertify') . '</span>';
                    case 3:
                        return '<span class="badge bg-danger">' . get_string('periodstatus_failed', 'tool_mucertify') . '</span>';
                    case 2:
                        return '<span class="badge bg-danger">' . get_string('periodstatus_overdue', 'tool_mucertify') . '</span>';
                    case 1:
                        return '<span class="badge bg-light text-dark">' . get_string('periodstatus_future', 'tool_mucertify') . '</span>';
                    default:
                        return '<span class="badge bg-warning text-dark">' . get_string('periodstatus_pending', 'tool_mucertify') . '</span>';
                }
            });

        return $columns;
    }

    /**
     * Return list of all available filters.
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $periodalias = $this->get_table_alias('tool_mucertify_period');

        $filters[] = (new filter(
            date::class,
            'timewindowstart',
            new lang_string('windowstartdate', 'tool_mucertify'),
            $this->get_entity_name(),
            "{$periodalias}.timewindowstart"
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
            'timewindowdue',
            new lang_string('windowduedate', 'tool_mucertify'),
            $this->get_entity_name(),
            "{$periodalias}.timewindowdue"
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
            'timewindowend',
            new lang_string('windowenddate', 'tool_mucertify'),
            $this->get_entity_name(),
            "{$periodalias}.timewindowend"
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
            'timeuntil',
            new lang_string('untildate', 'tool_mucertify'),
            $this->get_entity_name(),
            "{$periodalias}.timeuntil"
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
