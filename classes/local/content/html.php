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

namespace mod_mubook\local\content;

use mod_mubook\local\toc;
use mod_mubook\local\html_formatter;
use tool_mulib\local\mulib;

/**
 * Content in HTML format.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class html extends \mod_mubook\local\content {
    #[\Override]
    public function get_identification(): string {
        $ident = parent::get_identification();
        if (trim($this->data1 ?? '') === '') {
            return $ident;
        }
        return $ident . ' - ' . mulib::clean_string(trim(shorten_text(strip_tags($this->data1), 20)));
    }

    #[\Override]
    public function render(\renderer_base $output, toc $toc, bool $editing, int $firstheading, int $headingoffset = 0): string {
        $context = $toc->get_context();

        $html = file_rewrite_pluginfile_urls($this->data1 ?? '', 'pluginfile.php', $context->id, 'mod_mubook', 'content', $this->id);
        $html = html_formatter::normalise_headings($html, $firstheading);

        $formatoptions = [
            'noclean' => false,
            'para' => false,
            'context' => $context,
            'overflowdiv' => false,
        ];
        $html = format_text($html, FORMAT_HTML, $formatoptions);

        return '<div class="mubook-format-html no-overflow">' . $html . '</div>';
    }

    #[\Override]
    public static function get_file_areas(): array {
        return ['content'];
    }
}
