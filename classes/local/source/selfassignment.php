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

namespace tool_mucertify\local\source;

use stdClass;

/**
 * Certification self assignment source.
 *
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class selfassignment extends base {
    /**
     * Return short type name of source, it is used in database to identify this source.
     *
     * NOTE: this must be unique and ite cannot be changed later
     *
     * @return string
     */
    public static function get_type(): string {
        return 'selfassignment';
    }

    /**
     * Can the user request self-assignment?
     *
     * @param stdClass $certification
     * @param stdClass $source
     * @param int $userid
     * @param string|null $failurereason optional failure reason
     * @return bool
     */
    public static function can_user_request(\stdClass $certification, \stdClass $source, int $userid, ?string &$failurereason = null): bool {
        global $DB;

        if ($source->type !== 'selfassignment') {
            throw new \coding_exception('invalid source parameter');
        }

        if ($certification->archived) {
            return false;
        }

        if ($userid <= 0 || isguestuser($userid)) {
            return false;
        }

        if (!\tool_mucertify\local\catalogue::is_certification_visible($certification, $userid)) {
            return false;
        }

        if ($DB->record_exists('tool_mucertify_assignment', ['certificationid' => $certification->id, 'userid' => $userid])) {
            return false;
        }

        $data = (object)json_decode($source->datajson);
        if (isset($data->maxusers)) {
            // Any type of assignments.
            $count = $DB->count_records('tool_mucertify_assignment', ['certificationid' => $certification->id]);
            if ($count >= $data->maxusers) {
                $failurereason = get_string('source_selfassignment_maxusersreached', 'tool_mucertify');
                $failurereason = '<em><strong>' . $failurereason . '</strong></em>';
                return false;
            }
        }
        if (isset($data->allowsignup) && !$data->allowsignup) {
            return false;
        }

        return true;
    }

    /**
     * Returns list of actions available in certification catalogue.
     *
     * NOTE: This is intended mainly for students.
     *
     * @param stdClass $certification
     * @param stdClass $source
     * @return string[]
     */
    public static function get_catalogue_actions(\stdClass $certification, \stdClass $source): array {
        global $USER, $OUTPUT;

        $failurereason = null;
        if (!self::can_user_request($certification, $source, (int)$USER->id, $failurereason)) {
            if ($failurereason !== null) {
                return [$failurereason];
            } else {
                return [];
            }
        }

        $url = new \core\url('/admin/tool/mucertify/catalogue/source_selfassignment.php', ['sourceid' => $source->id]);
        $button = new \tool_mulib\output\ajax_form\button($url, get_string('source_selfassignment_assign', 'tool_mucertify'));

        $button = $OUTPUT->render($button);

        return [$button];
    }

    /**
     * Self-allocate current user to certification.
     *
     * @param int $certificationid
     * @param int $sourceid
     * @return stdClass
     */
    public static function signup(int $certificationid, int $sourceid): stdClass {
        global $DB, $USER;

        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $certificationid], '*', MUST_EXIST);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['id' => $sourceid, 'type' => static::get_type(), 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );

        $user = $DB->get_record('user', ['id' => $USER->id, 'deleted' => 0], '*', MUST_EXIST);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['certificationid' => $certification->id, 'userid' => $user->id]);
        if ($assignment) {
            // One assignment per certification only.
            return $assignment;
        }

        $assignment = self::assignment_create($certification, $source, $user->id, []);

        \tool_muprog\local\source\mucertify::sync_certifications($certification->id, $user->id);
        \tool_mucertify\local\notification_manager::trigger_notifications($certification->id, $user->id);

        return $assignment;
    }

    /**
     * Decode extra source settings.
     *
     * @param stdClass $source
     * @return stdClass
     */
    public static function decode_datajson(stdClass $source): stdClass {
        $source->selfassignment_maxusers = '';
        $source->selfassignment_key = '';
        $source->selfassignment_allowsignup = 1;

        if (isset($source->datajson)) {
            $data = (object)json_decode($source->datajson);
            if (isset($data->maxusers) && $data->maxusers !== '') {
                $source->selfassignment_maxusers = (int)$data->maxusers;
            }
            if (isset($data->key)) {
                $source->selfassignment_key = $data->key;
            }
            if (isset($data->allowsignup)) {
                $source->selfassignment_allowsignup = (int)(bool)$data->allowsignup;
            }
        }

        return $source;
    }

    /**
     * Encode extra source settings.
     *
     * @param stdClass $formdata
     * @return string
     */
    public static function encode_datajson(stdClass $formdata): string {
        $data = ['maxusers' => null, 'key' => null, 'allowsignup' => 1];
        if (
            isset($formdata->selfassignment_maxusers)
            && trim($formdata->selfassignment_maxusers) !== ''
            && $formdata->selfassignment_maxusers >= 0
        ) {
            $data['maxusers'] = (int)$formdata->selfassignment_maxusers;
        }
        if (
            isset($formdata->selfassignment_key)
            && trim($formdata->selfassignment_key) !== ''
        ) {
            $data['key'] = $formdata->selfassignment_key;
        }
        if (isset($formdata->selfassignment_allowsignup)) {
            $data['allowsignup'] = (int)(bool)$formdata->selfassignment_allowsignup;
        }
        return \tool_mucertify\local\util::json_encode($data);
    }

    /**
     * Render details about this enabled source in a certification management ui.
     *
     * @param stdClass $certification
     * @param stdClass|null $source
     * @return string
     */
    public static function render_status_details(stdClass $certification, ?stdClass $source): string {
        global $DB;

        $result = parent::render_status_details($certification, $source);

        if ($source) {
            $data = (object)json_decode($source->datajson);
            if (isset($data->key)) {
                $result .= '; ' . get_string('source_selfassignment_keyrequired', 'tool_mucertify');
            }
            if (isset($data->maxusers)) {
                $count = $DB->count_records('tool_mucertify_assignment', ['certificationid' => $certification->id, 'sourceid' => $source->id]);
                $a = (object)['count' => $count, 'max' => $data->maxusers];
                $result .= '; ' . get_string('source_selfassignment_maxusers_status', 'tool_mucertify', $a);
            }
            if (!isset($data->allowsignup) || $data->allowsignup) {
                $result .= '; ' . get_string('source_selfassignment_signupallowed', 'tool_mucertify');
            } else {
                $result .= '; ' . get_string('source_selfassignment_signupnotallowed', 'tool_mucertify');
            }
        }

        return $result;
    }
}
