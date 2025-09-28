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

namespace tool_mutenancy\reportbuilder\local\entities;

use lang_string;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\report\{column, filter};
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\filters\boolean_select;

/**
 * Tenant entity.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tenant extends base {
    #[\Override]
    protected function get_default_tables(): array {
        return [
            'tool_mutenancy_tenant',
        ];
    }

    #[\Override]
    protected function get_default_entity_title(): lang_string {
        return new lang_string('tenant', 'tool_mutenancy');
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
        $tenantalias = $this->get_table_alias('tool_mutenancy_tenant');

        $columns[] = (new column(
            'name',
            new lang_string('tenant_name', 'tool_mutenancy'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$tenantalias}.id, {$tenantalias}.name")
            ->set_is_sortable(true)
            ->set_callback(static function (?string $value, \stdClass $row): string {
                if (!$row->id) {
                    return '';
                }
                $context = \context_tenant::instance($row->id);
                $name = format_string($row->name);
                if (has_capability('tool/mutenancy:view', $context)) {
                    $url = new \moodle_url('/admin/tool/mutenancy/tenant.php', ['id' => $row->id]);
                    $name = \html_writer::link($url, $name);
                }
                return $name;
            });

        $columns[] = (new column(
            'idnumber',
            new lang_string('tenant_idnumber', 'tool_mutenancy'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$tenantalias}.idnumber")
            ->set_is_sortable(true)
            ->set_callback(static function (?string $value, \stdClass $row): string {
                return s($row->idnumber);
            });

        $columns[] = (new column(
            'sitefullname',
            new lang_string('tenant_sitefullname', 'tool_mutenancy'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$tenantalias}.sitefullname")
            ->set_is_sortable(true)
            ->set_callback(static function (?string $value, \stdClass $row): string {
                return s($row->sitefullname);
            });

        $columns[] = (new column(
            'siteshortname',
            new lang_string('tenant_siteshortname', 'tool_mutenancy'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$tenantalias}.siteshortname")
            ->set_is_sortable(true)
            ->set_callback(static function (?string $value, \stdClass $row): string {
                return s($row->siteshortname);
            });

        $columns[] = (new column(
            'archived',
            new lang_string('tenant_archived', 'tool_mutenancy'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->add_fields("{$tenantalias}.archived")
            ->set_is_sortable(true)
            ->set_callback([format::class, 'boolean_as_text']);

        $columns[] = (new column(
            'memberlimit',
            new lang_string('tenant_memberlimit', 'tool_mutenancy'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_field("(SELECT COUNT('x')
                            FROM {user} tm
                           WHERE tm.deleted = 0 AND tm.tenantid = {$tenantalias}.id)", 'membercount')
            ->add_field("{$tenantalias}.id")
            ->add_field("{$tenantalias}.memberlimit")
            ->set_is_sortable(true)
            ->set_disabled_aggregation_all()
            ->set_callback(static function (?int $value, \stdClass $row): string {
                if (!$row->memberlimit) {
                    return '';
                }
                $count = "$row->membercount / $row->memberlimit";
                return $count;
            });

        $columns[] = (new column(
            'usercount',
            new lang_string('tenant_users', 'tool_mutenancy'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_field("(SELECT COUNT('x')
                            FROM {user} tuser
                       LEFT JOIN {cohort_members} cm ON cm.cohortid = {$tenantalias}.assoccohortid AND cm.userid = tuser.id
                           WHERE (tuser.deleted = 0 AND tuser.tenantid IS NULL AND cm.id IS NOT NULL)
                                 OR (tuser.deleted = 0 AND tuser.tenantid = {$tenantalias}.id)
                         )", 'usercount')
            ->add_field("{$tenantalias}.id")
            ->set_is_sortable(true)
            ->set_disabled_aggregation_all()
            ->set_callback(static function (?int $value, \stdClass $row): string {
                $count = $row->usercount;
                $context = \context_tenant::instance($row->id);
                if (has_capability('tool/mutenancy:view', $context)) {
                    $url = new \moodle_url('/admin/tool/mutenancy/tenant_users.php', ['id' => $row->id]);
                    $count = \html_writer::link($url, $count);
                }
                return $count;
            });

        $columns[] = (new column(
            'loginurl',
            new lang_string('tenant_loginurl', 'tool_mutenancy'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$tenantalias}.idnumber, {$tenantalias}.archived, {$tenantalias}.id")
            ->set_is_sortable(false)
            ->set_callback(static function (?string $value, \stdClass $row): string {
                // NOTE: things go wrong when reportbuilder downloads colum that uses templates...
                global $OUTPUT, $SCRIPT;
                if ($row->archived || $row->idnumber === null) {
                    return '';
                }

                // Report builder download script is missing NO_DEBUG_DISPLAY
                // and template rendering is changing session after it is closed,
                // add a hacky workaround for now.
                if ($SCRIPT === '/reportbuilder/download.php') {
                    $url = \tool_mutenancy\local\tenant::get_login_url($row->id);
                    if ($url) {
                        return $url->out(false);
                    } else {
                        return '';
                    }
                }

                $loginurl = new \tool_mutenancy\output\loginurl($row->id);
                return $OUTPUT->render($loginurl);
            });

        return $columns;
    }

    /**
     * Return list of all available filters.
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $tenantalias = $this->get_table_alias('tool_mutenancy_tenant');

        $filters[] = (new filter(
            text::class,
            'name',
            new lang_string('tenant_name', 'tool_mutenancy'),
            $this->get_entity_name(),
            "{$tenantalias}.name"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            text::class,
            'idnumber',
            new lang_string('tenant_idnumber', 'tool_mutenancy'),
            $this->get_entity_name(),
            "{$tenantalias}.idnumber"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            boolean_select::class,
            'archived',
            new lang_string('tenant_archived', 'tool_mutenancy'),
            $this->get_entity_name(),
            "{$tenantalias}.archived"
        ))
            ->add_joins($this->get_joins());

        return $filters;
    }
}
