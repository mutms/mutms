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
use core\url;
use tool_mulib\output\ajax_form\link;

/**
 * Chapter content base class.
 *
 * @property-read int $id
 * @property-read string $type
 * @property-read int $chapterid
 * @property-read int $sortorder
 * @property-read string $data1
 * @property-read string $data2
 * @property-read string $data3
 * @property-read int $auxint1
 * @property-read int $auxint2
 * @property-read int $auxint3
 * @property-read int $unsafetrusted
 * @property-read int $hidden
 * @property-read int $groupid
 * @property-read string $originjson
 * @property-read int $timecreated
 * @property-read int $timemodified
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class content {
    /** @var stdClass */
    protected $record;
    /** @var chapter */
    protected $chapter;
    /** @var stdClass */
    protected $mubook;
    /** @var \context_module */
    protected $context;

    /**
     * Constructor.
     *
     * @param stdClass $record
     * @param chapter $chapter
     * @param stdClass $mubook
     * @param \context_module $context
     */
    final public function __construct(stdClass $record, chapter $chapter, stdClass $mubook, \context_module $context) {
        $statictype = static::get_type();
        if ($record->type !== $statictype && $statictype !== 'unknown') {
            throw new coding_exception('Incorrect content type');
        }
        if ($mubook->id != $chapter->mubookid) {
            throw new coding_exception('Book id mismatch');
        }
        if ($chapter->id != $record->chapterid) {
            throw new coding_exception('Chapter id mismatch');
        }
        $this->record = $record;
        $this->chapter = $chapter;
        $this->mubook = $mubook;
        $this->context = $context;
    }

    /**
     * Returns internal type name.
     *
     * @return string
     */
    public static function get_type(): string {
        $classname = explode('\\', static::class);
        return end($classname);
    }

    /**
     * Returns human-readable content type name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('content_type_' . self::get_type(), 'mod_mubook');
    }

    /**
     * Returns one line description of the content block instable.
     *
     * @return string
     */
    public function get_identification(): string {

        // NOTE: add some content preview in overridden class if possible.

        return $this->sortorder . ' - ' . static::get_name();
    }

    /**
     * Is the content type unsafe?
     *
     * Unsafe content requires 'mod/mubook:usexss' for creation and updates.
     *
     * @return bool
     */
    public static function is_unsafe(): bool {
        return false;
    }

    /**
     * Returns list of all used file areas.
     *
     * NOTE: it is recommended to use 'content' area if necessary.
     *
     * @return string[]
     */
    public static function get_file_areas(): array {
        return [];
    }

    /**
     * Returns base for component file serving.
     *
     * @param string $filearea
     * @return string file serving URL base with trailing slash.
     */
    public function get_fileserving_base(string $filearea): string {
        global $CFG;
        return "{$CFG->wwwroot}/pluginfile.php/{$this->context->id}/mod_mubook/{$filearea}/{$this->record->id}/";
    }

    /**
     * Create content.
     *
     * @param stdClass $data form data
     * @return self
     */
    final public static function create(stdClass $data): self {
        global $DB;

        $cman = \core\di::get(content_manager::class);

        $chapterrecord = $DB->get_record('mubook_chapter', ['id' => $data->chapterid], '*', MUST_EXIST);
        $mubook = $DB->get_record('mubook', ['id' => $chapterrecord->mubookid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('mubook', $mubook->id, $mubook->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $chapter = new \mod_mubook\local\chapter($chapterrecord, $mubook, $context);

        $formclass = static::get_create_form_classname();

        $now = time();
        $record = (object)[
            'type' => static::get_type(),
            'chapterid' => $data->chapterid,
            'sortorder' => $data->sortorder ?? 0,
            'hidden' => (int)(bool)($data->hidden ?? 0),
            'timecreated' => $now,
            'timemodified' => $now,
        ];

        $trans = $DB->start_delegated_transaction();

        if ($record->sortorder < 1) {
            $record->sortorder = 1 + (int)$DB->get_field('mubook_content', 'MAX(sortorder)', ['chapterid' => $record->chapterid]);
        }
        self::vacate_sortorder($record->chapterid, $record->sortorder);

        $formclass::before_db_insert($record, $data, $chapter, $mubook, $context);

        $record->id = $DB->insert_record('mubook_content', $record);
        self::fix_sortorders($chapter->id);
        $record = $DB->get_record('mubook_content', ['id' => $record->id], '*', MUST_EXIST);

        $formclass::after_db_insert($record, $data, $chapter, $mubook, $context);

        $trans->allow_commit();

        $record = $DB->get_record('mubook_content', ['id' => $record->id], '*', MUST_EXIST);
        $content = $cman->create_instance($record, $chapter);

        \mod_mubook\event\content_created::create_from_content($content)->trigger();

        return $content;
    }

    /**
     * Update content instance.
     *
     * @param stdClass $data
     * @return self
     */
    final public function update(stdClass $data): self {
        global $DB;

        $cman = \core\di::get(content_manager::class);

        if ($data->id != $this->record->id) {
            throw new \core\exception\invalid_parameter_exception('content id mismatch');
        }

        $contentrecord = $DB->get_record('mubook_content', ['id' => $data->id, 'type' => self::get_type()], '*', MUST_EXIST);
        $chapterrecord = $DB->get_record('mubook_chapter', ['id' => $contentrecord->chapterid], '*', MUST_EXIST);
        $mubook = $DB->get_record('mubook', ['id' => $chapterrecord->mubookid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('mubook', $mubook->id, $mubook->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $chapter = new chapter($chapterrecord, $mubook, $context);

        $formclass = static::get_update_form_classname();

        $record = (object)[
            'id' => $data->id,
            'timemodified' => time(),
        ];
        if (property_exists($data, 'hidden')) {
            $record->hidden = (int)(bool)$data->hidden;
        }

        $trans = $DB->start_delegated_transaction();

        if (property_exists($data, 'sortorder')) {
            self::move_to_sortorder($chapterrecord->id, $contentrecord->sortorder, $data->sortorder);
        }

        $formclass::before_db_update($record, $data, $chapter, $mubook, $context);

        $DB->update_record('mubook_content', $record);
        self::fix_sortorders($chapter->id);
        $record = $DB->get_record('mubook_content', ['id' => $record->id], '*', MUST_EXIST);

        $formclass::after_db_update($record, $data, $chapter, $mubook, $context);

        $trans->allow_commit();

        $record = $DB->get_record('mubook_content', ['id' => $record->id], '*', MUST_EXIST);
        $content = $cman->create_instance($record, $chapter);

        \mod_mubook\event\content_updated::create_from_content($this)->trigger();

        return $content;
    }

    /**
     * Delete content instance.
     */
    public function delete(): void {
        global $DB;

        $cman = \core\di::get(content_manager::class);

        $trans = $DB->start_delegated_transaction();

        $DB->delete_records('mubook_content', ['id' => $this->record->id]);

        $fs = get_file_storage();
        foreach (static::get_file_areas() as $area) {
            $fs->delete_area_files($this->context->id, 'mod_mubook', $area, $this->record->id);
        }

        self::fix_sortorders($this->chapter->id);

        $trans->allow_commit();

        \mod_mubook\event\content_deleted::create_from_content($this)->trigger();
    }

    /**
     * Vacate sortorder position.
     *
     * @param int $chapterid
     * @param int $sortorder
     * @return void
     */
    private static function vacate_sortorder(int $chapterid, int $sortorder): void {
        global $DB;

        $sql = "UPDATE {mubook_content}
                   SET sortorder = sortorder + 1
                 WHERE chapterid = :chapterid AND sortorder >= :sortorder";
        $params = ['chapterid' => $chapterid, 'sortorder' => $sortorder];
        $DB->execute($sql, $params);
    }

    /**
     * Change content sortorder to different value.
     *
     * @param int $chapterid
     * @param int $sortorder1
     * @param int $sortorder2
     * @return void
     */
    private static function move_to_sortorder(int $chapterid, int $sortorder1, int $sortorder2): void {
        global $DB;

        $DB->set_field('mubook_content', 'sortorder', -1, ['chapterid' => $chapterid, 'sortorder' => $sortorder1]);

        if ($sortorder1 < $sortorder2) {
            $sql = "UPDATE {mubook_content}
                       SET sortorder = sortorder - 1
                     WHERE chapterid = :chapterid AND sortorder > :sortorder1 AND sortorder <= :sortorder2";
            $params = ['chapterid' => $chapterid, 'sortorder1' => $sortorder1, 'sortorder2' => $sortorder2];
            $DB->execute($sql, $params);
        } else if ($sortorder1 > $sortorder2) {
            $sql = "UPDATE {mubook_content}
                       SET sortorder = sortorder + 1
                     WHERE chapterid = :chapterid AND sortorder < :sortorder1 AND sortorder >= :sortorder2";
            $params = ['chapterid' => $chapterid, 'sortorder1' => $sortorder1, 'sortorder2' => $sortorder2];
            $DB->execute($sql, $params);
        }

        $DB->set_field('mubook_content', 'sortorder', $sortorder2, ['chapterid' => $chapterid, 'sortorder' => -1]);
    }

    /**
     * Fix sortorder for all contents of given chapter.
     *
     * @param int $chapterid
     * @return void
     */
    private static function fix_sortorders(int $chapterid): void {
        global $DB;

        $contents = $DB->get_records('mubook_content', ['chapterid' => $chapterid], 'sortorder ASC, id ASC', 'id, sortorder');

        $i = 0;
        foreach ($contents as $content) {
            $i++;
            if ($content->sortorder != $i) {
                $DB->set_field('mubook_content', 'sortorder', $i, ['id' => $content->id]);
            }
        }
    }

    /**
     * Returns database record.
     *
     * @return stdClass
     */
    final public function get_record(): stdClass {
        return clone($this->record);
    }

    /**
     * Returns chapter.
     *
     * @return chapter
     */
    final public function get_chapter(): chapter {
        return $this->chapter;
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
     * Is current user allowed to create new content?
     *
     * @param chapter|null $chapter null means new chapter
     * @param stdClass $mubook
     * @param \context_module $context
     * @return bool
     */
    public static function can_create(?chapter $chapter, stdClass $mubook, \context_module $context): bool {
        if (!has_capability('mod/mubook:editcontent', $context)) {
            return false;
        }

        if (static::is_unsafe()) {
            if (!has_capability('mod/mubook:usexss', $context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns content creation URL.
     *
     * @param chapter $chapter
     * @param int $sortorder 1 means first, 0 means last
     * @return url|null
     */
    public static function get_create_url(chapter $chapter, int $sortorder): ?url {
        return new url(
            '/mod/mubook/management/content_create.php',
            ['chapterid' => $chapter->id, 'sortorder' => $sortorder, 'type' => static::get_type()]
        );
    }

    /**
     * Returns content creation class.
     *
     * @return class-string<\mod_mubook\local\form\content_create_base>
     */
    public static function get_create_form_classname(): string {
        return '\\mod_mubook\\local\\content\\form\\' . static::get_type() . '_create';
    }

    /**
     * Is current user allowed to update the content?
     *
     * @return bool
     */
    public function can_update(): bool {
        if (!has_capability('mod/mubook:editcontent', $this->context)) {
            return false;
        }

        if (static::is_unsafe()) {
            if (!has_capability('mod/mubook:usexss', $this->context)) {
                return false;
            }
        }

        if (!$this->can_view()) {
            return false;
        }

        return true;
    }

    /**
     * Returns content update URL.
     * @return url|null
     */
    public function get_update_url(): ?url {
        return new url(
            '/mod/mubook/management/content_update.php',
            ['id' => $this->record->id]
        );
    }

    /**
     * Returns content update class.
     *
     * @return class-string<\mod_mubook\local\form\content_update_base>
     */
    public static function get_update_form_classname(): string {
        return '\\mod_mubook\\local\\content\\form\\' . static::get_type() . '_update';
    }

    /**
     * Is current user allowed to delete the content?
     *
     * @return bool
     */
    public function can_delete(): bool {
        if (!has_capability('mod/mubook:editcontent', $this->context)) {
            return false;
        }

        if (!$this->can_view()) {
            return false;
        }

        return true;
    }

    /**
     * Returns deletion action link.
     *
     * @return link
     */
    final public function get_delete_link(): link {
        $url = new url(
            '/mod/mubook/management/content_delete.php',
            ['id' => $this->record->id]
        );
        $link = new link($url, get_string('content_delete', 'mod_mubook'), 'i/delete');
        $link->add_class('text-danger');
        $link->set_submitted_action($link::SUBMITTED_ACTION_RELOAD);

        return $link;
    }

    /**
     * Restore callback.
     *
     * @param stdClass $data
     * @param \restore_mubook_activity_structure_step $step
     * @return void
     */
    public static function restore_callback(stdClass $data, \restore_mubook_activity_structure_step $step): void {
    }

    /**
     * Is current user allowed to view the content?
     *
     * @return bool
     */
    public function can_view(): bool {
        if ($this->record->hidden) {
            if (!has_capability('mod/mubook:viewhiddencontent', $this->context)) {
                return false;
            }
        }

        // TODO: add group restriction here.

        return true;
    }

    /**
     * Render content instance.
     *
     * @param \renderer_base $output
     * @param toc $toc
     * @param bool $editing
     * @param int $firstheading
     * @param int $headingoffset
     * @return string
     */
    public function render(\renderer_base $output, toc $toc, bool $editing, int $firstheading, int $headingoffset = 0): string {
        throw new coding_exception('content rendering method must be overridden');
    }

    /**
     * Send attachment file.
     *
     * @param string $fullpath
     * @param bool $forcedownload
     * @param array $options
     * @return void|false
     */
    public function send_file(string $fullpath, bool $forcedownload, array $options) {
        $fs = get_file_storage();

        $file = $fs->get_file_by_hash(sha1($fullpath));
        if (!$file || $file->is_directory()) {
            return false;
        }

        send_stored_file($file, 0, 0, true, $options);
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
        throw new coding_exception('content properties cannot be changed directly');
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
        throw new coding_exception('content properties cannot be changed directly');
    }
}
