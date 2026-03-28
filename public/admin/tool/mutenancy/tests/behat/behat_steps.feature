@tool @tool_mutenancy @MuTMS @javascript
Feature: Multi-tenancy behat step tests
  Background:
    Given unnecessary Admin bookmarks block gets deleted
    And the following "roles" exist:
      | name            | shortname |
      | Tenant viewer   | tviewer   |
    And the following "permission overrides" exist:
      | capability                      | permission | role     | contextlevel | reference |
      | tool/mutenancy:view             | Allow      | tviewer  | System       |           |
      | moodle/site:viewuseridentity    | Allow      | tviewer  | System       |           |
    And the following "users" exist:
      | username  | firstname | lastname  | email                |
      | tviewer   | Tenant    | Viewer    | tviewer@example.com  |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | tviewer   | tviewer       | System       |           |

  Scenario: tool_mutenancy behat step: the multi-tenancy is activated
    When the multi-tenancy is activated
    And I log in as "admin"
    And I navigate to "Multi-tenancy > Tenants" in site administration
    Then I should see "De-activate multi-tenancy"
    And I should see "Nothing to display"

  Scenario: tool_mutenancy behat step: I am on the Tenants page
    Given the multi-tenancy is activated
    And the following "tool_mutenancy > tenants" exist:
      | name     | idnumber |
      | Tenant 1 | TEN1     |
      | Tenant 2 | TEN2     |
    And I log in as "tviewer"
    When I am on the "tool_mutenancy > Tenants" page
    Then the following should exist in the "reportbuilder-table" table:
      | Tenant name | Tenant ID | Tenant category | Tenant users | Archived | Tenant login URL    |
      | Tenant 1    | TEN1      | Tenant 1        | 0            | No       | /login/?tenant=TEN1 |
      | Tenant 2    | TEN2      | Tenant 2        | 0            | No       | /login/?tenant=TEN2 |

  Scenario: tool_mutenancy behat step: I am on the Tenant details page
    Given the multi-tenancy is activated
    And the following "tool_mutenancy > tenants" exist:
      | name     | idnumber |
      | Tenant 1 | TEN1     |
    And I log in as "tviewer"
    When I am on the "TEN1" "tool_mutenancy > Tenant" page
    Then I should see "Tenant 1" in the "Tenant name" definition list item
    And I should see "TEN1" in the "Tenant ID" definition list item

  Scenario: Global tenant viewer may access tenant list via course_list block hack
    Given the following "categories" exist:
      | name              | idnumber |
      | Tenant 1 category | CAT1     |
    Given the following "tool_mutenancy > tenants" exist:
      | name     | idnumber | category |
      | Tenant 1 | TEN1     | CAT1     |
    And the following "users" exist:
      | username  | firstname | lastname  | email                | tenant |
      | tviewer0  | Tenant    | Viewer    | tviewer0@example.com |        |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | tviewer0  | tviewer       | System       |           |
    And the following config values are set as admin:
      | unaddableblocks |  | theme_boost |
    And I log in as "tviewer0"
    And I turn editing mode on
    And I add the "Courses" block
    And I turn editing mode off

    When I follow "Tenant 1 category"
    And I click on "More" "link" in the ".secondary-navigation" "css_element"
    And I click on "Tenant" "link" in the ".secondary-navigation" "css_element"
    Then I should see "Tenant 1" in the "Tenant name" definition list item
    And I should see "TEN1" in the "Tenant ID" definition list item

  Scenario: Tenant viewer may access tenant list via course_list block hack
    Given the following "categories" exist:
      | name              | idnumber |
      | Tenant 1 category | CAT1     |
      | Tenant 2 category | CAT2     |
    Given the following "tool_mutenancy > tenants" exist:
      | name     | idnumber | category |
      | Tenant 1 | TEN1     | CAT1     |
      | Tenant 2 | TEN2     | CAT2     |
    And the following "users" exist:
      | username  | firstname | lastname  | email                | tenant |
      | tviewer1  | Tenant 1  | Viewer    | tviewer1@example.com | TEN1   |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | tviewer1  | tviewer       | Tenant       | TEN1      |
    And the following config values are set as admin:
      | unaddableblocks |  | theme_boost |
    And I log in as "tviewer1"
    And I turn editing mode on
    And I add the "Courses" block
    And I turn editing mode off

    When I follow "Tenant 1 category"
    And I click on "More" "link" in the ".secondary-navigation" "css_element"
    And I click on "Tenant" "link" in the ".secondary-navigation" "css_element"
    Then I should see "Tenant 1" in the "Tenant name" definition list item
    And I should see "TEN1" in the "Tenant ID" definition list item

  Scenario: tool_mutenancy behat step: I am on the Tenant users page
    Given the multi-tenancy is activated
    And the following "cohorts" exist:
      | name     | idnumber |
      | Cohort 1 | COH1     |
    And the following "tool_mutenancy > tenants" exist:
      | name     | idnumber | assoccohort |
      | Tenant 1 | TEN1     | COH1            |
      | Tenant 2 | TEN2     |                 |
    And the following "users" exist:
      | username | firstname | lastname | email                | tenant |
      | student0 | Student   | 0        | student0@example.com |        |
      | student1 | Student   | 1        | student1@example.com | TEN1   |
      | student2 | Student   | 2        | student2@example.com | TEN2   |
    And the following "cohort members" exist:
      | user     | cohort  |
      | student0 | COH1    |
      | student2 | COH1    |
    And I log in as "tviewer"
    When I am on the "TEN1" "tool_mutenancy > Tenant users" page
    Then the following should exist in the "reportbuilder-table" table:
      | First name | Email address        | Tenant member |
      | Student 0  | student0@example.com | No            |
      | Student 1  | student1@example.com | Yes           |
    And I should not see "student2@example.com"

  Scenario: tool_mutenancy behat step: I am on the Tenant authentication page
    Given the multi-tenancy is activated
    And the following "tool_mutenancy > tenants" exist:
      | name     | idnumber |
      | Tenant 1 | TEN1     |
      | Tenant 2 | TEN2     |
    And I log in as "tviewer"
    When I am on the "TEN1" "tool_mutenancy > Tenant authentication" page
    Then I should see "Default value (Disabled)" in the "Self registration" definition list item

  Scenario: tool_mutenancy behat step: I am on the Tenant appearance page
    Given the multi-tenancy is activated
    And the following "tool_mutenancy > tenants" exist:
      | name     | idnumber |
      | Tenant 1 | TEN1     |
      | Tenant 2 | TEN2     |
    And I log in as "tviewer"
    When I am on the "TEN1" "tool_mutenancy > Tenant appearance" page
    Then I should see "Default value (None)" in the "Logo" definition list item

  Scenario: tool_mutenancy behat step: I am on the Tenant login page
    Given the multi-tenancy is activated
    And the following "tool_mutenancy > tenants" exist:
      | name     | idnumber | loginshow | sitefullname     | siteshortname |
      | Tenant 1 | TEN1     | 1         | Tent Site full 1 | TSS1          |
      | Tenant 2 | TEN2     | 0         | Tent Site full 2 | TSS2          |

    When I am on the "TEN1" "tool_mutenancy > Tenant login" page
    Then I should see "Log in to Tent Site full 1"

    When I am on the "TEN2" "tool_mutenancy > Tenant login" page
    Then I should see "Log in to Tent Site full 2"
