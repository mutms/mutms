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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/mubook/backup/moodle2/restore_mubook_stepslib.php');

/**
 * Interactive book restore task.
 *
 * @package    mod_mubook
 * @copyright  2010 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_mubook_activity_task extends restore_activity_task {
    /**
     * Define (add) particular settings this activity can have.
     *
     * @return void
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have.
     */
    protected function define_my_steps() {
        // Choice only has one structure step.
        $this->add_step(new restore_mubook_activity_structure_step('mubook_structure', 'mubook.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder.
     *
     * @return array
     */
    public static function define_decode_contents() {
        $contents = [];

        $contents[] = new restore_decode_content('mubook', ['intro'], 'mubook');
        $contents[] = new restore_decode_content('mubook_content', ['data1'], 'mubook_content');
        $contents[] = new restore_decode_content('mubook_content', ['data2'], 'mubook_content');
        $contents[] = new restore_decode_content('mubook_content', ['data3'], 'mubook_content');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder.
     *
     * @return array
     */
    public static function define_decode_rules() {
        $rules = [];

        // List of mubooks in course.
        $rules[] = new restore_decode_rule('MUBOOKINDEX', '/mod/mubook/index.php?id=$1', 'course');

        // TOC by cm->id.
        $rules[] = new restore_decode_rule('MUBOOKVIEWBYID', '/mod/mubook/view.php?id=$1', 'course_module');

        // TOC by mubook->id.
        $rules[] = new restore_decode_rule('MUBOOKVIEWBYM', '/mod/mubook/view.php?m=$1', 'mubook');

        // Chapter by id.
        $rules[] = new restore_decode_rule('MUBOOKVIEWCHAPTERBYID', '/mod/mubook/viewchapter.php?id=$1', 'mubook_chapter');

        // All chapters by cm->id.
        $rules[] = new restore_decode_rule('MUBOOKVIEWALLBYID', '/mod/mubook/viewall.php?id=$1', 'course_module');

        // All chapters by mubook->id.
        $rules[] = new restore_decode_rule('MUBOOKVIEWALLBYM', '/mod/mubook/viewall.php?m=$1', 'mubook');

        return $rules;
    }

    /**
     * Obsolete.
     *
     * @return array
     */
    public static function define_restore_log_rules() {
        return [];
    }

    /**
     * Obsolete.
     *
     * @return array
     */
    public static function define_restore_log_rules_for_course() {
        return [];
    }
}
