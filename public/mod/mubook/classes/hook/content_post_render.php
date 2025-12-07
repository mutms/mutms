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
// phpcs:disable moodle.Commenting.VariableComment.Missing

namespace mod_mubook\hook;

use mod_mubook\local\toc;
use mod_mubook\local\chapter;
use mod_mubook\local\content;

/**
 * Hook triggered after content is rendered.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\label('Interactive book content post rendering hook')]
#[\core\attribute\tags('mod_mubook')]
final class content_post_render {
    /**
     * Constructor.
     *
     * @param string $html
     * @param content $content
     * @param chapter $chapter
     * @param \renderer_base $output
     * @param toc $toc
     * @param bool $editing
     * @param int $firstheading
     * @param int $headingoffset
     */
    public function __construct(
        public string $html,
        public content $content,
        public chapter $chapter,
        public \renderer_base $output,
        public toc $toc,
        public bool $editing,
        public int $firstheading,
        public int $headingoffset
    ) {
        \core\di::get(\core\hook\manager::class)->dispatch($this);
    }
}
