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

namespace tool_mupwned\event;

use core\event\base;
use stdClass;
use moodle_url;

/**
 * Compromised passwords blocked event.
 *
 * @package     tool_mupwned
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class user_login_blocked extends base {
    /**
     * Create event for user's current password failing password policy.
     *
     * @param stdClass $user
     * @param int $service
     * @return static
     */
    public static function create_from_user(stdClass $user, int $service): static {
        $data = [
            'objectid' => $user->id,
            'context' => \context_user::instance($user->id),
            'userid' => $user->id,
            'relateduserid' => $user->id,
            'other' => ['service' => $service],
        ];
        /** @var user_login_blocked $event */
        $event = self::create($data);
        $event->add_record_snapshot('user', $user);
        return $event;
    }

    #[\Override]
    protected function init(): void {
        $this->data['objecttable'] = 'user';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    #[\Override]
    public static function get_name(): string {
        return get_string('event_user_login_blocked', 'tool_mupwned');
    }

    #[\Override]
    public function get_description(): string {
        return "User with id '$this->userid' was prevented from logging-in with a compromised password.";
    }

    #[\Override]
    public function get_url(): moodle_url {
        return new moodle_url('/user/profile.php', ['id' => $this->userid]);
    }
}
