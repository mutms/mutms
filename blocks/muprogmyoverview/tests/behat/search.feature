@block @block_muprogmyoverview @tool_muprog @javascript @MuTMS
Feature: My programs page overview block searching

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                | idnumber |
      | student1 | Student   | X        | student1@example.com | S1       |
      | student2 | Student   | Y        | student2@example.com | S2       |
    And the following "tool_muprog > programs" exist:
      | fullname     | idnumber | category |
      | Program 01   | C1       | 0        |
      | Program 02   | C2       | 0        |
      | Program 03   | C3       | 0        |
      | Program 04   | C4       | 0        |
      | Program 05   | C5       | 0        |
      | Program 06   | C6       | 0        |
      | Program 07   | C7       | 0        |
      | Program 08   | C8       | 0        |
      | Program 09   | C9       | 0        |
      | Program 10   | C10      | 0        |
      | Program 11   | C11      | 0        |
      | Program 12   | C12      | 0        |
      | Program 13   | C13      | 0        |
      | Fake example | Fake     | 0        |
    And the following "tool_muprog > program_allocations" exist:
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

  Scenario: There is no search if I am not allocated in any program
    When I am on the "block_muprogmyoverview > My programs" page logged in as "student2"
    Then I should see "You not have any active programs." in the "My programs overview page" "block"
    And "Search programs" "field" should not exist in the "My programs overview page" "block"
    And I log out

  Scenario: Single page search
    Given I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I set the field "Search programs" in the "My programs overview page" "block" to "Program 0"
    Then I should see "Program 01" in the "My programs overview page" "block"
    And I should not see "Program 13" in the "My programs overview page" "block"
    And I log out

  Scenario: Paginated search
    Given I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I set the field "Search programs" in the "My programs overview page" "block" to "Program"
    And I should see "Program 01" in the "My programs overview page" "block"
    And I should not see "Program 13" in the "My programs overview page" "block"
    And I click on "[data-control='next']" "css_element" in the "My programs overview page" "block"
    And I wait until ".block_muprogmyoverview [data-control='next']" "css_element" exists
    Then I should see "Program 13" in the "My programs overview page" "block"
    And I should not see "Program 01" in the "My programs overview page" "block"
