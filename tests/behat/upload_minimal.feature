@tool @tool_muprog @MuTMS @javascript
Feature: Program minimal upload tests

  Background:
    Given unnecessary Admin bookmarks block gets deleted
    # Category 1 with no idnumber is expected at the top level - former Miscellaneous category.
    And the following "categories" exist:
      | name       | category | idnumber |
      | Category 2 | 0        | CAT2     |
      | Category 3 | 0        | CAT3     |
    And the following "courses" exist:
      | fullname  | shortname  | category |
      | Course 01 | C01        | CAT2     |
      | Course 02 | C02        | CAT2     |
      | Course 03 | C03        | CAT3     |
      | Course 04 | C04        | CAT3     |
      | Course 05 | C05        | CAT3     |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | manager1 | Manager   | 1        | manager1@example.com |
      | manager2 | Manager   | 2        | manager1@example.com |
    And the following "roles" exist:
      | name            | shortname |
      | Program viewer  | pviewer   |
      | Program manager | pmanager  |
    And the following "permission overrides" exist:
      | capability                     | permission | role     | contextlevel | reference |
      | tool/muprog:view            | Allow      | pmanager | System       |           |
      | tool/muprog:upload          | Allow      | pmanager | System       |           |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | manager1  | pmanager      | System       |           |
      | manager2  | pmanager      | Category     | CAT2      |
      | manager2  | pmanager      | Category     | CAT3      |

  @_file_upload
  Scenario: Program manager can upload minimal programs via JSON
    Given I log in as "manager2"
    And I am on the "Category 2" "tool_muprog > Program management" page

    When I click on "Upload programs" action from "Programs actions" dropdown
    And I upload "admin/tool/muprog/tests/fixtures/upload/minimal.json" file to "Files" filemanager
    And I press "Continue"
    And I set the following fields to these values:
      | usecategory | 0          |
      | contextid   | Category 2 |
    And the following should exist in the "upload_preview" table:
      | idnumber | Status | fullname   | category   |
      | P00      | OK     | Program 00 | -          |
      | P01      | OK     | Program 01 | -          |
      | P02      | OK     | Program 02 | -          |
    And I press "Upload programs"
    Then the following should exist in the "reportbuilder-table" table:
      | Program name | Program ID | Courses | Allocations | Public |
      | Program 00   | P00        | 2       | 0           | No     |
      | Program 01   | P01        | 0       | 0           | No     |
      | Program 02   | P02        | 0       | 0           | No     |
    And I follow "Program 00"
    And I should see "Program 00" in the "Program name" definition list item
    And I should see "No" in the "Archived" definition list item
    And I follow "Content"
    And the following should exist in the "program_content" table:
      | Item       | Points | Completion type         |
      | Program 00 |        | All in any order        |
      | Course 01  | 1      |                         |
      | Course 02  | 1      |                         |
    And I follow "Allocation settings"
    And I should see "Not set" in the "Allocation start" definition list item
    And I should see "Not set" in the "Allocation end" definition list item
    And I should see "Start immediately after allocation" in the "Program start" definition list item
    And I should see "Not set" in the "Program due" definition list item
    And I should see "Not set" in the "Program end" definition list item
    And I should see "Inactive" in the "Manual allocation" definition list item
    And I should see "Inactive" in the "Self allocation" definition list item
    And I should see "Inactive" in the "Requests with approval" definition list item
    And I should see "Inactive" in the "Automatic cohort allocation" definition list item

  @_file_upload
  Scenario: Program manager can upload minimal programs via CSV
    Given I log in as "manager2"
    And I am on the "Category 2" "tool_muprog > Program management" page

    When I click on "Upload programs" action from "Programs actions" dropdown
    And I upload "admin/tool/muprog/tests/fixtures/upload/minimal.csv" file to "Files" filemanager
    And I press "Continue"
    And I set the following fields to these values:
      | usecategory | 0          |
      | contextid   | Category 2 |
    And the following should exist in the "upload_preview" table:
      | idnumber | Status | fullname   | category   |
      | P00      | OK     | Program 00 | -          |
      | P01      | OK     | Program 01 | -          |
      | P02      | OK     | Program 02 | -          |
    And I press "Upload programs"
    Then the following should exist in the "reportbuilder-table" table:
      | Program name | Program ID | Courses | Allocations | Public |
      | Program 00   | P00        | 0       | 0           | No     |
      | Program 01   | P01        | 0       | 0           | No     |
      | Program 02   | P02        | 0       | 0           | No     |
