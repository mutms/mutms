@tool @tool_mucertify @MuTMS
Feature: Automatic cohorts assignments certification tests

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
      | name                  | shortname |
      | Certification viewer  | pviewer   |
      | Certification manager | pmanager  |
    And the following "permission overrides" exist:
      | capability                     | permission | role     | contextlevel | reference |
      | tool/mucertify:view            | Allow      | pviewer  | System       |           |
      | tool/mucertify:view            | Allow      | pmanager | System       |           |
      | tool/mucertify:edit            | Allow      | pmanager | System       |           |
      | tool/mucertify:delete          | Allow      | pmanager | System       |           |
      | tool/mucertify:assign          | Allow      | pmanager | System       |           |
      | tool/mucertify:unassign        | Allow      | pmanager | System       |           |
      | moodle/cohort:view             | Allow      | pmanager | System       |           |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | manager1  | pmanager      | System       |           |
      | manager2  | pmanager      | Category     | CAT2      |
      | manager2  | pmanager      | Category     | CAT3      |
      | viewer1   | pviewer       | System       |           |
    And the following "tool_muprog > programs" exist:
      | fullname    | idnumber | category | sources |
      | Program 000 | PR0      |          | mucertify |
      | Program 001 | PR1      | Cat 1    | mucertify |
      | Program 002 | PR2      | Cat 2    | mucertify |
      | Program 003 | PR3      | Cat 3    | mucertify |
    And the following "tool_mucertify > certifications" exist:
      | fullname          | idnumber | category | program1 |
      | Certification 000 | CT0      |          | PR0      |
      | Certification 001 | CT1      | Cat 1    | PR1      |
      | Certification 002 | CT2      | Cat 2    | PR2      |
      | Certification 003 | CT3      | Cat 3    | PR3      |

  @javascript
  Scenario: Manager may enable automatic cohort assignment in certifications
    Given I log in as "manager1"
    And I am on the "tool_mucertify > All certifications management" page
    And I follow "Certification 000"
    And I click on "Assignment settings" "link" in the ".secondary-navigation" "css_element"
    And I click on "Update Automatic cohort assignment" "link"
    And I set the following fields to these values:
      | Active            | Yes                |
      | Assign to cohorts | Cohort 1, Cohort 2 |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    Then I should see "Active (Cohort 1, Cohort 2)" in the "Automatic cohort assignment" definition list item
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"
    And "Student 1" row "Source" column of "reportbuilder-table" table should contain "Automatic cohort assignment"
    And "Student 1" row "Certification status" column of "reportbuilder-table" table should contain "Not certified"
    And "Student 2" row "Source" column of "reportbuilder-table" table should contain "Automatic cohort assignment"
    And "Student 2" row "Certification status" column of "reportbuilder-table" table should contain "Not certified"
    And "Student 3" row "Source" column of "reportbuilder-table" table should contain "Automatic cohort assignment"
    And "Student 3" row "Certification status" column of "reportbuilder-table" table should contain "Not certified"
    And "Student 4" row "Source" column of "reportbuilder-table" table should contain "Automatic cohort assignment"
    And "Student 4" row "Certification status" column of "reportbuilder-table" table should contain "Not certified"
    And I should not see "Student 5"

    When I click on "Assignment settings" "link" in the ".secondary-navigation" "css_element"
    And I click on "Update Automatic cohort assignment" "link"
    And I set the following fields to these values:
      | Assign to cohorts | Cohort 1 |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    Then I should see "Active (Cohort 1)" in the "Automatic cohort assignment" definition list item
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"
    And "Student 1" row "Source" column of "reportbuilder-table" table should contain "Automatic cohort assignment"
    And "Student 1" row "Certification status" column of "reportbuilder-table" table should contain "Not certified"
    And "Student 2" row "Source" column of "reportbuilder-table" table should contain "Automatic cohort assignment"
    And "Student 2" row "Certification status" column of "reportbuilder-table" table should contain "Not certified"
    And "Student 3" row "Source" column of "reportbuilder-table" table should contain "Automatic cohort assignment"
    And "Student 3" row "Certification status" column of "reportbuilder-table" table should contain "Not certified"
    And "Student 4" row "Source" column of "reportbuilder-table" table should contain "Automatic cohort assignment"
    And "Student 4" row "Certification status" column of "reportbuilder-table" table should contain "Archived"
    And I should not see "Student 5"

    When I click on "Assignment settings" "link" in the ".secondary-navigation" "css_element"
    And I click on "Update Automatic cohort assignment" "link"
    And I set the following fields to these values:
      | Assign to cohorts | Cohort 4 |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I should see "Active (Cohort 4)" in the "Automatic cohort assignment" definition list item
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"
    And I click on "Actions" "link" in the "Student 1" "table_row"
    And I click on "Delete assignment" "link" in the "Student 1" "table_row"
    And I click on "Delete assignment" "button" in the ".modal-dialog" "css_element"
    And I click on "Actions" "link" in the "Student 2" "table_row"
    And I click on "Delete assignment" "link" in the "Student 2" "table_row"
    And I click on "Delete assignment" "button" in the ".modal-dialog" "css_element"
    And I click on "Actions" "link" in the "Student 3" "table_row"
    And I click on "Delete assignment" "link" in the "Student 3" "table_row"
    And I click on "Delete assignment" "button" in the ".modal-dialog" "css_element"
    And I click on "Actions" "link" in the "Student 4" "table_row"
    And I click on "Delete assignment" "link" in the "Student 4" "table_row"
    And I click on "Delete assignment" "button" in the ".modal-dialog" "css_element"
    And I should see "No certification assignments found"
    And I click on "Assignment settings" "link" in the ".secondary-navigation" "css_element"
    And I click on "Update Automatic cohort assignment" "link"
    And I set the following fields to these values:
      | Active              | No                |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    Then I should see "Inactive" in the "Automatic cohort assignment" definition list item
