@tool @tool_mutenancy @MuTMS @javascript
Feature: Tenant authentication setting loginshow
  Background:
    Given unnecessary Admin bookmarks block gets deleted

  Scenario: Users may access tenant login pages
    Given I am on homepage
    When I click on "Log in" "link" in the ".logininfo" "css_element"
    Then I should see "Log in to Acceptance test site"
    And I should see "Access as a guest"
    And I should not see "Select site"

    When the following "tool_mutenancy > tenants" exist:
      | name     | idnumber | loginshow | sitefullname     | siteshortname | archived |
      | Tenant 1 | TEN1     | 1         | Tent Site full 1 | TSS1          | 0        |
      | Tenant 2 | TEN2     | 0         | Tent Site full 2 | TSS2          | 0        |
      | Tenant 3 | TEN3     | 1         | Tent Site full 3 | TSS3          | 0        |
      | Tenant 4 | TEN4     | 1         | Tent Site full 4 | TSS4          | 1        |
    And the following "users" exist:
      | username  | firstname | lastname  | email                | tenant |
      | manager1  | Tenant 1  | Manager   | manager1@example.com | TEN1   |
      | manager2  | Tenant 2  | Manager   | manager2@example.com | TEN2   |
    And the following "tool_mutenancy > tenant managers" exist:
      | tenant | user     |
      | TEN1   | manager1 |
      | TEN2   | manager2 |
    And I am on homepage
    And I click on "Log in" "link" in the ".logininfo" "css_element"
    Then I should see "Log in to Acceptance test site"
    And I should see "Access as a guest"
    And I should see "Select site"

    When I click on "Select site" "link"
    Then I should see "Tent Site full 1" in the ".dropdown-menu" "css_element"
    And I should not see "Tent Site full 2" in the ".dropdown-menu" "css_element"
    And I should see "Tent Site full 3" in the ".dropdown-menu" "css_element"
    And I should not see "Tent Site full 4" in the ".dropdown-menu" "css_element"
    And I should not see "Acceptance test site" in the ".dropdown-menu" "css_element"

    When I click on "Tent Site full 1" "link"
    Then I should see "Tent Site full 1"
    And I should not see "Access as a guest"
    And I should see "Select site"

    When I click on "Select site" "link"
    Then I should see "Acceptance test site" in the ".dropdown-menu" "css_element"
    And I should not see "Tent Site full 1" in the ".dropdown-menu" "css_element"
    And I should not see "Tent Site full 2" in the ".dropdown-menu" "css_element"
    And I should see "Tent Site full 3" in the ".dropdown-menu" "css_element"
    And I should not see "Tent Site full 4" in the ".dropdown-menu" "css_element"

    When I click on "Acceptance test site" "link"
    Then I should see "Log in to Acceptance test site"
    And I should see "Access as a guest"
    And I should see "Select site"

    When I am on the "TEN2" "tool_mutenancy > Tenant login" page
    Then I should see "Log in to Tent Site full 2"
    And I should not see "Access as a guest"
    And I should see "Select site"

    When I click on "Select site" "link"
    Then I should see "Acceptance test site" in the ".dropdown-menu" "css_element"
    And I should see "Tent Site full 1" in the ".dropdown-menu" "css_element"
    And I should not see "Tent Site full 2" in the ".dropdown-menu" "css_element"
    And I should see "Tent Site full 3" in the ".dropdown-menu" "css_element"
    And I should not see "Tent Site full 4" in the ".dropdown-menu" "css_element"

    When I am on the "TEN4" "tool_mutenancy > Tenant login" page
    Then I should see "Log in to Acceptance test site"
    And I should see "Access as a guest"
    And I should see "Select site"

    When I am on the "TEN2" "tool_mutenancy > Tenant login" page
    And I am on the "0" "tool_mutenancy > Tenant login" page
    Then I should see "Log in to Acceptance test site"
    And I should see "Access as a guest"
    And I should see "Select site"

  Scenario: Tenant managers may configure tenant login instructions
    Given the following "tool_mutenancy > tenants" exist:
      | name     | idnumber | loginshow | sitefullname     | siteshortname |
      | Tenant 1 | TEN1     | 1         | Tent Site full 1 | TSS1          |
      | Tenant 2 | TEN2     | 1         | Tent Site full 2 | TSS2          |
      | Tenant 3 | TEN3     | 1         | Tent Site full 3 | TSS3          |
    And the following "users" exist:
      | username  | firstname | lastname  | email                | tenant |
      | manager1  | Tenant 1  | Manager   | manager1@example.com | TEN1   |
      | manager2  | Tenant 2  | Manager   | manager2@example.com | TEN2   |
    And the following "tool_mutenancy > tenant managers" exist:
      | tenant | user     |
      | TEN1   | manager1 |
      | TEN2   | manager2 |
    And the following config values are set as admin:
      | auth_instructions | Welcome to main site |

    And I log in as "manager1"
    And I am on the "TEN1" "tool_mutenancy > Tenant authentication" page
    And I should see "Default value (Welcome to main site)" in the "Instructions" definition list item
    And I press "Update authentication"
    And I set the following fields to these values:
      | auth_instructions_override | 1                   |
      | auth_instructions[text]    | Welcome to Tenant 1 |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I should see "Welcome to Tenant 1" in the "Instructions" definition list item
    And I log out

    And I log in as "manager2"
    And I am on the "TEN2" "tool_mutenancy > Tenant authentication" page
    And I should see "Default value (Welcome to main site)" in the "Instructions" definition list item
    And I press "Update authentication"
    And I set the following fields to these values:
      | auth_instructions_override | 1                   |
      | auth_instructions[text]    |                     |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I should see "Empty" in the "Instructions" definition list item
    And I log out

    When I am on the "0" "tool_mutenancy > Tenant login" page
    Then I should see "Welcome to main site"

    When I am on the "TEN1" "tool_mutenancy > Tenant login" page
    Then I should see "Welcome to Tenant 1"
    And I should not see "Welcome to main site"

    When I am on the "TEN2" "tool_mutenancy > Tenant login" page
    Then I should not see "Welcome to Tenant 1"
    And I should not see "Welcome to main site"

    When I am on the "TEN3" "tool_mutenancy > Tenant login" page
    Then I should see "Welcome to main site"
