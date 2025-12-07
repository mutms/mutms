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

namespace tool_mulib\reportbuilder\local\systemreports;

use tool_mulib\reportbuilder\local\entities\server;
use tool_mulib\reportbuilder\local\entities\query;
use core_reportbuilder\system_report;
use moodle_url;
use lang_string;

/**
 * Embedded external database queries report.
 *
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class queries extends system_report {
    /** @var query */
    protected $queryentity;
    /** @var server */
    protected $serverentity;

    #[\Override]
    protected function initialise(): void {
        $this->queryentity = new query();
        $queryalias = $this->queryentity->get_table_alias('tool_mulib_extdb_query');

        $this->set_main_table('tool_mulib_extdb_query', $queryalias);
        $this->add_entity($this->queryentity);

        $this->serverentity = new server();
        $serveralias = $this->serverentity->get_table_alias('tool_mulib_extdb_server');
        $this->add_entity($this->serverentity);
        $this->add_join("JOIN {tool_mulib_extdb_server} {$serveralias} ON {$serveralias}.id = {$queryalias}.serverid");

        $this->add_base_fields("{$queryalias}.id, {$queryalias}.component, {$queryalias}.type");

        $this->add_columns();
        $this->add_filters();
        $this->add_actions();

        $this->set_downloadable(false);
        $this->set_initial_sort_column('query:name', SORT_ASC);
    }

    #[\Override]
    protected function can_view(): bool {
        if ($this->get_context()->contextlevel != CONTEXT_SYSTEM) {
            return false;
        }
        if (isguestuser() || !isloggedin()) {
            return false;
        }
        return has_capability('tool/mulib:useextdb', \context_system::instance());
    }

    /**
     * Adds the columns we want to display in the report.
     */
    public function add_columns(): void {
        $columns = [
            'query:name',
            'query:context',
        ];
        $this->add_columns_from_entities($columns);

        $column = $this->serverentity->get_column('name')
            ->set_title(new lang_string('extdb_server', 'tool_mulib'));
        $this->add_column($column);

        $columns = [
            'query:sqlquery',
            'query:note',
        ];
        $this->add_columns_from_entities($columns);
    }

    /**
     * Adds the filters we want to display in the report.
     */
    protected function add_filters(): void {
        $filters = [
            'query:name',
        ];
        $this->add_filters_from_entities($filters);

        $filter = $this->serverentity->get_filter('name');
        $filter->set_header(new lang_string('extdb_server', 'tool_mulib'));
        $this->add_filter($filter);
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

        $url = new moodle_url('/admin/tool/mulib/extdb/query_update.php', ['id' => ':id']);
        $link = new \tool_mulib\output\ajax_form\link($url, new lang_string('edit'), 't/edit');
        $link->set_modal_title(get_string('extdb_query_update', 'tool_mulib'));
        $link->set_form_size('xl');
        $this->add_action($link->create_report_action()
            ->add_callback(static function (\stdclass $row): bool {
                return has_capability('moodle/site:config', \context_system::instance());
            }));

        $url = new moodle_url('/admin/tool/mulib/extdb/query_delete.php', ['id' => ':id']);
        $link = new \tool_mulib\output\ajax_form\link($url, new lang_string('delete'), 't/delete');
        $link->set_modal_title(get_string('extdb_query_delete', 'tool_mulib'));
        $link->set_form_size('sm');
        $this->add_action($link->create_report_action(['class' => 'text-danger'])
            ->add_callback(static function (\stdclass $row): bool {
                if (!$row->id) {
                    return false;
                }
                $qman = \core\di::get(\tool_mulib\local\extdb\query_manager::class);
                $classname = $qman->get_class($row->component, $row->type);

                if ($classname && $classname::is_query_used($row->id)) {
                    return false;
                }
                return has_capability('moodle/site:config', \context_system::instance());
            }));
    }
}
