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

namespace block_muprogmyoverview\output;

use renderable;
use renderer_base;
use templatable;
use stdClass;

/**
 * Class containing data for My programs overview block.
 *
 * @copyright  2017 Ryan Wyllie <ryan@moodle.com>
 * @copyright  2018 Bas Brands <bas@moodle.com>
 * @copyright  2025 Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package block_muprogmyoverview
 */
class main implements renderable, templatable {
    /**
     * Store the grouping preference.
     *
     * @var string String matching the grouping constants defined in muprogmyoverview/lib.php
     */
    private $grouping;

    /**
     * Store the sort preference.
     *
     * @var string String matching the sort constants defined in muprogmyoverview/lib.php
     */
    private $sort;

    /**
     * Store the view preference.
     *
     * @var string String matching the view/display constants defined in muprogmyoverview/lib.php
     */
    private $view;

    /**
     * Store the paging preference.
     *
     * @var string String matching the paging constants defined in muprogmyoverview/lib.php
     */
    private $paging;

    /**
     * Store the display categories config setting.
     *
     * @var bool
     */
    private $displaycategories;

    /**
     * Store the configuration values for the muprogmyoverview block.
     *
     * @var array Array of available layouts matching view/display constants defined in muprogmyoverview/lib.php
     */
    private $layouts;

    /**
     * Store a program grouping option setting
     *
     * @var bool
     */
    private $displaygroupingallincludinghidden;

    /**
     * Store a program grouping option setting.
     *
     * @var bool
     */
    private $displaygroupingall;

    /**
     * Store a program grouping option setting.
     *
     * @var bool
     */
    private $displaygroupinginprogress;

    /**
     * Store a program grouping option setting.
     *
     * @var bool
     */
    private $displaygroupingfuture;

    /**
     * Store a program grouping option setting.
     *
     * @var bool
     */
    private $displaygroupingpast;

    /**
     * Store a program grouping option setting.
     *
     * @var bool
     */
    private $displaygroupingfavourites;

    /**
     * Store a program grouping option setting.
     *
     * @var bool
     */
    private $displaygroupinghidden;

    /** @var bool true if grouping selector should be shown, otherwise false. */
    protected $displaygroupingselector;

