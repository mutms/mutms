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
// phpcs:disable moodle.Commenting.DocblockDescription.Missing

// NOTE: No MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

use Behat\Mink\Exception\DriverException;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Exception\ElementNotFoundException;

require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');

/**
 * Library mulib behat steps.
 *
 * @package     tool_mulib
 * @copyright   2022 Open LMS (https://www.openlms.net/)
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_tool_mulib extends behat_base {
    /**
     * Click header action
     *
     * @Given I click on :action action from :header dropdown
     *
     * @param string $action
     * @param string $header
     */
    public function i_click_dropdown_action(string $action, string $header) {
        $this->get_selected_node('button', $header)->click();
        $this->get_selected_node('link', $action)->click();
    }

    /**
     * Execute a scheduled task via CURL.
     *
     * @Given I run the :taskname task
     *
     * @param string $taskname
     */
    public function execute_scheduled_task(string $taskname) {
        global $CFG;

        $task = \core\task\manager::get_scheduled_task($taskname);

        if (!$task) {
            throw new DriverException('The "' . $taskname . '" scheduled task does not exist');
        }
        $taskname = get_class($task);

        $ch = new curl();
        $options = [
            'FOLLOWLOCATION' => true,
            'RETURNTRANSFER' => true,
            'SSL_VERIFYPEER' => false,
            'SSL_VERIFYHOST' => 0,
            'HEADER' => 0,
        ];

        $content = $ch->get(
            "$CFG->wwwroot/admin/tool/mulib/tests/behat/task_runner.php",
            ['behat_task' => $taskname],
            $options
        );

        if (!str_contains($content, "Scheduled task '$taskname' completed")) {
            throw new ExpectationException("Scheduled task '$taskname' did not complete successfully, content : " . $content, $this->getSession());
        }

        $this->look_for_exceptions();
    }

    /**
     * Admin bookmark takes way too much space on admin pages,
     * so get rid of it.
     *
     * @Given unnecessary Admin bookmarks block gets deleted
     */
    public function delete_admin_bookmarks_block(): void {
        global $CFG, $DB;
        require_once("$CFG->libdir/blocklib.php");

        $instance = $DB->get_record('block_instances', ['blockname' => 'admin_bookmarks']);
        if ($instance) {
            blocks_delete_instance($instance);
        }
    }

    /**
     * @Given I skip tests if :plugin is not installed
     *
     * @param string $plugin
     */
    public function skip_if_plugin_missing($plugin): void {
        if (!get_config($plugin, 'version')) {
            throw new \Moodle\BehatExtension\Exception\SkippedException("Tests were skipped because plugin '$plugin' is not installed");
        }
    }

    /**
     * @Given I skip tests if :plugin is installed
     *
     * @param string $plugin
     */
    public function skip_if_plugin_installed($plugin): void {
        if (get_config($plugin, 'version')) {
            throw new \Moodle\BehatExtension\Exception\SkippedException("Tests were skipped because plugin '$plugin' is installed");
        }
    }

    /**
     * @Given I skip tests if :constant is defined and not empty
     *
     * @param string $constant
     */
    public function skip_if_constant_not_empty($constant): void {
        if (defined($constant) && constant($constant)) {
            throw new \Moodle\BehatExtension\Exception\SkippedException("Tests were skipped because constant '$constant' is defined and non-empty");
        }
    }

    /**
     * Looks for definition of a term in a list.
     *
     * @Then I should see :text in the :label definition list item
     *
     * @param string $text
     * @param string $label
     */
    public function list_term_contains_text($text, $label): void {

        $labelliteral = behat_context_helper::escape($label);
        $xpath = "//dl/dt[text()=$labelliteral]/following-sibling::dd[1]";

        $nodes = $this->getSession()->getPage()->findAll('xpath', $xpath);
        if (empty($nodes)) {
            throw new ExpectationException(
                'Unable to find a term item with label = ' . $labelliteral,
                $this->getSession()
            );
        }
        if (count($nodes) > 1) {
            throw new ExpectationException(
                'Found more than one term item with label = ' . $labelliteral,
                $this->getSession()
            );
        }
        $node = reset($nodes);

        $xpathliteral = behat_context_helper::escape($text);
        $xpath = "/descendant-or-self::*[contains(., $xpathliteral)]" .
            "[count(descendant::*[contains(., $xpathliteral)]) = 0]";

        // Wait until it finds the text inside the container, otherwise custom exception.
        try {
            $nodes = $this->find_all('xpath', $xpath, false, $node);
        } catch (ElementNotFoundException $e) {
            throw new ExpectationException('"' . $text . '" text was not found in the "' . $label . '" term', $this->getSession());
        }

        // If we are not running javascript we have enough with the
        // element existing as we can't check if it is visible.
        if (!$this->running_javascript()) {
            return;
        }

        // We also check the element visibility when running JS tests. Using microsleep as this
        // is a repeated step and global performance is important.
        $this->spin(
            function ($context, $args) {

                foreach ($args['nodes'] as $node) {
                    if ($node->isVisible()) {
                        return true;
                    }
                }

                throw new ExpectationException('"' . $args['text'] . '" text was found in the "' . $args['label'] . '" element but was not visible', $context->getSession());
            },
            ['nodes' => $nodes, 'text' => $text, 'label' => $label],
            false,
            false,
            true
        );
    }

    /**
     * Looks into definition of a term in a list and makes sure text is not there.
     *
     * @Then I should not see :text in the :label definition list item
     *
     * @param string $text
     * @param string $label
     */
    public function list_term_note_contains_text($text, $label): void {

        $labelliteral = behat_context_helper::escape($label);
        $xpath = "//dl/dt[text()=$labelliteral]/following-sibling::dd[1]";

        $nodes = $this->getSession()->getPage()->findAll('xpath', $xpath);
        if (empty($nodes)) {
            throw new ExpectationException(
                'Unable to find a term item with label = ' . $labelliteral,
                $this->getSession()
            );
        }
        if (count($nodes) > 1) {
            throw new ExpectationException(
                'Found more than one term item with label = ' . $labelliteral,
                $this->getSession()
            );
        }
        $node = reset($nodes);

        $xpathliteral = behat_context_helper::escape($text);
        $xpath = "/descendant-or-self::*[contains(., $xpathliteral)]" .
            "[count(descendant::*[contains(., $xpathliteral)]) = 0]";

        $nodes = null;
        try {
            $nodes = $this->find_all('xpath', $xpath, false, $node, 0);
        } catch (ElementNotFoundException $e) {
            // Good!
            $nodes = null;
        }
        if ($nodes) {
            throw new ExpectationException('"' . $text . '" text was found in the "' . $label . '" element', $this->getSession());
        }
    }

    /**
     * Opens user profile page.
     *
     * @Given I am on the profile page of user :username
     *
     * @param string $username
     */
    public function i_am_on_user_profile_page(string $username): void {
        global $DB;
        $user = $DB->get_record('user', ['username' => $username], '*', MUST_EXIST);
        $url = new moodle_url('/user/profile.php', ['id' => $user->id]);
        $this->execute('behat_general::i_visit', [$url]);
    }

    /**
     * Emulate clicking on email change confirmation link from the email.
     *
     * @When /^I confirm changed email for "(?P<username>(?:[^"]|\\")*)"$/
     *
     * @param string $username
     */
    public function i_confirm_changed_email_for(string $username): void {
        global $DB;

        $user = $DB->get_record('user', ['username' => $username], '*', MUST_EXIST);
        $key = $DB->get_field('user_private_key', 'value', ['userid' => $user->id, 'script' => 'core_user/email_change']);

        $url = new moodle_url('/user/emailupdate.php', ['id' => $user->id, 'key' => $key]);

        $this->execute('behat_general::i_visit', [$url->out(false)]);
    }

    /**
     * Add BEHAT_VISUAL_CHECK_PAUSE constant to config.php to interactively confirm test result.
     *
     * @When I perform a visual check :assert
     *
     * @param string $assert
     */
    public function i_pause_for_visual_check(string $assert) {
        if (!$this->has_tag('_visual_check')) {
            throw new DriverException('Visual check tests must have @_visual_check tag');
        }
        if (!defined('BEHAT_VISUAL_CHECK_PAUSE') || !BEHAT_VISUAL_CHECK_PAUSE) {
            return;
        }
        if (function_exists('posix_isatty') && !@posix_isatty(STDOUT)) {
            return;
        }

        $message = "<colour:lightRed>Press Enter/Return after confirming that: <colour:normal>$assert";
        behat_util::pause($this->getSession(), $message);
    }

    /**
     * @Given site is prepared for documentation screenshots
     */
    public function prepare_for_documentation_screenshots() {
        global $DB;
        $this->delete_admin_bookmarks_block();
        $this->execute('behat_general::i_change_window_size_to', ['window', '1208x780']);
        $DB->set_field('course', 'shortname', 'MuTMS', ['category' => 0]);
        $DB->set_field('course', 'fullname', 'MuTMS test site', ['category' => 0]);

        if (defined('BEHAT_MULIB_UPDATE_SCREENSHOTS') && BEHAT_MULIB_UPDATE_SCREENSHOTS) {
            // Hide theme footer only if actually taking the screenshot.
            purge_all_caches();
            $this->getSession()->reload();

            set_config('scss', '#page-footer { display: none }', 'theme_boost');

            purge_all_caches();
            theme_build_css_for_themes([theme_config::load('boost')], ['ltr']);
        }

        $this->getSession()->reload();
    }

    /**
     * @Then site is restored after documentation screenshots
     */
    public function restore_for_documentation_screenshots() {
        if (defined('BEHAT_MULIB_UPDATE_SCREENSHOTS') && BEHAT_MULIB_UPDATE_SCREENSHOTS) {
            // Undo hiding of footer to prevent other tests from failing.
            set_config('scss', '', 'theme_boost');
            purge_all_caches();
            theme_build_css_for_themes([theme_config::load('boost')], ['ltr']);
        }

        $this->getSession()->reload();
    }

    /**
     * Take screenshot for and save it as image for plugin documentation.
     *
     * NOTE: does nothing if BEHAT_MULIB_UPDATE_SCREENSHOTS not defined in config
     *
     * @When I make documentation screenshot :image for :plugin plugin
     *
     * @param string $image
     * @param string $plugin
     * @return void
     */
    public function create_documentation_screenshot(string $image, string $plugin) {
        if (!defined('BEHAT_MULIB_UPDATE_SCREENSHOTS') || !BEHAT_MULIB_UPDATE_SCREENSHOTS) {
            return;
        }
        $basedir = core_component::get_component_directory($plugin);
        if (!file_exists("$basedir/docs/en")) {
            throw new Exception('Plugin does not have docs directory');
        }
        $imagedir = "$basedir/docs/en/img";
        if (!file_exists($imagedir)) {
            mkdir($imagedir);
        }

        file_put_contents("$imagedir/$image", $this->getSession()->getScreenshot());
    }
}
