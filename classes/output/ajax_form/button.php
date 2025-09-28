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
 * Button that opens modal ajax form.
 *
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class button extends action {
    /** @var bool */
    protected $primary;

    /**
     * Create button that opens a form in modal dialog.
     *
     * @param moodle_url $formurl
     * @param string|lang_string $label button label
     * @param bool $primary is this a primary button?
     */
    public function __construct(moodle_url $formurl, string|lang_string $label, bool $primary = false) {
        parent::__construct($formurl, $label);
        $this->primary = $primary;

        $this->add_class('singlebutton');
    }

    /**
     * Set button as primary.
     *
     * @param bool $value
     * @return static
     */
    public function set_primary(bool $value): static {
        $this->primary = $value;
        return $this;
    }

    #[\Override]
    public function export_for_template(\renderer_base $output): array {
        $data = parent::export_for_template($output);
        $data['primary'] = $this->primary;

        return $data;
    }
}
