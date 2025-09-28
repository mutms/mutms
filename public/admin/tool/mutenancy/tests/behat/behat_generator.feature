@tool @tool_mutenancy @MuTMS @javascript
Feature: Multi-tenancy generator tests
  Background:
    Given unnecessary Admin bookmarks block gets deleted

  Scenario: Tenant creation via generator
    When the following "tool_mutenancy > tenants" exist:
      | name     | idnumber |
      | Tenant 1 | ten1     |
      | Tenant 2 | TEN2     |
    And I log in as "admin"
    And I navigate to "Multi-tenancy > Tenants" in site administration
    And the following should exist in the "reportbuilder-table" table:
      | Tenant name | Tenant ID | Tenant category | Tenant users | Archived | Tenant login URL    |
      | Tenant 1    | ten1      | Tenant 1        | 0            | No       | /login/?tenant=ten1 |
      | Tenant 2    | TEN2      | Tenant 2        | 0            | No       | /login/?tenant=TEN2 |

    When the following "cohorts" exist:
      | name     | idnumber  |
      | Cohort 3 | cohortid3 |
    And the following "tool_mutenancy > tenants" exist:
      | name     | idnumber | loginshow | memberlimit | assoccohort | sitefullname     | siteshortname |
      | Tenant 3 | ten3     | 1         | 11          | cohortid3   | Tent Site full 3 | TSS3          |
    And I log in as "admin"
    And I navigate to "Multi-tenancy > Tenants" in site administration
    And I follow "Tenant 3"
    Then I should see "Tenant 3" in the "Tenant name" definition list item
    And I should see "ten3" in the "Tenant ID" definition list item
    And I should see "/login/?tenant=ten3" in the "Tenant login URL" definition list item
    And I should see "Yes" in the "Show tenant on login page" definition list item
    And I should see "Tenant 3" in the "Tenant category" definition list item
    And I should see "Tenant users: Tenant 3" in the "Tenant cohort" definition list item
    And I should see "Cohort 3" in the "Associated users cohort" definition list item
    And I should see "Tent Site full 3" in the "Tenant site name" definition list item
    And I should see "TSS3" in the "Tenant site short name" definition list item
    And I should see "0" in the "Tenant users" definition list item
    And I should see "No" in the "Archived" definition list item

  Scenario: Tenant member creation via generator
    Given the following "tool_mutenancy > tenants" exist:
      | name     | idnumber |
      | Tenant 1 | ten1     |
      | Tenant 2 | TEN2     |
    When the following "users" exist:
      | username | firstname | lastname | email                | tenant |
      | student0 | Student   | 0        | student0@example.com |        |
      | student1 | Student   | 1        | student1@example.com | ten1   |
      | student2 | Student   | 2        | student2@example.com | TEN2   |
    And I log in as "admin"
    And I navigate to "Multi-tenancy > Tenants" in site administration
    And I follow "Tenant 1"
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | First name | Email address        | Tenant member |
      | Student 1  | student1@example.com | Yes           |
    And I should not see "student0@example.com"
    And I should not see "student2@example.com"

  Scenario: Tenant manager added via generator
    Given the following "tool_mutenancy > tenants" exist:
      | name     | idnumber |
      | Tenant 1 | ten1     |
    And the following "users" exist:
      | username  | firstname | lastname  | email                | tenant |
      | manager0  | Global    | Manager   | manager0@example.com |        |
      | manager1  | Tenant 1  | Manager   | manager1@example.com | ten1   |
    And the following "tool_mutenancy > tenant managers" exist:
      | tenant | user     |
      | ten1   | manager0 |
      | ten1   | manager1 |
    And I log in as "admin"
    And I navigate to "Multi-tenancy > Tenants" in site administration
    And I follow "Tenant 1"
    Then I should see "Global Manager" in the "Tenant managers" definition list item
    And I should see "Tenant 1 Manager" in the "Tenant managers" definition list item
