@block @block_muprogmyoverview @tool_muprog @javascript @MuTMS
Feature: Zero state on My programs page overview block

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
    And the following "categories" exist:
      | name        | category | idnumber |
      | Category 1  | 0        | CAT1     |
    And the following "tool_muprog > programs" exist:
      | fullname  | idnumber  | category |
      | Program 1 | C1        | 0        |
    And the following "tool_muprog > program_allocations" exist:
      | user     | program   |
      | student1 | Program 1 |

  Scenario: Users cannot see My programs menu entry if they do not have any programs
    When I log in as "student2"
    Then I should not see "My programs"

  Scenario: Users can see My programs menu if they have allocation
    When I log in as "student1"
    And I should see "My programs"
    And I follow "My programs"
    Then I should see "My programs overview page"
