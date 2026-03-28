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

namespace tool_mulib\output\ajax_form;

use moodle_url;
use lang_string;

/**
 * Action icon that opens modal ajax form.
 *
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class icon extends action {
    /**
     * Create and image link that opens a form in modal dialog.
     *
     * @param moodle_url $formurl
     * @param string|lang_string $title element title
     * @param string $pixname icon name
     * @param string $pixcomponent icon component
     */
    public function __construct(moodle_url $formurl, string|lang_string $title, string $pixname, string $pixcomponent = 'moodle') {
        parent::__construct($formurl, $title);
        $this->icon = new \core\output\pix_icon($pixname, '', $pixcomponent, ['aria-hidden' => 'true']);
    }
}
