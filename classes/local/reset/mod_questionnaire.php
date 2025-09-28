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

namespace tool_muprog\local\reset;

use stdClass;

/**
 * mod_questionnaire user data reset
 *
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_questionnaire extends base {
    /**
     * Custom course module reset method.
     *
     * @param stdClass $user
     * @param array $courseids
     * @param array $options
     * @return void
     */
    public static function purge_data(stdClass $user, array $courseids, array $options = []): void {
        global $DB;

        if (!$courseids) {
            return;
        }

        [$courses, $params] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        $params['userid'] = $user->id;

        $questionnaires = "SELECT q.id
                             FROM {questionnaire} q
                            WHERE q.course $courses";

        $responses = "SELECT qr.id
                        FROM {questionnaire_response} qr
                       WHERE qr.userid = :userid AND qr.questionnaireid IN ($questionnaires)";

        $sql = "DELETE
                  FROM {questionnaire_response_date}
                 WHERE response_id IN ($responses)";
        $DB->execute($sql, $params);

        $sql = "DELETE
                  FROM {questionnaire_resp_multiple}
                 WHERE response_id IN ($responses)";
        $DB->execute($sql, $params);

        $sql = "DELETE
                  FROM {questionnaire_response_other}
                 WHERE response_id IN ($responses)";
        $DB->execute($sql, $params);

        $sql = "DELETE
                  FROM {questionnaire_response_rank}
                 WHERE response_id IN ($responses)";
        $DB->execute($sql, $params);

        $sql = "DELETE
                  FROM {questionnaire_resp_single}
                 WHERE response_id IN ($responses)";
        $DB->execute($sql, $params);

        $sql = "DELETE
                  FROM {questionnaire_response_text}
                 WHERE response_id IN ($responses)";
        $DB->execute($sql, $params);

        $sql = "DELETE
                  FROM {questionnaire_response}
                 WHERE userid = :userid AND questionnaireid IN ($questionnaires)";
        $DB->execute($sql, $params);
    }
}
