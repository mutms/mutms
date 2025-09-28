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

namespace tool_muprog\reportbuilder\local\systemreports;

use tool_muprog\reportbuilder\local\entities\program;
use tool_muprog\reportbuilder\local\entities\allocation;
use tool_muprog\reportbuilder\local\entities\source;
use core_reportbuilder\local\entities\user;
use core_reportbuilder\system_report;
use core_reportbuilder\local\helpers\database;
use core_reportbuilder\local\helpers\user_profile_fields;
use lang_string;
use moodle_url;

/**
 * Embedded program allocations report.
 *
 * @package     tool_muprog
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class allocations extends system_report {
    /** @var \stdClass */
    protected $program;

    /** @var user */
    protected $userentity;
    /** @var allocation */
    protected $allocationentity;
    /** @var source */
    protected $sourceentity;

    #[\Override]
    protected function initialise(): void {
        global $DB;
        // Make sure programid and context match!
        $this->program = $DB->get_record(
            'tool_muprog_program',
            ['id' => $this->get_parameters()['programid'], 'contextid' => $this->get_context()->id],
            '*',
            MUST_EXIST
        );

        $this->allocationentity = new allocation();
        $allocationalias = $this->allocationentity->get_table_alias('tool_muprog_allocation');
        $this->set_main_table('tool_muprog_allocation', $allocationalias);
        $this->add_entity($this->allocationentity);

        $this->sourceentity = new source();
        $sourcealias = $this->sourceentity->get_table_alias('tool_muprog_source');
        $this->add_entity($this->sourceentity);
        $this->add_join("JOIN {tool_muprog_source} {$sourcealias} ON {$sourcealias}.id = {$allocationalias}.sourceid");

        $programentity = new program();
        $programalias = $programentity->get_table_alias('tool_muprog_program');
        $this->add_entity($programentity);
        $this->add_join("JOIN {tool_muprog_program} {$programalias} ON {$programalias}.id = {$allocationalias}.programid");

        $param = database::generate_param_name();
        $this->add_base_condition_sql("{$programalias}.id = :$param", [$param => $this->program->id]);

        $this->userentity = new user();
        $useralias = $this->userentity->get_table_alias('user');
        $this->add_entity($this->userentity);
        $this->add_join("JOIN {user} {$useralias} ON {$useralias}.id = {$allocationalias}.userid");

        $this->add_base_fields("{$allocationalias}.id, {$allocationalias}.sourceid, {$allocationalias}.userid,"
            . " {$allocationalias}.programid, {$allocationalias}.archived, {$sourcealias}.type, "
            . "{$programalias}.contextid, {$programalias}.archived AS programarchived");

        $this->add_columns();
        $this->add_filters();
        $this->add_actions();

        $this->set_downloadable(true);
        $this->set_default_no_results_notice(new lang_string('errornoallocations', 'tool_muprog'));
    }

    #[\Override]
    protected function can_view(): bool {
        return has_capability('tool/muprog:view', $this->get_context());
    }

    /**
     * Adds the columns we want to display in the report.
     */
    public function add_columns(): void {
        $allocationalias = $this->allocationentity->get_table_alias('tool_muprog_allocation');

        $column = $this->userentity->get_column('fullname');
        $column
            ->add_fields("$allocationalias.id")
            ->add_callback(static function (string $fullname, \stdClass $row): string {
                $url = new \moodle_url('/admin/tool/muprog/management/allocation.php', ['id' => $row->id]);
                return \html_writer::link($url, $fullname);
            });
        $this->add_column($column);

        // Include identity field columns.
        $identitycolumns = $this->userentity->get_identity_columns($this->get_context());
        foreach ($identitycolumns as $identitycolumn) {
            $this->add_column($identitycolumn);
        }

        $this->add_column_from_entity('allocation:timestart');
        $this->add_column_from_entity('allocation:timedue');
        $this->add_column_from_entity('allocation:timeend');
        $this->add_column_from_entity('allocation:status');
        $this->add_column_from_entity('source:type');
        $this->add_column_from_entity('allocation:archived');

        $this->set_initial_sort_column('user:fullname', SORT_ASC);
    }

    /**
     * Adds the filters we want to display in the report.
     */
    protected function add_filters(): void {
        $this->add_filter_from_entity('allocation:status');
        $this->add_filter_from_entity('allocation:archived');

        $userentityalias = $this->userentity->get_table_alias('user');

        $filters = [
            'user:fullname',
            'user:firstname',
            'user:lastname',
            'user:suspended',
        ];
        $this->add_filters_from_entities($filters);

        // Add user profile fields filters.
        $userprofilefields = new user_profile_fields($userentityalias . '.id', $this->userentity->get_entity_name());
        foreach ($userprofilefields->get_filters() as $filter) {
            $this->add_filter($filter);
        }
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

        $program = $this->program;

        $url = new moodle_url('/admin/tool/muprog/management/allocation_update.php', ['id' => ':id']);
        $link = new \tool_mulib\output\ajax_form\link($url, new lang_string('allocation_update', 'tool_muprog'), 'i/settings');
        $this->add_action($link->create_report_action()
            ->add_callback(static function (\stdclass $row) use ($program): bool {
                global $DB;
                if (!$row->id) {
                    return false;
                }
                if ($row->archived || $row->programarchived) {
                    return false;
                }
                $sourceclass = \tool_muprog\local\allocation::get_source_classname($row->type);
                if (!$sourceclass) {
                    return false;
                }
                if (!has_capability('tool/muprog:manageallocation', \context::instance_by_id($row->contextid))) {
                    return false;
                }
                $source = $DB->get_record('tool_muprog_source', ['id' => $row->sourceid]);
                $allocation = $DB->get_record('tool_muprog_allocation', ['id' => $row->id]);
                if (!$source || !$allocation) {
                    return false;
                }
                return $sourceclass::is_allocation_update_possible($program, $source, $allocation);
            }));

        $url = new moodle_url('/admin/tool/muprog/management/allocation_delete.php', ['id' => ':id']);
        $link = new \tool_mulib\output\ajax_form\link($url, new lang_string('deleteallocation', 'tool_muprog'), 'i/delete');
        $this->add_action($link->create_report_action(['class' => 'text-danger'])
            ->add_callback(static function (\stdclass $row) use ($program): bool {
                global $DB;
                if (!$row->id) {
                    return false;
                }
                $sourceclass = \tool_muprog\local\allocation::get_source_classname($row->type);
                if (!$sourceclass) {
                    return false;
                }
                if (!has_capability('tool/muprog:manageallocation', \context::instance_by_id($row->contextid))) {
                    return false;
                }
                $source = $DB->get_record('tool_muprog_source', ['id' => $row->sourceid]);
                $allocation = $DB->get_record('tool_muprog_allocation', ['id' => $row->id]);
                if (!$source || !$allocation) {
                    return false;
                }
                return $sourceclass::is_allocation_delete_possible($program, $source, $allocation);
            }));
    }
}
