@tool @tool_muprog @MuTMS
Feature: Completed programs allocation tests

  Background:
    Given unnecessary Admin bookmarks block gets deleted
    And the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
      | Cat 3 | 0        | CAT3     |
      | Cat 4 | CAT3     | CAT4     |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | manager1 | Manager   | 1        | manager1@example.com |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
    And the following "roles" exist:
      | name            | shortname |
      | Program manager | pmanager  |
    And the following "permission overrides" exist:
      | capability                   | permission | role     | contextlevel | reference |
      | tool/muprog:view             | Allow      | pmanager | System       |           |
      | tool/muprog:edit             | Allow      | pmanager | System       |           |
      | tool/muprog:allocate         | Allow      | pmanager | System       |           |
      | tool/muprog:manageallocation | Allow      | pmanager | System       |           |
      | tool/muprog:manageevidence   | Allow      | pmanager | System       |           |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | manager1  | pmanager      | System       |           |
    And the following "tool_muprog > programs" exist:
      | fullname    | idnumber | category | sources   |
      | Program 000 | PR0      |          | manual    |
      | Program 001 | PR1      | Cat 1    |           |
      | Program 002 | PR2      | Cat 2    |           |
    And the following "tool_muprog > program_allocations" exist:
      | program     | user     |
      | Program 000 | student1 |
      | Program 000 | student2 |

  @javascript
  Scenario: Manager may enable completed program allocation in programs
    Given I log in as "manager1"
    And I am on the "Program 000" "tool_muprog > Program" page
    And I follow "Users"
    And I follow "Student 1"
    And I click on "Update other evidence" "link" in the "Program 000" "table_row"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | evidencetimecompleted[enabled] | 1        |
      | Details                        | no need! |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I should see "Completed" in the "Program status" definition list item

    When I am on the "Program 001" "tool_muprog > Program" page
    And I click on "Allocation settings" "link" in the ".secondary-navigation" "css_element"
    And I click on "Update Completed program" "link"
    And I set the following fields to these values:
      | Active              | Yes         |
      | Program to complete | Program 000 |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    Then I should see "Active" in the "Completed program" definition list item
    And I should see "Program 000" in the "Completed program" definition list item
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"
    And "Student 1" row "Source" column of "reportbuilder-table" table should contain "Completed program"
    And "Student 1" row "Program status" column of "reportbuilder-table" table should contain "Open"
    And I should not see "Student 2"
