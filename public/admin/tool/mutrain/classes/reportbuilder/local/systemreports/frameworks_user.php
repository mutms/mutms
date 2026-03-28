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

use tool_mutrain\reportbuilder\local\entities\framework;
use core_reportbuilder\system_report;
use core_reportbuilder\local\report\column;
use lang_string;
use core\url;

/**
 * Embedded user credit frameworks report.
 *
 * @package     tool_mutrain
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class frameworks_user extends system_report {
    /** @var framework */
    protected $frameworkentity;
    /** @var string */
    protected $frameworkalias;
    /** @var string */
    protected $creditalias;

    #[\Override]
    protected function initialise(): void {
        $this->frameworkentity = new framework();
        $this->frameworkalias = $this->frameworkentity->get_table_alias('tool_mutrain_framework');
        $this->creditalias = $this->frameworkentity->get_table_alias('tool_mutrain_credit');

        $this->set_main_table('tool_mutrain_framework', $this->frameworkalias);
        $this->add_entity($this->frameworkentity);

        $this->add_base_fields("{$this->frameworkalias}.id");

        $contextalias = $this->frameworkentity->get_table_alias('context');
        $this->add_join($this->frameworkentity->get_context_join());

        $this->add_join("JOIN {tool_mutrain_credit} {$this->creditalias} ON {$this->creditalias}.frameworkid = {$this->frameworkalias}.id");

        $usercontext = $this->get_context();
        $userid = $usercontext->instanceid;

        $basewhere = "{$this->creditalias}.userid = $userid AND {$this->frameworkalias}.publicaccess = 1 AND {$this->frameworkalias}.archived = 0";

        if (\tool_mulib\local\mulib::is_mutenancy_active()) {
            if ($usercontext->tenantid) {
                $basewhere .= "AND ({$contextalias}.tenantid IS NULL OR {$contextalias}.tenantid = $usercontext->tenantid)";
            }
        }

        $this->add_base_condition_sql($basewhere);

        $this->add_columns();
        $this->add_filters();

        $this->set_initial_sort_column('framework:name', SORT_ASC);
        $this->set_downloadable(true);
        $this->set_default_no_results_notice(new lang_string('error_nocredits', 'tool_mutrain'));
    }

    #[\Override]
    protected function can_view(): bool {
        global $USER;
        if (isguestuser() || !isloggedin()) {
            return false;
        }
        if (!\tool_mulib\local\mulib::is_mutrain_available()) {
            return false;
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
            'framework:name',
            'framework:restrictcontext',
            'framework:restrictafter',
        ];
        $this->add_columns_from_entities($columns);

        $column = (new column(
            'credits',
            new lang_string('credits_current', 'tool_mutrain'),
            $this->frameworkentity->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_FLOAT)
            ->add_fields("{$this->creditalias}.credits, {$this->frameworkalias}.id, {$this->frameworkalias}.publicaccess, {$this->frameworkalias}.contextid")
            ->set_is_sortable(true)
            ->set_callback(function (mixed $value, \stdClass $row): string {
                global $USER;
                if (!isset($value)) {
                    return '';
                }
                if (!$value) {
                    return '0';
                }

                $credits = format_float($value, 2, true, true);

                $usercontext = $this->get_context();
                if ($usercontext->instanceid == $USER->id || has_capability('tool/mutrain:viewusercredits', $usercontext)) {
                    $context = \context::instance_by_id($row->contextid);
                    if ($row->publicaccess || has_capability('tool/mutrain:viewframeworks', $context)) {
                        $url = new url('/admin/tool/mutrain/my/completions.php', ['frameworkid' => $row->id, 'userid' => $usercontext->instanceid]);
                        $credits = \html_writer::link($url, $credits);
                    }
                }

                return $credits;
            });

        $this->add_column($column);

        $this->add_column_from_entity('framework:requiredcredits');
    }

    /**
     * Adds the filters we want to display in the report.
     */
    protected function add_filters(): void {
        $filters = [
            'framework:name',
        ];
        $this->add_filters_from_entities($filters);
    }
}
