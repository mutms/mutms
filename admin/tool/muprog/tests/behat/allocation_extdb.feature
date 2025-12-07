@tool @tool_muprog @MuTMS @javascript
Feature: External database query program allocation tests

  Background:
    Given unnecessary Admin bookmarks block gets deleted
    And the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
      | student3 | Student   | 3        | student3@example.com |
      | student4 | Student   | 4        | student4@example.com |
      | student5 | Student   | 5        | student5@example.com |
    And the following "tool_muprog > programs" exist:
      | fullname    | idnumber | category |
      | Program 001 | PR1      | Cat 1    |

  Scenario: Administrator may enable external database query allocation in programs
    Given the following "tool_mulib > extdb_servers" exist:
      | name          |
      | Test server 1 |
    And the following "tool_mulib > extdb_queries" exist:
      | name         | server        | component   | type       | contextlevel | reference | sqlquery                                                                   |
      | Test query 1 | Test server 1 | tool_muprog | allocation | Category     | CAT1      | SELECT id AS userid FROM {user} WHERE username IN ('student1', 'student2') |
      | Test query 2 | Test server 1 | tool_muprog | allocation | System       |           | SELECT id AS userid FROM {user} WHERE username IN ('student1')             |
    And the following config values are set as admin:
      | source_extdb_allownew | 1 | tool_muprog |
    And I log in as "admin"
    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 001"

    When I click on "Allocation settings" "link" in the ".secondary-navigation" "css_element"
    And I click on "Update External database allocation" "link"
    And I set the following fields to these values:
      | Active                  | Yes          |
      | External database query | Test query 1 |
      | Archive missing users   | 1            |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    Then I should see "Active (Test query 1)" in the "External database allocation" definition list item

    When I click on "Process allocations" "link"
    And I click on "Process allocations" "button" in the ".modal-dialog" "css_element"
    Then I should see "External database allocation ad-hoc task created"

    When I run all ad-hoc tasks
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"
    Then "Student 1" row "Source" column of "reportbuilder-table" table should contain "External database allocation"
    And "Student 1" row "Program status" column of "reportbuilder-table" table should contain "Open"
    And "Student 2" row "Source" column of "reportbuilder-table" table should contain "External database allocation"
    And "Student 2" row "Program status" column of "reportbuilder-table" table should contain "Open"
    And I should not see "Student 3"

    When I click on "Allocation settings" "link" in the ".secondary-navigation" "css_element"
    And I click on "Update External database allocation" "link"
    And I set the following fields to these values:
      | External database query | Test query 2 |
      | Archive missing users   | 0            |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I click on "Process allocations" "link"
    And I click on "Process allocations" "button" in the ".modal-dialog" "css_element"
    And I should see "External database allocation ad-hoc task created"
    And I run all ad-hoc tasks
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"
    Then "Student 1" row "Source" column of "reportbuilder-table" table should contain "External database allocation"
    And "Student 1" row "Program status" column of "reportbuilder-table" table should contain "Open"
    And "Student 2" row "Source" column of "reportbuilder-table" table should contain "External database allocation"
    And "Student 2" row "Program status" column of "reportbuilder-table" table should contain "Open"
    And I should not see "Student 3"

    When I click on "Allocation settings" "link" in the ".secondary-navigation" "css_element"
    And I click on "Update External database allocation" "link"
    And I set the following fields to these values:
      | External database query | Test query 2 |
      | Archive missing users   | 1            |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I click on "Process allocations" "link"
    And I click on "Process allocations" "button" in the ".modal-dialog" "css_element"
    And I should see "External database allocation ad-hoc task created"
    And I run all ad-hoc tasks
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"
    Then "Student 1" row "Source" column of "reportbuilder-table" table should contain "External database allocation"
    And "Student 1" row "Program status" column of "reportbuilder-table" table should contain "Open"
    And "Student 2" row "Source" column of "reportbuilder-table" table should contain "External database allocation"
    And "Student 2" row "Program status" column of "reportbuilder-table" table should contain "Archived"
    And I should not see "Student 3"

    When I click on "Allocation settings" "link" in the ".secondary-navigation" "css_element"
    And I click on "Update External database allocation" "link"
    And I set the following fields to these values:
      | External database query | Test query 1 |
      | Archive missing users   | 1            |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I click on "Process allocations" "link"
    And I click on "Process allocations" "button" in the ".modal-dialog" "css_element"
    And I should see "External database allocation ad-hoc task created"
    And I run all ad-hoc tasks
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"
    Then "Student 1" row "Source" column of "reportbuilder-table" table should contain "External database allocation"
    And "Student 1" row "Program status" column of "reportbuilder-table" table should contain "Open"
    And "Student 2" row "Source" column of "reportbuilder-table" table should contain "External database allocation"
    And "Student 2" row "Program status" column of "reportbuilder-table" table should contain "Open"
    And I should not see "Student 3"
