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

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');

/**
 * Programs behat steps.
 *
 * @package    tool_muprog
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_tool_muprog extends behat_base {
    /**
     * Convert page names to URLs for steps like 'When I am on the "[page name]" page'.
     *
     * @param string $page name of the page, with the component name removed e.g. 'Admin notification'.
     * @return moodle_url the corresponding URL.
     */
    protected function resolve_page_url(string $page): moodle_url {
        switch (strtolower($page)) {
            case 'all programs management':
                return new moodle_url('/admin/tool/muprog/management/index.php');
            case 'program catalogue':
                return new moodle_url('/admin/tool/muprog/catalogue/index.php');
            case 'my programs':
                return new moodle_url('/admin/tool/muprog/my/index.php');

            default:
                throw new Exception('Unrecognised tool_muprog page "' . $page . '."');
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
            case 'program management':
                if (strtolower($identifier) === 'system') {
                    $syscontext = context_system::instance();
                    return new moodle_url('/admin/tool/muprog/management/index.php', ['contextid' => $syscontext->id]);
                } else {
                    $category = $DB->get_record('course_categories', ['name' => $identifier]);
                    if (!$category) {
                        $category = $DB->get_record('course_categories', ['idnumber' => $identifier]);
                    }
                    if (!$category) {
                        throw new Exception('Invalid category "' . $identifier . '."');
                    }
                }
                $context = context_coursecat::instance($category->id);
                return new moodle_url('/admin/tool/muprog/management/index.php', ['contextid' => $context->id]);
            case 'program':
                $program = $DB->get_record('tool_muprog_program', ['fullname' => $identifier]);
                if (!$program) {
                    $program = $DB->get_record('tool_muprog_program', ['idnumber' => $identifier]);
                }
                if (!$program) {
                    throw new Exception('Invalid program "' . $identifier . '."');
                }
                return new moodle_url('/admin/tool/muprog/management/program.php', ['id' => $program->id]);

            default:
                throw new Exception('Unrecognised tool_muprog page type "' . $type . '."');
        }
    }
}
