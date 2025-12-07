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
 * Define all the backup steps that will be used by the backup_mubook_activity_task.
 *
 * @package    mod_mubook
 * @copyright  2010 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_mubook_activity_structure_step extends backup_activity_structure_step {
    /**
     * Define structure.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $cman = \core\di::get(\mod_mubook\local\content_manager::class);

        // Define each element separated.
        $mubook = new backup_nested_element('mubook', ['id'], [
            'name',
            'intro',
            'introformat',
            'numbering',
            'markdownhtml',
            'contentdefault',
            'timecreated',
            'timemodified',
        ]);
        $chapters = new backup_nested_element('chapters');
        $chapter = new backup_nested_element('chapter', ['id'], [
            'parentid',
            'title',
            'originjson',
            'timecreated',
            'timemodified',
        ]);
        $contents = new backup_nested_element('contents');
        $content = new backup_nested_element('content', ['id'], [
            'type',
            'data1',
            'data2',
            'data3',
            'auxint1',
            'auxint2',
            'auxint3',
            'unsafetrusted',
            'hidden',
            'groupid',
            'originjson',
            'timecreated',
            'timemodified',
        ]);

        $tags = new backup_nested_element('chaptertags');
        $tag = new backup_nested_element('tag', ['id'], ['itemid', 'rawname']);

        $mubook->add_child($chapters);
        $chapters->add_child($chapter);
        $chapter->add_child($contents);
        $contents->add_child($content);

        // Define sources.
        $mubook->set_source_table('mubook', ['id' => backup::VAR_ACTIVITYID]);
        $chapter->set_source_table('mubook_chapter', ['mubookid' => backup::VAR_PARENTID], 'parentid IS NULL DESC, sortorder ASC, parentid ASC');
        $content->set_source_table('mubook_content', ['chapterid' => backup::VAR_PARENTID], 'sortorder ASC');

        // Define file annotations.
        $mubook->annotate_files('mod_mubook', 'intro', null);

        $areas = [];
        /** @var class-string<\mod_mubook\local\content> $classname */
        foreach ($cman->get_available_classes() as $classname) {
            foreach ($classname::get_file_areas() as $filearea) {
                if (in_array($filearea, $areas)) {
                    continue;
                }
                $content->annotate_files('mod_mubook', $filearea, 'id');
                $areas[] = $filearea;
            }
        }

        $mubook->add_child($tags);
        $tags->add_child($tag);

        // All these source definitions only happen if we are including user info.
        if (core_tag_tag::is_enabled('mod_mubook', 'mubook_chapter')) {
            $tag->set_source_sql('SELECT t.id, ti.itemid, t.rawname
                                    FROM {tag} t
                                    JOIN {tag_instance} ti ON ti.tagid = t.id
                                   WHERE ti.itemtype = ?
                                     AND ti.component = ?
                                     AND ti.contextid = ?', [
                backup_helper::is_sqlparam('mubook_chapter'),
                backup_helper::is_sqlparam('mod_mubook'),
                backup::VAR_CONTEXTID]);
        }

        // Return the root element (mubook), wrapped into standard activity structure.
        return $this->prepare_activity_structure($mubook);
    }
}
