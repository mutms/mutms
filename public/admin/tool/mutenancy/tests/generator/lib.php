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

use tool_mutenancy\local\tenant;
use tool_mutenancy\local\tenancy;

/**
 * Multi-tenancy test data generator.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tool_mutenancy_generator extends component_generator_base {
    /** @var int tenant count */
    private $tenantcount = 0;

    #[\Override]
    public function reset() {
        $this->tenantcount = 0;
    }

    /**
     * Create new tenant.
     *
     * NOTE: multi-tenancy is activated automatically.
     *
     * @param stdClass|array|null $record
     * @return stdClass tenant record
     */
    public function create_tenant($record = null): stdClass {
        global $DB;

        $this->tenantcount++;

        if (!tenancy::is_active()) {
            tenancy::activate();
        }

        $record = (object)(array)$record;

        if (!isset($record->name)) {
            $record->name = 'Tenant ' . $this->tenantcount;
        }

        if (!isset($record->idnumber)) {
            $record->idnumber = 'ten' . $this->tenantcount;
        }

        if (!empty($record->assoccohort)) {
            $cohort = $DB->get_record('cohort', ['idnumber' => $record->assoccohort], '*', MUST_EXIST);
            $record->assoccohortid = $cohort->id;
        }
        unset($record->assoccohort);

        if (!empty($record->category)) {
            $category = $DB->get_record('course_categories', ['idnumber' => $record->category], '*', MUST_EXIST);
            $record->categoryid = $category->id;
        }

        return tenant::create($record);
    }

    /**
     * Add new tenant manager.
     *
     * @param stdClass|array $record userid and tenantid are required
     * @return void
     */
    public function create_tenant_manager($record = null): void {
        $record = (object)(array)$record;

        if (!\tool_mutenancy\local\manager::add($record->tenantid, $record->userid)) {
            throw new \core\exception\invalid_parameter_exception('cannot add manager');
        }
    }
}
