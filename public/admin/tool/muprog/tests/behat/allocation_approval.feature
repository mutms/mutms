@tool @tool_muprog @MuTMS
Feature: Program approval allocations tests

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
      | username  | firstname | lastname  | email                 |
      | manager1  | Manager   | 1         | manager1@example.com  |
      | manager2  | Manager   | 2         | manager2@example.com  |
      | viewer1   | Viewer    | 1         | viewer1@example.com   |
      | student1  | Student   | 1         | student1@example.com  |
      | student2  | Student   | 2         | student2@example.com  |
      | student3  | Student   | 3         | student3@example.com  |
      | student4  | Student   | 4         | student4@example.com  |
      | student5  | Student   | 5         | student5@example.com  |
      | allocator | Program   | Allocator | allocator@example.com |
    And the following "cohort members" exist:
      | user     | cohort |
      | student1 | CH1    |
      | student2 | CH1    |
      | student3 | CH1    |
      | student2 | CH2    |
      | student4 | CH2    |
    And the following "roles" exist:
      | name              | shortname |
      | Program viewer    | pviewer   |
      | Program manager   | pmanager  |
      | Program allocator | allocator |
    And the following "permission overrides" exist:
      | capability                      | permission | role      | contextlevel | reference |
      | tool/muprog:view             | Allow      | pviewer   | System       |           |
      | tool/muprog:view             | Allow      | pmanager  | System       |           |
      | tool/muprog:edit             | Allow      | pmanager  | System       |           |
      | tool/muprog:delete           | Allow      | pmanager  | System       |           |
      | tool/muprog:addcourse        | Allow      | pmanager  | System       |           |
      | tool/muprog:allocate         | Allow      | pmanager  | System       |           |
      | tool/muprog:manageallocation | Allow      | pmanager  | System       |           |
      | moodle/cohort:view           | Allow      | pmanager  | System       |           |
      | tool/muprog:view             | Allow      | allocator | System       |           |
      | tool/muprog:allocate         | Allow      | allocator | System       |           |
      | tool/muprog:manageallocation | Allow      | allocator | System       |           |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | manager1  | pmanager      | System       |           |
      | manager2  | pmanager      | Category     | CAT2      |
      | manager2  | pmanager      | Category     | CAT3      |
      | viewer1   | pviewer       | System       |           |
      | allocator | allocator     | Category     | CAT1      |
    And the following "tool_muprog > programs" exist:
      | fullname    | idnumber | category | cohorts  | publicaccess |
      | Program 000 | PR0      |          | Cohort 2 |              |
      | Program 001 | PR1      | Cat 1    |          | 1            |
      | Program 002 | PR2      | Cat 2    |          |              |
      | Program 003 | PR3      | Cat 3    |          |              |

  @javascript
  Scenario: Allocator approves student allocation request for a program
    When I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 001"
    And I follow "Allocation settings"
    And I click on "Update Requests with approval" "link"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Active             | Yes |
      | Allow new requests | No  |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    Then I should see "Active; Requests are not allowed" in the "Requests with approval" definition list item
    And I log out

    When I log in as "student2"
    And I am on the "tool_muprog > Program catalogue" page
    And I follow "Program 001"
    And I should not see "Request access"
    And I log out

    When I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 001"
    And I follow "Allocation settings"
    And I click on "Update Requests with approval" "link"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Allow new requests | Yes |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    Then I should see "Active; Requests are allowed" in the "Requests with approval" definition list item
    And I log out

    When I log in as "student2"
    And I am on the "tool_muprog > Program catalogue" page
    And I follow "Program 001"
    And I press "Request access"
    And I click on "Cancel" "button" in the ".modal-dialog" "css_element"
    And I press "Request access"
    And I click on "Request access" "button" in the ".modal-dialog" "css_element"
    Then I should see "Access request pending"
    And I log out

    When I log in as "allocator"
    And I am on the "Cat 1" "tool_muprog > Program management" page
    And I follow "Program 001"
    And I follow "Requests"
    And I click on "Actions" "link" in the "Student 2" "table_row"
    And I click on "Approve request" "link" in the "Student 2" "table_row"
    And I click on "Approve request" "button" in the ".modal-dialog" "css_element"
    Then I should not see "Student 2"
    And I follow "Users"
    And "Student 2" row "Source" column of "reportbuilder-table" table should contain "Requests with approval"
    And I log out

    When I log in as "student2"
    And I am on the "tool_muprog > My programs" page
    And "Program 001" row "Program status" column of "reportbuilder-table" table should contain "Open"
    And I log out

    When I log in as "allocator"
    And I am on the "Cat 1" "tool_muprog > Program management" page
    And I follow "Program 001"
    And I follow "Users"
    And I click on "Actions" "link" in the "Student 2" "table_row"
    And I click on "Delete program allocation" "link" in the "Student 2" "table_row"
    And I click on "Delete program allocation" "button" in the ".modal-dialog" "css_element"
    Then I should not see "Student 2"
    And I log out

    When I log in as "student2"
    And I am on the "tool_muprog > My programs" page
    And I should not see "Program 001"
    And I log out

  @javascript
  Scenario: Allocator rejects student allocation request for a program
    Given I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 001"
    And I follow "Allocation settings"

    When I click on "Update Requests with approval" "link"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Active | Yes |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    Then I should see "Active" in the "Requests with approval" definition list item
    And I log out

    When I log in as "student2"
    And I am on the "tool_muprog > Program catalogue" page
    And I follow "Program 001"
    And I press "Request access"
    And I click on "Request access" "button" in the ".modal-dialog" "css_element"
    Then I should see "Access request pending"
    And I log out

    When I log in as "allocator"
    And I am on the "Cat 1" "tool_muprog > Program management" page
    And I follow "Program 001"
    And I follow "Requests"
    And I click on "Actions" "link" in the "Student 2" "table_row"
    And I click on "Reject request" "link" in the "Student 2" "table_row"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Rejection reason | Sorry mate! |
    And I click on "Reject request" "button" in the ".modal-dialog" "css_element"
    Then I should see "Student 2"
    And I follow "Users"
    And I should not see "Student 2"
    And I log out

    When I log in as "student2"
    And I am on the "tool_muprog > Program catalogue" page
    And I follow "Program 001"
    Then I should see "Access request was rejected"
    And I log out

    When I log in as "allocator"
    And I am on the "Cat 1" "tool_muprog > Program management" page
    And I follow "Program 001"
    And I follow "Requests"
    And I click on "Actions" "link" in the "Student 2" "table_row"
    And I click on "Delete request" "link" in the "Student 2" "table_row"
    And I click on "Delete request" "button" in the ".modal-dialog" "css_element"
    Then I should not see "Student 2"
    And I log out

    When I log in as "student2"
    And I am on the "tool_muprog > Program catalogue" page
    And I follow "Program 001"
    And I press "Request access"
    And I click on "Request access" "button" in the ".modal-dialog" "css_element"
    Then I should see "Access request pending"
    And I log out
