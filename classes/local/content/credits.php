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

namespace tool_muprog\local\content;

use tool_muprog\local\util;
use stdClass;

/**
 * Credits item.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class credits extends item {
    /** @var int */
    protected $creditframeworkid;

    /** @var ?item Previous item is not actually used because there is no concept of start of credits */
    protected $previous;

    /**
     * ReturnGet credit credit framework id.
     *
     * @return int
     */
    public function get_creditframeworkid(): int {
        return $this->creditframeworkid;
    }

    /**
     * What is the required total learning?
     *
     * @return string|null decimal number
     */
    public function get_required_credits(): ?string {
        global $DB;
        $framework = $DB->get_record('tool_mutrain_framework', ['id' => $this->creditframeworkid]);
        if (!$framework) {
            return null;
        }
        return $framework->requiredcredits;
    }

    /**
     * What is the current sum of completed credits?
     *
     * @param stdClass $allocation
     * @return int
     */
    public function get_completed_credits(stdClass $allocation): int {
        global $DB;

        return (int)$DB->get_field('tool_mutrain_credit', 'credits', [
            'frameworkid' => $this->creditframeworkid,
            'userid' => $allocation->userid,
        ]);
    }

    /**
     * Current progress for current active program allocations,
     * required credits in all other cases.
     *
     * @param stdClass $allocation
     * @return string
     */
    public function get_current_credits(stdClass $allocation): string {
        global $DB;

        $now = time();

        $framework = $DB->get_record('tool_mutrain_framework', ['id' => $this->creditframeworkid]);
        if (!$framework) {
            return get_string('error');
        }

        $requiredcredits = format_float($framework->requiredcredits, 2, true, true);

        if (
            $framework->archived || $allocation->archived
            || $allocation->timestart > $now || ($allocation->timeend && $allocation->timeend <= $now)
        ) {
            return get_string('credits_requiredcredits', 'tool_muprog', $requiredcredits);
        }

        $data = [
            'current' => self::get_completed_credits($allocation),
            'total' => $requiredcredits,
        ];

        return get_string('credits_progress', 'tool_muprog', $data);
    }

    /**
     * Is this item deletable?
     *
     * @return bool
     */
    public function is_deletable(): bool {
        if (!$this->id) {
            return false;
        }
        return true;
    }

    /**
     * Return item that must be completed before allowing access to this credits item.
     *
     * @return item|null
     */
    public function get_previous(): ?item {
        return $this->previous;
    }

    /**
     * Set previous item to new value.
     *
     * @param item|null $previous new previous item
     * @return void
     */
    protected function fix_previous(?item $previous): void {
        $this->previous = $previous;
    }

    /**
     * Factory method.
     *
     * @param \stdClass $record
     * @param item|null $previous
     * @param array $unusedrecords
     * @param array $prerequisites
     * @return credits
     */
    protected static function init_from_record(\stdClass $record, ?item $previous, array &$unusedrecords, array &$prerequisites): item {
        if ($record->topitem || $record->courseid !== null || $record->creditframeworkid === null) {
            throw new \coding_exception('Invalid credits item');
        }
        $item = new credits();
        $item->id = $record->id;
        $item->programid = $record->programid;
        $item->creditframeworkid = $record->creditframeworkid;
        $item->previous = $previous;
        if ($previous) {
            if ($previous->id == $record->id) {
                $item->previous = null;
                $item->problemdetected = true;
            } else if ($record->previtemid != $previous->id) {
                $item->problemdetected = true;
            }
        } else {
            if ($record->previtemid) {
                $item->problemdetected = true;
            }
        }
        $item->fullname = $record->fullname;
        $item->points = $record->points;
        $item->completiondelay = $record->completiondelay;

        if ($record->minprerequisites !== null) {
            $item->problemdetected = true;
        }
        if ($record->minpoints !== null) {
            $item->problemdetected = true;
        }

        // NOTE: Prerequisites are verified in set that contains this credits item.

        return $item;
    }

    /**
     * Fix item prerequisites if necessary.
     *
     * @param array $prerequisites
     * @return bool true if fix applied
     */
    protected function fix_prerequisites(array &$prerequisites): bool {
        // Nothing to do, parent is defining the prerequisites.
        return false;
    }

    /**
     * Returns expected item record data.
     *
     * @return array
     */
    protected function get_record(): array {
        global $DB;

        $fullname = $DB->get_field('tool_mutrain_framework', 'name', ['id' => $this->creditframeworkid]);
        if ($fullname === false) {
            $fullname = $this->fullname;
        }

        return [
            'id' => (empty($this->id) ? null : (string)$this->id),
            'programid' => (string)$this->programid,
            'topitem' => null,
            'courseid' => null,
            'creditframeworkid' => (string)$this->creditframeworkid,
            'previtemid' => (isset($this->previous) ? (string)$this->previous->id : null),
            'fullname' => $fullname,
            'sequencejson' => util::json_encode([]),
            'minprerequisites' => null,
            'points' => (string)$this->points,
            'minpoints' => null,
            'completiondelay' => (string)$this->completiondelay,
        ];
    }
}
