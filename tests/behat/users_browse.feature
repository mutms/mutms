@tool @tool_mutenancy @MuTMS @javascript
Feature: Multi-tenancy features of browse users page
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

  Scenario: Admin may filters users by tenants on browse users page
    Given I log in as "admin"
    And I navigate to "Users > Accounts > Browse list of users" in site administration

    When I click on "Filters" "button"
    And I set the following fields in the "Tenant name" "core_reportbuilder > Filter" to these values:
      | Tenant name operator | Is equal to |
      | Tenant name value    | Tenant 1    |
    And I click on "Apply" "button" in the "[data-region='report-filters']" "css_element"
    And I click on "Filters" "button"
    Then the following should exist in the "reportbuilder-table" table:
      | First name     | Email address        | Tenant   |
      | Prvni Student  | student1@example.com | Tenant 1 |
    And I should not see "Nulty Student"
    And I should not see "Druhy Student"

    When I click on "Filters" "button"
    And I set the following fields in the "Tenant name" "core_reportbuilder > Filter" to these values:
      | Tenant name operator | Is any value |
    And I set the following fields in the "Tenant member" "core_reportbuilder > Filter" to these values:
      | Tenant member operator  | Yes |
    And I click on "Apply" "button" in the "[data-region='report-filters']" "css_element"
    And I click on "Filters" "button"
    Then the following should exist in the "reportbuilder-table" table:
      | First name     | Email address        | Tenant   |
      | Prvni Student  | student1@example.com | Tenant 1 |
      | Druhy Student  | student2@example.com | Tenant 2 |
      | Treti Student  | student3@example.com | Tenant 3 |
    And I should not see "Nulty Student"

    When I click on "Filters" "button"
    And I set the following fields in the "Tenant member" "core_reportbuilder > Filter" to these values:
      | Tenant member operator  | No |
    And I click on "Apply" "button" in the "[data-region='report-filters']" "css_element"
    And I click on "Filters" "button"
    Then the following should exist in the "reportbuilder-table" table:
      | First name     | Email address        | Tenant   |
      | Nulty Student  | student0@example.com |          |
    And I should not see "Prvni Student"

    When I click on "Filters" "button"
    And I set the following fields in the "Tenant member" "core_reportbuilder > Filter" to these values:
      | Tenant member operator  | Is any value |
    And I set the following fields in the "Tenant ID" "core_reportbuilder > Filter" to these values:
      | Tenant ID operator | Is equal to |
      | Tenant ID value    | TEN1        |
    And I click on "Apply" "button" in the "[data-region='report-filters']" "css_element"
    And I click on "Filters" "button"
    Then the following should exist in the "reportbuilder-table" table:
      | First name     | Email address        | Tenant   |
      | Prvni Student  | student1@example.com | Tenant 1 |
    And I should not see "Nulty Student"
    And I should not see "Druhy Student"

    When I click on "Tenant 1" "link" in the "Prvni Student" "table_row"
    Then I should see "Tenant 1" in the "Tenant name" definition list item
    And I should see "TEN1" in the "Tenant ID" definition list item

  Scenario: Tenant admin may allocate tenant members on browse users page
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
      | moodle/user:update                   | Allow      | tadmin   | System       |           |
    And the following "users" exist:
      | username  | firstname | lastname  | email                | tenant |
      | tadmin    | Tenant    | Admin     | tadmin@example.com   |        |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | tadmin    | tadmin        | System       |           |
    And I log in as "tadmin"

    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And the following should exist in the "reportbuilder-table" table:
      | First name     | Email address        | Tenant   |
      | Nulty Student  | student0@example.com |          |
      | Prvni Student  | student1@example.com | Tenant 1 |
      | Druhy Student  | student2@example.com | Tenant 2 |
      | Treti Student  | student3@example.com | Tenant 3 |

    When I click on "Actions" "link" in the "Nulty Student" "table_row"
    And I click on "Allocate user" "link" in the "Nulty Student" "table_row"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Tenant | Tenant 1 |
    And I click on "Allocate user" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | First name     | Email address        | Tenant   |
      | Nulty Student  | student0@example.com | Tenant 1 |

    When I click on "Actions" "link" in the "Nulty Student" "table_row"
    And I click on "Allocate user" "link" in the "Nulty Student" "table_row"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Tenant | Tenant 2 |
    And I click on "Allocate user" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | First name     | Email address        | Tenant   |
      | Nulty Student  | student0@example.com | Tenant 2 |

    When I click on "Actions" "link" in the "Nulty Student" "table_row"
    And I click on "Allocate user" "link" in the "Nulty Student" "table_row"
    And I click on "Tenant 2" "text" in the ".modal-dialog" "css_element"
    And I click on "Allocate user" "button" in the ".modal-dialog" "css_element"
    Then I should not see "Tenant" in the "Nulty Student" "table_row"

  Scenario: Site admin may bulk allocate tenant members
    Given I log in as "admin"
    When I navigate to "Users > Accounts > Browse list of users" in site administration
    And I click on "Nulty Student" "checkbox"
    And I click on "Prvni Student" "checkbox"
    And I click on "Druhy Student" "checkbox"
    And the "Bulk user actions" select box should contain "Allocate users to tenant"
    And I set the field "Bulk user actions" to "Allocate users to tenant"
    And I set the following fields to these values:
      | Tenant | Tenant 1 |
    And I press "Allocate users to tenant"
    Then the following should exist in the "reportbuilder-table" table:
      | First name     | Email address        | Tenant   |
      | Nulty Student  | student0@example.com | Tenant 1 |
      | Prvni Student  | student1@example.com | Tenant 1 |
      | Druhy Student  | student2@example.com | Tenant 1 |
      | Treti Student  | student3@example.com | Tenant 3 |

  Scenario: Site admin may bulk deallocate tenant members
    Given I log in as "admin"
    When I navigate to "Users > Accounts > Browse list of users" in site administration
    And I click on "Nulty Student" "checkbox"
    And I click on "Prvni Student" "checkbox"
    And the "Bulk user actions" select box should contain "Allocate users to tenant"
    And I set the field "Bulk user actions" to "Deallocate tenant members"
    And I press "Deallocate tenant members"
    Then the following should exist in the "reportbuilder-table" table:
      | First name     | Email address        | Tenant   |
      | Nulty Student  | student0@example.com |          |
      | Prvni Student  | student1@example.com |          |
      | Druhy Student  | student2@example.com | Tenant 2 |
      | Treti Student  | student3@example.com | Tenant 3 |
