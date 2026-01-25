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

/**
 * Program item base class.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class item {
    /** @var ?int */
    protected $id = null;
    /** @var int */
    protected $programid;
    /** @var string */
    protected $type;
    /** @var int */
    protected $courseid;
    /** @var int */
    protected $creditframeworkid;
    /** @var string */
    protected $fullname;
    /** @var int */
    protected $points;
    /** @var int */
    protected $completiondelay;
    /** @var ?item Previous item needs to be completed in order to allow item access - not used in sets */
    protected $previous;
    /** @var bool */
    protected $problemdetected = false;

    /**
     * Item instances can be created only from the top class.
     */
    protected function __construct() {
        // Properties are initialised from init_from_record() method.
    }

    /**
     * Returns item type.
     *
     * @return string
     */
    public static function get_type(): string {
        throw new \core\exception\coding_exception('get_type() must be overridden');
    }

    /**
     * Returns item type.
     *
     * @return string
     */
    public static function get_type_name(): string {
        throw new \core\exception\coding_exception('get_type_name() must be overridden');
    }

    /**
     * Returns list of available item types.
     *
     * @return array
     */
    final public static function get_types(): array {
        return [
            top::get_type() => top::get_type_name(),
            set::get_type() => set::get_type_name(),
            course::get_type() => course::get_type_name(),
            attendance::get_type() => attendance::get_type_name(),
            credits::get_type() => credits::get_type_name(),
        ];
    }

    /**
     * Factory method.
     *
     * @param \stdClass $record
     * @param item|null $previous
     * @param array $unusedrecords
     * @param array $prerequisites
     * @return item
     */
    protected static function init_from_record(\stdClass $record, ?item $previous, array &$unusedrecords, array &$prerequisites): item {
        if ($record->topitem) {
            throw new \core\exception\coding_exception('Invalid item');
        }

        $item = new static();
        $item->id = $record->id;
        $item->programid = $record->programid;
        $item->type = $record->type;
        if (isset($record->courseid)) {
            $item->courseid = $record->courseid;
        }
        if (isset($record->creditframeworkid)) {
            $item->creditframeworkid = $record->creditframeworkid;
        }
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

        return $item;
    }

    /**
     * Fix item prerequisites if necessary.
     *
     * @param array $prerequisites
     * @return bool true if fix applied
     */
    protected function fix_prerequisites(array &$prerequisites): bool {
        // Nothing to do for individual items, parent set is defining the prerequisites.
        return false;
    }

    /**
     * Return item that must be completed before allowing access to this item.
     *
     * NOTE: always null for sets
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
     * Returns item full name.
     *
     * @return string
     */
    public function get_fullname(): string {
        return format_string($this->fullname);
    }

    /**
     * Returns item id.
     *
     * @return int|null
     */
    public function get_id(): ?int {
        return $this->id;
    }

    /**
     * Returns point based value.
     *
     * @return int
     */
    public function get_points(): int {
        return $this->points;
    }

    /**
     * Returns point based value.
     *
     * @return int
     */
    public function get_completiondelay(): int {
        return $this->completiondelay;
    }

    /**
     * Returns program id.
     *
     * @return int
     */
    public function get_programid(): int {
        return $this->programid;
    }

    /**
     * Is this a course item?
     *
     * @return bool
     */
    final public function is_course(): bool {
        return ($this instanceof course);
    }

    /**
     * Is this a credits item?
     *
     * @return bool
     */
    final public function is_credits(): bool {
        return ($this instanceof credits);
    }

    /**
     * Is this a credits item?
     *
     * @return bool
     */
    final public function is_attendance(): bool {
        return ($this instanceof attendance);
    }

    /**
     * Is this a credits item?
     *
     * @return bool
     */
    final public function is_set(): bool {
        return ($this instanceof set);
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
        if ($this->get_children()) {
            return false;
        }
        return true;
    }

    /**
     * Return children.
     *
     * @return item[]
     */
    public function get_children(): array {
        return [];
    }

    /**
     * Search for item by id.
     *
     * @param int $itemid
     * @return item|null
     */
    final public function find_item(int $itemid): ?item {
        if ($this->id == $itemid) {
            return $this;
        }
        $children = $this->get_children();
        foreach ($children as $child) {
            $found = $child->find_item($itemid);
            if ($found) {
                return $found;
            }
        }
        return null;
    }

    /**
     * Look for parent of item with given id.
     *
     * @param int $itemid
     * @return set|null
     */
    final public function find_parent_set(int $itemid): ?set {
        if (!($this instanceof set)) {
            return null;
        }
        if ($this->id == $itemid) {
            return null;
        }
        $children = $this->get_children();
        foreach ($children as $child) {
            if ($child->id == $itemid) {
                return $this;
            }
            $found = $child->find_parent_set($itemid);
            if ($found) {
                return $found;
            }
        }
        return null;
    }

    /**
     * Was there any program detected in this item during loading?
     *
     * @return bool
     */
    public function is_problem_detected(): bool {
        return $this->problemdetected;
    }

    /**
     * Returns expected item record data.
     *
     * @return array
     */
    protected function get_record(): array {
        return [
            'id' => ($this->id ? (string)$this->id : null),
            'programid' => (string)$this->programid,
            'type' => $this->type,
            'topitem' => null,
            'courseid' => ($this->courseid ? (string)$this->courseid : null),
            'creditframeworkid' => ($this->creditframeworkid ? (string)$this->creditframeworkid : null),
            'previtemid' => ($this->previous ? (string)$this->previous->id : null),
            'fullname' => $this->fullname,
            'sequencejson' => util::json_encode([]),
            'minprerequisites' => null,
            'points' => (string)$this->points,
            'minpoints' => null,
            'completiondelay' => (string)$this->completiondelay,
        ];
    }
}
