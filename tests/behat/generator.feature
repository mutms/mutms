@tool @tool_murelation @javascript @MuTMS
Feature: Behat tool_murelation generator usage
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
      | parent1   | Parent    | 1         | parent1@example.com   |
      | parent2   | Parent    | 2         | parent2@example.com   |
      | parent3   | Parent    | 3         | parent3@example.com   |
      | student1  | Student   | 1         | student1@example.com  |
      | student2  | Student   | 2         | student2@example.com  |
      | student3  | Student   | 3         | student3@example.com  |
      | student4  | Student   | 4         | student4@example.com  |

  Scenario: tool_murelation generator may create supervisor roles
    When the following "tool_murelation > supervisor_roles" exist:
      | shortname | name     |
      | umanager  | UManager |
      | tmanager  | TManager |
    And I log in as "admin"
    And I navigate to "Users > Supervisors and teams > User relation frameworks" in site administration
    And I press "Add framework"
    Then the "supervisorroleid" select box should contain "UManager"
    And the "supervisorroleid" select box should contain "TManager"

  Scenario: tool_murelation generator may create frameworks
    Given the following "tool_murelation > supervisor_roles" exist:
      | shortname | name     |
      | umanager  | UManager |
      | tmanager  | TManager |
    When the following "tool_murelation > frameworks" exist:
      | name        | uimode      |
      | Framework 1 | supervisors |
      | Framework 2 | teams       |
    And the following "tool_murelation > frameworks" exist:
      | name        | idnumber | uimode      | description           | visibility | managecohort | supervisortitle | supervisorstitle | supervisorcohort | supervisorrole | subordinatetitle | subordinatestitle | subordinatecohort |
      | Framework 3 | fw3      | supervisors | Some framework 3 desc | managers   | CH1          | Ucitel          | Ucitele          | CH2              | umanager       | Zak              | Zaci              | CH3               |
      | Framework 4 | fw4      | teams       | Some framework 4 desc | everybody  | CH1          | Velitel         | Velitele         | CH2              | tmanager       | Pesak            | Pesaci            | CH3               |
    And I log in as "admin"
    And I navigate to "Users > Supervisors and teams > User relation frameworks" in site administration
    Then the following should exist in the "reportbuilder-table" table:
      | Framework name | Framework ID | Framework mode   | Supervisor title  | Supervisor role | Subordinate title |
      | Framework 1    |              | Supervisors      | Supervisor        |                 | Subordinate       |
      | Framework 2    |              | Teams            | Supervisor        |                 | Subordinate       |
      | Framework 3    | fw3          | Supervisors      | Ucitel            | UManager        | Zak               |
      | Framework 4    | fw4          | Teams            | Velitel           | TManager        | Pesak             |

    And I follow "Framework 1"
    And I should see "Framework 1" in the "Framework name" definition list item
    And I should see "Not set" in the "Framework ID" definition list item
    And I should see "Supervisors" in the "Framework mode" definition list item
    And I should see "Position managers, supervisors, course teachers and subordinates" in the "Positions visibility" definition list item
    And I should see "Not set" in the "Management restricted to cohort" definition list item
    And I should see "Supervisor" in the "Supervisor title" definition list item
    And I should see "Supervisors" in the "Supervisors plural" definition list item
    And I should see "Not set" in the "Supervisor candidates cohort" definition list item
    And I should see "Not set" in the "Supervisor role" definition list item
    And I should see "Subordinate" in the "Subordinate title" definition list item
    And I should see "Subordinates" in the "Subordinates plural" definition list item
    And I should see "Not set" in the "Subordinate candidates cohort" definition list item

    And I navigate to "Users > Supervisors and teams > User relation frameworks" in site administration
    And I follow "Framework 2"
    And I should see "Framework 2" in the "Framework name" definition list item
    And I should see "Not set" in the "Framework ID" definition list item
    And I should see "Teams" in the "Framework mode" definition list item
    And I should see "Position managers, supervisors, course teachers and subordinates" in the "Positions visibility" definition list item
    And I should see "Not set" in the "Management restricted to cohort" definition list item
    And I should see "Supervisor" in the "Supervisor title" definition list item
    And I should see "Supervisors" in the "Supervisors plural" definition list item
    And I should see "Not set" in the "Supervisor candidates cohort" definition list item
    And I should see "Not set" in the "Supervisor role" definition list item
    And I should see "Subordinate" in the "Subordinate title" definition list item
    And I should see "Subordinates" in the "Subordinates plural" definition list item
    And I should see "Not set" in the "Subordinate candidates cohort" definition list item

    And I navigate to "Users > Supervisors and teams > User relation frameworks" in site administration
    And I follow "Framework 3"
    And I should see "Some framework 3 desc"
    And I should see "Framework 3" in the "Framework name" definition list item
    And I should see "fw3" in the "Framework ID" definition list item
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
    And I follow "Framework 4"
    And I should see "Some framework 4 desc"
    And I should see "Framework 4" in the "Framework name" definition list item
    And I should see "fw4" in the "Framework ID" definition list item
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

  Scenario: tool_murelation generator may create supervisors
    Given the following "tool_murelation > frameworks" exist:
      | name        | idnumber | uimode      | supervisortitle | supervisorstitle | subordinatetitle | subordinatestitle |
      | Framework 1 | fw1      | supervisors | Rodic           | Rodice           | Potomek          | Potomci           |
    When the following "tool_murelation > supervisors" exist:
      | framework | user     | subuser  |
      | fw1       | parent1  | student1 |
      | fw1       | parent1  | student2 |
      | fw1       | parent2  | student3 |
    And I log in as "admin"
    And I navigate to "Users > Supervisors and teams > User relation frameworks" in site administration
    And I follow "Framework 1"
    And I follow "Potomci"
    Then the following should exist in the "reportbuilder-table" table:
      | First name | Email address        | Rodic    |
      | Student 1  | student1@example.com | Parent 1 |
      | Student 2  | student2@example.com | Parent 1 |
      | Student 3  | student3@example.com | Parent 2 |

  Scenario: tool_murelation generator may create teams
    Given the following "tool_murelation > frameworks" exist:
      | name        | idnumber | uimode      | supervisortitle | supervisorstitle | subordinatetitle | subordinatestitle |
      | Framework 1 | fw1      | teams       | Manazer         | Manazeri         | Podrizeny        | Podrizeni         |
    When the following "tool_murelation > teams" exist:
      | framework | teamname  | teamidnumber | user     |
      | fw1       | Prvni tym | tm1          | manager1 |
      | fw1       | Druhy tym |              |          |
    And the following "tool_murelation > teams" exist:
      | framework | teamname  | supmanaged |
      | fw1       | Treti tym | 1          |
    And I log in as "admin"
    And I navigate to "Users > Supervisors and teams > User relation frameworks" in site administration
    And I follow "Framework 1"
    And I follow "Teams"
    Then the following should exist in the "reportbuilder-table" table:
      | Team name | Team ID number        | Manazer    | Supervisor-managed team | Podrizeni |
      | Prvni tym | tm1                   | Manager 1  | No                      | 0         |
      | Druhy tym |                       |            | No                      | 0         |
      | Treti tym |                       |            | Yes                     | 0         |

  Scenario: tool_murelation generator may create team members
    Given the following "tool_murelation > frameworks" exist:
      | name        | idnumber | uimode      | supervisortitle | supervisorstitle | subordinatetitle | subordinatestitle |
      | Framework 1 | fw1      | teams       | Manazer         | Manazeri         | Podrizeny        | Podrizeni         |
    And the following "tool_murelation > teams" exist:
      | framework | teamname  | teamidnumber | user     |
      | fw1       | Prvni tym | tm1          | manager1 |
      | fw1       | Druhy tym |              |          |
    When the following "tool_murelation > team_members" exist:
      | team      | user     |
      | tm1       | student1 |
      | tm1       | student2 |
    And the following "tool_murelation > team_members" exist:
      | team      | user     | teamposition |
      | Prvni tym | manager1 | Velitel      |
      | Druhy tym | student3 |              |
    And I log in as "admin"
    And I navigate to "Users > Supervisors and teams > User relation frameworks" in site administration
    And I follow "Framework 1"
    And I follow "Podrizeni"
    Then the following should exist in the "reportbuilder-table" table:
      | First name | Email address        | Team position | Manazer   | Team name | Team ID number |
      | Manager 1  | manager1@example.com | Velitel       | Manager 1 | Prvni tym | tm1            |
      | Student 1  | student1@example.com |               | Manager 1 | Prvni tym | tm1            |
      | Student 2  | student2@example.com |               | Manager 1 | Prvni tym | tm1            |
      | Student 3  | student3@example.com |               |           | Druhy tym |                |

  @tool_mutenancy
  Scenario: tool_murelation generator may create tenant teams
    Given I skip tests if "tool_mutenancy" is not installed
    And the following "tool_mutenancy > tenants" exist:
      | name     | idnumber |
      | Tenant 1 | TEN1     |
      | Tenant 2 | TEN2     |
    And the following "tool_murelation > frameworks" exist:
      | name        | idnumber | uimode      | supervisortitle | supervisorstitle | subordinatetitle | subordinatestitle |
      | Framework 1 | fw1      | teams       | Manazer         | Manazeri         | Podrizeny        | Podrizeni         |
    When the following "tool_murelation > teams" exist:
      | framework | teamname  | tenant |
      | fw1       | Prvni tym | TEN1   |
      | fw1       | Druhy tym | TEN2   |
      | fw1       | Treti tym |        |
    And I log in as "admin"
    And I navigate to "Users > Supervisors and teams > User relation frameworks" in site administration
    And I follow "Framework 1"
    And I follow "Teams"
    Then the following should exist in the "reportbuilder-table" table:
      | Team name | Team ID number        | Manazer    | Supervisor-managed team | Tenant   | Podrizeni |
      | Prvni tym |                       |            | No                      | Tenant 1 | 0         |
      | Druhy tym |                       |            | No                      | Tenant 2 | 0         |
      | Treti tym |                       |            | No                      |          | 0         |
