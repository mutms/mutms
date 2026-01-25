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
      | capability                  | permission | role    | contextlevel | reference |
      | tool/muprog:view            | Allow      | pviewer | System       |           |
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
      | program     | parent     | course   | fullname   | sequencetype     | minprerequisites | minpoints | points | type       | completiondelay |
      | Program 000 |            | Course 1 |            |                  |                  |           |        |            |                 |
      | Program 000 |            |          | First set  | Minimum X points |                  | 2         |        |            |                 |
      | Program 000 | First set  |          | Second set | All in order     |                  |           |        |            |                 |
      | Program 000 | First set  |          | Third set  | At least X       | 3                |           |        | set        |                 |
      | Program 000 | Second set |          | Fourth set | All in any order |                  |           |        |            |                 |
      | Program 000 | First set  | Course 2 |            |                  |                  |           | 9      | course     | 3600            |
      | Program 000 | Second set |          | Riding     |                  |                  |           | 4      | attendance |                 |
    And the following "tool_muprog > program_items" exist:
      | program     | course   |
      | Program 001 | Course 1 |
    And the following "tool_muprog > program_items" exist:
      | program     | fullname |
      | Program 001 | Set 1    |
    And the following "tool_muprog > program_items" exist:
      | program     | type       | fullname |
      | Program 001 | attendance | Driving  |
    And I log in as "viewer1"

    And I am on the "Program 000" "tool_muprog > Program" page
    And I follow "Content"
    Then the following should exist in the "program_content" table:
      | Item        | Points | Completion type           |
      | Program 000 |        | All in any order          |
      | Course 1    | 1      | Course completion         |
      | First set   | 1      | Minimum 2 points          |
      | Second set  | 1      | All in order              |
      | Fourth set  | 1      | All in any order          |
      | Riding      | 4      | Offline attendance        |
      | Third set   | 1      | At least 3                |
      | Course 2    | 9      | Course completion         |
      | Course 2    | 9      | Completion delay: 1 hours |

    And I am on the "Program 001" "tool_muprog > Program" page
    And I follow "Content"
    Then the following should exist in the "program_content" table:
      | Item        | Points | Completion type           |
      | Program 001 |        | All in any order          |
      | Course 1    | 1      | Course completion         |
      | Set 1       | 1      | All in any order          |
      | Driving     | 1      | Offline attendance        |

  Scenario: Programs Behat generator creates program credits items
    Given I skip tests if "tool_mutrain" is not installed
    And the following "custom field categories" exist:
      | name              | component   | area   | itemid |
      | Category for test | core_course | course | 0      |
    And the following "custom fields" exist:
      | name       | category           | type    | shortname | description | configdata            |
      | CreditsF 1 | Category for test  | mutrain | credits1  | tf1         |                       |
      | CreditsF 2 | Category for test  | mutrain | credits2  | tf2         |                       |
    And the following "tool_mutrain > frameworks" exist:
      | name        | publicaccess | requiredcredits  | restrictcontext | fields   | contextlevel | reference |
      | Framework 1 | 1            | 5                | 0               | credits1 | System       |           |
      | Framework 2 | 1            | 7                | 1               | credits1 | Category     | CAT2      |
    And the following "tool_muprog > programs" exist:
      | fullname    | idnumber | category | publicaccess | cohorts            |
      | Program 000 | PR0      |          | 0            | Cohort 1, Cohort 2 |
      | Program 001 | PR1      | Cat 1    | 1            |                    |
      | Program 002 | PR2      | Cat 2    | 0            |                    |

    When the following "tool_muprog > program_items" exist:
      | program     | credits     |
      | Program 000 | Framework 1 |
    And the following "tool_muprog > program_items" exist:
      | program     | credits     | type    | points | completiondelay |
      | Program 000 | Framework 2 | credits | 3      | 3600            |
    And I log in as "viewer1"
    And I am on the "Program 000" "tool_muprog > Program" page
    And I follow "Content"
    Then the following should exist in the "program_content" table:
      | Item        | Points | Completion type           |
      | Program 000 |        | All in any order          |
      | Framework 1 | 1      | Required credits: 5       |
      | Framework 2 | 3      | Required credits: 7       |
      | Framework 2 | 3      | Completion delay: 1 hours |

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
    And I am on the "Program 002" "tool_muprog > Program" page
    And I follow "Users"
    Then  "Student 1" row "Source" column of "reportbuilder-table" table should contain "Manual allocation"
    And  "Student 1" row "Program status" column of "reportbuilder-table" table should contain "Open"