    /**
     * main constructor.
     * Initialize the user preferences
     *
     * @param string $grouping Grouping user preference
     * @param string $sort Sort user preference
     * @param string $view Display user preference
     * @param int $paging
     *
     * @throws \dml_exception
     */
    public function __construct($grouping, $sort, $view, $paging) {
        global $CFG;
        require_once($CFG->dirroot . '/blocks/muprogmyoverview/lib.php');

        // Get plugin config.
        $config = get_config('block_muprogmyoverview');

        // Build the program grouping option name to check if the given grouping is enabled afterwards.
        $groupingconfigname = 'displaygrouping' . $grouping;
        // Check the given grouping and remember it if it is enabled.
        if ($grouping && $config->$groupingconfigname == true) {
            $this->grouping = $grouping;

            // Otherwise fall back to another grouping in a reasonable order.
            // This is done to prevent one-time UI glitches in the case when a user has chosen a grouping option previously which
            // was then disabled by the admin in the meantime.
        } else {
            $this->grouping = $this->get_fallback_grouping($config);
        }
        unset($groupingconfigname);

        // Check and remember the given sorting.
        if ($sort) {
            $this->sort = $sort;
        } else {
            $this->sort = BLOCK_MUPROGMYOVERVIEW_SORTING_TITLE;
        }

        // Check and remember the given view.
        $this->view = $view ? $view : BLOCK_MUPROGMYOVERVIEW_VIEW_CARD;

        // Check and remember the given page size, `null` indicates no page size set
        // while a `0` indicates a paging size of `All`.
        if (!is_null($paging) && $paging == BLOCK_MUPROGMYOVERVIEW_PAGING_ALL) {
            $this->paging = BLOCK_MUPROGMYOVERVIEW_PAGING_ALL;
        } else {
            $this->paging = $paging ? $paging : BLOCK_MUPROGMYOVERVIEW_PAGING_12;
        }

        // Check and remember if the program categories should be shown or not.
        if (!$config->displaycategories) {
            $this->displaycategories = BLOCK_MUPROGMYOVERVIEW_DISPLAY_CATEGORIES_OFF;
        } else {
            $this->displaycategories = BLOCK_MUPROGMYOVERVIEW_DISPLAY_CATEGORIES_ON;
        }

        // Get and remember the available layouts.
        $this->set_available_layouts();
        $this->view = $view ? $view : reset($this->layouts);

        // Check and remember if the particular grouping options should be shown or not.
        $this->displaygroupingallincludinghidden = $config->displaygroupingallincludinghidden;
        $this->displaygroupingall = $config->displaygroupingall;
        $this->displaygroupinginprogress = $config->displaygroupinginprogress;
        $this->displaygroupingfuture = $config->displaygroupingfuture;
        $this->displaygroupingpast = $config->displaygroupingpast;
        $this->displaygroupingfavourites = $config->displaygroupingfavourites;
        $this->displaygroupinghidden = $config->displaygroupinghidden;

        // Check and remember if the grouping selector should be shown at all or not.
        // It will be shown if more than 1 grouping option is enabled.
        $displaygroupingselectors = [$this->displaygroupingallincludinghidden,
                $this->displaygroupingall,
                $this->displaygroupinginprogress,
                $this->displaygroupingfuture,
                $this->displaygroupingpast,
                $this->displaygroupingfavourites,
                $this->displaygroupinghidden];
        $displaygroupingselectorscount = count(array_filter($displaygroupingselectors));
        if ($displaygroupingselectorscount > 1) {
            $this->displaygroupingselector = true;
        } else {
            $this->displaygroupingselector = false;
        }
        unset($displaygroupingselectors, $displaygroupingselectorscount);
    }
    /**
     * Determine the most sensible fallback grouping to use (in cases where the stored selection
     * is no longer available).
     * @param object $config
     * @return string
     */
    private function get_fallback_grouping($config) {
        if ($config->displaygroupingall == true) {
            return BLOCK_MUPROGMYOVERVIEW_GROUPING_ALL;
        }
        if ($config->displaygroupingallincludinghidden == true) {
            return BLOCK_MUPROGMYOVERVIEW_GROUPING_ALLINCLUDINGHIDDEN;
        }
        if ($config->displaygroupinginprogress == true) {
            return BLOCK_MUPROGMYOVERVIEW_GROUPING_INPROGRESS;
        }
        if ($config->displaygroupingfuture == true) {
            return BLOCK_MUPROGMYOVERVIEW_GROUPING_FUTURE;
        }
        if ($config->displaygroupingpast == true) {
            return BLOCK_MUPROGMYOVERVIEW_GROUPING_PAST;
        }
        if ($config->displaygroupingfavourites == true) {
            return BLOCK_MUPROGMYOVERVIEW_GROUPING_FAVOURITES;
        }
        if ($config->displaygroupinghidden == true) {
            return BLOCK_MUPROGMYOVERVIEW_GROUPING_HIDDEN;
        }
        // In this case, no grouping option is enabled and the grouping is not needed at all.
        // But it's better not to leave $this->grouping unset for any unexpected case.
        return BLOCK_MUPROGMYOVERVIEW_GROUPING_ALLINCLUDINGHIDDEN;
    }

    /**
     * Set the available layouts based on the config table settings,
     * if none are available, defaults to the cards view.
     *
     * @throws \dml_exception
     *
     */
    public function set_available_layouts() {

        if ($config = get_config('block_muprogmyoverview', 'layouts')) {
            $this->layouts = explode(',', $config);
        } else {
            $this->layouts = [BLOCK_MUPROGMYOVERVIEW_VIEW_CARD];
        }
    }

