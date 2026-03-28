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

namespace tool_muprog\local\content;

use stdClass;

/**
 * Credits item.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class credits extends item {
    #[\Override]
    public static function get_type(): string {
        return 'credits';
    }

    #[\Override]
    public static function get_type_name(): string {
        return get_string('credits', 'tool_muprog');
    }

    /**
     * ReturnGet credit credit framework id.
     *
     * @return int
     */
    public function get_creditframeworkid(): int {
        return $this->creditframeworkid;
    }

    /**
     * What is the required total learning?
     *
     * @return string|null decimal number
     */
    public function get_required_credits(): ?string {
        global $DB;
        $framework = $DB->get_record('tool_mutrain_framework', ['id' => $this->creditframeworkid]);
        if (!$framework) {
            return null;
        }
        return $framework->requiredcredits;
    }

    /**
     * What is the current sum of completed credits?
     *
     * @param stdClass $allocation
     * @return int
     */
    public function get_completed_credits(stdClass $allocation): int {
        global $DB;

        return (int)$DB->get_field('tool_mutrain_credit', 'credits', [
            'frameworkid' => $this->creditframeworkid,
            'userid' => $allocation->userid,
        ]);
    }

    /**
     * Current progress for current active program allocations,
     * required credits in all other cases.
     *
     * @param stdClass $allocation
     * @return string
     */
    public function get_current_credits(stdClass $allocation): string {
        global $DB, $USER;

        $now = time();

        $framework = $DB->get_record('tool_mutrain_framework', ['id' => $this->creditframeworkid]);
        if (!$framework) {
            return get_string('error');
        }

        $frameworkcontext = \context::instance_by_id($framework->contextid);
        $usercontext = \context_user::instance($allocation->userid);

        $requiredcredits = format_float($framework->requiredcredits, 2, true, true);

        if (
            $framework->archived || $allocation->archived
            || $allocation->timestart > $now || ($allocation->timeend && $allocation->timeend <= $now)
        ) {
            return get_string('credits_requiredcredits', 'tool_muprog', $requiredcredits);
        }

        $currentcredits = self::get_completed_credits($allocation);
        $data = [
            'current' => $currentcredits,
            'total' => $requiredcredits,
        ];

        $result = get_string('credits_progress', 'tool_muprog', $data);

        if ($currentcredits) {
            if ($USER->id == $allocation->userid || has_capability('tool/mutrain:viewusercredits', $usercontext)) {
                if ($framework->publicaccess || has_capability('tool/mutrain:viewframeworks', $frameworkcontext)) {
                    $url = new \core\url('/admin/tool/mutrain/my/completions.php', ['frameworkid' => $framework->id, 'userid' => $allocation->userid]);
                    $result = \html_writer::link($url, $result);
                }
            }
        }

        return $result;
    }

    #[\Override]
    protected static function init_from_record(\stdClass $record, ?item $previous, array &$unusedrecords, array &$prerequisites): item {
        if ($record->type !== 'credits' || $record->topitem || $record->courseid !== null || !$record->creditframeworkid) {
            throw new \core\exception\coding_exception('Invalid credits item');
        }
        return parent::init_from_record($record, $previous, $unusedrecords, $prerequisites);
    }

    #[\Override]
    protected function get_record(): array {
        global $DB;

        $record = parent::get_record();

        $fullname = $DB->get_field('tool_mutrain_framework', 'name', ['id' => $this->creditframeworkid]);
        if ($fullname !== false) {
            $record['fullname'] = $fullname;
        }

        return $record;
    }
}
