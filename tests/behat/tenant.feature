@tool @tool_muhome @javascript @MuTMS
Feature: Multi-tenancy use cases for custom home pages
  Background:
    Given I skip tests if "tool_mutenancy" is not installed
    And the following "tool_mutenancy > tenants" exist:
      | name     | idnumber | sitefullname     | siteshortname | archived | categoryidnumber | categoryname |
      | Tenant 1 | TEN1     | Tent Site full 1 | TSS1          | 0        | TC1              | Tenant cat 1 |
      | Tenant 2 | TEN2     | Tent Site full 2 | TSS2          | 0        | TC2              | Tenant cat 2 |
    And the following "users" exist:
      | username  | firstname | lastname  | email                | tenant |
      | manager1  | Tenant 1  | Manager   | manager1@example.com | TEN1   |
      | manager2  | Tenant 2  | Manager   | manager2@example.com | TEN2   |
      | user0     | User      | 0         | user0@example.com    |        |
      | user1     | User      | 1         | user1@example.com    | TEN1   |
      | user2     | User      | 2         | user2@example.com    | TEN2   |
    And the following "tool_mutenancy > tenant managers" exist:
      | tenant | user     |
      | TEN1   | manager1 |
      | TEN2   | manager2 |
    And the following "tool_muhome > pages" exist:
      | name         | status   | guestvisible | uservisible | title              | hiddenfromtenants | contextlevel | reference |
      | Other page 0 | active   | 1            | 1           | Other page title 0 | 0                 |              |           |
      | Other page 1 | active   | 1            | 1           | Other page title 1 | 0                 | Category     | TC1       |
      | Other page 2 | active   | 1            | 1           | Other page title 2 | 0                 | Category     | TC2       |
      | Other page 3 | active   | 1            | 1           | Other page title 3 | 1                 |              |           |

  Scenario: Tenant pages are not visible outside of tenants and hiddenfromtenants is respected
    Given the following config values are set as admin:
      | replacehome        | 0          | tool_muhome |
      | addmenu            | Fancy menu | tool_muhome |
      | defaulthomepage    | 0          |             |
    And I am on site homepage

    When I click on "Fancy menu" "link" in the ".primary-navigation" "css_element"
    And  I click on "Other page 0" "link" in the ".primary-navigation" "css_element"
    Then I should see "Other page title 0" in the "h1" "css_element"

    When I click on "Fancy menu" "link" in the ".primary-navigation" "css_element"
    And  I click on "Other page 3" "link" in the ".primary-navigation" "css_element"
    Then I should see "Other page title 3" in the "h1" "css_element"

    When I click on "Fancy menu" "link" in the ".primary-navigation" "css_element"
    Then I should not see "Other page title 1" in the ".primary-navigation" "css_element"
    And I should not see "Other page title 2" in the ".primary-navigation" "css_element"

    And I log out
    And I log in as "user0"

    When I click on "Fancy menu" "link" in the ".primary-navigation" "css_element"
    And  I click on "Other page 0" "link" in the ".primary-navigation" "css_element"
    Then I should see "Other page title 0" in the "h1" "css_element"

    When I click on "Fancy menu" "link" in the ".primary-navigation" "css_element"
    And  I click on "Other page 3" "link" in the ".primary-navigation" "css_element"
    Then I should see "Other page title 3" in the "h1" "css_element"

    When I click on "Fancy menu" "link" in the ".primary-navigation" "css_element"
    Then I should not see "Other page title 1" in the ".primary-navigation" "css_element"
    And I should not see "Other page title 2" in the ".primary-navigation" "css_element"

    And I log out
    And I log in as "user1"

    When I click on "Fancy menu" "link" in the ".primary-navigation" "css_element"
    And  I click on "Other page 0" "link" in the ".primary-navigation" "css_element"
    Then I should see "Other page title 0" in the "h1" "css_element"

    When I click on "Fancy menu" "link" in the ".primary-navigation" "css_element"
    And  I click on "Other page 1" "link" in the ".primary-navigation" "css_element"
    Then I should see "Other page title 1" in the "h1" "css_element"

    When I click on "Fancy menu" "link" in the ".primary-navigation" "css_element"
    Then I should not see "Other page title 2" in the ".primary-navigation" "css_element"
    And I should not see "Other page title 3" in the ".primary-navigation" "css_element"

  Scenario: Admin may see tenant custom pages management menu after switch
    Given I log in as "admin"
    And I click on "Switch tenant" "link" in the ".navbar" "css_element"
    And I click on "Switch tenant" "button" in the ".modal-dialog" "css_element"
    And I set the following fields to these values:
      | Tenant      | Tenant 1         |
    When I click on "Switch tenant" "button" in the ".modal-dialog" "css_element"
    Then I should see "Tenant management" in the ".primary-navigation" "css_element"

    When I click on "Tenant management" "link" in the ".primary-navigation" "css_element"
    And I click on "Home pages management" "link" in the ".primary-navigation" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | Page priority | Page name       | Management category | Status   |
      | 990           | Other page 1    | Tenant cat 1        | Active   |

  Scenario: Tenant manager may see tenant custom pages management menu
    When I log in as "manager1"
    Then I should see "Tenant management" in the ".primary-navigation" "css_element"

    When I click on "Tenant management" "link" in the ".primary-navigation" "css_element"
    And I click on "Home pages management" "link" in the ".primary-navigation" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | Page priority | Page name       | Management category | Status   |
      | 990           | Other page 1    | Tenant cat 1        | Active   |
