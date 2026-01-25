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
use tool_mucertify\reportbuilder\local\entities\request;
use tool_mucertify\reportbuilder\local\entities\source;
use core_reportbuilder\local\entities\user;
use core_reportbuilder\system_report;
use core_reportbuilder\local\helpers\database;
use core_reportbuilder\local\helpers\user_profile_fields;
use lang_string;

/**
 * Embedded certification requests report.
 *
 * @package     tool_mucertify
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class requests extends system_report {
    /** @var \stdClass */
    protected $certification;

    /** @var user */
    protected $userentity;
    /** @var request */
    protected $requestentity;
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

        $this->requestentity = new request();
        $requestalias = $this->requestentity->get_table_alias('tool_mucertify_request');
        $this->set_main_table('tool_mucertify_request', $requestalias);
        $this->add_entity($this->requestentity);

        $this->sourceentity = new source();
        $sourcealias = $this->sourceentity->get_table_alias('tool_mucertify_source');
        $this->add_entity($this->sourceentity);
        $this->add_join("JOIN {tool_mucertify_source} {$sourcealias} ON {$sourcealias}.id = {$requestalias}.sourceid");

        $certificationentity = new certification();
        $certificationalias = $certificationentity->get_table_alias('tool_mucertify_certification');
        $this->add_entity($certificationentity);
        $this->add_join("JOIN {tool_mucertify_certification} {$certificationalias} ON {$certificationalias}.id = {$sourcealias}.certificationid");

        $param = database::generate_param_name();
        $this->add_base_condition_sql("{$certificationalias}.id = :$param", [$param => $this->certification->id]);

        $this->userentity = new user();
        $useralias = $this->userentity->get_table_alias('user');
        $this->add_entity($this->userentity);
        $this->add_join("JOIN {user} {$useralias} ON {$useralias}.id = {$requestalias}.userid");

        $this->add_base_fields("{$requestalias}.id, {$requestalias}.sourceid, {$requestalias}.userid, {$sourcealias}.certificationid,"
            . " {$sourcealias}.type, {$certificationalias}.contextid, {$requestalias}.timerejected");

        $this->add_columns();
        $this->add_filters();
        $this->add_actions();

        $this->set_downloadable(true);
        $this->set_default_no_results_notice(new lang_string('errornorequests', 'tool_mucertify'));
    }

    #[\Override]
    protected function can_view(): bool {
        return has_capability('tool/mucertify:view', $this->get_context());
    }

    /**
     * Adds the columns we want to display in the report.
     */
    public function add_columns(): void {
        $this->add_column_from_entity('user:fullnamewithpicturelink');

        // Include identity field columns.
        $identitycolumns = $this->userentity->get_identity_columns($this->get_context());
        foreach ($identitycolumns as $identitycolumn) {
            $this->add_column($identitycolumn);
        }

        $this->add_column_from_entity('request:timerequested');
        $this->add_column_from_entity('request:timerejected');

        $this->set_initial_sort_column('user:fullnamewithpicturelink', SORT_ASC);
    }

    /**
     * Adds the filters we want to display in the report.
     */
    protected function add_filters(): void {
        $this->add_filter_from_entity('request:status');

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
        return $row->timerejected ? 'text-muted' : '';
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

        $url = new \core\url('/admin/tool/mucertify/management/source_approval_approve.php', ['id' => ':id']);
        $link = new \tool_mulib\output\ajax_form\link($url, get_string('source_approval_requestapprove', 'tool_mucertify'), 'requestapprove', 'tool_mucertify');
        $this->add_action($link->create_report_action()
            ->add_callback(static function (\stdclass $row) use ($certification): bool {
                global $DB;
                if (!$row->id) {
                    return false;
                }
                if ($row->timerejected || $certification->archived) {
                    return false;
                }
                if (!has_capability('tool/mucertify:assign', \context::instance_by_id($row->contextid))) {
                    return false;
                }
                if ($DB->record_exists('tool_mucertify_assignment', ['certificationid' => $row->certificationid, 'userid' => $row->userid])) {
                    return false;
                }
                return true;
            }));

        $url = new \core\url('/admin/tool/mucertify/management/source_approval_reject.php', ['id' => ':id']);
        $link = new \tool_mulib\output\ajax_form\link($url, get_string('source_approval_requestreject', 'tool_mucertify'), 'requestreject', 'tool_mucertify');
        $this->add_action($link->create_report_action()
            ->add_callback(static function (\stdclass $row) use ($certification): bool {
                if (!$row->id) {
                    return false;
                }
                if ($row->timerejected) {
                    return false;
                }
                if (!has_capability('tool/mucertify:assign', \context::instance_by_id($row->contextid))) {
                    return false;
                }
                return true;
            }));

        $url = new \core\url('/admin/tool/mucertify/management/source_approval_delete.php', ['id' => ':id']);
        $link = new \tool_mulib\output\ajax_form\link($url, get_string('source_approval_requestdelete', 'tool_mucertify'), 'i/delete');
        $this->add_action($link->create_report_action(['class' => 'text-danger'])
            ->add_callback(static function (\stdclass $row) use ($certification): bool {
                global $DB;
                if (!$row->id) {
                    return false;
                }
                if (!has_capability('tool/mucertify:assign', \context::instance_by_id($row->contextid))) {
                    return false;
                }
                if ($row->timerejected) {
                    return true;
                }
                if ($DB->record_exists('tool_mucertify_assignment', ['certificationid' => $row->certificationid, 'userid' => $row->userid])) {
                    return true;
                }
                return false;
            }));
    }
}
