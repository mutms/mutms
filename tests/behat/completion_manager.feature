@tool @tool_muprog @MuTMS
Feature: Program completion by managers tests

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
      | admin1   | Admin     | 1        | admin1@example.com   |
      | manager1 | Manager   | 1        | manager1@example.com |
      | viewer1  | Viewer    | 1        | viewer1@example.com  |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
      | student3 | Student   | 3        | student3@example.com |
    And the following "roles" exist:
      | name            | shortname |
      | Program viewer  | pviewer   |
      | Program manager | pmanager  |
      | Program admin   | padmin    |
    And the following "permission overrides" exist:
      | capability                       | permission | role     | contextlevel | reference |
      | tool/muprog:view              | Allow      | pviewer  | System       |           |
      | tool/muprog:view              | Allow      | pmanager | System       |           |
      | tool/muprog:edit              | Allow      | pmanager | System       |           |
      | tool/muprog:delete            | Allow      | pmanager | System       |           |
      | tool/muprog:manageevidence    | Allow      | pmanager | System       |           |
      | tool/muprog:view              | Allow      | padmin   | System       |           |
      | tool/muprog:edit              | Allow      | padmin   | System       |           |
      | tool/muprog:delete            | Allow      | padmin   | System       |           |
      | tool/muprog:allocate          | Allow      | pmanager | System       |           |
      | tool/muprog:deallocate        | Allow      | pmanager | System       |           |
      | tool/muprog:manageevidence    | Allow      | padmin   | System       |           |
      | tool/muprog:manageallocation  | Allow      | pmanager | System       |           |
      | tool/muprog:admin             | Allow      | padmin   | System       |           |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | admin1    | padmin        | System       |           |
      | manager1  | pmanager      | System       |           |
      | viewer1   | pviewer       | System       |           |
    And the following "tool_muprog > programs" exist:
      | fullname    | idnumber | category | publicaccess |
      | Program 000 | PR0      |          | 1            |
      | Program 001 | PR1      | Cat 1    | 1            |
    And the following "tool_muprog > program_items" exist:
      | program     | parent     | course   | fullname   | sequencetype     | minprerequisites |
      | Program 000 |            |          | First set  | All in order     |                  |
      | Program 000 | First set  | Course 1 |            |                  |                  |
      | Program 000 | First set  | Course 2 |            |                  |                  |
      | Program 000 |            |          | Second set | At least X       | 1                |
      | Program 000 | Second set | Course 3 |            |                  |                  |
      | Program 000 | Second set | Course 4 |            |                  |                  |
    And the following "tool_muprog > program_allocations" exist:
      | program     | user     |
      | Program 000 | student1 |
      | Program 000 | student2 |
      | Program 000 | student3 |

  @javascript
  Scenario: Program manager may alter completion with evidence
    Given I log in as "manager1"

    When I am on the "tool_muprog > All programs management" page
    And I follow "Program 000"
    And I follow "Users"
    And I follow "Student 1"
    And I click on "Update other evidence" "link" in the "Program 000" "table_row"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | evidencetimecompleted[enabled] | 1        |
      | Details                        | no need! |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    Then I should see "Completed" in the "Program status" definition list item
    And I should see "no need!"

    When I click on "Update other evidence" "link" in the "Program 000" "table_row"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | evidencetimecompleted[enabled] | 0        |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    Then I should see "Completed" in the "Program status" definition list item
    And I should not see "no need!"

    When I click on "Update other evidence" "link" in the "Program 000" "table_row"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | evidencetimecompleted[enabled] | 0        |
      | itemrecalculate                | 1        |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    Then I should see "Open" in the "Program status" definition list item

    When I am on the "tool_muprog > All programs management" page
    And I follow "Program 000"
    And I follow "Users"
    And I follow "Student 2"
    And I click on "Update other evidence" "link" in the "Course 1" "table_row"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | evidencetimecompleted[enabled] | 1        |
      | Details                        | no need! |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I should see "Open" in the "Program status" definition list item
    And I click on "Update other evidence" "link" in the "Course 3" "table_row"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | evidencetimecompleted[enabled] | 1        |
      | Details                        | no need! |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I should see "Open" in the "Program status" definition list item
    And I click on "Update other evidence" "link" in the "Course 2" "table_row"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | evidencetimecompleted[enabled] | 1        |
      | Details                        | no need! |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    Then I should see "Completed" in the "Program status" definition list item
    And I should see "no need!"

  @javascript
  Scenario: Program admin may mark override completion
    Given I log in as "admin1"

    When I am on the "tool_muprog > All programs management" page
    And I follow "Program 000"
    And I follow "Users"
    And I follow "Student 1"
    And I click on "Override program completion" action from "Allocation actions" dropdown
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | timecompleted[enabled] | 1    |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    Then I should see "Completed" in the "Program status" definition list item

    When I am on the "tool_muprog > All programs management" page
    And I follow "Program 000"
    And I follow "Users"
    And I follow "Student 2"
    And I click on "Override completion" "link" in the "Program 000" "table_row"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | timecompleted[enabled] | 1    |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    Then I should see "Completed" in the "Program status" definition list item

    When I am on the "tool_muprog > All programs management" page
    And I follow "Program 000"
    And I follow "Users"
    And I follow "Student 3"
    And I click on "Override completion" "link" in the "Course 1" "table_row"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | timecompleted[enabled] | 1    |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I should see "Open" in the "Program status" definition list item
    And I click on "Override completion" "link" in the "Course 3" "table_row"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | timecompleted[enabled] | 1    |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I should see "Open" in the "Program status" definition list item
    And I click on "Override completion" "link" in the "Course 2" "table_row"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | timecompleted[enabled] | 1    |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    Then I should see "Completed" in the "Program status" definition list item
