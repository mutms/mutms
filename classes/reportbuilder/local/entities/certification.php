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
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\report\{column, filter};
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\filters\boolean_select;

/**
 * Certification entity.
 *
 * @package     tool_mucertify
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class certification extends base {
    #[\Override]
    protected function get_default_tables(): array {
        return [
            'tool_mucertify_certification',
            'context',
            'tool_mulib_context_map',
        ];
    }

    #[\Override]
    protected function get_default_entity_title(): lang_string {
        return new lang_string('certification', 'tool_mucertify');
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
        $certificationalias = $this->get_table_alias('tool_mucertify_certification');
        $contextalias = $this->get_table_alias('context');

        return "JOIN {context} {$contextalias} ON {$contextalias}.id = {$certificationalias}.contextid";
    }

    /**
     * Return syntax for joining on the context map table to restrict result to subcontexts.
     *
     * @param \context $context
     * @return string
     */
    public function get_context_map_join(\context $context): string {
        $certificationalias = $this->get_table_alias('tool_mucertify_certification');
        $contextmapalias = $this->get_table_alias('tool_mulib_context_map');

        return "JOIN {tool_mulib_context_map} {$contextmapalias} ON
                     {$contextmapalias}.contextid = {$certificationalias}.contextid AND {$contextmapalias}.relatedcontextid = {$context->id}";
    }

    /**
     * Returns list of all available columns.
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        $certificationalias = $this->get_table_alias('tool_mucertify_certification');

        $columns[] = (new column(
            'fullname',
            new lang_string('certificationname', 'tool_mucertify'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$certificationalias}.id, {$certificationalias}.fullname, {$certificationalias}.contextid")
            ->set_is_sortable(true)
            ->set_callback(static function (?string $value, \stdClass $row): string {
                if (!$row->id) {
                    return '';
                }
                $context = \context::instance_by_id($row->contextid);
                $name = format_string($row->fullname);
                if (has_capability('tool/mucertify:view', $context)) {
                    $url = new \core\url('/admin/tool/mucertify/management/certification.php', ['id' => $row->id]);
                    $name = \html_writer::link($url, $name);
                }
                return $name;
            });

        $columns[] = (new column(
            'idnumber',
            new lang_string('certificationidnumber', 'tool_mucertify'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$certificationalias}.idnumber")
            ->set_is_sortable(true)
            ->set_callback(static function (?string $value, \stdClass $row): string {
                return s($row->idnumber);
            });

        $columns[] = (new column(
            'publicaccess',
            new lang_string('publicaccess', 'tool_mucertify'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->add_fields("{$certificationalias}.publicaccess, {$certificationalias}.id, {$certificationalias}.contextid")
            ->set_is_sortable(true)
            ->set_callback([format::class, 'boolean_as_text'])
            ->add_callback(static function (string $value, \stdClass $row): string {
                $context = \context::instance_by_id($row->contextid);
                if (!has_capability('tool/mucertify:view', $context)) {
                    return $value;
                }
                $url = new \core\url('/admin/tool/mucertify/management/certification_visibility.php', ['id' => $row->id]);
                $value = \html_writer::link($url, $value);
                return $value;
            });

        $columns[] = (new column(
            'archived',
            new lang_string('archived', 'tool_mucertify'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->add_fields("{$certificationalias}.archived")
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
            ->add_fields("{$certificationalias}.contextid")
            ->set_is_sortable(false)
            ->set_callback(static function (?int $value, \stdClass $row): string {
                global $PAGE;
                if (!$row->contextid) {
                    return '';
                }
                $context = \context::instance_by_id($row->contextid);
                $name = $context->get_context_name(false);

                if (!has_capability('tool/mucertify:view', $context)) {
                    return $name;
                }
                $url = new \core\url('/admin/tool/mucertify/management/index.php', ['contextid' => $context->id]);
                if ($url->compare($PAGE->url)) {
                    return $name;
                }
                $name = \html_writer::link($url, $name);
                return $name;
            });

        $columns[] = (new column(
            'assignmentcount',
            new lang_string('assignments', 'tool_mucertify'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_field('(' . "SELECT COUNT('x')
                                 FROM {tool_mucertify_assignment} a
                                WHERE a.certificationid = {$certificationalias}.id" . ')', 'assignmentcount')
            ->add_field("{$certificationalias}.id")
            ->add_field("{$certificationalias}.contextid")
            ->set_is_sortable(true)
            ->set_disabled_aggregation_all()
            ->set_callback(static function (?int $value, \stdClass $row): string {
                $count = $row->assignmentcount;
                $context = \context::instance_by_id($row->contextid);
                if (has_capability('tool/mucertify:view', $context)) {
                    $url = new \core\url('/admin/tool/mucertify/management/certification_users.php', ['id' => $row->id]);
                    $count = \html_writer::link($url, $count);
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
        $certificationalias = $this->get_table_alias('tool_mucertify_certification');

        $filters[] = (new filter(
            text::class,
            'fullname',
            new lang_string('certificationname', 'tool_mucertify'),
            $this->get_entity_name(),
            "{$certificationalias}.name"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            text::class,
            'idnumber',
            new lang_string('certificationidnumber', 'tool_mucertify'),
            $this->get_entity_name(),
            "{$certificationalias}.idnumber"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            boolean_select::class,
            'publicaccess',
            new lang_string('publicaccess', 'tool_mucertify'),
            $this->get_entity_name(),
            "{$certificationalias}.publicaccess"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            boolean_select::class,
            'archived',
            new lang_string('archived', 'tool_mucertify'),
            $this->get_entity_name(),
            "{$certificationalias}.archived"
        ))
            ->add_joins($this->get_joins());

        return $filters;
    }
}
