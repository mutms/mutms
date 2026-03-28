@tool @tool_mutrain @tool_muprog @javascript @MuTMS
Feature: Managers can manage credits custom program fields

  Background:
    Given I skip tests if "tool_muprog" is not installed
    Given the following "custom field categories" exist:
      | name              | component   | area     | itemid |
      | Category for test | tool_muprog | program | 0      |
    And the following "tool_muprog > programs" exist:
      | fullname  | shortname |
      | Program 1 | C1        |

  Scenario: Create a credits custom program field
    Given I log in as "admin"
    And I navigate to "Programs > Program custom fields" in site administration

    When I click add custom field of type "Training credits"
    And I set the following fields to these values:
      | Name       | Test field |
      | Short name | testfield  |
    And I click on "Save changes" "button" in the "Adding a new Training credits" "dialogue"
    Then I should see "Test field"
    And I log out

  Scenario: Edit a credits custom program field
    Given I log in as "admin"
    And I navigate to "Programs > Program custom fields" in site administration

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

  Scenario: Delete a credits custom program field
    Given I log in as "admin"
    And I navigate to "Programs > Program custom fields" in site administration

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

  Scenario: Create credits custom program field via generator
    When the following "custom fields" exist:
      | name             | category           | type    | shortname | configdata            |
      | Training Field 1 | Category for test  | mutrain | training1 |                       |
      | Training Field 2 | Category for test  | mutrain | training2 |                       |
    And the following "tool_muprog > programs" exist:
      | fullname  | shortname | customfield_training1 |
      | Program 2 | C2        | 27                    |
    And I log in as "admin"
    And I navigate to "Programs > Program custom fields" in site administration
    Then I should see "Training credits" in the "training1" "table_row"
    And I should see "Training Field 1" in the "training1" "table_row"
    And I should see "Training credits" in the "training2" "table_row"
    And I should see "Training Field 2" in the "training2" "table_row"
    And I am on the "Program 2" "tool_muprog > Program" page
    Then I should see "27" in the "Training Field 1" definition list item
    And I should not see "Training Field 2"

  Scenario: Set credits value custom field for programs
    Given the following "custom fields" exist:
      | name               | category           | type    | shortname | configdata            |
      | Optional training  | Category for test  | mutrain | training1 |                       |
      | Mandatory training | Category for test  | mutrain | training2 |                       |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | manager1 | Manager   | 1        | manager1@example.com |
    And the following "role assigns" exist:
      | user      | role         | contextlevel | reference |
      | manager1  | manager      | System       |           |
    And I log in as "manager1"
    And I am on the "Program 1" "tool_muprog > Program" page

    When I press "Edit"
    And I set the following fields to these values:
      | Mandatory training | 7 |
    And I press "Update program"
    Then I should see "7" in the "Mandatory training" definition list item
    And I should not see "Optional training"

    When I press "Edit"
    And I set the following fields to these values:
      | Mandatory training |   |
      | Optional training  | 3 |
    And I press "Update program"
    Then I should see "3" in the "Optional training" definition list item
    And I should not see "Mandatory training"
