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

use tool_muprog\local\allocation;
use tool_mulib\output\header_actions;
use stdClass;

/**
 * Program allocation source abstraction.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
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
        return get_string('source_' . $type, 'tool_muprog');
    }

    /**
     * Can a new source of this type be added to programs?
     *
     * NOTE: Existing enabled sources in programs cannot be deleted/hidden
     * if there are any allocated users to program.
     *
     * @param stdClass $program
     * @return bool
     */
    public static function is_new_allowed(\stdClass $program): bool {
        $type = static::get_type();
        return (bool)get_config('tool_muprog', 'source_' . $type . '_allownew');
    }

    /**
     * Can existing source of this type be updated or deleted to programs?
     *
     * NOTE: Existing enabled sources in programs cannot be deleted/hidden
     * if there are any allocated users to program.
     *
     * @param stdClass $program
     * @return bool
     */
    public static function is_update_allowed(stdClass $program): bool {
        return true;
    }

    /**
     * Make sure users are allocated properly.
     *
     * This is expected to be called from cron and when
     * program allocation settings are updated.
     *
     * @param int|null $programid
     * @param int|null $userid
     * @return bool true if anything updated
     */
    public static function fix_allocations(?int $programid, ?int $userid): bool {
        return false;
    }

    /**
     * Return extra tab for managing the source data in program.
     *
     * @param \tool_muprog\navigation\views\program_secondary $secondary
     * @param stdClass $program
     */
    public static function add_program_secondary_tabs(\tool_muprog\navigation\views\program_secondary $secondary, stdClass $program): void {
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
        if (
            $program->id != $source->programid
            || $program->id != $allocation->programid
            || $source->id != $allocation->sourceid
        ) {
            throw new \coding_exception('invalid parameters');
        }
        if ($program->archived) {
            return false;
        }
        if ($allocation->archived) {
            return false;
        }
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
        if (
            $program->id != $source->programid
            || $program->id != $allocation->programid
            || $source->id != $allocation->sourceid
        ) {
            throw new \coding_exception('invalid parameters');
        }
        if ($program->archived) {
            return false;
        }
        if ($allocation->archived) {
            return false;
        }
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
    public static function is_allocation_restore_possible(stdClass $program, stdClass $source, stdClass $allocation): bool {
        if (
            $program->id != $source->programid
            || $program->id != $allocation->programid
            || $source->id != $allocation->sourceid
        ) {
            throw new \coding_exception('invalid parameters');
        }
        if ($program->archived) {
            return false;
        }
        if (!$allocation->archived) {
            return false;
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
        if (
            $program->id != $source->programid
            || $program->id != $allocation->programid
            || $source->id != $allocation->sourceid
        ) {
            throw new \coding_exception('invalid parameters');
        }
        if ($program->archived) {
            return false;
        }
        if (!$allocation->archived) {
            return false;
        }
        return true;
    }

    /**
     * Source related extra menu items for program allocation tab.
     *
     * @param header_actions $actions
     * @param stdClass $program
     * @param stdClass $source source record
     */
    public static function add_management_program_users_actions(header_actions $actions, stdClass $program, stdClass $source): void {
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
        return [];
    }

    /**
     * Are the date overrides valid for a new program allocation in near future?
     *
     * NOTE: This is intended for validation of external date such as upload of allocations.
     *
     * @param stdClass $program
     * @param array $dateoverrides
     * @return bool
     */
    final public static function is_valid_dateoverrides(stdClass $program, array $dateoverrides): bool {
        $timeallocated = time();

        $timestart = empty($dateoverrides['timestart']) ?
            allocation::get_default_timestart($program, $timeallocated) : $dateoverrides['timestart'];
        $timedue = empty($dateoverrides['timedue']) ?
            allocation::get_default_timedue($program, $timeallocated, $timestart) : $dateoverrides['timedue'];
        $timeend = empty($dateoverrides['timeend']) ?
            allocation::get_default_timeend($program, $timeallocated, $timestart) : $dateoverrides['timeend'];

        $errors = allocation::validate_allocation_dates($timestart, $timedue, $timeend);
        return empty($errors);
    }

    /**
     * Allocate user to program.
     *
     * @param stdClass $program
     * @param stdClass $source
     * @param int $userid
     * @param array $sourcedata
     * @param array $dateoverrides
     * @param int|null $sourceinstanceid
     * @return stdClass user allocation record
     */
    final protected static function allocation_create(
        \stdClass $program,
        \stdClass $source,
        int $userid,
        array $sourcedata,
        array $dateoverrides = [],
        ?int $sourceinstanceid = null
    ): \stdClass {
        global $DB;

        if ($userid <= 0 || isguestuser($userid)) {
            throw new \coding_exception('Only real users can be allocated to programs');
        }

        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0, 'confirmed' => 1], '*', MUST_EXIST);

        $now = time();

        $record = new \stdClass();
        $record->programid = $program->id;
        $record->userid = $userid;
        $record->sourceid = $source->id;
        $record->archived = 0;
        $record->sourcedatajson = \tool_muprog\local\util::json_encode($sourcedata);
        $record->sourceinstanceid = $sourceinstanceid;
        $record->timeallocated = empty($dateoverrides['timeallocated']) ? $now : $dateoverrides['timeallocated'];
        $record->timecreated = $now;

        $record->timestart = empty($dateoverrides['timestart']) ?
            allocation::get_default_timestart($program, $record->timeallocated) : $dateoverrides['timestart'];
        $record->timedue = empty($dateoverrides['timedue']) ?
            allocation::get_default_timedue($program, $record->timeallocated, $record->timestart) : $dateoverrides['timedue'];
        $record->timeend = empty($dateoverrides['timeend']) ?
            allocation::get_default_timeend($program, $record->timeallocated, $record->timestart) : $dateoverrides['timeend'];

        // NOTE: do not validate dates here, the reason is that the defaults validity may change over time.
        if ($record->timeend && $record->timeend <= $record->timestart) {
            $record->timeend = $record->timestart + 1;
        }
        if ($record->timedue && $record->timedue <= $record->timestart) {
            $record->timedue = $record->timestart + 1;
        }
        if ($record->timedue && $record->timeend && $record->timedue > $record->timeend) {
            $record->timedue = $record->timeend;
        }

        $record->id = $DB->insert_record('tool_muprog_allocation', $record);
        $allocation = $DB->get_record('tool_muprog_allocation', ['id' => $record->id], '*', MUST_EXIST);

        \tool_muprog\event\allocation_created::create_from_allocation($allocation, $program)->trigger();

        \tool_muprog\local\calendar::fix_allocation_events($allocation, $program);

        \tool_muprog\local\notification\allocation::notify_now($user, $program, $source, $allocation);

        return $allocation;
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
        return \tool_muprog\local\util::json_encode([]);
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
     * Returns class for editing of source settings in program.
     *
     * @return string
     */
    public static function get_edit_form_class(): string {
        $type = static::get_type();
        $class = "tool_muprog\\local\\form\source_{$type}_edit";
        if (!class_exists($class)) {
            throw new \coding_exception('source edit class not found, either override get_edit_form_class or add class: ' . $class);
        }
        return $class;
    }

    /**
     * Can settings of this source be imported to other program?
     *
     * @param stdClass $fromprogram
     * @param stdClass $targetprogram
     * @return bool
     */
    public static function is_import_allowed(stdClass $fromprogram, stdClass $targetprogram): bool {
        return false;
    }

    /**
     * Import source data from one program to another.
     *
     * @param int $fromprogramid
     * @param int $targetprogramid
     * @return stdClass created or updated source record
     */
    public static function import_source_data(int $fromprogramid, int $targetprogramid): stdClass {
        global $DB;

        $fromsource = $DB->get_record(
            'tool_muprog_source',
            ['programid' => $fromprogramid, 'type' => static::get_type()],
            '*',
            MUST_EXIST
        );
        $targetsource = $DB->get_record(
            'tool_muprog_source',
            ['programid' => $targetprogramid, 'type' => static::get_type()]
        );

        if ($targetsource) {
            $fromsource->id = $targetsource->id;
            $fromsource->programid = $targetprogramid;
            $DB->update_record('tool_muprog_source', $fromsource);
        } else {
            unset($fromsource->id);
            $fromsource->programid = $targetprogramid;
            $DB->insert_record('tool_muprog_source', $fromsource);
        }

        return $DB->get_record(
            'tool_muprog_source',
            ['programid' => $targetprogramid, 'type' => static::get_type()],
            '*',
            MUST_EXIST
        );
    }

    /**
     * Render details about this enabled source in a programs management ui.
     *
     * @param stdClass $program
     * @param stdClass|null $source
     * @return string
     */
    public static function render_status_details(stdClass $program, ?stdClass $source): string {
        return ($source ? get_string('active') : get_string('inactive'));
    }

    /**
     * Render basic status of the program source.
     *
     * @param stdClass $program
     * @param stdClass|null $source
     * @return string
     */
    public static function render_status(stdClass $program, ?stdClass $source): string {
        global $OUTPUT;

        $type = static::get_type();

        if ($source && $source->type !== $type) {
            throw new \coding_exception('Invalid source type');
        }

        $result = static::render_status_details($program, $source);

        $context = \context::instance_by_id($program->contextid);
        if (has_capability('tool/muprog:edit', $context) && static::is_update_allowed($program)) {
            $label = get_string('updatesource', 'tool_muprog', static::get_name());
            $editurl = new \moodle_url('/admin/tool/muprog/management/program_source_edit.php', ['programid' => $program->id, 'type' => $type]);
            $editbutton = new \tool_mulib\output\ajax_form\icon($editurl, $label, 'i/settings');
            $editbutton->set_modal_title(static::get_name());
            $result .= ' ' . $OUTPUT->render($editbutton);
        }

        return $result;
    }

    /**
     * Render allocation source information.
     *
     * @param stdClass $program
     * @param stdClass $source
     * @param stdClass $allocation
     * @return string HTML fragment
     */
    public static function render_allocation_source(stdClass $program, stdClass $source, stdClass $allocation): string {
        $type = static::get_type();

        if ($source && $source->type !== $type) {
            throw new \coding_exception('Invalid source type');
        }

        return static::get_name();
    }

    /**
     * Update source details.
     *
     * @param stdClass $data
     * @return stdClass|null allocation source
     */
    final public static function update_source(stdClass $data): ?stdClass {
        global $DB;

        /** @var base[] $sourceclasses */
        $sourceclasses = \tool_muprog\local\allocation::get_source_classes();
        if (!isset($sourceclasses[$data->type])) {
            throw new \coding_exception('Invalid source type');
        }
        $sourcetype = $data->type;
        $sourceclass = $sourceclasses[$sourcetype];

        $program = $DB->get_record('tool_muprog_program', ['id' => $data->programid], '*', MUST_EXIST);
        $source = $DB->get_record('tool_muprog_source', ['type' => $sourcetype, 'programid' => $program->id]);
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
                $DB->update_record('tool_muprog_source', $source);
            } else {
                $source = new \stdClass();
                $source->programid = $data->programid;
                $source->type = $sourcetype;
                $source->datajson = $sourceclass::encode_datajson($data);
                $source->auxint1 = $data->auxint1 ?? null;
                $source->auxint2 = $data->auxint2 ?? null;
                $source->auxint3 = $data->auxint3 ?? null;
                $source->id = $DB->insert_record('tool_muprog_source', $source);
            }
            $source = $DB->get_record('tool_muprog_source', ['id' => $source->id], '*', MUST_EXIST);
        } else {
            if ($source) {
                if ($DB->record_exists('tool_muprog_allocation', ['sourceid' => $source->id])) {
                    throw new \coding_exception('Cannot delete source with allocations');
                }
                $DB->delete_records('tool_muprog_request', ['sourceid' => $source->id]);
                $DB->delete_records('tool_muprog_src_cohort', ['sourceid' => $source->id]);
                $DB->delete_records('tool_muprog_source', ['id' => $source->id]);
                $source = null;
            }
        }
        $sourceclass::after_update($oldsource, $data, $source);

        \tool_muprog\local\allocation::fix_allocation_sources($program->id, null);
        \tool_muprog\local\allocation::fix_enrol_instances($program->id);
        \tool_muprog\local\allocation::fix_user_enrolments($program->id, null);

        return $source;
    }

    /**
     * Returns the user who is responsible for allocation.
     *
     * Override if plugin knows anybody better than admin.
     *
     * @param stdClass $program
     * @param stdClass $source
     * @param stdClass $allocation
     * @return stdClass user record
     */
    public static function get_allocator(stdClass $program, stdClass $source, stdClass $allocation): stdClass {
        // NOTE: tweak this if there is a need for tenant specific sender.
        return get_admin();
    }

    /**
     * Manually update user allocation data including program completion.
     *
     * @param stdClass $data
     * @return stdClass
     */
    final public static function allocation_update(stdClass $data): stdClass {
        global $DB;

        $allocation = (object)(array)$data;

        $record = $DB->get_record('tool_muprog_allocation', ['id' => $allocation->id], '*', MUST_EXIST);
        $program = $DB->get_record('tool_muprog_program', ['id' => $record->programid], '*', MUST_EXIST);
        $oldrecord = clone($record);

        unset($allocation->userid);
        unset($allocation->sourceid);
        foreach ((array)$record as $k => $v) {
            if (!property_exists($allocation, $k)) {
                $allocation->$k = $v;
            }
        }

        $trans = $DB->start_delegated_transaction();

        $record->timeallocated = $allocation->timeallocated;
        $record->timestart = $allocation->timestart;
        $record->timedue = $allocation->timedue;
        if (!$record->timedue) {
            $record->timedue = null;
        } else if ($record->timedue <= $record->timestart) {
            throw new \coding_exception('invalid due date');
        }
        $record->timeend = $allocation->timeend;
        if (!$record->timeend) {
            $record->timeend = null;
        } else if ($record->timeend <= $record->timestart) {
            throw new \coding_exception('invalid end date');
        }
        if ($record->timedue && $record->timeend && $record->timedue > $record->timeend) {
            throw new \coding_exception('invalid due date');
        }
        $record->timecompleted = $allocation->timecompleted;
        if (!$record->timecompleted) {
            $record->timecompleted = null;
        }

        // Do not change archived flag here!
        if (isset($allocation->archived) && $allocation->archived != $record->archived) {
            debugging('Use base::allocation_archive() and base::allocation_restore() to change archived flag', DEBUG_DEVELOPER);
        }

        $DB->update_record('tool_muprog_allocation', $record);

        $handler = \tool_muprog\customfield\allocation_handler::create();
        $handler->instance_form_save($data);

        $allocation = $DB->get_record('tool_muprog_allocation', ['id' => $allocation->id], '*', MUST_EXIST);

        \tool_muprog\event\allocation_updated::create_from_allocation($allocation, $program)->trigger();

        $trans->allow_commit();

        allocation::fix_allocation_sources($allocation->programid, $allocation->userid);
        allocation::fix_user_enrolments($allocation->programid, $allocation->userid);
        \tool_muprog\local\calendar::fix_allocation_events($allocation, $program);
        $allocation = $DB->get_record('tool_muprog_allocation', ['id' => $allocation->id], '*', MUST_EXIST);

        \tool_muprog\local\notification_manager::trigger_notifications($allocation->programid, $allocation->userid);

        if ($oldrecord->timecompleted === null && $allocation->timecompleted !== null) {
            $source = $DB->get_record('tool_muprog_source', ['id' => $allocation->sourceid], '*', MUST_EXIST);
            $user = $DB->get_record('user', ['id' => $allocation->userid], '*', MUST_EXIST);
            \tool_muprog\event\allocation_completed::create_from_allocation($allocation, $program)->trigger();
            \tool_muprog\local\notification\completion::notify_now($user, $program, $source, $allocation);
            \tool_muprog\local\calendar::delete_allocation_events($allocation->id);
        }

        return $allocation;
    }

    /**
     * Archive allocation.
     *
     * @param int $allocationid
     * @return stdClass allocation record
     */
    final public static function allocation_archive(int $allocationid): stdClass {
        global $DB;

        $allocation = $DB->get_record('tool_muprog_allocation', ['id' => $allocationid], '*', MUST_EXIST);
        $program = $DB->get_record('tool_muprog_program', ['id' => $allocation->programid], '*', MUST_EXIST);

        if ($allocation->archived) {
            return $allocation;
        }

        $DB->set_field('tool_muprog_allocation', 'archived', 1, ['id' => $allocation->id]);
        $allocation = $DB->get_record('tool_muprog_allocation', ['id' => $allocation->id], '*', MUST_EXIST);

        \tool_muprog\event\allocation_archived::create_from_allocation($allocation, $program)->trigger();

        allocation::fix_allocation_sources($allocation->programid, $allocation->userid);
        allocation::fix_user_enrolments($allocation->programid, $allocation->userid);
        \tool_muprog\local\calendar::fix_allocation_events($allocation, $program);

        return $allocation;
    }

    /**
     * Restore allocation.
     *
     * @param int $allocationid
     * @return stdClass allocation record
     */
    final public static function allocation_restore(int $allocationid): stdClass {
        global $DB;

        $allocation = $DB->get_record('tool_muprog_allocation', ['id' => $allocationid], '*', MUST_EXIST);
        $program = $DB->get_record('tool_muprog_program', ['id' => $allocation->programid], '*', MUST_EXIST);

        if (!$allocation->archived) {
            return $allocation;
        }

        $DB->set_field('tool_muprog_allocation', 'archived', 0, ['id' => $allocation->id]);
        $allocation = $DB->get_record('tool_muprog_allocation', ['id' => $allocation->id], '*', MUST_EXIST);

        \tool_muprog\event\allocation_restored::create_from_allocation($allocation, $program)->trigger();

        allocation::fix_allocation_sources($allocation->programid, $allocation->userid);
        allocation::fix_user_enrolments($allocation->programid, $allocation->userid);
        \tool_muprog\local\calendar::fix_allocation_events($allocation, $program);

        return $allocation;
    }

    /**
     * Deallocate user from a program.
     *
     * @param stdClass $program
     * @param stdClass $source
     * @param stdClass $allocation
     * @param bool $skipnotify
     * @return void
     */
    final public static function allocation_delete(stdClass $program, stdClass $source, stdClass $allocation, bool $skipnotify = false): void {
        global $DB;

        if (static::get_type() !== $source->type || $program->id != $allocation->programid || $program->id != $source->programid) {
            throw new \coding_exception('invalid parameters');
        }
        $user = $DB->get_record('user', ['id' => $allocation->userid]);

        $trans = $DB->start_delegated_transaction();

        self::purge_allocation($allocation->id);

        \tool_muprog\event\allocation_deleted::create_from_allocation($allocation, $program)->trigger();

        $trans->allow_commit();

        \tool_muprog\local\allocation::fix_allocation_sources($program->id, $allocation->userid);
        \tool_muprog\local\allocation::fix_user_enrolments($program->id, $allocation->userid);
        \tool_muprog\local\calendar::delete_allocation_events($allocation->id);

        // Notification cannot be done in transaction due to MDL-86370.
        if ($user && !$skipnotify) {
            \tool_muprog\local\notification\deallocation::notify_now($user, $program, $source, $allocation);
        }
        \tool_muprog\local\notification_manager::delete_allocation_notifications($allocation);
    }

    /**
     * Purge all user allocation data.
     *
     * @param int $allocationid
     * @return void
     */
    protected static function purge_allocation(int $allocationid): void {
        global $DB;

        $allocation = $DB->get_record('tool_muprog_allocation', ['id' => $allocationid]);
        if (!$allocation) {
            return;
        }

        $issues = $DB->get_records('tool_muprog_cert_issue', ['allocationid' => $allocation->id]);
        foreach ($issues as $issue) {
            $DB->set_field('tool_muprog_cert_issue', 'allocationid', null, ['id' => $issue->id]);
        }
        if (\tool_muprog\local\certificate::is_available()) {
            foreach ($issues as $issue) {
                $DB->set_field('tool_certificate_issues', 'archived', 1, ['id' => $issue->issueid]);
            }
        }
        unset($issues);

        $items = $DB->get_records('tool_muprog_item', ['programid' => $allocation->programid]);
        foreach ($items as $item) {
            $DB->delete_records('tool_muprog_evidence', ['itemid' => $item->id, 'userid' => $allocation->userid]);
            $DB->delete_records('tool_muprog_completion', ['itemid' => $item->id, 'allocationid' => $allocation->id]);
        }
        unset($items);
        $DB->delete_records('tool_muprog_allocation', ['id' => $allocation->id]);
    }
}
