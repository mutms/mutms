@tool @tool_muhome @javascript @MuTMS
Feature: Behat tool_muhome generator usage
  Background:
    Given the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
      | Cat 3 | CAT2     | CAT3     |
    And the following "cohorts" exist:
      | name       | idnumber | contextlevel | reference |
      | Cohort 1   | CH1      | System       |           |
      | Cohort 2   | CH2      | System       |           |
      | Cohort 3   | CH3      | System       |           |
    And the following "users" exist:
      | username  | firstname | lastname  | email                |
      | manager1  | Manager   | 1         | manager1@example.com |
      | viewer1   | Viewer    | 1         | viewer1@example.com  |
    And the following "roles" exist:
      | name         | shortname |
      | Page manager | pmanager  |
      | Page viewer  | pviewer   |
    And the following "permission overrides" exist:
      | capability                       | permission | role     | contextlevel | reference |
      | tool/muhome:view                 | Allow      | pmanager | System       |           |
      | tool/muhome:manage               | Allow      | pmanager | System       |           |
      | moodle/cohort:view               | Allow      | pmanager | System       |           |
      | moodle/site:configview           | Allow      | pmanager | System       |           |
      | tool/muhome:view                 | Allow      | pviewer  | System       |           |
      | moodle/site:configview           | Allow      | pviewer  | System       |           |
    And the following "role assigns" exist:
      | user      | role         | contextlevel | reference |
      | manager1  | pmanager     | System       |           |
      | viewer1   | pviewer      | System       |           |

  Scenario: tool_muhome generator may create custom home pages
    When the following "tool_muhome > pages" exist:
      | name         | status   | guestvisible | uservisible |
      | Home page 1  | active   | 1            | 1           |
      | Other page 2 | draft    | 0            | 0           |
    And the following "tool_muhome > pages" exist:
      | priority | contextlevel | reference | name         | title | guestvisible | uservisible | cohortvisible | hiddenbefore     | hiddenafter      | status   |
      |          |              |           | Other page 3 |       | 0            | 1           |               |                  |                  | archived |
      | 999      | Category     | CAT3      | Other page 4 | PP4   | 1            | 0           | CH1, CH2      | ## 2025-12-24 ## | ## 2035-01-01 ## | active   |
    And I log in as "viewer1"
    And I navigate to "Appearance > Custom home pages > Home pages management" in site administration
    Then the following should exist in the "reportbuilder-table" table:
      | Page priority | Page name       | Management category | Title | Visible to guests | Visible to all users | Visible to cohorts  | Hidden before   | Hidden after   | Status   |
      | 1000          | Home page 1     | System              |       | Yes               | Yes                  |                     |                 |                | Active   |
      | 990           | Other page 2    | System              |       | No                | No                   |                     |                 |                | Draft    |
      | 980           | Other page 3    | System              |       | No                | Yes                  |                     |                 |                | Archived |
      | 999           | Other page 4    | Cat 3               | PP4   | Yes               | No                   | Cohort 1, Cohort 2  | 24/12/25, 00:00 | 1/01/35, 00:00 | Active   |
