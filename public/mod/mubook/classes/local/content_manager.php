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

namespace mod_mubook\local;

use stdClass;
use tool_mulib\output\ajax_form\link;
use core\url;

/**
 * Content class manager.
 *
 * Usage: $cman = \core\di::get(\mod_mubook\local\content_manager::class);
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class content_manager {
    /** @var class-string<content>[] */
    private $classes;

    /**
     * Constructor.
     */
    public function __construct() {
        $contentclasses = new \mod_mubook\hook\content_classes();
        $this->classes = $contentclasses->get_classes();
    }

    /**
     * Returns all available content classes,
     * note that unknown content class is not included.
     *
     * @return class-string<content>[]
     */
    public function get_available_classes(): array {
        return $this->classes;
    }

    /**
     * Returns all available content classes,
     * note that unknown content class is not included.
     *
     * @param bool $safeonly
     * @return string[]
     */
    public function get_types_menu(bool $safeonly): array {
        $result = [];
        foreach ($this->classes as $classname) {
            if ($safeonly && $classname::is_unsafe()) {
                continue;
            }
            $result[$classname::get_type()] = $classname::get_name();
        }
        \core_collator::asort($result);

        return $result;
    }

    /**
     * Returns content type class.
     *
     * @param string $type
     * @return class-string<content>|null
     */
    public function get_class(string $type): ?string {
        return $this->classes[$type] ?? null;
    }

    /**
     * Create instance of chapter content,
     * if class unavailable unknown content class is returned.
     *
     * @param stdClass $record content record
     * @param chapter $chapter
     * @return content
     */
    public function create_instance(stdClass $record, chapter $chapter): content {
        $mubook = $chapter->get_mubook();
        $context = $chapter->get_context();
        if (!isset($this->classes[$record->type])) {
            return new content\unknown($record, $chapter, $mubook, $context);
        } else {
            $classname = $this->classes[$record->type];
            return new $classname($record, $chapter, $mubook, $context);
        }
    }

    /**
     * Is current user allowed to create any content?
     *
     * @param chapter|null $chapter null means chapter is being created
     * @param stdClass $mubook
     * @param \context_module $context
     * @return bool
     */
    public function can_create_content(?chapter $chapter, stdClass $mubook, \context_module $context): bool {
        foreach ($this->classes as $classname) {
            if ($classname::can_create($chapter, $mubook, $context)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns dialog link for new content creation.
     *
     * @param chapter $chapter
     * @param int $sortorder
     * @return link
     */
    public function get_create_content_link(chapter $chapter, int $sortorder): link {
        $url = new url(
            '/mod/mubook/management/content_create_select.php',
            ['chapterid' => $chapter->id, 'sortorder' => $sortorder]
        );
        $action = new link($url, get_string('content_create', 'mod_mubook'), 'content_create', 'mod_mubook');
        $action->set_submitted_action($action::SUBMITTED_ACTION_REDIRECT);
        return $action;
    }
}