    /**
     * Get the user preferences as an array to figure out what has been selected.
     *
     * @return array $preferences Array with the pref as key and value set to true
     */
    public function get_preferences_as_booleans() {
        $preferences = [];
        $preferences[$this->sort] = true;
        $preferences[$this->grouping] = true;
        // Only use the user view/display preference if it is in available layouts.
        if (in_array($this->view, $this->layouts)) {
            $preferences[$this->view] = true;
        } else {
            $preferences[reset($this->layouts)] = true;
        }

        return $preferences;
    }

    /**
     * Format a layout into an object for export as a Context variable to template.
     *
     * @param string $layoutname
     *
     * @return \stdClass $layout an object representation of a layout
     * @throws \coding_exception
     */
    public function format_layout_for_export($layoutname) {
        $layout = new stdClass();

        $layout->id = $layoutname;
        $layout->name = get_string($layoutname, 'block_muprogmyoverview');
        $layout->active = $this->view == $layoutname ? true : false;
        $layout->arialabel = get_string('aria:' . $layoutname, 'block_muprogmyoverview');

        return $layout;
    }

    /**
     * Get the available layouts formatted for export.
     *
     * @return array an array of objects representing available layouts
     */
    public function get_formatted_available_layouts_for_export() {

        return array_map([$this, 'format_layout_for_export'], $this->layouts);
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return array Context variables for the template
     */
    public function export_for_template(renderer_base $output) {
        global $CFG, $USER;

        $noprogramsurl = $output->image_url('programs', 'block_muprogmyoverview')->out();

        $preferences = $this->get_preferences_as_booleans();
        $availablelayouts = $this->get_formatted_available_layouts_for_export();
        $sort = $this->sort;

        $defaultvariables = [
            'totalprogramcount' => \block_muprogmyoverview\local\util::count_active_programs(),
            'noprogramsimg' => $noprogramsurl,
            'grouping' => $this->grouping,
            'sort' => $sort,
            // If the user preference display option is not available, default to first available layout.
            'view' => in_array($this->view, $this->layouts) ? $this->view : reset($this->layouts),
            'paging' => $this->paging,
            'layouts' => $availablelayouts,
            'displaycategories' => $this->displaycategories,
            'displaydropdown' => (count($availablelayouts) > 1) ? true : false,
            'displaygroupingallincludinghidden' => $this->displaygroupingallincludinghidden,
            'displaygroupingall' => $this->displaygroupingall,
            'displaygroupinginprogress' => $this->displaygroupinginprogress,
            'displaygroupingfuture' => $this->displaygroupingfuture,
            'displaygroupingpast' => $this->displaygroupingpast,
            'displaygroupingfavourites' => $this->displaygroupingfavourites,
            'displaygroupinghidden' => $this->displaygroupinghidden,
            'displaygroupingselector' => $this->displaygroupingselector,
        ];
        return array_merge($defaultvariables, $preferences);
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return array Context variables for the template
     */
    public function export_for_zero_state_template(renderer_base $output) {
        $buttons = [];

        return $this->generate_zero_state_data(
            $output->image_url('programs', 'block_muprogmyoverview'),
            $buttons,
            ['title' => 'zero_default_title', 'intro' => 'zero_default_intro']
        );
    }

    /**
     * Generate the state zero data.
     *
     * @param \moodle_url $imageurl The URL to the image to show
     * @param string[] $buttons Exported {@see \single_button} instances
     * @param array $strings Title and intro strings for the zero state if needed.
     * @return array Context variables for the template
     */
    private function generate_zero_state_data(\moodle_url $imageurl, array $buttons, array $strings) {
        return [
            'noprogramsimg' => $imageurl->out(),
            'title' => ($strings['title']) ? get_string($strings['title'], 'block_muprogmyoverview') : '',
            'intro' => ($strings['intro']) ? get_string($strings['intro'], 'block_muprogmyoverview') : '',
            'buttons' => $buttons,
        ];
    }
}
