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
use moodle_url;

/**
 * Embedded My certification report.
 *
 * @package     tool_mucertify
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class my_assignments extends system_report {
    /** @var user */
    protected $userentity;
    /** @var assignment */
    protected $assignmententity;
    /** @var source */
    protected $sourceentity;
    /** @var certification */
    protected $certificationentity;

    #[\Override]
    protected function initialise(): void {
        global $USER;

        $this->assignmententity = new assignment();
        $assignmentalias = $this->assignmententity->get_table_alias('tool_mucertify_assignment');
        $this->set_main_table('tool_mucertify_assignment', $assignmentalias);
        $this->add_entity($this->assignmententity);

        $this->sourceentity = new source();
        $sourcealias = $this->sourceentity->get_table_alias('tool_mucertify_source');
        $this->add_entity($this->sourceentity);
        $this->add_join("JOIN {tool_mucertify_source} {$sourcealias} ON {$sourcealias}.id = {$assignmentalias}.sourceid");

        $this->certificationentity = new certification();
        $certificationalias = $this->certificationentity->get_table_alias('tool_mucertify_certification');
        $this->add_entity($this->certificationentity);
        $this->add_join("JOIN {tool_mucertify_certification} {$certificationalias} ON {$certificationalias}.id = {$assignmentalias}.certificationid");

        $param = database::generate_param_name();
        $this->add_base_condition_sql(
            "{$assignmentalias}.userid = :$param AND {$assignmentalias}.archived = 0 AND {$certificationalias}.archived = 0",
            [$param => $USER->id]
        );

        $this->add_columns();
        $this->add_filters();

        $this->set_downloadable(false);
        $this->set_default_no_results_notice(new lang_string('errornomycertifications', 'tool_mucertify'));
    }

    #[\Override]
    protected function can_view(): bool {
        global $USER;

        // Everybody may view own certifications.
        if (!\tool_mulib\local\mulib::is_mucertify_active()) {
            return false;
        }
        if (isguestuser() || !isloggedin()) {
            return false;
        }
        $usercontext = $this->get_context();
        if ($usercontext->contextlevel != CONTEXT_USER || $usercontext->instanceid != $USER->id) {
            return false;
        }
        return true;
    }

    /**
     * Adds the columns we want to display in the report.
     */
    public function add_columns(): void {
        $assignmentalias = $this->assignmententity->get_table_alias('tool_mucertify_assignment');
        $sourcealias = $this->sourceentity->get_table_alias('tool_mucertify_source');
        $certificationalias = $this->certificationentity->get_table_alias('tool_mucertify_certification');

        $column = $this->certificationentity->get_column('fullname')
            ->add_field("{$certificationalias}.id")
            ->add_callback(static function ($value, \stdClass $row): string {
                if (!$value) {
                    return '';
                }
                $value = format_string($value);
                $url = new \moodle_url('/admin/tool/mucertify/my/certification.php', ['id' => $row->id]);
                return \html_writer::link($url, $value);
            });
        $this->add_column($column);

        $this->add_column_from_entity('certification:idnumber');
        $this->add_column_from_entity('assignment:timecertifiedfrom');
        $this->add_column_from_entity('assignment:timecertifieduntil');

        $this->add_column_from_entity('assignment:status');

        $this->set_initial_sort_column('certification:fullname', SORT_ASC);
    }

    /**
     * Adds the filters we want to display in the report.
     */
    protected function add_filters(): void {
        $this->add_filter_from_entity('assignment:status');
    }
}
