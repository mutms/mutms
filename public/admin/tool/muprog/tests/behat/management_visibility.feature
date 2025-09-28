@tool @tool_muprog @MuTMS
Feature: Program visibility management tests

  Background:
    Given unnecessary Admin bookmarks block gets deleted
    And the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
      | Cat 3 | 0        | CAT3     |
      | Cat 4 | CAT3     | CAT4     |
    And the following "cohorts" exist:
      | name     | idnumber |
      | Cohort 1 | CH1      |
      | Cohort 2 | CH2      |
      | Cohort 3 | CH3      |
    And the following "courses" exist:
      | fullname | shortname | format | category |
      | Course 1 | C1        | topics | CAT1     |
      | Course 2 | C2        | topics | CAT2     |
      | Course 3 | C3        | topics | CAT3     |
      | Course 4 | C4        | topics | CAT4     |
      | Course 5 | C5        | topics | CAT4     |
      | Course 6 | C6        | topics | CAT4     |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | manager1 | Manager   | 1        | manager1@example.com |
      | manager2 | Manager   | 2        | manager2@example.com |
      | viewer1  | Viewer    | 1        | viewer1@example.com  |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
      | student3 | Student   | 3        | student3@example.com |
      | student4 | Student   | 4        | student4@example.com |
      | student5 | Student   | 5        | student5@example.com |
    And the following "cohort members" exist:
      | user     | cohort |
      | student1 | CH1    |
      | student2 | CH1    |
      | student3 | CH1    |
      | student2 | CH2    |
    And the following "roles" exist:
      | name            | shortname |
      | Program viewer  | pviewer   |
      | Program manager | pmanager  |
    And the following "permission overrides" exist:
      | capability                     | permission | role     | contextlevel | reference |
      | tool/muprog:view            | Allow      | pviewer  | System       |           |
      | tool/muprog:view            | Allow      | pmanager | System       |           |
      | tool/muprog:edit            | Allow      | pmanager | System       |           |
      | tool/muprog:delete          | Allow      | pmanager | System       |           |
      | tool/muprog:addcourse       | Allow      | pmanager | System       |           |
      | tool/muprog:allocate        | Allow      | pmanager | System       |           |
      | moodle/cohort:view             | Allow      | pmanager | System       |           |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | manager1  | pmanager      | System       |           |
      | manager2  | pmanager      | Category     | CAT2      |
      | manager2  | pmanager      | Category     | CAT3      |
      | viewer1   | pviewer       | System       |           |

  @javascript
  Scenario: Manager may update program Catalogue visibility
    Given the following "tool_muprog > programs" exist:
      | fullname    | idnumber | category |
      | Program 000 | PR0      |          |
      | Program 001 | PR1      | Cat 1    |
      | Program 002 | PR2      | Cat 2    |
      | Program 003 | PR3      | Cat 3    |
    And I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page
    And "Program 000" row "Public" column of "reportbuilder-table" table should contain "No"
    And "Program 001" row "Public" column of "reportbuilder-table" table should contain "No"
    And "Program 002" row "Public" column of "reportbuilder-table" table should contain "No"
    And "Program 003" row "Public" column of "reportbuilder-table" table should contain "No"

    When I follow "Program 000"
    And I follow "Catalogue visibility"
    And I press "Edit"
    And the following fields in the ".modal-dialog" "css_element" match these values:
      | Public             | No             |
      | Visible to cohorts |                |
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Public             | Yes            |
    And I click on "Update program" "button" in the ".modal-dialog" "css_element"
    Then I press "Edit"
    And the following fields in the ".modal-dialog" "css_element" match these values:
      | Public             | Yes            |
    And I click on "Cancel" "button" in the ".modal-dialog" "css_element"
    And I am on the "tool_muprog > All programs management" page
    And "Program 000" row "Public" column of "reportbuilder-table" table should contain "Yes"

    When I click on "No" "link" in the "Program 001" "table_row"
    And I press "Edit"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Visible to cohorts | Cohort 1 |
    And I click on "Update program" "button" in the ".modal-dialog" "css_element"
    Then I should see "Cohort 1"
    And I press "Edit"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Visible to cohorts | Cohort 2 |
    And I click on "Update program" "button" in the ".modal-dialog" "css_element"
    And I should see "Cohort 2"
    And I am on the "tool_muprog > All programs management" page
    And "Program 001" row "Public" column of "reportbuilder-table" table should contain "No"

    When I follow "Program 002"
    And I follow "Catalogue visibility"
    And I press "Edit"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Visible to cohorts | Cohort 2, Cohort 1 |
    And I click on "Update program" "button" in the ".modal-dialog" "css_element"
    Then I should see "Cohort 1"
    And I should see "Cohort 2"

    When I am on the "tool_muprog > Program catalogue" page
    Then I should see "Program 000"
    And I should not see "Program 001"
    And I should not see "Program 002"
    And I should not see "Program 003"
    And I log out

    When I log in as "student1"
    And I am on the "tool_muprog > Program catalogue" page
    Then I should see "Program 000"
    And I should not see "Program 001"
    And I should see "Program 002"
    And I should not see "Program 003"
    And I log out

    When I log in as "student2"
    And I am on the "tool_muprog > Program catalogue" page
    Then I should see "Program 000"
    And I should see "Program 001"
    And I should see "Program 002"
    And I should not see "Program 003"
    And I log out

    When I log in as "student3"
    And I am on the "tool_muprog > Program catalogue" page
    Then I should see "Program 000"
    And I should not see "Program 001"
    And I should see "Program 002"
    And I should not see "Program 003"
    And I log out

    When I log in as "student4"
    And I am on the "tool_muprog > Program catalogue" page
    Then I should see "Program 000"
    And I should not see "Program 001"
    And I should not see "Program 002"
    And I should not see "Program 003"
    And I log out
