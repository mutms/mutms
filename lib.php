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

/**
 * User relations and teams core integration functions.
 *
 * @package    tool_murelation
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add nodes to myprofile page.
 *
 * @param \core_user\output\myprofile\tree $tree Tree object
 * @param stdClass $user user object
 * @param bool $iscurrentuser
 * @param stdClass $course Course object
 */
function tool_murelation_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course): void {
    global $USER, $DB, $OUTPUT;

    if (!\tool_murelation\local\util::is_murelation_active()) {
        return;
    }

    $frameworks = \tool_murelation\local\uimode_supervisors::get_visible_frameworks($user, $course);
    foreach ($frameworks as $framework) {
        $supervisortitle = format_string($framework->supervisortitle);
        if ($framework->supuserid) {
            $supuser = $DB->get_record('user', ['id' => $framework->supuserid]);
        } else {
            $supuser = false;
        }
        if ($supuser) {
            $supname = fullname($supuser);
            $url = new moodle_url('/user/profile.php', ['id' => $supuser->id]);
            $supname = html_writer::link($url, $supname);
            $dropdown = new \tool_mulib\output\dropdown(get_string('actions_a', 'tool_murelation', $supervisortitle));
            if ($framework->canmanage) {
                $link = new tool_mulib\output\ajax_form\link(
                    formurl: new moodle_url('/admin/tool/murelation/management/supervisor_edit.php', ['frameworkid' => $framework->id, 'subuserid' => $user->id]),
                    text: get_string('supervisor_update_a', 'tool_murelation', $supervisortitle),
                    pixname: 'i/edit'
                );
                $dropdown->add_ajax_form($link);
                $link = new tool_mulib\output\ajax_form\link(
                    formurl: new moodle_url('/admin/tool/murelation/management/supervisor_delete.php', ['frameworkid' => $framework->id, 'subuserid' => $user->id]),
                    text: get_string('supervisor_delete_a', 'tool_murelation', $supervisortitle),
                    pixname: 'i/delete'
                );
                $link->add_class('text-danger');
                $dropdown->add_ajax_form($link);
            }
            if ($dropdown->has_items()) {
                $supname .= $OUTPUT->render($dropdown);
            }
            $tree->add_node(new core_user\output\myprofile\node(
                'contact',
                'supervisor' . $framework->id,
                $supervisortitle,
                null,
                null,
                $supname
            ));
        } else if ($framework->canmanage) {
            $dropdown = new \tool_mulib\output\dropdown(get_string('actions_a', 'tool_murelation', $supervisortitle));
            $link = new tool_mulib\output\ajax_form\link(
                formurl: new moodle_url('/admin/tool/murelation/management/supervisor_edit.php', ['frameworkid' => $framework->id, 'subuserid' => $user->id]),
                text: get_string('supervisor_create_a', 'tool_murelation', $supervisortitle),
                pixname: 'i/edit'
            );
            $dropdown->add_ajax_form($link);

            $actions = $OUTPUT->render($dropdown);
            $tree->add_node(new core_user\output\myprofile\node(
                'contact',
                'supervisor' . $framework->id,
                $supervisortitle,
                null,
                null,
                get_string('notset', 'tool_mulib') . $actions
            ));
        }
    }

    $showsubordinates = false;
    if ($USER->id == $user->id) {
        $showsubordinates = true;
    } else {
        $tenantid = null;
        if (\tool_mulib\local\mulib::is_mutenancy_active()) {
            $tenantid = $user->tenantid;
        }
        if ($tenantid) {
            $context = context_tenant::instance($tenantid);
        } else {
            $context = context_system::instance();
        }
        if (has_capability('tool/murelation:viewpositions', $context)) {
            $showsubordinates = true;
        }
    }
    if ($showsubordinates) {
        if (\tool_murelation\local\uimode_supervisors::supervisor_has_subordinates($user->id)) {
            if ($USER->id == $user->id) {
                $link = get_string('subordinates_my', 'tool_murelation');
            } else {
                $link = get_string('subordinates', 'tool_murelation');
            }
            $url = new moodle_url('/admin/tool/murelation/profile/user_subordinates.php', ['id' => $user->id]);
            $node = new core_user\output\myprofile\node('reports', 'tool_murelation_user_subordinates', $link, null, $url);
            $tree->add_node($node);
        }
    }
    unset($frameworks);

    $syscontext = \context_system::instance();

    $teams = \tool_murelation\local\uimode_teams::get_visible_teams($user, $course);
    if ($teams) {
        $teamslist = [];
        foreach ($teams as $team) {
            $teamname = format_string($team->teamname);
            $context = $syscontext;
            if (\tool_mulib\local\mulib::is_mutenancy_active() && $team->tenantid) {
                $context = \context_tenant::instance($team->tenantid);
            }
            if (has_capability('tool/murelation:viewpositions', $context)) {
                $url = new \moodle_url('/admin/tool/murelation/management/team.php', ['id' => $team->id]);
                $teamname = html_writer::link($url, $teamname);
            }
            $teamslist[] = $teamname;
        }
        $teamslist = implode(', ', $teamslist);
        $tree->add_node(new core_user\output\myprofile\node(
            'contact',
            'subordinateteams',
            get_string('teams', 'tool_murelation'),
            null,
            null,
            $teamslist
        ));
    }

    // List of supervised teams.
    $teams = \tool_murelation\local\uimode_teams::get_supervised_teams($user);
    if ($teams) {
        $link = get_string('teams_supervised', 'tool_murelation');
        $url = new moodle_url('/admin/tool/murelation/profile/user_teams.php', ['id' => $user->id]);
        $node = new core_user\output\myprofile\node('reports', 'tool_murelation_user_teams_supervised', $link, null, $url);
        $tree->add_node($node);
    }
}
