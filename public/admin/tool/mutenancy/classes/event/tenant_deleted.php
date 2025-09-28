<?php
// This file is part of MuTMS suite of plugins for Moodle™ LMS.
//
// This tenant is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This tenant is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this tenant.  If not, see <https://www.gnu.org/licenses/>.

// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

namespace tool_mutenancy\event;

/**
 * Tenant deleted event.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tenant_deleted extends \core\event\base {
    /**
     * Helper for event creation.
     *
     * @param \stdClass $tenant
     * @param \context_tenant $context
     *
     * @return static
     */
    public static function create_from_tenant(\stdClass $tenant, \context_tenant $context): static {
        $data = [
            'context' => $context,
            'objectid' => $tenant->id,
        ];
        /** @var static $event */
        $event = self::create($data);
        $event->add_record_snapshot('tool_mutenancy_tenant', $tenant);
        return $event;
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' deleted tenant with id '$this->objectid'";
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_tenant_deleted', 'tool_mutenancy');
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/admin/tool/mutenancy/index.php');
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'tool_mutenancy_tenant';
    }
}
