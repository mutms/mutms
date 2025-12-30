@block @block_muprogmyoverview @tool_muprog @javascript @MuTMS
Feature: My programs page overview block pagination

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                | idnumber |
      | student1 | Student   | X        | student1@example.com | S1       |
    And the following "tool_muprog > programs" exist:
      | fullname   | idnumber | category |
      | Program 01 | C1       | 0        |
      | Program 02 | C2       | 0        |
      | Program 03 | C3       | 0        |
      | Program 04 | C4       | 0        |
      | Program 05 | C5       | 0        |
      | Program 06 | C6       | 0        |
      | Program 07 | C7       | 0        |
      | Program 08 | C8       | 0        |
      | Program 09 | C9       | 0        |
      | Program 10 | C10      | 0        |
      | Program 11 | C11      | 0        |
      | Program 12 | C12      | 0        |
      | Program 13 | C13      | 0        |
      | Program 14 | C14      | 0        |
      | Program 15 | C15      | 0        |
      | Program 16 | C16      | 0        |
      | Program 17 | C17      | 0        |
      | Program 18 | C18      | 0        |
      | Program 19 | C19      | 0        |
      | Program 20 | C20      | 0        |
      | Program 21 | C21      | 0        |
      | Program 22 | C22      | 0        |
      | Program 23 | C23      | 0        |
      | Program 24 | C24      | 0        |
      | Program 25 | C25      | 0        |

  Scenario: The pagination controls should be hidden if I am not allocated in any programs
    When I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    Then I should see "You not have any active programs." in the "My programs overview page" "block"
    And I should not see "Show" in the "My programs overview page" "block"
    And ".block_muprogmyoverview .dropdown-menu.show" "css_element" should not be visible
    And ".block_muprogmyoverview [data-control='next']" "css_element" should not be visible
    And ".block_muprogmyoverview [data-control='previous']" "css_element" should not be visible
    And I log out

  Scenario: The pagination controls should be hidden if I am allocated in 12 programs or less
    Given the following "tool_muprog > program_allocations" exist:
      | user     | program    |
      | student1 | Program 01 |
      | student1 | Program 02 |
      | student1 | Program 03 |
      | student1 | Program 04 |
      | student1 | Program 05 |
      | student1 | Program 06 |
      | student1 | Program 07 |
      | student1 | Program 08 |
      | student1 | Program 09 |
      | student1 | Program 10 |
      | student1 | Program 11 |
      | student1 | Program 12 |
    When I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    Then I should not see "Show" in the "My programs overview page" "block"
    And ".block_muprogmyoverview .dropdown-menu.show" "css_element" should not be visible
    And ".block_muprogmyoverview [data-control='next']" "css_element" should not be visible
    And ".block_muprogmyoverview [data-control='previous']" "css_element" should not be visible
    And I log out

  Scenario: The default pagination should be 12 programs
    Given the following "tool_muprog > program_allocations" exist:
      | user     | program    |
      | student1 | Program 01 |
      | student1 | Program 02 |
      | student1 | Program 03 |
      | student1 | Program 04 |
      | student1 | Program 05 |
      | student1 | Program 06 |
      | student1 | Program 07 |
      | student1 | Program 08 |
      | student1 | Program 09 |
      | student1 | Program 10 |
      | student1 | Program 11 |
      | student1 | Program 12 |
      | student1 | Program 13 |
    When I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    Then I should see "12" in the ".block_muprogmyoverview [data-action='limit-toggle']" "css_element"
    And I log out

  Scenario: I should only see pagination limit options less than total number of allocated programs
    Given the following "tool_muprog > program_allocations" exist:
      | user     | program    |
      | student1 | Program 01 |
      | student1 | Program 02 |
      | student1 | Program 03 |
      | student1 | Program 04 |
      | student1 | Program 05 |
      | student1 | Program 06 |
      | student1 | Program 07 |
      | student1 | Program 08 |
      | student1 | Program 09 |
      | student1 | Program 10 |
      | student1 | Program 11 |
      | student1 | Program 12 |
      | student1 | Program 13 |
    And I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    When I click on "[data-action='limit-toggle']" "css_element" in the "My programs overview page" "block"
    Then I should see "All" in the ".dropdown-menu.show" "css_element"
    And I should see "12" in the ".dropdown-menu.show" "css_element"
    And ".block_muprogmyoverview [data-control='next']" "css_element" should be visible
    And ".block_muprogmyoverview [data-control='previous']" "css_element" should be visible
    But I should not see "24" in the ".block_muprogmyoverview .dropdown-menu.show" "css_element"
    And I log out

  Scenario: Previous page button should be disabled when on the first page of programs
    Given the following "tool_muprog > program_allocations" exist:
      | user     | program    |
      | student1 | Program 01 |
      | student1 | Program 02 |
      | student1 | Program 03 |
      | student1 | Program 04 |
      | student1 | Program 05 |
      | student1 | Program 06 |
      | student1 | Program 07 |
      | student1 | Program 08 |
      | student1 | Program 09 |
      | student1 | Program 10 |
      | student1 | Program 11 |
      | student1 | Program 12 |
      | student1 | Program 13 |
    When I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    Then the "class" attribute of ".block_muprogmyoverview [data-control='previous']" "css_element" should contain "disabled"
    And I log out

  Scenario: Next page button should be disabled when on the last page of programs
    Given the following "tool_muprog > program_allocations" exist:
      | user     | program    |
      | student1 | Program 01 |
      | student1 | Program 02 |
      | student1 | Program 03 |
      | student1 | Program 04 |
      | student1 | Program 05 |
      | student1 | Program 06 |
      | student1 | Program 07 |
      | student1 | Program 08 |
      | student1 | Program 09 |
      | student1 | Program 10 |
      | student1 | Program 11 |
      | student1 | Program 12 |
      | student1 | Program 13 |
    When I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I wait until ".block_muprogmyoverview [data-control='next']" "css_element" exists
    And I click on "[data-control='next']" "css_element" in the "My programs overview page" "block"
    Then the "class" attribute of ".block_muprogmyoverview [data-control='next']" "css_element" should contain "disabled"
    And I log out

  Scenario: Next and previous page buttons should both be enabled when not on last or first page of programs
    Given the following "tool_muprog > program_allocations" exist:
      | user     | program    |
      | student1 | Program 01 |
      | student1 | Program 02 |
      | student1 | Program 03 |
      | student1 | Program 04 |
      | student1 | Program 05 |
      | student1 | Program 06 |
      | student1 | Program 07 |
      | student1 | Program 08 |
      | student1 | Program 09 |
      | student1 | Program 10 |
      | student1 | Program 11 |
      | student1 | Program 12 |
      | student1 | Program 13 |
      | student1 | Program 14 |
      | student1 | Program 15 |
      | student1 | Program 16 |
      | student1 | Program 17 |
      | student1 | Program 18 |
      | student1 | Program 19 |
      | student1 | Program 20 |
      | student1 | Program 21 |
      | student1 | Program 22 |
      | student1 | Program 23 |
      | student1 | Program 24 |
      | student1 | Program 25 |
    When I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I wait until ".block_muprogmyoverview [data-control='next']" "css_element" exists
    And I click on "[data-control='next']" "css_element" in the "My programs overview page" "block"
    Then the "class" attribute of ".block_muprogmyoverview [data-control='next']" "css_element" should not contain "disabled"
    And the "class" attribute of ".block_muprogmyoverview [data-control='previous']" "css_element" should not contain "disabled"
    And I should see "Program 13" in the "My programs overview page" "block"
    And I should see "Program 24" in the "My programs overview page" "block"
    But I should not see "Program 12" in the "My programs overview page" "block"
    And I should not see "Program 25" in the "My programs overview page" "block"
    And I log out
