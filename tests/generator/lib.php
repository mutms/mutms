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

use mod_mubook\local\chapter;
use mod_mubook\local\content;

/**
 * Interactive book generator.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_mubook_generator extends \testing_module_generator {
    /** @var int */
    protected $chaptercount = 0;

    #[\Override]
    public function reset(): void {
        $this->chaptercount = 0;
        parent::reset();
    }

    #[\Override]
    public function create_instance($record = null, ?array $options = null): stdClass {
        $record = (object)(array)$record;

        return parent::create_instance($record, (array)$options);
    }

    /**
     * Create a chapter.
     *
     * @param stdClass|array $record
     * @return chapter
     */
    public function create_chapter(stdClass|array $record): chapter {
        global $DB;

        $record = (object)(array)$record;

        $mubook = $DB->get_record('mubook', ['id' => $record->mubookid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('mubook', $mubook->id, $mubook->course, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $context = context_module::instance($cm->id);

        $this->chaptercount++;
        if (empty($record->title)) {
            $record->title = 'Chapter ' . $this->chaptercount;
        }

        return chapter::create($record);
    }

    /**
     * Create a chapter content.
     *
     * @param stdClass|array $record
     * @return content
     */
    public function create_chapter_content(stdClass|array $record): content {
        global $DB;

        $record = (object)(array)$record;

        $chapterrecord = $DB->get_record('mubook_chapter', ['id' => $record->chapterid], '*', MUST_EXIST);
        $mubook = $DB->get_record('mubook', ['id' => $chapterrecord->mubookid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('mubook', $mubook->id, $mubook->course, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $context = context_module::instance($cm->id);
        $chapter = new chapter($chapterrecord, $mubook, $context);

        $changetounknown = false;
        if ($record->type === 'unknown') {
            $changetounknown = true;
            $record->type = 'markdown';
        }

        $cman = \core\di::get(\mod_mubook\local\content_manager::class);
        $classname = $cman->get_class($record->type);
        if (!$classname) {
            throw new \core\exception\coding_exception('$record->type must be a valid content type');
        }

        if (!isset($record->sortorder)) {
            $record->sortorder = 0;
        }

        $content = $classname::create($record);

        if ($changetounknown) {
            $DB->set_field('mubook_content', 'type', 'xyzunknowncyz', ['id' => $content->id]);
            $DB->set_field('mubook_content', 'data1', null, ['id' => $content->id]);
            $contentrecord = $DB->get_record('mubook_content', ['id' => $content->id], '*', MUST_EXIST);
            $content = $cman->create_instance($contentrecord, $chapter);
        }

        return $content;
    }
}
