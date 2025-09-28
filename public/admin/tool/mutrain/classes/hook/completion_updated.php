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

namespace tool_mutrain\hook;

use core\hook\described_hook;

/**
 * Allows plugins to update their completion based on training.
 *
 * @package    tool_mutrain
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class completion_updated implements described_hook {
    /** @var int int */
    protected $userid;
    /** @var int[] array */
    protected $frameworkids;

    /**
     * Creates new hook.
     *
     * @param int $userid
     * @param int[] $frameworkids
     */
    public function __construct(int $userid, array $frameworkids) {
        $this->userid = $userid;
        $this->frameworkids = $frameworkids;
    }

    /**
     * Updated completion for given framework ids.
     *
     * @return int[]
     */
    public function get_frameworkids(): array {
        return $this->frameworkids;
    }

    /**
     * Updated completion for given user.
     *
     * @return int
     */
    public function get_userid(): int {
        return $this->userid;
    }

    /**
     * Describes the hook purpose.
     *
     * @return string
     */
    public static function get_hook_description(): string {
        return 'Allows plugins to trigger completion recalculation depending on training';
    }

    /**
     * List of tags that describe this hook.
     *
     * @return string[]
     */
    public static function get_hook_tags(): array {
        return ['trainingss'];
    }
}
