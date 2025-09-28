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
 * Alternative Log-in-as plugin.
 *
 * @package     tool_muloginas
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['creatingtoken'] = 'Preparing log in as token...';
$string['error_incognitoproglem'] = 'Error initialising "Log in as" session, make sure all Incognito windows are closed and retry.';
$string['error_invalidrequest'] = 'Invalid "Log in as" request detected.';
$string['error_linkexpired'] = '"Log in as" link expired, retry if necessary.';
$string['error_notincognito'] = '"Log in as" link was not opened in a new Incognito window.';
$string['info_heading'] = 'Log in as at {$a}';
$string['loggedinas'] = 'You are now logged in as "{$a}" user, log out or close this window when finished.';
$string['loggedoutprev'] = 'Previous "Log in as" session for user "{$a}" was terminated.';
$string['loginas'] = 'Log in as (via new Incognito window)';
$string['loginas_a'] = 'Log in as "{$a}"';
$string['logoutinfo'] = '"Log in as" session terminated, close this window to continue.';
$string['muloginas:loginas'] = 'Log in as via Incognito window';
$string['pluginname'] = 'Log in as via Incognito window';
$string['popup_link'] = 'Right-click this link and select <strong>Open Link in Incognito Window</strong> or similar option.';
$string['privacy:metadata'] = 'Log in as plugin does not store any personal user data persistently';
$string['privacy:metadata:targetuserid'] = 'target user id';
$string['privacy:metadata:tool_muloginas_request:tableexplanation'] = 'Log-in-as request tokens';
$string['privacy:metadata:userid'] = 'User id';
$string['taskcron'] = 'Log-in-as tokens cleanup task';
