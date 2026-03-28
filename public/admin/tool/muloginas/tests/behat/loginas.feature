@tool @tool_muloginas @MuTMS @javascript
Feature: Test Log in as via Incognito window
  Background:
    Given the following "users" exist:
      | username  | firstname | lastname  | email                 |
      | manager1  | Manager   | 1         | manager1@example.com  |
      | manager2  | Manager   | 2         | manager2@example.com  |
      | student1  | Student   | 1         | student1@example.com  |
      | student2  | Student   | 2         | student2@example.com  |
    And the following "role assigns" exist:
      | user      | role         | contextlevel | reference |
      | manager1  | manager      | System       |           |
      | manager2  | manager      | System       |           |

  Scenario: Manager can see Log in as (via new Incognito window) link
    Given I am on the "student1" "user > profile" page logged in as manager1
    Then I should see "Log in as (via new Incognito window)"
