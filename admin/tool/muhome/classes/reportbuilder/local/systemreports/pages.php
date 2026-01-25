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

namespace tool_muhome\reportbuilder\local\systemreports;

use tool_muhome\reportbuilder\local\entities\page;
use core_reportbuilder\system_report;
use core_reportbuilder\local\report\filter;
use core_reportbuilder\local\filters\boolean_select;
use lang_string;
use core\url;

/**
 * Embedded pages report.
 *
 * @package     tool_muhome
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class pages extends system_report {
    /** @var page */
    protected $pageentity;
    /** @var string */
    protected $pagealias;

    #[\Override]
    protected function initialise(): void {
        $this->pageentity = new page();
        $this->pagealias = $this->pageentity->get_table_alias('tool_muhome_page');

        $this->set_main_table('tool_muhome_page', $this->pagealias);
        $this->add_entity($this->pageentity);

        $this->add_base_fields("{$this->pagealias}.id, {$this->pagealias}.contextid, {$this->pagealias}.status");

        $this->add_join($this->pageentity->get_context_join());

        // Make sure only pages from context and its subcontexts are shown.
        $context = $this->get_context();
        if ($context->contextlevel != CONTEXT_SYSTEM) {
            $this->add_join($this->pageentity->get_context_map_join($context));
        }

        $this->add_columns();
        $this->add_filters();
        $this->add_actions();

        $this->set_downloadable(true);
        $this->set_default_no_results_notice(new lang_string('error_nopages', 'tool_muhome'));
    }

    #[\Override]
    protected function can_view(): bool {
        return has_capability('tool/muhome:view', $this->get_context());
    }

    /**
     * Adds the columns we want to display in the report.
     */
    public function add_columns(): void {
        $columns = [
            'page:priority',
            'page:name',
            'page:context',
            'page:title',
            'page:guestvisible',
            'page:uservisible',
            'page:cohortvisible',
            'page:hiddenbefore',
            'page:hiddenafter',
        ];
        if (\tool_mulib\local\mulib::is_mutenancy_active()) {
            $columns[] = 'page:hiddenfromtenants';
        }
        $columns[] = 'page:status';

        $this->add_columns_from_entities($columns);

        $this->set_initial_sort_column('page:priority', SORT_DESC);
    }

    /**
     * Adds the filters we want to display in the report.
     */
    protected function add_filters(): void {
        $filters = [
            'page:name',
            'page:guestvisible',
            'page:uservisible',
            'page:status',
        ];
        $this->add_filters_from_entities($filters);
        $context = $this->get_context();

        $filter = new filter(
            boolean_select::class,
            'currentcontextonly',
            new lang_string('currentcontextonly', 'tool_muhome'),
            $this->pageentity->get_entity_name(),
            "CASE WHEN {$this->pagealias}.contextid = {$context->id} THEN 1 ELSE 0 END"
        );
        $this->add_filter($filter);
    }

    /**
     * Row class.
     *
     * @param \stdClass $row
     * @return string
     */
    public function get_row_class(\stdClass $row): string {
        return ($row->status != \tool_muhome\local\page::STATUS_ACTIVE) ? 'text-muted' : '';
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

        $url = new url('/admin/tool/muhome/management/page_update.php', ['id' => ':id']);
        $link = new \tool_mulib\output\ajax_form\link($url, get_string('page_update', 'tool_muhome'), 'i/settings');
        $this->add_action($link->create_report_action()
            ->add_callback(static function (\stdclass $row): bool {
                if (!$row->id) {
                    return false;
                }
                $context = \context::instance_by_id($row->contextid, IGNORE_MISSING);
                if (!$context) {
                    return false;
                }

                return has_capability('tool/muhome:manage', $context);
            }));
        $url = new url('/admin/tool/muhome/management/page_move.php', ['id' => ':id']);
        $link = new \tool_mulib\output\ajax_form\link($url, get_string('page_move', 'tool_muhome'), 'i/folder');
        $this->add_action($link->create_report_action()
            ->add_callback(static function (\stdclass $row): bool {
                if (!$row->id) {
                    return false;
                }
                $context = \context::instance_by_id($row->contextid, IGNORE_MISSING);
                if (!$context) {
                    return false;
                }

                return has_capability('tool/muhome:manage', $context);
            }));

        $url = new url('/admin/tool/muhome/management/page_delete.php', ['id' => ':id']);
        $link = new \tool_mulib\output\ajax_form\link($url, get_string('page_delete', 'tool_muhome'), 'i/delete');
        $this->add_action($link->create_report_action(['class' => 'text-danger'])
            ->add_callback(static function (\stdclass $row): bool {
                if (!$row->id) {
                    return false;
                }
                $context = \context::instance_by_id($row->contextid, IGNORE_MISSING);
                if ($context && $row->status == \tool_muhome\local\page::STATUS_ACTIVE) {
                    return false;
                }

                if (!$context) {
                    $context = \context_system::instance();
                }

                return has_capability('tool/muhome:manage', $context);
            }));
    }
}
