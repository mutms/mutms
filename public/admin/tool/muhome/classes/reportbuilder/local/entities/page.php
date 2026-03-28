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

namespace tool_muhome\reportbuilder\local\entities;

use core\url;
use lang_string;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\report\{column, filter};
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\filters\boolean_select;
use core_reportbuilder\local\filters\select;

/**
 * Page entity.
 *
 * @package     tool_muhome
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class page extends base {
    #[\Override]
    protected function get_default_tables(): array {
        return [
            'tool_muhome_page',
            'context',
            'tool_mulib_context_map',
        ];
    }

    #[\Override]
    protected function get_default_entity_title(): lang_string {
        return new lang_string('page', 'tool_muhome');
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
        $pagealias = $this->get_table_alias('tool_muhome_page');
        $contextalias = $this->get_table_alias('context');

        return "JOIN {context} {$contextalias} ON {$contextalias}.id = {$pagealias}.contextid";
    }

    /**
     * Return syntax for joining on the context map table to restrict result to subcontexts.
     *
     * @param \context $context
     * @return string
     */
    public function get_context_map_join(\context $context): string {
        $pagealias = $this->get_table_alias('tool_muhome_page');
        $contextmapalias = $this->get_table_alias('tool_mulib_context_map');

        return "JOIN {tool_mulib_context_map} {$contextmapalias} ON
                     {$contextmapalias}.contextid = {$pagealias}.contextid AND {$contextmapalias}.relatedcontextid = {$context->id}";
    }

    /**
     * Returns list of all available columns.
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        $pagealias = $this->get_table_alias('tool_muhome_page');
        $dateformat = get_string('strftimedatetimeshort');

        $columns[] = (new column(
            'priority',
            new lang_string('page_priority', 'tool_muhome'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$pagealias}.priority")
            ->set_is_sortable(true);

        $columns[] = (new column(
            'name',
            new lang_string('page_name', 'tool_muhome'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$pagealias}.name, {$pagealias}.id, {$pagealias}.contextid")
            ->set_is_sortable(true)
            ->set_callback(static function (?string $value, \stdClass $row): string {
                if (!$row->id) {
                    return '';
                }
                $context = \context::instance_by_id($row->contextid);
                $name = format_string($row->name);
                if (has_capability('tool/muhome:view', $context)) {
                    $url = \tool_muhome\local\page::get_url($row->id);
                    $name = \html_writer::link($url, $name);
                }
                return $name;
            });

        $columns[] = (new column(
            'title',
            new lang_string('page_title', 'tool_muhome'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_field("{$pagealias}.title")
            ->set_is_sortable(true)
            ->set_callback(static function (?string $value, \stdClass $row): string {
                if ($value === null) {
                    return '';
                }
                return format_string($row->title);
            });

        $columns[] = (new column(
            'status',
            new lang_string('page_status', 'tool_muhome'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_field("{$pagealias}.status")
            ->set_is_sortable(true)
            ->add_callback(static function (?int $value, \stdClass $row): string {
                if ($value === null) {
                    return '';
                }
                $statuses = \tool_muhome\local\page::get_statuses_menu();
                return $statuses[$value];
            });

        $columns[] = (new column(
            'guestvisible',
            new lang_string('guestvisible', 'tool_muhome'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->add_field("{$pagealias}.guestvisible")
            ->set_is_sortable(true)
            ->set_callback([format::class, 'boolean_as_text']);

        $columns[] = (new column(
            'uservisible',
            new lang_string('uservisible', 'tool_muhome'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->add_field("{$pagealias}.uservisible")
            ->set_is_sortable(true)
            ->set_callback([format::class, 'boolean_as_text']);

        $columns[] = (new column(
            'hiddenfromtenants',
            new lang_string('hiddenfromtenants', 'tool_muhome'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->add_field("{$pagealias}.hiddenfromtenants")
            ->set_is_sortable(true)
            ->set_callback([format::class, 'boolean_as_text']);

        $columns[] = (new column(
            'context',
            new lang_string('page_category', 'tool_muhome'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_join($this->get_context_join())
            ->set_type(column::TYPE_INTEGER)
            ->add_field("{$pagealias}.contextid")
            ->set_is_sortable(false)
            ->set_callback(static function (?int $value, \stdClass $row): string {
                if (!$row->contextid) {
                    return '';
                }
                $context = \context::instance_by_id($row->contextid);
                $name = $context->get_context_name(false);

                if (!has_capability('tool/muhome:view', $context)) {
                    return $name;
                }
                $url = new url('/admin/tool/muhome/management/index.php', ['contextid' => $context->id]);
                $name = \html_writer::link($url, $name);
                return $name;
            });

        $columns[] = (new column(
            'hiddenbefore',
            new lang_string('hiddenbefore', 'tool_muhome'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_field("{$pagealias}.hiddenbefore")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate'], $dateformat);

        $columns[] = (new column(
            'hiddenafter',
            new lang_string('hiddenafter', 'tool_muhome'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_field("{$pagealias}.hiddenafter")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate'], $dateformat);

        $columns[] = (new column(
            'cohortvisible',
            new lang_string('cohortvisible', 'tool_muhome'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_field("{$pagealias}.id")
            ->add_field("{$pagealias}.uservisible")
            ->set_is_sortable(false)
            ->add_callback(static function (?int $value, \stdClass $row): string {
                if (!$row->id || $row->uservisible) {
                    return '';
                }
                $cohorts = \tool_muhome\local\page::get_cohortvisible_menu($row->id);
                if (!$cohorts) {
                    return '';
                }
                $cohorts = array_map('format_string', $cohorts);
                return implode(', ', $cohorts);
            });

        return $columns;
    }

    /**
     * Return list of all available filters.
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $pagealias = $this->get_table_alias('tool_muhome_page');

        $filters[] = (new filter(
            text::class,
            'name',
            new lang_string('page_name', 'tool_muhome'),
            $this->get_entity_name(),
            "{$pagealias}.name"
        ))
            ->add_joins($this->get_joins());
        $filters[] = (new filter(
            text::class,
            'title',
            new lang_string('page_title', 'tool_muhome'),
            $this->get_entity_name(),
            "{$pagealias}.title"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            boolean_select::class,
            'guestvisible',
            new lang_string('guestvisible', 'tool_muhome'),
            $this->get_entity_name(),
            "{$pagealias}.guestvisible"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            boolean_select::class,
            'uservisible',
            new lang_string('uservisible', 'tool_muhome'),
            $this->get_entity_name(),
            "{$pagealias}.uservisible"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            select::class,
            'status',
            new lang_string('page_status', 'tool_muhome'),
            $this->get_entity_name(),
            "{$pagealias}.status"
        ))
            ->add_joins($this->get_joins())
            ->set_options(\tool_muhome\local\page::get_statuses_menu());

        return $filters;
    }
}
