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

namespace tool_murelation\navigation\views;

use stdClass;

/**
 * Relation framework pages secondary menu.
 *
 * @package     tool_murelation
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class framework_secondary extends \core\navigation\views\secondary {
    /** @var stdClass */
    protected $framework;

    /**
     * navigation constructor.
     * @param \moodle_page $page
     * @param stdClass $framework
     */
    public function __construct(\moodle_page $page, stdClass $framework) {
        parent::__construct($page);
        $this->framework = $framework;
    }

    /**
     * Init secondary menu.
     */
    public function initialise(): void {
        $this->id = 'secondary_navigation';
        $framework = $this->framework;

        $this->headertitle = get_string('menu');

        $syscontext = \context_system::instance();

        if (has_capability('tool/murelation:viewframeworks', $syscontext)) {
            $url = new \moodle_url('/admin/tool/murelation/management/framework.php', ['id' => $framework->id]);
            $this->add(get_string('framework_details', 'tool_murelation'), $url, \navigation_node::TYPE_SETTING, null, 'framework_details');
        }

        if (has_capability('tool/murelation:viewpositions', $syscontext)) {
            if ($framework->uimode == \tool_murelation\local\framework::UIMODE_TEAMS) {
                $url = new \moodle_url('/admin/tool/murelation/management/framework_teams.php', ['id' => $framework->id]);
                $this->add(get_string('teams', 'tool_murelation'), $url, \navigation_node::TYPE_SETTING, null, 'framework_teams');
                $url = new \moodle_url('/admin/tool/murelation/management/framework_members.php', ['id' => $framework->id]);
                $this->add(format_string($framework->subordinatestitle), $url, \navigation_node::TYPE_SETTING, null, 'framework_members');
            } else if ($framework->uimode == \tool_murelation\local\framework::UIMODE_SUPERVISORS) {
                $url = new \moodle_url('/admin/tool/murelation/management/framework_subordinates.php', ['id' => $framework->id]);
                $this->add(format_string($framework->subordinatestitle), $url, \navigation_node::TYPE_SETTING, null, 'framework_subordinates');
            }
        }

        $this->scan_for_active_node($this);
        $this->initialised = true;
    }
}
