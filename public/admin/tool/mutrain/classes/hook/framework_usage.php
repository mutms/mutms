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
use stdClass;

/**
 * Allows plugins to update their completion based on training.
 *
 * @package    tool_mutrain
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class framework_usage implements described_hook {
    /** @var int */
    protected $frameworkid;
    /** @var int  */
    protected $usage = 0;

    /**
     * Creates new hook.
     *
     * @param int $frameworkid
     */
    public function __construct(int $frameworkid) {
        $this->frameworkid = $frameworkid;
    }

    /**
     * Framework id.
     *
     * @return int
     */
    public function get_frameworkid(): int {
        return $this->frameworkid;
    }

    /**
     * Updated completion for given user.
     *
     * @param int $usage
     */
    public function add_usage(int $usage): void {
        if ($usage < 0) {
            throw new \coding_exception('usage cannot be negative');
        }
        $this->usage += $usage;
    }

    /**
     * Updated completion for given user.
     *
     * @return int
     */
    public function get_usage(): int {
        return $this->usage;
    }

    /**
     * Describes the hook purpose.
     *
     * @return string
     */
    public static function get_hook_description(): string {
        return 'Discovers how many times is framework used';
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
