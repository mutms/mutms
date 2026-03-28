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
 * Update tenant Boost overrides.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mutenancy\local\tenancy;
use tool_mutenancy\local\appearance;
use tool_mutenancy\local\config;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */

define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');

require_once($CFG->libdir . '/filelib.php');

$tenantid = required_param('id', PARAM_INT);

require_login();

if (!tenancy::is_active()) {
    redirect(new \core\url('/'));
}

$tenant = $DB->get_record('tool_mutenancy_tenant', ['id' => $tenantid], '*', MUST_EXIST);

$context = context_tenant::instance($tenant->id);
require_capability('tool/mutenancy:configappearance', $context);
$syscontext = context_system::instance();

$PAGE->set_url('/admin/tool/mutenancy/management/theme_boost_edit.php', ['id' => $tenant->id]);
$PAGE->set_context($context);

$returnurl = new \core\url('/admin/tool/tenant_appearance.php', ['id' => $tenant->id]);

$currentdata = (object)[
    'id' => $tenant->id,
    'backgroundimage' => file_get_submitted_draft_itemid('backgroundimage'),
    'loginbackgroundimage' => file_get_submitted_draft_itemid('loginbackgroundimage'),
];

$logooptions = \tool_mutenancy\local\form\theme_boost_edit::get_logo_options();

file_prepare_draft_area($currentdata->backgroundimage, $context->id, 'theme_boost', 'backgroundimage', 0, $logooptions);
file_prepare_draft_area($currentdata->loginbackgroundimage, $context->id, 'theme_boost', 'loginbackgroundimage', 0, $logooptions);

$form = new \tool_mutenancy\local\form\theme_boost_edit(null, ['currentdata' => $currentdata, 'tenant' => $tenant]);

if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
}

if ($data = $form->get_data()) {
    $fs = get_file_storage();

    if (isset($data->preset_override)) {
        if ($data->preset_override) {
            config::override($tenant->id, 'preset', $data->preset, 'theme_boost');
        } else {
            config::override($tenant->id, 'preset', null, 'theme_boost');
        }
    }

    if (isset($data->backgroundimage_override)) {
        if ($data->backgroundimage_override) {
            file_save_draft_area_files($data->backgroundimage, $context->id, 'theme_boost', 'backgroundimage', 0, $logooptions);
            $files = $fs->get_area_files($context->id, 'theme_boost', 'backgroundimage', 0, 'id DESC', false);
            if ($files) {
                $file = reset($files);
                config::override($tenant->id, 'backgroundimage', '/' . $file->get_filename(), 'theme_boost');
            } else {
                config::override($tenant->id, 'backgroundimage', '', 'theme_boost');
            }
        } else {
            config::override($tenant->id, 'backgroundimage', null, 'theme_boost');
            $fs->delete_area_files($context->id, 'theme_boost', 'backgroundimage', 0);
        }
    }

    if (isset($data->loginbackgroundimage_override)) {
        if ($data->loginbackgroundimage_override) {
            file_save_draft_area_files($data->loginbackgroundimage, $context->id, 'theme_boost', 'loginbackgroundimage', 0, $logooptions);
            $files = $fs->get_area_files($context->id, 'theme_boost', 'loginbackgroundimage', 0, 'id DESC', false);
            if ($files) {
                $file = reset($files);
                config::override($tenant->id, 'loginbackgroundimage', '/' . $file->get_filename(), 'theme_boost');
            } else {
                config::override($tenant->id, 'loginbackgroundimage', '', 'theme_boost');
            }
        } else {
            config::override($tenant->id, 'loginbackgroundimage', null, 'theme_boost');
            $fs->delete_area_files($context->id, 'theme_boost', 'loginbackgroundimage', 0);
        }
    }

    if (isset($data->brandcolor_override)) {
        if ($data->brandcolor_override) {
            config::override($tenant->id, 'brandcolor', $data->brandcolor, 'theme_boost');
        } else {
            config::override($tenant->id, 'brandcolor', null, 'theme_boost');
        }
    }

    if (has_capability('moodle/site:config', $syscontext)) {
        if (isset($data->scsspre_override)) {
            if ($data->scsspre_override) {
                config::override($tenant->id, 'scsspre', $data->scsspre, 'theme_boost');
            } else {
                config::override($tenant->id, 'scsspre', null, 'theme_boost');
            }
        }

        if (isset($data->scss_override)) {
            if ($data->scss_override) {
                config::override($tenant->id, 'scss', $data->scss, 'theme_boost');
            } else {
                config::override($tenant->id, 'scss', null, 'theme_boost');
            }
        }
    }

    if (appearance::has_custom_css($tenant->id, 'boost')) {
        theme_reset_all_caches();
    }

    \tool_mutenancy\event\appearance_updated::create_from_tenant($tenant)->trigger();

    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
