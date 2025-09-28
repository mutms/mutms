@tool @tool_muprog @MuTMS
Feature: Import allocation settings

  Background:
    Given unnecessary Admin bookmarks block gets deleted
    And the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
      | Cat 3 | CAT2     | CAT3     |
    And the following "courses" exist:
      | fullname | shortname | format | category |
      | Course 1 | C1        | topics | CAT1     |
      | Course 2 | C2        | topics | CAT2     |
      | Course 3 | C3        | topics | CAT3     |
      | Course 4 | C4        | topics | CAT1     |
      | Course 5 | C5        | topics | CAT1     |
      | Course 6 | C6        | topics | CAT1     |
    And the following "cohorts" exist:
      | name     | idnumber |
      | Cohort 1 | CH1      |
      | Cohort 2 | CH2      |
      | Cohort 3 | CH3      |
      | Cohort 4 | CH3      |
      | Cohort 5 | CH3      |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | manager  | Site      | Manager  | manager@example.com  |
      | manager1 | Manager   | 1        | manager1@example.com |
      | manager2 | Manager   | 2        | manager2@example.com |
      | viewer1  | Viewer    | 1        | viewer1@example.com  |
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
      | tool/muprog:clone           | Allow      | pmanager | System       |           |
      | moodle/cohort:view             | Allow      | pmanager | System       |           |
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
      | Program 001 | PR1      |          |
      | Program 002 | PR2      | Cat 2    |
      | Program 003 | PR3      | Cat 3    |

  @javascript
  Scenario: Manager may import allocation settings from another program
    Given I log in as "manager1"

    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 000"
    And I click on "Allocation settings" "link" in the ".secondary-navigation" "css_element"
    And I click on "Update allocations" "link"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | timeallocationstart[enabled] | 1    |
      | timeallocationstart[day]     | 5    |
      | timeallocationstart[month]   | 11   |
      | timeallocationstart[year]    | 2020 |
      | timeallocationstart[hour]    | 09   |
      | timeallocationstart[minute]  | 00   |
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | timeallocationend[enabled] | 1    |
      | timeallocationend[day]     | 5    |
      | timeallocationend[month]   | 11   |
      | timeallocationend[year]    | 2028 |
      | timeallocationend[hour]    | 09   |
      | timeallocationend[minute]  | 00   |
    And I click on "Update allocations" "button" in the ".modal-dialog" "css_element"
    And I click on "Update scheduling" "link"
    And I set the following fields to these values:
      | Program start             | Delay start after allocation |
      | programstart_delay[value] | 5      |
      | programstart_delay[type]  | months |
      | Program due               | Due after start |
      | programdue_delay[value]   | 8      |
      | programdue_delay[type]    | months |
      | Program end               | End after start |
      | programend_delay[value]   | 10     |
      | programend_delay[type]    | months |
    And I click on "Update scheduling" "button" in the ".modal-dialog" "css_element"
    And I click on "Update Automatic cohort allocation" "link"
    And I set the following fields to these values:
      | Active           | Yes                |
      | Allocate cohorts | Cohort 1, Cohort 2 |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I click on "Update Requests with approval" "link"
    And I set the following fields to these values:
      | Active             | Yes |
      | Allow new requests | No  |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I click on "Update Manual allocation" "link"
    And I set the following fields to these values:
      | Active | Yes |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I click on "Update Self allocation" "link"
    And I set the following fields to these values:
      | Active             | Yes |
      | Allow new sign ups | No  |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I should see "Thursday, 5 November 2020, 9:00" in the "Allocation start" definition list item
    And I should see "Sunday, 5 November 2028, 9:00" in the "Allocation end" definition list item
    And I should see "Delay start after allocation - 5 months" in the "Program start" definition list item
    And I should see "Due after start - 8 months" in the "Program due" definition list item
    And I should see "End after start - 10 months" in the "Program end" definition list item
    And I should see "Active; Requests are not allowed" in the "Requests with approval" definition list item
    And I should see "Active (Cohort 1, Cohort 2)" in the "Automatic cohort allocation" definition list item
    And I should see "Active" in the "Manual allocation" definition list item
    And I should see "Active; Sign ups are not allowed" in the "Self allocation" definition list item

    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 001"
    And I click on "Allocation settings" "link" in the ".secondary-navigation" "css_element"
    And I should see "Not set" in the "Allocation start" definition list item
    And I should see "Not set" in the "Allocation end" definition list item
    And I should see "Start immediately after allocation" in the "Program start" definition list item
    And I should see "Not set" in the "Program due" definition list item
    And I should see "Not set" in the "Program end" definition list item
    And I should see "Inactive" in the "Manual allocation" definition list item
    And I should see "Inactive" in the "Self allocation" definition list item
    And I should see "Inactive" in the "Requests with approval" definition list item
    And I should see "Inactive" in the "Automatic cohort allocation" definition list item

    When I click on "Import allocation settings" action from "Allocation settings actions" dropdown
    And I set the following fields to these values:
      | Select program | Program 000 |
    And I click on "Continue" "button" in the ".modal-dialog" "css_element"
    And I set the following fields to these values:
      | Allocation start | 1 |
    And I click on "Import allocation settings" "button" in the ".modal-dialog" "css_element"
    Then I should see "Thursday, 5 November 2020, 9:00" in the "Allocation start" definition list item
    And I should see "Not set" in the "Allocation end" definition list item
    And I should see "Start immediately after allocation" in the "Program start" definition list item
    And I should see "Not set" in the "Program due" definition list item
    And I should see "Not set" in the "Program end" definition list item
    And I should see "Inactive" in the "Manual allocation" definition list item
    And I should see "Inactive" in the "Self allocation" definition list item
    And I should see "Inactive" in the "Requests with approval" definition list item
    And I should see "Inactive" in the "Automatic cohort allocation" definition list item

    When I click on "Import allocation settings" action from "Allocation settings actions" dropdown
    And I set the following fields to these values:
      | Select program | Program 000 |
    And I click on "Continue" "button" in the ".modal-dialog" "css_element"
    And I set the following fields to these values:
      | Allocation start            | 1 |
      | Allocation end              | 1 |
      | Program start               | 1 |
      | Program due                 | 1 |
      | Program end                 | 1 |
      | Requests with approval      | 1 |
      | Automatic cohort allocation | 1 |
      | Manual allocation           | 1 |
      | Self allocation             | 1 |
    And I click on "Import allocation settings" "button" in the ".modal-dialog" "css_element"
    Then I should see "Thursday, 5 November 2020, 9:00" in the "Allocation start" definition list item
    And I should see "Sunday, 5 November 2028, 9:00" in the "Allocation end" definition list item
    And I should see "Delay start after allocation - 5 months" in the "Program start" definition list item
    And I should see "Due after start - 8 months" in the "Program due" definition list item
    And I should see "End after start - 10 months" in the "Program end" definition list item
    And I should see "Active; Requests are not allowed" in the "Requests with approval" definition list item
    And I should see "Active (Cohort 1, Cohort 2)" in the "Automatic cohort allocation" definition list item
    And I should see "Active" in the "Manual allocation" definition list item
    And I should see "Active; Sign ups are not allowed" in the "Self allocation" definition list item
