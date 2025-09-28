@tool @tool_mutenancy @MuTMS @javascript
Feature: Tenant management primary menu
  Background:
    Given unnecessary Admin bookmarks block gets deleted
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

  Scenario: Site admin may see Tenant management menu after switch to tenant
    Given I log in as "admin"
    And I should not see "Tenant management" in the ".primary-navigation" "css_element"

    When I click on "Switch tenant" "link" in the ".navbar" "css_element"
    And I set the following fields to these values:
      | Tenant      | Tenant 1         |
    And I click on "Switch tenant" "button" in the ".modal-dialog" "css_element"
    Then I should see "Tenant management" in the ".primary-navigation" "css_element"

    When I click on "Tenant management" "link" in the ".primary-navigation" "css_element"
    And I click on "Tenant 1" "link" in the ".primary-navigation" "css_element"
    Then I should see "Tenant 1" in the "Tenant name" definition list item
    And I should see "TEN1" in the "Tenant ID" definition list item

    When I click on "Tenant management" "link" in the ".primary-navigation" "css_element"
    And I click on "Tenant category" "link" in the ".primary-navigation" "css_element"
    Then I should see "Manage courses and categories"

  Scenario: Site admin may disable Tenant management menu
    When the following config values are set as admin:
      | tenantprimarynav | 0 | tool_mutenancy|
    And I log in as "admin"
    And I should not see "Tenant management" in the ".primary-navigation" "css_element"
    And I click on "Switch tenant" "link" in the ".navbar" "css_element"
    And I set the following fields to these values:
      | Tenant      | Tenant 1         |
    And I click on "Switch tenant" "button" in the ".modal-dialog" "css_element"
    Then I should not see "Tenant management" in the ".primary-navigation" "css_element"

  Scenario: Tenant manager may see Tenant management menu
    When I log in as "manager1"
    Then I should see "Tenant management" in the ".primary-navigation" "css_element"

    When I click on "Tenant management" "link" in the ".primary-navigation" "css_element"
    And I click on "Tenant 1" "link" in the ".primary-navigation" "css_element"
    Then I should see "Tenant 1" in the "Tenant name" definition list item
    And I should see "TEN1" in the "Tenant ID" definition list item

    When I click on "Tenant management" "link" in the ".primary-navigation" "css_element"
    And I click on "Tenant category" "link" in the ".primary-navigation" "css_element"
    Then I should see "Manage courses and categories"
