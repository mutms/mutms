@tool @tool_mutenancy @MuTMS @javascript
Feature: Tenant switching
  Background:
    Given unnecessary Admin bookmarks block gets deleted
    And the following "cohorts" exist:
      | name     | idnumber  |
      | Cohort 1 | cohort1   |
      | Cohort 2 | cohort2   |
      | Cohort 4 | cohort4   |
    And the following "tool_mutenancy > tenants" exist:
      | name     | idnumber | sitefullname     | siteshortname | archived | assoccohort |
      | Tenant 1 | TEN1     | Tent Site full 1 | TSS1          | 0        | cohort1     |
      | Tenant 2 | TEN2     | Tent Site full 2 | TSS2          | 0        | cohort2     |
      | Tenant 3 | TEN3     | Tent Site full 3 | TSS3          | 0        |             |
      | Tenant 4 | TEN4     | Tent Site full 4 | TSS4          | 1        | cohort4     |

  Scenario: Admin may switch to any active tenant
    Given I log in as "admin"
    And I should see "Acceptance test site" in the ".navbar" "css_element"

    When I click on "Switch tenant" "link" in the ".navbar" "css_element"
    And I click on "Switch tenant" "button" in the ".modal-dialog" "css_element"
    And I should see "Change required" in the ".modal-dialog" "css_element"
    And I set the following fields to these values:
      | Tenant      | Tenant 1         |
    And I click on "Switch tenant" "button" in the ".modal-dialog" "css_element"
    Then I should see "TSS1" in the ".navbar" "css_element"

    When I click on "Switch tenant" "link" in the ".navbar" "css_element"
    And I click on "Switch tenant" "button" in the ".modal-dialog" "css_element"
    And I should see "Change required" in the ".modal-dialog" "css_element"
    And I set the following fields to these values:
      | Tenant      | Tenant 2         |
    And I click on "Switch tenant" "button" in the ".modal-dialog" "css_element"
    Then I should see "TSS2" in the ".navbar" "css_element"

    When I click on "Switch tenant" "link" in the ".navbar" "css_element"
    And I set the following fields to these values:
      | Tenant      | No tenant        |
    And I click on "Switch tenant" "button" in the ".modal-dialog" "css_element"
    Then I should see "Acceptance test site" in the ".navbar" "css_element"

  Scenario: Tenant switcher may switch to associated tenants
    Given the following "roles" exist:
      | name            | shortname |
      | Tenant switcher | tswitcher |
    And the following "permission overrides" exist:
      | capability                           | permission | role      | contextlevel | reference |
      | tool/mutenancy:switch                | Allow      | tswitcher | System       |           |
    And the following "users" exist:
      | username  | firstname | lastname  | email                 | tenant |
      | tswitcher | Tenant    | Switcher  | tswitcher@example.com |        |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | tswitcher | tswitcher     | System       |           |
    And the following "cohort members" exist:
      | user      | cohort  |
      | tswitcher | cohort1 |
      | tswitcher | cohort2 |
      | tswitcher | cohort4 |
    And I log in as "tswitcher"
    And I should see "Acceptance test site" in the ".navbar" "css_element"

    When I click on "Switch tenant" "link" in the ".navbar" "css_element"
    And I set the following fields to these values:
      | Tenant      | Tenant 1         |
    And I click on "Switch tenant" "button" in the ".modal-dialog" "css_element"
    Then I should see "TSS1" in the ".navbar" "css_element"

    When I click on "Switch tenant" "link" in the ".navbar" "css_element"
    And I set the following fields to these values:
      | Tenant      | Tenant 2         |
    And I click on "Switch tenant" "button" in the ".modal-dialog" "css_element"
    Then I should see "TSS2" in the ".navbar" "css_element"

    When I click on "Switch tenant" "link" in the ".navbar" "css_element"
    And I set the following fields to these values:
      | Tenant      | No tenant        |
    And I click on "Switch tenant" "button" in the ".modal-dialog" "css_element"
    Then I should see "Acceptance test site" in the ".navbar" "css_element"

  Scenario: Tenant viewer may switch to any active tenant
    Given the following "roles" exist:
      | name          | shortname |
      | Tenant viewer | tviewer   |
    And the following "permission overrides" exist:
      | capability                           | permission | role    | contextlevel | reference |
      | tool/mutenancy:switch                | Allow      | tviewer | System       |           |
      | tool/mutenancy:view                  | Allow      | tviewer | System       |           |
    And the following "users" exist:
      | username  | firstname | lastname  | email               | tenant |
      | tviewer   | Tenant    | Viewer    | tviewer@example.com |        |
    And the following "role assigns" exist:
      | user    | role        | contextlevel | reference |
      | tviewer | tviewer     | System       |           |
    And I log in as "tviewer"
    And I should see "Acceptance test site" in the ".navbar" "css_element"

    When I click on "Switch tenant" "link" in the ".navbar" "css_element"
    And I set the following fields to these values:
      | Tenant      | Tenant 1         |
    And I click on "Switch tenant" "button" in the ".modal-dialog" "css_element"
    Then I should see "TSS1" in the ".navbar" "css_element"

    When I click on "Switch tenant" "link" in the ".navbar" "css_element"
    And I set the following fields to these values:
      | Tenant      | Tenant 2         |
    And I click on "Switch tenant" "button" in the ".modal-dialog" "css_element"
    Then I should see "TSS2" in the ".navbar" "css_element"

    When I click on "Switch tenant" "link" in the ".navbar" "css_element"
    And I set the following fields to these values:
      | Tenant      | Tenant 3         |
    And I click on "Switch tenant" "button" in the ".modal-dialog" "css_element"
    Then I should see "TSS3" in the ".navbar" "css_element"

    When I click on "Switch tenant" "link" in the ".navbar" "css_element"
    And I set the following fields to these values:
      | Tenant      | No tenant        |
    And I click on "Switch tenant" "button" in the ".modal-dialog" "css_element"
    Then I should see "Acceptance test site" in the ".navbar" "css_element"
