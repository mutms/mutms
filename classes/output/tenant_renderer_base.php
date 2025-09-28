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

namespace tool_mutenancy\output;

/**
 * Tenant renderer.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class tenant_renderer_base extends \plugin_renderer_base {
    /**
     * Set up page.
     *
     * @param \stdClass $tenant
     * @return void
     */
    public function setup_page(\stdClass $tenant): void {
        $syscontext = \context_system::instance();
        if (has_capability('tool/mutenancy:view', $syscontext)) {
            $url = new \moodle_url('/admin/tool/mutenancy/index.php');
            $this->page->navbar->add(get_string('tenants', 'tool_mutenancy'), $url);
        }
        $this->page->navbar->add(format_string($tenant->name), $this->page->url);

        $this->page->set_heading($tenant->name);

        $secondarynav = new \tool_mutenancy\navigation\views\tenant_secondary($this->page);
        $secondarynav->initialise();
        $this->page->set_secondarynav($secondarynav);
    }

    /**
     * Render section details.
     *
     * @param \stdClass $tenant
     * @return string
     */
    abstract public function render_section(\stdClass $tenant): string;
}
