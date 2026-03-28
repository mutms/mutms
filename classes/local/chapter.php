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

namespace mod_mubook\local;

use stdClass;
use core\exception\coding_exception;
use core\exception\invalid_parameter_exception;
use tool_mulib\output\ajax_form\button;
use tool_mulib\output\ajax_form\link;
use tool_mulib\local\mulib;

/**
 * Book chapter.
 *
 * @property-read int $id
 * @property-read int $mubookid
 * @property-read int|null $parentid
 * @property-read string $title
 * @property-read int $sortorder
 * @property-read string|null $originjson
 * @property-read int $timecreated
 * @property-read int $timemodified
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class chapter {
    /** @var stdClass */
    private $record;
    /** @var stdClass */
    private $mubook;
    /** @var \context_module book context */
    private $context;

    /**
     * Constructor.
     *
     * @param stdClass $record
     * @param stdClass $mubook
     * @param \context_module $context
     */
    public function __construct(stdClass $record, stdClass $mubook, \context_module $context) {
        if ($mubook->id != $record->mubookid) {
            throw new coding_exception('Book id mismatch');
        }
        $this->record = $record;
        $this->mubook = $mubook;
        $this->context = $context;
    }

    /**
     * Create a new chapter.
     *
     * @param stdClass $data
     * @return chapter
     */
    public static function create(stdClass $data): chapter {
        global $DB;

        $mubook = $DB->get_record('mubook', ['id' => $data->mubookid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('mubook', $mubook->id, $mubook->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        if (trim($data->title ?? '') === '') {
            throw new invalid_parameter_exception('Chapter title is required');
        }

        $sortorder = 1;
        $parent = null;
        if ($data->subchapter ?? null) {
            $afterchapter = $DB->get_record('mubook_chapter', ['id' => $data->position, 'mubookid' => $mubook->id], '*', MUST_EXIST);
            if ($afterchapter->parentid) {
                $parent = $DB->get_record('mubook_chapter', ['id' => $afterchapter->parentid, 'mubookid' => $mubook->id], '*', MUST_EXIST);
                $sortorder = $afterchapter->sortorder + 1;
            } else {
                $parent = $afterchapter;
            }
        } else {
            if (!isset($data->position)) {
                $sortorder = 1 + (int)$DB->get_field('mubook_chapter', 'MAX(sortorder)', ['mubookid' => $mubook->id, 'parentid' => null]);
            } else if ($data->position) {
                $afterchapter = $DB->get_record('mubook_chapter', ['id' => $data->position, 'mubookid' => $mubook->id, 'parentid' => null], '*', MUST_EXIST);
                $sortorder = $afterchapter->sortorder + 1;
            }
        }

        $record = new stdClass();
        $record->mubookid = $mubook->id;
        if ($parent) {
            $record->parentid = $parent->id;
        } else {
            $record->parentid = null;
        }
        $record->title = $data->title;
        $record->sortorder = $sortorder;
        $record->timecreated = time();
        $record->timemodified = $record->timecreated;

        $trans = $DB->start_delegated_transaction();

        $sql = new \tool_mulib\local\sql(
            "UPDATE {mubook_chapter}
                SET sortorder = sortorder + 1
              WHERE mubookid = :mubookid AND sortorder >= :sortorder /* parent */",
            ['mubookid' => $record->mubookid, 'sortorder' => $record->sortorder]
        );
        if ($record->parentid) {
            $sql = $sql->replace_comment('parent', "AND parentid = ?", [$record->parentid]);
        } else {
            $sql = $sql->replace_comment('parent', "AND parentid IS NULL");
        }
        $DB->execute($sql->sql, $sql->params);

        $record->id = $DB->insert_record('mubook_chapter', $record);

        $record = $DB->get_record('mubook_chapter', ['id' => $record->id], '*', MUST_EXIST);

        if (isset($data->tags)) {
            \core_tag_tag::set_item_tags('mod_mubook', 'mubook_chapter', $record->id, $context, $data->tags);
        }

        $trans->allow_commit();

        $chapter = new chapter($record, $mubook, $context);

        \mod_mubook\event\chapter_created::create_from_chapter($chapter)->trigger();

        return $chapter;
    }

    /**
     * Update chapter title.
     *
     * @param stdClass $data
     * @return chapter
     */
    public static function update(stdClass $data): chapter {
        global $DB;

        $record = $DB->get_record('mubook_chapter', ['id' => $data->id], '*', MUST_EXIST);
        $mubook = $DB->get_record('mubook', ['id' => $record->mubookid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('mubook', $mubook->id, $mubook->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        if (trim($data->title ?? '') === '') {
            throw new invalid_parameter_exception('Chapter title is required');
        }

        if ($record->title === $data->title) {
            // Nothing to update.
            return new chapter($record, $mubook, $context);
        }

        $update = (object)[
            'id' => $record->id,
            'title' => $data->title,
            'timemodified' => time(),
        ];

        $trans = $DB->start_delegated_transaction();

        $DB->update_record('mubook_chapter', $update);

        if (isset($data->tags)) {
            \core_tag_tag::set_item_tags('mod_mubook', 'mubook_chapter', $update->id, $context, $data->tags);
        }

        $trans->allow_commit();

        $record = $DB->get_record('mubook_chapter', ['id' => $record->id], '*', MUST_EXIST);
        $chapter = new chapter($record, $mubook, $context);

        \mod_mubook\event\chapter_updated::create_from_chapter($chapter)->trigger();

        return $chapter;
    }

    /**
     * Delete chapter and optionally all subchapters.
     *
     * NOTE: if subchapters are not deleted then they become orphaned.
     *
     * @param int $chapterid
     * @param bool $includesubchapters
     * @return void
     */
    public static function delete(int $chapterid, bool $includesubchapters): void {
        global $DB;

        $record = $DB->get_record('mubook_chapter', ['id' => $chapterid]);
        if (!$record) {
            return;
        }

        $mubook = $DB->get_record('mubook', ['id' => $record->mubookid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('mubook', $mubook->id, $mubook->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $chapter = new self($record, $mubook, $context);

        $trans = $DB->start_delegated_transaction();

        if ($includesubchapters) {
            $subchapters = $DB->get_records('mubook_chapter', ['parentid' => $record->id, 'mubookid' => $record->mubookid], 'sortorder DESC');
            foreach ($subchapters as $subchapter) {
                self::delete($subchapter->id, false);
            }
        }

        $cman = \core\di::get(\mod_mubook\local\content_manager::class);
        $contents = $DB->get_records('mubook_content', ['chapterid' => $record->id], 'sortorder DESC');
        foreach ($contents as $contentrecord) {
            $instance = $cman->create_instance($contentrecord, $chapter);
            $instance->delete();
        }

        \core_tag_tag::remove_all_item_tags('mod_mubook', 'mubook_chapter', $chapter->id);

        $DB->delete_records('mubook_chapter', ['id' => $record->id]);

        $sql = new \tool_mulib\local\sql(
            "UPDATE {mubook_chapter}
                SET sortorder = sortorder - 1
              WHERE mubookid = :mubookid AND sortorder > :sortorder /* parent */",
            ['mubookid' => $record->mubookid, 'sortorder' => $record->sortorder]
        );
        if ($record->parentid) {
            $sql = $sql->replace_comment('parent', "AND parentid = ?", [$record->parentid]);
        } else {
            $sql = $sql->replace_comment('parent', "AND parentid IS NULL");
        }
        $DB->execute($sql->sql, $sql->params);

        $trans->allow_commit();

        \mod_mubook\event\chapter_deleted::create_from_chapter($chapter)->trigger();
    }

    /**
     * Move chapter.
     *
     * @param int $chapterid
     * @param bool $subchapter
     * @param int $position chapter id, item is placed right after it, 0 means first chapter
     * @return chapter
     */
    public static function move(int $chapterid, bool $subchapter, int $position): chapter {
        global $DB;

        $record = $DB->get_record('mubook_chapter', ['id' => $chapterid], '*', MUST_EXIST);
        $mubook = $DB->get_record('mubook', ['id' => $record->mubookid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('mubook', $mubook->id, $mubook->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        if ($subchapter && !$record->parentid) {
            if ($DB->record_exists('mubook_chapter', ['parentid' => $record->id])) {
                throw new invalid_parameter_exception('Chapter with sub-chapters cannot become a sub-chapter');
            }
        }

        $oldparentid = $record->parentid;
        $sortorder = 1;
        $parent = null;
        if ($subchapter) {
            $afterchapter = $DB->get_record('mubook_chapter', ['id' => $position, 'mubookid' => $mubook->id], '*', MUST_EXIST);
            if ($afterchapter->parentid) {
                $parent = $DB->get_record('mubook_chapter', ['id' => $afterchapter->parentid, 'mubookid' => $mubook->id], '*', MUST_EXIST);
                $sortorder = $afterchapter->sortorder + 1;
            } else {
                $parent = $afterchapter;
            }
        } else {
            if ($position) {
                $afterchapter = $DB->get_record('mubook_chapter', ['id' => $position, 'mubookid' => $mubook->id, 'parentid' => null], '*', MUST_EXIST);
                $sortorder = $afterchapter->sortorder + 1;
            }
        }
        $newparentid = $parent->id ?? null;

        $trans = $DB->start_delegated_transaction();

        // Vacate sortorder.
        $sql = new \tool_mulib\local\sql(
            "UPDATE {mubook_chapter}
                SET sortorder = sortorder + 1
              WHERE mubookid = :mubookid AND sortorder >= :sortorder /* parent */",
            ['mubookid' => $record->mubookid, 'sortorder' => $sortorder]
        );
        if ($parent) {
            $sql = $sql->replace_comment('parent', "AND parentid = ?", [$parent->id]);
        } else {
            $sql = $sql->replace_comment('parent', "AND parentid IS NULL");
        }
        $DB->execute($sql->sql, $sql->params);

        $update = [
            'id' => $record->id,
            'parentid' => $parent->id ?? null,
            'sortorder' => $sortorder,
            'timemodified' => time(),
        ];
        $DB->update_record('mubook_chapter', $update);

        // Fix sort orders.
        $parentids = [$oldparentid];
        if ($oldparentid != $newparentid) {
            $parentids[] = $newparentid;
        }
        foreach ($parentids as $parentid) {
            $chapters = $DB->get_records('mubook_chapter', ['mubookid' => $mubook->id, 'parentid' => $parentid], 'sortorder ASC, id ASC', 'id, sortorder');
            $i = 0;
            foreach ($chapters as $ch) {
                $i++;
                if ($ch->sortorder != $i) {
                    $DB->set_field('mubook_chapter', 'sortorder', $i, ['id' => $ch->id]);
                }
            }
        }

        $trans->allow_commit();

        $record = $DB->get_record('mubook_chapter', ['id' => $record->id], '*', MUST_EXIST);
        $chapter = new chapter($record, $mubook, $context);

        \mod_mubook\event\chapter_moved::create_from_chapter($chapter)->trigger();

        return $chapter;
    }

    /**
     * Returns chapter database record.
     *
     * @return stdClass
     */
    public function get_record(): stdClass {
        return clone($this->record);
    }

    /**
     * Returns mubook record.
     *
     * @return stdClass
     */
    public function get_mubook(): stdClass {
        return $this->mubook;
    }

    /**
     * Returns mubook context.
     *
     * @return \context_module
     */
    public function get_context(): \context_module {
        return $this->context;
    }

    /**
     * Is current user allowed to create chapters?
     *
     * @param stdClass $mubook
     * @param \context_module $context
     * @return bool
     */
    public static function can_create(stdClass $mubook, \context_module $context): bool {
        return has_capability('mod/mubook:editchapter', $context);
    }

    /**
     * Is current user allowed to update chapter?
     *
     * @return bool
     */
    public function can_update(): bool {
        return has_capability('mod/mubook:editchapter', $this->context);
    }

    /**
     * Is current user allowed to move chapter?
     *
     * @return bool
     */
    public function can_move(): bool {
        return has_capability('mod/mubook:editchapter', $this->context);
    }

    /**
     * Is current user allowed to delete chapter?
     *
     * @return bool
     */
    public function can_delete(): bool {
        return has_capability('mod/mubook:editchapter', $this->context);
    }

    /**
     * Returns dialog link for creation of a chapter?
     *
     * @param stdClass $mubook
     * @param int $position
     * @param bool $subchapter
     * @param int $fromcreatechapterid
     * @return link
     */
    public static function get_create_link(stdClass $mubook, int $position, bool $subchapter, int $fromcreatechapterid = -1): link {
        $url = new \core\url(
            '/mod/mubook/management/chapter_create.php',
            ['mubookid' => $mubook->id, 'subchapter' => (int)$subchapter, 'position' => $position]
        );
        if ($fromcreatechapterid >= 0) {
            $url->param('fromcreatechapterid', $fromcreatechapterid);
        }
        if ($subchapter) {
            $action = new link($url, get_string('subchapter_create', 'mod_mubook'), 'subchapter', 'mod_mubook');
        } else {
            $action = new link($url, get_string('chapter_create', 'mod_mubook'), 'chapter', 'mod_mubook');
        }
        $action->set_submitted_action($action::SUBMITTED_ACTION_REDIRECT);
        return $action;
    }

    /**
     * Return dialog link for updating of a chapter.
     *
     * @return link
     */
    public function get_update_link(): link {
        $url = new \core\url(
            '/mod/mubook/management/chapter_update.php',
            ['id' => $this->record->id]
        );
        if ($this->record->parentid) {
            $action = new link($url, get_string('subchapter_update', 'mod_mubook'), 'i/edit');
        } else {
            $action = new link($url, get_string('chapter_update', 'mod_mubook'), 'i/edit');
        }
        $action->set_submitted_action($action::SUBMITTED_ACTION_RELOAD);
        return $action;
    }

    /**
     * Returns a dialog link for moving of a chapter.
     *
     * @return link
     */
    public function get_move_link(): link {
        $url = new \core\url(
            '/mod/mubook/management/chapter_move.php',
            ['id' => $this->record->id]
        );
        if ($this->record->parentid) {
            $action = new link($url, get_string('subchapter_move', 'mod_mubook'), 't/move');
        } else {
            $action = new link($url, get_string('chapter_move', 'mod_mubook'), 't/move');
        }
        $action->set_submitted_action($action::SUBMITTED_ACTION_REDIRECT);
        return $action;
    }

    /**
     * Returns a dialog link for deleting of a chapter.
     *
     * @return link
     */
    public function get_delete_link(): link {
        $url = new \core\url(
            '/mod/mubook/management/chapter_delete.php',
            ['id' => $this->record->id]
        );
        if ($this->record->parentid) {
            $action = new link($url, get_string('subchapter_delete', 'mod_mubook'), 'i/delete');
        } else {
            $action = new link($url, get_string('chapter_delete', 'mod_mubook'), 'i/delete');
        }
        $action->add_class('text-danger');
        $action->set_submitted_action($action::SUBMITTED_ACTION_REDIRECT);
        return $action;
    }

    /**
     * Format chapter title.
     *
     * @return string
     */
    public function format_title(): string {
        $title = format_string($this->title, true, ['context' => $this->context]);
        return mulib::clean_string($title);
    }

    /**
     * Fetch chapter contents.
     *
     * @return content[]
     */
    public function get_contents(): array {
        global $DB;

        $mubook = $this->get_mubook();
        $context = $this->get_context();

        if ($this->record->mubookid != $mubook->id) {
            throw new coding_exception('mismatched toc');
        }

        $cman = \core\di::get(\mod_mubook\local\content_manager::class);
        $records = $DB->get_records('mubook_content', ['chapterid' => $this->record->id], 'sortorder ASC, id ASC');

        $contents = [];
        foreach ($records as $record) {
            $contents[$record->id] = $cman->create_instance($record, $this);
        }

        return $contents;
    }

    /**
     * Magic getter method.
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name): mixed {
        return $this->record->$name;
    }

    /**
     * Magic setter method.
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set(string $name, mixed $value): void {
        throw new coding_exception('chapter properties cannot be modified directly');
    }

    /**
     * Magic isset method.
     *
     * @param string $name
     * @return bool
     */
    public function __isset(string $name): bool {
        return isset($this->record->$name);
    }

    /**
     * Magic unset method.
     *
     * @param string $name
     */
    public function __unset(string $name): void {
        throw new coding_exception('chapter properties cannot be modified directly');
    }
}
