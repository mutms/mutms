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

namespace tool_mulib\output;

/**
 * Page headers actions - combo of buttons and dropdown.
 *
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class header_actions implements \core\output\named_templatable, \core\output\renderable {
    /** @var dropdown */
    private $dropdown;
    /** @var array */
    private $buttons = [];

    /**
     * Constructor.
     *
     * @param string $title actions dropdown title
     */
    public function __construct(string $title) {
        $this->dropdown = new dropdown($title);
    }

    /**
     * Add button.
     *
     * @param mixed $button renderable or html
     * @return void
     */
    final public function add_button($button): void {
        if (!$button instanceof \core\output\renderable && !is_string($button)) {
            debugging('Invalid $button parameter, must be \core\output\renderable or string', DEBUG_DEVELOPER);
            return;
        }
        $this->buttons[] = $button;
    }

    /**
     * Returns actions menu dropdown to be used when adding actions.
     *
     * @return dropdown
     */
    final public function get_dropdown(): dropdown {
        return $this->dropdown;
    }

    /**
     * Are there any items?
     * @return bool
     */
    final public function has_items(): bool {
        return ($this->buttons || $this->dropdown->has_items());
    }

    /**
     * Export data for template.
     *
     * @param \renderer_base $output
     * @return array
     */
    final public function export_for_template(\renderer_base $output): array {
        if ($this->buttons) {
            $buttons = [];
            foreach ($this->buttons as $button) {
                if ($button instanceof \core\output\renderable) {
                    $buttons[] = $output->render($button);
                } else {
                    $buttons[] = (string)$button;
                }
            }
            $buttons = implode(' ', $buttons);
        } else {
            $buttons = null;
        }
        if ($this->dropdown->has_items()) {
            $dropdown = $this->dropdown->export_for_template($output);
        } else {
            $dropdown = null;
        }
        return [
            'buttons' => $buttons,
            'dropdown' => $dropdown,
        ];
    }

    /**
     * Get the name of the template to use for this templatable.
     *
     * @param \renderer_base $renderer The renderer requesting the template name
     * @return string
     */
    final public function get_template_name(\renderer_base $renderer): string {
        return 'tool_mulib/header_actions';
    }
}
