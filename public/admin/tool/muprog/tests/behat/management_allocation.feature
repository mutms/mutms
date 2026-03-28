@tool @tool_muprog @MuTMS
Feature: Program allocation management tests

  Background:
    Given unnecessary Admin bookmarks block gets deleted
    And the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
      | Cat 3 | 0        | CAT3     |
      | Cat 4 | CAT3     | CAT4     |
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
    And the following "roles" exist:
      | name            | shortname |
      | Program viewer  | pviewer   |
      | Program manager | pmanager  |
    And the following "permission overrides" exist:
      | capability                     | permission | role     | contextlevel | reference |
      | tool/muprog:view            | Allow      | pviewer  | System       |           |
      | tool/muprog:view            | Allow      | pmanager | System       |           |
      | tool/muprog:edit            | Allow      | pmanager | System       |           |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | manager1  | pmanager      | System       |           |
      | manager2  | pmanager      | Category     | CAT2      |
      | manager2  | pmanager      | Category     | CAT3      |
      | viewer1   | pviewer       | System       |           |

  @javascript
  Scenario: Manager creates programs with expected default allocation settings
    Given I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page

    And I click on "Add program" "button"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Program name  | Program 001 |
      | Program ID    | PR01        |
    And I click on "Add program" "button" in the ".modal-dialog" "css_element"
    And I follow "Allocation settings"
    And I should see "Not set" in the "Allocation start" definition list item
    And I should see "Not set" in the "Allocation end" definition list item
    And I should see "Start immediately after allocation" in the "Program start" definition list item
    And I should see "Not set" in the "Program due" definition list item
    And I should see "Not set" in the "Program end" definition list item
    And I should see "Inactive" in the "Manual allocation" definition list item
    And I should see "Inactive" in the "Self allocation" definition list item
    And I should see "Inactive" in the "Requests with approval" definition list item
    And I should see "Inactive" in the "Automatic cohort allocation" definition list item

  @javascript
  Scenario: Manager updates allocation start and end dates
    Given the following "tool_muprog > programs" exist:
      | fullname    | idnumber |
      | Program 000 | PR0      |
      | Program 001 | PR1      |
    And I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 000"
    And I follow "Allocation settings"

    When I click on "Update allocations" "link"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | timeallocationstart[enabled] | 1    |
      | timeallocationstart[day]     | 5    |
      | timeallocationstart[month]   | 11   |
      | timeallocationstart[year]    | 2020 |
      | timeallocationstart[hour]    | 09   |
      | timeallocationstart[minute]  | 00   |
    And I click on "Update allocations" "button" in the ".modal-dialog" "css_element"
    Then I should see "Thursday, 5 November 2020, 9:00" in the "Allocation start" definition list item
    And I should see "Not set" in the "Allocation end" definition list item

    When I click on "Update allocations" "link"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | timeallocationend[enabled]   | 1    |
      | timeallocationend[day]       | 4   |
      | timeallocationend[month]     | 11   |
      | timeallocationend[year]      | 2020 |
      | timeallocationend[hour]      | 20   |
      | timeallocationend[minute]    | 00   |
    And I click on "Update allocations" "button" in the ".modal-dialog" "css_element"
    Then I should see "Error"
    When  I set the following fields in the ".modal-dialog" "css_element" to these values:
      | timeallocationend[enabled]   | 1    |
      | timeallocationend[day]       | 10   |
      | timeallocationend[month]     | 11   |
      | timeallocationend[year]      | 2020 |
      | timeallocationend[hour]      | 20   |
      | timeallocationend[minute]    | 00   |
    And I click on "Update allocations" "button" in the ".modal-dialog" "css_element"
    Then I should see "Thursday, 5 November 2020, 9:00" in the "Allocation start" definition list item
    And I should see "Tuesday, 10 November 2020, 8:00" in the "Allocation end" definition list item

    When I click on "Update allocations" "link"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | timeallocationstart[enabled] | 0    |
      | timeallocationend[enabled]   | 0    |
    And I click on "Cancel" "button" in the ".modal-dialog" "css_element"
    Then I should see "Thursday, 5 November 2020, 9:00" in the "Allocation start" definition list item
    And I should see "Tuesday, 10 November 2020, 8:00" in the "Allocation end" definition list item

    When I click on "Update allocations" "link"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | timeallocationstart[enabled] | 0    |
    And I click on "Update allocations" "button" in the ".modal-dialog" "css_element"
    And I should see "Not set" in the "Allocation start" definition list item
    And I should see "Tuesday, 10 November 2020, 8:00" in the "Allocation end" definition list item

    When I click on "Update allocations" "link"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | timeallocationend[enabled]   | 0    |
    And I click on "Update allocations" "button" in the ".modal-dialog" "css_element"
    And I should see "Not set" in the "Allocation start" definition list item
    And I should see "Not set" in the "Allocation end" definition list item

  @javascript
  Scenario: Manager updates allocation scheduling
    Given the following "tool_muprog > programs" exist:
      | fullname    | idnumber |
      | Program 000 | PR0      |
      | Program 001 | PR1      |
    And I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 000"
    And I follow "Allocation settings"

    When I click on "Update scheduling" "link"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Program start              | At a fixed date |
      | programstart_date[day]     | 5    |
      | programstart_date[month]   | 11   |
      | programstart_date[year]    | 2032 |
      | programstart_date[hour]    | 09   |
      | programstart_date[minute]  | 00   |
    And I click on "Update scheduling" "button" in the ".modal-dialog" "css_element"
    Then I should see "Friday, 5 November 2032, 9:00" in the "Program start" definition list item

    When I click on "Update scheduling" "link"
    And I set the following fields to these values:
      | Program start             | Delay start after allocation |
      | programstart_delay[value] | 5      |
      | programstart_delay[type]  | months |
    And I click on "Update scheduling" "button" in the ".modal-dialog" "css_element"
    Then I should see "Delay start after allocation - 5 months" in the "Program start" definition list item

    When I click on "Update scheduling" "link"
    And I set the following fields to these values:
      | Program start             | Delay start after allocation |
      | programstart_delay[value] | 3      |
      | programstart_delay[type]  | days   |
    And I click on "Update scheduling" "button" in the ".modal-dialog" "css_element"
    Then I should see "Delay start after allocation - 3 days" in the "Program start" definition list item

    When I click on "Update scheduling" "link"
    And I set the following fields to these values:
      | Program start             | Delay start after allocation |
      | programstart_delay[value] | 7      |
      | programstart_delay[type]  | hours  |
    And I click on "Update scheduling" "button" in the ".modal-dialog" "css_element"
    Then I should see "Delay start after allocation - 7 hours" in the "Program start" definition list item

    When I click on "Update scheduling" "link"
    And I set the following fields to these values:
      | Program start             | Start immediately after allocation |
    And I click on "Update scheduling" "button" in the ".modal-dialog" "css_element"
    Then I should see "Start immediately after allocation" in the "Program start" definition list item

    When I click on "Update scheduling" "link"
    And I set the following fields to these values:
      | Program due              | At a fixed date |
      | programdue_date[day]     | 5    |
      | programdue_date[month]   | 11   |
      | programdue_date[year]    | 2032 |
      | programdue_date[hour]    | 09   |
      | programdue_date[minute]  | 00   |
    And I click on "Update scheduling" "button" in the ".modal-dialog" "css_element"
    Then I should see "Friday, 5 November 2032, 9:00" in the "Program due" definition list item

    When I click on "Update scheduling" "link"
    And I set the following fields to these values:
      | Program due             | Due after start |
      | programdue_delay[value] | 5      |
      | programdue_delay[type]  | months |
    And I click on "Update scheduling" "button" in the ".modal-dialog" "css_element"
    Then I should see "Due after start - 5 months" in the "Program due" definition list item

    When I click on "Update scheduling" "link"
    And I set the following fields to these values:
      | Program due             | Due after start |
      | programdue_delay[value] | 3      |
      | programdue_delay[type]  | days   |
    And I click on "Update scheduling" "button" in the ".modal-dialog" "css_element"
    Then I should see "Due after start - 3 days" in the "Program due" definition list item

    When I click on "Update scheduling" "link"
    And I set the following fields to these values:
      | Program due             | Due after start |
      | programdue_delay[value] | 7      |
      | programdue_delay[type]  | hours  |
    And I click on "Update scheduling" "button" in the ".modal-dialog" "css_element"
    Then I should see "Due after start - 7 hours" in the "Program due" definition list item

    When I click on "Update scheduling" "link"
    And I set the following fields to these values:
      | Program due             | Not set |
    And I click on "Update scheduling" "button" in the ".modal-dialog" "css_element"
    Then I should see "Not set" in the "Program due" definition list item

    When I click on "Update scheduling" "link"
    And I set the following fields to these values:
      | Program end              | At a fixed date |
      | programend_date[day]     | 5    |
      | programend_date[month]   | 11   |
      | programend_date[year]    | 2032 |
      | programend_date[hour]    | 09   |
      | programend_date[minute]  | 00   |
    And I click on "Update scheduling" "button" in the ".modal-dialog" "css_element"
    Then I should see "Friday, 5 November 2032, 9:00" in the "Program end" definition list item

    When I click on "Update scheduling" "link"
    And I set the following fields to these values:
      | Program end             | End after start |
      | programend_delay[value] | 5      |
      | programend_delay[type]  | months |
    And I click on "Update scheduling" "button" in the ".modal-dialog" "css_element"
    Then I should see "End after start - 5 months" in the "Program end" definition list item

    When I click on "Update scheduling" "link"
    And I set the following fields to these values:
      | Program end             | End after start |
      | programend_delay[value] | 3      |
      | programend_delay[type]  | days   |
    And I click on "Update scheduling" "button" in the ".modal-dialog" "css_element"
    Then I should see "End after start - 3 days" in the "Program end" definition list item

    When I click on "Update scheduling" "link"
    And I set the following fields to these values:
      | Program end             | End after start |
      | programend_delay[value] | 7      |
      | programend_delay[type]  | hours  |
    And I click on "Update scheduling" "button" in the ".modal-dialog" "css_element"
    Then I should see "End after start - 7 hours" in the "Program end" definition list item

    When I click on "Update scheduling" "link"
    And I set the following fields to these values:
      | Program end             | Not set |
    And I click on "Update scheduling" "button" in the ".modal-dialog" "css_element"
    Then I should see "Not set" in the "Program end" definition list item

    When I click on "Update scheduling" "link"
    And I set the following fields to these values:
      | Program start            | At a fixed date |
      | programstart_date[day]   | 5    |
      | programstart_date[month] | 11   |
      | programstart_date[year]  | 2032 |
      | programstart_date[hour]  | 09   |
      | programstart_date[minute]| 00   |
    And I set the following fields to these values:
      | Program end              | At a fixed date |
      | programend_date[day]     | 1    |
      | programend_date[month]   | 11   |
      | programend_date[year]    | 2032 |
      | programend_date[hour]    | 09   |
      | programend_date[minute]  | 00   |
    And I click on "Update scheduling" "button" in the ".modal-dialog" "css_element"
    Then I should see "Error"
    And I set the following fields to these values:
      | Program end              | At a fixed date |
      | programend_date[day]     | 20   |
      | programend_date[month]   | 11   |
      | programend_date[year]    | 2032 |
      | programend_date[hour]    | 09   |
      | programend_date[minute]  | 00   |
    And I set the following fields to these values:
      | Program due              | At a fixed date |
      | programdue_date[day]     | 1    |
      | programdue_date[month]   | 11   |
      | programdue_date[year]    | 2032 |
      | programdue_date[hour]    | 09   |
      | programdue_date[minute]  | 00   |
    And I click on "Update scheduling" "button" in the ".modal-dialog" "css_element"
    Then I should see "Error"
    And I set the following fields to these values:
      | Program due              | At a fixed date |
      | programdue_date[day]     | 22   |
      | programdue_date[month]   | 11   |
      | programdue_date[year]    | 2032 |
      | programdue_date[hour]    | 09   |
      | programdue_date[minute]  | 00   |
    And I click on "Update scheduling" "button" in the ".modal-dialog" "css_element"
    Then I should see "Error"
    And I set the following fields to these values:
      | Program due              | At a fixed date |
      | programdue_date[day]     | 15   |
      | programdue_date[month]   | 11   |
      | programdue_date[year]    | 2032 |
      | programdue_date[hour]    | 09   |
      | programdue_date[minute]  | 00   |
    And I click on "Update scheduling" "button" in the ".modal-dialog" "css_element"
    Then I should see "Friday, 5 November 2032, 9:00" in the "Program start" definition list item
    And I should see "Monday, 15 November 2032, 9:00" in the "Program due" definition list item
    And I should see "Saturday, 20 November 2032, 9:00" in the "Program end" definition list item
