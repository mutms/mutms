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

namespace tool_mucertify\reportbuilder\local\systemreports;

use tool_mucertify\reportbuilder\local\entities\certification;
use tool_mucertify\reportbuilder\local\entities\assignment;
use tool_mucertify\reportbuilder\local\entities\source;
use core_reportbuilder\local\entities\user;
use core_reportbuilder\system_report;
use core_reportbuilder\local\helpers\database;
use core_reportbuilder\local\helpers\user_profile_fields;
use lang_string;

/**
 * Embedded certification assignments report.
 *
 * @package     tool_mucertify
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class assignments extends system_report {
    /** @var \stdClass */
    protected $certification;

    /** @var user */
    protected $userentity;
    /** @var assignment */
    protected $assignmententity;
    /** @var source */
    protected $sourceentity;

    #[\Override]
    protected function initialise(): void {
        global $DB;
        // Make sure certificationid and context match!
        $this->certification = $DB->get_record(
            'tool_mucertify_certification',
            ['id' => $this->get_parameters()['certificationid'], 'contextid' => $this->get_context()->id],
            '*',
            MUST_EXIST
        );

        $this->assignmententity = new assignment();
        $assignmentalias = $this->assignmententity->get_table_alias('tool_mucertify_assignment');
        $this->set_main_table('tool_mucertify_assignment', $assignmentalias);
        $this->add_entity($this->assignmententity);

        $this->sourceentity = new source();
        $sourcealias = $this->sourceentity->get_table_alias('tool_mucertify_source');
        $this->add_entity($this->sourceentity);
        $this->add_join("JOIN {tool_mucertify_source} {$sourcealias} ON {$sourcealias}.id = {$assignmentalias}.sourceid");

        $certificationentity = new certification();
        $certificationalias = $certificationentity->get_table_alias('tool_mucertify_certification');
        $this->add_entity($certificationentity);
        $this->add_join("JOIN {tool_mucertify_certification} {$certificationalias} ON {$certificationalias}.id = {$assignmentalias}.certificationid");

        $param = database::generate_param_name();
        $this->add_base_condition_sql("{$certificationalias}.id = :$param", [$param => $this->certification->id]);

        $this->userentity = new user();
        $useralias = $this->userentity->get_table_alias('user');
        $this->add_entity($this->userentity);
        $this->add_join("JOIN {user} {$useralias} ON {$useralias}.id = {$assignmentalias}.userid");

        $this->add_base_fields("{$assignmentalias}.id, {$assignmentalias}.sourceid, {$assignmentalias}.userid,"
            . " {$assignmentalias}.certificationid, {$assignmentalias}.archived, {$sourcealias}.type, "
            . "{$certificationalias}.contextid, {$certificationalias}.archived AS certificationarchived");

        $this->add_columns();
        $this->add_filters();
        $this->add_actions();

        $this->set_downloadable(true);
        $this->set_default_no_results_notice(new lang_string('errornoassignments', 'tool_mucertify'));
    }

    #[\Override]
    protected function can_view(): bool {
        return has_capability('tool/mucertify:view', $this->get_context());
    }

    /**
     * Adds the columns we want to display in the report.
     */
    public function add_columns(): void {
        $assignmentalias = $this->assignmententity->get_table_alias('tool_mucertify_assignment');

        $column = $this->userentity->get_column('fullname');
        $column
            ->add_fields("$assignmentalias.id")
            ->add_callback(static function (string $fullname, \stdClass $row): string {
                $url = new \core\url('/admin/tool/mucertify/management/assignment.php', ['id' => $row->id]);
                return \html_writer::link($url, $fullname);
            });
        $this->add_column($column);

        // Include identity field columns.
        $identitycolumns = $this->userentity->get_identity_columns($this->get_context());
        foreach ($identitycolumns as $identitycolumn) {
            $this->add_column($identitycolumn);
        }

        $this->add_column_from_entity('assignment:timecertifiedfrom');
        $this->add_column_from_entity('assignment:timecertifieduntil');
        $this->add_column_from_entity('source:type');
        $this->add_column_from_entity('assignment:archived');
        $this->add_column_from_entity('assignment:status');

        $this->set_initial_sort_column('user:fullname', SORT_ASC);
    }

    /**
     * Adds the filters we want to display in the report.
     */
    protected function add_filters(): void {
        $this->add_filter_from_entity('assignment:status');
        $this->add_filter_from_entity('assignment:archived');
        $this->add_filter_from_entity('assignment:timecertifiedfrom');
        $this->add_filter_from_entity('assignment:timecertifieduntil');

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

        $certification = $this->certification;

        $url = new \core\url('/admin/tool/mucertify/management/assignment_update.php', ['id' => ':id']);
        $link = new \tool_mulib\output\ajax_form\link($url, get_string('assignment_update', 'tool_mucertify'), 'i/settings');
        $this->add_action($link->create_report_action()
            ->add_callback(static function (\stdclass $row) use ($certification): bool {
                global $DB;
                if (!$row->id) {
                    return false;
                }
                if ($row->certificationarchived) {
                    return false;
                }
                if ($row->archived) {
                    return false;
                }
                if (!has_capability('tool/mucertify:admin', \context::instance_by_id($row->contextid))) {
                    return false;
                }
                $sourceclass = \tool_mucertify\local\assignment::get_source_classname($row->type);
                if (!$sourceclass) {
                    return false;
                }
                $source = $DB->get_record('tool_mucertify_source', ['id' => $row->sourceid]);
                $assignment = $DB->get_record('tool_mucertify_assignment', ['id' => $row->id]);
                if (!$source || !$assignment) {
                    return false;
                }
                return $sourceclass::is_assignment_update_possible($certification, $source, $assignment);
            }));

        $url = new \core\url('/admin/tool/mucertify/management/assignment_archive.php', ['id' => ':id']);
        $link = new \tool_mulib\output\ajax_form\link($url, get_string('assignment_archive', 'tool_mucertify'), 'i/lock');
        $this->add_action($link->create_report_action()
            ->add_callback(static function (\stdclass $row) use ($certification): bool {
                global $DB;
                if (!$row->id) {
                    return false;
                }
                if ($row->certificationarchived) {
                    return false;
                }
                if ($row->archived) {
                    return false;
                }
                if (!has_capability('tool/mucertify:unassign', \context::instance_by_id($row->contextid))) {
                    return false;
                }
                $sourceclass = \tool_mucertify\local\assignment::get_source_classname($row->type);
                if (!$sourceclass) {
                    return false;
                }
                $source = $DB->get_record('tool_mucertify_source', ['id' => $row->sourceid]);
                $assignment = $DB->get_record('tool_mucertify_assignment', ['id' => $row->id]);
                if (!$source || !$assignment) {
                    return false;
                }
                return $sourceclass::is_assignment_archive_possible($certification, $source, $assignment);
            }));

        $url = new \core\url('/admin/tool/mucertify/management/assignment_restore.php', ['id' => ':id']);
        $link = new \tool_mulib\output\ajax_form\link($url, get_string('assignment_restore', 'tool_mucertify'), 'i/unlock');
        $this->add_action($link->create_report_action()
            ->add_callback(static function (\stdclass $row) use ($certification): bool {
                global $DB;
                if (!$row->id) {
                    return false;
                }
                if ($row->certificationarchived) {
                    return false;
                }
                if (!$row->archived) {
                    return false;
                }
                if (!has_capability('tool/mucertify:assign', \context::instance_by_id($row->contextid))) {
                    return false;
                }
                $sourceclass = \tool_mucertify\local\assignment::get_source_classname($row->type);
                if (!$sourceclass) {
                    return false;
                }
                $source = $DB->get_record('tool_mucertify_source', ['id' => $row->sourceid]);
                $assignment = $DB->get_record('tool_mucertify_assignment', ['id' => $row->id]);
                if (!$source || !$assignment) {
                    return false;
                }
                return $sourceclass::is_assignment_restore_possible($certification, $source, $assignment);
            }));

        $url = new \core\url('/admin/tool/mucertify/management/assignment_delete.php', ['id' => ':id']);
        $link = new \tool_mulib\output\ajax_form\link($url, get_string('assignment_delete', 'tool_mucertify'), 'i/delete');
        $this->add_action($link->create_report_action(['class' => 'text-danger'])
            ->add_callback(static function (\stdclass $row) use ($certification): bool {
                global $DB;
                if (!$row->id) {
                    return false;
                }
                if ($row->certificationarchived) {
                    return false;
                }
                if (!$row->archived) {
                    return false;
                }
                if (!has_capability('tool/mucertify:unassign', \context::instance_by_id($row->contextid))) {
                    return false;
                }
                $sourceclass = \tool_mucertify\local\assignment::get_source_classname($row->type);
                if (!$sourceclass) {
                    return false;
                }
                $source = $DB->get_record('tool_mucertify_source', ['id' => $row->sourceid]);
                $assignment = $DB->get_record('tool_mucertify_assignment', ['id' => $row->id]);
                if (!$source || !$assignment) {
                    return false;
                }
                return $sourceclass::is_assignment_delete_possible($certification, $source, $assignment);
            }));
    }
}
