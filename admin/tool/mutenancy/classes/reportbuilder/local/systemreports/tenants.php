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

namespace tool_mutenancy\reportbuilder\local\systemreports;

use tool_mutenancy\reportbuilder\local\entities\tenant;
use core_course\reportbuilder\local\entities\course_category;
use core_reportbuilder\system_report;
use moodle_url;
use lang_string;
use tool_mutenancy\local\tenancy;

/**
 * Embedded tenants report.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tenants extends system_report {
    #[\Override]
    protected function initialise(): void {
        $tenantentity = new tenant();
        $tenantalias = $tenantentity->get_table_alias('tool_mutenancy_tenant');

        $this->set_main_table('tool_mutenancy_tenant', $tenantalias);
        $this->add_entity($tenantentity);

        $this->add_base_fields("{$tenantalias}.id, {$tenantalias}.archived");

        $categoryentity = new course_category();
        $categoryalias = $categoryentity->get_table_alias('course_categories');

        $this->add_entity($categoryentity->add_join(
            "JOIN {course_categories} {$categoryalias} ON {$categoryalias}.id = {$tenantalias}.categoryid"
        ));

        $this->add_columns();
        $this->add_filters();
        $this->add_actions();

        $this->set_downloadable(true);
        $this->set_initial_sort_column('tenant:name', SORT_ASC);
    }

    #[\Override]
    protected function can_view(): bool {
        if ($this->get_context()->contextlevel != CONTEXT_SYSTEM) {
            return false;
        }
        if (isguestuser() || !isloggedin()) {
            return false;
        }
        return has_capability('tool/mutenancy:view', \context_system::instance());
    }

    /**
     * Adds the columns we want to display in the report.
     */
    public function add_columns(): void {
        $columns = [
            'tenant:name',
            'tenant:idnumber',
            'course_category:namewithlink',
            'tenant:usercount',
            'tenant:memberlimit',
            'tenant:archived',
            'tenant:loginurl',
        ];
        $this->add_columns_from_entities($columns);

        $this->get_column('course_category:namewithlink')
            ->set_title(new \lang_string('tenant_category', 'tool_mutenancy'))
            ->set_callback(static function ($ignored, \stdClass $category): string {
                if (empty($category->id)) {
                    return '';
                }
                $context = \context_coursecat::instance($category->id);
                $url = null;
                if (has_capability('moodle/category:manage', $context)) {
                    $url = new \moodle_url('/course/management.php', ['categoryid' => $category->id]);
                } else if (has_capability('moodle/category:viewcourselist', $context)) {
                    $url = new \moodle_url('/course/index.php', ['categoryid' => $category->id]);
                }
                $name = format_string($category->name, true, ['context' => $context]);
                if ($url) {
                    $name = \html_writer::link($url, $name);
                }
                return $name;
            });
    }

    /**
     * Adds the filters we want to display in the report.
     */
    protected function add_filters(): void {
        $filters = [
            'tenant:name',
            'tenant:idnumber',
            'tenant:archived',
        ];
        $this->add_filters_from_entities($filters);
    }

    /**
     * Row class
     *
     * @param \stdClass $row
     * @return string
     */
    public function get_row_class(\stdClass $row): string {
        return $row->archived ? 'text-muted' : '';
    }

    /**
     * Add the system report actions. An extra column will be appended to each row, containing all actions added here
     *
     * Note the use of ":id" placeholder which will be substituted according to actual values in the row
     */
    protected function add_actions(): void {
        global $SCRIPT;

        // Report builder download script is missing NO_DEBUG_DISPLAY
        // and template rendering is changing session after it is closed,
        // add a hacky workaround for now.
        if ($SCRIPT === '/reportbuilder/download.php') {
            return;
        }

        $url = new moodle_url('/admin/tool/mutenancy/management/tenant_update.php', ['id' => ':id']);
        $link = new \tool_mulib\output\ajax_form\link($url, new lang_string('edit', 'moodle'), 't/edit');
        $link->set_modal_title(tenancy::get_tenant_string('tenant_update'));
        $link->set_form_size('lg');
        $this->add_action($link->create_report_action()
            ->add_callback(static function (\stdclass $row): bool {
                return has_capability('tool/mutenancy:admin', \context_tenant::instance($row->id));
            }));
    }
}
