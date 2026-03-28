@tool @tool_muprog @tool_mutenancy @MuTMS @javascript
Feature: Program management in Tenant management primary menu
  Background:
    Given I skip tests if "tool_mutenancy" is not installed
    And unnecessary Admin bookmarks block gets deleted
    And the following "tool_mutenancy > tenants" exist:
      | name     | idnumber | sitefullname     | siteshortname | archived |
      | Tenant 1 | TEN1     | Tent Site full 1 | TSS1          | 0        |
      | Tenant 2 | TEN2     | Tent Site full 2 | TSS2          | 0        |
    And the following "users" exist:
      | username  | firstname | lastname  | email                | tenant |
      | manager1  | Tenant 1  | Manager   | manager1@example.com | TEN1   |
      | manager2  | Tenant 2  | Manager   | manager2@example.com | TEN2   |
    And the following "tool_mutenancy > tenant managers" exist:
      | tenant | user     |
      | TEN1   | manager1 |
      | TEN2   | manager2 |
    And the following "tool_muprog > programs" exist:
      | fullname    | idnumber | category |
      | Program 000 | PR0      |          |

  Scenario: Tenant manager may see Program management in Tenant management menu
    When I log in as "manager1"
    Then I should see "Tenant management" in the ".primary-navigation" "css_element"

    When I click on "Tenant management" "link" in the ".primary-navigation" "css_element"
    And I click on "Program management" "link" in the ".primary-navigation" "css_element"
    Then I should see "No programs found"
