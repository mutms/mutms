@tool @tool_muprog @MuTMS
Feature: Upload program completion evidence using csv

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
      | capability                     | permission | role     | contextlevel | reference |
      | tool/muprog:view            | Allow      | pviewer  | System       |           |
      | tool/muprog:view            | Allow      | pmanager | System       |           |
      | tool/muprog:manageevidence  | Allow      | pmanager | System       |           |
      | tool/muprog:edit            | Allow      | pmanager | System       |           |
      | tool/muprog:allocate        | Allow      | pmanager | System       |           |
      | tool/muprog:deallocate      | Allow      | pmanager | System       |           |
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

  @javascript @_file_upload
  Scenario: Manager may upload CSV file for other evidence completion
    Given I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 001"
    And I follow "Allocation settings"
    And I click on "Update Manual allocation" "link"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Active | Yes |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I should see "Active" in the "Manual allocation" definition list item
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"
    And I click on "Users actions" "button"
    And I should not see "Upload completion evidences"

    And I click on "Upload allocations" "link"
    And I upload "admin/tool/muprog/tests/fixtures/evidence1.csv" file to "CSV file" filemanager
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | CSV separator | ,     |
      | Encoding      | UTF-8 |
    And I click on "Continue" "button" in the ".modal-dialog" "css_element"
    And the following fields in the ".modal-dialog" "css_element" match these values:
      | User identification column | username |
      | User mapping via           | Username |
      | First line is header       | 1        |
    And I click on "Upload allocations" "button" in the ".modal-dialog" "css_element"
    And I should see "5 users were allocated to program."
    And I click on "Users actions" "button"
    And I should see "Upload completion evidences"

    And I follow "Student 3"
    And I click on "Archive" action from "Allocation actions" dropdown
    And I click on "Archive" "button" in the ".modal-dialog" "css_element"
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"

    When I click on "Upload completion evidences" action from "Users actions" dropdown
    And I upload "admin/tool/muprog/tests/fixtures/evidence1.csv" file to "CSV file" filemanager
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | CSV separator | ,     |
      | Encoding      | UTF-8 |
    And I click on "Continue" "button" in the ".modal-dialog" "css_element"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | User identification column | username      |
      | User mapping via           | Username      |
      | First line is header       | 1             |
      | Completion date            | completed     |
      | Details                    | evidence      |
      | Default details            | EvidenceX     |
    And I click on "Upload completion evidences" "button" in the ".modal-dialog" "css_element"
    Then I should see "Completion evidence updated for 2 users"
    And I should see "2 rows skipped"
    And I should see "2 invalid rows detected"
    And the following should exist in the "reportbuilder-table" table:
      | First name             | Program status |
      | Student 1              | Completed      |
      | Student 2              | Completed      |
      | Student 3              | Archived       |
      | Student 4              | Open           |
      | Student 5              | Open           |

    And I follow "Student 1"
    And I should see "12 February 2023, 12:00" in the "Program completion date" definition list item
    And the following should exist in the "program_content" table:
      | Completion date | Other evidence |
      | 12/02/23, 00:00 | Evidence1      |
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"

    And I follow "Student 2"
    And I should see "12 March 2023, 12:00" in the "Program completion date" definition list item
    And the following should exist in the "program_content" table:
      | Completion date | Other evidence |
      | 12/03/23, 00:00 | EvidenceX      |
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"

    When I click on "Upload completion evidences" action from "Users actions" dropdown
    And I upload "admin/tool/muprog/tests/fixtures/evidence2.csv" file to "CSV file" filemanager
    And I set the following fields to these values:
      | CSV separator | ,     |
      | Encoding      | UTF-8 |
    And I click on "Continue" "button" in the ".modal-dialog" "css_element"
    And I set the following fields to these values:
      | User identification column | email         |
      | User mapping via           | Email address |
      | First line is header       | 1             |
      | Completion date            | completed     |
    And I click on "Upload completion evidences" "button" in the ".modal-dialog" "css_element"
    And I set the following fields to these values:
      | User identification column | email         |
      | User mapping via           | Email         |
      | First line is header       | 1             |
      | Completion date            | completed     |
      | Default details            | EvidenceY     |
    And I click on "Upload completion evidences" "button" in the ".modal-dialog" "css_element"
    Then I should see "Completion evidence updated for 2 users"
    And I should see "1 rows skipped"

    And I follow "Student 1"
    And I should see "12 July 2023, 12:00" in the "Program completion date" definition list item
    And the following should exist in the "program_content" table:
      | Completion date | Other evidence |
      | 12/07/23, 00:00 | EvidenceY      |
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"

    And I follow "Student 2"
    And I should see "12 March 2023, 12:00" in the "Program completion date" definition list item
    And the following should exist in the "program_content" table:
      | Completion date | Other evidence |
      | 12/03/23, 00:00 | EvidenceX      |
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"

    And I follow "Student 4"
    And I should see "Not set" in the "Program completion date" definition list item
    And the following should exist in the "program_content" table:
      | Completion date | Other evidence |
      | 11/04/35, 00:00 | EvidenceY      |
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"
