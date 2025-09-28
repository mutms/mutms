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

namespace tool_muprog\local\source;

use stdClass;

/**
 * Program self allocation source.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class selfallocation extends base {
    /**
     * Return short type name of source, it is used in database to identify this source.
     *
     * NOTE: this must be unique and ite cannot be changed later
     *
     * @return string
     */
    public static function get_type(): string {
        return 'selfallocation';
    }

    /**
     * Is it possible to manually edit user allocation?
     *
     * @param stdClass $program
     * @param stdClass $source
     * @param stdClass $allocation
     * @return bool
     */
    public static function is_allocation_update_possible(stdClass $program, stdClass $source, stdClass $allocation): bool {
        return true;
    }

    /**
     * Is it possible to manually archive and unarchive user allocation?
     *
     * @param stdClass $program
     * @param stdClass $source
     * @param stdClass $allocation
     * @return bool
     */
    public static function is_allocation_archive_possible(stdClass $program, stdClass $source, stdClass $allocation): bool {
        return true;
    }

    /**
     * Can settings of this source be imported to other program?
     *
    /**
     * Can settings of this source be imported to other program?
     *
     * @param stdClass $fromprogram
     * @param stdClass $targetprogram
     * @return bool
     */
    public static function is_import_allowed(stdClass $fromprogram, stdClass $targetprogram): bool {
        global $DB;

        if (!$DB->record_exists('tool_muprog_source', ['type' => static::get_type(), 'programid' => $fromprogram->id])) {
            return false;
        }

        if (!$DB->record_exists('tool_muprog_source', ['type' => static::get_type(), 'programid' => $targetprogram->id])) {
            if (!static::is_new_allowed($targetprogram)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Is it possible to manually delete user allocation?
     *
     * @param stdClass $program
     * @param stdClass $source
     * @param stdClass $allocation
     * @return bool
     */
    public static function is_allocation_delete_possible(stdClass $program, stdClass $source, stdClass $allocation): bool {
        return true;
    }

    /**
     * Can the user request self-allocation?
     *
     * @param stdClass $program
     * @param stdClass $source
     * @param int $userid
     * @param string|null $failurereason optional failure reason
     * @return bool
     */
    public static function can_user_request(\stdClass $program, \stdClass $source, int $userid, ?string &$failurereason = null): bool {
        global $DB;

        if ($source->type !== 'selfallocation') {
            throw new \coding_exception('invalid source parameter');
        }

        if ($program->archived) {
            return false;
        }

        if ($userid <= 0 || isguestuser($userid)) {
            return false;
        }

        if ($program->timeallocationstart && $program->timeallocationstart > time()) {
            return false;
        }

        if ($program->timeallocationend && $program->timeallocationend < time()) {
            return false;
        }

        if (!\tool_muprog\local\catalogue::is_program_visible($program, $userid)) {
            return false;
        }

        if ($DB->record_exists('tool_muprog_allocation', ['programid' => $program->id, 'userid' => $userid])) {
            return false;
        }

        $data = (object)json_decode($source->datajson);
        if (isset($data->maxusers)) {
            // Any type of allocations.
            $count = $DB->count_records('tool_muprog_allocation', ['programid' => $program->id]);
            if ($count >= $data->maxusers) {
                $failurereason = get_string('source_selfallocation_maxusersreached', 'tool_muprog');
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
     * Returns list of actions available in Program catalogue.
     *
     * NOTE: This is intended mainly for students.
     *
     * @param stdClass $program
     * @param stdClass $source
     * @return string[]
     */
    public static function get_catalogue_actions(\stdClass $program, \stdClass $source): array {
        global $USER, $OUTPUT;

        $failurereason = null;
        if (!self::can_user_request($program, $source, (int)$USER->id, $failurereason)) {
            if ($failurereason !== null) {
                return [$failurereason];
            } else {
                return [];
            }
        }

        $url = new \moodle_url('/admin/tool/muprog/catalogue/source_selfallocation.php', ['sourceid' => $source->id]);
        $button = new \tool_mulib\output\ajax_form\button($url, get_string('source_selfallocation_allocate', 'tool_muprog'));

        $button = $OUTPUT->render($button);

        return [$button];
    }

    /**
     * Self-allocate current user to program.
     *
     * @param int $programid
     * @param int $sourceid
     * @return stdClass
     */
    public static function signup(int $programid, int $sourceid): stdClass {
        global $DB, $USER;

        $program = $DB->get_record('tool_muprog_program', ['id' => $programid], '*', MUST_EXIST);
        $source = $DB->get_record(
            'tool_muprog_source',
            ['id' => $sourceid, 'type' => static::get_type(), 'programid' => $program->id],
            '*',
            MUST_EXIST
        );

        $user = $DB->get_record('user', ['id' => $USER->id, 'deleted' => 0], '*', MUST_EXIST);
        $allocation = $DB->get_record('tool_muprog_allocation', ['programid' => $program->id, 'userid' => $user->id]);
        if ($allocation) {
            // One allocation per program only.
            return $allocation;
        }

        $allocation = self::allocation_create($program, $source, $user->id, []);

        \tool_muprog\local\allocation::fix_user_enrolments($program->id, $user->id);
        \tool_muprog\local\notification_manager::trigger_notifications($program->id, $user->id);

        return $allocation;
    }

    /**
     * Decode extra source settings.
     *
     * @param stdClass $source
     * @return stdClass
     */
    public static function decode_datajson(stdClass $source): stdClass {
        $source->selfallocation_maxusers = '';
        $source->selfallocation_key = '';
        $source->selfallocation_allowsignup = 1;

        if (isset($source->datajson)) {
            $data = (object)json_decode($source->datajson);
            if (isset($data->maxusers) && $data->maxusers !== '') {
                $source->selfallocation_maxusers = (int)$data->maxusers;
            }
            if (isset($data->key)) {
                $source->selfallocation_key = $data->key;
            }
            if (isset($data->allowsignup)) {
                $source->selfallocation_allowsignup = (int)(bool)$data->allowsignup;
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
            isset($formdata->selfallocation_maxusers)
            && trim($formdata->selfallocation_maxusers) !== ''
            && $formdata->selfallocation_maxusers >= 0
        ) {
            $data['maxusers'] = (int)$formdata->selfallocation_maxusers;
        }
        if (
            isset($formdata->selfallocation_key)
            && trim($formdata->selfallocation_key) !== ''
        ) {
            $data['key'] = (string)$formdata->selfallocation_key;
        }
        if (isset($formdata->selfallocation_allowsignup)) {
            $data['allowsignup'] = (int)(bool)$formdata->selfallocation_allowsignup;
        }
        return \tool_muprog\local\util::json_encode($data);
    }

    /**
     * Render details about this enabled source in a programs management ui.
     *
     * @param stdClass $program
     * @param stdClass|null $source
     * @return string
     */
    public static function render_status_details(stdClass $program, ?stdClass $source): string {
        global $DB;

        $result = parent::render_status_details($program, $source);

        if ($source) {
            $data = (object)json_decode($source->datajson);
            if (isset($data->key)) {
                $result .= '; ' . get_string('source_selfallocation_keyrequired', 'tool_muprog');
            }
            if (isset($data->maxusers)) {
                $count = $DB->count_records('tool_muprog_allocation', ['programid' => $program->id, 'sourceid' => $source->id]);
                $a = (object)['count' => $count, 'max' => $data->maxusers];
                $result .= '; ' . get_string('source_selfallocation_maxusers_status', 'tool_muprog', $a);
            }
            if (!isset($data->allowsignup) || $data->allowsignup) {
                $result .= '; ' . get_string('source_selfallocation_signupallowed', 'tool_muprog');
            } else {
                $result .= '; ' . get_string('source_selfallocation_signupnotallowed', 'tool_muprog');
            }
        }

        return $result;
    }
}
