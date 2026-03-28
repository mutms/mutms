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

/**
 * User relation behat generators.
 *
 * @package    tool_murelation
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_tool_murelation_generator extends behat_generator_base {
    /**
     * Get a list of the entities that Behat can create using the generator step.
     *
     * @return array
     */
    protected function get_creatable_entities(): array {
        return [
            'supervisor_roles' => [
                'singular' => 'supervisor_role',
                'datagenerator' => 'supervisor_role',
                'required' => ['name', 'shortname'],
            ],
            'frameworks' => [
                'singular' => 'framework',
                'datagenerator' => 'framework',
                'required' => ['uimode'],
                'switchids' => [
                    'uimode' => 'uimode',
                    'visibility' => 'visibility',
                    'managecohort' => 'managecohortid',
                    'supervisorcohort' => 'supervisorcohortid',
                    'supervisorrole' => 'supervisorroleid',
                    'subordinatecohort' => 'subordinatecohortid',
                ],
            ],
            'supervisors' => [
                'singular' => 'supervisor',
                'datagenerator' => 'supervisor',
                'required' => ['framework', 'user', 'subuser'],
                'switchids' => [
                    'framework' => 'frameworkid',
                    'user' => 'userid',
                    'subuser' => 'subuserid',
                ],
            ],
            'teams' => [
                'singular' => 'team',
                'datagenerator' => 'team',
                'required' => ['framework', 'teamname'],
                'switchids' => [
                    'framework' => 'frameworkid',
                    'tenant' => 'tenantid',
                    'user' => 'userid',
                ],
            ],
            'team_members' => [
                'singular' => 'team_member',
                'datagenerator' => 'team_member',
                'required' => ['team', 'user'],
                'switchids' => [
                    'team' => 'supervisorid',
                    'user' => 'userid',
                ],
            ],
        ];
    }

    /**
     * Look up the uimode constant.
     *
     * @param string $uimode
     * @return int
     */
    protected function get_uimode_id(string $uimode): int {
        $uimode = strtolower($uimode);
        switch ($uimode) {
            case 'supervisors':
                return \tool_murelation\local\framework::UIMODE_SUPERVISORS;
            case 'teams':
                return \tool_murelation\local\framework::UIMODE_TEAMS;
            default:
                throw new \Exception('Invalid uimode "' . $uimode . '"');
        }
    }

    /**
     * Look up visibility constant.
     *
     * @param string $visibility
     * @return int
     */
    protected function get_visibility_id(string $visibility): int {
        $visibility = strtolower($visibility);
        switch ($visibility) {
            case 'hidden':
                return \tool_murelation\local\framework::VISIBILITY_HIDDEN;
            case 'managers':
                return \tool_murelation\local\framework::VISIBILITY_MANAGERS;
            case 'supervisors':
                return \tool_murelation\local\framework::VISIBILITY_SUPERVISORS;
            case '':
            case 'subordinate':
                return \tool_murelation\local\framework::VISIBILITY_SUBORDINATES;
            case 'everybody':
                return \tool_murelation\local\framework::VISIBILITY_EVERYBODY;
            default:
                throw new \Exception('Invalid visibility "' . $visibility . '"');
        }
    }

    /**
     * Gets the cohort id from idnumber.
     *
     * @param string $idnumber
     * @return int|null
     */
    protected function get_managecohort_id(string $idnumber): ?int {
        if ($idnumber === '') {
            return null;
        }
        return $this->get_cohort_id($idnumber);
    }

    /**
     * Gets the supervisor candidates cohort id from idnumber.
     *
     * @param string $idnumber
     * @return int|null
     */
    protected function get_supervisorcohort_id(string $idnumber): ?int {
        if ($idnumber === '') {
            return null;
        }
        return $this->get_cohort_id($idnumber);
    }

    /**
     * Gets the supervisor role id from shorname.
     *
     * @param string $shorname
     * @return int|null
     */
    protected function get_supervisorrole_id(string $shorname): ?int {
        if ($shorname === '') {
            return null;
        }
        return $this->get_role_id($shorname);
    }

    /**
     * Gets the subordinate candidates cohort id from idnumber.
     *
     * @param string $idnumber
     * @return int|null
     */
    protected function get_subordinatecohort_id(string $idnumber): ?int {
        if ($idnumber === '') {
            return null;
        }
        return $this->get_cohort_id($idnumber);
    }

    /**
     * Gets the framework id from idnumber.
     *
     * @param string $idnumber
     * @return int
     */
    protected function get_framework_id(string $idnumber): int {
        global $DB;

        if (!$id = $DB->get_field('tool_murelation_framework', 'id', ['idnumber' => $idnumber])) {
            throw new Exception('The specified framework with idnumber "' . $idnumber . '" does not exist');
        }
        return $id;
    }

    /**
     * Gets the tenant id from idnumber.
     *
     * @param string|null $idnumber
     * @return int
     */
    protected function get_tenant_id(string $idnumber): ?int {
        global $DB;

        if ($idnumber === '') {
            return null;
        }

        if (!$id = $DB->get_field('tool_mutenancy_tenant', 'id', ['idnumber' => $idnumber])) {
            throw new Exception('The specified tenant with idnumber "' . $idnumber . '" does not exist');
        }
        return $id;
    }

    /**
     * Gets the user id from username.
     *
     * @param string $username
     * @return int|null
     */
    protected function get_user_id($username): ?int {
        if ($username === '') {
            return null;
        }
        return parent::get_user_id($username);
    }

    /**
     * Gets the subuser id from username.
     *
     * @param string $username
     * @return int
     */
    protected function get_subuser_id(string $username): int {
        return parent::get_user_id($username);
    }

    /**
     * Gets the supervisor id from team idnumber or name.
     *
     * @param string $idnumber
     * @return int
     */
    protected function get_team_id(string $idnumber): int {
        global $DB;

        if (!$id = $DB->get_field('tool_murelation_supervisor', 'id', ['teamidnumber' => $idnumber])) {
            if (!$id = $DB->get_field('tool_murelation_supervisor', 'id', ['teamname' => $idnumber])) {
                throw new Exception('The specified team with idnumber or name "' . $idnumber . '" does not exist');
            }
        }
        return $id;
    }
}
