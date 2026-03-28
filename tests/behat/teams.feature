@tool @tool_murelation @javascript @MuTMS
Feature: Teams management
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
      | teacher1  | Teacher   | 1         | teacher1@example.com  |
      | teacher2  | Teacher   | 2         | teacher2@example.com  |
      | teacher3  | teacher   | 3         | teacher3@example.com  |
      | student1  | Student   | 1         | student1@example.com  |
      | student2  | Student   | 2         | student2@example.com  |
      | student3  | Student   | 3         | student3@example.com  |
      | student4  | Student   | 4         | student4@example.com  |
      | student5  | Student   | 5         | student5@example.com  |
    And the following "cohort members" exist:
      | user     | cohort |
      | manager1 | CH1    |
      | manager2 | CH1    |
      | teacher1 | CH2    |
      | teacher2 | CH2    |
      | teacher3 | CH2    |
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
      | tool/murelation:viewpositions    | Allow      | pviewer  | System       |           |
    And the following "role assigns" exist:
      | user      | role         | contextlevel | reference |
      | manager1  | pmanager     | System       |           |
      | manager2  | pviewer      | System       |           |
    And the following "tool_murelation > supervisor_roles" exist:
      | shortname | name     |
      | umanager  | UManager |
      | tmanager  | TManager |

  Scenario: Position manager may create, update and delete teams via framework page
    Given the following "tool_murelation > frameworks" exist:
      | name        | idnumber | uimode | visibility | managecohort | supervisortitle | supervisorstitle | supervisorcohort | supervisorrole | subordinatetitle | subordinatestitle | subordinatecohort |
      | Framework 1 | fw1      | teams  | managers   |              | Ucitel          | Ucitele          |                  |                | Zak              | Zaci              |                   |
      | Framework 2 | fw2      | teams  | everybody  | CH1          | Teacher         | Teachers         | CH2              | tmanager       | Student          | Students          | CH3               |
    And I log in as "manager1"
    And I am on the "Framework 2" "tool_murelation > Framework" page
    And I follow "Teams"

    When I press "Create team"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Team name | Team One |
    And I click on "Create team" "button" in the ".modal-dialog" "css_element"
    Then I should see "Team One" in the "Team name" definition list item
    And I should see "Not set" in the "Teacher" definition list item
    And I should see "No" in the "Supervisor-managed team" definition list item
    And I should see "Not set" in the "Team cohort" definition list item

    And I am on the "Framework 2" "tool_murelation > Framework" page
    And I follow "Teams"
    When I press "Create team"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Team name               | Team Two                                  |
      | Team ID number          | team2                                     |
      | Teacher                 | teacher 2                                 |
      | Supervisor-managed team | 1                                         |
      | Max team members        | 10                                        |
      | Students                | student1@example.com,student2@example.com |
      | Create team cohort      | 1                                         |
      | Team cohort name        | Team cohort 2                             |
    And I click on "Create team" "button" in the ".modal-dialog" "css_element"
    Then I should see "Team Two" in the "Team name" definition list item
    And I should see "team2" in the "Team ID number" definition list item
    And I should see "Teacher 2" in the "Teacher" definition list item
    And I should see "Yes" in the "Supervisor-managed team" definition list item
    And I should see "2 / 10" in the "Max team members" definition list item
    And I should see "Team cohort 2" in the "Team cohort" definition list item
    And the following should exist in the "reportbuilder-table" table:
      | First name | Email address        | Team position |
      | Student 1  | student1@example.com |               |
      | Student 2  | student2@example.com |               |

    When I click on "Team actions" "link_or_button"
    And I click on "Update team" "link"
    And the following fields in the ".modal-dialog" "css_element" match these values:
      | Team name               | Team Two                                  |
      | Team ID number          | team2                                     |
      | Supervisor-managed team | 1                                         |
      | Max team members        | 10                                        |
      | Team cohort name        | Team cohort 2                             |
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Team name               | Team B                                    |
      | Team ID number          | teamb                                     |
      | Teacher                 | Teacher 1                                 |
      | Supervisor-managed team | 0                                         |
      | Max team members        | 11                                        |
      | Team cohort name        | Team cohort B                             |
    And I click on "Update team" "button" in the ".modal-dialog" "css_element"
    Then I should see "Team B" in the "Team name" definition list item
    And I should see "teamb" in the "Team ID number" definition list item
    And I should see "Teacher 1" in the "Teacher" definition list item
    And I should see "No" in the "Supervisor-managed team" definition list item
    And I should see "2 / 11" in the "Max team members" definition list item
    And I should see "Team cohort B" in the "Team cohort" definition list item

    When I press "Add Students"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Users | student3@example.com |
    And I click on "Add Students" "button" in the ".modal-dialog" "css_element"
    Then I should see "3 / 11" in the "Max team members" definition list item
    And the following should exist in the "reportbuilder-table" table:
      | First name | Email address        | Team position |
      | Student 1  | student1@example.com |               |
      | Student 2  | student2@example.com |               |
      | Student 3  | student3@example.com |               |

    When I press "Add Students"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Team position | helper                                    |
      | Users         | student4@example.com,student5@example.com |
    And I click on "Add Students" "button" in the ".modal-dialog" "css_element"
    Then I should see "5 / 11" in the "Max team members" definition list item
    And the following should exist in the "reportbuilder-table" table:
      | First name | Email address        | Team position |
      | Student 1  | student1@example.com |               |
      | Student 2  | student2@example.com |               |
      | Student 3  | student3@example.com |               |
      | Student 4  | student4@example.com | helper        |
      | Student 5  | student5@example.com | helper        |

    When I click on "Actions" "link_or_button" in the "Student 4" "table_row"
    And I click on "Update Student" "link" in the "Student 4" "table_row"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Team position | leader |
    And I click on "Update Student" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | First name | Email address        | Team position |
      | Student 1  | student1@example.com |               |
      | Student 2  | student2@example.com |               |
      | Student 3  | student3@example.com |               |
      | Student 4  | student4@example.com | leader        |
      | Student 5  | student5@example.com | helper        |

    When I click on "Actions" "link_or_button" in the "Student 5" "table_row"
    And I click on "Remove Student" "link" in the "Student 5" "table_row"
    And I click on "Remove Student" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | First name | Email address        | Team position |
      | Student 1  | student1@example.com |               |
      | Student 2  | student2@example.com |               |
      | Student 3  | student3@example.com |               |
      | Student 4  | student4@example.com | leader        |
    And I should not see "Student 5"

    When I click on "Team actions" "link_or_button"
    And I click on "Delete team" "link"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Tick the checkbox | 1 |
    And I click on "Delete team" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | Team name | Team ID number        | Supervisor-managed team |
      | Team One  |                       | No                      |
    And I should not see "Team B"

  Scenario: User teams are visible in user profile
    Given the following "tool_murelation > frameworks" exist:
      | name        | idnumber | uimode | supervisortitle | supervisorstitle | subordinatetitle | subordinatestitle |
      | Framework 1 | fw1      | teams  | Ucitel          | Ucitele          | Zak              | Zaci              |
      | Framework 2 | fw2      | teams  | Teacher         | Teachers         | Student          | Students          |
    And the following "tool_murelation > teams" exist:
      | framework | teamname  | teamidnumber | user     |
      | fw1       | Team One  | tm1          | manager2 |
      | fw2       | Team Two  | tm2          |          |
    And the following "tool_murelation > team_members" exist:
      | team      | user     |
      | tm1       | student1 |
      | tm1       | student2 |
      | tm2       | student1 |
      | tm2       | student3 |
      | tm2       | student4 |

    When I am on the "student1" "user > profile" page logged in as "student1"
    Then I should see "Team One, Team Two" in the "Teams" definition list item
    And I log out

    When I am on the "student1" "user > profile" page logged in as "manager1"
    Then I should see "Team One, Team Two" in the "Teams" definition list item
    And I log out

    When I am on the "manager2" "user > profile" page logged in as "manager1"
    And I follow "Supervised teams"
    Then the following should exist in the "reportbuilder-table" table:
      | Relation framework | Team name | Team ID number        | Supervisor title    | Supervisor-managed team | Subordinates |
      | Framework 1        | Team One  | tm1                   | Ucitel              | No                      | 2            |

  Scenario: Team supervisor may manage own team
    Given the following "tool_murelation > frameworks" exist:
      | name        | idnumber | uimode | supervisortitle | supervisorstitle | subordinatetitle | subordinatestitle |
      | Framework 1 | fw1      | teams  | Ucitel          | Ucitele          | Zak              | Zaci              |
      | Framework 2 | fw2      | teams  | Teacher         | Teachers         | Student          | Students          |
    And the following "tool_murelation > teams" exist:
      | framework | teamname  | teamidnumber | user     | supmanaged |
      | fw1       | Team One  | tm1          | manager2 | 1          |
      | fw2       | Team Two  | tm2          |          | 0          |

    And I log in as "manager2"
    And I am on the profile page of user "manager2"
    And I follow "Supervised teams"
    And I follow "Team One"

    When I press "Add Zaci"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Users | Student 1,Student 2 |
    And I click on "Add Zaci" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | First name | Team position |
      | Student 1  |               |
      | Student 2  |               |

    When I press "Add Zaci"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Users         | Student 3 |
      | Team position | leader    |
    And I click on "Add Zaci" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | First name | Team position |
      | Student 1  |               |
      | Student 2  |               |
      | Student 3  | leader        |

    When I click on "Actions" "link_or_button" in the "Student 1" "table_row"
    And I click on "Update Zak" "link" in the "Student 1" "table_row"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Team position | subleader |
    And I click on "Update Zak" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | First name | Team position |
      | Student 1  | subleader     |
      | Student 2  |               |
      | Student 3  | leader        |

    When I click on "Actions" "link_or_button" in the "Student 3" "table_row"
    And I click on "Remove Zak" "link" in the "Student 3" "table_row"
    And I click on "Remove Zak" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | First name | Team position |
      | Student 1  | subleader     |
      | Student 2  |               |
    And I should not see "Student 3"

  Scenario: Position manager may add cohort members to team
    Given the following "tool_murelation > frameworks" exist:
      | name        | idnumber | uimode | visibility | managecohort | supervisortitle | supervisorstitle | supervisorcohort | supervisorrole | subordinatetitle | subordinatestitle | subordinatecohort |
      | Framework 1 | fw1      | teams  | managers   |              | Ucitel          | Ucitele          |                  |                | Zak              | Zaci              |                   |
    And I log in as "manager1"
    And I am on the "Framework 1" "tool_murelation > Framework" page
    And I follow "Teams"
    And I press "Create team"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Team name | Team One |
    And I click on "Create team" "button" in the ".modal-dialog" "css_element"

    When I click on "Team actions" "link_or_button"
    And I click on "Add from cohort" "link"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Team position | xyz      |
      | Cohort        | Cohort 3 |
    And I click on "Add from cohort" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | First name | Email address        | Team position |
      | Student 1  | student1@example.com | xyz           |
      | Student 2  | student2@example.com | xyz           |
      | Student 3  | student3@example.com | xyz           |
      | Student 4  | student4@example.com | xyz           |
      | Student 5  | student5@example.com | xyz           |
