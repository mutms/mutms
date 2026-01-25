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

use tool_mucertify\local\certification;

/**
 * Certification generator class.
 *
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_mucertify_generator extends component_generator_base {
    /**
     * @var int Framework count.
     */
    private $certificationcount = 0;

    /**
     * To be called from data reset code only,
     * do not use in tests.
     * @return void
     */
    public function reset() {
        parent::reset();
        $this->certificationcount = 0;
    }

    /**
     * Create certification.
     *
     * @param array|stdClass|null $record
     * @return stdClass
     */
    public function create_certification($record = null): stdClass {
        global $DB, $CFG;

        $this->certificationcount++;

        $record = (object)(array)$record;

        if (!isset($record->fullname)) {
            $record->fullname = 'Certification ' . $this->certificationcount;
        }
        if (!isset($record->idnumber)) {
            $record->idnumber = 'crt' . $this->certificationcount;
        }
        if (!isset($record->description)) {
            $record->description = '';
        }
        if (!isset($record->descriptionformat)) {
            $record->descriptionformat = FORMAT_HTML;
        }
        if (!isset($record->contextid)) {
            if (!empty($record->category)) {
                $category = $DB->get_record('course_categories', ['name' => $record->category]);
                if (!$category) {
                    $category = $DB->get_record('course_categories', ['idnumber' => $record->category], '*', MUST_EXIST);
                }
                $context = context_coursecat::instance($category->id);
                $record->contextid = $context->id;
            } else {
                $syscontext = \context_system::instance();
                $record->contextid = $syscontext->id;
            }
        }
        unset($record->category);

        if (!empty($record->program1)) {
            $program = $DB->get_record('tool_muprog_program', ['idnumber' => $record->program1], '*', MUST_EXIST);
            $record->programid1 = $program->id;
        }
        unset($record->program1);
        if (!empty($record->program2)) {
            $program = $DB->get_record('tool_muprog_program', ['idnumber' => $record->program2], '*', MUST_EXIST);
            $record->programid2 = $program->id;
        }
        unset($record->program2);

        $sources = [];
        if (!empty($record->sources)) {
            if (is_array($record->sources)) {
                $sources = $record->sources;
            }
            if (is_string($record->sources)) {
                foreach (explode(',', $record->sources) as $type) {
                    $type = trim($type);
                    if ($type === '') {
                        continue;
                    }
                    $sources[$type] = [];
                }
            }
        }
        unset($record->sources);

        $cohorts = [];
        if (!empty($record->cohortids)) {
            $cohorts = $record->cohortids;
        } else if (!empty($record->cohorts)) {
            $cohorts = $record->cohorts;
        }
        unset($record->cohorts);
        unset($record->cohortids);

        $image = null;
        if (!empty($record->image)) {
            $image = $record->image;
        }
        unset($record->image);

        $periodsdefauls = (array)certification::get_periods_defaults();
        $periods = [];
        foreach ((array)$record as $key => $value) {
            if (!str_starts_with($key, 'periods_')) {
                continue;
            }
            unset($record->$key);
            $k = preg_replace('/^periods_/', '', $key);
            if (!array_key_exists($k, $periodsdefauls)) {
                continue;
            }
            $periods[$k] = $value;
        }
        $certification = certification::create($record);

        if ($cohorts) {
            $cohortids = [];
            if (!is_array($cohorts)) {
                $cohorts = explode(',', $cohorts);
            }
            foreach ($cohorts as $cohort) {
                $cohort = trim($cohort);
                if (is_number($cohort)) {
                    $cohortids[] = $cohort;
                } else {
                    $record = $DB->get_record('cohort', ['name' => $cohort], '*', MUST_EXIST);
                    $cohortids[] = $record->id;
                }
            }
            certification::update_visibility((object)['id' => $certification->id, 'publicaccess' => $certification->publicaccess, 'cohortids' => $cohortids]);
        }

        if ($periods) {
            $periods['id'] = $certification->id;
            $certification = certification::update_settings((object)$periods);
        }

        foreach ($sources as $source => $data) {
            $data['enable'] = 1;
            $data['certificationid'] = $certification->id;
            $data['type'] = $source;
            $data = (object)$data;
            \tool_mucertify\local\source\base::update_source($data);
        }

        if ($image) {
            $imagefile = $CFG->dirroot . '/' . ltrim($image, '/');
            if (!file_exists($imagefile)) {
                throw new Exception('Certification image file does not exist');
            }
            $context = \context::instance_by_id($certification->contextid);
            $fs = get_file_storage();
            $filerecord = [
                'contextid' => $context->id,
                'component' => 'tool_mucertify',
                'filearea' => 'image',
                'itemid' => $certification->id,
                'filepath' => '/',
                'filename' => basename($image),
            ];
            $file = $fs->create_file_from_pathname($filerecord, $imagefile);
            $presenation = (array)json_decode($certification->presentationjson);
            $presenation['image'] = $file->get_filename();
            $DB->set_field(
                'tool_mucertify_certification',
                'presentationjson',
                \tool_mucertify\local\util::json_encode($presenation),
                ['id' => $certification->id]
            );
            $certification = $DB->get_record('tool_mucertify_certification', ['id' => $certification->id], '*', MUST_EXIST);
        }

        return $certification;
    }

    /**
     * Manually assign user to certification.
     *
     * @param mixed $record
     * @return \stdClass assignment record
     */
    public function create_certification_assignment($record): stdClass {
        global $DB;

        $record = (object)(array)$record;

        if (!empty($record->certificationid)) {
            $certification = $DB->get_record('tool_mucertify_certification', ['id' => $record->certificationid], '*', MUST_EXIST);
        } else {
            $certification = $DB->get_record('tool_mucertify_certification', ['fullname' => $record->certification], '*', MUST_EXIST);
        }

        if (!empty($record->userid)) {
            $user = $DB->get_record('user', ['id' => $record->userid], '*', MUST_EXIST);
        } else {
            $user = $DB->get_record('user', ['username' => $record->user], '*', MUST_EXIST);
        }

        $source = $DB->get_record('tool_mucertify_source', ['type' => 'manual', 'certificationid' => $certification->id]);
        if (!$source) {
            $data = [];
            $data['enable'] = 1;
            $data['certificationid'] = $certification->id;
            $data['type'] = 'manual';
            $data = (object)$data;
            $source = \tool_mucertify\local\source\manual::update_source($data);
        }

        $overridable = ['timecreated', 'timecertifiedtemp', 'noperiod', 'timewindowstart', 'timewindowdue', 'timewindowend', 'timefrom', 'timeuntil'];
        $dateoverrides = [];
        foreach ($overridable as $override) {
            if (!empty($record->{$override}) && is_number($record->{$override})) {
                $dateoverrides[$override] = $record->{$override};
            }
        }

        $assignmentids = \tool_mucertify\local\source\manual::assign_users($certification->id, $source->id, [$user->id], $dateoverrides);

        // Save custom fields.
        foreach ($assignmentids as $assignmentid) {
            $data = (object)(array)$record;
            /** @var \tool_mucertify\customfield\assignment_handler $handler */
            $handler = \tool_mucertify\customfield\assignment_handler::create();
            $data->id = $assignmentid;
            $handler->instance_form_save($data);
        }

        return $DB->get_record('tool_mucertify_assignment', ['certificationid' => $certification->id, 'userid' => $user->id], '*', MUST_EXIST);
    }
    /**
     * Add certification notification.
     *
     * @param mixed $record
     * @return \stdClass notification record
     */
    public function create_certifiction_notification($record): stdClass {
        global $DB;

        $record = (object)(array)$record;

        if (!empty($record->certificationid)) {
            $certification = $DB->get_record('tool_mucertify_certification', ['id' => $record->certificationid], '*', MUST_EXIST);
        } else {
            $certification = $DB->get_record('tool_mucertify_certification', ['fullname' => $record->certification], '*', MUST_EXIST);
        }

        $alltypes = \tool_mucertify\local\notification_manager::get_all_types();
        if (!$record->notificationtype || !isset($alltypes[$record->notificationtype])) {
            throw new coding_exception('Invalid notification type');
        }

        $data = [
            'component' => 'tool_mucertify',
            'notificationtype' => $record->notificationtype,
            'instanceid' => $certification->id,
            'supervisorframeworkid' => $record->supervisorframeworkid ?? null,
            'enabled' => '1',
        ];
        return \tool_mulib\local\notification\util::notification_create($data);
    }
}
