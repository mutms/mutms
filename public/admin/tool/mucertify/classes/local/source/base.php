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

use tool_mucertify\local\assignment;
use tool_mucertify\navigation\views\certification_secondary;
use tool_mucertify\local\notification_manager;
use tool_mucertify\local\period;
use stdClass;

/**
 * Certification source abstraction.
 *
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base {
    /**
     * Return short type name of source, it is used in database to identify this source.
     *
     * NOTE: this must be unique and ite cannot be changed later
     *
     * @return string
     */
    public static function get_type(): string {
        throw new \coding_exception('cannot be called on base class');
    }

    /**
     * Returns name of the source.
     *
     * @return string
     */
    public static function get_name(): string {
        $type = static::get_type();
        return get_string('source_' . $type, 'tool_mucertify');
    }

    /**
     * Can a new source of this type be added to certifications?
     *
     * NOTE: Existing enabled sources in certifications cannot be deleted/hidden
     * if there are any assigned users to certification.
     *
     * @param stdClass $certification
     * @return bool
     */
    public static function is_new_allowed(stdClass $certification): bool {
        $type = static::get_type();
        return (bool)get_config('tool_mucertify', 'source_' . $type . '_allownew');
    }

    /**
     * Can existing source of this type be updated or deleted to certifications?
     *
     * NOTE: Existing enabled sources in certifications cannot be deleted/hidden
     * if there are any assigned users to certification.
     *
     * @param stdClass $certification
     * @return bool
     */
    public static function is_update_allowed(stdClass $certification): bool {
        return true;
    }

    /**
     * Make sure users are assigned properly.
     *
     * This is expected to be called from cron and when
     * certification assignment settings are updated.
     *
     * @param int|null $certificationid
     * @param int|null $userid
     * @return bool true if anything updated
     */
    public static function fix_assignments(?int $certificationid, ?int $userid): bool {
        return false;
    }

    /**
     * Return extra tab for managing the source data in program.
     *
     * @param certification_secondary $secondary
     * @param stdClass $certification
     */
    public static function add_certification_secondary_tabs(certification_secondary $secondary, stdClass $certification): void {
    }

    /**
     * Is it possible to manually edit user assignment?
     *
     * @param stdClass $certification
     * @param stdClass $source
     * @param stdClass $assignment
     * @return bool
     */
    public static function is_assignment_update_possible(stdClass $certification, stdClass $source, stdClass $assignment): bool {
        if (
            $certification->id != $source->certificationid
            || $certification->id != $assignment->certificationid
            || $source->id != $assignment->sourceid
        ) {
            throw new \coding_exception('invalid parameters');
        }
        if ($certification->archived) {
            return false;
        }
        if ($assignment->archived) {
            return false;
        }
        return true;
    }

    /**
     * Is it possible to manually archive user assignment?
     *
     * @param stdClass $certification
     * @param stdClass $source
     * @param stdClass $assignment
     * @return bool
     */
    public static function is_assignment_archive_possible(stdClass $certification, stdClass $source, stdClass $assignment): bool {
        if (
            $certification->id != $source->certificationid
            || $certification->id != $assignment->certificationid
            || $source->id != $assignment->sourceid
        ) {
            throw new \coding_exception('invalid parameters');
        }
        if ($certification->archived) {
            return false;
        }
        if ($assignment->archived) {
            return false;
        }
        return true;
    }

    /**
     * Is it possible to manually restore user assignment?
     *
     * @param stdClass $certification
     * @param stdClass $source
     * @param stdClass $assignment
     * @return bool
     */
    public static function is_assignment_restore_possible(stdClass $certification, stdClass $source, stdClass $assignment): bool {
        if (
            $certification->id != $source->certificationid
            || $certification->id != $assignment->certificationid
            || $source->id != $assignment->sourceid
        ) {
            throw new \coding_exception('invalid parameters');
        }
        if ($certification->archived) {
            return false;
        }
        if (!$assignment->archived) {
            return false;
        }
        return true;
    }

    /**
     * Is it possible to manually delete user assignment?
     *
     * @param stdClass $certification
     * @param stdClass $source
     * @param stdClass $assignment
     * @return bool
     */
    public static function is_assignment_delete_possible(stdClass $certification, stdClass $source, stdClass $assignment): bool {
        if (
            $certification->id != $source->certificationid
            || $certification->id != $assignment->certificationid
            || $source->id != $assignment->sourceid
        ) {
            throw new \coding_exception('invalid parameters');
        }
        if ($certification->archived) {
            return false;
        }
        if (!$assignment->archived) {
            return false;
        }
        return true;
    }

    /**
     * Assignment related buttons for certification users page.
     *
     * @param \tool_mulib\output\header_actions $actions
     * @param stdClass $certification
     * @param stdClass $source
     * @return void
     */
    public static function add_management_certification_users_actions(\tool_mulib\output\header_actions $actions, stdClass $certification, stdClass $source): void {
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
    public static function get_catalogue_actions(stdClass $certification, stdClass $source): array {
        return [];
    }

    /**
     * Update source details.
     *
     * @param stdClass $data
     * @return stdClass|null assignment source
     */
    final public static function update_source(stdClass $data): ?stdClass {
        global $DB;

        /** @var base[] $sourceclasses */
        $sourceclasses = assignment::get_source_classes();
        if (!isset($sourceclasses[$data->type])) {
            throw new \coding_exception('Invalid source type');
        }
        $sourcetype = $data->type;
        $sourceclass = $sourceclasses[$sourcetype];

        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $data->certificationid], '*', MUST_EXIST);
        $source = $DB->get_record('tool_mucertify_source', ['type' => $sourcetype, 'certificationid' => $certification->id]);
        if ($source) {
            $oldsource = clone($source);
        } else {
            $source = null;
            $oldsource = null;
        }
        if ($source && $source->type !== $data->type) {
            throw new \coding_exception('Invalid source type');
        }

        if ($data->enable) {
            if ($source) {
                $source->datajson = $sourceclass::encode_datajson($data);
                $source->auxint1 = $data->auxint1 ?? null;
                $source->auxint2 = $data->auxint2 ?? null;
                $source->auxint3 = $data->auxint3 ?? null;
                $DB->update_record('tool_mucertify_source', $source);
            } else {
                $source = new stdClass();
                $source->certificationid = $data->certificationid;
                $source->type = $sourcetype;
                $source->datajson = $sourceclass::encode_datajson($data);
                $source->auxint1 = $data->auxint1 ?? null;
                $source->auxint2 = $data->auxint2 ?? null;
                $source->auxint3 = $data->auxint3 ?? null;
                $source->id = $DB->insert_record('tool_mucertify_source', $source);
            }
            $source = $DB->get_record('tool_mucertify_source', ['id' => $source->id], '*', MUST_EXIST);
        } else {
            if ($source) {
                if ($DB->record_exists('tool_mucertify_assignment', ['sourceid' => $source->id])) {
                    throw new \coding_exception('Cannot delete source with assignments');
                }
                $DB->delete_records('tool_mucertify_request', ['sourceid' => $source->id]);
                $DB->delete_records('tool_mucertify_src_cohort', ['sourceid' => $source->id]);
                $DB->delete_records('tool_mucertify_source', ['id' => $source->id]);
                $source = null;
            }
        }
        $sourceclass::after_update($oldsource, $data, $source);

        $sourceclass::fix_assignments($certification->id, null);
        \tool_muprog\local\source\mucertify::sync_certifications($certification->id, null);

        return $source;
    }

    /**
     * Assign user to certification.
     *
     * @param stdClass $certification
     * @param stdClass $source
     * @param int $userid
     * @param array $sourcedata
     * @param array $dateoverrides if 'noperiod' non-empty then period is not created
     * @return stdClass user assignment record
     */
    final protected static function assignment_create(stdClass $certification, stdClass $source, int $userid, array $sourcedata, array $dateoverrides = []): stdClass {
        global $DB;

        if ($userid <= 0 || isguestuser($userid)) {
            throw new \coding_exception('Only real users can be assigned to certifications');
        }

        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0, 'confirmed' => 1], '*', MUST_EXIST);

        $now = time();

        $record = new stdClass();
        $record->certificationid = $certification->id;
        $record->userid = $userid;
        $record->sourceid = $source->id;
        $record->sourcedatajson = \tool_mucertify\local\util::json_encode($sourcedata);
        $record->archived = 0;
        $record->timecertifiedtemp = null;
        if (isset($dateoverrides['timecertifiedtemp']) && $dateoverrides['timecertifiedtemp'] > 0) {
            $record->timecertifiedtemp = $dateoverrides['timecertifiedtemp'];
        }
        $record->evidencejson = \tool_mucertify\local\util::json_encode([]);
        $record->timecreated = empty($dateoverrides['timecreated']) ? $now : $dateoverrides['timecreated'];

        $trans = $DB->start_delegated_transaction();

        $record->id = $DB->insert_record('tool_mucertify_assignment', $record);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['id' => $record->id], '*', MUST_EXIST);

        \tool_mucertify\event\assignment_created::create_from_assignment($certification, $assignment)->trigger();

        if (empty($dateoverrides['noperiod'])) {
            \tool_mucertify\local\period::add_first($assignment, $dateoverrides);
        }
        $assignment = $DB->get_record('tool_mucertify_assignment', ['id' => $assignment->id], '*', MUST_EXIST);

        $trans->allow_commit();

        \tool_mucertify\local\notification\assignment::notify_now($user, $certification, $source, $assignment);

        return $DB->get_record('tool_mucertify_assignment', ['id' => $assignment->id], '*', MUST_EXIST);
    }

    /**
     * Manually update user assignment data including temporary certification.
     *
     * @param stdClass $data
     * @return stdClass assignment record
     */
    final public static function assignment_update(stdClass $data): stdClass {
        global $DB;

        $assignment = $DB->get_record('tool_mucertify_assignment', ['id' => $data->id], '*', MUST_EXIST);
        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $assignment->certificationid], '*', MUST_EXIST);

        $trans = $DB->start_delegated_transaction();

        $update = [];

        if (property_exists($data, 'timecertifiedtemp')) {
            if ($data->timecertifiedtemp != $assignment->timecertifiedtemp) {
                $update['timecertifiedtemp'] = $data->timecertifiedtemp;
                if ($update['timecertifiedtemp'] <= 0) {
                    $update['timecertifiedtemp'] = null;
                }
            }
        }

        // Do not change archived flag here!
        if (isset($data->archived) && $data->archived != $assignment->archived) {
            debugging('Use base::assignment_archive() and base::assignment_restore() to change archived flag', DEBUG_DEVELOPER);
        }

        if ($update) {
            $update['id'] = $assignment->id;
            $DB->update_record('tool_mucertify_assignment', (object)$update);
            $assignment = period::fix_flags($assignment->certificationid, $assignment->userid);
        }

        if ($certification->recertify !== null && property_exists($data, 'stoprecertify')) {
            $stoprecertify = !$DB->record_exists('tool_mucertify_period', [
                'certificationid' => $assignment->certificationid,
                'userid' => $assignment->userid,
                'recertifiable' => 1,
            ]);
            if ($stoprecertify != $data->stoprecertify) {
                $assignment = period::update_recertifiable($assignment, (bool)$data->stoprecertify);
                $update['stoprecertify'] = $data->stoprecertify;
            }
        }

        $handler = \tool_mucertify\customfield\assignment_handler::create();
        $handler->instance_form_save($data);

        \tool_mucertify\event\assignment_updated::create_from_assignment($certification, $assignment)->trigger();

        $trans->allow_commit();

        if ($update) {
            \tool_muprog\local\source\mucertify::sync_certifications($assignment->certificationid, $assignment->userid);
            notification_manager::trigger_notifications($assignment->certificationid, $assignment->userid);
        }

        return $DB->get_record('tool_mucertify_assignment', ['id' => $assignment->id], '*', MUST_EXIST);
    }

    /**
     * Archive user assignment.
     *
     * @param int $assignmentid
     * @return stdClass
     */
    public static function assignment_archive(int $assignmentid): stdClass {
        global $DB;

        $assignment = $DB->get_record('tool_mucertify_assignment', ['id' => $assignmentid], '*', MUST_EXIST);
        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $assignment->certificationid], '*', MUST_EXIST);

        if ($assignment->archived) {
            return $assignment;
        }

        $DB->set_field('tool_mucertify_assignment', 'archived', 1, ['id' => $assignment->id]);

        \tool_mucertify\event\assignment_archived::create_from_assignment($certification, $assignment)->trigger();

        \tool_muprog\local\source\mucertify::sync_certifications($assignment->certificationid, $assignment->userid);

        return $DB->get_record('tool_mucertify_assignment', ['id' => $assignment->id], '*', MUST_EXIST);
    }

    /**
     * Restore user assignment.
     *
     * @param int $assignmentid
     * @return stdClass
     */
    public static function assignment_restore(int $assignmentid): stdClass {
        global $DB;

        $assignment = $DB->get_record('tool_mucertify_assignment', ['id' => $assignmentid], '*', MUST_EXIST);
        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $assignment->certificationid], '*', MUST_EXIST);

        if (!$assignment->archived) {
            return $assignment;
        }

        $DB->set_field('tool_mucertify_assignment', 'archived', 0, ['id' => $assignment->id]);

        \tool_mucertify\event\assignment_restored::create_from_assignment($certification, $assignment)->trigger();

        \tool_muprog\local\source\mucertify::sync_certifications($assignment->certificationid, $assignment->userid);

        return $DB->get_record('tool_mucertify_assignment', ['id' => $assignment->id], '*', MUST_EXIST);
    }

    /**
     * Unassign user from a certification.
     *
     * @param stdClass $certification
     * @param stdClass $source
     * @param stdClass $assignment
     * @return void
     */
    final public static function assignment_delete(stdClass $certification, stdClass $source, stdClass $assignment): void {
        global $DB;

        if (static::get_type() !== $source->type || $certification->id != $assignment->certificationid || $certification->id != $source->certificationid) {
            throw new \coding_exception('invalid parameters');
        }
        $user = $DB->get_record('user', ['id' => $assignment->userid]);

        $trans = $DB->start_delegated_transaction();

        $periods = $DB->get_records('tool_mucertify_period', ['certificationid' => $assignment->certificationid, 'userid' => $assignment->userid]);
        foreach ($periods as $period) {
            if ($period->certificateissueid) {
                \tool_mucertify\local\certificate::revoke($period->id);
            }
        }

        $DB->delete_records(
            'tool_mucertify_period',
            ['certificationid' => $assignment->certificationid, 'userid' => $assignment->userid]
        );
        $DB->delete_records('tool_mucertify_assignment', ['id' => $assignment->id]);

        \tool_mucertify\event\assignment_deleted::create_from_assignment($certification, $assignment)->trigger();

        $trans->allow_commit();

        // Notification cannot be done in transaction due to MDL-86370.
        if ($user) {
            \tool_mucertify\local\notification\unassignment::notify_now($user, $certification, $source, $assignment);
        }
        \tool_mucertify\local\notification_manager::delete_assignment_notifications($assignment);
    }

    /**
     * Decode extra source settings.
     *
     * @param stdClass $source
     * @return stdClass
     */
    public static function decode_datajson(stdClass $source): stdClass {
        // Override if necessary.
        return $source;
    }

    /**
     * Encode extra source settings.
     * @param stdClass $formdata
     * @return string
     */
    public static function encode_datajson(stdClass $formdata): string {
        // Override if necessary.
        return \tool_mucertify\local\util::json_encode([]);
    }

    /**
     * Callback method for source updates.
     *
     * @param stdClass|null $oldsource
     * @param stdClass $data
     * @param stdClass|null $source
     * @return void
     */
    public static function after_update(?stdClass $oldsource, stdClass $data, ?stdClass $source): void {
        // Override if necessary.
    }

    /**
     * Returns class for editing of source settings in certification.
     *
     * @return string
     */
    public static function get_edit_form_class(): string {
        $type = static::get_type();
        $class = "tool_mucertify\\local\\form\source_{$type}_edit";
        if (!class_exists($class)) {
            throw new \coding_exception('source edit class not found, either override get_edit_form_class or add class: ' . $class);
        }
        return $class;
    }

    /**
     * Render details about this enabled source in a certification management ui.
     *
     * @param stdClass $certification
     * @param stdClass|null $source
     * @return string
     */
    public static function render_status_details(stdClass $certification, ?stdClass $source): string {
        return ($source ? get_string('active') : get_string('inactive'));
    }

    /**
     * Render basic status of the certification source.
     *
     * @param stdClass $certification
     * @param stdClass|null $source
     * @return string
     */
    public static function render_status(stdClass $certification, ?stdClass $source): string {
        global $OUTPUT;

        $type = static::get_type();

        if ($source && $source->type !== $type) {
            throw new \coding_exception('Invalid source type');
        }

        $result = static::render_status_details($certification, $source);

        $context = \context::instance_by_id($certification->contextid);
        if (has_capability('tool/mucertify:edit', $context) && static::is_update_allowed($certification)) {
            $label = get_string('updatesource', 'tool_mucertify', static::get_name());
            $editurl = new \moodle_url('/admin/tool/mucertify/management/certification_source_edit.php', ['certificationid' => $certification->id, 'type' => $type]);
            $editbutton = new \tool_mulib\output\ajax_form\icon($editurl, $label, 'i/settings');
            $editbutton->set_modal_title(static::get_name());
            $result .= ' ' . $OUTPUT->render($editbutton);
        }

        return $result;
    }


    /**
     * Returns the user who is responsible for assignment.
     *
     * Override if plugin knows anybody better than admin.
     *
     * @param stdClass $certification
     * @param stdClass $source
     * @param stdClass $assignment
     * @return stdClass user record
     */
    public static function get_assigner(stdClass $certification, stdClass $source, stdClass $assignment): stdClass {
        // NOTE: tweak this if there is a need for tenant specific sender.
        return get_admin();
    }
}
