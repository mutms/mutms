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

/**
 * Define all the restore steps that will be used by the restore_mubook_activity_task.
 *
 * @package    mod_mubook
 * @copyright  2010 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_mubook_activity_structure_step extends restore_activity_structure_step {
    /**
     * Define restore structure.
     *
     * @return mixed
     */
    protected function define_structure() {
        $paths = [];

        $paths[] = new restore_path_element('mubook', '/activity/mubook');
        $paths[] = new restore_path_element('mubook_chapter', '/activity/mubook/chapters/chapter');
        $paths[] = new restore_path_element('mubook_content', '/activity/mubook/chapters/chapter/contents/content');
        $paths[] = new restore_path_element('mubook_chapter_tag', '/activity/mubook/chaptertags/tag');

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process mubook information.
     *
     * @param array $data information
     */
    protected function process_mubook($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        unset($data->id);
        $newitemid = $DB->insert_record('mubook', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process chapter information.
     *
     * @param array $data information
     */
    protected function process_mubook_chapter($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->mubookid = $this->get_new_parentid('mubook');

        if ($data->parentid) {
            $parentid = $this->get_mappingid('mubook_chapter', $data->parentid);
            if ($parentid) {
                $data->parentid = $parentid;
            } else {
                // Invalid chapter id means chapter is orphaned.
                $data->parentid = -1;
            }
            $data->sortorder = 1 + (int)$DB->get_field('mubook_chapter', 'MAX(sortorder)', ['mubookid' => $data->mubookid, 'parentid' => $data->parentid]);
        } else {
            $data->sortorder = 1 + (int)$DB->get_field('mubook_chapter', 'MAX(sortorder)', ['mubookid' => $data->mubookid, 'parentid' => null]);
        }

        unset($data->id);
        $newitemid = $DB->insert_record('mubook_chapter', $data);
        $this->set_mapping('mubook_chapter', $oldid, $newitemid);
    }

    /**
     * Process content information.
     *
     * @param array $data information
     */
    protected function process_mubook_content($data) {
        global $DB;

        $cman = \core\di::get(\mod_mubook\local\content_manager::class);

        $data = (object)$data;
        $oldid = $data->id;

        $data->chapterid = $this->get_new_parentid('mubook_chapter');

        if ($data->groupid) {
            $data->groupid = $this->get_mappingid('group', $data->groupid);
        }

        $data->sortorder = 1 + $DB->get_field('mubook_content', 'MAX(sortorder)', ['chapterid' => $data->chapterid]);

        $classname = $cman->get_class($data->type);
        if ($classname) {
            if ($classname::is_unsafe()) {
                if (!get_config('mubook', 'restoreothertrustunsafe')) {
                    if (!$this->get_task()->is_samesite()) {
                        // Do not trust unsafe raw HTML form other sites!
                        $data->unsafetrusted = 0;
                    }
                }
            } else {
                $data->unsafetrusted = null;
            }
            $classname::restore_callback($data, $this);
        } else {
            $data->unsafetrusted = null;
        }

        unset($data->id);
        $newitemid = $DB->insert_record('mubook_content', $data);
        $this->set_mapping('mubook_content', $oldid, $newitemid, true);
    }

    /**
     * Process chapter tag information.
     *
     * @param array $data information
     */
    protected function process_mubook_chapter_tag($data) {
        $data = (object)$data;

        if (!core_tag_tag::is_enabled('mod_mubook', 'mubook_chapter')) { // Tags disabled in server, nothing to process.
            return;
        }

        $tag = $data->rawname;

        if (!$itemid = $this->get_mappingid('mubook_chapter', $data->itemid)) {
            return;
        }

        $context = context_module::instance($this->task->get_moduleid());
        core_tag_tag::add_item_tag('mod_mubook', 'mubook_chapter', $itemid, $context, $tag);
    }

    /**
     * Deal with files.
     */
    protected function after_execute() {
        $cman = \core\di::get(\mod_mubook\local\content_manager::class);

        // Add mubook related files.
        $this->add_related_files('mod_mubook', 'intro', null);

        $areas = [];
        /** @var class-string<\mod_mubook\local\content> $classname */
        foreach ($cman->get_available_classes() as $classname) {
            foreach ($classname::get_file_areas() as $filearea) {
                if (in_array($filearea, $areas)) {
                    continue;
                }
                $this->add_related_files('mod_mubook', $filearea, 'mubook_content');
                $areas[] = $filearea;
            }
        }
    }
}
