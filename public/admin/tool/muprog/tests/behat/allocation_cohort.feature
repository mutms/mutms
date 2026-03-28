@tool @tool_muprog @MuTMS
Feature: Visible cohorts program allocation tests

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
      | Cohort 4 | CH3      |
      | Cohort 5 | CH3      |
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
      | student4 | CH2    |
    And the following "roles" exist:
      | name            | shortname |
      | Program viewer  | pviewer   |
      | Program manager | pmanager  |
    And the following "permission overrides" exist:
      | capability                   | permission | role     | contextlevel | reference |
      | tool/muprog:view             | Allow      | pviewer  | System       |           |
      | tool/muprog:view             | Allow      | pmanager | System       |           |
      | tool/muprog:edit             | Allow      | pmanager | System       |           |
      | tool/muprog:delete           | Allow      | pmanager | System       |           |
      | tool/muprog:addcourse        | Allow      | pmanager | System       |           |
      | tool/muprog:allocate         | Allow      | pmanager | System       |           |
      | tool/muprog:manageallocation | Allow      | pmanager | System       |           |
      | moodle/cohort:view           | Allow      | pmanager | System       |           |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | manager1  | pmanager      | System       |           |
      | manager2  | pmanager      | Category     | CAT2      |
      | manager2  | pmanager      | Category     | CAT3      |
      | viewer1   | pviewer       | System       |           |
    And the following "tool_muprog > programs" exist:
      | fullname    | idnumber | category |
      | Program 000 | PR0      |          |
      | Program 001 | PR1      | Cat 1    |
      | Program 002 | PR2      | Cat 2    |
      | Program 003 | PR3      | Cat 3    |

  @javascript
  Scenario: Manager may enable automatic cohort allocation in programs
    Given I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 000"
    And I click on "Allocation settings" "link" in the ".secondary-navigation" "css_element"
    And I click on "Update Automatic cohort allocation" "link"
    And I set the following fields to these values:
      | Active           | Yes                |
      | Allocate cohorts | Cohort 1, Cohort 2 |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    Then I should see "Active (Cohort 1, Cohort 2)" in the "Automatic cohort allocation" definition list item
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"
    And "Student 1" row "Source" column of "reportbuilder-table" table should contain "Automatic cohort allocation"
    And "Student 1" row "Program status" column of "reportbuilder-table" table should contain "Open"
    And "Student 2" row "Source" column of "reportbuilder-table" table should contain "Automatic cohort allocation"
    And "Student 2" row "Program status" column of "reportbuilder-table" table should contain "Open"
    And "Student 3" row "Source" column of "reportbuilder-table" table should contain "Automatic cohort allocation"
    And "Student 3" row "Program status" column of "reportbuilder-table" table should contain "Open"
    And "Student 4" row "Source" column of "reportbuilder-table" table should contain "Automatic cohort allocation"
    And "Student 4" row "Program status" column of "reportbuilder-table" table should contain "Open"
    And I should not see "Student 5"

    When I click on "Allocation settings" "link" in the ".secondary-navigation" "css_element"
    And I click on "Update Automatic cohort allocation" "link"
    And I set the following fields to these values:
      | Allocate cohorts | Cohort 1 |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    Then I should see "Active (Cohort 1)" in the "Automatic cohort allocation" definition list item
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"
    And "Student 1" row "Source" column of "reportbuilder-table" table should contain "Automatic cohort allocation"
    And "Student 1" row "Program status" column of "reportbuilder-table" table should contain "Open"
    And "Student 2" row "Source" column of "reportbuilder-table" table should contain "Automatic cohort allocation"
    And "Student 2" row "Program status" column of "reportbuilder-table" table should contain "Open"
    And "Student 3" row "Source" column of "reportbuilder-table" table should contain "Automatic cohort allocation"
    And "Student 3" row "Program status" column of "reportbuilder-table" table should contain "Open"
    And "Student 4" row "Source" column of "reportbuilder-table" table should contain "Automatic cohort allocation"
    And "Student 4" row "Program status" column of "reportbuilder-table" table should contain "Archived"
    And I should not see "Student 5"

    When I click on "Allocation settings" "link" in the ".secondary-navigation" "css_element"
    And I click on "Update Automatic cohort allocation" "link"
    And I set the following fields to these values:
      | Allocate cohorts | Cohort 4 |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I should see "Active (Cohort 4)" in the "Automatic cohort allocation" definition list item
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"
    And I click on "Actions" "link" in the "Student 1" "table_row"
    And I click on "Delete program allocation" "link" in the "Student 1" "table_row"
    And I click on "Delete program allocation" "button" in the ".modal-dialog" "css_element"
    And I click on "Actions" "link" in the "Student 2" "table_row"
    And I click on "Delete program allocation" "link" in the "Student 2" "table_row"
    And I click on "Delete program allocation" "button" in the ".modal-dialog" "css_element"
    And I click on "Actions" "link" in the "Student 3" "table_row"
    And I click on "Delete program allocation" "link" in the "Student 3" "table_row"
    And I click on "Delete program allocation" "button" in the ".modal-dialog" "css_element"
    And I click on "Actions" "link" in the "Student 4" "table_row"
    And I click on "Delete program allocation" "link" in the "Student 4" "table_row"
    And I click on "Delete program allocation" "button" in the ".modal-dialog" "css_element"
    And I should see "No user allocations found"
    And I click on "Allocation settings" "link" in the ".secondary-navigation" "css_element"
    And I click on "Update Automatic cohort allocation" "link"
    And I set the following fields to these values:
      | Active              | No                |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    Then I should see "Inactive" in the "Automatic cohort allocation" definition list item
