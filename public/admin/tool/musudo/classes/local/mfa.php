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

namespace tool_musudo\local;

use tool_mfa\local\factor\object_factor_base;

/**
 * MFA helper for sudo.
 *
 * @package    tool_musudo
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class mfa {
    /**
     * Is the mfa tool enabled?
     *
     * @return bool
     */
    public static function is_mfa_enabled(): bool {
        return (bool)get_config('tool_mfa', 'enabled');
    }

    /**
     * Return factors that use can use for sudo.
     *
     * @return object_factor_base[]
     */
    public static function get_user_factors(): array {
        global $USER;

        // Allow only a subset of factors that are interactive using some secret,
        // order is based on how easy to use they are.
        $allowed = [
            'webauthn' => null,
            'totp' => null,
            'email' => null,
            'sms' => null,
        ];

        /** @var object_factor_base[] $userfactors */
        $userfactors = \tool_mfa\plugininfo\factor::get_active_user_factor_types($USER);
        foreach ($userfactors as $factor) {
            if (array_key_exists($factor->name, $allowed)) {
                $allowed[$factor->name] = $factor;
            }
        }
        foreach ($allowed as $k => $v) {
            if ($v === null) {
                unset($allowed[$k]);
            }
        }

        return $allowed;
    }

    /**
     * Add factor to start form.
     *
     * @param \MoodleQuickForm $mform
     * @param object_factor_base $factor
     * @param object_factor_base[] $factors
     * @return void
     */
    public static function form_definition(\MoodleQuickForm $mform, object_factor_base $factor, array $factors): void {
        global $OUTPUT;
        $factorname = $factor->name;

        $disablefactor = false;

        $factor->load_locked_state();
        $remattempts = $factor->get_remaining_attempts();
        if ($remattempts <= 0) {
            $disablefactor = true;
        }

        $header = $OUTPUT->render_from_template('tool_musudo/factorheader', [
            'factoricon' => $factor->get_icon(),
            'logintitle' => get_string('logintitle', 'factor_' . $factorname),
            'logindesc' => $factor->get_login_desc(),
            'disablefactor' => $disablefactor,
        ]);
        $mform->addElement('html', $header);

        $factor->login_form_definition($mform);

        if ($disablefactor) {
            if ($mform->elementExists('verificationcode')) {
                $mform->hardFreeze('verificationcode');
            }
        }
    }

    /**
     * Add additional factors to start form.
     *
     * @param \MoodleQuickForm $mform
     * @param object_factor_base $factor
     * @param object_factor_base[] $factors
     * @return void
     */
    public static function form_definition_additional(\MoodleQuickForm $mform, object_factor_base $factor, array $factors): void {
        global $OUTPUT;

        if (count($factors) < 2) {
            return;
        }

        $additionalfactors = [];
        foreach ($factors as $f) {
            if ($f->name === $factor->name) {
                continue;
            }
            $a = [
                'url' => new \moodle_url('/admin/tool/musudo/sudo_start.php', ['factor' => $f->name]),
                'icon' => $f->get_icon(),
                'name' => $f->name,
                'loginoption' => get_string('logintitle', 'factor_' . $f->name),
                'disable' => false,
            ];
            $f->load_locked_state();
            $remattempts = $f->get_remaining_attempts();
            if ($remattempts <= 0) {
                $a['disable'] = true;
                $a['loginoption'] = get_string('locked', 'tool_mfa', $a['loginoption']);
                ;
            }
            $additionalfactors[] = $a;
        }
        $additional = $OUTPUT->render_from_template('tool_musudo/additionalfactors', [
            'additionalfactors' => $additionalfactors,
        ]);
        $mform->addElement('html', $additional);
    }

    /**
     * Alter "after data" start form.
     *
     * @param \MoodleQuickForm $mform
     * @param object_factor_base $factor
     * @return void
     */
    public static function form_definition_after_data(\MoodleQuickForm $mform, object_factor_base $factor): void {
        $factor->login_form_definition_after_data($mform);
    }

    /**
     * Validate factor on start form.
     *
     * @param \MoodleQuickForm $mform
     * @param object_factor_base $factor
     * @param array $data submitted data
     * @param array $files not used
     * @return array
     */
    public static function form_validation(\MoodleQuickForm $mform, object_factor_base $factor, array $data, array $files): array {
        global $DB, $USER;

        $factor->load_locked_state();
        $remattempts = $factor->get_remaining_attempts();
        if ($remattempts <= 0) {
            return ['verificationcode' => get_string('state:locked', 'tool_mfa')];
        }

        $errors = $factor->login_form_validation($data);
        if ($errors) {
            $factor->load_locked_state();
            $factor->increment_lock_counter();

            // Execute sleep time bruteforce mitigation.
            \tool_mfa\manager::sleep_timer();

            $remattempts = $factor->get_remaining_attempts();
            if ($remattempts <= 0) {
                if ($mform->elementExists('verificationcode')) {
                    $mform->freeze('verificationcode');
                }
                $errors['verificationcode'] = get_string('state:locked', 'tool_mfa');
            } else if (isset($errors['verificationcode'])) {
                $errors['verificationcode'] .= '&nbsp;' . get_string('lockoutnotification', 'tool_mfa', $remattempts);
            }
            return $errors;
        }

        // Deleted secrets after each pass.
        $factor->post_pass_state();
        // Update flags.
        $factor->update_lastverified();
        $factor->increment_lock_counter();
        $DB->set_field('tool_mfa', 'lockcounter', 0, ['userid' => $USER->id, 'factor' => $factor->name]);

        return [];
    }
}
