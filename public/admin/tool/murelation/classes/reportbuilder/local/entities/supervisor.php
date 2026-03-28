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

namespace tool_murelation\reportbuilder\local\entities;

use lang_string;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\filters\boolean_select;
use core_reportbuilder\local\report\{column, filter};
use core_reportbuilder\local\helpers\format;

/**
 * Supervisor entity.
 *
 * @package    tool_murelation
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class supervisor extends base {
    #[\Override]
    protected function get_default_tables(): array {
        return [
            'tool_murelation_supervisor',
            'user',
        ];
    }

    #[\Override]
    protected function get_default_entity_title(): lang_string {
        return new lang_string('supervisor', 'tool_murelation');
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
     * Return syntax for joining on the user table
     *
     * @return string
     */
    public function get_user_join(): string {
        $supervisoralias = $this->get_table_alias('tool_murelation_supervisor');
        $useralias = $this->get_table_alias('user');

        return "LEFT JOIN {user} {$useralias} ON {$useralias}.id = {$supervisoralias}.userid";
    }

    /**
     * Returns list of all available columns.
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        $supervisoralias = $this->get_table_alias('tool_murelation_supervisor');
        $useralias = $this->get_table_alias('user');

        $columns[] = (new column(
            'teamname',
            new lang_string('team_name', 'tool_murelation'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$supervisoralias}.teamname, {$supervisoralias}.id")
            ->set_is_sortable(true)
            ->set_callback(static function (?string $value, \stdClass $row): string {
                if (!$row->id) {
                    return '';
                }
                if (!isset($row->teamname)) {
                    // This should not happen.
                    $name = get_string('notset', 'tool_mulib');
                } else {
                    $name = format_string($row->teamname);
                }

                return $name;
            });

        $columns[] = (new column(
            'teamidnumber',
            new lang_string('team_idnumber', 'tool_murelation'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$supervisoralias}.teamidnumber")
            ->set_is_sortable(true)
            ->set_callback(static function (?string $value, \stdClass $row): string {
                return s($row->teamidnumber);
            });

        $columns[] = (new column(
            'supmanaged',
            new lang_string('team_supmanaged', 'tool_murelation'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->add_field('supmanaged')
            ->set_is_sortable(true)
            ->set_callback([format::class, 'boolean_as_text']);

        $columns[] = (new column(
            'maxsubordinates',
            new lang_string('team_maxsubordinates', 'tool_murelation'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_field('maxsubordinates')
            ->set_is_sortable(true)
            ->set_callback(static function (?int $value, \stdClass $row): string {
                if (!$value) {
                    return get_string('notset', 'tool_mulib');
                }
                return $value;
            });

        $columns[] = (new column(
            'subordinates',
            new lang_string('subordinates', 'tool_murelation'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_field('(' .
                "SELECT COUNT('x')
                   FROM {tool_murelation_subordinate} s
                  WHERE s.supervisorid = {$supervisoralias}.id" . ')', 'subordinates')
            ->add_field("{$supervisoralias}.maxsubordinates")
            ->add_field("{$supervisoralias}.id")
            ->set_is_sortable(true)
            ->set_callback(static function (?int $value, \stdClass $row): string {
                $result = $value;
                if ($row->maxsubordinates) {
                    $result .= ' / ' . $row->maxsubordinates;
                }
                return $result;
            });

        $columns[] = (new column(
            'vacant',
            new lang_string('supervisor_vacant', 'tool_murelation'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_join($this->get_user_join())
            ->set_type(column::TYPE_BOOLEAN)
            ->add_field("(CASE WHEN {$useralias}.id IS NULL THEN 1 ELSE 0 END)", 'vacant')
            ->set_is_sortable(true)
            ->set_callback([format::class, 'boolean_as_text']);

        if (\tool_mulib\local\mulib::is_mutenancy_active()) {
            $columns[] = (new column(
                'tenant',
                new lang_string('tenant', 'tool_mutenancy'),
                $this->get_entity_name()
            ))
                ->add_joins($this->get_joins())
                ->set_type(column::TYPE_TEXT)
                ->add_fields("{$supervisoralias}.tenantid")
                ->set_is_sortable(false)
                ->set_callback(static function (?string $value, \stdClass $row): string {
                    if (!$value) {
                        return '';
                    }
                    $tenant = \tool_mutenancy\local\tenant::fetch($value);
                    if (!$tenant) {
                        return get_string('error');
                    }

                    return format_string($tenant->name);
                });
        }

        return $columns;
    }

    /**
     * Return list of all available filters.
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $supervisoralias = $this->get_table_alias('tool_murelation_supervisor');
        $useralias = $this->get_table_alias('user');

        $filters[] = (new filter(
            text::class,
            'teamidnumber',
            new lang_string('team_idnumber', 'tool_murelation'),
            $this->get_entity_name(),
            "{$supervisoralias}.teamidnumber"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            text::class,
            'teamname',
            new lang_string('team_name', 'tool_murelation'),
            $this->get_entity_name(),
            "{$supervisoralias}.teamname"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            boolean_select::class,
            'supmanaged',
            new lang_string('team_supmanaged', 'tool_murelation'),
            $this->get_entity_name(),
            "{$supervisoralias}.supmanaged"
        ))
            ->add_joins($this->get_joins())
            ->add_join($this->get_user_join());

        $filters[] = (new filter(
            boolean_select::class,
            'vacant',
            new lang_string('supervisor_vacant', 'tool_murelation'),
            $this->get_entity_name(),
            "(CASE WHEN {$useralias}.id IS NULL THEN 1 ELSE 0 END)"
        ))
            ->add_joins($this->get_joins())
            ->add_join($this->get_user_join());

        return $filters;
    }
}
