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

use tool_murelation\local\framework;

/**
 * Teams and supervisors test data generator.
 *
 * @package     tool_murelation
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tool_murelation_generator extends component_generator_base {
    /** @var int framework count */
    private $frameworkcount = 0;

    /** @var int team count */
    private $teamcountcount = 0;

    #[\Override]
    public function reset() {
        $this->frameworkcount = 0;
        $this->teamcountcount = 0;
    }

    /**
     * Create new role and allow it for supervisors.
     *
     * @param stdClass|array $record
     * @return int role id
     */
    public function create_supervisor_role($record): int {
        $record = (object)(array)$record;

        $roleid = create_role($record->name, $record->shortname, '', '');
        set_role_contextlevels($roleid, [CONTEXT_USER]);
        $allowed = get_config('tool_murelation', 'roles');
        if ($allowed) {
            $allowed = explode(',', $allowed);
        } else {
            $allowed = [];
        }
        $allowed[] = $roleid;
        set_config('roles', implode(',', $allowed), 'tool_murelation');

        return $roleid;
    }

    /**
     * Create new framework.
     *
     * @param stdClass|array $record
     * @return stdClass framework record
     */
    public function create_framework($record): stdClass {
        $this->frameworkcount++;

        $record = (object)(array)$record;

        if (!isset($record->uimode)) {
            throw new \core\exception\coding_exception('uimode is required');
        }
        if ($record->uimode === 'teams' || $record->uimode == framework::UIMODE_TEAMS) {
            $record->uimode = framework::UIMODE_TEAMS;
        } else if ($record->uimode === 'supervisors' || $record->uimode == framework::UIMODE_SUPERVISORS) {
            $record->uimode = framework::UIMODE_SUPERVISORS;
        }

        if (!isset($record->name)) {
            $record->name = 'Framework ' . $this->frameworkcount;
        }

        if (!isset($record->supervisortitle)) {
            $record->supervisortitle = 'Supervisor';
        }
        if (!isset($record->supervisorstitle)) {
            $record->supervisorstitle = 'Supervisors';
        }
        if (!isset($record->subordinatetitle)) {
            $record->subordinatetitle = 'Subordinate';
        }
        if (!isset($record->subordinatestitle)) {
            $record->subordinatestitle = 'Subordinates';
        }

        return framework::create($record);
    }

    /**
     * Create new supervisor for subordinate.
     *
     * @param stdClass|array $record
     * @return stdClass supervisor record
     */
    public function create_supervisor($record): stdClass {
        $record = (object)(array)$record;

        if (!isset($record->frameworkid)) {
            throw new \core\exception\coding_exception('frameworkid is required');
        }
        if (!isset($record->subuserid)) {
            throw new \core\exception\coding_exception('subuserid is required');
        }
        if (!isset($record->userid)) {
            throw new \core\exception\coding_exception('userid is required');
        }

        return \tool_murelation\local\uimode_supervisors::supervisor_edit($record);
    }

    /**
     * Create new team.
     *
     * @param stdClass|array $record
     * @return stdClass supervisor record
     */
    public function create_team($record): stdClass {
        $this->teamcountcount++;

        $record = (object)(array)$record;

        if (!isset($record->frameworkid)) {
            throw new \core\exception\coding_exception('frameworkid is required');
        }

        if (!isset($record->teamname)) {
            $record->teamname = 'Team name ' . $this->frameworkcount;
        }

        return \tool_murelation\local\uimode_teams::team_create($record);
    }

    /**
     * Create new team member.
     *
     * @param stdClass|array $record
     * @return stdClass subordinate record
     */
    public function create_team_member($record): stdClass {
        global $DB;
        $record = (object)(array)$record;

        if (empty($record->supervisorid)) {
            if (empty($record->teamname)) {
                throw new \core\exception\coding_exception('supervisorid or teamname is required');
            }
            $supervisor = $DB->get_record('tool_murelation_supervisor', ['teamname' => $record->teamname], '*', MUST_EXIST);
            $record->supervisorid = $supervisor->id;
        }
        if (!isset($record->userid)) {
            throw new \core\exception\coding_exception('userid is required');
        }

        $d = (object)[
            'supervisorid' => $record->supervisorid,
            'subuserids' => [$record->userid],
            'teamposition' => $record->teamposition ?? '',
        ];
        \tool_murelation\local\uimode_teams::members_create($d);

        return $DB->get_record('tool_murelation_subordinate', ['supervisorid' => $record->supervisorid, 'userid' => $record->userid], '*', MUST_EXIST);
    }
}
