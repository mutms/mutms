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

namespace block_muprogmyoverview\external;

use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_api;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use tool_mulib\local\mulib;
use core\exception\invalid_parameter_exception;
use block_muprogmyoverview\local\util;
use tool_mulib\local\sql;

/**
 * Provides list of program allocations.
 *
 * @package     block_muprogmyoverview
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class get_active_programs extends external_api {
    /**
     * Describes the external function arguments.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'classification' => new external_value(PARAM_ALPHA, 'future, inprogress, or past'),
            'limit' => new external_value(PARAM_INT, 'Result set limit', VALUE_DEFAULT, 0),
            'offset' => new external_value(PARAM_INT, 'Result set offset', VALUE_DEFAULT, 0),
            'sort' => new external_value(PARAM_ALPHANUM, 'Sort string: title, idnumber or duedate', VALUE_DEFAULT, null),
            'searchvalue' => new external_value(PARAM_RAW, 'The value a user wishes to search against', VALUE_DEFAULT, null),
            'showdescription' => new external_value(PARAM_BOOL, 'Return description', VALUE_DEFAULT, null),
        ]);
    }

    /**
     * Execute.
     *
     * @param string $classification
     * @param int $limit
     * @param int $offset
     * @param string|null $sort
     * @param string|null $searchvalue
     * @param int $showdescription
     * @return array
     */
    public static function execute(
        string $classification,
        int $limit = 0,
        int $offset = 0,
        ?string $sort = null,
        ?string $searchvalue = null,
        int $showdescription = 0
    ): array {
        global $USER, $DB, $CFG;
        require_once("$CFG->libdir/filelib.php");

        if (!mulib::is_muprog_available()) {
            throw new invalid_parameter_exception('programs not available');
        }

        [
            'classification' => $classification,
            'limit' => $limit,
            'offset' => $offset,
            'sort' => $sort,
            'searchvalue' => $searchvalue,
            'showdescription' => $showdescription,
        ] = self::validate_parameters(self::execute_parameters(), [
            'classification' => $classification,
            'limit' => $limit,
            'offset' => $offset,
            'sort' => $sort,
            'searchvalue' => $searchvalue,
            'showdescription' => $showdescription,
        ]);

        $syscontext = \context_system::instance();
        self::validate_context($syscontext);

        $allclassifications = [
            'allincludinghidden',
            'all',
            'past',
            'inprogress',
            'future',
            'favourites',
            'hidden',
        ];

        if (!in_array($classification, $allclassifications)) {
            throw new invalid_parameter_exception('Invalid classification: ' . $classification);
        }

        $config = get_config('block_muprogmyoverview');
        $now = time();

        $sql = new sql(
            "SELECT p.*, f.id AS favid
               FROM {tool_muprog_allocation} a
               JOIN {tool_muprog_program} p ON p.id = a.programid
          LEFT JOIN {favourite} f ON f.itemid = p.id AND f.userid = a.userid AND f.component = 'tool_muprog' AND f.itemtype = 'programs'
              WHERE a.userid = :userid AND a.archived = 0 AND p.archived = 0
                    /* hiddenwhere */
                    /* classificationwhere */
                    /* searchwhere */
            /* orderby */
            ",
            ['userid' => $USER->id]
        );

        if (trim($searchvalue ?? '') !== '') {
            $conditions = [];
            $searchparam = '%' . $DB->sql_like_escape($searchvalue) . '%';
            foreach (['fullname', 'idnumber', 'description'] as $field) {
                $conditions[] = new sql($DB->sql_like('p.' . $field, '?', false), [$searchparam]);
            }
            $sql = $sql->replace_comment(
                'searchwhere',
                sql::join(' OR ', $conditions)->wrap('AND (', ')')
            );
        }

        if ($sort) {
            // Do not attempt to sanitise stuff here, accept only allowed values!
            if ($sort === 'idnumber') {
                $sql = $sql->replace_comment('orderby', 'ORDER BY p.idnumber ASC');
            } else if ($sort === 'duedate') {
                $sql = $sql->replace_comment(
                    'orderby',
                    'ORDER BY CASE WHEN a.timedue IS NULL THEN 1 ELSE 0 END, a.timedue ASC'
                );
            } else {
                if ($sort !== 'title') {
                    debugging('Unknown sort parameter', DEBUG_DEVELOPER);
                }
                $sql = $sql->replace_comment('orderby', 'ORDER BY p.fullname ASC');
            }
        }

        $hiddenprograms = util::get_hidden_programs_on_timeline();

        if ($classification === 'hidden') {
            if ($hiddenprograms) {
                $hidden = implode(',', $hiddenprograms);
            } else {
                $hidden = '-1'; // Exclude everything.
            }
            $sql = $sql->replace_comment(
                'hiddenwhere',
                "AND p.id IN ($hidden)"
            );
        } else if ($classification !== 'allincludinghidden') {
            if ($hiddenprograms) {
                $hidden = implode(',', $hiddenprograms);
                $sql = $sql->replace_comment(
                    'hiddenwhere',
                    "AND p.id NOT IN ($hidden)"
                );
            }
        }

        if ($classification === 'past') {
            $sql = $sql->replace_comment(
                'classificationwhere',
                "AND (a.timeend < $now OR a.timecompleted IS NOT NULL)"
            );
        } else if ($classification === 'future') {
            $sql = $sql->replace_comment(
                'classificationwhere',
                "AND a.timestart > $now"
            );
        } else if ($classification === 'inprogress') {
            $sql = $sql->replace_comment(
                'classificationwhere',
                "AND a.timestart < $now AND (a.timeend IS NULL OR a.timeend > $now) AND a.timecompleted IS NULL"
            );
        } else if ($classification === 'favourites') {
            $sql = $sql->replace_comment(
                'classificationwhere',
                "AND f.id IS NOT NULL"
            );
        }

        $programs = $DB->get_records_sql($sql->sql, $sql->params, $offset, $limit);

        $shortdate = get_string('strftimedatetimeshort', 'langconfig');
        $showcategory = !empty($config->displaycategories);

        $returnprograms = [];
        foreach ($programs as $program) {
            $allocation = $DB->get_record('tool_muprog_allocation', ['userid' => $USER->id, 'programid' => $program->id]);
            $result = (object)['id' => $program->id];

            $context = \context::instance_by_id($program->contextid);
            $result->programcategory = $context->get_context_name(false);
            if ($syscontext->id != $context->id && $showcategory) {
                $result->showprogramcategory = 1;
            } else {
                $result->showprogramcategory = 0;
            }

            $result->fullname = mulib::clean_string(format_string($program->fullname));

            $result->idnumber = mulib::clean_string($program->idnumber);

            if ($allocation->timestart) {
                $result->startdate = userdate($allocation->timestart, $shortdate);
            } else {
                $result->startdate = null;
            }

            if ($allocation->timedue) {
                $result->duedate = userdate($allocation->timedue, $shortdate);
            } else {
                $result->duedate = null;
            }

            if ($allocation->timeend) {
                $result->enddate = userdate($allocation->timeend, $shortdate);
            } else {
                $result->enddate = null;
            }

            $result->programimage = \tool_muprog\local\program::get_image_uri($program, true);
            $result->viewurl = (new \core\url('/admin/tool/muprog/my/program.php', ['id' => $program->id]))->out(false);

            [$result->status, $result->statusclass] = \tool_muprog\local\allocation::get_completion_status($program, $allocation);
            ;

            if ($allocation->timestart > time()) {
                $result->progress = 0;
                $result->hasprogress = 0;
            } else {
                $result->progress = \tool_muprog\local\allocation::get_progress_integer($program, $allocation);
                $result->hasprogress = ($result->progress !== null);
            }

            $result->hidden = (int)in_array($program->id, $hiddenprograms);

            $result->isfavourite = (int)!empty($program->favid);

            $result->description = null;
            if ($showdescription) {
                $description = file_rewrite_pluginfile_urls($program->description, 'pluginfile.php', $context->id, 'tool_muprog', 'description', $program->id);
                $result->description = format_text($description, $program->descriptionformat, ['context' => $context]);
            }

            $returnprograms[] = (array)$result;
        }

        util::cleanup_hidden_programs();

        return [
            'programs' => $returnprograms,
            'nextoffset' => $offset + count($returnprograms),
        ];
    }

    /**
     * Describes the external function parameters.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'programs' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Program id'),
                    'fullname' => new external_value(PARAM_TEXT, 'Program fullname'),
                    'idnumber' => new external_value(PARAM_RAW, 'Program ID'),
                    'description' => new external_value(PARAM_RAW, 'Optional program description and list of courses'),
                    'startdate' => new external_value(PARAM_RAW, 'Program start'),
                    'duedate' => new external_value(PARAM_RAW, 'Program due'),
                    'enddate' => new external_value(PARAM_RAW, 'Program end'),
                    'programcategory' => new external_value(PARAM_TEXT, 'Category name'),
                    'programimage' => new external_value(PARAM_RAW, 'Program image URI'),
                    'viewurl' => new external_value(PARAM_URL, 'Program URL'),
                    'status' => new external_value(PARAM_TEXT, 'Status of program'),
                    'statusclass' => new external_value(PARAM_RAW, 'Bootstrap badge class'),
                    'hasprogress' => new external_value(PARAM_BOOL, 'Is progress possible'),
                    'progress' => new external_value(PARAM_INT, 'Progress'),
                    'isfavourite' => new external_value(PARAM_BOOL, 'If fav program'),
                    'hidden' => new external_value(PARAM_BOOL, 'If hidden program'),
                    'showprogramcategory' => new external_value(PARAM_BOOL, 'Should it show program category'),
                ])
            ),
            'nextoffset' => new external_value(PARAM_INT, 'Offset for the next request'),
        ]);
    }
}
