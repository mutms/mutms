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

namespace mod_mubook\local\form;

use mod_mubook\local\chapter;
use mod_mubook\local\toc;
use core\url;
use core\exception\coding_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/** @var $CFG stdClass */
require_once($CFG->dirroot . '/lib/formslib.php');

/**
 * Base class for content creation forms.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class content_create_base extends \moodleform {
    /**
     * Returns relevant content type name.
     *
     * @return string
     */
    public static function get_content_type(): string {
        $parts = explode('\\', static::class);
        $part = end($parts);
        if ($part === 'content_create_base') {
            throw new coding_exception('get_content_type() cannot be used with base class');
        }
        if (str_ends_with($part, '_create')) {
            return substr($part, 0, -7);
        }
        throw new coding_exception('unexpected form name');
    }

    /**
     * Create instance of the form.
     *
     * @param chapter $chapter
     * @param int $sortorder
     * @param toc $toc
     * @param int $fromcreatechapterid
     * @return static
     */
    public static function init_form(chapter $chapter, int $sortorder, toc $toc, int $fromcreatechapterid): static {
        return new static(
            null,
            ['chapter' => $chapter, 'sortorder' => $sortorder, 'toc' => $toc, 'fromcreatechapterid' => $fromcreatechapterid],
        );
    }

    /**
     * Set up page for adding of new content.
     *
     * @param chapter $chapter
     * @param toc $toc
     * @return void
     */
    public static function setup_content_page(chapter $chapter, toc $toc): void {
        global $PAGE;

        $mubook = $toc->get_mubook();

        $title = get_string('content_create', 'mod_mubook');
        $PAGE->set_title(implode(\moodle_page::TITLE_SEPARATOR, [$mubook->name, $title]));
        $PAGE->set_pagelayout('base');
        $PAGE->set_secondary_navigation(false);
        $PAGE->activityheader->set_hidecompletion(true);
        $PAGE->activityheader->set_description('');

        $parent = null;
        if ($chapter->parentid) {
            $parent = $toc->get_chapter($chapter->parentid);
        }
        if ($parent) {
            $PAGE->navbar->add(
                $toc->format_chapter_numbers($parent->id) . ' ' . $parent->format_title(),
                new url('/mod/mubook/viewchapter.php', ['id' => $parent->id])
            );
        }
        $PAGE->navbar->add(
            $toc->format_chapter_numbers($chapter->id) . ' ' . $chapter->format_title(),
            new url('/mod/mubook/viewchapter.php', ['id' => $chapter->id])
        );
        $PAGE->navbar->add($title);
    }

    /**
     * Add shared elements for content creation.
     *
     * @return void
     */
    public function add_shared_content_elements(): void {
        $mform = $this->_form;
        /** @var chapter $chapter */
        $chapter = $this->_customdata['chapter'];
        /** @var int $sortorder */
        $sortorder = $this->_customdata['sortorder'];
        /** @var toc $toc */
        $toc = $this->_customdata['toc'];
        $context = $toc->get_context();
        $fromcreatechapterid = $this->_customdata['fromcreatechapterid'];

        $mform->addElement('hidden', 'chapterid');
        $mform->setType('chapterid', PARAM_INT);
        $mform->setDefault('chapterid', $chapter->id);

        $mform->addElement('hidden', 'type');
        $mform->setType('type', PARAM_ALPHANUM);
        $mform->setDefault('type', static::get_content_type());

        $mform->addElement('hidden', 'fromcreatechapterid');
        $mform->setType('fromcreatechapterid', PARAM_INT);
        $mform->setDefault('fromcreatechapterid', $fromcreatechapterid);

        $options = [];
        foreach ($chapter->get_contents() as $c) {
            $options[$c->sortorder] = $c->sortorder;
        }
        if ($options) {
            $options[0] = max($options) + 1;
        } else {
            $options[1] = 1;
        }
        $mform->addElement('select', 'sortorder', get_string('content_sortorder', 'mod_mubook'), $options);
        $mform->setDefault('sortorder', $sortorder);

        if (has_capability('mod/mubook:viewhiddencontent', $context)) {
            $mform->addElement('advcheckbox', 'hidden', get_string('content_hidden', 'mod_mubook'));
        } else {
            $mform->addElement('hidden', 'hidden');
            $mform->setType('hidden', PARAM_INT);
            $mform->setConstant('hidden', 0);
        }

        // TODO: add group selection.
    }

    /**
     * To be called from content::create() methods before record is inserted.
     *
     * @param stdClass $record
     * @param stdClass $data
     * @param chapter $chapter
     * @param stdClass $mubook
     * @param \context_module $context
     */
    public static function before_db_insert(stdClass $record, stdClass $data, chapter $chapter, stdClass $mubook, \context_module $context): void {
    }

    /**
     * To be called from content::create() methods after record is inserted.
     *
     * @param stdClass $record
     * @param stdClass $data
     * @param chapter $chapter
     * @param stdClass $mubook
     * @param \context_module $context
     */
    public static function after_db_insert(stdClass $record, stdClass $data, chapter $chapter, stdClass $mubook, \context_module $context): void {
    }
}
