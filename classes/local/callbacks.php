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

namespace tool_murelation\local;

use stdClass;

/**
 * user relation callbacks.
 *
 * @package    tool_murelation
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class callbacks {
    /**
     * Event observer for user deletion.
     *
     * @param \core\event\user_deleted $event
     */
    public static function user_deleted(\core\event\user_deleted $event): void {
        if (!util::is_murelation_active()) {
            return;
        }

        $userid = $event->objectid;

        supervisor::user_deleted($userid);
        subordinate::user_deleted($userid);
    }

    /**
     * Event observer for tenant deletion.
     *
     * @param \tool_mutenancy\event\tenant_deleted $event
     */
    public static function tenant_deleted(\tool_mutenancy\event\tenant_deleted $event): void {
        global $DB;

        if (!util::is_murelation_active()) {
            return;
        }

        $tenantid = $event->objectid;
        $DB->set_field('tool_murelation_supervisor', 'tenantid', null, ['tenantid' => $tenantid]);
        $DB->delete_records('tool_murelation_tenant_allow', ['tenantid' => $tenantid]);
    }

    /**
     * Event observer for user tenant allocation change.
     *
     * @param \tool_mutenancy\event\user_allocated $event
     */
    public static function user_allocated(\tool_mutenancy\event\user_allocated $event): void {
        if (!util::is_murelation_active()) {
            return;
        }

        $userid = $event->objectid;

        uimode_supervisors::tenant_allocation_changed($userid);
    }
}
