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

use tool_muprog\local\program;
use tool_muprog\local\util;
use tool_muprog\local\allocation;

/**
 * Program top item.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class top extends set {
    /** @var course[] list of orphaned courses in program */
    protected $orphanedcourses = [];

    /** @var training[] list of orphaned trainings in program */
    protected $orphanedtrainings = [];

    /** @var set[] list of orphaned sets in program */
    protected $orphanedsets = [];

    /**
     * Is this item deletable?
     *
     * @return bool
     */
    public function is_deletable(): bool {
        return false;
    }

    /**
     * Returns expected item record data.
     *
     * @return array
     */
    protected function get_record(): array {
        global $DB;

        $program = $DB->get_record('tool_muprog_program', ['id' => $this->programid], '*', MUST_EXIST);

        $record = parent::get_record();
        $record['topitem'] = '1';
        $record['fullname'] = $program->fullname;

        return $record;
    }

    /**
     * Create in memory program content structure representation.
     *
     * @param int $programid
     * @return top
     */
    public static function load(int $programid): top {
        global $DB;

        $records = $DB->get_records('tool_muprog_item', ['programid' => $programid], 'id ASC');
        if (!$records) {
            throw new \coding_exception('No program items found');
        }

        $prerequisites = self::get_prerequisites($programid);

        $toprecord = null;
        foreach ($records as $k => $record) {
            if ($record->topitem) {
                $toprecord = $record;
                unset($records[$k]);
                break;
            }
        }
        if (!$toprecord) {
            throw new \coding_exception('Missing top program item');
        }
        /** @var top $top */
        $top = set::init_from_record($toprecord, null, $records, $prerequisites);

        if ($records) {
            // Deal with orphans.
            foreach ($records as $record) {
                if ($record->topitem) {
                    throw new \coding_exception('only one item can be topitem');
                }
                if ($record->courseid !== null) {
                    $fakerecords = [];
                    // Prevent course access by requiring program completion.
                    $orphan = course::init_from_record($record, $top, $fakerecords, $prerequisites);
                    if ($orphan->problemdetected) {
                        $top->problemdetected = true;
                    }
                    $top->orphanedcourses[$orphan->id] = $orphan;
                } else if ($record->trainingid !== null) {
                    $fakerecords = [];
                    $orphan = training::init_from_record($record, $top, $fakerecords, $prerequisites);
                    if ($orphan->problemdetected) {
                        $top->problemdetected = true;
                    }
                    $top->orphanedtrainings[$orphan->id] = $orphan;
                } else {
                    $record = clone($record);
                    $fakerecords = [];  // We do not want to load any children for orphaned sets.
                    $orphan = set::init_from_record($record, null, $fakerecords, $prerequisites);
                    if ($orphan->problemdetected) {
                        $top->problemdetected = true;
                    }
                    $top->orphanedsets[$orphan->id] = $orphan;
                }
            }
        }

        if ($prerequisites) {
            // Unexpected pre-requisites detected.
            $top->problemdetected = true;
        }

        return $top;
    }

    /**
     * Returns list of program courses that are not correctly linked to any valid set.
     *
     * @return course[]
     */
    public function get_orphaned_courses(): array {
        return $this->orphanedcourses;
    }

    /**
     * Returns list of program trainings that are not correctly linked to any valid set.
     *
     * @return training[]
     */
    public function get_orphaned_trainings(): array {
        return $this->orphanedtrainings;
    }

    /**
     * Returns list of sets that are not correctly linked to any valid set.
     *
     * @return set[]
     */
    public function get_orphaned_sets(): array {
        return $this->orphanedsets;
    }

    /**
     * Returns orphaned item with given id.
     *
     * @param int $itemid
     * @return item|null
     */
    public function find_orphaned_item(int $itemid): ?item {
        if (isset($this->orphanedcourses[$itemid])) {
            return $this->orphanedcourses[$itemid];
        }
        if (isset($this->orphanedtrainings[$itemid])) {
            return $this->orphanedtrainings[$itemid];
        }
        if (isset($this->orphanedsets[$itemid])) {
            return $this->orphanedsets[$itemid];
        }
        return null;
    }

    /**
     * Fetches all current prerequisites for given program id.
     *
     * @param int $programid
     * @return array
     */
    protected static function get_prerequisites(int $programid): array {
        global $DB;

        $sql = "SELECT p.*
                  FROM {tool_muprog_prerequisite} p
                  JOIN {tool_muprog_item} i ON i.id = p.itemid AND i.programid = :programid
                  JOIN {tool_muprog_item} pi ON pi.id = p.prerequisiteitemid AND pi.programid = i.programid
              ORDER BY p.id ASC";
        return $DB->get_records_sql($sql, ['programid' => $programid]);
    }

    /**
     * Add new course item to given parent set.
     *
     * @param set $parent
     * @param int $courseid
     * @param array $data
     * @return course
     */
    public function append_course(set $parent, int $courseid, array $data = []): course {
        global $DB;

        if ($parent->programid != $this->programid) {
            throw new \coding_exception('invalid programid');
        }
        $program = $DB->get_record('tool_muprog_program', ['id' => $this->programid], '*', MUST_EXIST);
        if (isset($this->orphanedsets[$parent->id])) {
            throw new \coding_exception('orphaned set cannot be modified');
        }

        if (array_key_exists('points', $data)) {
            if ($data['points'] < 0) {
                throw new \invalid_parameter_exception('Points cannot be negative');
            }
            $points = (string)(int)$data['points'];
        } else {
            $points = '1';
        }

        $completiondelay = $data['completiondelay'] ?? 0;
        if ($completiondelay < 0) {
            throw new \invalid_parameter_exception('Completion delay cannot be negative');
        }

        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

        $record = [
            'id' => null,
            'programid' => (string)$this->programid,
            'topitem' => null,
            'courseid' => (string)$course->id,
            'trainingid' => null,
            'previtemid' => null,
            'fullname' => $course->fullname,
            'sequencejson' => util::json_encode([]),
            'minprerequisites' => null,
            'points' => $points,
            'minpoints' => null,
            'completiondelay' => (string)$completiondelay,
        ];
        $fakerecords = [];
        $fakeprerequisites = [];
        /** @var course $item */
        $item = course::init_from_record((object)$record, null, $fakerecords, $fakeprerequisites);

        $trans = $DB->start_delegated_transaction();
        $item->id = (string)$DB->insert_record('tool_muprog_item', (object)$item->get_record());
        $parent->add_child($item);
        $DB->update_record('tool_muprog_item', (object)$parent->get_record());

        $this->fix_content();

        \tool_muprog\event\program_updated::create_from_program($program, 'item_append', $item->id)->trigger();

        $trans->allow_commit();

        // Do not use transactions for enrolments, we can always fix them later.
        allocation::fix_enrol_instances($this->programid);
        allocation::fix_user_enrolments($this->programid, null);

        return $item;
    }

    /**
     * Add new training item to given parent set.
     *
     * @param set $parent
     * @param int $trainingid
     * @param array $data
     * @return training
     */
    public function append_training(set $parent, int $trainingid, array $data = []): training {
        global $DB;

        if ($parent->programid != $this->programid) {
            throw new \coding_exception('invalid programid');
        }
        $program = $DB->get_record('tool_muprog_program', ['id' => $this->programid], '*', MUST_EXIST);
        if (isset($this->orphanedsets[$parent->id])) {
            throw new \coding_exception('orphaned set cannot be modified');
        }

        if (array_key_exists('points', $data)) {
            if ($data['points'] < 0) {
                throw new \invalid_parameter_exception('Points cannot be negative');
            }
            $points = (string)(int)$data['points'];
        } else {
            $points = '1';
        }

        $completiondelay = $data['completiondelay'] ?? 0;
        if ($completiondelay < 0) {
            throw new \invalid_parameter_exception('Completion delay cannot be negative');
        }

        if (!util::is_mutrain_available()) {
            throw new \core\exception\coding_exception('mutrain is not avialable');
        }

        $framework = $DB->get_record('tool_mutrain_framework', ['id' => $trainingid], '*', MUST_EXIST);

        $record = [
            'id' => null,
            'programid' => (string)$this->programid,
            'topitem' => null,
            'courseid' => null,
            'trainingid' => (string)$framework->id,
            'previtemid' => null,
            'fullname' => $framework->name,
            'sequencejson' => util::json_encode([]),
            'minprerequisites' => null,
            'points' => $points,
            'minpoints' => null,
            'completiondelay' => (string)$completiondelay,
        ];
        $fakerecords = [];
        $fakeprerequisites = [];
        /** @var training $item */
        $item = training::init_from_record((object)$record, null, $fakerecords, $fakeprerequisites);

        $trans = $DB->start_delegated_transaction();
        $item->id = (string)$DB->insert_record('tool_muprog_item', (object)$item->get_record());
        $parent->add_child($item);
        $DB->update_record('tool_muprog_item', (object)$parent->get_record());

        $this->fix_content();

        \tool_muprog\event\program_updated::create_from_program($program, 'item_append', $item->id)->trigger();

        $trans->allow_commit();

        // Do not use transactions for enrolments, we can always fix them later.
        allocation::fix_enrol_instances($this->programid);
        allocation::fix_user_enrolments($this->programid, null);

        return $item;
    }

    /**
     * Add new set to given parent set.
     *
     * @param set $parent
     * @param array $data
     * @return set
     */
    public function append_set(set $parent, array $data): set {
        global $DB;

        if ($parent->programid != $this->programid) {
            throw new \coding_exception('invalid programid');
        }
        $program = $DB->get_record('tool_muprog_program', ['id' => $this->programid], '*', MUST_EXIST);
        if (isset($this->orphanedsets[$parent->id])) {
            throw new \coding_exception('orphaned set cannot be modified');
        }

        $types = set::get_sequencetype_types();
        if (empty($data['sequencetype']) || !isset($types[$data['sequencetype']])) {
            throw new \coding_exception('invalid sequence type');
        }
        $sequencetype = $data['sequencetype'];

        if (array_key_exists('points', $data)) {
            if ($data['points'] < 0) {
                throw new \invalid_parameter_exception('Points cannot be negative');
            }
            $points = (string)(int)$data['points'];
        } else {
            $points = '1';
        }

        $fullname = $data['fullname'] ?? '';
        if (trim($fullname) === '') {
            throw new \invalid_parameter_exception('Fullname is required');
        }

        if ($sequencetype === set::SEQUENCE_TYPE_MINPOINTS) {
            if (!isset($data['minpoints']) || $data['minpoints'] <= 0) {
                throw new \coding_exception('Minimum points number is required');
            }
            $minprerequisites = null;
            $minpoints = (string)(int)$data['minpoints'];
        } else if ($sequencetype === set::SEQUENCE_TYPE_ATLEAST) {
            if (!isset($data['minprerequisites']) || $data['minprerequisites'] <= 0) {
                throw new \coding_exception('Minimum prerequisites number is required');
            }
            $minprerequisites = (string)(int)$data['minprerequisites'];
            $minpoints = null;
        } else {
            $minprerequisites = '1';
            $minpoints = null;
        }

        $sequence = [
            'children' => [],
            'type' => $sequencetype,
        ];

        $completiondelay = $data['completiondelay'] ?? 0;
        if ($completiondelay < 0) {
            throw new \invalid_parameter_exception('Completion delay cannot be negative');
        }

        $record = [
            'id' => null,
            'programid' => (string)$this->programid,
            'topitem' => null,
            'courseid' => null,
            'trainingid' => null,
            'previtemid' => null,
            'fullname' => $fullname,
            'sequencejson' => util::json_encode($sequence),
            'minprerequisites' => $minprerequisites,
            'points' => $points,
            'minpoints' => $minpoints,
            'completiondelay' => $completiondelay,
        ];

        $fakerecords = [];
        $fakeprerequisites = [];
        /** @var set $item */
        $item = set::init_from_record((object)$record, null, $fakerecords, $fakeprerequisites);

        $trans = $DB->start_delegated_transaction();
        $item->id = (string)$DB->insert_record('tool_muprog_item', (object)$item->get_record());
        $parent->add_child($item);
        $DB->update_record('tool_muprog_item', (object)$parent->get_record());

        $this->fix_content();

        \tool_muprog\event\program_updated::create_from_program($program, 'item_append', $item->id)->trigger();

        $trans->allow_commit();

        // Do not use transactions for enrolments, we can always fix them later.
        allocation::fix_enrol_instances($this->programid);
        allocation::fix_user_enrolments($this->programid, null);

        return $item;
    }

    /**
     * Update item set.
     *
     * @param set $set
     * @param array $data
     * @return set
     */
    public function update_set(set $set, array $data): set {
        global $DB;

        if ($set->programid != $this->programid) {
            throw new \coding_exception('invalid programid');
        }
        $program = $DB->get_record('tool_muprog_program', ['id' => $this->programid], '*', MUST_EXIST);
        if (isset($this->orphanedsets[$set->id])) {
            throw new \coding_exception('orphaned set cannot be modified');
        }

        if (array_key_exists('fullname', $data)) {
            if ($set->get_id() != $this->id) {
                $set->fullname = $data['fullname'];
            }
        }

        if (array_key_exists('sequencetype', $data)) {
            $sequencetype = $data['sequencetype'];
            $types = set::get_sequencetype_types();
            if (!isset($types[$sequencetype])) {
                throw new \coding_exception('invalid sequence type');
            }
            $set->sequencetype = $sequencetype;
            if ($sequencetype === set::SEQUENCE_TYPE_MINPOINTS) {
                if (!isset($data['minpoints']) || $data['minpoints'] <= 0) {
                    throw new \coding_exception('Minimum points number is required');
                }
                $set->minprerequisites = null;
                $set->minpoints = (string)(int)$data['minpoints'];
            } else if ($set->sequencetype === set::SEQUENCE_TYPE_ATLEAST) {
                if (!isset($data['minprerequisites']) || $data['minprerequisites'] <= 0) {
                    throw new \coding_exception('Minimum prerequisites number is required');
                }
                $set->minprerequisites = (string)(int)$data['minprerequisites'];
                $set->minpoints = null;
            } else {
                $set->minprerequisites = count($set->get_children());
                if (!$set->minprerequisites) {
                    $set->minprerequisites = 1;
                }
                $set->minpoints = null;
            }
        }

        if (array_key_exists('points', $data)) {
            if ($data['points'] < 0) {
                throw new \coding_exception('Points cannot be negative');
            }
            $set->points = (string)(int)$data['points'];
        }

        if (array_key_exists('completiondelay', $data)) {
            if ($data['completiondelay'] < 0) {
                throw new \invalid_parameter_exception('Completion delay cannot be negative');
            }
            $set->completiondelay = (int)$data['completiondelay'];
        }

        $trans = $DB->start_delegated_transaction();
        $DB->update_record('tool_muprog_item', (object)$set->get_record());

        $this->fix_content();

        \tool_muprog\event\program_updated::create_from_program($program, 'item_update', $set->id)->trigger();

        $trans->allow_commit();

        // Do not use transactions for enrolments, we can always fix them later.
        allocation::fix_enrol_instances($this->programid);
        allocation::fix_user_enrolments($this->programid, null);

        return $set;
    }

    /**
     * Update course item.
     *
     * @param course $course
     * @param array $data
     * @return course
     */
    public function update_course(course $course, array $data): course {
        global $DB;

        if ($course->programid != $this->programid) {
            throw new \coding_exception('invalid programid');
        }
        $program = $DB->get_record('tool_muprog_program', ['id' => $this->programid], '*', MUST_EXIST);

        if (!array_key_exists('points', $data)) {
            return $course;
        }

        if ($data['points'] < 0) {
            throw new \invalid_parameter_exception('Points cannot be negative');
        }

        $course->points = (string)(int)$data['points'];

        if (array_key_exists('completiondelay', $data)) {
            if ($data['completiondelay'] < 0) {
                throw new \invalid_parameter_exception('Completion delay cannot be negative');
            }
            $course->completiondelay = (int)$data['completiondelay'];
        }

        $trans = $DB->start_delegated_transaction();
        $DB->update_record('tool_muprog_item', (object)$course->get_record());

        $this->fix_content();

        \tool_muprog\event\program_updated::create_from_program($program, 'item_update', $course->id)->trigger();

        $trans->allow_commit();

        // Do not use transactions for enrolments, we can always fix them later.
        allocation::fix_enrol_instances($this->programid);
        allocation::fix_user_enrolments($this->programid, null);

        return $course;
    }

    /**
     * Update training item.
     *
     * @param training $training
     * @param array $data
     * @return training
     */
    public function update_training(training $training, array $data): training {
        global $DB;

        if ($training->programid != $this->programid) {
            throw new \coding_exception('invalid programid');
        }
        $program = $DB->get_record('tool_muprog_program', ['id' => $this->programid], '*', MUST_EXIST);

        if (!array_key_exists('points', $data)) {
            return $training;
        }

        if ($data['points'] < 0) {
            throw new \invalid_parameter_exception('Points cannot be negative');
        }

        $training->points = (string)(int)$data['points'];

        if (array_key_exists('completiondelay', $data)) {
            if ($data['completiondelay'] < 0) {
                throw new \invalid_parameter_exception('Completion delay cannot be negative');
            }
            $training->completiondelay = (int)$data['completiondelay'];
        }

        $trans = $DB->start_delegated_transaction();
        $DB->update_record('tool_muprog_item', (object)$training->get_record());

        $this->fix_content();

        \tool_muprog\event\program_updated::create_from_program($program, 'item_update', $training->id)->trigger();

        $trans->allow_commit();

        // Do not use transactions for enrolments, we can always fix them later.
        allocation::fix_enrol_instances($this->programid);
        allocation::fix_user_enrolments($this->programid, null);

        return $training;
    }

    /**
     * Move item to a different parent or position.
     *
     * @param int $itemid
     * @param int $parentid
     * @param int $position
     * @return bool
     */
    public function move_item(int $itemid, int $parentid, int $position): bool {
        global $DB;

        if ($itemid == $parentid) {
            debugging('Item cannot be moved to self', DEBUG_DEVELOPER);
            return false;
        }
        $program = $DB->get_record('tool_muprog_program', ['id' => $this->programid], '*', MUST_EXIST);
        if ($itemid == $this->get_id()) {
            debugging('Top item cannot be moved', DEBUG_DEVELOPER);
            return false;
        }

        $item = $this->find_item($itemid);
        if (!$item) {
            debugging('Cannot find new item', DEBUG_DEVELOPER);
            return false;
        }
        $oldparent = $this->find_parent_set($item->get_id());
        if (!$oldparent) {
            debugging('Cannot find new item parent', DEBUG_DEVELOPER);
        }

        $newparent = $this->find_item($parentid);
        if (!$newparent || !($newparent instanceof set)) {
            debugging('Cannot find new parent of item', DEBUG_DEVELOPER);
            return false;
        }

        if ($item->find_item($newparent->get_id())) {
            debugging('Cannot move item to own child', DEBUG_DEVELOPER);
            return false;
        }

        $trans = $DB->start_delegated_transaction();

        if ($oldparent->get_id() != $newparent->get_id()) {
            foreach ($oldparent->children as $i => $child) {
                if ($child->get_id() == $item->get_id()) {
                    unset($oldparent->children[$i]);
                    $oldparent->children = array_values($oldparent->children);
                    break;
                }
            }
            if ($oldparent->sequencetype === set::SEQUENCE_TYPE_ALLINORDER || $oldparent->sequencetype === set::SEQUENCE_TYPE_ALLINANYORDER) {
                $oldparent->minprerequisites = count($oldparent->children);
            }
            if ($oldparent->sequencetype !== set::SEQUENCE_TYPE_MINPOINTS) {
                if ($oldparent->minprerequisites < 1) {
                    $oldparent->minprerequisites = 1;
                }
            }
            $DB->update_record('tool_muprog_item', (object)$oldparent->get_record());
        }

        $newchildren = [];
        $added = false;
        $i = 0;
        foreach ($newparent->children as $child) {
            if ($i == $position) {
                $newchildren[] = $item;
                $added = true;
            }
            if ($child->get_id() != $item->get_id()) {
                $newchildren[] = $child;
            }
            $i++;
        }
        if (!$added) {
            $newchildren[] = $item;
        }
        $newparent->children = $newchildren;
        if ($newparent->sequencetype === set::SEQUENCE_TYPE_ALLINORDER || $newparent->sequencetype === set::SEQUENCE_TYPE_ALLINANYORDER) {
            $newparent->minprerequisites = count($newparent->children);
        }
        if ($newparent->sequencetype !== set::SEQUENCE_TYPE_MINPOINTS) {
            if ($newparent->minprerequisites < 1) {
                $newparent->minprerequisites = 1;
            }
        }
        $DB->update_record('tool_muprog_item', (object)$newparent->get_record());

        $this->fix_content();

        \tool_muprog\event\program_updated::create_from_program($program, 'item_move', $item->id)->trigger();

        $trans->allow_commit();

        // Do not use transactions for enrolments, we can always fix them later.
        allocation::fix_enrol_instances($this->programid);
        allocation::fix_user_enrolments($this->programid, null);

        return true;
    }

    /**
     * Delete item if possible.
     *
     * @param int $itemid
     * @return bool true if item deleted
     */
    public function delete_item(int $itemid): bool {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/group/lib.php');

        $program = $DB->get_record('tool_muprog_program', ['id' => $this->programid], '*', MUST_EXIST);

        $item = $this->find_item($itemid);
        if ($item) {
            if (!$item->is_deletable()) {
                return false;
            }
            $parent = $this->find_parent_set($item->get_id());
            if (!$parent) {
                debugging('Cannot find parent of item to be deleted', DEBUG_DEVELOPER);
                return false;
            }
            foreach ($parent->children as $i => $child) {
                if ($child->get_id() == $itemid) {
                    unset($parent->children[$i]);
                    break;
                }
            }
            $parent->children = array_values($parent->children);
            if ($parent->sequencetype === set::SEQUENCE_TYPE_ALLINORDER || $parent->sequencetype === set::SEQUENCE_TYPE_ALLINANYORDER) {
                $parent->minprerequisites = count($parent->get_children());
                if (!$parent->minprerequisites) {
                    $parent->minprerequisites = 1;
                }
            }
        } else {
            $item = $this->find_orphaned_item($itemid);
            if (!$item) {
                return false;
            }
            // Do not bother with orphaned item parents, just delete it.
            $parent = null;
            if ($item instanceof course) {
                unset($this->orphanedcourses[$item->get_id()]);
            } else if ($item instanceof training) {
                unset($this->orphanedtrainings[$item->get_id()]);
            } else {
                unset($this->orphanedsets[$item->get_id()]);
            }
        }

        $trans = $DB->start_delegated_transaction();

        $record = $DB->get_record('tool_muprog_item', ['id' => $itemid], '*', MUST_EXIST);
        if ($record->courseid !== null) {
            $groups = $DB->get_records('tool_muprog_group', ['programid' => $record->programid, 'courseid' => $record->courseid]);
            foreach ($groups as $g) {
                groups_delete_group($g->groupid);
            }
        }
        $DB->delete_records('tool_muprog_prerequisite', ['itemid' => $itemid]);
        $DB->delete_records('tool_muprog_prerequisite', ['prerequisiteitemid' => $itemid]);
        if ($parent) {
            $parent->remove_chid($itemid);
            $DB->update_record('tool_muprog_item', (object)$parent->get_record());
        }
        $DB->delete_records('tool_muprog_evidence', ['itemid' => $itemid]);
        $DB->delete_records('tool_muprog_completion', ['itemid' => $itemid]);
        $DB->delete_records('tool_muprog_item', ['id' => $itemid]);

        $this->fix_content();

        \tool_muprog\event\program_updated::create_from_program($program, 'item_delete', $itemid)->trigger();

        $trans->allow_commit();

        // Do not use transactions for enrolments, we can always fix them later.
        allocation::fix_enrol_instances($this->programid);
        allocation::fix_user_enrolments($this->programid, null);

        return true;
    }

    /**
     * Import content from another program.
     *
     * @param \stdClass $data from \tool_muprog\local\form\program_content_import_confirmation
     * @return void
     */
    public function content_import(\stdClass $data) {
        if ($data->fromprogram == $this->programid) {
            throw new \coding_exception('invalid parameters');
        }
        if ($data->id != $this->programid) {
            throw new \coding_exception('invalid parameters');
        }
        $topfrom = self::load($data->fromprogram);
        if (!$this->get_children()) {
            $this->update_set($this, [
                'fullname' => $this->get_fullname(),
                'sequencetype' => $topfrom->get_sequencetype(),
                'minprerequisites' => $topfrom->get_minprerequisites(),
                'minpoints' => $topfrom->get_minpoints(),
                'points' => $topfrom->get_points(),
                'completiondelay' => $topfrom->get_completiondelay(),
            ]);
        }
        $copyfunction = function (item $item, set $newparent, top $top) use (&$copyfunction) {
            global $DB;
            if ($item instanceof course) {
                if (!$DB->record_exists('course', ['id' => $item->get_courseid()])) {
                    return;
                }
                if ($newparent === $top) {
                    // Prevent duplicate course at the top level.
                    foreach ($top->get_children() as $tch) {
                        if ($tch instanceof course) {
                            if ($tch->get_courseid() == $item->get_courseid()) {
                                return;
                            }
                        }
                    }
                }
                $top->append_course($newparent, $item->get_courseid(), [
                    'points' => $item->get_points(),
                    'completiondelay' => $item->get_completiondelay(),
                ]);
            } else if ($item instanceof training) {
                $top->append_training($newparent, $item->get_trainingid(), [
                    'points' => $item->get_points(),
                    'completiondelay' => $item->get_completiondelay(),
                ]);
            } else if ($item instanceof set) {
                $newset = $top->append_set($newparent, [
                    'fullname' => $item->get_fullname(),
                    'sequencetype' => $item->get_sequencetype(),
                    'minprerequisites' => $item->get_minprerequisites(),
                    'minpoints' => $item->get_minpoints(),
                    'points' => $item->get_points(),
                    'completiondelay' => $item->get_completiondelay(),
                ]);
                foreach ($item->get_children() as $child) {
                    $copyfunction($child, $newset, $top);
                }
            }
        };
        foreach ($topfrom->get_children() as $item) {
            $copyfunction($item, $this, $this);
        }
    }

    /**
     * Update content in database to match the in-memory representation.
     *
     * @return void
     */
    protected function fix_content(): void {
        global $DB;

        $this->fix_previous(null);

        $saveclosure = function (item $item) use (&$saveclosure, &$DB): void {
            $record = $item->get_record();
            if ($record['id']) {
                $oldrecord = $DB->get_record('tool_muprog_item', ['id' => $record['id']]);
                if ($oldrecord) {
                    foreach ((array)$oldrecord as $k => $v) {
                        if ($record[$k] !== $v) {
                            $DB->update_record('tool_muprog_item', (object)$record);
                            break;
                        }
                    }
                } else {
                    debugging('Ignoring update of missing item', DEBUG_DEVELOPER);
                }
            } else {
                $item->id = (string)$DB->insert_record('tool_muprog_item', $record);
            }

            foreach ($item->get_children() as $child) {
                $saveclosure($child);
            }
        };

        $saveclosure($this);

        foreach ($this->get_orphaned_courses() as $item) {
            $saveclosure($item);
        }
        foreach ($this->get_orphaned_trainings() as $item) {
            $saveclosure($item);
        }
        foreach ($this->get_orphaned_sets() as $item) {
            $saveclosure($item);
        }

        // Fix all pre-requisites.
        $prerequisites = self::get_prerequisites($this->programid);
        $this->fix_prerequisites($prerequisites);
        foreach ($prerequisites as $prerequisite) {
            $DB->delete_records('tool_muprog_prerequisite', ['id' => $prerequisite->id]);
        }
    }

    /**
     * Attempt to automatically fix the content structure.
     *
     * @return void
     */
    public function autorepair(): void {
        global $DB;

        $trans = $DB->start_delegated_transaction();
        $this->fix_content();
        $trans->allow_commit();

        // Do not use transactions for enrolments, we can always fix them later.
        allocation::fix_enrol_instances($this->programid);
        allocation::fix_user_enrolments($this->programid, null);
    }
}
