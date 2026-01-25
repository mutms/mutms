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
use core_reportbuilder\local\report\{column, filter};
use stdClass;

/**
 * Credit framework related completions entity.
 *
 * @package    tool_mutrain
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class completion extends base {
    /** @var array all used custom credits fields */
    protected $creditcustomfields;

    /**
     * Returns training credits custom field.
     *
     * @param int $fieldid
     * @return stdClass|null
     */
    public function get_customfield(int $fieldid): ?stdClass {
        global $DB;

        if (!isset($this->creditcustomfields)) {
            $sql = "SELECT f.id, f.name, c.component, c.area
                      FROM {customfield_field} f
                      JOIN {customfield_category} c ON c.id = f.categoryid
                     WHERE f.type = 'mutrain'";
            $this->creditcustomfields = $DB->get_records_sql($sql);
        }

        return $this->creditcustomfields[$fieldid] ?? null;
    }


    #[\Override]
    protected function get_default_tables(): array {
        return [
            'tool_mutrain_completion',
            'tool_mutrain_field',
            'tool_mutrain_framework',
            'context',
            'tool_mulib_context_map',
            'customfield_data',
        ];
    }

    #[\Override]
    protected function get_default_entity_title(): lang_string {
        return new lang_string('completion', 'tool_mutrain');
    }

    #[\Override]
    public function initialise(): base {
        $dataalias = $this->get_table_alias('customfield_data');
        $completionalias = $this->get_table_alias('tool_mutrain_completion');

        $this->add_join("JOIN {customfield_data} {$dataalias} ON {$dataalias}.fieldid = {$completionalias}.fieldid AND {$dataalias}.instanceid = {$completionalias}.instanceid");

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
        $completionkalias = $this->get_table_alias('tool_mutrain_completion');
        $contextalias = $this->get_table_alias('context');

        return "JOIN {context} {$contextalias} ON {$contextalias}.id = {$completionkalias}.contextid";
    }

    /**
     * Return syntax for joining on the context map table to restrict result to subcontexts.
     *
     * @param \context $context
     * @return string
     */
    public function get_context_map_join(\context $context): string {
        $completionkalias = $this->get_table_alias('tool_mutrain_completion');
        $contextmapalias = $this->get_table_alias('tool_mulib_context_map');

        return "JOIN {tool_mulib_context_map} {$contextmapalias} ON
                     {$contextmapalias}.contextid = $completionkalias.contextid AND {$contextmapalias}.relatedcontextid = {$context->id}";
    }

    /**
     * Returns list of all available columns.
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        $completionalias = $this->get_table_alias('tool_mutrain_completion');
        $dataalias = $this->get_table_alias('customfield_data');

        $columns[] = (new column(
            'type',
            new lang_string('completion_type', 'tool_mutrain'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$completionalias}.fieldid")
            ->set_is_sortable(false)
            ->set_callback(function (?int $value, \stdClass $row): string {
                if (!$row->fieldid) {
                    return '';
                }

                $field = $this->get_customfield($row->fieldid);
                if (!$field) {
                    return get_string('error');
                }

                if ($field->component === 'core_course' && $field->area === 'course') {
                    return get_string('area_core_course_course', 'tool_mutrain');
                } else if ($field->component === 'tool_muprog' && $field->area === 'program') {
                    if (!\tool_mulib\local\mulib::is_muprog_available()) {
                        return get_string('error');
                    }
                    return get_string('area_tool_muprog_program', 'tool_mutrain');
                } else {
                    return get_string('error');
                }
            });

        $columns[] = (new column(
            'field',
            new lang_string('customfield', 'core_customfield'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$completionalias}.fieldid")
            ->set_is_sortable(false)
            ->set_callback(function (?int $value, \stdClass $row): string {
                if (!$row->fieldid) {
                    return '';
                }

                $field = $this->get_customfield($row->fieldid);
                if (!$field) {
                    return get_string('error');
                }

                return format_string($field->name);
            });

        $columns[] = (new column(
            'instance',
            new lang_string('completion_instance', 'tool_mutrain'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$completionalias}.instanceid, {$completionalias}.fieldid, {$completionalias}.userid")
            ->set_is_sortable(false)
            ->set_callback(function (?int $value, \stdClass $row): string {
                global $DB, $USER;

                if (!$row->instanceid) {
                    return '';
                }

                $field = $this->get_customfield($row->fieldid);
                if (!$field) {
                    return get_string('error');
                }

                if ($field->component === 'core_course' && $field->area === 'course') {
                    $name = $DB->get_field('course', 'fullname', ['id' => $row->instanceid]);
                    $context = \context_course::instance($row->instanceid, IGNORE_MISSING);
                    if (!$context || $name === false) {
                        return get_string('error');
                    }
                    $name = format_string($name);
                    if (has_capability('moodle/course:view', $context) || is_enrolled($context)) {
                        $url = new \core\url('/course/view.php', ['id' => $row->instanceid]);
                        $name = \html_writer::link($url, $name);
                    }
                    return $name;
                } else if ($field->component === 'tool_muprog' && $field->area === 'program') {
                    if (!\tool_mulib\local\mulib::is_muprog_available()) {
                        return get_string('error');
                    }
                    $program = $DB->get_record('tool_muprog_program', ['id' => $row->instanceid]);
                    if (!$program) {
                        return get_string('error');
                    }
                    $name = format_string($program->fullname);
                    if (!$program->archived) {
                        if ($row->userid == $USER->id) {
                            $url = new \core\url('/admin/tool/muprog/my/program.php', ['id' => $program->id]);
                            $name = \html_writer::link($url, $name);
                        }
                    }
                    return $name;
                }
                return get_string('error');
            });

        $columns[] = (new column(
            'timecompleted',
            new lang_string('completed'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_fields("{$completionalias}.timecompleted")
            ->set_is_sortable(true)
            ->set_callback(static function (?int $value, \stdClass $row): string {
                if (!$value) {
                    return '';
                }
                return userdate($value, get_string('strftimedatetimeshort'));
            });

        $columns[] = (new column(
            'credits',
            new lang_string('credits', 'tool_mutrain'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_FLOAT)
            ->add_fields("{$dataalias}.decvalue")
            ->set_is_sortable(true)
            ->set_callback(static function (mixed $value, \stdClass $row): string {
                if (!$value) {
                    return '';
                }
                return format_float($row->decvalue, 2, true, true);
            });

        return $columns;
    }

    /**
     * Return list of all available filters.
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        return [];
    }
}
