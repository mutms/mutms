@block @block_muprogmyoverview @tool_muprog @javascript @MuTMS
Feature: The My programs page overview block allows users to persistence of their page limits

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                | idnumber |
      | student1 | Student   | X        | student1@example.com | S1       |
    And the following "tool_muprog > programs" exist:
      | fullname   | idnumber   | category |
      | Program 1  | C01        | 0        |
      | Program 2  | C02        | 0        |
      | Program 3  | C03        | 0        |
      | Program 4  | C04        | 0        |
      | Program 5  | C05        | 0        |
      | Program 6  | C06        | 0        |
      | Program 7  | C07        | 0        |
      | Program 8  | C08        | 0        |
      | Program 9  | C09        | 0        |
      | Program 10 | C10        | 0        |
      | Program 11 | C11        | 0        |
      | Program 12 | C12        | 0        |
      | Program 13 | C13        | 0        |
    And the following "tool_muprog > program_allocations" exist:
      | user     | program    |
      | student1 | Program 1  |
      | student1 | Program 2  |
      | student1 | Program 3  |
      | student1 | Program 4  |
      | student1 | Program 5  |
      | student1 | Program 6  |
      | student1 | Program 7  |
      | student1 | Program 8  |
      | student1 | Program 9  |
      | student1 | Program 10 |
      | student1 | Program 11 |
      | student1 | Program 12 |
      | student1 | Program 13 |

  Scenario: Toggle the page limit between page reloads
    Given I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    When I click on "[data-action='limit-toggle']" "css_element" in the "My programs overview page" "block"
    And I click on "All" "link" in the ".dropdown-menu.show" "css_element"
    Then I should see "Program 13"
    And I reload the page
    Then I should see "Program 13"
    And I should see "All" in the ".block-muprogmyoverview [data-action='limit-toggle']" "css_element"

  Scenario: Toggle the page limit between grouping changes
    Given I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    When I click on "[data-action='limit-toggle']" "css_element" in the "My programs overview page" "block"
    And I click on "All" "link" in the ".dropdown-menu.show" "css_element"
    And I click on "All" "button" in the "My programs overview page" "block"
    And I click on "In progress" "link" in the "My programs overview page" "block"
    Then I should see "Program 13"
    And I should see "All" in the ".block-muprogmyoverview [data-action='limit-toggle']" "css_element"
