@tool @tool_mutrain @javascript @MuTMS
Feature: Managers can manage credits custom course fields

  Background:
    Given the following "custom field categories" exist:
      | name              | component   | area   | itemid |
      | Category for test | core_course | course | 0      |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |

  Scenario: Create a credits custom course field
    Given I log in as "admin"
    And I navigate to "Courses > Course custom fields" in site administration

    When I click add custom field of type "Training credits"
    And I set the following fields to these values:
      | Name       | Test field |
      | Short name | testfield  |
    And I click on "Save changes" "button" in the "Adding a new Training credits" "dialogue"
    Then I should see "Test field"
    And I log out

  Scenario: Edit a credits custom course field
    Given I log in as "admin"
    And I navigate to "Courses > Course custom fields" in site administration

    When I click add custom field of type "Training credits"
    And I set the following fields to these values:
      | Name       | Test field |
      | Short name | testfield  |
    And I click on "Save changes" "button" in the "Adding a new Training credits" "dialogue"
    And I click Edit custom field "Test field"
    And I set the following fields to these values:
      | Name | Edited field |
    And I click on "Save changes" "button" in the "Updating Test field" "dialogue"
    Then I should see "Edited field"
    And I log out

  Scenario: Delete a credits custom course field
    Given I log in as "admin"
    And I navigate to "Courses > Course custom fields" in site administration

    When I click add custom field of type "Training credits"
    And I set the following fields to these values:
      | Name       | Test field |
      | Short name | testfield  |
    And I click on "Save changes" "button" in the "Adding a new Training credits" "dialogue"
    And I click Delete custom field "Test field"
    And I click on "Yes" "button" in the "Confirm" "dialogue"
    And I wait until the page is ready
    And I wait until "Test field" "text" does not exist
    Then I should not see "Test field"
    And I log out

  Scenario: Create credits custom course field via generator
    Given the following config values are set as admin:
      | enablemyhome | 1 |

    When the following "custom fields" exist:
      | name             | category           | type    | shortname | configdata            |
      | Training Field 1 | Category for test  | mutrain | training1 |                       |
      | Training Field 2 | Category for test  | mutrain | training2 |                       |
    And the following "courses" exist:
      | fullname | shortname | customfield_training1 |
      | Course 2 | C2        | 27                    |
    And I log in as "admin"
    And I navigate to "Courses > Course custom fields" in site administration
    Then I should see "Training credits" in the "training1" "table_row"
    And I should see "Training Field 1" in the "training1" "table_row"
    And I should see "Training credits" in the "training2" "table_row"
    And I should see "Training Field 2" in the "training2" "table_row"
    And I am on site homepage
    And I should see "Training Field 1: 27"
    And I should not see "Training Field 2"

  Scenario: Set credits value custom field for courses
    Given the following config values are set as admin:
      | enablemyhome | 1 |
    And the following "custom fields" exist:
      | name               | category           | type    | shortname | configdata            |
      | Optional training  | Category for test  | mutrain | training1 |                       |
      | Mandatory training | Category for test  | mutrain | training2 |                       |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And I log in as "teacher1"

    When I am on "Course 1" course homepage
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Mandatory training | 7 |
    And I press "Save and display"
    And I am on site homepage
    Then I should see "Mandatory training: 7"
    And I should not see "Optional training"

    When I am on "Course 1" course homepage
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Mandatory training |   |
      | Optional training  | 3 |
    And I press "Save and display"
    And I am on site homepage
    Then I should see "Optional training: 3"
    And I should not see "Mandatory training"
