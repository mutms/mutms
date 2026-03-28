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

/**
 * Behat generator for mod_mubook.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_mubook_generator extends behat_generator_base {
    /**
     * Get a list of the entities that Behat can create using the generator step.
     *
     * @return array
     */
    protected function get_creatable_entities(): array {
        return [
            'chapters' => [
                'singular' => 'chapter',
                'datagenerator' => 'chapter',
                'required' => ['mubook'],
                'switchids' => ['mubook' => 'mubookid', 'positionafter' => 'position'],
            ],
            'chapter_contents' => [
                'singular' => 'chapter_content',
                'datagenerator' => 'chapter_content',
                'required' => ['chapter', 'type'],
                'switchids' => ['chapter' => 'chapterid'],
            ],
        ];
    }

    /**
     * Get interactive book id from name or idnumber.
     *
     * @param string $idnumberorname
     * @return int
     */
    protected function get_mubook_id(string $idnumberorname): int {
        return $this->get_cm_by_activity_name('mubook', $idnumberorname)->instance;
    }

    /**
     * Get (sub)chapter position.
     *
     * @param string $after
     * @return ?int
     */
    protected function get_positionafter_id(string $after): ?int {
        global $DB;
        if ($after === '') {
            return null;
        }
        $chapter = $DB->get_record('mubook_chapter', ['title' => $after], '*', MUST_EXIST);
        return $chapter->id;
    }

    /**
     * Get chapter id from title.
     *
     * @param string $title
     * @return int
     */
    protected function get_chapter_id(string $title): int {
        global $DB;
        $chapter = $DB->get_record('mubook_chapter', ['title' => $title], '*', MUST_EXIST);
        return $chapter->id;
    }
}
