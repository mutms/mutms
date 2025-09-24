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

namespace tool_murelation\output\management;

use tool_murelation\local\framework;
use stdClass, moodle_url;

/**
 * Frameworks management renderer.
 *
 * @package    tool_murelation
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {
    /**
     * Render framework.
     *
     * @param stdClass $framework
     * @return string
     */
    public function render_framework(stdClass $framework): string {
        global $DB;

        $result = '';

        $description = '';
        if ($framework->description) {
            $description = format_text($framework->description, $framework->descriptionformat);
            $description = $this->output->box($description);
        }

        $strnotset = get_string('notset', 'tool_mulib');

        $getfield = function (string $field) use ($framework, $strnotset): array {
            global $DB;
            $property = get_string('framework_' . $field, 'tool_murelation');
            $field .= 'id';
            if ($framework->$field) {
                $cohort = $DB->get_record('cohort', ['id' => $framework->$field]);
                if ($cohort) {
                    $value = format_string($cohort->name);
                } else {
                    $value = get_string('error');
                }
            } else {
                $value = $strnotset;
            }
            return [$property, $value];
        };

        $details = new \tool_mulib\output\entity_details();

        $details->add(get_string('framework_name', 'tool_murelation'), format_string($framework->name));
        if ($framework->idnumber === null) {
            $idnumber = $strnotset;
        } else {
            $idnumber = s($framework->idnumber);
        }
        $details->add(get_string('framework_idnumber', 'tool_murelation'), $idnumber);

        $uimodes = framework::get_uimodes();
        $details->add(get_string('framework_uimode', 'tool_murelation'), $uimodes[$framework->uimode]);

        $options = \tool_murelation\local\framework::get_visibility_options();
        $details->add(get_string('framework_visibility', 'tool_murelation'), $options[$framework->visibility]);

        [$property, $value] = $getfield('managecohort');
        $details->add($property, $value);

        if (\tool_mulib\local\mulib::is_mutenancy_active()) {
            $alltenants = $framework->alltenants ? get_string('yes') : get_string('no');
            $details->add(get_string('framework_alltenants', 'tool_murelation'), $alltenants);
            if (!$framework->alltenants) {
                $sql = "SELECT t.id, t.name
                          FROM {tool_mutenancy_tenant} t
                          JOIN {tool_murelation_tenant_allow} ta ON ta.tenantid = t.id
                         WHERE ta.frameworkid = :frameworkid
                      ORDER BY t.name ASC";
                $tenants = $DB->get_records_menu($sql, ['frameworkid' => $framework->id]);
                $tenants = array_map('format_string', $tenants);
                $tenants = implode(', ', $tenants);
                $details->add(get_string('tenants', 'tool_mutenancy'), $tenants);
            }
        }

        $result .= $description;
        $result .= $this->output->render($details);

        $details = new \tool_mulib\output\entity_details();
        $details->add(get_string('supervisortitle', 'tool_murelation'), format_string($framework->supervisortitle));
        $details->add(get_string('supervisorstitle', 'tool_murelation'), format_string($framework->supervisorstitle));
        [$property, $value] = $getfield('supervisorcohort');
        $details->add($property, $value);

        if ($framework->supervisorroleid) {
            $role = $DB->get_record('role', ['id' => $framework->supervisorroleid]);
            if ($role) {
                $rolename = role_get_name($role, null, ROLENAME_ORIGINAL);
            } else {
                $rolename = get_string('error');
            }
            $details->add(get_string('framework_supervisorrole', 'tool_murelation'), $rolename);
        } else {
            $details->add(get_string('framework_supervisorrole', 'tool_murelation'), $strnotset);
        }

        $result .= $this->output->heading(get_string('supervisor', 'tool_murelation'), 2, 'h3');
        $result .= $this->output->render($details);

        $details = new \tool_mulib\output\entity_details();
        $details->add(get_string('subordinatetitle', 'tool_murelation'), format_string($framework->subordinatetitle));
        $details->add(get_string('subordinatestitle', 'tool_murelation'), format_string($framework->subordinatestitle));
        [$property, $value] = $getfield('subordinatecohort');
        $details->add($property, $value);

        $result .= $this->output->heading(get_string('subordinate', 'tool_murelation'), 2, 'h3');
        $result .= $this->output->render($details);

        return $result;
    }

    /**
     * Render team.
     *
     * @param stdClass $framework
     * @param stdClass $supervisor
     * @return string
     */
    public function render_team(stdClass $framework, stdClass $supervisor): string {
        global $DB;

        $canmanage = \tool_murelation\local\uimode_teams::can_update_team($framework, $supervisor);

        $strnotset = get_string('notset', 'tool_mulib');
        $supervisortitle = format_string($framework->supervisortitle);

        $details = new \tool_mulib\output\entity_details();

        if (\tool_mulib\local\mulib::is_mutenancy_active() && $supervisor->tenantid) {
            $tenant = \tool_mutenancy\local\tenant::fetch($supervisor->tenantid);
            if ($tenant) {
                $tenantname = format_string($tenant->name);
            } else {
                $tenantname = get_string('error');
            }
            $details->add(get_string('tenant', 'tool_mutenancy'), $tenantname);
        }

        if (isset($supervisor->teamname)) {
            $team = format_string($supervisor->teamname);
        } else {
            $team = $strnotset;
        }
        $details->add(get_string('team_name', 'tool_murelation'), $team);

        if (isset($supervisor->teamidnumber)) {
            $idnumber = s($supervisor->teamidnumber);
            $details->add(get_string('team_idnumber', 'tool_murelation'), $idnumber);
        }

        if ($supervisor->userid) {
            $user = $DB->get_record('user', ['id' => $supervisor->userid, 'deleted' => 0]);
            if ($user) {
                $username = fullname($user);
                $url = new moodle_url('/user/profile.php', ['id' => $user->id]);
                $username = \html_writer::link($url, $username);
            } else {
                $username = get_string('error');
            }
        } else {
            $username = $strnotset;
        }
        $details->add($supervisortitle, $username);

        $supmanaged = $supervisor->supmanaged ? get_string('yes') : get_string('no');
        $details->add(get_string('team_supmanaged', 'tool_murelation'), $supmanaged);

        if ($supervisor->maxsubordinates) {
            $current = $DB->count_records('tool_murelation_subordinate', ['supervisorid' => $supervisor->id]);
            $details->add(
                get_string('team_maxsubordinates', 'tool_murelation'),
                "$current / $supervisor->maxsubordinates"
            );
        }

        if ($supervisor->teamcohortid) {
            $cohort = $DB->get_record('cohort', ['id' => $supervisor->teamcohortid]);
            if ($cohort) {
                $cohortname = format_string($cohort->name);
                $cohortcontext = \context::instance_by_id($cohort->contextid);
                if (has_capability('moodle/cohort:view', $cohortcontext)) {
                    $cohortname = \html_writer::link(
                        new moodle_url('/cohort/index.php', ['contextid' => $cohortcontext->id]),
                        $cohortname
                    );
                }
            } else {
                $cohortname = get_string('error');
            }
        } else {
            $cohort = false;
            $cohortname = get_string('notset', 'tool_mulib');
        }
        $details->add(get_string('team_cohort', 'tool_murelation'), $cohortname);

        return $this->output->render($details);
    }
}
