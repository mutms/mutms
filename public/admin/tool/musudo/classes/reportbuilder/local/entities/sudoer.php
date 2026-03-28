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

namespace tool_musudo\reportbuilder\local\entities;

use lang_string;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\filters\boolean_select;
use core_reportbuilder\local\report\{column, filter};

/**
 * Sudoer entity.
 *
 * @package    tool_musudo
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class sudoer extends base {
    #[\Override]
    protected function get_default_tables(): array {
        return [
            'tool_musudo_sudoer',
        ];
    }

    #[\Override]
    protected function get_default_entity_title(): lang_string {
        return new lang_string('sudoer', 'tool_musudo');
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
        $sudoeralias = $this->get_table_alias('tool_musudo_sudoer');
        $useralias = $this->get_table_alias('user');

        return "JOIN {user} {$useralias} ON {$useralias}.id = {$sudoeralias}.userid";
    }

    /**
     * Returns list of all available columns.
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        $sudoeralias = $this->get_table_alias('tool_musudo_sudoer');

        $columns[] = (new column(
            'note',
            new lang_string('sudoer_note', 'tool_musudo'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$sudoeralias}.note")
            ->set_is_sortable(false)
            ->set_callback(static function (?string $value, \stdClass $row): string {
                return s($row->note);
            });

        $columns[] = (new column(
            'mfarequired',
            new lang_string('mfarequired', 'tool_musudo'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_fields("{$sudoeralias}.mfarequired")
            ->set_is_sortable(true)
            ->set_callback(static function (?string $value, \stdClass $row): string {
                if ($value) {
                    return get_string('yes');
                } else {
                    return get_string('no');
                }
            });

        $columns[] = (new column(
            'privileges',
            new lang_string('privileges', 'tool_musudo'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_fields("{$sudoeralias}.privilegesjson")
            ->set_is_sortable(false)
            ->set_callback(static function (string $value, \stdClass $row): string {
                return \tool_musudo\local\sudoer::get_privileges_description($row, true);
            });

        return $columns;
    }

    /**
     * Return list of all available filters.
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $sudoeralias = $this->get_table_alias('tool_musudo_sudoer');

        $filters[] = (new filter(
            text::class,
            'note',
            new lang_string('sudoer_note', 'tool_musudo'),
            $this->get_entity_name(),
            "{$sudoeralias}.note"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            boolean_select::class,
            'mfarequired',
            new lang_string('mfarequired', 'tool_musudo'),
            $this->get_entity_name(),
            "{$sudoeralias}.mfarequired"
        ))
            ->add_joins($this->get_joins());

        return $filters;
    }
}
