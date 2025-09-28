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

namespace tool_mutenancy\hook;

/**
 * Hook for extra Tenant management menu items.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\label('Allows plugins to add items to Tenant management primary menu.')]
#[\core\attribute\tags('tool_mutenancy')]
final class tenant_management_menu {
    /** @var \navigation_node */
    public $tenantnode;
    /** @var \stdClass */
    public $tenant;
    /** @var \context_tenant */
    public $tenantcontext;
    /** @var \context_coursecat */
    public $catcontext;

    /**
     * Constructor.
     *
     * @param \navigation_node $tenantnode
     * @param \stdClass $tenant
     * @param \context_tenant $tenantcontext
     * @param \context_coursecat $catcontext
     */
    public function __construct(
        \navigation_node $tenantnode,
        \stdClass $tenant,
        \context_tenant $tenantcontext,
        \context_coursecat $catcontext
    ) {
        $this->tenantnode = $tenantnode;
        $this->tenant = $tenant;
        $this->tenantcontext = $tenantcontext;
        $this->catcontext = $catcontext;
    }
}
