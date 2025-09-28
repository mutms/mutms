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
 * Action menu dropdown.
 *
 * @package     tool_mulib
 * @copyright   2024 Open LMS (https://www.openlms.net/)
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dropdown implements \core\output\named_templatable, \core\output\renderable {
    /** @var array $items links, dividers or custom html fragments */
    private $items = [];
    /** @var string */
    private $title;

    /**
     * Constructor.
     *
     * @param string $title
     */
    public function __construct(string $title) {
        $this->title = $title;
    }

    /**
     * Add standard link item to dropdown.
     *
     * @param string $label
     * @param \moodle_url $url
     * @param \core\output\pix_icon|null $icon
     * @param string $class
     */
    final public function add_item(string $label, \moodle_url $url, ?\core\output\pix_icon $icon = null, string $class = ''): void {
        $this->items[] = ['label' => $label, 'url' => $url->out(false), 'icon' => $icon, 'class' => $class];
    }

    /**
     * Add divider element.
     */
    final public function add_divider(): void {
        $this->items[] = ['divider' => true];
    }

    /**
     * Add link that opens ajax_form.
     *
     * @param \tool_mulib\output\ajax_form\link $link
     */
    final public function add_ajax_form(\tool_mulib\output\ajax_form\link $link): void {
        global $OUTPUT;
        $oldclasses = $link->get_classes();
        $link->add_class('dropdown-item');
        $this->items[] = ['customhtml' => $OUTPUT->render($link)];
        $link->set_classes($oldclasses);
    }

    /**
     * Are there any items?
     *
     * @return bool
     */
    final public function has_items(): bool {
        return !empty($this->items);
    }

    /**
     * Export data for template.
     *
     * @param \renderer_base $output
     * @return array
     */
    final public function export_for_template(\renderer_base $output): array {
        $items = [];
        foreach ($this->items as $item) {
            if (isset($item['icon'])) {
                $item['icon'] = \core\output\icon_system::instance()->render_pix_icon($output, $item['icon']);
            }
            $items[] = $item;
        }

        return [
            'title' => $this->title,
            'items' => $items,
        ];
    }

    /**
     * Get the name of the template to use for this templatable.
     *
     * @param \renderer_base $renderer The renderer requesting the template name
     * @return string
     */
    final public function get_template_name(\renderer_base $renderer): string {
        return 'tool_mulib/dropdown';
    }
}
