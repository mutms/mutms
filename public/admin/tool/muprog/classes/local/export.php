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
 * Program export helper.
 *
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class export {
    /**
     * Format timestamp as full W3C date for export.
     *
     * @param int|null $timestamp
     * @return string|null
     */
    public static function format_date(?int $timestamp): ?string {
        if ($timestamp === null || $timestamp === 0) {
            return null;
        }

        $date = new \DateTime('@' . $timestamp);
        $date->setTimezone(\core_date::get_user_timezone_object());
        return $date->format(\DateTime::W3C);
    }

    /**
     * Export programs data.
     *
     * NOTE: debugging is triggered when schema validation fails
     *
     * @param string $select
     * @param array $params
     * @return array of programs objects
     */
    public static function export_programs(string $select, array $params): array {
        global $DB;

        $result = [];
        $rs = $DB->get_recordset_select('tool_muprog_program', $select, $params, 'fullname ASC, id ASC');
        foreach ($rs as $record) {
            $context = \context::instance_by_id($record->contextid);
            if ($context instanceof \context_system) {
                $cat = '';
            } else {
                $coursecat = $DB->get_record('course_categories', ['id' => $context->instanceid], '*', MUST_EXIST);
                if (trim($coursecat->idnumber ?? '') === '') {
                    $cat = $coursecat->name;
                } else {
                    $cat = $coursecat->idnumber;
                }
            }

            $program = [];
            $program['idnumber'] = $record->idnumber;
            $program['fullname'] = $record->fullname;
            $program['category'] = $cat;
            $program['description'] = $record->description;
            $program['descriptionformat'] = (int)$record->descriptionformat;
            $program['publicaccess'] = (int)$record->publicaccess;
            // Cohort visibility is problematic in exports, skip it for now.
            // Do not export archived flag, it is not possible to import archived programs anyway.
            $program['creategroups'] = (int)$record->creategroups;
            $program['allocationstart'] = self::format_date($record->timeallocationstart);
            $program['allocationend'] = self::format_date($record->timeallocationend);

            $startdate = (array)json_decode($record->startdatejson);
            if (isset($startdate['date'])) {
                $startdate['date'] = self::format_date($startdate['date']);
            }
            $program['startdate'] = $startdate;

            $duedate = (array)json_decode($record->duedatejson);
            if (isset($duedate['date'])) {
                $duedate['date'] = self::format_date($duedate['date']);
            }
            $program['duedate'] = $duedate;

            $enddate = (array)json_decode($record->enddatejson);
            if (isset($enddate['date'])) {
                $enddate['date'] = self::format_date($enddate['date']);
            }
            $program['enddate'] = $enddate;

            $cfhandler = \tool_muprog\customfield\program_handler::create();
            $cfdatas = $cfhandler->get_instance_data($record->id);
            foreach ($cfdatas as $cfdata) {
                // We need to use raw internal value here to allow imports.
                $program['customfield_' . $cfdata->get_field()->get('shortname')] = $cfdata->get_value();
            }

            $top = \tool_muprog\local\content\top::load($record->id);
            $iterator = function (\tool_muprog\local\content\item $item) use (&$iterator): array {
                global $DB;
                if ($item instanceof \tool_muprog\local\content\set) {
                    $result = [
                        'itemtype' => 'set',
                        'points' => $item->get_points(),
                        'completiondelay' => $item->get_completiondelay(),
                        'setname' => $item->get_fullname(),
                        'sequencetype' => $item->get_sequencetype(),
                    ];
                    if ($result['sequencetype'] === 'minpoints') {
                        $result['minpoints'] = $item->get_minpoints();
                    } else if ($result['sequencetype'] === 'atleast') {
                        $result['minprerequisites'] = $item->get_minprerequisites();
                    }
                    $result['items'] = [];
                    foreach ($item->get_children() as $child) {
                        $result['items'][] = $iterator($child);
                    }
                    return $result;
                }

                if ($item instanceof \tool_muprog\local\content\course) {
                    $course = $DB->get_record('course', ['id' => $item->get_courseid()]);
                    if ($course) {
                        $reference = $course->shortname;
                    } else {
                        $reference = null; // Error - this will fail during validation.
                    }
                    $result = [
                        'itemtype' => 'course',
                        'reference' => $reference,
                        'points' => $item->get_points(),
                        'completiondelay' => $item->get_completiondelay(),
                    ];
                    return $result;
                }

                if ($item instanceof \tool_muprog\local\content\training) {
                    if (util::is_mutrain_available()) {
                        $framework = $DB->get_record('tool_mutrain_framework', ['id' => $item->get_trainingid()]);
                        if ($framework) {
                            $reference = $framework->idnumber ?? $framework->name;
                        } else {
                            $reference = null; // Error - this will fail during validation.
                        }
                    } else {
                        $reference = null; // Error - this will fail during validation.
                    }
                    $result = [
                        'itemtype' => 'training',
                        'reference' => $reference,
                        'points' => $item->get_points(),
                        'completiondelay' => $item->get_completiondelay(),
                    ];
                    return $result;
                }

                throw new \coding_exception('invalid item type');
            };

            $program['contents'] = $iterator($top);
            unset($program['contents']['setname']);
            unset($program['contents']['points']);

            $program['sources'] = [];
            $sources = $DB->get_records('tool_muprog_source', ['programid' => $record->id], 'type ASC');
            foreach ($sources as $source) {
                if (!in_array($source->type, ['manual', 'selfallocation', 'approval', 'mucertify'])) {
                    // Cohort references are problematic in exports, skip it for now.
                    continue;
                }
                $s = ['sourcetype' => $source->type];
                if ($source->datajson) {
                    $d = (array)json_decode($source->datajson);
                    if ($d) {
                        $s['data'] = $d;
                    }
                }
                $program['sources'][] = $s;
            }

            $result[] = $program;
        }
        $rs->close();

        // Fix objects and arrays to be JSON compatible.
        $result = \tool_mulib\local\json_schema::normalise_data($result);

        if (debugging('', DEBUG_DEVELOPER)) {
            $json = (object)[
                'programs' => $result,
            ];

            $schema = file_get_contents(__DIR__ . '/../../db/programs_schema.json');
            [$valid, $errors] = \tool_mulib\local\json_schema::validate($json, $schema);
            if (!$valid) {
                $debug = [];
                foreach ($errors as $i => $lines) {
                    $debug[] = $i . ' ' . implode('; ', $lines);
                }
                $debug = implode("\n", $debug);
                debugging("programs export validation error:\n$debug", DEBUG_DEVELOPER);
            }
        }

        return $result;
    }

    /**
     * Export data in JSON format.
     *
     * @param stdClass $data settings from export form
     * @return string zip file
     */
    public static function export_json(stdClass $data): string {
        global $DB;

        if (!empty($data->programids)) {
            [$in, $params] = $DB->get_in_or_equal($data->programids);
            $select = "id $in";
        } else {
            if ($data->contextid) {
                $select = "contextid = ? AND archived = ?";
                $params = [$data->contextid, $data->archived];
            } else {
                $select = "archived = ?";
                $params = [$data->archived];
            }
        }

        $exportedprograms = self::export_programs($select, $params);

        $dir = make_request_directory();
        $schemafile = 'programs_schema.json';
        $jsonfile = 'programs.json';
        $zipfile = 'export.zip';

        $export = [
            '$schema' => './' . $schemafile,
            'programs' => $exportedprograms,
        ];

        $json = json_encode($export, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        file_put_contents("$dir/$jsonfile", $json);

        $packer = get_file_packer('application/zip');
        $packer->archive_to_pathname([
            $schemafile => realpath(__DIR__ . '/../../db/programs_schema.json'),
            $jsonfile => "$dir/$jsonfile",
        ], "$dir/$zipfile");

        return "$dir/$zipfile";
    }

    /**
     * Flatten row for CSV export.
     *
     * @param array $columns
     * @param array $row
     * @param string|null $programidnumber
     * @return array
     */
    public static function normalise_csv_row(array $columns, array $row, ?string $programidnumber = null): array {
        $result = [];
        foreach ($columns as $column) {
            if ($column === 'program') {
                $result[] = $programidnumber;
                continue;
            }
            $val = $row[$column] ?? null;
            if (is_bool($val)) {
                $val = (int)$val;
            } else if ($val === null) {
                $val = '';
            } else if (is_array($val) || is_object($val)) {
                $newval = [];
                foreach ((array)$val as $k => $v) {
                    $newval[] = "$k=$v";
                }
                $val = implode('|', $newval);
            }
            $result[] = $val;
        }
        return $result;
    }

    /**
     * Write data into CSV file.
     *
     * @param string $file
     * @param array $data
     * @param string $delimiter
     * @param string $encoding
     * @return void
     */
    public static function write_csv_file(string $file, array $data, string $delimiter, string $encoding): void {
        $fp = fopen($file, 'w');
        foreach ($data as $row) {
            fputcsv($fp, $row, $delimiter, '"', '');
        }
        fclose($fp);

        if ($encoding === 'UTF-8') {
            return;
        }

        $content = file_get_contents($file);
        $content = \core_text::convert($content, 'UTF-8', $encoding);
        unlink($file);
        file_put_contents($file, $content);
    }

    /**
     * Export data in CSV format.
     *
     * @param stdClass $data settings from export form
     * @return string zip file
     */
    public static function export_csv(stdClass $data): string {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/lib/csvlib.class.php');

        $delimiter = \csv_import_reader::get_delimiter($data->delimiter_name);

        if (!empty($data->programids)) {
            [$in, $params] = $DB->get_in_or_equal($data->programids);
            $select = "id $in";
        } else {
            if ($data->contextid) {
                $select = "contextid = ? AND archived = ?";
                $params = [$data->contextid, $data->archived];
            } else {
                $select = "archived = ?";
                $params = [$data->archived];
            }
        }

        $exportedprograms = self::export_programs($select, $params);

        $programs = [];
        $programs[] = [
            'idnumber',
            'fullname',
            'category',
            'description',
            'descriptionformat',
            'publicaccess',
            'creategroups',
            'allocationstart',
            'allocationend',
            'startdate',
            'duedate',
            'enddate',
        ];

        $sources = [];
        $sources[] = [
            'program',
            'sourcetype',
            'data',
        ];

        $contents = [];
        $contents[] = [
            'program',
            'itemtype',
            'reference',
            'points',
            'completiondelay',
            'parentset',
            'setname',
            'sequencetype',
            'minprerequisites',
            'minpoints',
        ];

        foreach ($exportedprograms as $program) {
            $program = (array)$program;
            $programid = $program['idnumber'];

            $programs[] = self::normalise_csv_row($programs[0], $program);

            $rows = [];
            $flatten = function (stdClass $item, int $parentset) use (&$flatten, &$rows): void {
                $item = (array)$item;
                $item['parentset'] = $parentset;
                $rows[] = $item;
                if ($item['itemtype'] === 'set') {
                    $parentset++;
                    foreach ($item['items'] as $child) {
                        $flatten($child, $parentset);
                    }
                }
            };
            $flatten($program['contents'], 0);
            foreach ($rows as $row) {
                $contents[] = self::normalise_csv_row($contents[0], $row, $programid);
            }

            foreach ($program['sources'] as $source) {
                $sources[] = self::normalise_csv_row($sources[0], (array)$source, $programid);
            }
        }

        $dir = make_request_directory();
        $programsfile = 'programs.csv';
        $contentsfile = 'programs_contents.csv';
        $sourcesfile = 'programs_sources.csv';
        $zipfile = 'export.zip';

        self::write_csv_file("$dir/$programsfile", $programs, $delimiter, $data->encoding);
        self::write_csv_file("$dir/$contentsfile", $contents, $delimiter, $data->encoding);
        self::write_csv_file("$dir/$sourcesfile", $sources, $delimiter, $data->encoding);

        $packer = get_file_packer('application/zip');
        $packer->archive_to_pathname([
            $programsfile => "$dir/$programsfile",
            $contentsfile => "$dir/$contentsfile",
            $sourcesfile => "$dir/$sourcesfile",
        ], "$dir/$zipfile");

        return "$dir/$zipfile";
    }

    /**
     * Send zip file with exported data.
     *
     * @param stdClass $data export form settings
     * @return void
     */
    public static function process(stdClass $data): void {
        if ($data->format === 'json') {
            $file = self::export_json($data);
            $filename = get_string('exportfile_programs', 'tool_muprog') . '_json-' . gmdate('Ymd_Hi') . '.zip';
        } else if ($data->format === 'csv') {
            $file = self::export_csv($data);
            $filename = get_string('exportfile_programs', 'tool_muprog') . '_csv-' . gmdate('Ymd_Hi') . '.zip';
        } else {
            throw new \invalid_parameter_exception('invalid program export format');
        }

        $filename = clean_filename($filename);
        send_file($file, $filename, 0, 0, false, true);
    }
}
