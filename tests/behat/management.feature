@block @block_muprogmyoverview @tool_muprog @javascript @MuTMS
Feature: Program overview block allows viewer to access Program managment

  Background:
    Given unnecessary Admin bookmarks block gets deleted
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | viewer1  | Viewer    | 1        | viewer1@example.com  |
    And the following "roles" exist:
      | name            | shortname |
      | Program viewer  | pviewer   |
    And the following "permission overrides" exist:
      | capability                     | permission | role     | contextlevel | reference |
      | tool/muprog:view            | Allow      | pviewer  | System       |           |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | viewer1   | pviewer       | System       |           |
    And the following "tool_muprog > programs" exist:
      | fullname    | idnumber | category | publicaccess |
      | Program 000 | PR0      |          | 1            |
    And the following "tool_muprog > program_allocations" exist:
      | program     | user    |
      | Program 000 | viewer1 |

  @javascript
  Scenario: Program viewer with allocation may access Program management from Program overview block
    Given I log in as "viewer1"
    And I click on "My programs" "link" in the ".primary-navigation" "css_element"

    When I click on "Program management" action from "Actions" dropdown
    Then the following should exist in the "reportbuilder-table" table:
      | Program name | Program ID | Category |
      | Program 000  | PR0        | System   |
