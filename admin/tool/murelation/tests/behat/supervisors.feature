@tool @tool_murelation @javascript @MuTMS
Feature: Supervisors management
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
      | parent1   | Parent    | 1         | parent1@example.com   |
      | parent2   | Parent    | 2         | parent2@example.com   |
      | parent3   | Parent    | 3         | parent3@example.com   |
      | student1  | Student   | 1         | student1@example.com  |
      | student2  | Student   | 2         | student2@example.com  |
      | student3  | Student   | 3         | student3@example.com  |
      | student4  | Student   | 4         | student4@example.com  |
      | student5  | Student   | 5         | student5@example.com  |
    And the following "cohort members" exist:
      | user     | cohort |
      | manager1 | CH1    |
      | manager2 | CH1    |
      | parent1  | CH2    |
      | parent2  | CH2    |
      | parent3  | CH2    |
      | student1 | CH3    |
      | student2 | CH3    |
      | student3 | CH3    |
      | student4 | CH3    |
      | student5 | CH3    |
    And the following "roles" exist:
      | name             | shortname |
      | Position viewer  | pviewer   |
      | Position manager | pmanager  |
    And the following "permission overrides" exist:
      | capability                       | permission | role     | contextlevel | reference |
      | tool/murelation:viewframeworks   | Allow      | pmanager | System       |           |
      | tool/murelation:viewpositions    | Allow      | pmanager | System       |           |
      | tool/murelation:managepositions  | Allow      | pmanager | System       |           |
      | moodle/site:viewuseridentity     | Allow      | pmanager | System       |           |
      | moodle/user:viewalldetails       | Allow      | pmanager | System       |           |
      | moodle/site:configview           | Allow      | pmanager | System       |           |
      | tool/murelation:viewframeworks   | Allow      | pviewer  | System       |           |
      | tool/murelation:viewpositions    | Allow      | pviewer  | System       |           |
      | moodle/user:viewalldetails       | Allow      | pviewer  | System       |           |
      | moodle/site:configview           | Allow      | pviewer  | System       |           |
    And the following "role assigns" exist:
      | user      | role         | contextlevel | reference |
      | manager1  | pmanager     | System       |           |
      | viewer1   | pviewer      | System       |           |
    And the following "tool_murelation > supervisor_roles" exist:
      | shortname | name     |
      | umanager  | UManager |
      | tmanager  | TManager |

  Scenario: Position manager may create, update and delete user supervisors via framework page
    Given the following "tool_murelation > frameworks" exist:
      | name        | idnumber | uimode      | visibility | managecohort | supervisortitle | supervisorstitle | supervisorcohort | supervisorrole | subordinatetitle | subordinatestitle | subordinatecohort |
      | Framework 1 | fw1      | supervisors | managers   |              | Ucitel          | Ucitele          |                  |                | Zak              | Zaci              |                   |
      | Framework 2 | fw2      | supervisors | everybody  | CH1          | Parent          | Parents          | CH2              | tmanager       | Child            | Children          | CH3               |
    And I log in as "manager1"
    And I am on the "Framework 1" "tool_murelation > Framework" page
    And I follow "Zaci"

    When I press "Add Zaci"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Ucitel | manager1@example.com |
    And I click on "Continue" "button" in the ".modal-dialog" "css_element"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Zaci   | student1@example.com,student2@example.com |
    And I click on "Add Zaci" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | First name | Email address        | Ucitel    |
      | Student 1  | student1@example.com | Manager 1 |
      | Student 2  | student2@example.com | Manager 1 |

    When I click on "Actions" "link_or_button" in the "Student 1" "table_row"
    And I click on "Update Ucitel" "link" in the "Student 1" "table_row"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Ucitel | manager2@example.com |
    And I click on "Update Ucitel" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | First name | Email address        | Ucitel    |
      | Student 1  | student1@example.com | Manager 2 |
      | Student 2  | student2@example.com | Manager 1 |

    When I click on "Actions" "link_or_button" in the "Student 2" "table_row"
    And I click on "Remove Ucitel" "link" in the "Student 2" "table_row"
    And I click on "Remove Ucitel" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | First name | Email address        | Ucitel    |
      | Student 1  | student1@example.com | Manager 2 |
    And I should not see "Student 2"

    And I am on the "Framework 2" "tool_murelation > Framework" page
    And I follow "Children"

    When I press "Add Children"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Parent | parent1@example.com |
    And I click on "Continue" "button" in the ".modal-dialog" "css_element"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Children   | student1@example.com,student2@example.com |
    And I click on "Add Children" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | First name | Email address        | Parent    |
      | Student 1  | student1@example.com | Parent 1  |
      | Student 2  | student2@example.com | Parent 1  |

    When I click on "Actions" "link_or_button" in the "Student 1" "table_row"
    And I click on "Update Parent" "link" in the "Student 1" "table_row"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Parent | parent2@example.com |
    And I click on "Update Parent" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | First name | Email address        | Parent    |
      | Student 1  | student1@example.com | Parent 2  |
      | Student 2  | student2@example.com | Parent 1  |

    When I click on "Actions" "link_or_button" in the "Student 2" "table_row"
    And I click on "Remove Parent" "link" in the "Student 2" "table_row"
    And I click on "Remove Parent" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | First name | Email address        | Parent    |
      | Student 1  | student1@example.com | Parent 2  |
    And I should not see "Student 2"

  Scenario: Position manager may create, update and delete user supervisors via user profile
    Given the following "tool_murelation > frameworks" exist:
      | name        | idnumber | uimode      | visibility | managecohort | supervisortitle | supervisorstitle | supervisorcohort | supervisorrole | subordinatetitle | subordinatestitle | subordinatecohort |
      | Framework 1 | fw1      | supervisors | managers   |              | Ucitel          | Ucitele          |                  |                | Zak              | Zaci              |                   |
      | Framework 2 | fw2      | supervisors | everybody  | CH1          | Parent          | Parents          | CH2              | tmanager       | Child            | Children          | CH3               |
    And I log in as "manager1"
    And I am on the "student2" "user > profile" page
    And I should see "Not set" in the "Parent" definition list item
    And I should see "Not set" in the "Ucitel" definition list item

    When I click on "Ucitel actions" "link_or_button"
    And I click on "Add Ucitel" "link"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Ucitel   | manager1@example.com |
    And I click on "Add Ucitel" "button" in the ".modal-dialog" "css_element"
    And I should see "Not set" in the "Parent" definition list item
    And I should see "Manager 1" in the "Ucitel" definition list item

    When I click on "Parent actions" "link_or_button"
    And I click on "Add Parent" "link"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Parent   | parent1@example.com |
    And I click on "Add Parent" "button" in the ".modal-dialog" "css_element"
    And I should see "Parent 1" in the "Parent" definition list item
    And I should see "Manager 1" in the "Ucitel" definition list item

    When I click on "Parent actions" "link_or_button"
    And I click on "Update Parent" "link"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Parent   | parent2@example.com |
    And I click on "Update Parent" "button" in the ".modal-dialog" "css_element"
    And I should see "Parent 2" in the "Parent" definition list item
    And I should see "Manager 1" in the "Ucitel" definition list item

    When I click on "Parent actions" "link_or_button"
    And I click on "Remove Parent" "link"
    And I click on "Remove Parent" "button" in the ".modal-dialog" "css_element"
    And I should see "Not set" in the "Parent" definition list item
    And I should see "Manager 1" in the "Ucitel" definition list item
