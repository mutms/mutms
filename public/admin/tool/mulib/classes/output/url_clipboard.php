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
use core\url;

/**
 * A link with copy to clipboard icon.
 *
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class url_clipboard implements \core\output\named_templatable, \renderable {
    /** @var url */
    protected $url;
    /** @var string */
    protected $copylabel;

    /**
     * Constructor.
     *
     * @param url $url
     * @param string $copylabel
     */
    public function __construct(url $url, string $copylabel) {
        $this->url = $url;
        $this->copylabel = $copylabel;
    }

    /**
     * Export data for template.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $output): array {
        return [
            'url' => $this->url->out(false),
            'copylabel' => \tool_mulib\local\mulib::clean_string($this->copylabel),
        ];
    }

    /**
     * Get the name of the template to use for this templatable.
     *
     * @param \renderer_base $renderer The renderer requesting the template name
     * @return string
     */
    public function get_template_name(\renderer_base $renderer): string {
        return 'tool_mulib/url_clipboard';
    }
}
