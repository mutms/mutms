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
 * Program generator.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_muprog_generator extends component_generator_base {
    /**
     * @var int keeps track of how many programs have been created.
     */
    protected $programcount = 0;

    /**
     * To be called from data reset code only,
     * do not use in tests.
     * @return void
     */
    public function reset() {
        $this->programcount = 0;
        parent::reset();
    }

    /**
     * Create a new program.
     *
     * @param mixed $record
     * @return stdClass program record
     */
    public function create_program($record = null): stdClass {
        global $DB, $CFG;
        require_once("$CFG->libdir/filelib.php");

        $record = (object)(array)$record;

        $this->programcount++;

        if (!isset($record->fullname)) {
            $record->fullname = 'Program ' . $this->programcount;
        }
        if (!isset($record->idnumber)) {
            $record->idnumber = 'prg' . $this->programcount;
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

        $program = tool_muprog\local\program::create($record);

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
            \tool_muprog\local\program::update_visibility((object)['id' => $program->id, 'publicaccess' => $program->publicaccess, 'cohortids' => $cohortids]);
        }

        foreach ($sources as $source => $data) {
            $data['enable'] = 1;
            $data['programid'] = $program->id;
            $data['type'] = $source;
            $data = (object)$data;
            \tool_muprog\local\source\base::update_source($data);
        }

        if ($image) {
            $imagefile = $CFG->dirroot . '/' . ltrim($image, '/');
            if (!file_exists($imagefile)) {
                throw new Exception('Program image file does not exist');
            }
            $context = \context::instance_by_id($program->contextid);
            $fs = get_file_storage();
            $filerecord = [
                'contextid' => $context->id,
                'component' => 'tool_muprog',
                'filearea' => 'image',
                'itemid' => $program->id,
                'filepath' => '/',
                'filename' => basename($image),
            ];
            $file = $fs->create_file_from_pathname($filerecord, $imagefile);
            $presenation = (array)json_decode($program->presentationjson);
            $presenation['image'] = $file->get_filename();
            $DB->set_field(
                'tool_muprog_program',
                'presentationjson',
                \tool_muprog\local\util::json_encode($presenation),
                ['id' => $program->id]
            );
            $program = $DB->get_record('tool_muprog_program', ['id' => $program->id], '*', MUST_EXIST);
        }

        return $program;
    }

    /**
     * Add program item.
     *
     * @param mixed $record
     * @return \tool_muprog\local\content\item
     */
    public function create_program_item($record): \tool_muprog\local\content\item {
        global $DB;

        $record = (object)(array)$record;

        if (!empty($record->programid)) {
            $program = $DB->get_record('tool_muprog_program', ['id' => $record->programid], '*', MUST_EXIST);
        } else {
            $program = $DB->get_record('tool_muprog_program', ['fullname' => $record->program], '*', MUST_EXIST);
        }
        $top = \tool_muprog\local\program::load_content($program->id);
        if (!empty($record->parent)) {
            $parentrecord = $DB->get_record('tool_muprog_item', ['programid' => $program->id, 'fullname' => $record->parent], '*', MUST_EXIST);
            $parent = $top->find_item($parentrecord->id);
        } else {
            $parent = $top;
        }

        if (!empty($record->courseid) || !empty($record->course)) {
            if (!empty($record->courseid)) {
                $course = $DB->get_record('course', ['id' => $record->courseid], '*', MUST_EXIST);
            } else {
                $course = $DB->get_record('course', ['fullname' => $record->course], '*', MUST_EXIST);
            }
            return $top->append_course($parent, $course->id);
        } else if (!empty($record->trainingid) || !empty($record->training)) {
            if (!empty($record->trainingid)) {
                $framework = $DB->get_record('tool_mutrain_framework', ['id' => $record->trainingid], '*', MUST_EXIST);
            } else {
                $framework = $DB->get_record('tool_mutrain_framework', ['name' => $record->training], '*', MUST_EXIST);
            }
            return $top->append_training($parent, $framework->id);
        } else {
            if (!empty($record->sequencetype)) {
                $types = \tool_muprog\local\content\set::get_sequencetype_types();
                if (isset($types[$record->sequencetype])) {
                    $sequencetype = $record->sequencetype;
                } else {
                    $types = array_flip($types);
                    $sequencetype = $types[$record->sequencetype];
                }
            } else {
                $sequencetype = \tool_muprog\local\content\set::SEQUENCE_TYPE_ALLINANYORDER;
            }
            if (!empty($record->minprerequisites)) {
                $minprerequisites = $record->minprerequisites;
            } else {
                $minprerequisites = 1;
            }
            return $top->append_set($parent, ['fullname' => $record->fullname, 'sequencetype' => $sequencetype, 'minprerequisites' => $minprerequisites]);
        }
    }

    /**
     * Manually allocate user to program.
     *
     * @param mixed $record
     * @return \stdClass allocation record
     */
    public function create_program_allocation($record): stdClass {
        global $DB;

        $record = (object)(array)$record;

        if (!empty($record->programid)) {
            $program = $DB->get_record('tool_muprog_program', ['id' => $record->programid], '*', MUST_EXIST);
        } else {
            $program = $DB->get_record('tool_muprog_program', ['fullname' => $record->program], '*', MUST_EXIST);
        }

        if (!empty($record->userid)) {
            $user = $DB->get_record('user', ['id' => $record->userid], '*', MUST_EXIST);
        } else {
            $user = $DB->get_record('user', ['username' => $record->user], '*', MUST_EXIST);
        }

        $source = $DB->get_record('tool_muprog_source', ['type' => 'manual', 'programid' => $program->id]);
        if (!$source) {
            $data = [];
            $data['enable'] = 1;
            $data['programid'] = $program->id;
            $data['type'] = 'manual';
            $data = (object)$data;
            $source = \tool_muprog\local\source\manual::update_source($data);
        }

        $overridable = ['timeallocated', 'timestart', 'timedue', 'timeend'];
        $dateoverrides = [];
        foreach ($overridable as $override) {
            if (!empty($record->{$override}) && is_number($record->{$override})) {
                $dateoverrides[$override] = $record->{$override};
            }
        }

        $allocationids = \tool_muprog\local\source\manual::allocate_users($program->id, $source->id, [$user->id], $dateoverrides);
        foreach ($allocationids as $allocationid) {
            $data = (object)(array)$record;
            /** @var \tool_muprog\customfield\allocation_handler $handler */
            $handler = \tool_muprog\customfield\allocation_handler::create();
            $data->id = $allocationid;
            $handler->instance_form_save($data);
        }

        return $DB->get_record('tool_muprog_allocation', ['programid' => $program->id, 'userid' => $user->id], '*', MUST_EXIST);
    }

    /**
     * Add program notification.
     *
     * @param mixed $record
     * @return \stdClass notification record
     */
    public function create_program_notification($record): stdClass {
        global $DB;

        $record = (object)(array)$record;

        if (!empty($record->programid)) {
            $program = $DB->get_record('tool_muprog_program', ['id' => $record->programid], '*', MUST_EXIST);
        } else {
            $program = $DB->get_record('tool_muprog_program', ['fullname' => $record->program], '*', MUST_EXIST);
        }

        $alltypes = \tool_muprog\local\notification_manager::get_all_types();
        if (!$record->notificationtype || !isset($alltypes[$record->notificationtype])) {
            throw new coding_exception('Invalid notification type');
        }

        $data = [
            'component' => 'tool_muprog',
            'notificationtype' => $record->notificationtype,
            'instanceid' => $program->id,
            'enabled' => '1',
        ];
        if (!empty($record->custom)) {
            $data['custom'] = 1;
            $data['body'] = $record->body ?? '';
            $data['subject'] = $record->subject ?? '';
        }
        return \tool_mulib\local\notification\util::notification_create($data);
    }
}
