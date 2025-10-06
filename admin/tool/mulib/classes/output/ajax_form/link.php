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
 * Text link with optional icon that opens modal ajax form.
 *
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class link extends action {
    /**
     * Create a text link with optional icon that opens a form in modal dialog.
     *
     * @param moodle_url $formurl
     * @param string|lang_string $text link text
     * @param string $pixname optional icon name
     * @param string $pixcomponent icon component
     */
    public function __construct(
        moodle_url $formurl,
        string|lang_string $text,
        string $pixname = '',
        string $pixcomponent = 'moodle'
    ) {
        parent::__construct($formurl, $text);
        if ($pixname !== '') {
            $this->icon = new \core\output\pix_icon($pixname, '', $pixcomponent, ['aria-hidden' => 'true']);
        }
    }

    /**
     * Create reportbuilder report action.
     *
     * @param array $attributes action attributes
     * @return \core_reportbuilder\local\report\action
     */
    public function create_report_action(array $attributes = []): \core_reportbuilder\local\report\action {
        $formsubmittedaction = json_encode($this->formsubmittedaction);
        $formsize = json_encode($this->formsize);
        $modaltitle = json_encode($this->modaltitle ?? (string)$this->label);

        $attributes['onclick'] = "
let link = this;
require(['tool_mulib/ajax_form/modal'], function(AjaxFormModal) {
    AjaxFormModal.create({
        formUrl: link.href,
        formSize: $formsize,
        formSubmittedAction: $formsubmittedaction,
        title: $modaltitle,
    });
});
return false;";
        $attributes['title'] = $this->label;

        $action = new \core_reportbuilder\local\report\action(
            $this->formurl,
            $this->icon,
            $attributes,
            false,
            null
        );

        return $action;
    }

    /**
     * Create icon with the same data.
     *
     * @return icon
     */
    public function create_icon(): icon {
        if ($this->icon) {
            $icon = new icon($this->formurl, $this->label, $this->icon->pix, $this->icon->component);
        } else {
            debugging('Link does not have an icon defined', DEBUG_DEVELOPER);
            $icon = new icon($this->formurl, $this->label, 'i/empty', 'core');
        }
        $icon->set_form_size($this->formsize);
        $icon->set_submitted_action($this->formsubmittedaction);
        $icon->set_modal_title($this->modaltitle);
        return $icon;
    }

    /**
     * Create button with the same data.
     *
     * @param bool $primary
     * @param bool $useicon
     * @return button
     */
    public function create_button(bool $primary = false, bool $useicon = false): button {
        $button = new button($this->formurl, $this->label, $primary);
        $button->set_form_size($this->formsize);
        $button->set_submitted_action($this->formsubmittedaction);
        $button->set_modal_title($this->modaltitle);
        if ($useicon && $this->icon) {
            $button->set_icon($this->icon);
        }
        return $button;
    }
}
