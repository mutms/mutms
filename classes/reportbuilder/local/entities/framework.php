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
use core_reportbuilder\local\filters\select;
use core_reportbuilder\local\report\{column, filter};

/**
 * Relation framework entity.
 *
 * @package    tool_murelation
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class framework extends base {
    #[\Override]
    protected function get_default_tables(): array {
        return [
            'tool_murelation_framework',
        ];
    }

    #[\Override]
    protected function get_default_entity_title(): lang_string {
        return new lang_string('framework', 'tool_murelation');
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
        $frameworkalias = $this->get_table_alias('tool_murelation_framework');

        $columns[] = (new column(
            'name',
            new lang_string('framework_name', 'tool_murelation'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$frameworkalias}.name, {$frameworkalias}.id")
            ->set_is_sortable(true)
            ->set_callback(static function (?string $value, \stdClass $row): string {
                if (!$row->id) {
                    return '';
                }
                $context = \context_system::instance();
                $name = format_string($row->name);
                if (has_capability('tool/murelation:viewframeworks', $context)) {
                    $url = new \moodle_url('/admin/tool/murelation/management/framework.php', ['id' => $row->id]);
                    $name = \html_writer::link($url, $name);
                }
                return $name;
            });

        $columns[] = (new column(
            'idnumber',
            new lang_string('framework_idnumber', 'tool_murelation'),
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
            'uimode',
            new lang_string('framework_uimode', 'tool_murelation'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_fields("{$frameworkalias}.uimode")
            ->set_is_sortable(true)
            ->set_callback(static function (?string $value, \stdClass $row): string {
                if ($value == \tool_murelation\local\framework::UIMODE_SUPERVISORS) {
                    return get_string('framework_uimode_supervisors', 'tool_murelation');
                } else if ($value == \tool_murelation\local\framework::UIMODE_TEAMS) {
                    return get_string('framework_uimode_teams', 'tool_murelation');
                } else {
                    return '';
                }
            });

        $columns[] = (new column(
            'supervisortitle',
            new lang_string('supervisortitle', 'tool_murelation'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$frameworkalias}.supervisortitle")
            ->set_is_sortable(true)
            ->set_callback(static function (?string $value, \stdClass $row): string {
                return format_string($row->supervisortitle);
            });

        $columns[] = (new column(
            'subordinatetitle',
            new lang_string('subordinatetitle', 'tool_murelation'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$frameworkalias}.subordinatetitle")
            ->set_is_sortable(true)
            ->set_callback(static function (?string $value, \stdClass $row): string {
                return format_string($row->subordinatetitle);
            });

        $columns[] = (new column(
            'supervisorrole',
            new lang_string('framework_supervisorrole', 'tool_murelation'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$frameworkalias}.supervisorroleid")
            ->set_is_sortable(false)
            ->set_callback(static function (?string $value, \stdClass $row): string {
                global $DB;
                if (!$row->supervisorroleid) {
                    return '';
                }
                $role = $DB->get_record('role', ['id' => $row->supervisorroleid]);
                if (!$role) {
                    return get_string('error');
                }
                return role_get_name($role, ROLENAME_ORIGINAL);
            });

        return $columns;
    }

    /**
     * Return list of all available filters.
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $frameworkalias = $this->get_table_alias('tool_murelation_framework');

        $filters[] = (new filter(
            select::class,
            'uimode',
            new lang_string('framework_uimode', 'tool_murelation'),
            $this->get_entity_name(),
            "{$frameworkalias}.uimode"
        ))
            ->add_joins($this->get_joins())
            ->set_options([
                \tool_murelation\local\framework::UIMODE_SUPERVISORS => get_string('framework_uimode_supervisors', 'tool_murelation'),
                \tool_murelation\local\framework::UIMODE_TEAMS => get_string('framework_uimode_teams', 'tool_murelation'),
            ]);

        $filters[] = (new filter(
            text::class,
            'name',
            new lang_string('framework_name', 'tool_murelation'),
            $this->get_entity_name(),
            "{$frameworkalias}.name"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            text::class,
            'idnumber',
            new lang_string('framework_idnumber', 'tool_murelation'),
            $this->get_entity_name(),
            "{$frameworkalias}.idnumber"
        ))
            ->add_joins($this->get_joins());

        return $filters;
    }
}
