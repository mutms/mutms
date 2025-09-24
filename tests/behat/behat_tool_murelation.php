<?php
// This file is part of MuTMS suite of plugins for Moodle™ LMS.
//
// This framework is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This framework is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this framework.  If not, see <https://www.gnu.org/licenses/>.

// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');

/**
 * User relations behat steps.
 *
 * @package    tool_murelation
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_tool_murelation extends behat_base {
    /**
     * Convert page names to URLs for steps like 'When I am on the "[page name]" page'.
     *
     * @param string $page name of the page, with the component name removed e.g. 'Admin notification'.
     * @return moodle_url the corresponding URL.
     */
    protected function resolve_page_url(string $page): moodle_url {
        switch (strtolower($page)) {
            case 'user relation frameworks':
                return new moodle_url('/admin/tool/murelation/management/index.php');

            default:
                throw new Exception('Unrecognised tool_murelation page "' . $page . '."');
        }
    }

    /**
     * Convert page names to URLs for steps like 'When I am on the "[identifier]" "[page type]" page'.
     *
     * @param string $type identifies which type of page this is, e.g. 'Preview'.
     * @param string $identifier identifies the particular page, e.g. 'My question'.
     * @return moodle_url the corresponding URL.
     */
    protected function resolve_page_instance_url(string $type, string $identifier): moodle_url {
        global $DB;
        switch (strtolower($type)) {
            case 'framework':
                $framework = $DB->get_record('tool_murelation_framework', ['idnumber' => $identifier]);
                if (!$framework) {
                    $framework = $DB->get_record('tool_murelation_framework', ['name' => $identifier]);
                }
                if (!$framework) {
                    throw new Exception('Invalid framework "' . $identifier . '."');
                }
                return new moodle_url('/admin/tool/murelation/management/framework.php', ['id' => $framework->id]);

            default:
                throw new Exception('Unrecognised tool_murelation page type "' . $type . '."');
        }
    }
}
