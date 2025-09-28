@tool @tool_musudo @MuTMS
Feature: Test tool_musudo privileged session
  Background:
    Given unnecessary Admin bookmarks block gets deleted
    And the following "users" exist:
      | username  | firstname | lastname  | email                |
      | manager1  | First     | Manager   | manager1@example.com |
      | manager2  | Second    | Manager   | manager2@example.com |

  @javascript
  Scenario: User may sudo to manager role at system context with MFA disabled
    Given I log in as "admin"
    And I navigate to "Users > Permissions > Privileged users" in site administration
    And I press "Add privileged user"
    And I set the following fields to these values:
      | User | manager1 |
      | Role | Manager  |
    And I click on "Add privileged user" "button" in the ".modal-dialog" "css_element"
    And I log out

    And I log in as "manager 1"
    And I should not see "Site administration"

    When I follow "Start privileged session" in the user menu
    And I should see "Manager in System"
    And I press "Continue"
    Then I should see "Privileged session has started."
    And I should see "Site administration"

    When I follow "End privileged session" in the user menu
    Then I should see "Privileged session has ended."
    And I should not see "Site administration"

  @javascript
  Scenario: User may sudo to manager role at system context with MFA enabled
    And I log in as "admin"
    And I set the following administration settings values:
      | MFA plugin enabled | 1 |
    And I navigate to "Users > Permissions > Privileged users" in site administration
    And I press "Add privileged user"
    And I set the following fields to these values:
      | User         | manager1 |
      | Role         | Manager  |
      | MFA required | 0        |
    And I click on "Add privileged user" "button" in the ".modal-dialog" "css_element"
    And I press "Add privileged user"
    And I set the following fields to these values:
      | User         | manager2 |
      | Role         | Manager  |
      | MFA required | 1        |
    And I click on "Add privileged user" "button" in the ".modal-dialog" "css_element"
    And I log out

    When I log in as "manager1"
    And I set the MFA secret field with valid code for "manager1"
    And I wait "1" seconds
    And I follow "Start privileged session" in the user menu
    And I press "Continue"
    Then I should see "Privileged session has started."
    And I should see "Site administration"
    And I log out

    When I log in as "manager2"
    And I set the MFA secret field with valid code for "manager2"
    And I wait "1" seconds
    And I follow "Start privileged session" in the user menu
    And I should see "Manager in System"
    And I set the MFA secret field with valid code for "manager2"
    Then I should see "Privileged session has started."
    And I should see "Site administration"
    And I log out

  @javascript
  Scenario: Sudo user gets locked out if invalid MFA code provided repeatedly
    And I log in as "admin"
    And I set the following administration settings values:
      | MFA plugin enabled | 1 |
      | Lockout threshold  | 3 |
    And I navigate to "Users > Permissions > Privileged users" in site administration
    And I press "Add privileged user"
    And I set the following fields to these values:
      | User         | manager1 |
      | Role         | Manager  |
      | MFA required | 1        |
    And I click on "Add privileged user" "button" in the ".modal-dialog" "css_element"
    And I log out

    When I log in as "manager1"
    And I set the MFA secret field with valid code for "manager1"
    And I wait "1" seconds
    And I follow "Start privileged session" in the user menu
    And I set the following fields to these values:
      | Enter code | 123456 |
    And I should see "Wrong code. Try again."
    And I should see "You have 2 attempts left."
    And I set the following fields to these values:
      | Enter code | 654321 |
    And I should see "Wrong code. Try again."
    And I should see "You have 1 attempts left."
    And I set the following fields to these values:
      | Enter code | 123456 |
    And I should see "Locked"
    And I press "Cancel"
    And I log out

    When I log in as "manager1"
    Then I should see "Unable to authenticate"
