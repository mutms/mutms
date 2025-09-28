@tool @tool_muprog @MuTMS @javascript
Feature: Program encoding upload tests

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
  Scenario: Program manager can upload CSV with custom encoding
    Given I log in as "manager2"
    And I am on the "Category 2" "tool_muprog > Program management" page

    When I click on "Upload programs" action from "Programs actions" dropdown
    And I upload "admin/tool/muprog/tests/fixtures/upload/czech.zip" file to "Files" filemanager
    And I set the following fields to these values:
      | Encoding    | ISO-8859-2 |
    And I press "Continue"
    And I set the following fields to these values:
      | usecategory | 0          |
      | contextid   | Category 2 |
    And the following should exist in the "upload_preview" table:
      | idnumber | Status | fullname       | category   |
      | PČ0      | OK     | Programíček 00 | -          |
      | PČ1      | OK     | Programíček 01 | -          |
      | PČ2      | OK     | Programíček 02 | -          |
    And I press "Upload programs"
    Then the following should exist in the "reportbuilder-table" table:
      | Program name     | Program ID | Allocations | Public |
      | Programíček 00   | PČ0        | 0           | No     |
      | Programíček 01   | PČ1        | 0           | No     |
      | Programíček 02   | PČ2        | 0           | No     |
