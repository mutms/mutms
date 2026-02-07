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

namespace block_muprogmyoverview\local;

use tool_mulib\local\sql;

/**
 * Utility class for My programs overview block.
 *
 * @package    block_muprogmyoverview
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class util {
    /**
     * Add block to the overview page if necessary.
     *
     * @return void
     */
    public static function ensure_block_added(): void {
        global $DB, $PAGE;

        $syscontext = \context_system::instance();
        if ($PAGE->context->id != $syscontext->id) {
            throw new \core\exception\coding_exception('block can be added only to the dedicated programs overview page');
        }

        // Thanks to broken timeline block we cannot add the block during plugin installation.
        $conditions = [
            'blockname' => 'muprogmyoverview',
            'parentcontextid' => $syscontext->id,
            'pagetypepattern' => 'block-muprogmyoverview-index',
        ];
        if (!$DB->record_exists('block_instances', $conditions)) {
            $PAGE->blocks->add_blocks(['content' => ['muprogmyoverview']], 'block-muprogmyoverview-index');
        }
    }

    /**
     * Count active programs of current user.
     *
     * @return int
     */
    public static function count_active_programs(): int {
        global $DB, $USER;

        if (!isloggedin()) {
            return 0;
        }
        if (isguestuser()) {
            return 0;
        }

        $sql = new sql(
            "SELECT COUNT(a.id)
               FROM {tool_muprog_allocation} a
               JOIN {tool_muprog_program} p ON p.id = a.programid
              WHERE a.archived = 0 AND p.archived = 0
                    AND a.userid = :userid",
            ['userid' => $USER->id]
        );
        return $DB->count_records_sql($sql->sql, $sql->params);
    }

    /**
     * Get a list of hidden programs.
     *
     * @return int[] $ids List of hidden courses
     */
    public static function get_hidden_programs_on_timeline(): array {
        $preferences = get_user_preferences();
        $ids = [];
        foreach ($preferences as $key => $unused) {
            if (preg_match('/^block_muprogmyoverview_hidden_program_(\d+)$/', $key, $matches)) {
                $ids[] = (int)$matches[1];
            }
        }

        return $ids;
    }

    /**
     * Once a week clean up hidden programs without allocation.
     * @param bool $force
     */
    public static function cleanup_hidden_programs(bool $force = false): void {
        global $DB, $USER;

        $lastcleanup = get_user_preferences('block_muprogmyoverview_lastcleanup');
        if (!$force && $lastcleanup > time() - WEEKSECS) {
            return;
        }

        $hiddenprograms = self::get_hidden_programs_on_timeline();
        if ($hiddenprograms) {
            $ids = implode(',', $hiddenprograms);
            $sql = new sql(
                "SELECT p.id
                   FROM {tool_muprog_program} p
                   JOIN {tool_muprog_allocation} a ON a.programid = p.id
                  WHERE a.userid = :userid AND p.id IN ($ids)",
                ['userid' => $USER->id]
            );
            $programids = $DB->get_fieldset_sql($sql->sql, $sql->params);
            foreach ($hiddenprograms as $id) {
                if (!in_array($id, $programids)) {
                    unset_user_preference('block_muprogmyoverview_hidden_program_' . $id);
                }
            }
        }

        set_user_preference('block_muprogmyoverview_lastcleanup', time());
    }

    /**
     * Add "My programs" to primary menu.
     *
     * @param \core\hook\navigation\primary_extend $hook
     */
    public static function hook_primary_extend(\core\hook\navigation\primary_extend $hook): void {
        if (!is_callable([\tool_mulib\local\mulib::class, 'is_muprog_active'])) {
            return;
        }
        if (!\tool_mulib\local\mulib::is_muprog_active()) {
            return;
        }
        if (!self::count_active_programs()) {
            return;
        }

        /** @var \core\plugininfo\block $plugininfo */
        $plugininfo = \core_plugin_manager::instance()->get_plugin_info('block_muprogmyoverview');
        if (!$plugininfo->is_enabled()) {
            return;
        }

        $primary = $hook->get_primaryview();

        $node = \navigation_node::create(
            get_string('myprograms', 'block_muprogmyoverview'),
            new \core\url('/blocks/muprogmyoverview/'),
            $primary::TYPE_CUSTOM,
            get_string('myprograms', 'block_muprogmyoverview'),
            'block_muprogmyoverview_myprograms'
        );

        // Put it right after 'mycourses'.
        $keys = $primary->get_children_key_list();
        $beforekey = null;
        $mycoursesfound = false;
        foreach ($keys as $key) {
            if ($key === 'mycourses') {
                $mycoursesfound = true;
                continue;
            }
            if ($mycoursesfound) {
                $beforekey = $key;
                break;
            }
        }
        if ($beforekey === null) {
            if (in_array('siteadminnode', $keys)) {
                $beforekey = 'siteadminnode';
            }
        }

        $primary->add_node($node, $beforekey);
    }
}
