@tool @tool_murelation @javascript @MuTMS
Feature: tool_murelation behat steps

  Scenario: tool_murelation I am on the xx page
    Given the following "tool_murelation > frameworks" exist:
      | name        | idnumber | uimode      |
      | Framework 1 | fw1      | supervisors |
      | Framework 2 | fw2      | teams       |
    And I log in as "admin"
    When I am on the "tool_murelation > User relation frameworks" page
    Then the following should exist in the "reportbuilder-table" table:
      | Framework name | Framework ID | Framework mode   |
      | Framework 1    | fw1          | Supervisors      |
      | Framework 2    | fw2          | Teams            |

  Scenario: tool_murelation I am on the xx yy page
    Given the following "tool_murelation > frameworks" exist:
      | name        | idnumber | uimode      |
      | Framework 1 | fw1      | supervisors |
      | Framework 2 | fw2      | teams       |
    And I log in as "admin"

    When I am on the "fw1" "tool_murelation > Framework" page
    Then I should see "Framework 1" in the "Framework name" definition list item
    And I should see "fw1" in the "Framework ID" definition list item

    When I am on the "Framework 2" "tool_murelation > Framework" page
    Then I should see "Framework 2" in the "Framework name" definition list item
    And I should see "fw2" in the "Framework ID" definition list item
