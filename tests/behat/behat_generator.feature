@tool @tool_muprog @MuTMS
Feature: Programs behat generator tests

  Background:
    Given unnecessary Admin bookmarks block gets deleted
    And the following "cohorts" exist:
      | name     | idnumber |
      | Cohort 1 | CH1      |
      | Cohort 2 | CH2      |
      | Cohort 3 | CH3      |
    And the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
      | Cat 3 | 0        | CAT3     |
      | Cat 4 | CAT3     | CAT4     |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
      | Course 2 | C2        | topics |
      | Course 3 | C3        | topics |
      | Course 4 | C4        | topics |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | viewer1  | Viewer    | 1        | viewer1@example.com  |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
    And the following "roles" exist:
      | name           | shortname |
      | Program viewer | pviewer   |
    And the following "permission overrides" exist:
      | capability                     | permission | role    | contextlevel | reference |
      | tool/muprog:view            | Allow      | pviewer | System       |           |
#      | moodle/category:viewcourselist | Allow      | pviewer | System       |           |
#      | moodle/category:viewcourselist | Allow      | manager | System       |           |
    And the following "role assigns" exist:
      | user     | role          | contextlevel | reference |
      | viewer1  | pviewer       | System       |           |

  Scenario: Programs Behat generator creates programs
    When the following "tool_muprog > programs" exist:
      | fullname    | idnumber | category | publicaccess | cohorts            |
      | Program 000 | PR0      |          | 0            | Cohort 1, Cohort 2 |
      | Program 001 | PR1      | Cat 1    | 1            |                    |
      | Program 002 | PR2      | Cat 2    | 0            |                    |

    And I log in as "viewer1"
    And I am on the "tool_muprog > All programs management" page
    Then the following should exist in the "reportbuilder-table" table:
      | Program name  | Category   | Program ID | Courses | Allocations | Public |
      | Program 000   | System     | PR0        | 0       | 0           | No     |
      | Program 001   | Cat 1      | PR1        | 0       | 0           | Yes    |
      | Program 002   | Cat 2      | PR2        | 0       | 0           | No     |
    And I follow "Program 000"
    And I follow "Catalogue visibility"
    And I should see "Cohort 1"
    And I should see "Cohort 2"
    And I should not see "Cohort 3"

  Scenario: Programs Behat generator creates program items
    Given the following "tool_muprog > programs" exist:
      | fullname    | idnumber | category | publicaccess | cohorts            |
      | Program 000 | PR0      |          | 0            | Cohort 1, Cohort 2 |
      | Program 001 | PR1      | Cat 1    | 1            |                    |
      | Program 002 | PR2      | Cat 2    | 0            |                    |

    When the following "tool_muprog > program_items" exist:
      | program     | parent     | course   | fullname   | sequencetype     | minprerequisites |
      | Program 000 |            | Course 1 |            |                  |                  |
      | Program 000 |            |          | First set  |                  |                  |
      | Program 000 | First set  |          | Second set | All in order     |                  |
      | Program 000 | First set  |          | Third set  | At least X       | 3                |
      | Program 000 | Second set |          | Fourth set | All in any order |                  |
      | Program 000 | First set  | Course 2 |            |                  |                  |
    And the following "tool_muprog > program_items" exist:
      | program     | course   |
      | Program 001 | Course 1 |
    And the following "tool_muprog > program_items" exist:
      | program     | fullname |
      | Program 001 | Set 1    |
    And I log in as "viewer1"
    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 000"
    And I follow "Content"
    Then I should see "All in any order" in the "Program 000" "table_row"
    And I should see "All in any order" in the "Program 000" "table_row"
    And I should see "Course 1"
    And I should see "All in any order" in the "First set" "table_row"
    And I should see "All in order" in the "Second set" "table_row"
    And I should see "All in any order" in the "Fourth set" "table_row"
    And I should see "At least 3" in the "Third set" "table_row"
    And I should see "Course 2"
    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 001"
    And I follow "Content"
    Then I should see "Course 1"
    And I should see "Set 1"

  Scenario: Programs Behat generator creates allocations
    Given the following "tool_muprog > programs" exist:
      | fullname    | idnumber | category | publicaccess | cohorts            |
      | Program 000 | PR0      |          | 0            | Cohort 1, Cohort 2 |
      | Program 001 | PR1      | Cat 1    | 1            |                    |
      | Program 002 | PR2      | Cat 2    | 0            |                    |

    When the following "tool_muprog > program_allocations" exist:
      | program     | user     |
      | Program 002 | student1 |
    And I log in as "viewer1"
    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 002"
    And I follow "Users"
    Then  "Student 1" row "Source" column of "reportbuilder-table" table should contain "Manual allocation"
    And  "Student 1" row "Program status" column of "reportbuilder-table" table should contain "Open"
