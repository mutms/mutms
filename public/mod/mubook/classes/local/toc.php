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

/**
 * Table of contents structure.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class toc {
    /** @var int no chapter numbers */
    public const NUMBERING_NONE = 0;
    /** @var int 1, 1.1, 1.2, 2, ... */
    public const NUMBERING_DOTSEPARATOR = 1;
    /** @var int 1., 1.1., 1.2., ... */
    public const NUMBERING_DOTTRAILING = 2;

    /** @var stdClass  */
    private $mubook;
    /** @var \context_module book context */
    private $context;

    /** @var chapter[] array of ordered chapter records with additional numbers array */
    private $chapters;
    /** @var chapter[] array of chapters that are not attached properly to TOC */
    private $orphaned = [];

    /** @var array numbers for chapters */
    private $chapternumbers;

    /**
     * Constructor.
     *
     * @param stdClass $mubook
     */
    public function __construct(stdClass $mubook) {
        global $DB;
        $cm = get_coursemodule_from_instance('mubook', $mubook->id, $mubook->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        $allchapters = $DB->get_records('mubook_chapter', ['mubookid' => $mubook->id], 'sortorder ASC, id ASC');

        $chapters = [];
        $chapternumbers = [];
        $addchildren = function (?int $parentid, array $numbers) use (&$allchapters, &$chapters, &$chapternumbers, &$addchildren, $mubook, $context): void {
            if (count($numbers) >= 2) {
                // Nesting problem.
                return;
            }
            $i = 0;
            foreach ($allchapters as $chid => $chapter) {
                if ($parentid == $chapter->parentid) {
                    $i++;
                    $chapters[$chid] = new chapter($chapter, $mubook, $context);
                    $chapternumbers[$chid] = array_merge($numbers, [$i]);
                    unset($allchapters[$chid]);
                    $addchildren($chid, $chapternumbers[$chid]);
                }
            }
        };

        $addchildren(null, []);

        $this->mubook = $mubook;
        $this->context = $context;
        $this->chapters = $chapters;
        $this->chapternumbers = $chapternumbers;

        foreach ($allchapters as $chapter) {
            $this->orphaned[$chapter->id] = new chapter($chapter, $mubook, $context);
        }
    }

    /**
     * Returns all valid book chapters.
     *
     * @return chapter[]
     */
    public function get_chapters(): array {
        return $this->chapters;
    }

    /**
     * Returns book record.
     *
     * @return stdClass
     */
    public function get_mubook(): stdClass {
        return $this->mubook;
    }

    /**
     * Returns book context.
     *
     * @return \context_module
     */
    public function get_context(): \context_module {
        return $this->context;
    }

    /**
     * Returns orphaned chapters.
     *
     * @return chapter[]
     */
    public function get_orphaned_chapters(): array {
        return $this->orphaned;
    }

    /**
     * Is chapter orphaned?
     *
     * @param int $chapterid
     * @return bool
     */
    public function is_orphaned_chapter(int $chapterid): bool {
        return isset($this->orphaned[$chapterid]);
    }

    /**
     * Returns a book chapter if exists.
     *
     * @param int $chapterid
     * @param int $strictness
     * @return chapter|null
     */
    public function get_chapter(int $chapterid, int $strictness = MUST_EXIST): ?chapter {
        if (isset($this->chapters[$chapterid])) {
            return $this->chapters[$chapterid];
        }
        if (isset($this->orphaned[$chapterid])) {
            return $this->orphaned[$chapterid];
        }

        if ($strictness == MUST_EXIST) {
            throw new invalid_parameter_exception('invalid book chapter');
        }
        return null;
    }

    /**
     * Fetch first chapter.
     *
     * @return chapter|null
     */
    public function get_first_chapter(): ?chapter {
        if (!$this->chapters) {
            return null;
        }
        return reset($this->chapters);
    }

    /**
     * Fetch last chapter.
     *
     * @return chapter|null
     */
    public function get_last_chapter(): ?chapter {
        if (!$this->chapters) {
            return null;
        }
        return end($this->chapters);
    }

    /**
     * Fetch last subchapter.
     *
     * @param int $parentid parent chapter id
     * @return chapter|null
     */
    public function get_last_subchapter(int $parentid): ?chapter {
        $last = null;
        foreach ($this->chapters as $chapter) {
            if ($chapter->parentid == $parentid) {
                $last = $chapter;
            }
        }
        return $last;
    }


    /**
     * Returns previous chapter of the give chapter.
     *
     * @param int $chapterid
     * @return chapter|null
     */
    public function get_previous_chapter(int $chapterid): ?chapter {
        $prev = null;
        foreach ($this->chapters as $ch) {
            if ($ch->id == $chapterid) {
                return $prev;
            }
            $prev = $ch;
        }
        return null;
    }

    /**
     * Returns next chapter of the givem chapter.
     *
     * @param int $chapterid
     * @return chapter|null
     */
    public function get_next_chapter(int $chapterid): ?chapter {
        $found = false;
        foreach ($this->chapters as $ch) {
            if ($found) {
                return $ch;
            }
            if ($ch->id == $chapterid) {
                $found = true;
            }
        }
        return null;
    }


    /**
     * Returns chapter numbering options.
     *
     * @return string[]
     */
    public static function get_numbering_menu(): array {
        return [
            self::NUMBERING_NONE => get_string('none'),
            self::NUMBERING_DOTSEPARATOR => '1, 1.1, 1.2',
            self::NUMBERING_DOTTRAILING => '1., 1.1., 1.2.',
        ];
    }

    /**
     * Returns chapter and optional subchapter number.
     *
     * @param int $chapterid
     * @return array empty array for non-existent and orphaned chapters
     */
    public function get_chapter_numbers(int $chapterid): array {
        if (!isset($this->chapternumbers[$chapterid])) {
            return [];
        }
        return $this->chapternumbers[$chapterid];
    }

    /**
     * Format chapter numbers.
     *
     * @param int $chapterid
     * @return string|null
     */
    public function format_chapter_numbers(int $chapterid): ?string {
        if (0 == $this->mubook->numbering) {
            return null;
        }

        $numbers = $this->get_chapter_numbers($chapterid);
        if (!$numbers) {
            return null;
        }

        if (2 == $this->mubook->numbering) {
            return implode('.', $numbers) . '.';
        }

        // Option 1 is the default.
        return implode('.', $numbers);
    }

    /**
     * Returns formatted chapter title with numbers.
     *
     * @param int $chapterid
     * @return string
     */
    public function get_numbered_chapter_title(int $chapterid): string {
        $chapter = $this->get_chapter($chapterid);
        if (!$chapter) {
            return '';
        }
        $title = $chapter->format_title();

        $numbers = $this->format_chapter_numbers($chapterid);
        if ($numbers === null) {
            return $title;
        }

        return $numbers . ' ' . $title;
    }


    /**
     * Fix all sortorder fields of non-orphaned chapters if necessary.
     *
     * @param int $mubookid
     * @return self
     */
    public static function fix_sortorders(int $mubookid): self {
        global $DB;

        $mubook = $DB->get_record('mubook', ['id' => $mubookid], '*', MUST_EXIST);

        $toc = new self($mubook);

        foreach ($toc->get_chapters() as $chapter) {
            $numbers = $toc->get_chapter_numbers($chapter->id);
            if (!$numbers) {
                // This should not happen.
                continue;
            }
            if ($chapter->parentid) {
                $sortorder = $numbers[1];
            } else {
                $sortorder = $numbers[0];
            }
            if ($chapter->sortorder != $sortorder) {
                $DB->set_field('mubook_chapter', 'sortorder', $sortorder, ['id' => $chapter->id]);
            }
        }

        return new self($mubook);
    }
}
