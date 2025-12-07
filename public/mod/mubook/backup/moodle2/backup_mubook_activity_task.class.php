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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/mubook/backup/moodle2/backup_mubook_stepslib.php');
require_once($CFG->dirroot . '/mod/mubook/backup/moodle2/backup_mubook_settingslib.php');

/**
 * Description of mubook backup task.
 *
 * @package    mod_mubook
 * @copyright  2010 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_mubook_activity_task extends backup_activity_task {
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
     *
     * @return void
     */
    protected function define_my_steps() {
        // Only has one structure step.
        $this->add_step(new backup_mubook_activity_structure_step('mubook_structure', 'mubook.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links.
     *
     * @param string $content
     * @return string encoded content
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of mubooks.
        $search  = "/($base\/mod\/mubook\/index.php\?id=)([0-9]+)/";
        $content = preg_replace($search, '$@MUBOOKINDEX*$2@$', $content);

        // Link to mubook view by moduleid.
        $search  = "/($base\/mod\/mubook\/view.php\?id=)([0-9]+)/";
        $content = preg_replace($search, '$@MUBOOKVIEWBYID*$2@$', $content);

        // Link to mubook view by mubookid.
        $search  = "/($base\/mod\/mubook\/view.php\?b=)([0-9]+)/";
        $content = preg_replace($search, '$@MUBOOKVIEWBYM*$2@$', $content);

        // Link to chapter.
        $search  = "/($base\/mod\/mubook\/viewchapter.php\?id=)([0-9]+)/";
        $content = preg_replace($search, '$@MUBOOKVIEWCHAPTERBYID*$2@$', $content);

        // Link to all chapters view by moduleid.
        $search  = "/($base\/mod\/mubook\/viewall.php\?id=)([0-9]+)/";
        $content = preg_replace($search, '$@MUBOOKVIEWALLBYID*$2@$', $content);

        // Link to all chapters view by mubookid.
        $search  = "/($base\/mod\/mubook\/viewall.php\?b=)([0-9]+)/";
        $content = preg_replace($search, '$@MUBOOKVIEWALLBYM*$2@$', $content);

        return $content;
    }
}
