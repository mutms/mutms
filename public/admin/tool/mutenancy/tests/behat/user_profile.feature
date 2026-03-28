@tool @tool_mutenancy @MuTMS @javascript
Feature: Multi-tenancy features of user profile page
  Background:
    Given unnecessary Admin bookmarks block gets deleted
    And the following "cohorts" exist:
      | name     | idnumber  |
      | Cohort 1 | cohort1   |
      | Cohort 2 | cohort2   |
    And the following "tool_mutenancy > tenants" exist:
      | name     | idnumber | sitefullname     | siteshortname | archived | assoccohort |
      | Tenant 1 | TEN1     | Tent Site full 1 | TSS1          | 0        | cohort1     |
      | Tenant 2 | TEN2     | Tent Site full 2 | TSS2          | 0        | cohort2     |
      | Tenant 3 | TEN3     | Tent Site full 3 | TSS3          | 0        |             |
    And the following "users" exist:
      | username | firstname | lastname | email                | tenant |
      | student0 | Nulty     | Student  | student0@example.com |        |
      | student1 | Prvni     | Student  | student1@example.com | TEN1   |
      | student2 | Druhy     | Student  | student2@example.com | TEN2   |
      | student3 | Treti     | Student  | student3@example.com | TEN3   |
      | manager1 | Tenant 1  | Manager  | manager1@example.com | TEN1   |
      | manager2 | Tenant 2  | Manager  | manager2@example.com | TEN2   |
    And the following "tool_mutenancy > tenant managers" exist:
      | tenant | user     |
      | TEN1   | manager1 |
      | TEN2   | manager2 |

  Scenario: Admin may see tenant membership and tenant association on user profile page
    Given I log in as "admin"

    When I am on the profile page of user "student0"
    Then I should see "No" in the "Tenant member" definition list item
    And I should not see "Associated with tenants"

    When the following "cohort members" exist:
      | user     | cohort  |
      | student0 | cohort1 |
      | student0 | cohort2 |
    And I am on the profile page of user "student0"
    Then I should see "No" in the "Tenant member" definition list item
    And I should see "Tenant 1, Tenant 2" in the "Associated with tenants" definition list item

    When I am on the profile page of user "student1"
    Then I should see "Tenant 1" in the "Tenant member" definition list item
    And I should not see "Associated with tenants"

  Scenario: Tenant manager may see tenant membership and tenant association on user profile page
    Given  I log in as "manager1"

    When I am on the profile page of user "student1"
    Then I should see "Tenant 1" in the "Tenant member" definition list item
    And I should not see "Associated with tenants"

    When I am on the profile page of user "student0"
    Then I should see "The details of this user are not available to you"

    When the following "permission overrides" exist:
      | capability                           | permission | role     | contextlevel | reference |
      | moodle/user:viewdetails              | Allow      | user     | System       |           |
    And I am on the profile page of user "student0"
    Then I should see "Australia" in the "Timezone" definition list item
    And I should not see "Tenant member"
    And I should not see "Associated with tenants"

    When I am on the profile page of user "student2"
    Then I should see "The details of this user are not available to you"

  Scenario: Tenant admin may allocate tenant members on profile page
    Given the following "roles" exist:
      | name            | shortname |
      | Tenant admin    | tadmin    |
    And the following "permission overrides" exist:
      | capability                           | permission | role     | contextlevel | reference |
      | tool/mutenancy:allocate              | Allow      | tadmin   | System       |           |
      | tool/mutenancy:view                  | Allow      | tadmin   | System       |           |
      | moodle/site:configview               | Allow      | tadmin   | System       |           |
      | moodle/site:viewuseridentity         | Allow      | tadmin   | System       |           |
      | moodle/user:viewalldetails           | Allow      | tadmin   | System       |           |
    And the following "users" exist:
      | username  | firstname | lastname  | email                | tenant |
      | tadmin    | Tenant    | Admin     | tadmin@example.com   |        |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | tadmin    | tadmin        | System       |           |
    And I log in as "tadmin"
    And I am on the profile page of user "student0"

    When I click on "Allocate user" "link"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Tenant | Tenant 1 |
    And I click on "Allocate user" "button" in the ".modal-dialog" "css_element"
    Then I should see "Tenant 1" in the "Tenant member" definition list item

    When I click on "Allocate user" "link"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Tenant | Tenant 2 |
    And I click on "Allocate user" "button" in the ".modal-dialog" "css_element"
    Then I should see "Tenant 2" in the "Tenant member" definition list item

    When I click on "Allocate user" "link"
    And I click on "Tenant 2" "text" in the ".modal-dialog" "css_element"
    And I click on "Allocate user" "button" in the ".modal-dialog" "css_element"
    Then I should see "No" in the "Tenant member" definition list item

  Scenario: Admin may see custom tenant membership name and association on user profile page
    Given the following config values are set as admin:
      | tenantentity   | Faculty   | tool_mutenancy |
      | tenantentities | Faculties | tool_mutenancy |
    And I log in as "admin"

    When I am on the profile page of user "student0"
    Then I should see "No" in the "Faculty member" definition list item
    And I should not see "Associated with Faculties"

    When the following "cohort members" exist:
      | user     | cohort  |
      | student0 | cohort1 |
      | student0 | cohort2 |
    And I am on the profile page of user "student0"
    Then I should see "No" in the "Faculty member" definition list item
    And I should see "Tenant 1, Tenant 2" in the "Associated with Faculties" definition list item

    When I am on the profile page of user "student1"
    Then I should see "Tenant 1" in the "Faculty member" definition list item
    And I should not see "Associated with Faculties"
