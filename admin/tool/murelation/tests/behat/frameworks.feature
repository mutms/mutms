@tool @tool_murelation @javascript @MuTMS
Feature: Site managers can manage frameworks for user relations
  Background:
    Given the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
      | Cat 3 | CAT2     | CAT3     |
    And the following "cohorts" exist:
      | name       | idnumber | contextlevel | reference |
      | Cohort 1   | CH1      | System       |           |
      | Cohort 2   | CH2      | System       |           |
      | Cohort 3   | CH3      | System       |           |
      | Cohort 4   | CH4      | Category     | CAT2      |
      | Cohort 5   | CH5      | Category     | CAT3      |
    And the following "users" exist:
      | username  | firstname | lastname  | email                 |
      | manager1  | Manager   | 1         | manager1@example.com  |
      | manager2  | Manager   | 2         | manager2@example.com  |
      | viewer1   | Viewer    | 1         | viewer1@example.com   |
    And the following "roles" exist:
      | name              | shortname |
      | Framework viewer  | fviewer   |
      | Framework manager | fmanager  |
    And the following "permission overrides" exist:
      | capability                       | permission | role     | contextlevel | reference |
      | tool/murelation:viewframeworks   | Allow      | fmanager | System       |           |
      | tool/murelation:manageframeworks | Allow      | fmanager | System       |           |
      | moodle/cohort:view               | Allow      | fmanager | System       |           |
      | moodle/site:configview           | Allow      | fmanager | System       |           |
      | tool/murelation:viewframeworks   | Allow      | fviewer  | System       |           |
      | moodle/site:configview           | Allow      | fviewer  | System       |           |
    And the following "role assigns" exist:
      | user      | role         | contextlevel | reference |
      | manager1  | fmanager     | System       |           |
      | viewer1   | fviewer      | System       |           |
    And the following "tool_murelation > supervisor_roles" exist:
      | shortname | name     |
      | umanager  | UManager |
      | tmanager  | TManager |

  Scenario: Framework manager may create user relation frameworks
    Given I log in as "manager1"
    And I navigate to "Users > Supervisors and teams > User relation frameworks" in site administration

    When I press "Add framework"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Framework name      | Framework 1 |
      | Supervisors         | 1           |
      | Supervisor title    | Parent      |
      | Supervisors plural  | Parents     |
      | Subordinate title   | Child       |
      | Subordinates plural | Children    |
    And I click on "Add framework" "button" in the ".modal-dialog" "css_element"
    Then I should see "Framework 1" in the "Framework name" definition list item
    And I should see "Not set" in the "Framework ID" definition list item
    And I should see "Supervisors" in the "Framework mode" definition list item
    And I should see "Position managers, supervisors, course teachers and subordinates" in the "Positions visibility" definition list item
    And I should see "Not set" in the "Management restricted to cohort" definition list item
    And I should see "Parent" in the "Supervisor title" definition list item
    And I should see "Parents" in the "Supervisors plural" definition list item
    And I should see "Not set" in the "Supervisor candidates cohort" definition list item
    And I should see "Not set" in the "Supervisor role" definition list item
    And I should see "Child" in the "Subordinate title" definition list item
    And I should see "Children" in the "Subordinates plural" definition list item
    And I should see "Not set" in the "Subordinate candidates cohort" definition list item

    And I navigate to "Users > Supervisors and teams > User relation frameworks" in site administration
    When I press "Add framework"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Framework name                  | Framework 2 |
      | Framework ID                    | fw2         |
      | Supervisors                     | 1           |
      | Description                     | Desc fw 2   |
      | Positions visibility            | Everybody   |
      | Management restricted to cohort | CH1         |
      | Supervisor title                | Rodic       |
      | Supervisors plural              | Rodice      |
      | Supervisor candidates cohort    | CH2         |
      | Supervisor role                 | UManager    |
      | Subordinate title               | Potomek     |
      | Subordinates plural             | Potomci     |
      | Subordinate candidates cohort   | CH3         |
    And I click on "Add framework" "button" in the ".modal-dialog" "css_element"
    Then I should see "Framework 2" in the "Framework name" definition list item
    And I should see "Desc fw 2"
    And I should see "fw2" in the "Framework ID" definition list item
    And I should see "Supervisors" in the "Framework mode" definition list item
    And I should see "Everybody" in the "Positions visibility" definition list item
    And I should see "Cohort 1" in the "Management restricted to cohort" definition list item
    And I should see "Rodic" in the "Supervisor title" definition list item
    And I should see "Rodice" in the "Supervisors plural" definition list item
    And I should see "Cohort 2" in the "Supervisor candidates cohort" definition list item
    And I should see "UManager" in the "Supervisor role" definition list item
    And I should see "Potomek" in the "Subordinate title" definition list item
    And I should see "Potomci" in the "Subordinates plural" definition list item
    And I should see "Cohort 3" in the "Subordinate candidates cohort" definition list item

    And I navigate to "Users > Supervisors and teams > User relation frameworks" in site administration
    When I press "Add framework"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Framework name                  | Framework 3 |
      | Framework ID                    | fw3         |
      | Teams                           | 1           |
      | Description                     | Desc fw 3   |
      | Positions visibility            | Everybody   |
      | Management restricted to cohort | CH1         |
      | Supervisor title                | Velitel     |
      | Supervisors plural              | Velitele    |
      | Supervisor candidates cohort    | CH2         |
      | Supervisor role                 | UManager    |
      | Subordinate title               | Pesak       |
      | Subordinates plural             | Pesaci      |
      | Subordinate candidates cohort   | CH3         |
    And I click on "Add framework" "button" in the ".modal-dialog" "css_element"
    Then I should see "Framework 3" in the "Framework name" definition list item
    And I should see "Desc fw 3"
    And I should see "fw3" in the "Framework ID" definition list item
    And I should see "Teams" in the "Framework mode" definition list item
    And I should see "Everybody" in the "Positions visibility" definition list item
    And I should see "Cohort 1" in the "Management restricted to cohort" definition list item
    And I should see "Velitel" in the "Supervisor title" definition list item
    And I should see "Velitele" in the "Supervisors plural" definition list item
    And I should see "Cohort 2" in the "Supervisor candidates cohort" definition list item
    And I should see "UManager" in the "Supervisor role" definition list item
    And I should see "Pesak" in the "Subordinate title" definition list item
    And I should see "Pesaci" in the "Subordinates plural" definition list item
    And I should see "Cohort 3" in the "Subordinate candidates cohort" definition list item

  Scenario: Framework manager may update user relation frameworks
    Given the following "tool_murelation > frameworks" exist:
      | name        | uimode      | visibility | supervisortitle | supervisorstitle | subordinatetitle | subordinatestitle |
      | Framework 1 | supervisors | managers   | Ucitel          | Ucitele          | Zak              | Zaci              |
      | Framework 2 | teams       | everybody  | Velitel         | Velitele         | Pesak            | Pesaci            |
    And I log in as "manager1"

    And I navigate to "Users > Supervisors and teams > User relation frameworks" in site administration
    And I follow "Framework 1"
    When I press "Update framework"
    And the following fields in the ".modal-dialog" "css_element" match these values:
      | Framework name                  | Framework 1         |
      | Positions visibility            | Position managers   |
      | Supervisor title                | Ucitel              |
      | Supervisors plural              | Ucitele             |
      | Subordinate title               | Zak                 |
      | Subordinates plural             | Zaci                |
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Framework name                  | Framework 1x |
      | Framework ID                    | fw1x         |
      | Description                     | Desc fw 1x   |
      | Positions visibility            | Everybody    |
      | Management restricted to cohort | CH1          |
      | Supervisor title                | Parent       |
      | Supervisors plural              | Parents      |
      | Supervisor candidates cohort    | CH2          |
      | Supervisor role                 | UManager     |
      | Subordinate title               | Child        |
      | Subordinates plural             | Children     |
      | Subordinate candidates cohort   | CH3          |
    And I click on "Update framework" "button" in the ".modal-dialog" "css_element"
    Then I should see "Framework 1x" in the "Framework name" definition list item
    And I should see "Desc fw 1x"
    And I should see "fw1x" in the "Framework ID" definition list item
    And I should see "Supervisors" in the "Framework mode" definition list item
    And I should see "Everybody" in the "Positions visibility" definition list item
    And I should see "Cohort 1" in the "Management restricted to cohort" definition list item
    And I should see "Parent" in the "Supervisor title" definition list item
    And I should see "Parents" in the "Supervisors plural" definition list item
    And I should see "Cohort 2" in the "Supervisor candidates cohort" definition list item
    And I should see "UManager" in the "Supervisor role" definition list item
    And I should see "Child" in the "Subordinate title" definition list item
    And I should see "Children" in the "Subordinates plural" definition list item
    And I should see "Cohort 3" in the "Subordinate candidates cohort" definition list item

    And I navigate to "Users > Supervisors and teams > User relation frameworks" in site administration
    And I follow "Framework 2"
    When I press "Update framework"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Framework name                  | Framework 2x      |
      | Framework ID                    | fw2x              |
      | Description                     | Desc fw 2x        |
      | Positions visibility            | Position managers |
      | Management restricted to cohort | CH1               |
      | Supervisor title                | Leader            |
      | Supervisors plural              | Leaders           |
      | Supervisor candidates cohort    | CH2               |
      | Supervisor role                 | UManager          |
      | Subordinate title               | Follower          |
      | Subordinates plural             | Followers         |
      | Subordinate candidates cohort   | CH3               |
    And I click on "Update framework" "button" in the ".modal-dialog" "css_element"
    Then I should see "Framework 2x" in the "Framework name" definition list item
    And I should see "Desc fw 2x"
    And I should see "fw2x" in the "Framework ID" definition list item
    And I should see "Teams" in the "Framework mode" definition list item
    And I should see "Position managers" in the "Positions visibility" definition list item
    And I should see "Cohort 1" in the "Management restricted to cohort" definition list item
    And I should see "Leader" in the "Supervisor title" definition list item
    And I should see "Leaders" in the "Supervisors plural" definition list item
    And I should see "Cohort 2" in the "Supervisor candidates cohort" definition list item
    And I should see "UManager" in the "Supervisor role" definition list item
    And I should see "Follower" in the "Subordinate title" definition list item
    And I should see "Followers" in the "Subordinates plural" definition list item
    And I should see "Cohort 3" in the "Subordinate candidates cohort" definition list item

  Scenario: Framework manager may delete user relation frameworks
    Given the following "tool_murelation > frameworks" exist:
      | name        | idnumber | uimode      | description           | visibility | managecohort | supervisortitle | supervisorstitle | supervisorcohort | supervisorrole | subordinatetitle | subordinatestitle | subordinatecohort |
      | Framework 1 | fw1      | supervisors | Some framework 1 desc | managers   | CH1          | Ucitel          | Ucitele          | CH2              | umanager       | Zak              | Zaci              | CH3               |
      | Framework 2 | fw2      | teams       | Some framework 2 desc | everybody  | CH1          | Velitel         | Velitele         | CH2              | tmanager       | Pesak            | Pesaci            | CH3               |
    And I log in as "manager1"

    And I navigate to "Users > Supervisors and teams > User relation frameworks" in site administration
    And I follow "Framework 1"

    When I click on "Delete framework" action from "Framework actions" dropdown
    And I click on "Delete framework" "button" in the ".modal-dialog" "css_element"
    Then I should not see "Framework 1"
    And I should see "Framework 2"

    And I follow "Framework 2"
    When I click on "Delete framework" action from "Framework actions" dropdown
    And I click on "Delete framework" "button" in the ".modal-dialog" "css_element"
    Then I should see "No user relation frameworks found"

  Scenario: Framework viewer may see all user relation frameworks
    Given the following "tool_murelation > frameworks" exist:
      | name        | idnumber | uimode      | description           | visibility | managecohort | supervisortitle | supervisorstitle | supervisorcohort | supervisorrole | subordinatetitle | subordinatestitle | subordinatecohort |
      | Framework 1 | fw1      | supervisors | Some framework 1 desc | managers   | CH1          | Ucitel          | Ucitele          | CH2              | umanager       | Zak              | Zaci              | CH3               |
      | Framework 2 | fw2      | teams       | Some framework 2 desc | everybody  | CH1          | Velitel         | Velitele         | CH2              | tmanager       | Pesak            | Pesaci            | CH3               |
    And I log in as "viewer1"

    And I navigate to "Users > Supervisors and teams > User relation frameworks" in site administration

    Then the following should exist in the "reportbuilder-table" table:
      | Framework name | Framework ID | Framework mode   | Supervisor title  | Supervisor role | Subordinate title |
      | Framework 1    | fw1          | Supervisors      | Ucitel            | UManager        | Zak               |
      | Framework 2    | fw2          | Teams            | Velitel           | TManager        | Pesak             |

    And I navigate to "Users > Supervisors and teams > User relation frameworks" in site administration
    And I follow "Framework 1"
    And I should see "Some framework 1 desc"
    And I should see "Framework 1" in the "Framework name" definition list item
    And I should see "fw1" in the "Framework ID" definition list item
    And I should see "Supervisors" in the "Framework mode" definition list item
    And I should see "Position managers" in the "Positions visibility" definition list item
    And I should see "Cohort 1" in the "Management restricted to cohort" definition list item
    And I should see "Ucitel" in the "Supervisor title" definition list item
    And I should see "Ucitele" in the "Supervisors plural" definition list item
    And I should see "Cohort 2" in the "Supervisor candidates cohort" definition list item
    And I should see "UManager" in the "Supervisor role" definition list item
    And I should see "Zak" in the "Subordinate title" definition list item
    And I should see "Zaci" in the "Subordinates plural" definition list item
    And I should see "Cohort 3" in the "Subordinate candidates cohort" definition list item

    And I navigate to "Users > Supervisors and teams > User relation frameworks" in site administration
    And I follow "Framework 2"
    And I should see "Some framework 2 desc"
    And I should see "Framework 2" in the "Framework name" definition list item
    And I should see "fw2" in the "Framework ID" definition list item
    And I should see "Teams" in the "Framework mode" definition list item
    And I should see "Everybody" in the "Positions visibility" definition list item
    And I should see "Cohort 1" in the "Management restricted to cohort" definition list item
    And I should see "Velitel" in the "Supervisor title" definition list item
    And I should see "Velitele" in the "Supervisors plural" definition list item
    And I should see "Cohort 2" in the "Supervisor candidates cohort" definition list item
    And I should see "TManager" in the "Supervisor role" definition list item
    And I should see "Pesak" in the "Subordinate title" definition list item
    And I should see "Pesaci" in the "Subordinates plural" definition list item
    And I should see "Cohort 3" in the "Subordinate candidates cohort" definition list item

  Scenario: Framework manager may create and update tenant user relation frameworks
    Given I skip tests if "tool_mutenancy" is not installed
    And the following "tool_mutenancy > tenants" exist:
      | name     | idnumber |
      | Tenant 1 | TEN1     |
      | Tenant 2 | TEN2     |
      | Tenant 3 | TEN3     |
    And I log in as "manager1"
    And I navigate to "Users > Supervisors and teams > User relation frameworks" in site administration

    When I press "Add framework"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Framework name      | Framework 1 |
      | Supervisors         | 1           |
      | Supervisor title    | Parent      |
      | Supervisors plural  | Parents     |
      | Subordinate title   | Child       |
      | Subordinates plural | Children    |
    And I click on "Add framework" "button" in the ".modal-dialog" "css_element"
    Then I should see "Framework 1" in the "Framework name" definition list item
    And I should see "Not set" in the "Framework ID" definition list item
    And I should see "Supervisors" in the "Framework mode" definition list item
    And I should see "Position managers, supervisors, course teachers and subordinates" in the "Positions visibility" definition list item
    And I should see "Not set" in the "Management restricted to cohort" definition list item
    And I should see "Yes" in the "Available in all tenants" definition list item
    And I should see "Parent" in the "Supervisor title" definition list item
    And I should see "Parents" in the "Supervisors plural" definition list item
    And I should see "Not set" in the "Supervisor candidates cohort" definition list item
    And I should see "Not set" in the "Supervisor role" definition list item
    And I should see "Child" in the "Subordinate title" definition list item
    And I should see "Children" in the "Subordinates plural" definition list item
    And I should see "Not set" in the "Subordinate candidates cohort" definition list item

    And I navigate to "Users > Supervisors and teams > User relation frameworks" in site administration

    When I press "Add framework"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Framework name           | Framework 2        |
      | Supervisors              | 1                  |
      | Available in all tenants | 0                  |
      | Tenants                  | Tenant 1, Tenant 2 |
      | Supervisor title         | Parent             |
      | Supervisors plural       | Parents            |
      | Subordinate title        | Child              |
      | Subordinates plural      | Children           |
    And I click on "Add framework" "button" in the ".modal-dialog" "css_element"
    Then I should see "Framework 2" in the "Framework name" definition list item
    And I should see "Not set" in the "Framework ID" definition list item
    And I should see "Supervisors" in the "Framework mode" definition list item
    And I should see "Position managers, supervisors, course teachers and subordinates" in the "Positions visibility" definition list item
    And I should see "Not set" in the "Management restricted to cohort" definition list item
    And I should see "No" in the "Available in all tenants" definition list item
    And I should see "Tenant 1, Tenant 2" in the "Tenants" definition list item
    And I should see "Parent" in the "Supervisor title" definition list item
    And I should see "Parents" in the "Supervisors plural" definition list item
    And I should see "Not set" in the "Supervisor candidates cohort" definition list item
    And I should see "Not set" in the "Supervisor role" definition list item
    And I should see "Child" in the "Subordinate title" definition list item
    And I should see "Children" in the "Subordinates plural" definition list item
    And I should see "Not set" in the "Subordinate candidates cohort" definition list item

    When I press "Update framework"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Available in all tenants | 0                  |
      | Tenants                  | Tenant 3           |
    And I click on "Update framework" "button" in the ".modal-dialog" "css_element"
    Then I should see "Framework 2" in the "Framework name" definition list item
    And I should see "No" in the "Available in all tenants" definition list item
    And I should see "Tenant 3" in the "Tenants" definition list item
    And I should not see "Tenant 1"
    And I should not see "Tenant 2"

    When I press "Update framework"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Available in all tenants | 1                  |
    And I click on "Update framework" "button" in the ".modal-dialog" "css_element"
    Then I should see "Framework 2" in the "Framework name" definition list item
    And I should see "Yes" in the "Available in all tenants" definition list item
    And I should not see "Tenant 1"
    And I should not see "Tenant 2"
    And I should not see "Tenant 3"
