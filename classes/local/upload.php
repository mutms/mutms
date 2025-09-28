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

namespace tool_muprog\local;

use stdClass;

/**
 * Programs upload helper.
 *
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class upload {
    /**
     * Create programs using upload files.
     *
     * @param stdClass $data
     * @param array $rawprograms
     * @return void
     */
    public static function process(stdClass $data, array $rawprograms): void {
        foreach ($rawprograms as $rawprogram) {
            if ($rawprogram->errors) {
                continue;
            }

            $record = (object)[
                'fullname' => $rawprogram->fullname,
                'idnumber' => $rawprogram->idnumber,
                'contextid' => ($data->usecategory ? $rawprogram->contextid : $data->contextid),
                'description' => $rawprogram->description ?? '',
                'descriptionformat' => $rawprogram->descriptionformat ?? FORMAT_HTML,
                'archived' => 0,
                'publicaccess' => $rawprogram->publicaccess ?? 0,
                'creategroups' => $rawprogram->creategroups ?? 0,
                'timeallocationstart' => self::parse_date($rawprogram->allocationstart ?? null),
                'timeallocationend' => self::parse_date($rawprogram->allocationend ?? null),
                'startdate' => $rawprogram->startdate ?? null,
                'duedate' => $rawprogram->duedate ?? null,
                'enddate' => $rawprogram->enddate ?? null,
            ];
            if (isset($rawprogram->startdate)) {
                $record->startdate = $rawprogram->startdate;
                if (isset($record->startdate->date)) {
                    $record->startdate->date = strtotime($record->startdate->date);
                }
            }
            if (isset($rawprogram->duedate)) {
                $record->duedate = $rawprogram->duedate;
                if (isset($record->duedate->date)) {
                    $record->duedate->date = strtotime($record->duedate->date);
                }
            }
            if (isset($rawprogram->enddate)) {
                $record->enddate = $rawprogram->enddate;
                if (isset($record->enddate->date)) {
                    $record->enddate->date = strtotime($record->enddate->date);
                }
            }

            $cfhandler = \tool_muprog\customfield\program_handler::create();
            foreach ($cfhandler->get_fields() as $cfield) {
                $cfname = 'customfield_' . $cfield->get('shortname');
                if (isset($rawprogram->$cfname)) {
                    // Custom fields API is not designed to allow imports,
                    // this may break easily - bad luck!
                    $record->$cfname = $rawprogram->$cfname;
                }
            }

            $program = program::create($record);

            if (isset($rawprogram->contents) && $rawprogram->contents->itemtype === 'set') {
                $top = program::load_content($program->id);
                $topitem = $rawprogram->contents;
                $update = [
                    'id' => $top->get_id(),
                    'programid' => $top->get_programid(),
                    'completiondelay' => $topitem->completiondelay ?? 0,
                    'sequencetype' => $topitem->sequencetype ?? 'allinanyorder',
                    'minpoints' => $topitem->minpoints ?? 1,
                    'minprerequisites' => $topitem->minprerequisites ?? 1,
                ];
                $top->update_set($top, $update);

                $addfunction = function (
                    \tool_muprog\local\content\top $top,
                    \tool_muprog\local\content\set $parent,
                    stdClass $item
                ) use (&$addfunction): void {

                    if ($item->itemtype === 'course') {
                        $data = [
                            'points' => $item->points ?? 1,
                            'completiondelay' => $item->completiondelay ?? 0,
                        ];
                        $top->append_course($parent, $item->courseid, $data);
                    } else if ($item->itemtype === 'training') {
                        $data = [
                            'points' => $item->points ?? 1,
                            'completiondelay' => $item->completiondelay ?? 0,
                        ];
                        $top->append_training($parent, $item->trainingid, $data);
                    } else if ($item->itemtype === 'set') {
                        $data = [
                            'points' => $item->points ?? 1,
                            'completiondelay' => $item->completiondelay ?? 0,
                            'sequencetype' => $item->sequencetype ?? 'allinanyorder',
                            'fullname' => clean_text($item->setname),
                            'minpoints' => $item->minpoints ?? 1,
                            'minprerequisites' => $item->minprerequisites ?? 1,
                        ];
                        $set = $top->append_set($parent, $data);
                        if (!empty($item->items)) {
                            foreach ($item->items as $child) {
                                $addfunction($top, $set, (object)$child);
                            }
                        }
                    }
                };
                foreach ($topitem->items as $item) {
                    $addfunction($top, $top, $item);
                }
            }

            if (isset($rawprogram->sources) && is_array($rawprogram->sources)) {
                foreach ($rawprogram->sources as $source) {
                    $record = (object)[
                        'programid' => $program->id,
                        'type' => $source->sourcetype,
                        'enable' => 1,
                    ];
                    if ($record->type === 'approval') {
                        $record->approval_allowrequest = $source->data->allowrequest ?? 0;
                    } else if ($record->type === 'selfallocation') {
                        $record->selfallocation_maxusers = $source->data->maxusers ?? '';
                        $record->selfallocation_key = $source->data->key ?? '';
                        $record->selfallocation_allowsignup = $source->data->allowsignup ?? 0;
                    }
                    \tool_muprog\local\source\base::update_source($record);
                }
            }
        }
    }

    /**
     * Preview upload date contents.
     *
     * @param array $filedata
     * @return string
     */
    public static function preview(array $filedata): string {
        // NOTE: let's not localise the column names for now.
        $columns = [
            get_string('upload_status', 'tool_muprog'),
            'idnumber',
            'fullname',
            'category',
            'description',
            'publicaccess',
            'contents',
            'creategroups',
            'allocationstart',
            'allocationend',
            'startdate',
            'duedate',
            'enddate',
            'sources',
        ];
        $cfhandler = \tool_muprog\customfield\program_handler::create();
        foreach ($cfhandler->get_fields() as $cfield) {
            $columns[] = 'customfield_' . $cfield->get('shortname');
        }

        $yesno = [
            '0' => get_string('no'),
            '1' => get_string('yes'),
        ];
        $parsedate = function ($value): string {
            if (!$value) {
                return '';
            }
            $date = self::parse_date($value);
            return export::format_date($date);
        };

        $formaterror = function ($value): string {
            if (is_array($value)) {
                $value = implode('<br />', $value);
            }
            return '<span class="alert-danger">' . $value . '</span>';
        };

        $data = [];
        foreach ($filedata as $program) {
            $program->errors = (array)$program->errors;
            if ($program->errors) {
                $status = '<div class="badge bg-danger">' . get_string('upload_status_invalid', 'tool_muprog') . '</div>';
            } else {
                $status = '<div class="badge bg-info text-dark">' . get_string('ok') . '</div>';
            }

            $idnumber = s($program->idnumber);
            if (!empty($program->errors['idnumber'])) {
                $idnumber = $formaterror($idnumber . ' - ' . $program->errors['idnumber']);
            }

            $fullname = s($program->fullname);
            if (!empty($program->errors['fullname'])) {
                $fullname = $formaterror($fullname . ' - ' . $program->errors['fullname']);
            }

            if ($program->contextid) {
                $context = \context::instance_by_id($program->contextid);
                $cat = $context->get_context_name(false);
            } else {
                $cat = '-';
            }

            $types = program::get_program_startdate_types();
            $startdate = null;
            if (!empty($program->startdate)) {
                if ($program->startdate->type === 'date') {
                    $startdate = s($program->startdate->date);
                } else if ($program->startdate->type === 'delay') {
                    $startdate = $types[$program->startdate->type] . ' - ' . util::format_delay($program->startdate->delay);
                } else {
                    $startdate = $types[$program->startdate->type];
                }
            }

            $types = program::get_program_duedate_types();
            $duedate = null;
            if (!empty($program->duedate)) {
                if ($program->duedate->type === 'date') {
                    $duedate = s($program->duedate->date);
                } else if ($program->duedate->type === 'delay') {
                    $duedate = $types[$program->duedate->type] . ' - ' . util::format_delay($program->duedate->delay);
                } else {
                    $duedate = $types[$program->duedate->type];
                }
            }

            $types = program::get_program_enddate_types();
            $enddate = null;
            if (!empty($program->enddate)) {
                if ($program->enddate->type === 'date') {
                    $enddate = s($program->enddate->date);
                } else if ($program->enddate->type === 'delay') {
                    $enddate = $types[$program->enddate->type] . ' - ' . util::format_delay($program->enddate->delay);
                } else {
                    $enddate = $types[$program->enddate->type];
                }
            }

            $allowed = ['manual', 'selfallocation', 'approval'];
            if (get_config('tool_mucertify', 'version')) {
                $allowed[] = 'mucertify';
            }
            $sources = '';
            if (!empty($program->sources)) {
                $sources = [];
                foreach ($program->sources as $source) {
                    $source = (object)$source;
                    if (!in_array($source->sourcetype, $allowed, true)) {
                        $sources = [$formaterror(get_string('error'))];
                        break;
                    }
                    $sources[] = get_string('source_' . $source->sourcetype, 'tool_muprog');
                }
                $sources = implode(', ', $sources);
            }

            if (!empty($program->errors['contents'])) {
                $contents = $formaterror($program->errors['contents']);
            } else if (empty($program->contents->items)) {
                $contents = '';
            } else {
                $itemformatter = function (stdClass $item, int $level) use (&$itemformatter): array {
                    global $DB;
                    $padding = str_repeat('-', $level);
                    if ($item->itemtype === 'course') {
                        $coursename = $DB->get_field('course', 'fullname', ['id' => $item->courseid]);
                        return [$padding . format_string($coursename)];
                    } else if ($item->itemtype === 'training') {
                        if (util::is_mutrain_available()) {
                            $frameworkname = $DB->get_field('tool_mutrain_framework', 'name', ['id' => $item->trainingid]);
                            return [$padding . format_string($frameworkname)];
                        } else {
                            return [$padding . get_string('error')];
                        }
                    } else if ($item->itemtype === 'set') {
                        $result[] = $padding . s($item->fullname ?? get_string('set', 'tool_muprog'));
                        foreach ($item->items as $child) {
                            $result = array_merge($result, $itemformatter((object)$child, $level + 1));
                        }
                        return $result;
                    } else {
                        return [];
                    }
                };
                $contents = $itemformatter((object)$program->contents, 0);
                $contents = implode('<br />', $contents);
            }

            $description = format_text($program->description ?? '', $program->descriptionformat ?? FORMAT_HTML);

            $row = [
                $status,
                $idnumber,
                $fullname,
                $cat,
                clean_text(shorten_text($description, 30)),
                $yesno[$program->publicaccess ?? 0],
                $contents,
                $yesno[$program->creategroups ?? 0],
                $program->allocationstart ?? '',
                $program->allocationend ?? '',
                $startdate,
                $duedate,
                $enddate,
                $sources,
            ];

            foreach ($cfhandler->get_fields() as $cfield) {
                $colname = 'customfield_' . $cfield->get('shortname');
                if (isset($program->$colname)) {
                    $row[] = s($program->$colname);
                } else {
                    $row[] = '';
                }
            }

            $data[] = $row;
        }

        $table = new \html_table();
        $table->head = $columns;
        $table->id = 'upload_preview';
        $table->attributes['class'] = 'admintable generaltable';
        $table->data = $data;
        return \html_writer::table($table);
    }

    /**
     * Validate programs upload files and store decoded file.
     *
     * @param int $draftid
     * @param string $encoding
     * @return ?string error message, NULL means data seems ok
     */
    public static function store_filedata(int $draftid, string $encoding): ?string {
        global $USER;

        $fs = get_file_storage();
        $context = \context_user::instance($USER->id);

        $fs->delete_area_files($context->id, 'tool_muprog', 'upload', $draftid);

        $files = $fs->get_area_files($context->id, 'user', 'draft', $draftid, 'id DESC', false);
        if (!$files) {
            return get_string('required');
        }

        $zipfiles = [];
        $textfiles = [];
        $jsonfiles = [];
        $otherfiles = [];

        foreach ($files as $file) {
            $mimetype = $file->get_mimetype();
            if ($mimetype === 'application/zip') {
                $zipfiles[] = $file;
                continue;
            }
            if ($mimetype === 'text/csv' || $mimetype === 'text/plain') {
                $textfiles[] = $file;
                continue;
            }
            if ($mimetype === 'application/json') {
                $jsonfiles[] = $file;
                continue;
            }
            $otherfiles[] = $file;
        }

        if ($zipfiles) {
            if (count($zipfiles) > 1 || $textfiles || $jsonfiles || $otherfiles) {
                return get_string('upload_files_error', 'tool_muprog');
            }

            $zipfile = $zipfiles[0];
            $packer = get_file_packer('application/zip');
            $packer->extract_to_storage($zipfile, $context->id, 'user', 'draft', $draftid, '/');
            $zipfile->delete();
            $files = $fs->get_area_files($context->id, 'user', 'draft', $draftid, 'id DESC', false);
            if (!$files) {
                return get_string('required');
            }

            foreach ($files as $file) {
                $mimetype = $file->get_mimetype();
                if ($mimetype === 'text/csv' || $mimetype === 'text/plain') {
                    $textfiles[] = $file;
                    continue;
                }
                if ($mimetype === 'application/json') {
                    $jsonfiles[] = $file;
                    continue;
                }
                $otherfiles[] = $file;
            }
        }

        if ($textfiles) {
            if ($jsonfiles || $otherfiles) {
                return get_string('upload_files_error', 'tool_muprog');
            }

            $programs = self::decode_csv_files($textfiles, $encoding);
            if ($programs === null) {
                return get_string('upload_files_error', 'tool_muprog');
            }
        } else if ($jsonfiles) {
            if ($otherfiles) {
                return get_string('upload_files_error', 'tool_muprog');
            }

            // There might be schema file, so try to parse all json files.
            $programs = null;
            foreach ($jsonfiles as $jsonfile) {
                $programs = self::decode_json_file($jsonfile, $encoding);
                if ($programs !== null) {
                    break;
                }
            }
            if ($programs === null) {
                return get_string('upload_files_error', 'tool_muprog');
            }
        } else {
            return get_string('upload_files_error', 'tool_muprog');
        }

        $programs = \tool_mulib\local\json_schema::normalise_data($programs);

        $json = (object)[
            'programs' => $programs,
        ];
        $schema = file_get_contents(__DIR__ . '/../../db/programs_schema.json');
        [$valid, $errors] = \tool_mulib\local\json_schema::validate($json, $schema);
        if (!$valid) {
            $debug = [];
            foreach ($errors as $i => $lines) {
                $debug[] = $i . ' ' . implode('; ', $lines);
            }
            return implode("<br />", $debug);
        }

        // Look for non-fatal errors - these will be skipped during upload later.
        self::validate_references($programs);

        util::store_uploaded_data($draftid, $programs);

        return null;
    }

    /**
     * Check program idnumber uniqueness, category names, course and training references, etc.
     *
     * NOTE: This is done only once right after file upload for performance reasons.
     *
     * @param array $programs program objects to be checked and updated
     * @return void
     */
    public static function validate_references(array $programs): void {
        global $DB;

        $allprograms = $DB->get_fieldset_select('tool_muprog_program', 'idnumber', '1=1');
        $allprograms = array_map([\core_text::class, 'strtolower'], $allprograms);

        $programs = array_values($programs);
        foreach ($programs as $program) {
            $program->errors = [];

            // Validate program idnumber.
            if (trim($program->idnumber ?? '') === '') {
                $program->errors['idnumber'] = get_string('required');
            } else if (trim($program->idnumber) !== $program->idnumber) {
                $program->errors['idnumber'] = get_string('error');
            } else {
                $idnumberlower = \core_text::strtolower($program->idnumber);
                if (in_array($idnumberlower, $allprograms, true)) {
                    $program->errors['idnumber'] = get_string('duplicate');
                } else {
                    $allprograms[] = $idnumberlower;
                }
            }

            // Validate program name.
            if (trim($program->fullname ?? '') === '') {
                $program->errors['fullname'] = get_string('required');
            }

            // Validate program category.
            if (!isset($program->category)) {
                $program->category = null;
                $program->contextid = null;
            } else if ($program->category === '') {
                $context = \context_system::instance();
                if (has_capability('tool/muprog:upload', $context)) {
                    $program->contextid = $context->id;
                } else {
                    $program->contextid = null;
                }
            } else {
                $categories = $DB->get_records('course_categories', ['idnumber' => $program->category]);
                if (count($categories) > 1) {
                    $program->contextid = null;
                } else if (count($categories) === 1) {
                    $category = reset($categories);
                    $context = \context_coursecat::instance($category->id, IGNORE_MISSING);
                    if ($context && has_capability('tool/muprog:upload', $context)) {
                        $program->contextid = $context->id;
                    } else {
                        $program->contextid = null;
                    }
                } else {
                    $categories = $DB->get_records('course_categories', ['name' => $program->category]);
                    if (count($categories) === 1) {
                        $category = reset($categories);
                        $context = \context_coursecat::instance($category->id, IGNORE_MISSING);
                        if ($context && has_capability('tool/muprog:upload', $context)) {
                            $program->contextid = $context->id;
                        } else {
                            $program->contextid = null;
                        }
                    } else {
                        $program->contextid = null;
                    }
                }
            }

            // Validate content references.
            if (empty($program->contents)) {
                $program->contents = null;
            } else {
                $validator = function ($item) use (&$validator): ?string {
                    global $DB;
                    if ($item->itemtype === 'course') {
                        unset($item->items);
                        $courses = $DB->get_records('course', ['shortname' => $item->reference], '', 'id');
                        if (count($courses) == 1) {
                            $course = reset($courses);
                            $item->courseid = $course->id;
                        } else {
                            return get_string('error');
                        }
                        return null;
                    } else if ($item->itemtype === 'training' && util::is_mutrain_available()) {
                        unset($item->items);
                        $frameworks = $DB->get_records('tool_mutrain_framework', ['idnumber' => $item->reference]);
                        if (count($frameworks) === 1) {
                            $framework = reset($frameworks);
                            $item->trainingid = $framework->id;
                        } else if (count($frameworks) === 0) {
                            $frameworks = $DB->get_records('tool_mutrain_framework', ['name' => $item->reference]);
                            if (count($frameworks) === 1) {
                                $framework = reset($frameworks);
                                $item->trainingid = $framework->id;
                            } else {
                                return get_string('error');
                            }
                        }
                        return null;
                    } else if ($item->itemtype === 'set') {
                        if (empty($item->items)) {
                            $item->items = [];
                        }
                        foreach ($item->items as $child) {
                            $error = $validator($child);
                            if ($error !== null) {
                                return $error;
                            }
                        }
                        $item->items = array_values($item->items);
                        return null;
                    } else {
                        return get_string('error');
                    }
                };
                $error = $validator($program->contents);
                if ($error !== null) {
                    $program->errors['contents'] = $error;
                }
            }
        }
    }

    /**
     * Decode JSON file.
     *
     * @param string|\stored_file $file
     * @param string $encoding
     * @return array|null $programs
     */
    public static function decode_json_file($file, string $encoding): ?array {
        if ($file instanceof \stored_file) {
            $data = $file->get_content();
        } else {
            $data = file_get_contents($file);
        }

        if ($encoding !== 'UTF-8') {
            $data = \core_text::convert($data, $encoding, 'UTF-8');
        }
        $data = \core_text::trim_utf8_bom($data);

        // BC hack to solve DB field renaming.
        $data = str_replace('"public"', '"publicaccess"', $data);

        $decoded = json_decode($data, false);

        if (!isset($decoded->programs) || !is_array($decoded->programs) || !$decoded->programs) {
            return null;
        }

        $programs = fix_utf8($decoded->programs);
        return $programs;
    }

    /**
     * Decode flat CSV files to array of programs tree structure.
     *
     * NOTE: there is no validation here apart from required columns that are used for file identification.
     *
     * @param array $files
     * @param string $encoding
     * @return array|null $programs
     */
    public static function decode_csv_files(array $files, string $encoding): ?array {
        global $CFG;
        require_once($CFG->dirroot . '/lib/csvlib.class.php');

        $dir = make_request_directory();
        $tempfile = "$dir/temp.csv";

        $delimiters = [',', ';', ':', "\t"];
        if (isset($CFG->CSV_DELIMITER) && strlen($CFG->CSV_DELIMITER) === 1) {
            if (!in_array($delimiters, $CFG->CSV_DELIMITER, true)) {
                $delimiters[] = $CFG->CSV_DELIMITER;
            }
        }

        $programs = null;
        $sources = null;
        $contents = null;
        foreach ($files as $file) {
            if ($file instanceof \stored_file) {
                $data = $file->get_content();
            } else {
                $data = file_get_contents($file);
            }
            if ($encoding !== 'UTF-8') {
                $data = \core_text::convert($data, $encoding, 'UTF-8');
            } else {
                $data = fix_utf8($data);
            }
            $data = \core_text::trim_utf8_bom($data);

            [$firstline, $rest] = explode("\n", $data, 2);
            $delimiter = null;
            foreach ($delimiters as $del) {
                $cols = explode($del, $firstline);
                if (count($cols) >= 2) {
                    $delimiter = $del;
                    break;
                }
            }
            unset($firstline);
            unset($rest);
            if ($delimiter === null) {
                // Unknown delimiter.
                return null;
            }
            file_put_contents($tempfile, $data);
            $rows = [];
            $fh = fopen($tempfile, "r");
            while (($line = fgetcsv($fh, null, $delimiter, '"', '')) !== false) {
                if ($line === [null] || $line === []) {
                    // Empty line.
                    continue;
                }
                $rows[] = $line;
            }
            fclose($fh);
            unlink($tempfile);
            if (!$rows) {
                // Empty file.
                return null;
            }
            if (in_array('itemtype', $rows[0], true) && in_array('program', $rows[0], true)) {
                if (isset($contents)) {
                    // Duplicate contents.
                    return null;
                }
                $contents = $rows;
            } else if (in_array('sourcetype', $rows[0], true) && in_array('program', $rows[0], true)) {
                if (isset($sources)) {
                    // Duplicate sources.
                    return null;
                }
                $sources = $rows;
            } else if (in_array('fullname', $rows[0], true) && in_array('idnumber', $rows[0], true)) {
                if (isset($programs)) {
                    // Duplicate programs.
                    return null;
                }
                $programs = $rows;
            } else {
                // Unknown file.
                return null;
            }
        }

        if (!$programs) {
            // No programs.
            return null;
        }

        $result = [];
        $idmap = [];
        $colmap = null;
        foreach ($programs as $row) {
            if ($colmap === null) {
                $colmap = array_flip($row);
                continue;
            }
            $program = [];
            foreach ($colmap as $colname => $ci) {
                if (!isset($row[$ci])) {
                    continue;
                }
                if ($colname === 'public') {
                    // BC hack to solve DB field renaming.
                    $colname = 'publicaccess';
                }
                if (
                    $row[$ci] === ''
                    && !in_array($colname, ['category', 'description', 'allocationstart', 'allocationend'])
                ) {
                    continue;
                }
                $value = $row[$ci];
                if (in_array($colname, ['descriptionformat', 'publicaccess', 'creategroups'])) {
                    $value = intval($value);
                } else if (in_array($colname, ['allocationstart', 'allocationend'])) {
                    if ($value === '') {
                        $value = null;
                    }
                } else if (in_array($colname, ['startdate', 'duedate', 'enddate'])) {
                    $value = self::csv_decode_object($value);
                }
                $program[$colname] = $value;
            }

            $program['sources'] = [];
            $program['rawcontents'] = [];
            $result[] = (object)$program;
            $idmap[$program['idnumber']] = array_key_last($result);
        }
        unset($programs);
        unset($program);

        if ($sources) {
            $colmap = null;
            foreach ($sources as $row) {
                if ($colmap === null) {
                    $colmap = array_flip($row);
                    continue;
                }
                $pidnumber = $row[$colmap['program']];
                if (!isset($idmap[$pidnumber])) {
                    continue;
                }
                $program = $result[$idmap[$pidnumber]];

                $source = [];
                foreach ($colmap as $colname => $ci) {
                    if ($colname === 'program') {
                        continue;
                    }
                    if (!isset($row[$ci]) || $row[$ci] === '') {
                        continue;
                    }
                    $value = $row[$ci];
                    if ($colname === 'data') {
                        $value = self::csv_decode_object($value);
                    }
                    $source[$colname] = $value;
                }
                $program->sources[] = (object)$source;
            }
        }
        unset($sources);
        unset($program);

        if ($contents) {
            $colmap = null;
            foreach ($contents as $row) {
                if ($colmap === null) {
                    $colmap = array_flip($row);
                    continue;
                }
                $pidnumber = $row[$colmap['program']];
                if (!isset($idmap[$pidnumber])) {
                    continue;
                }
                $program = $result[$idmap[$pidnumber]];
                $content = [];
                foreach ($colmap as $colname => $ci) {
                    if ($colname === 'program') {
                        continue;
                    }
                    if (!isset($row[$ci]) || $row[$ci] === '') {
                        continue;
                    }
                    $value = $row[$ci];
                    $content[$colname] = $value;
                }
                $program->rawcontents[] = $content;
            }
            unset($contents);
        }

        // Fix nesting of contents items and objects.
        foreach ($result as $program) {
            $program->contents = (object)[
                'itemtype' => 'set',
                'completiondelay' => 0,
                'sequencetype' => 'allinanyorder',
                'items' => [],
            ];
            $rawcontents = $program->rawcontents;
            unset($program->rawcontents);

            $setmap = [];
            $si = 1;
            $setmap[$si] = $program->contents;

            foreach ($rawcontents as $rawitem) {
                foreach ($rawitem as $k => $v) {
                    if (in_array($k, ['completiondelay', 'points', 'minpoints', 'minprerequisites'])) {
                        if ($v === '') {
                            $v = null;
                        } else {
                            $v = (int)$v;
                        }
                    }
                    $rawitem[$k] = $v;
                }
                $parent = $program->contents;
                if ($rawitem['itemtype'] === 'set') {
                    if (isset($rawitem['parentset']) && trim($rawitem['parentset']) !== '') {
                        if ($rawitem['parentset'] == 0) {
                            foreach ($rawitem as $k => $v) {
                                if ($k === 'parentset' || $k === 'items') {
                                    continue;
                                }
                                $program->contents->$k = $v;
                            }
                            continue;
                        }
                        if (isset($setmap[$rawitem['parentset']])) {
                            $parent = $setmap[$rawitem['parentset']];
                        }
                    }
                    unset($rawitem['parentset']);
                    $rawitem['items'] = [];
                    $set = (object)$rawitem;
                    $parent->items[] = $set;
                    $si++;
                    $setmap[$si] = $set;
                } else if ($rawitem['itemtype'] === 'course' || $rawitem['itemtype'] === 'training') {
                    if (isset($rawitem['parentset']) && isset($setmap[$rawitem['parentset']])) {
                        $parent = $setmap[$rawitem['parentset']];
                    }
                    unset($rawitem['parentset']);
                    unset($rawitem['items']);
                    $parent->items[] = (object)$rawitem;
                }
            }
        }

        $result = fix_utf8($result);
        return $result;
    }

    /**
     * Parse date value.
     *
     * @param mixed $value
     * @return int|null
     */
    public static function parse_date($value): ?int {
        if (is_array($value) || is_object($value) || is_bool($value)) {
            // Invalid type.
            return null;
        }
        if (trim($value ?? '') === '') {
            return null;
        }
        if (is_number($value)) {
            if ($value > strtotime('1980-01-01')) {
                // Looks like a valid timestamp.
                return (int)$value;
            } else {
                return null;
            }
        }
        $result = strtotime($value);
        if (!$result) {
            return null;
        }
        return $result;
    }

    /**
     * Decode custom objects from CSV column value.
     *
     * @param string|null $value
     * @return stdClass|null
     */
    public static function csv_decode_object(?string $value): ?stdClass {
        if ($value === null || trim($value) === '') {
            return null;
        }
        $val = [];
        foreach (explode('|', $value) as $part) {
            if (!str_contains($part, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $part, 2);
            if ($v === '') {
                $v = null;
            } else if (is_number($v)) {
                $v = (int)$v;
            }
            $val[$k] = $v;
        }
        return (object)$val;
    }
}
