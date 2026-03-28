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

namespace tool_mutrain\table;

use stdClass;
use core\url;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

/**
 * All credit frameworks.
 *
 * @package    tool_mutrain
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class fields extends \table_sql {
    /** @var int */
    const DEFAULT_PERPAGE = 99;
    /** @var stdClass */
    protected $framework;

    /**
     * Constructor.
     *
     * @param url $url
     * @param stdClass $framework
     */
    public function __construct(url $url, stdClass $framework) {
        parent::__construct('tool_mutrain_field');

        $this->framework = $framework;
        $page = optional_param('page', 0, PARAM_INT);
        $params = [];
        if ($page > 0) {
            $params['page'] = $page;
            $this->currpage = $page;
        }
        $baseurl = new url($url, $params);
        $this->define_baseurl($baseurl);
        $this->pagesize = self::DEFAULT_PERPAGE;

        $this->collapsible(false);
        $this->sortable(true, 'name', SORT_ASC);
        $this->pageable(true);
        $this->is_downloadable(false);

        $columns = [
            'name',
            'shortname',
            'component',
            'area',
            'actions',
        ];
        $headers = [
            get_string('name'),
            get_string('shortname'),
            get_string('component', 'tool_mutrain'),
            get_string('area', 'tool_mutrain'),
            get_string('actions'),
        ];

        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->set_attribute('id', 'tool_mutrain_field_table');

        foreach ($columns as $column) {
            if ($column !== 'name') {
                $this->no_sorting($column);
            }
        }

        $frameworkid = (int)$this->framework->id;

        // NOTE: for now only course and program custom fields.
        $sql = "SELECT cf.id, cf.name, cf.shortname, cc.component, cc.area
                  FROM {customfield_field} cf
                  JOIN {customfield_category} cc ON cc.id = cf.categoryid
                  JOIN {tool_mutrain_field} tf ON tf.fieldid = cf.id
                 WHERE cf.type = 'mutrain' AND tf.frameworkid = $frameworkid
                       AND (
                            (cc.component = 'core_course' AND cc.area = 'course')
                                OR
                            (cc.component = 'tool_muprog' AND cc.area = 'program')
                           )
                 ";
        $this->set_sql("*", "($sql) AS fields", "1=1", []);
    }

    /**
     * Display the framework name.
     *
     * @param stdClass $field
     * @return string html used to display the framework name
     */
    public function col_name(stdClass $field) {
        $name = format_string($field->name);
        return $name;
    }

    /**
     * Display the framework name.
     *
     * @param stdClass $field
     * @return string html used to display the framework name
     */
    public function col_shortname(stdClass $field) {
        $name = s($field->shortname);
        return $name;
    }

    /**
     * Display the framework public flag.
     *
     * @param stdClass $field
     * @return string
     */
    public function col_component(stdClass $field) {
        return $field->component;
    }

    /**
     * Display the fields list.
     *
     * @param stdClass $field
     * @return string
     */
    public function col_area(stdClass $field) {
        return $field->area;
    }

    /**
     * Display the action buttons.
     *
     * @param stdClass $field
     * @return string
     */
    public function col_actions(stdClass $field) {
        global $OUTPUT;

        $html = '';

        if (!$this->framework->archived && has_capability('tool/mutrain:manageframeworks', \context_system::instance())) {
            $url = new \core\url(
                '/admin/tool/mutrain/management/field_remove.php',
                ['frameworkid' => $this->framework->id, 'fieldid' => $field->id]
            );
            $button = new \tool_mulib\output\ajax_form\icon($url, get_string('field_remove', 'tool_mutrain'), 'i/delete', 'moodle');
            $html .= $OUTPUT->render($button);
        }

        return $html;
    }

    /**
     * Nothing info.
     *
     * @return void
     */
    public function print_nothing_to_display() {
        // Get rid of ugly H2 heading.
        echo '<em>' . get_string('nothingtodisplay') . '</em>';
    }
}
