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

namespace tool_mutrain\reportbuilder\local\systemreports;

use tool_mutrain\reportbuilder\local\entities\completion;
use tool_mutrain\reportbuilder\local\entities\framework;
use core_reportbuilder\system_report;
use core_reportbuilder\local\report\column;
use lang_string;
use stdClass;

/**
 * Embedded user completions for given framework report.
 *
 * @package     tool_mutrain
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class completions_user extends system_report {
    /** @var stdClass */
    protected $framework;
    /** @var completion */
    protected $completionentity;
    /** @var string */
    protected $completionalias;
    /** @var string */
    protected $fieldalias;
    /** @var string */
    protected $frameworkalias;

    #[\Override]
    protected function initialise(): void {
        global $DB;

        $frameworkid = $this->get_parameter('frameworkid', 0, PARAM_INT);
        $this->framework = $DB->get_record('tool_mutrain_framework', ['id' => $frameworkid, 'archived' => 0], '*', MUST_EXIST);

        $this->completionentity = new completion();
        $this->completionalias = $this->completionentity->get_table_alias('tool_mutrain_completion');
        $this->add_entity($this->completionentity);

        $this->fieldalias = $this->completionentity->get_table_alias('tool_mutrain_field');
        $this->frameworkalias = $this->completionentity->get_table_alias('tool_mutrain_framework');

        $this->set_main_table('tool_mutrain_completion', $this->completionalias);

        $this->add_base_fields("{$this->completionalias}.id");

        $this->add_join("JOIN {tool_mutrain_field} {$this->fieldalias} ON {$this->fieldalias}.fieldid = {$this->completionalias}.fieldid");
        $this->add_join("JOIN {tool_mutrain_framework} {$this->frameworkalias} ON {$this->frameworkalias}.id = {$this->fieldalias}.frameworkid");

        $usercontext = $this->get_context();

        $basewhere[] = "{$this->completionalias}.userid = {$usercontext->instanceid}";
        $basewhere[] = "{$this->frameworkalias}.id = {$this->framework->id}";

        if ($this->framework->restrictcontext) {
            $frameworkcontext = \context::instance_by_id($this->framework->contextid);
            if ($frameworkcontext->contextlevel != CONTEXT_SYSTEM) {
                $this->add_join($this->completionentity->get_context_map_join($frameworkcontext));
            }
        }
        if ($this->framework->restrictafter) {
            $basewhere[] = "{$this->completionalias}.timecompleted >= {$this->framework->restrictafter}";
        }

        $this->add_base_condition_sql(implode(" AND ", $basewhere));

        $this->add_columns();
        $this->add_filters();

        $this->set_initial_sort_column('completion:timecompleted', SORT_ASC);
        $this->set_downloadable(true);
        $this->set_default_no_results_notice(new lang_string('error_nocompletions', 'tool_mutrain'));
    }

    #[\Override]
    protected function can_view(): bool {
        global $USER, $DB;
        if (isguestuser() || !isloggedin()) {
            return false;
        }
        if (!\tool_mulib\local\mulib::is_mutrain_available()) {
            return false;
        }

        $frameworkid = $this->get_parameter('frameworkid', 0, PARAM_INT);
        $framework = $DB->get_record('tool_mutrain_framework', ['id' => $frameworkid, 'archived' => 0]);
        if (!$framework) {
            return false;
        }
        if (!$framework->publicaccess) {
            if (!has_capability('tool/mutrain:viewframeworks', \context::instance_by_id($framework->contextid))) {
                return false;
            }
        }

        $usercontext = $this->get_context();
        if (!$usercontext instanceof \context_user) {
            return false;
        }
        if ($usercontext->instanceid == $USER->id) {
            return true;
        }

        return has_capability('tool/mutrain:viewusercredits', $usercontext);
    }

    /**
     * Adds the columns we want to display in the report.
     */
    public function add_columns(): void {
        $columns = [
            'completion:timecompleted',
            'completion:type',
            'completion:instance',
            'completion:credits',
        ];
        $this->add_columns_from_entities($columns);
    }

    /**
     * Adds the filters we want to display in the report.
     */
    protected function add_filters(): void {
    }
}
