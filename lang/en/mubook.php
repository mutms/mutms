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

/**
 * Interactive book language pack.
 *
 * @package    mod_mubook
 * @copyright  2004 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['book_actions'] = 'Book actions';
$string['book_print'] = 'Print book';
$string['book_printedby'] = 'Printed by';
$string['book_toc'] = 'Table of contents';
$string['book_viewall'] = 'View all chapters';
$string['chapter_actions'] = 'Chapter actions';
$string['chapter_actions_a'] = 'Chapter actions: {$a}';
$string['chapter_create'] = 'Add chapter';
$string['chapter_delete'] = 'Delete chapter';
$string['chapter_first_a'] = 'First chapter {$a}';
$string['chapter_move'] = 'Move chapter';
$string['chapter_next_a'] = 'Next chapter {$a}';
$string['chapter_position'] = 'Chapter position';
$string['chapter_position_after'] = 'After {$a}';
$string['chapter_position_first'] = 'First chapter';
$string['chapter_previous_a'] = 'Previous chapter {$a}';
$string['chapter_title'] = 'Chapter title';
$string['chapter_update'] = 'Update chapter';
$string['chapters_all'] = 'All chapters';
$string['content_actions_a'] = 'Content {$a} actions';
$string['content_create'] = 'Add content';
$string['content_create_a'] = 'Add {$a}';
$string['content_delete'] = 'Delete content';
$string['content_files'] = 'Files';
$string['content_hidden'] = 'Hidden content';
$string['content_sortorder'] = 'Position';
$string['content_text'] = 'Text';
$string['content_type_disclosure'] = 'Show solution button';
$string['content_type_disclosure_hide'] = 'Hide solution';
$string['content_type_disclosure_hide_custom'] = 'Custom hide label';
$string['content_type_disclosure_printed'] = 'Solution';
$string['content_type_disclosure_printed_custom'] = 'Custom printed label';
$string['content_type_disclosure_show'] = 'Show solution';
$string['content_type_disclosure_show_custom'] = 'Custom show label';
$string['content_type_disclosure_target'] = 'Target content';
$string['content_type_disclosure_target_info'] = 'Solution disclosure targets the next content in the same chapter';
$string['content_type_disclosure_target_none'] = 'Next element does not exist, disclosure will be disabled.';
$string['content_type_html'] = 'HTML text';
$string['content_type_markdown'] = 'Markdown text';
$string['content_type_unknown'] = 'Unknown content type';
$string['content_type_unsafehtml'] = 'Unsafe raw HTML';
$string['content_unavailable'] = 'Unavailable content';
$string['content_unknowntype'] = 'Content cannot be displayed (unknown content type "{$a}").';
$string['content_unsafetrusted'] = 'Trusted unsafe content';
$string['content_unsafetrusted_confirmation'] = 'Only enable if you fully trust the source of this HTML and all associated files';
$string['content_update'] = 'Update content';
$string['content_update_a'] = 'Update {$a}';
$string['contentdefault'] = 'Default content type';
$string['contentdefault_desc'] = 'Select content type to be present in new books.';
$string['event_all_chapters_viewed'] = 'All chapters page viewed';
$string['event_chapter_created'] = 'Chapter created';
$string['event_chapter_deleted'] = 'Chapter deleted';
$string['event_chapter_moved'] = 'Chapter moved';
$string['event_chapter_updated'] = 'Chapter updated';
$string['event_chapter_viewed'] = 'Chapter page viewed';
$string['event_content_created'] = 'Content created';
$string['event_content_deleted'] = 'Content deleted';
$string['event_content_updated'] = 'Content updated';
$string['markdown_alert_caution'] = 'Caution';
$string['markdown_alert_important'] = 'Important';
$string['markdown_alert_note'] = 'Note';
$string['markdown_alert_tip'] = 'Tip';
$string['markdown_alert_warning'] = 'Warning';
$string['markdown_html'] = 'Markdown HTML processing';
$string['markdown_html_allow'] = 'Allow HTML';
$string['markdown_html_escape'] = 'Escape HTML';
$string['markdown_html_setting'] = 'Default Markdown HTML processing';
$string['markdown_html_setting_desc'] = 'Select default Markdown HTML processing for new interactive books.';
$string['markdown_html_strip'] = 'Remove HTML';
$string['markdown_task_completed'] = 'Task completed';
$string['markdown_task_notcompleted'] = 'Task not completed';
$string['modulename'] = 'Interactive book';
$string['modulename_link'] = 'mod/mubook/view';
$string['modulenameplural'] = 'Interactive books';
$string['mubook:addinstance'] = 'Add a new book';
$string['mubook:editchapter'] = 'Add and delete chapters';
$string['mubook:editcontent'] = 'Edit chapter contents';
$string['mubook:usexss'] = 'Use unsafe HTML';
$string['mubook:view'] = 'View book contents';
$string['mubook:viewall'] = 'View book as one page';
$string['mubook:viewhiddencontent'] = 'View hidden content';
$string['nochaptercontent'] = 'Chapter content is not available.';
$string['nocontent'] = 'No content has been added to this book yet.';
$string['nocontent_edit'] = 'Turn on edit mode to create book chapters.';
$string['numbering'] = 'Chapter numbering';
$string['numberingdefault'] = 'Default chapter numbering';
$string['page-mod-mubook-viewall'] = 'All bool chapters on one page';
$string['page-mod-mubook-viewchapter'] = 'One book chapter per page';
$string['page-mod-mubook-x'] = 'Any book page';
$string['pluginadministration'] = 'Book administration';
$string['pluginname'] = 'Interactive book';
$string['privacy:metadata'] = 'Interactive book module does not store any personal data.';
$string['restoreothertrustunsafe'] = 'Trust unsafe content from other sites';
$string['restoreothertrustunsafe_desc'] = 'For security reasons it is not recommended to trust restored unsafe book contents from other sites.

If disabled then every unsafe content instance restored from backups from other sites will have to be marked as trusted manually.';
$string['subchapter'] = 'Sub-chapter';
$string['subchapter_actions'] = 'Sub-chapter actions';
$string['subchapter_actions_a'] = 'Sub-chapter actions: {$a}';
$string['subchapter_create'] = 'Add sub-chapter';
$string['subchapter_delete'] = 'Delete sub-chapter';
$string['subchapter_move'] = 'Move sub-chapter';
$string['subchapter_position'] = 'Sub-chapter position';
$string['subchapter_position_after'] = 'After {$a}';
$string['subchapter_position_first'] = 'First in {$a}';
$string['subchapter_title'] = 'Sub-chapter title';
$string['subchapter_update'] = 'Update sub-chapter';
$string['subchapters'] = 'Sub-chapters';
$string['subchapters_delete_a'] = 'Confirm deletion of {$a} sub-chapters';
$string['subchapters_orphaned'] = 'Orphaned subchapters';
$string['tagarea_mubook_chapter'] = 'Interactive book chapters';
$string['toc'] = 'Table of contents';
