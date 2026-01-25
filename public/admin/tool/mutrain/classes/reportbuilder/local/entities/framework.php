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

namespace tool_mutrain\reportbuilder\local\entities;

use lang_string;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\report\{column, filter};
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\filters\boolean_select;

/**
 * Credit framework entity.
 *
 * @package    tool_mutrain
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class framework extends base {
    #[\Override]
    protected function get_default_tables(): array {
        return [
            'tool_mutrain_framework',
            'tool_mutrain_credit',
            'context',
            'tool_mulib_context_map',
        ];
    }

    #[\Override]
    protected function get_default_entity_title(): lang_string {
        return new lang_string('framework', 'tool_mutrain');
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
        $frameworkalias = $this->get_table_alias('tool_mutrain_framework');
        $contextalias = $this->get_table_alias('context');

        return "JOIN {context} {$contextalias} ON {$contextalias}.id = {$frameworkalias}.contextid";
    }

    /**
     * Return syntax for joining on the context map table to restrict result to subcontexts.
     *
     * @param \context $context
     * @return string
     */
    public function get_context_map_join(\context $context): string {
        $frameworkalias = $this->get_table_alias('tool_mutrain_framework');
        $contextmapalias = $this->get_table_alias('tool_mulib_context_map');

        return "JOIN {tool_mulib_context_map} {$contextmapalias} ON
                     {$contextmapalias}.contextid = {$frameworkalias}.contextid AND {$contextmapalias}.relatedcontextid = {$context->id}";
    }

    /**
     * Returns list of all available columns.
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        $frameworkalias = $this->get_table_alias('tool_mutrain_framework');

        $columns = [];

        $columns[] = (new column(
            'name',
            new lang_string('framework_name', 'tool_mutrain'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$frameworkalias}.name")
            ->set_is_sortable(true)
            ->set_callback(static function (?string $value, \stdClass $row): string {
                if (!isset($value)) {
                    return '';
                }
                return format_string($value);
            });

        $columns[] = (new column(
            'namewithlink',
            new lang_string('framework_name', 'tool_mutrain'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$frameworkalias}.name, {$frameworkalias}.id, {$frameworkalias}.contextid, {$frameworkalias}.publicaccess")
            ->set_is_sortable(true)
            ->set_callback(static function (?string $value, \stdClass $row): string {
                if (!$row->id) {
                    return '';
                }
                $context = \context::instance_by_id($row->contextid);
                $name = format_string($row->name);
                if (has_capability('tool/mutrain:viewframeworks', $context)) {
                    $url = new \core\url('/admin/tool/mutrain/management/framework.php', ['id' => $row->id]);
                    $name = \html_writer::link($url, $name);
                }
                return $name;
            });

        $columns[] = (new column(
            'idnumber',
            new lang_string('framework_idnumber', 'tool_mutrain'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$frameworkalias}.idnumber")
            ->set_is_sortable(true)
            ->set_callback(static function (?string $value, \stdClass $row): string {
                return s($row->idnumber);
            });

        $columns[] = (new column(
            'publicaccess',
            new lang_string('publicaccess', 'tool_mutrain'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->add_fields("{$frameworkalias}.publicaccess")
            ->set_is_sortable(true)
            ->set_callback([format::class, 'boolean_as_text']);

        $columns[] = (new column(
            'context',
            new lang_string('category'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_join($this->get_context_join())
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$frameworkalias}.contextid")
            ->set_is_sortable(false)
            ->set_callback(static function (?int $value, \stdClass $row): string {
                global $PAGE;
                if (!$row->contextid) {
                    return '';
                }
                $context = \context::instance_by_id($row->contextid);
                $name = $context->get_context_name(false);

                if (!has_capability('tool/mutrain:viewframeworks', $context)) {
                    return $name;
                }
                $url = new \core\url('/admin/tool/mutrain/management/index.php', ['contextid' => $context->id]);
                if ($url->compare($PAGE->url)) {
                    return $name;
                }
                $name = \html_writer::link($url, $name);
                return $name;
            });

        $columns[] = (new column(
            'requiredcredits',
            new lang_string('requiredcredits', 'tool_mutrain'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_FLOAT)
            ->add_field("{$frameworkalias}.requiredcredits")
            ->set_is_sortable(true)
            ->set_callback(static function (mixed $value, \stdClass $row): string {
                return format_float($row->requiredcredits, 2, true, true);
            });

        $columns[] = (new column(
            'restrictafter',
            new lang_string('restrictafter', 'tool_mutrain'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_fields("{$frameworkalias}.restrictafter")
            ->set_is_sortable(true)
            ->set_callback(static function (?int $value, \stdClass $row): string {
                if (!$value) {
                    return get_string('notset', 'tool_mulib');
                }

                return userdate($value, get_string('strftimedatetimeshort'));
            });

        $columns[] = (new column(
            'restrictcontext',
            new lang_string('restrictcontext', 'tool_mutrain'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->add_fields("{$frameworkalias}.restrictcontext, {$frameworkalias}.contextid")
            ->set_is_sortable(true)
            ->set_callback(static function (bool $value, \stdClass $row): string {
                if (!$value) {
                    return get_string('no');
                }
                $context = \context::instance_by_id($row->contextid);
                return $context->get_context_name(false);
            });

        $columns[] = (new column(
            'archived',
            new lang_string('archived', 'tool_mutrain'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->add_fields("{$frameworkalias}.archived")
            ->set_is_sortable(true)
            ->set_callback([format::class, 'boolean_as_text']);

        $columns[] = (new column(
            'fieldcount',
            new lang_string('fields', 'tool_mutrain'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_field('(' . "SELECT COUNT('x')
                                 FROM {tool_mutrain_field} tf
                                WHERE tf.frameworkid = {$frameworkalias}.id" . ')', 'fieldcount')
            ->set_is_sortable(true)
            ->set_disabled_aggregation_all();

        return $columns;
    }

    /**
     * Return list of all available filters.
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $frameworkalias = $this->get_table_alias('tool_mutrain_framework');

        $filters[] = (new filter(
            text::class,
            'name',
            new lang_string('framework_name', 'tool_mutrain'),
            $this->get_entity_name(),
            "{$frameworkalias}.name"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            text::class,
            'idnumber',
            new lang_string('framework_idnumber', 'tool_mutrain'),
            $this->get_entity_name(),
            "{$frameworkalias}.idnumber"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            boolean_select::class,
            'publicaccess',
            new lang_string('publicaccess', 'tool_mutrain'),
            $this->get_entity_name(),
            "{$frameworkalias}.publicaccess"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            boolean_select::class,
            'archived',
            new lang_string('archived', 'tool_mutrain'),
            $this->get_entity_name(),
            "{$frameworkalias}.archived"
        ))
            ->add_joins($this->get_joins());

        return $filters;
    }
}
