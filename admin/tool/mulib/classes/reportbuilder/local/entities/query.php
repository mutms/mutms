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

namespace tool_mulib\reportbuilder\local\entities;

use lang_string;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\report\{column, filter};

/**
 * External database query entity.
 *
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class query extends base {
    #[\Override]
    protected function get_default_tables(): array {
        return [
            'tool_mulib_extdb_query',
            'context',
        ];
    }

    #[\Override]
    protected function get_default_entity_title(): lang_string {
        return new lang_string('extdb_query', 'tool_mulib');
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
     * Return syntax for joining on the context table
     *
     * @return string
     */
    public function get_context_join(): string {
        $queryalias = $this->get_table_alias('tool_mulib_extdb_query');
        $contextalias = $this->get_table_alias('context');

        return "JOIN {context} {$contextalias} ON {$contextalias}.id = {$queryalias}.contextid";
    }

    /**
     * Returns list of all available columns.
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        $queryalias = $this->get_table_alias('tool_mulib_extdb_query');

        $columns[] = (new column(
            'name',
            new lang_string('name'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$queryalias}.name")
            ->set_is_sortable(true);

        $columns[] = (new column(
            'context',
            new lang_string('category'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_join($this->get_context_join())
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$queryalias}.contextid")
            ->set_is_sortable(false)
            ->set_callback(static function (?int $value, \stdClass $row): string {
                if (!$row->contextid) {
                    return '';
                }
                $context = \context::instance_by_id($row->contextid);
                return $context->get_context_name(false);
            });

        $columns[] = (new column(
            'component',
            new lang_string('plugin'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$queryalias}.component")
            ->set_is_sortable(true)
            ->set_callback(static function (?string $value, \stdClass $row): string {
                return s($row->component);
            });

        $columns[] = (new column(
            'type',
            new lang_string('extdb_query_type', 'tool_mulib'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$queryalias}.type")
            ->set_is_sortable(true)
            ->set_callback(static function (?string $value, \stdClass $row): string {
                return s($row->type); // TODO: ask the query class for name of type.
            });

        $columns[] = (new column(
            'sqlquery',
            new lang_string('extdb_query_sqlquery', 'tool_mulib'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$queryalias}.sqlquery")
            ->set_is_sortable(false)
            ->set_callback(static function (?string $value, \stdClass $row): string {
                return '<pre>' . s($row->sqlquery) . '</pre>';
            });

        $columns[] = (new column(
            'note',
            new lang_string('note', 'core_notes'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$queryalias}.note")
            ->set_is_sortable(false)
            ->set_callback(static function (?string $value, \stdClass $row): string {
                return s($row->note);
            });

        return $columns;
    }

    /**
     * Return list of all available filters.
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $queryalias = $this->get_table_alias('tool_mulib_extdb_query');

        $filters[] = (new filter(
            text::class,
            'name',
            new lang_string('name'),
            $this->get_entity_name(),
            "{$queryalias}.name"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            text::class,
            'component',
            new lang_string('plugin'),
            $this->get_entity_name(),
            "{$queryalias}.component"
        ))
            ->add_joins($this->get_joins());

        return $filters;
    }
}
