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
 * MuTMS lib settings.
 *
 * @package    tool_mulib
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** @var admin_root $ADMIN */

$ADMIN->add('server', new admin_category('tool_mulib_extdb', get_string('extdb', 'tool_mulib')));

$ADMIN->add('tool_mulib_extdb', new admin_externalpage(
    'tool_mulib_extdb_servers',
    get_string('extdb_servers', 'tool_mulib'),
    new moodle_url('/admin/tool/mulib/extdb/servers.php'),
    'moodle/site:config'
));

$ADMIN->add('tool_mulib_extdb', new admin_externalpage(
    'tool_mulib_extdb_queries',
    get_string('extdb_queries', 'tool_mulib'),
    new moodle_url('/admin/tool/mulib/extdb/queries.php'),
    'tool/mulib:useextdb'
));
