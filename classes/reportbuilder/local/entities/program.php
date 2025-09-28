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
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\report\{column, filter};
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\filters\boolean_select;

/**
 * Program entity.
 *
 * @package     tool_muprog
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program extends base {
    #[\Override]
    protected function get_default_tables(): array {
        return [
            'tool_muprog_program',
            'context',
        ];
    }

    #[\Override]
    protected function get_default_entity_title(): lang_string {
        return new lang_string('program', 'tool_muprog');
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
        $programalias = $this->get_table_alias('tool_muprog_program');
        $contextalias = $this->get_table_alias('context');

        return "JOIN {context} {$contextalias} ON {$contextalias}.id = {$programalias}.contextid";
    }

    /**
     * Returns list of all available columns.
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        $programalias = $this->get_table_alias('tool_muprog_program');

        $columns[] = (new column(
            'fullname',
            new lang_string('programname', 'tool_muprog'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$programalias}.id, {$programalias}.fullname, {$programalias}.contextid")
            ->set_is_sortable(true)
            ->set_callback(static function (?string $value, \stdClass $row): string {
                if (!$row->id) {
                    return '';
                }
                $context = \context::instance_by_id($row->contextid);
                $name = format_string($row->fullname);
                if (has_capability('tool/muprog:view', $context)) {
                    $url = new \moodle_url('/admin/tool/muprog/management/program.php', ['id' => $row->id]);
                    $name = \html_writer::link($url, $name);
                }
                return $name;
            });

        $columns[] = (new column(
            'idnumber',
            new lang_string('programidnumber', 'tool_muprog'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$programalias}.idnumber")
            ->set_is_sortable(true)
            ->set_callback(static function (?string $value, \stdClass $row): string {
                return s($row->idnumber);
            });

        $columns[] = (new column(
            'publicaccess',
            new lang_string('publicaccess', 'tool_muprog'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->add_fields("{$programalias}.publicaccess, {$programalias}.id, {$programalias}.contextid")
            ->set_is_sortable(true)
            ->set_callback([format::class, 'boolean_as_text'])
            ->add_callback(static function (string $value, \stdClass $row): string {
                $context = \context::instance_by_id($row->contextid);
                if (!has_capability('tool/muprog:view', $context)) {
                    return $value;
                }
                $url = new \moodle_url('/admin/tool/muprog/management/program_visibility.php', ['id' => $row->id]);
                $value = \html_writer::link($url, $value);
                return $value;
            });

        $columns[] = (new column(
            'archived',
            new lang_string('archived', 'tool_muprog'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->add_fields("{$programalias}.archived")
            ->set_is_sortable(true)
            ->set_callback([format::class, 'boolean_as_text']);

        $columns[] = (new column(
            'creategroups',
            new lang_string('creategroups', 'tool_muprog'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->add_fields("{$programalias}.creategroups")
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
            ->add_fields("{$programalias}.contextid")
            ->set_is_sortable(false)
            ->set_callback(static function (?int $value, \stdClass $row): string {
                if (!$row->contextid) {
                    return '';
                }
                $context = \context::instance_by_id($row->contextid);
                $name = $context->get_context_name(false);

                if (!has_capability('tool/muprog:view', $context)) {
                    return $name;
                }
                $url = new \moodle_url('/admin/tool/muprog/management/index.php', ['contextid' => $context->id]);
                $name = \html_writer::link($url, $name);
                return $name;
            });

        $columns[] = (new column(
            'allocationcount',
            new lang_string('allocations', 'tool_muprog'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_field('(' . "SELECT COUNT('x')
                                 FROM {tool_muprog_allocation} a
                                WHERE a.programid = {$programalias}.id" . ')', 'allocationcount')
            ->add_field("{$programalias}.id")
            ->add_field("{$programalias}.contextid")
            ->set_is_sortable(true)
            ->set_disabled_aggregation_all()
            ->set_callback(static function (?int $value, \stdClass $row): string {
                $count = $row->allocationcount;
                $context = \context::instance_by_id($row->contextid);
                if (has_capability('tool/muprog:view', $context)) {
                    $url = new \moodle_url('/admin/tool/muprog/management/program_allocation.php', ['id' => $row->id]);
                    $count = \html_writer::link($url, $count);
                }
                return $count;
            });

        $columns[] = (new column(
            'coursecount',
            new lang_string('courses'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_field('(' . "SELECT COUNT('x')
                                 FROM {tool_muprog_item} i
                                WHERE i.courseid IS NOT NULL AND i.programid = {$programalias}.id" . ')', 'coursecount')
            ->add_field("{$programalias}.id")
            ->add_field("{$programalias}.contextid")
            ->set_is_sortable(true)
            ->set_disabled_aggregation_all()
            ->set_callback(static function (?int $value, \stdClass $row): string {
                global $DB;
                $count = $row->coursecount;
                $context = \context::instance_by_id($row->contextid);
                if (has_capability('tool/muprog:view', $context)) {
                    $url = new \moodle_url('/admin/tool/muprog/management/program_content.php', ['id' => $row->id]);
                    $count = \html_writer::link($url, $count);
                }
                if ($row->coursecount) {
                    $sql = "SELECT COUNT(DISTINCT pi.courseid)
                              FROM {tool_muprog_item} pi
                         LEFT JOIN {course} c ON c.id = pi.courseid
                             WHERE pi.courseid IS NOT NULL AND c.id IS NULL AND pi.programid = :programid";
                    $params = ['programid' => $row->id];
                    $missingcount = $DB->count_records_sql($sql, $params);
                    if ($missingcount) {
                        $count .= '</br><span class="badge bg-danger">' . get_string('errorcoursesmissing', 'tool_muprog', $missingcount) . '</span>';
                    }
                }
                return $count;
            });

        return $columns;
    }

    /**
     * Return list of all available filters.
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $programalias = $this->get_table_alias('tool_muprog_program');

        $filters[] = (new filter(
            text::class,
            'fullname',
            new lang_string('programname', 'tool_muprog'),
            $this->get_entity_name(),
            "{$programalias}.name"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            text::class,
            'idnumber',
            new lang_string('programidnumber', 'tool_muprog'),
            $this->get_entity_name(),
            "{$programalias}.idnumber"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            boolean_select::class,
            'publicaccess',
            new lang_string('publicaccess', 'tool_muprog'),
            $this->get_entity_name(),
            "{$programalias}.publicaccess"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            boolean_select::class,
            'archived',
            new lang_string('archived', 'tool_muprog'),
            $this->get_entity_name(),
            "{$programalias}.archived"
        ))
            ->add_joins($this->get_joins());

        return $filters;
    }
}
