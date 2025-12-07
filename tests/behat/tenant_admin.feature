@tool @tool_mutenancy @MuTMS @javascript
Feature: Tenant administration
  Background:
    Given unnecessary Admin bookmarks block gets deleted
    And the following "roles" exist:
      | name            | shortname |
      | Tenant admin    | tadmin    |
      | Tenant viewer   | tviewer   |
    And the following "permission overrides" exist:
      | capability                           | permission | role     | contextlevel | reference |
      | tool/mutenancy:admin                 | Allow      | tadmin   | System       |           |
      | tool/mutenancy:view                  | Allow      | tadmin   | System       |           |
      | moodle/site:configview               | Allow      | tadmin   | System       |           |
      | moodle/site:viewuseridentity         | Allow      | tviewer  | System       |           |
      | moodle/site:viewuseridentity         | Allow      | tadmin   | System       |           |
      | tool/mutenancy:view                  | Allow      | tviewer  | System       |           |
      | moodle/category:viewhiddencategories | Allow      | tviewer  | System       |           |
      | moodle/course:viewhiddencourses      | Allow      | tviewer  | System       |           |
    And the following "users" exist:
      | username  | firstname | lastname  | email                | tenant |
      | tadmin    | Tenant    | Admin     | tadmin@example.com   |        |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | tadmin    | tadmin        | System       |           |
    And the following "cohorts" exist:
      | name     | idnumber  |
      | Cohort 1 | cohort1   |
      | Cohort 2 | cohort2   |

  Scenario: System admin may activate multi-tenancy
    Given I skip tests if multi-tenancy is activated
    And I log in as "admin"
    And I navigate to "Multi-tenancy > Tenants" in site administration
    When I press "Activate multi-tenancy"
    And I should see "New roles for Tenant managers and Tenant users will be created"
    And I click on "Activate multi-tenancy" "button" in the ".modal-dialog" "css_element"
    Then I should see "Nothing to display"
    And I should see "De-activate multi-tenancy"

  Scenario: System admin may deactivate multi-tenancy
    Given the multi-tenancy is activated
    And I log in as "admin"
    And I navigate to "Multi-tenancy > Tenants" in site administration
    When I press "De-activate multi-tenancy"
    And I should see "Tenant manager role will be deleted"
    And I click on "De-activate multi-tenancy" "button" in the ".modal-dialog" "css_element"
    Then I should see "Activate multi-tenancy"

  Scenario: Tenant admin may create, update and delete tenants
    Given the multi-tenancy is activated
    And I log in as "tadmin"

    And I navigate to "Multi-tenancy > Tenants" in site administration
    When I press "Add tenant"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Tenant name  | Tenant 1 |
      | Tenant ID    | ten1     |
    And I click on "Add tenant" "button" in the ".modal-dialog" "css_element"
    Then I should see "Tenant 1" in the "Tenant name" definition list item
    And I should see "ten1" in the "Tenant ID" definition list item
    And I should see "/login/?tenant=ten1" in the "Tenant login URL" definition list item
    And I should see "No" in the "Show tenant on login page" definition list item
    And I should see "Tenant 1" in the "Tenant category" definition list item
    And I should see "Tenant users: Tenant 1" in the "Tenant cohort" definition list item
    And I should see "Not set" in the "Associated users cohort" definition list item
    And I should see "Tenant 1" in the "Tenant site name" definition list item
    And I should see "ten1" in the "Tenant site short name" definition list item
    And I should see "0" in the "Tenant users" definition list item
    And I should see "No" in the "Archived" definition list item

    And I navigate to "Multi-tenancy > Tenants" in site administration
    When I press "Add tenant"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Tenant name               | Tenant 2      |
      | Tenant ID                 | ten2          |
      | Show tenant on login page | 1             |
      | Tenant members limit      | 22            |
      | Associated users cohort   | Cohort 2      |
      | Tenant site name          | Tenant site 2 |
      | Tenant site short name    | TSS2          |
      | Tenant category name      | Cat for T2    |
      | Tenant category ID number | catt2         |
      | Tenant cohort name        | Koh for ten 2 |
      | Tenant cohort ID number   | KFT2          |
    And I click on "Add tenant" "button" in the ".modal-dialog" "css_element"
    Then I should see "Tenant 2" in the "Tenant name" definition list item
    And I should see "ten2" in the "Tenant ID" definition list item
    And I should see "/login/?tenant=ten2" in the "Tenant login URL" definition list item
    And I should see "Yes" in the "Show tenant on login page" definition list item
    And I should see "0 / 22" in the "Tenant members limit" definition list item
    And I should see "Cat for T2" in the "Tenant category" definition list item
    And I should see "Koh for ten 2" in the "Tenant cohort" definition list item
    And I should see "Cohort 2" in the "Associated users cohort" definition list item
    And I should see "Tenant site 2" in the "Tenant site name" definition list item
    And I should see "TSS2" in the "Tenant site short name" definition list item
    And I should see "0" in the "Tenant users" definition list item
    And I should see "No" in the "Archived" definition list item

    When I press "Update tenant"
    And the following fields in the ".modal-dialog" "css_element" match these values:
      | Tenant name               | Tenant 2      |
      | Tenant ID                 | ten2          |
      | Show tenant on login page | 1             |
      | Tenant members limit      | 22            |
      | Tenant site name          | Tenant site 2 |
      | Tenant site short name    | TSS2          |
      | Tenant category name      | Cat for T2    |
      | Tenant category ID number | catt2         |
      | Tenant cohort name        | Koh for ten 2 |
      | Tenant cohort ID number   | KFT2          |
    And I click on "Update tenant" "button" in the ".modal-dialog" "css_element"
    Then I should see "Tenant 2" in the "Tenant name" definition list item
    And I should see "ten2" in the "Tenant ID" definition list item
    And I should see "/login/?tenant=ten2" in the "Tenant login URL" definition list item
    And I should see "Yes" in the "Show tenant on login page" definition list item
    And I should see "0 / 22" in the "Tenant members limit" definition list item
    And I should see "Cat for T2" in the "Tenant category" definition list item
    And I should see "Koh for ten 2" in the "Tenant cohort" definition list item
    And I should see "Cohort 2" in the "Associated users cohort" definition list item
    And I should see "Tenant site 2" in the "Tenant site name" definition list item
    And I should see "TSS2" in the "Tenant site short name" definition list item
    And I should see "0" in the "Tenant users" definition list item
    And I should see "No" in the "Archived" definition list item

    When I press "Update tenant"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Tenant name               | XTenant 2      |
      | Tenant ID                 | xten2          |
      | Show tenant on login page | 0              |
      | Tenant members limit      | 0              |
      | Associated users cohort   | Cohort 1       |
      | Tenant site name          | XTenant site 2 |
      | Tenant site short name    | XTSS2          |
      | Tenant category name      | XCat for T2    |
      | Tenant category ID number | xcatt2         |
      | Tenant cohort name        | XKoh for ten 2 |
      | Tenant cohort ID number   | XKFT2          |
    And I click on "Update tenant" "button" in the ".modal-dialog" "css_element"
    Then I should see "XTenant 2" in the "Tenant name" definition list item
    And I should see "xten2" in the "Tenant ID" definition list item
    And I should see "/login/?tenant=xten2" in the "Tenant login URL" definition list item
    And I should see "No" in the "Show tenant on login page" definition list item
    And I should see "XCat for T2" in the "Tenant category" definition list item
    And I should see "XKoh for ten 2" in the "Tenant cohort" definition list item
    And I should see "Cohort 1" in the "Associated users cohort" definition list item
    And I should see "XTenant site 2" in the "Tenant site name" definition list item
    And I should see "XTSS2" in the "Tenant site short name" definition list item
    And I should see "0" in the "Tenant users" definition list item
    And I should see "No" in the "Archived" definition list item

    When I press "Update tenant"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Tenant name               | Tenant 2       |
      | Tenant ID                 | ten2           |
      | Show tenant on login page | 1              |
      | Tenant members limit      | 11             |
      | Tenant site name          |                |
      | Tenant site short name    |                |
      | Tenant category name      | Cat for T2     |
      | Tenant category ID number |                |
      | Tenant cohort name        | Koh for ten 2  |
      | Tenant cohort ID number   |                |
    And I click on "Update tenant" "button" in the ".modal-dialog" "css_element"
    Then I should see "Tenant 2" in the "Tenant name" definition list item
    And I should see "ten2" in the "Tenant ID" definition list item
    And I should see "/login/?tenant=ten2" in the "Tenant login URL" definition list item
    And I should see "Yes" in the "Show tenant on login page" definition list item
    And I should see "0 / 11" in the "Tenant members limit" definition list item
    And I should see "Cat for T2" in the "Tenant category" definition list item
    And I should see "Koh for ten 2" in the "Tenant cohort" definition list item
    And I should see "Cohort 1" in the "Associated users cohort" definition list item
    And I should see "Tenant 2" in the "Tenant site name" definition list item
    And I should see "ten2" in the "Tenant site short name" definition list item
    And I should see "0" in the "Tenant users" definition list item
    And I should see "No" in the "Archived" definition list item

    When I click on "Archive tenant" "link"
    And I click on "Archive tenant" "button" in the ".modal-dialog" "css_element"
    Then I should see "Yes" in the "Archived" definition list item

    When I click on "Restore archived tenant" "link"
    And I click on "Restore archived tenant" "button" in the ".modal-dialog" "css_element"
    Then I should see "No" in the "Archived" definition list item

    When I click on "Archive tenant" "link"
    And I click on "Archive tenant" "button" in the ".modal-dialog" "css_element"
    And I press "Delete tenant"
    And I click on "Delete tenant" "button" in the ".modal-dialog" "css_element"
    Then I should see "Tenant 1"
    And I should not see "Tenant 2"

  Scenario: Site admin may create associated users cohort
    Given the multi-tenancy is activated
    And I log in as "admin"
    And I navigate to "Multi-tenancy > Tenants" in site administration

    When I press "Add tenant"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Tenant name                    | Tenant 1      |
      | Tenant ID                      | ten1          |
      | Create associated users cohort | 1             |
    And I click on "Add tenant" "button" in the ".modal-dialog" "css_element"
    Then I should see "Tenant 1" in the "Tenant name" definition list item
    And I should see "ten1" in the "Tenant ID" definition list item
    And I should see "Associated users: Tenant 1" in the "Associated users cohort" definition list item

    And I navigate to "Multi-tenancy > Tenants" in site administration
    And I press "Add tenant"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Tenant name                    | Tenant 2      |
      | Tenant ID                      | ten2          |
    And I click on "Add tenant" "button" in the ".modal-dialog" "css_element"
    And I should see "Not set" in the "Associated users cohort" definition list item
    When I press "Update tenant"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Create associated users cohort | 1             |
    And I click on "Update tenant" "button" in the ".modal-dialog" "css_element"
    Then I should see "Associated users: Tenant 2" in the "Associated users cohort" definition list item

  Scenario: Tenant admin may assign tenant managers
    Given the multi-tenancy is activated
    And the following "tool_mutenancy > tenants" exist:
      | name     | idnumber |
      | Tenant 1 | ten1     |
      | Tenant 2 | ten2     |
      | Tenant 3 | ten3     |
    And the following "users" exist:
      | username  | firstname | lastname  | email                | tenant |
      | manager0  | Zero      | Manager   | manager0@example.com |        |
      | manager1  | First     | Manager   | manager1@example.com | ten1   |
      | manager2  | Second    | Manager   | manager2@example.com | ten2   |
    And I log in as "tadmin"
    And I navigate to "Multi-tenancy > Tenants" in site administration
    And I follow "Tenant 1"

    When I click on "Tenant managers" "link"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Tenant managers | Zero |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    Then I should see "Zero Manager" in the "Tenant managers" definition list item

    When I click on "Tenant managers" "link"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Tenant managers | First |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    Then I should see "First Manager" in the "Tenant managers" definition list item

  Scenario: Tenant admin may set and change associated users cohort
    Given the multi-tenancy is activated
    And the following "tool_mutenancy > tenants" exist:
      | name     | idnumber |
      | Tenant 1 | ten1     |
      | Tenant 2 | ten2     |
    And the following "users" exist:
      | username | firstname | lastname | email                | tenant |
      | student0 | Student   | 0        | student0@example.com |        |
      | student1 | Student   | 1        | student1@example.com | ten1   |
      | student2 | Student   | 2        | student2@example.com | ten2   |
      | student3 | Student   | 3        | student3@example.com |        |
    And the following "cohort members" exist:
      | user     | cohort  |
      | student0 | cohort1 |
      | student1 | cohort1 |
      | student2 | cohort1 |
      | student0 | cohort2 |
      | student3 | cohort2 |

    And I log in as "tadmin"
    And I am on the "ten1" "tool_mutenancy > Tenant" page

    When I press "Update tenant"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Associated users cohort   | Cohort 1       |
    And I click on "Update tenant" "button" in the ".modal-dialog" "css_element"
    And I should see "Cohort 1" in the "Associated users cohort" definition list item
    Then I should see "2" in the "Tenant users" definition list item

    When I press "Update tenant"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Associated users cohort   | Cohort 2       |
    And I click on "Update tenant" "button" in the ".modal-dialog" "css_element"
    Then I should see "Cohort 2" in the "Associated users cohort" definition list item
    And I should see "3" in the "Tenant users" definition list item

    And I am on the "ten2" "tool_mutenancy > Tenant" page
    When I press "Update tenant"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Associated users cohort   | Cohort 2       |
    And I click on "Update tenant" "button" in the ".modal-dialog" "css_element"
    And I should see "Cohort 2" in the "Associated users cohort" definition list item
    And I should see "3" in the "Tenant users" definition list item

  Scenario: Tenant admin may create, update and delete with customised tenant names
    Given the multi-tenancy is activated
    And the following config values are set as admin:
      | tenantentity   | Faculty   | tool_mutenancy |
      | tenantentities | Faculties | tool_mutenancy |
    And I log in as "tadmin"

    And I navigate to "Multi-tenancy > Tenants" in site administration
    When I press "Add Faculty"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Tenant name  | Tenant 1 |
      | Tenant ID    | ten1     |
    And I click on "Add Faculty" "button" in the ".modal-dialog" "css_element"
    Then I should see "Tenant 1" in the "Tenant name" definition list item
    And I should see "ten1" in the "Tenant ID" definition list item
    And I should see "/login/?tenant=ten1" in the "Tenant login URL" definition list item
    And I should see "No" in the "Show tenant on login page" definition list item
    And I should see "Tenant 1" in the "Tenant category" definition list item
    And I should see "Faculty users: Tenant 1" in the "Tenant cohort" definition list item
    And I should see "Not set" in the "Associated users cohort" definition list item
    And I should see "Tenant 1" in the "Tenant site name" definition list item
    And I should see "ten1" in the "Tenant site short name" definition list item
    And I should see "0" in the "Tenant users" definition list item
    And I should see "No" in the "Archived" definition list item

    And I navigate to "Multi-tenancy > Tenants" in site administration
    When I press "Add Faculty"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Tenant name               | Tenant 2      |
      | Tenant ID                 | ten2          |
      | Show tenant on login page | 1             |
      | Tenant members limit      | 22            |
      | Associated users cohort   | Cohort 2      |
      | Tenant site name          | Tenant site 2 |
      | Tenant site short name    | TSS2          |
      | Tenant category name      | Cat for T2    |
      | Tenant category ID number | catt2         |
      | Tenant cohort name        | Koh for ten 2 |
      | Tenant cohort ID number   | KFT2          |
    And I click on "Add Faculty" "button" in the ".modal-dialog" "css_element"

    When I press "Update Faculty"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Show tenant on login page | 0             |
    And I click on "Update Faculty" "button" in the ".modal-dialog" "css_element"
    Then I should see "Tenant 2" in the "Tenant name" definition list item
    And I should see "ten2" in the "Tenant ID" definition list item

    When I click on "Archive Faculty" "link"
    And I click on "Archive Faculty" "button" in the ".modal-dialog" "css_element"
    Then I should see "Yes" in the "Archived" definition list item

    When I click on "Restore archived Faculty" "link"
    And I click on "Restore archived Faculty" "button" in the ".modal-dialog" "css_element"
    Then I should see "No" in the "Archived" definition list item

    When I click on "Archive Faculty" "link"
    And I click on "Archive Faculty" "button" in the ".modal-dialog" "css_element"
    And I press "Delete Faculty"
    And I click on "Delete Faculty" "button" in the ".modal-dialog" "css_element"
    Then I should see "Tenant 1"
    And I should not see "Tenant 2"
