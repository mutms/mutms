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

/**
 * Custom home pages behat generators.
 *
 * @package    tool_muhome
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_tool_muhome_generator extends behat_generator_base {
    /**
     * Get a list of the entities that Behat can create using the generator step.
     *
     * @return array
     */
    protected function get_creatable_entities(): array {
        return [
            'pages' => [
                'singular' => 'page',
                'datagenerator' => 'page',
                'required' => ['name', 'status', 'guestvisible', 'uservisible'],
                'switchids' => [
                    'status' => 'status',
                ],
            ],
        ];
    }

    /**
     * Look up the status constant.
     *
     * @param string $status
     * @return int
     */
    protected function get_status_id(string $status): int {
        $status = strtolower($status);
        switch ($status) {
            case 'draft':
                return \tool_muhome\local\page::STATUS_DRAFT;
            case 'active':
                return \tool_muhome\local\page::STATUS_ACTIVE;
            case 'archived':
                return \tool_muhome\local\page::STATUS_ARCHIVED;
            default:
                throw new \Exception('Invalid status "' . $status . '"');
        }
    }

    /**
     * Pre-process page, populate contextid property.
     *
     * @param array $page
     * @return array
     */
    protected function preprocess_page(array $page): array {
        if (!empty($page['contextlevel'])) {
            $page['contextid'] = $this->get_context($page['contextlevel'], $page['reference'])->id;
            unset($page['contextlevel'], $page['reference']);
        }

        if (!empty($page['cohortvisible'])) {
            $cohortids = [];
            $cohortidnumbers = explode(',', $page['cohortvisible']);
            foreach ($cohortidnumbers as $cohortidnumber) {
                $cohortidnumber = trim($cohortidnumber);
                if ($cohortidnumber === '') {
                    continue;
                }
                $cohortids[] = $this->get_cohort_id($cohortidnumber);
            }
            $page['cohortvisible'] = $cohortids;
        }

        return $page;
    }
}
