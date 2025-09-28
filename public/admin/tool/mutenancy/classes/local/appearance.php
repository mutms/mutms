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

namespace tool_mutenancy\local;

use moodle_url;

/**
 * Multi-tenancy appearance helper.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class appearance {
    /**
     * Does the theme have any custom tenant CSS?
     *
     * @param int $tenantid
     * @param string $theme
     * @return bool
     */
    public static function has_custom_css(int $tenantid, string $theme): bool {
        $overrides = \tool_mutenancy\local\config::fetch_overrides($tenantid, 'theme_' . $theme);
        return !empty($overrides);
    }

    /**
     * Returns tenant specific logo URL if configured.
     *
     * @param int|null $maxwidth
     * @param int|null $maxheight
     * @param int|null $tenantid -1 means use current tenant
     * @return moodle_url|false
     */
    public static function get_logo_url(?int $maxwidth, ?int $maxheight, ?int $tenantid = -1) {
        if ($tenantid < 0) {
            $tenantid = tenancy::get_current_tenantid();
        }
        if (!$tenantid || !config::is_overridden($tenantid, 'core_admin', 'logo')) {
            $logo = get_config('core_admin', 'logo');
            if (!$logo) {
                return false;
            }

            $filepath = ((int) $maxwidth . 'x' . (int) $maxheight) . '/';

            return moodle_url::make_pluginfile_url(
                \context_system::instance()->id,
                'core_admin',
                'logo',
                $filepath,
                theme_get_revision(),
                $logo
            );
        }

        $logo = config::get($tenantid, 'core_admin', 'logo');
        if ($logo === '') {
            return false;
        }

        $tenantcontext = \context_tenant::instance($tenantid);

        $filepath = ((int) $maxwidth . 'x' . (int) $maxheight) . '/';

        return moodle_url::make_pluginfile_url(
            $tenantcontext->id,
            'core_admin',
            'logo',
            $filepath,
            theme_get_revision(),
            $logo
        );
    }

    /**
     * Returns tenant specific logocompact URL if configured.
     *
     * @param int|null $maxwidth
     * @param int|null $maxheight
     * @param int|null $tenantid -1 means use current tenant
     * @return moodle_url|false
     */
    public static function get_compact_logo_url(?int $maxwidth, ?int $maxheight, ?int $tenantid = -1) {
        if ($tenantid < 0) {
            $tenantid = tenancy::get_current_tenantid();
        }
        if (!$tenantid || !config::is_overridden($tenantid, 'core_admin', 'logocompact')) {
            $logo = get_config('core_admin', 'logocompact');
            if (!$logo) {
                return false;
            }

            $filepath = ((int) $maxwidth . 'x' . (int) $maxheight) . '/';

            return moodle_url::make_pluginfile_url(
                \context_system::instance()->id,
                'core_admin',
                'logocompact',
                $filepath,
                theme_get_revision(),
                $logo
            );
        }

        $logocompact = config::get($tenantid, 'core_admin', 'logocompact');
        if ($logocompact === '') {
            return false;
        }

        $tenantcontext = \context_tenant::instance($tenantid);

        $filepath = ((int) $maxwidth . 'x' . (int) $maxheight) . '/';

        return moodle_url::make_pluginfile_url(
            $tenantcontext->id,
            'core_admin',
            'logocompact',
            $filepath,
            theme_get_revision(),
            $logocompact
        );
    }

    /**
     * Returns tenant specific favicon URL if configured.
     *
     * @param int|null $tenantid -1 means use current tenant
     * @return moodle_url|false
     */
    public static function get_favicon_url(?int $tenantid = -1) {
        if ($tenantid < 0) {
            $tenantid = tenancy::get_current_tenantid();
        }
        if (!$tenantid || !config::is_overridden($tenantid, 'core_admin', 'favicon')) {
            $logo = get_config('core_admin', 'favicon');
            if (!$logo) {
                return false;
            }

            return moodle_url::make_pluginfile_url(
                \context_system::instance()->id,
                'core_admin',
                'favicon',
                '64x64/',
                theme_get_revision(),
                $logo
            );
        }

        $logo = config::get($tenantid, 'core_admin', 'favicon');
        if ($logo === '') {
            return false;
        }

        $tenantcontext = \context_tenant::instance($tenantid);

        return moodle_url::make_pluginfile_url(
            $tenantcontext->id,
            'core_admin',
            'favicon',
            '64x64/',
            theme_get_revision(),
            $logo
        );
    }

    /**
     * Returns tenant specific boost theme image URL if configured.
     *
     * @param string $setting
     * @param int|null $tenantid -1 means use current tenant
     * @return moodle_url|false
     */
    public static function get_boost_setting_image_url(string $setting, ?int $tenantid = -1) {
        global $CFG;

        if ($tenantid < 0) {
            $tenantid = tenancy::get_current_tenantid();
        }
        if (!$tenantid || !config::is_overridden($tenantid, 'theme_boost', $setting)) {
            $image = get_config('theme_boost', $setting);
            if (!$image) {
                return false;
            }
            $context = \context_system::instance();
        } else {
            $image = config::get($tenantid, 'theme_boost', $setting);
            if ($image === '') {
                return false;
            }
            $context = \context_tenant::instance($tenantid);
        }

        $itemid = theme_get_revision();
        return moodle_url::make_file_url(
            "$CFG->wwwroot/pluginfile.php",
            "/$context->id/theme_boost/$setting/$itemid" . $image,
        );
    }

    /**
     * Is the value a valid CSS colour?
     *
     * @param string $value
     * @return bool
     */
    public static function is_valid_color(string $value): bool {
        // See original code in \admin_setting_configcolourpicker::validate().
        $colornames = [
            'aliceblue', 'antiquewhite', 'aqua', 'aquamarine', 'azure',
            'beige', 'bisque', 'black', 'blanchedalmond', 'blue',
            'blueviolet', 'brown', 'burlywood', 'cadetblue', 'chartreuse',
            'chocolate', 'coral', 'cornflowerblue', 'cornsilk', 'crimson',
            'cyan', 'darkblue', 'darkcyan', 'darkgoldenrod', 'darkgray',
            'darkgrey', 'darkgreen', 'darkkhaki', 'darkmagenta',
            'darkolivegreen', 'darkorange', 'darkorchid', 'darkred',
            'darksalmon', 'darkseagreen', 'darkslateblue', 'darkslategray',
            'darkslategrey', 'darkturquoise', 'darkviolet', 'deeppink',
            'deepskyblue', 'dimgray', 'dimgrey', 'dodgerblue', 'firebrick',
            'floralwhite', 'forestgreen', 'fuchsia', 'gainsboro',
            'ghostwhite', 'gold', 'goldenrod', 'gray', 'grey', 'green',
            'greenyellow', 'honeydew', 'hotpink', 'indianred', 'indigo',
            'ivory', 'khaki', 'lavender', 'lavenderblush', 'lawngreen',
            'lemonchiffon', 'lightblue', 'lightcoral', 'lightcyan',
            'lightgoldenrodyellow', 'lightgray', 'lightgrey', 'lightgreen',
            'lightpink', 'lightsalmon', 'lightseagreen', 'lightskyblue',
            'lightslategray', 'lightslategrey', 'lightsteelblue', 'lightyellow',
            'lime', 'limegreen', 'linen', 'magenta', 'maroon',
            'mediumaquamarine', 'mediumblue', 'mediumorchid', 'mediumpurple',
            'mediumseagreen', 'mediumslateblue', 'mediumspringgreen',
            'mediumturquoise', 'mediumvioletred', 'midnightblue', 'mintcream',
            'mistyrose', 'moccasin', 'navajowhite', 'navy', 'oldlace', 'olive',
            'olivedrab', 'orange', 'orangered', 'orchid', 'palegoldenrod',
            'palegreen', 'paleturquoise', 'palevioletred', 'papayawhip',
            'peachpuff', 'peru', 'pink', 'plum', 'powderblue', 'purple', 'red',
            'rosybrown', 'royalblue', 'saddlebrown', 'salmon', 'sandybrown',
            'seagreen', 'seashell', 'sienna', 'silver', 'skyblue', 'slateblue',
            'slategray', 'slategrey', 'snow', 'springgreen', 'steelblue', 'tan',
            'teal', 'thistle', 'tomato', 'turquoise', 'violet', 'wheat', 'white',
            'whitesmoke', 'yellow', 'yellowgreen',
        ];

        if (preg_match('/^#([[:xdigit:]]{3}){1,2}$/', $value)) {
            return true;
        } else if (in_array(strtolower($value), $colornames)) {
            return true;
        } else if (preg_match('/rgb\(\d{0,3}%?, ?\d{0,3}%?, ?\d{0,3}%?\)/i', $value)) {
            return true;
        } else if (preg_match('/hsl\(\d{0,3}, ?\d{0,3}%, ?\d{0,3}%\)/i', $value)) {
            return true;
        } else {
            return false;
        }
    }
}
