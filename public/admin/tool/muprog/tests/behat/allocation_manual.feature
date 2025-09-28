@tool @tool_muprog @MuTMS
Feature: Manual program allocation tests

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
      | Program viewer  | pviewer   |
      | Program manager | pmanager  |
    And the following "permission overrides" exist:
      | capability                        | permission | role     | contextlevel | reference |
      | tool/muprog:view                  | Allow      | pviewer  | System       |           |
      | tool/muprog:view                  | Allow      | pmanager | System       |           |
      | tool/muprog:edit                  | Allow      | pmanager | System       |           |
      | tool/muprog:delete                | Allow      | pmanager | System       |           |
      | tool/muprog:addcourse             | Allow      | pmanager | System       |           |
      | tool/muprog:allocate              | Allow      | pmanager | System       |           |
      | tool/muprog:deallocate            | Allow      | pmanager | System       |           |
      | tool/muprog:manageallocation      | Allow      | pmanager | System       |           |
      | moodle/cohort:view                | Allow      | pmanager | System       |           |
      | moodle/site:configview            | Allow      | pmanager | System       |           |
      | tool/muprog:configurecustomfields | Allow      | pmanager | System       |           |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | manager   | manager       | System       |           |
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
  Scenario: Manager may allocate users manually to program
    Given I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 000"
    And I follow "Allocation settings"
    And I click on "Update Manual allocation" "link"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Active | Yes |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I should see "Active" in the "Manual allocation" definition list item
    And I follow "Users"

    When I press "Allocate users"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Users | Student 1, Student 5 |
    And I click on "Allocate users" "button" in the ".modal-dialog" "css_element"
    Then "Student 1" row "Source" column of "reportbuilder-table" table should contain "Manual allocation"
    And "Student 5" row "Source" column of "reportbuilder-table" table should contain "Manual allocation"
    And I should not see "Student 2"
    And I should not see "Student 3"
    And I should not see "Student 4"

    When I press "Allocate users"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Cohort | Cohort 2 |
    And I click on "Allocate users" "button" in the ".modal-dialog" "css_element"
    Then "Student 1" row "Source" column of "reportbuilder-table" table should contain "Manual allocation"
    And "Student 2" row "Source" column of "reportbuilder-table" table should contain "Manual allocation"
    And "Student 5" row "Source" column of "reportbuilder-table" table should contain "Manual allocation"
    And I should not see "Student 3"
    And I should not see "Student 4"

    When I click on "Actions" "link" in the "Student 2" "table_row"
    And I click on "Delete program allocation" "link" in the "Student 2" "table_row"
    And I click on "Cancel" "button" in the ".modal-dialog" "css_element"
    Then "Student 2" row "Source" column of "reportbuilder-table" table should contain "Manual allocation"

    When I click on "Actions" "link" in the "Student 2" "table_row"
    And I click on "Delete program allocation" "link" in the "Student 2" "table_row"
    And I click on "Delete program allocation" "button" in the ".modal-dialog" "css_element"
    Then I should not see "Student 2"

  @javascript @tool_mutenancy
  Scenario: Tenant manager may allocate users manually to program
    Given I skip tests if "tool_mutenancy" is not installed
    And the following "tool_mutenancy > tenants" exist:
      | name     | idnumber | category | assoccohort |
      | Tenant 1 | ten1     | CAT1     | CH1         |
      | Tenant 2 | ten2     | CAT2     | CH2         |
    And the following "users" exist:
      | username | firstname | lastname | email                | tenant   |
      | tu1      | Tenant 1  | Student  | tu1@example.com      | ten1     |
      | tu2      | Tenant 2  | Student  | tu2@example.com      | ten2     |
    And I log in as "manager"

    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 000"
    And I follow "Allocation settings"
    And I click on "Update Manual allocation" "link"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Active | Yes |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I should see "Active" in the "Manual allocation" definition list item
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"
    When I press "Allocate users"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Users | Student 1 |
    And I click on "Allocate users" "button" in the ".modal-dialog" "css_element"
    Then "Student 1" row "Source" column of "reportbuilder-table" table should contain "Manual allocation"

    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 001"
    And I follow "Allocation settings"
    And I click on "Update Manual allocation" "link"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Active | Yes |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I should see "Active" in the "Manual allocation" definition list item
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"

    When I press "Allocate users"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Users | Student 1 |
    And I click on "Allocate users" "button" in the ".modal-dialog" "css_element"
    And "Student 1" row "Source" column of "reportbuilder-table" table should contain "Manual allocation"

    And I click on "Switch tenant" "link"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Tenant      | Tenant 1         |
    And I click on "Switch tenant" "button" in the ".modal-dialog" "css_element"

    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 000"
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"

    When I press "Allocate users"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Users | Tenant 1 Student |
    And I click on "Allocate users" "button" in the ".modal-dialog" "css_element"
    Then "Tenant 1 Student" row "Source" column of "reportbuilder-table" table should contain "Manual allocation"

    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 001"
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"

    When I press "Allocate users"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Users | Tenant 1 Student |
    And I click on "Allocate users" "button" in the ".modal-dialog" "css_element"
    Then "Tenant 1 Student" row "Source" column of "reportbuilder-table" table should contain "Manual allocation"

  @javascript @_file_upload
  Scenario: Manager may upload CSV file with manual allocations without dates
    Given I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 000"
    And I follow "Allocation settings"
    And I click on "Update Manual allocation" "link"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Active | Yes |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I should see "Active" in the "Manual allocation" definition list item
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"

    When I click on "Upload allocations" action from "Users actions" dropdown
    And I upload "admin/tool/muprog/tests/fixtures/upload1.csv" file to "CSV file" filemanager
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | CSV separator | ,     |
      | Encoding      | UTF-8 |
    And I click on "Continue" "button" in the ".modal-dialog" "css_element"
    And the following fields in the ".modal-dialog" "css_element" match these values:
      | User identification column | username |
      | User mapping via           | Username |
      | First line is header       | 1        |
    And I click on "Upload allocations" "button" in the ".modal-dialog" "css_element"
    Then I should see "3 users were allocated to program."
    And "Student 1" row "Source" column of "reportbuilder-table" table should contain "Manual allocation"
    And "Student 2" row "Source" column of "reportbuilder-table" table should contain "Manual allocation"
    And "Student 3" row "Source" column of "reportbuilder-table" table should contain "Manual allocation"

    When I click on "Upload allocations" action from "Users actions" dropdown
    And I upload "admin/tool/muprog/tests/fixtures/upload2.csv" file to "CSV file" filemanager
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | CSV separator | ,     |
      | Encoding      | UTF-8 |
    And I click on "Continue" "button" in the ".modal-dialog" "css_element"
    And the following fields in the ".modal-dialog" "css_element" match these values:
      | User identification column | student1@example.com |
      | User mapping via           | Username             |
      | First line is header       | 0                    |
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | User mapping via           | Email address    |
    And I click on "Upload allocations" "button" in the ".modal-dialog" "css_element"
    Then I should see "1 users were allocated to program."
    And I should see "2 users were already allocated to program."
    And I should see "1 errors detected when allocating programs."
    And "Student 1" row "Source" column of "reportbuilder-table" table should contain "Manual allocation"
    And "Student 2" row "Source" column of "reportbuilder-table" table should contain "Manual allocation"
    And "Student 3" row "Source" column of "reportbuilder-table" table should contain "Manual allocation"
    And "Student 4" row "Source" column of "reportbuilder-table" table should contain "Manual allocation"

    When I click on "Upload allocations" action from "Users actions" dropdown
    And I upload "admin/tool/muprog/tests/fixtures/upload3.csv" file to "CSV file" filemanager
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | CSV separator | ;     |
      | Encoding      | UTF-8 |
    And I click on "Continue" "button" in the ".modal-dialog" "css_element"
    And the following fields in the ".modal-dialog" "css_element" match these values:
      | User identification column | idnumber  |
      | User mapping via           | ID number |
      | First line is header       | 1         |
    And I click on "Upload allocations" "button" in the ".modal-dialog" "css_element"
    Then I should see "1 users were allocated to program."
    And I should see "1 users were already allocated to program."
    And "Student 1" row "Source" column of "reportbuilder-table" table should contain "Manual allocation"
    And "Student 2" row "Source" column of "reportbuilder-table" table should contain "Manual allocation"
    And "Student 3" row "Source" column of "reportbuilder-table" table should contain "Manual allocation"
    And "Student 4" row "Source" column of "reportbuilder-table" table should contain "Manual allocation"
    And "Student 5" row "Source" column of "reportbuilder-table" table should contain "Manual allocation"

  @javascript @_file_upload
  Scenario: Manager may upload CSV file with manual allocations including program dates
    Given I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 000"
    And I follow "Allocation settings"
    And I click on "Update scheduling" "link"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Program start              | At a fixed date |
      | programstart_date[year]    | 2022 |
      | programstart_date[day]     | 5    |
      | programstart_date[month]   | 11   |
      | programstart_date[hour]    | 09   |
      | programstart_date[minute]  | 00   |
      | Program due                | At a fixed date |
      | programdue_date[year]      | 2023 |
      | programdue_date[day]       | 22   |
      | programdue_date[month]     | 1    |
      | programdue_date[hour]      | 09   |
      | programdue_date[minute]    | 00   |
      | Program end                | At a fixed date |
      | programend_date[year]      | 2023 |
      | programend_date[month]     | 12   |
      | programend_date[day]       | 31   |
      | programend_date[hour]      | 09   |
      | programend_date[minute]    | 00   |
    And I click on "Update scheduling" "button" in the ".modal-dialog" "css_element"
    And I click on "Update Manual allocation" "link"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Active | Yes |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I should see "Active" in the "Manual allocation" definition list item
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"

    When I click on "Upload allocations" action from "Users actions" dropdown
    And I upload "admin/tool/muprog/tests/fixtures/upload4.csv" file to "CSV file" filemanager
    And I set the following fields to these values:
      | CSV separator | ,     |
      | Encoding      | UTF-8 |
    And I click on "Continue" "button" in the ".modal-dialog" "css_element"
    And the following fields in the ".modal-dialog" "css_element" match these values:
      | User identification column | username           |
      | User mapping via           | Username           |
    And I set the following fields to these values:
      | Time start column          | startdate          |
      | Time due column            | duedate            |
      | Time end column            | enddate            |
    And I click on "Upload allocations" "button" in the ".modal-dialog" "css_element"
    Then I should see "3 users were allocated to program."
    And I should see "3 errors detected when allocating programs."
    Then the following should exist in the "reportbuilder-table" table:
      | First name          | Program start   | Due date        | Program end     | Source            |
      | Student 1           | 5/11/22, 09:00  | 22/01/23, 09:00 | 31/12/23, 09:00 | Manual allocation |
      | Student 2           | 11/10/22, 00:00 | 31/12/22, 00:00 | 31/01/23, 23:52 | Manual allocation |
      | Student 3           | 11/10/22, 00:00 | 22/01/23, 09:00 | 31/12/23, 09:00 | Manual allocation |

  @javascript
  Scenario: Program manager cannot alter dates of archived allocation
    Given the following "tool_muprog > program_allocations" exist:
      | program     | user     |
      | Program 000 | student1 |
    And I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 000"
    And I follow "Users"
    And I follow "Student 1"
    And I click on "Allocation actions" "button"
    And I should see "Update allocation"
    And I should not see "Delete program allocation"

    When I click on "Archive allocation" "link"
    And I click on "Archive allocation" "button" in the ".modal-dialog" "css_element"
    And I click on "Allocation actions" "button"
    Then I should not see "Update allocation"
    And I should see "Delete program allocation"

    When I click on "Restore allocation" "link"
    And I click on "Restore allocation" "button" in the ".modal-dialog" "css_element"
    And I click on "Allocation actions" "button"
    Then I should see "Update allocation"
    And I should not see "Delete program allocation"

  @javascript
  Scenario: Set up, add and update custom fields for program allocations
    And the following "permission overrides" exist:
      | capability                           | permission | role     | contextlevel | reference |
      | tool/muprog:admin                    | Allow      | pmanager | System       |           |
    And I log in as "manager1"
    And I navigate to "Programs > Program allocation custom fields" in site administration
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
      | Allocatee                                | 1            |
    And I click on "Save changes" "button" in the "Adding a new Short text" "dialogue"

    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 000"
    And I click on "Allocation settings" "link" in the ".secondary-navigation" "css_element"
    And I click on "Update Manual allocation" "link"
    And I set the following fields to these values:
      | Active | Yes |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I should see "Active" in the "Manual allocation" definition list item
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"

    When I press "Allocate users"
    And I set the following fields to these values:
      | Users        | Student 1 |
      | Test field 1 | Prvni     |
      | Test field 2 | ASF2     |
    And I click on "Allocate users" "button" in the ".modal-dialog" "css_element"
    And I follow "Student 1"
    Then I should see "Prvni" in the "Test field 1" definition list item
    And I should see "ASF2" in the "Test field 2" definition list item

    When I click on "Update allocation" action from "Allocation actions" dropdown
    And I set the following fields to these values:
      | Test field 1 | Druhy     |
    And I click on "Update allocation" "button" in the ".modal-dialog" "css_element"
    Then I should see "Druhy" in the "Test field 1" definition list item
    And I should see "ASF2" in the "Test field 2" definition list item

    And I log out
    When I log in as "student1"
    And I am on the "tool_muprog > My programs" page
    And I follow "Program 000"
    Then I should see "ASF2" in the "Test field 2" definition list item
    And I should not see "Test field 1"
