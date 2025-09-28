@tool @tool_mucertify @MuTMS
Feature: Manual certification assignment tests

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
    And the following "tool_muprog > programs" exist:
      | fullname    | idnumber | category | publicaccess | sources    |
      | Program 000 | PR0      |          | 0            | mucertify  |
      | Program 001 | PR1      | Cat 1    | 0            | mucertify  |
      | Program 002 | PR2      | Cat 2    | 0            | mucertify  |
      | Program 003 | PR3      | Cat 3    | 0            | mucertify  |
    And the following "users" exist:
      | username | firstname | lastname | email                | idnumber |
      | manager  | Site      | Manager  | manager@example.com  | m        |
      | manager1 | Manager   | 1        | manager1@example.com | m1       |
      | manager2 | Manager   | 2        | manager2@example.com | m2       |
      | viewer1  | Viewer    | 1        | viewer1@example.com  | v1       |
      | student1 | Student   | 1        | student1@example.com | s1       |
      | student2 | Student   | 2        | student2@example.com | s2       |
      | student3 | Student   | 3        | student3@example.com | s3       |
      | student4 | Student   | 4        | student4@example.com | s4       |
      | student5 | Student   | 5        | student5@example.com | s5       |
    And the following "cohort members" exist:
      | user     | cohort |
      | student1 | CH1    |
      | student2 | CH1    |
      | student3 | CH1    |
      | student2 | CH2    |
    And the following "roles" exist:
      | name            | shortname |
      | certification viewer  | pviewer   |
      | certification manager | pmanager  |
    And the following "permission overrides" exist:
      | capability                           | permission | role     | contextlevel | reference |
      | tool/mucertify:view                  | Allow      | pviewer  | System       |           |
      | tool/mucertify:view                  | Allow      | pmanager | System       |           |
      | tool/mucertify:edit                  | Allow      | pmanager | System       |           |
      | tool/mucertify:delete                | Allow      | pmanager | System       |           |
      | tool/mucertify:assign                | Allow      | pmanager | System       |           |
      | tool/mucertify:unassign              | Allow      | pmanager | System       |           |
      | moodle/cohort:view                   | Allow      | pmanager | System       |           |
      | moodle/site:configview               | Allow      | pmanager | System       |           |
      | tool/mucertify:configurecustomfields | Allow      | pmanager | System       |           |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | manager   | manager       | System       |           |
      | manager1  | pmanager      | System       |           |
      | manager2  | pmanager      | Category     | CAT2      |
      | manager2  | pmanager      | Category     | CAT3      |
      | viewer1   | pviewer       | System       |           |
    And the following "tool_mucertify > certifications" exist:
      | fullname          | idnumber | category | program1 |
      | Certification 000 | CT0      |          | PR0      |
      | Certification 001 | CT1      | Cat 1    | PR1      |
      | Certification 002 | CT2      | Cat 2    | PR2      |
      | Certification 003 | CT3      | Cat 3    | PR3      |

  @javascript
  Scenario: Manager may assign users manually to certification
    Given I log in as "manager1"
    And I am on the "tool_mucertify > All certifications management" page
    And I follow "Certification 000"
    And I click on "Assignment settings" "link" in the ".secondary-navigation" "css_element"
    And I click on "Update Manual assignment" "link"
    And I set the following fields to these values:
      | Active | Yes |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I should see "Active" in the "Manual assignment" definition list item
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"

    When I press "Assign users"
    And I set the following fields to these values:
      | Users | Student 1, Student 5 |
    And I click on "Assign users" "button" in the ".modal-dialog" "css_element"
    Then "Student 1" row "Source" column of "reportbuilder-table" table should contain "Manual assignment"
    And "Student 5" row "Source" column of "reportbuilder-table" table should contain "Manual assignment"
    And I should not see "Student 2"
    And I should not see "Student 3"
    And I should not see "Student 4"

    When I press "Assign users"
    And I set the following fields to these values:
      | Cohort | Cohort 2 |
    And I click on "Assign users" "button" in the ".modal-dialog" "css_element"
    Then "Student 1" row "Source" column of "reportbuilder-table" table should contain "Manual assignment"
    And "Student 2" row "Source" column of "reportbuilder-table" table should contain "Manual assignment"
    And "Student 5" row "Source" column of "reportbuilder-table" table should contain "Manual assignment"
    And I should not see "Student 3"
    And I should not see "Student 4"

    And I click on "Actions" "link" in the "Student 2" "table_row"
    When I click on "Archive assignment" "link" in the "Student 2" "table_row"
    And I click on "Archive assignment" "button" in the ".modal-dialog" "css_element"
    Then "Student 2" row "Archived" column of "reportbuilder-table" table should contain "Yes"

    And I click on "Actions" "link" in the "Student 2" "table_row"
    When I click on "Restore assignment" "link" in the "Student 2" "table_row"
    And I click on "Restore assignment" "button" in the ".modal-dialog" "css_element"
    Then "Student 2" row "Archived" column of "reportbuilder-table" table should contain "No"

    And I click on "Actions" "link" in the "Student 2" "table_row"
    And I click on "Archive assignment" "link" in the "Student 2" "table_row"
    And I click on "Archive assignment" "button" in the ".modal-dialog" "css_element"
    And I click on "Actions" "link" in the "Student 2" "table_row"
    When I click on "Delete assignment" "link" in the "Student 2" "table_row"
    And I click on "Cancel" "button" in the ".modal-dialog" "css_element"
    Then "Student 2" row "Source" column of "reportbuilder-table" table should contain "Manual assignment"

    And I click on "Actions" "link" in the "Student 2" "table_row"
    When I click on "Delete assignment" "link" in the "Student 2" "table_row"
    And I click on "Delete assignment" "button" in the ".modal-dialog" "css_element"
    Then I should not see "Student 2"

  @javascript @tool_mutenancy
  Scenario: Tenant manager may assign users manually to certification
    Given I skip tests if "tool_mutenancy" is not installed
    Given the following "tool_mutenancy > tenants" exist:
      | name     | idnumber | category | assoccohort |
      | Tenant 1 | ten1     | CAT1     | CH1         |
      | Tenant 2 | ten2     | CAT2     |             |
    And the following "users" exist:
      | username | firstname | lastname | email                | tenant   |
      | tu1      | Tenant 1  | Student  | tu1@example.com      | ten1     |
      | tu2      | Tenant 2  | Student  | tu2@example.com      | ten2     |
    And I log in as "manager"

    And I am on the "tool_mucertify > All certifications management" page
    And I follow "Certification 000"
    And I click on "Assignment settings" "link" in the ".secondary-navigation" "css_element"
    And I click on "Update Manual assignment" "link"
    And I set the following fields to these values:
      | Active | Yes |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I should see "Active" in the "Manual assignment" definition list item
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"
    When I press "Assign users"
    And I set the following fields to these values:
      | Users | Student 1 |
    And I click on "Assign users" "button" in the ".modal-dialog" "css_element"
    Then "Student 1" row "Source" column of "reportbuilder-table" table should contain "Manual assignment"

    And I am on the "tool_mucertify > All certifications management" page
    And I follow "Certification 001"
    And I click on "Assignment settings" "link" in the ".secondary-navigation" "css_element"
    And I click on "Update Manual assignment" "link"
    And I set the following fields to these values:
      | Active | Yes |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I should see "Active" in the "Manual assignment" definition list item
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"

    When I press "Assign users"
    And I set the following fields to these values:
      | Users | Student 1 |
    And I click on "Assign users" "button" in the ".modal-dialog" "css_element"
    And "Student 1" row "Source" column of "reportbuilder-table" table should contain "Manual assignment"
    And I click on "Switch tenant" "link"
    And I set the following fields to these values:
      | Tenant      | Tenant 1         |
    And I click on "Switch tenant" "button" in the ".modal-dialog" "css_element"

    And I am on the "tool_mucertify > All certifications management" page
    And I follow "Certification 000"
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"

    When I press "Assign users"
    And I set the following fields to these values:
      | Users | Tenant 1 Student |
    And I click on "Assign users" "button" in the ".modal-dialog" "css_element"
    Then "Tenant 1 Student" row "Source" column of "reportbuilder-table" table should contain "Manual assignment"

    And I am on the "tool_mucertify > All certifications management" page
    And I follow "Certification 001"
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"

    When I press "Assign users"
    And I set the following fields to these values:
      | Users | Tenant 1 Student |
    And I click on "Assign users" "button" in the ".modal-dialog" "css_element"
    Then "Tenant 1 Student" row "Source" column of "reportbuilder-table" table should contain "Manual assignment"

  @javascript @_file_upload
  Scenario: Manager may assign users in bulk using csv to certification
    Given I log in as "manager1"
    And I am on the "tool_mucertify > All certifications management" page
    And I follow "Certification 000"
    And I click on "Assignment settings" "link" in the ".secondary-navigation" "css_element"
    And I click on "Update Manual assignment" "link"
    And I set the following fields to these values:
      | Active | Yes |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"

    When I click on "Upload assignments" action from "User actions" dropdown
    And I upload "admin/tool/mucertify/tests/fixtures/assign.csv" file to "CSV file" filemanager
    And I click on "Continue" "button" in the ".modal-dialog" "css_element"
    And the following fields match these values:
      | User identification column | username |
      | User mapping via           | Username |
      | First line is header       | 1        |
    And I click on "Upload assignments" "button" in the ".modal-dialog" "css_element"
    Then I should see "4 users were assigned to certification"
    And I should see "1 errors detected when assigning certification"

    When I click on "Upload assignments" action from "User actions" dropdown
    And I upload "admin/tool/mucertify/tests/fixtures/assign.csv" file to "CSV file" filemanager
    And I click on "Continue" "button" in the ".modal-dialog" "css_element"
    And I set the following fields to these values:
      | User identification column | email         |
      | User mapping via           | Email address |
      | First line is header       | 1             |
    And I click on "Upload assignments" "button" in the ".modal-dialog" "css_element"
    Then I should see "3 users were already assigned to certification"
    And I should see "2 errors detected when assigning certification"

    Given I am on the "tool_mucertify > All certifications management" page
    And I follow "Certification 001"
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"
    And I click on "Assignment settings" "link" in the ".secondary-navigation" "css_element"
    And I click on "Update Manual assignment" "link"
    And I set the following fields to these values:
      | Active | Yes |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"

    When I click on "Upload assignments" action from "User actions" dropdown
    And I upload "admin/tool/mucertify/tests/fixtures/assign.csv" file to "CSV file" filemanager
    And I click on "Continue" "button" in the ".modal-dialog" "css_element"
    And I set the following fields to these values:
     | Window opening time column    | timewindowstart |
     | Certification due time column | timewindowdue   |
     | Window closing time column    | timewindowend   |
    And I click on "Upload assignments" "button" in the ".modal-dialog" "css_element"
    Then I should see "3 users were assigned to certification"
    And I should see "2 errors detected when assigning certification"
    And I click on "Student 1" "link"
    And the following should exist in the "reportbuilder-table" table:
      | Program     | Window opening  | Certification due | Window closing | Expiration  |
      | Program 001 | 11/10/24, 00:00	| 2/01/25, 00:00    | 2/07/25, 00:00 | Not set     |

  @javascript
  Scenario: Set up, add and update custom fields for certification assignments
    And the following "permission overrides" exist:
      | capability                           | permission | role     | contextlevel | reference |
      | tool/mucertify:admin                 | Allow      | pmanager | System       |           |
    And I log in as "manager1"
    And I navigate to "Certifications > Certification assignment custom fields" in site administration
    And I press "Add a new category"
    And I click on "Add a new custom field" "link"
    And I click on "Short text" "link"
    And I set the following fields to these values:
      | Name                                     | Test field 1 |
      | Short name                               | testfield1   |
    And I click on "Save changes" "button" in the "Adding a new Short text" "dialogue"
    And I click on "Add a new custom field" "link"
    And I click on "Short text" "link"
    And I set the following fields to these values:
      | Name                                     | Test field 2 |
      | Short name                               | testfield2   |
      | Assignee                                 | 1            |
    And I click on "Save changes" "button" in the "Adding a new Short text" "dialogue"

    And I am on the "tool_mucertify > All certifications management" page
    And I follow "Certification 000"
    And I click on "Assignment settings" "link" in the ".secondary-navigation" "css_element"
    And I click on "Update Manual assignment" "link"
    And I set the following fields to these values:
      | Active | Yes |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I should see "Active" in the "Manual assignment" definition list item
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"

    When I press "Assign users"
    And I set the following fields to these values:
      | Users        | Student 1 |
      | Test field 1 | Prvni     |
      | Test field 2 | ASF2     |
    And I click on "Assign users" "button" in the ".modal-dialog" "css_element"
    And I follow "Student 1"
    Then I should see "Prvni" in the "Test field 1" definition list item
    And I should see "ASF2" in the "Test field 2" definition list item

    When I press "Update assignment"
    And I set the following fields to these values:
      | Test field 1 | Druhy     |
    And I click on "Update assignment" "button" in the ".modal-dialog" "css_element"
    Then I should see "Druhy" in the "Test field 1" definition list item
    And I should see "ASF2" in the "Test field 2" definition list item

    And I log out
    When I log in as "student1"
    And I am on the "tool_mucertify > My certifications" page
    And I follow "Certification 000"
    Then I should see "ASF2" in the "Test field 2" definition list item
    And I should not see "Test field 1"
