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

namespace tool_mucertify\navigation\views;

use tool_mucertify\local\assignment;
use stdClass;
use moodle_url;

/**
 * Certification page secondary menu.
 *
 * @package     tool_mucertify
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certification_secondary extends \core\navigation\views\secondary {
    /** @var stdClass Certification */
    protected $certification;

    /**
     * navigation constructor.
     * @param \moodle_page $page
     * @param stdClass $certification
     */
    public function __construct(\moodle_page $page, stdClass $certification) {
        parent::__construct($page);
        $this->certification = $certification;
    }

    /**
     * Init secondary menu.
     */
    public function initialise(): void {
        $this->id = 'secondary_navigation';
        $this->headertitle = get_string('menu');

        $certification = $this->certification;

        $url = new moodle_url('/admin/tool/mucertify/management/certification.php', ['id' => $certification->id]);
        $this->add(get_string('tabgeneral', 'tool_mucertify'), $url, \navigation_node::TYPE_SETTING, null, 'certification_general');

        $url = new moodle_url('/admin/tool/mucertify/management/certification_settings.php', ['id' => $certification->id]);
        $this->add(get_string('tabsettings', 'tool_mucertify'), $url, \navigation_node::TYPE_SETTING, null, 'certification_periods');

        $url = new moodle_url('/admin/tool/mucertify/management/certification_visibility.php', ['id' => $certification->id]);
        $this->add(get_string('tabvisibility', 'tool_mucertify'), $url, \navigation_node::TYPE_SETTING, null, 'certification_visibility');

        $url = new moodle_url('/admin/tool/mucertify/management/certification_assignment.php', ['id' => $certification->id]);
        $this->add(get_string('tabassignment', 'tool_mucertify'), $url, \navigation_node::TYPE_SETTING, null, 'certification_assignment');

        $url = new moodle_url('/admin/tool/mucertify/management/certification_notifications.php', ['id' => $certification->id]);
        $this->add(get_string('notifications', 'tool_mulib'), $url, \navigation_node::TYPE_SETTING, null, 'certification_notifications');

        /** @var \tool_mucertify\local\source\base[] $sourceclasses */ // Class name hack.
        $sourceclasses = assignment::get_source_classes();
        foreach ($sourceclasses as $sourceclass) {
            $sourceclass::add_certification_secondary_tabs($this, $certification);
        }

        $url = new moodle_url('/admin/tool/mucertify/management/certification_users.php', ['id' => $certification->id]);
        $this->add(get_string('tabusers', 'tool_mucertify'), $url, \navigation_node::TYPE_SETTING, null, 'certification_users');

        $this->scan_for_active_node($this);
        $this->initialised = true;
    }
}
