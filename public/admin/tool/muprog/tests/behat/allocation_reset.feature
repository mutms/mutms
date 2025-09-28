@tool @tool_muprog @MuTMS
Feature: Program progress reset by managers tests

  Background:
    Given unnecessary Admin bookmarks block gets deleted
    And the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
      | Cat 3 | CAT2     | CAT3     |
    And the following "courses" exist:
      | fullname | shortname | format | category | enablecompletion | showcompletionconditions |
      | Course 1 | C1        | topics | CAT1     | 1                | 1                        |
      | Course 2 | C2        | topics | CAT2     | 1                | 1                        |
      | Course 3 | C3        | topics | CAT3     | 1                | 1                        |
      | Course 4 | C4        | topics | CAT1     | 1                | 1                        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | manager1 | Manager   | 1        | manager1@example.com |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
      | student3 | Student   | 3        | student3@example.com |
    And the following "roles" exist:
      | name            | shortname |
      | Program viewer  | pviewer   |
      | Program manager | pmanager  |
    And the following "permission overrides" exist:
      | capability                       | permission | role     | contextlevel | reference |
      | tool/muprog:view              | Allow      | pviewer  | System       |           |
      | tool/muprog:view              | Allow      | pmanager | System       |           |
      | tool/muprog:edit              | Allow      | pmanager | System       |           |
      | tool/muprog:delete            | Allow      | pmanager | System       |           |
      | tool/muprog:manageevidence    | Allow      | pmanager | System       |           |
      | tool/muprog:reset             | Allow      | pmanager | System       |           |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | manager1  | pmanager      | System       |           |
    And the following "tool_muprog > programs" exist:
      | fullname    | idnumber | category | publicaccess |
      | Program 000 | PR0      |          | 1            |
      | Program 001 | PR1      | Cat 1    | 1            |
    And the following "tool_muprog > program_items" exist:
      | program     | parent     | course   | fullname   | sequencetype     | minprerequisites |
      | Program 000 |            |          | First set  | All in order     |                  |
      | Program 000 | First set  | Course 1 |            |                  |                  |
    And the following "tool_muprog > program_allocations" exist:
      | program     | user     |
      | Program 000 | student1 |
      | Program 000 | student2 |
      | Program 000 | student3 |

  @javascript
  Scenario: Program manager may reset program completion
    Given I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 000"
    And I follow "Users"
    And I follow "Student 1"
    And I click on "Update other evidence" "link" in the "Program 000" "table_row"
    And I set the following fields to these values:
      | evidencetimecompleted[enabled] | 1        |
      | Details                        | no need! |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I should see "Completed" in the "Program status" definition list item
    And I should see "no need!"

    When I am on the "tool_muprog > All programs management" page
    And I follow "Program 000"
    And I follow "Users"
    And I follow "Student 1"
    And I click on "Reset program progress" action from "Allocation actions" dropdown
    And I set the following fields to these values:
      | Reset type         | Standard course purge |
      | Update allocation  | 0                     |
    And I click on "Reset program progress" "button" in the ".modal-dialog" "css_element"
    Then I should see "Not set" in the "Program completion date" definition list item

    When I click on "Reset program progress" action from "Allocation actions" dropdown
    And I set the following fields to these values:
      | Reset type         | Full course purge |
      | Update allocation  | 1                 |
      | timestart[year]    | 2023 |
      | timestart[day]     | 5    |
      | timestart[month]   | 11   |
      | timestart[hour]    | 09   |
      | timestart[minute]  | 00   |
      | timedue[enabled]   | 1    |
      | timedue[year]      | 2024 |
      | timedue[day]       | 22   |
      | timedue[month]     | 1    |
      | timedue[hour]      | 09   |
      | timedue[minute]    | 00   |
      | timeend[enabled]   | 1    |
      | timeend[year]      | 2030 |
      | timeend[month]     | 12   |
      | timeend[day]       | 31   |
      | timeend[hour]      | 09   |
      | timeend[minute]    | 00   |
    And I click on "Reset program progress" "button" in the ".modal-dialog" "css_element"
    Then I should see "Not set" in the "Program completion date" definition list item
    And I should see "Sunday, 5 November 2023, 9:00 AM" in the "Program start" definition list item
    And I should see "Monday, 22 January 2024, 9:00 AM" in the "Program due" definition list item
    And I should see "Tuesday, 31 December 2030, 9:00 AM" in the "Program end" definition list item

    When I click on "Reset program progress" action from "Allocation actions" dropdown
    And I set the following fields to these values:
      | Update allocation  | 1    |
      | timestart[year]    | 2024 |
      | timestart[day]     | 5    |
      | timestart[month]   | 11   |
      | timestart[hour]    | 09   |
      | timestart[minute]  | 00   |
      | timedue[enabled]   | 0    |
      | timeend[enabled]   | 0    |
    And I click on "Reset program progress" "button" in the ".modal-dialog" "css_element"
    Then I should see "Not set" in the "Program completion date" definition list item
    And I should see "Tuesday, 5 November 2024, 9:00 AM" in the "Program start" definition list item
    And I should see "Not set" in the "Program due" definition list item
    And I should see "Not set" in the "Program end" definition list item
