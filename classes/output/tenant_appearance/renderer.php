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

namespace tool_mutenancy\output\tenant_appearance;

use tool_mutenancy\local\config;
use tool_mutenancy\local\appearance;

/**
 * Tenant appearance settings renderer.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class renderer extends \tool_mutenancy\output\tenant_renderer_base {
    #[\Override]
    public function render_section(\stdClass $tenant): string {
        $tenantcontext = \context_tenant::instance($tenant->id);
        $canconfig = has_capability('tool/mutenancy:configappearance', $tenantcontext);

        $result = '<h2>' . get_string('logossettings', 'core_admin') . '</h2>';

        $details = new \tool_mulib\output\entity_details();

        if (config::is_overridden($tenant->id, 'core_admin', 'logo')) {
            $logo = \tool_mutenancy\local\appearance::get_logo_url(100, 64, $tenant->id);
            $isdefault = false;
        } else {
            $logo = \tool_mutenancy\local\appearance::get_logo_url(100, 64, null);
            $isdefault = true;
        }
        if ($logo) {
            $logo = \core\output\html_writer::img($logo, '', ['style' => 'max-height: 64px;']);
        } else {
            $logo = get_string('none');
        }
        if ($isdefault) {
            $logo = get_string('config_default_value', 'tool_mutenancy', $logo);
        }
        $details->add(get_string('logo', 'admin'), $logo);

        if (config::is_overridden($tenant->id, 'core_admin', 'logocompact')) {
            $logocompact = \tool_mutenancy\local\appearance::get_compact_logo_url(100, 64, $tenant->id);
            $isdefault = false;
        } else {
            $logocompact = \tool_mutenancy\local\appearance::get_compact_logo_url(100, 64, null);
            $isdefault = true;
        }
        if ($logocompact) {
            $logocompact = \core\output\html_writer::img($logocompact, '', ['style' => 'max-height: 64px;']);
        } else {
            $logocompact = get_string('none');
        }
        if ($isdefault) {
            $logocompact = get_string('config_default_value', 'tool_mutenancy', $logocompact);
        }
        $details->add(get_string('logocompact', 'admin'), $logocompact);

        if (config::is_overridden($tenant->id, 'core_admin', 'favicon')) {
            $favicon = \tool_mutenancy\local\appearance::get_favicon_url($tenant->id);
            $isdefault = false;
        } else {
            $favicon = \tool_mutenancy\local\appearance::get_favicon_url(null);
            $isdefault = true;
        }
        if ($favicon === false) {
            $favicon = $this->image_url('favicon', 'theme');
        }
        $favicon = \core\output\html_writer::img($favicon, '', ['style' => 'width: 16px; height: 16px;']);
        if ($isdefault) {
            $favicon = get_string('config_default_value', 'tool_mutenancy', $favicon);
        }
        $details->add(get_string('favicon', 'admin'), $favicon);

        $result .= $this->output->render($details);

        $buttons = [];
        if ($canconfig) {
            $url = new \core\url('/admin/tool/mutenancy/management/logos_edit.php', ['id' => $tenant->id]);
            $button = new \tool_mulib\output\ajax_form\button($url, get_string('logos_edit', 'tool_mutenancy'));
            $button->set_form_size('xl');
            $buttons[] = $this->render($button);
        }
        $result .= '<div class="buttons">' . implode('', $buttons) . '</div>';

        $result .= '<br />';
        $result .= '<h2>' . get_string('pluginname', 'theme_boost') . '</h2>';

        $details = new \tool_mulib\output\entity_details();

        if (config::is_overridden($tenant->id, 'theme_boost', 'preset')) {
            $preset = config::get($tenant->id, 'theme_boost', 'preset');
            $isdefault = false;
        } else {
            $preset = get_config('theme_boost', 'preset');
            $isdefault = true;
        }
        if (!$preset) {
            $preset = get_string('none');
        }
        if ($isdefault) {
            $preset = get_string('config_default_value', 'tool_mutenancy', $preset);
        }
        $details->add(get_string('preset', 'theme_boost'), $preset);

        if (config::is_overridden($tenant->id, 'theme_boost', 'backgroundimage')) {
            $backgroundimage = \tool_mutenancy\local\appearance::get_boost_setting_image_url('backgroundimage', $tenant->id);
            $isdefault = false;
        } else {
            $backgroundimage = \tool_mutenancy\local\appearance::get_boost_setting_image_url('backgroundimage', null);
            $isdefault = true;
        }
        if ($backgroundimage) {
            $backgroundimage = \core\output\html_writer::img($backgroundimage, '', ['style' => 'max-height: 64px;']);
        } else {
            $backgroundimage = get_string('none');
        }
        if ($isdefault) {
            $backgroundimage = get_string('config_default_value', 'tool_mutenancy', $backgroundimage);
        }
        $details->add(get_string('backgroundimage', 'theme_boost'), $backgroundimage);

        if (config::is_overridden($tenant->id, 'theme_boost', 'loginbackgroundimage')) {
            $loginbackgroundimage = \tool_mutenancy\local\appearance::get_boost_setting_image_url('loginbackgroundimage', $tenant->id);
            $isdefault = false;
        } else {
            $loginbackgroundimage = \tool_mutenancy\local\appearance::get_boost_setting_image_url('loginbackgroundimage', null);
            $isdefault = true;
        }
        if ($loginbackgroundimage) {
            $loginbackgroundimage = \core\output\html_writer::img($loginbackgroundimage, '', ['style' => 'max-height: 64px;']);
        } else {
            $loginbackgroundimage = get_string('none');
        }
        if ($isdefault) {
            $loginbackgroundimage = get_string('config_default_value', 'tool_mutenancy', $loginbackgroundimage);
        }
        $details->add(get_string('loginbackgroundimage', 'theme_boost'), $loginbackgroundimage);

        if (config::is_overridden($tenant->id, 'theme_boost', 'brandcolor')) {
            $color = config::get($tenant->id, 'theme_boost', 'brandcolor');
            $isdefault = false;
        } else {
            $color = get_config('theme_boost', 'brandcolor');
            $isdefault = true;
        }
        if ($color) {
            $color = "<span style='color: $color'>$color</span>";
        } else {
            $color = get_string('none');
        }
        if ($isdefault) {
            $color = get_string('config_default_value', 'tool_mutenancy', $color);
        }
        $details->add(get_string('brandcolor', 'theme_boost'), $color);

        if (config::is_overridden($tenant->id, 'theme_boost', 'scsspre')) {
            $scsspre = config::get($tenant->id, 'theme_boost', 'scsspre');
            $isdefault = false;
        } else {
            $scsspre = get_config('theme_boost', 'scsspre');
            $isdefault = true;
        }
        if ($scsspre !== '') {
            $scsspre = '<pre>' . s($scsspre) . '</pre>';
        } else {
            $scsspre = get_string('none');
        }
        if ($isdefault) {
            $scsspre = get_string('config_default_value', 'tool_mutenancy', $scsspre);
        }
        $details->add(get_string('rawscsspre', 'theme_boost'), $scsspre);

        if (config::is_overridden($tenant->id, 'theme_boost', 'scss')) {
            $scss = config::get($tenant->id, 'theme_boost', 'scss');
            $isdefault = false;
        } else {
            $scss = get_config('theme_boost', 'scss');
            $isdefault = true;
        }
        if ($scss !== '') {
            $scss = '<pre>' . s($scss) . '</pre>';
        } else {
            $scss = get_string('none');
        }
        if ($isdefault) {
            $scss = get_string('config_default_value', 'tool_mutenancy', $scss);
        }
        $details->add(get_string('rawscss', 'theme_boost'), $scss);

        $result .= $this->output->render($details);

        $buttons = [];
        if ($canconfig) {
            $url = new \core\url('/admin/tool/mutenancy/management/theme_boost_edit.php', ['id' => $tenant->id]);
            $button = new \tool_mulib\output\ajax_form\button($url, get_string('boost_edit', 'tool_mutenancy'));
            $button->set_form_size('xl');
            $buttons[] = $this->render($button);
        }
        $result .= '<div class="buttons">' . implode('', $buttons) . '</div>';

        return $result;
    }
}
