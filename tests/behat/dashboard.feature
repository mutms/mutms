@block @block_muprog_my @tool_muprog @MuTMS
Feature: My programs block

  @javascript
  Scenario: Users can add My programs block to their dashboard
    Given the following "users" exist:
      | username  | firstname | lastname | email                |
      | student1  | Student   | 1        | student1@example.com |
    And the following "tool_muprog > programs" exist:
      | fullname    | idnumber |
      | Program 001 | PR1      |
      | Program 002 | PR2      |
      | Program 003 | PR3      |
    And the following "tool_muprog > program_allocations" exist:
      | program     | user     |
      | Program 001 | student1 |
      | Program 002 | student1 |
    And I log in as "student1"
    And I turn editing mode on

    When I add the "My programs" block
    And I turn editing mode on
    Then I should see "Program 001"
    And I should see "Program 002"
    And I should not see "Program 003"
