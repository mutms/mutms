@tool @tool_muprog @tool_mutrain @MuTMS
Feature: Training program completion by students tests

  Background:
    Given I skip tests if "tool_mutrain" is not installed
    And unnecessary Admin bookmarks block gets deleted
    And the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
      | Cat 3 | CAT2     | CAT3     |
    And the following "custom field categories" exist:
      | name              | component   | area   | itemid |
      | Category for test | core_course | course | 0      |
    And the following "custom fields" exist:
      | name        | category           | type    | shortname | description | configdata            |
      | TrainingF 1 | Category for test  | mutrain | training1 | tf1         |                       |
      | TrainingF 2 | Category for test  | mutrain | training2 | tf2         |                       |
    And the following "courses" exist:
      | fullname | shortname | format | category | enablecompletion | showcompletionconditions | customfield_training1 |  customfield_training2 |
      | Course 1 | C1        | topics | CAT1     | 1                | 1                        | 4                     | 8                      |
      | Course 2 | C2        | topics | CAT2     | 1                | 1                        | 8                     | 4                      |
      | Course 3 | C3        | topics | CAT3     | 1                | 1                        | 16                    | 2                      |
      | Course 4 | C4        | topics | CAT1     | 1                | 1                        |                       | 1                      |
    And the following "tool_mutrain > frameworks" exist:
      | name        | publicaccess | requiredtraining | restrictedcompletion | fields    |
      | Framework 1 | 1            | 5                | 0                    | training1 |
      | Framework 2 | 1            | 5                | 1                    | training2 |
    And the following "activity" exists:
      | activity       | page                     |
      | course         | C1                       |
      | idnumber       | page1                    |
      | name           | Sample page              |
      | intro          | A lesson learned in life |
      | completion     | 2                        |
      | completionview | 1                        |
    And the following "activity" exists:
      | activity       | page                     |
      | course         | C2                       |
      | idnumber       | page2                    |
      | name           | Sample page              |
      | intro          | A lesson learned in life |
      | completion     | 2                        |
      | completionview | 1                        |
    And the following "activity" exists:
      | activity       | page                     |
      | course         | C3                       |
      | idnumber       | page3                    |
      | name           | Sample page              |
      | intro          | A lesson learned in life |
      | completion     | 2                        |
      | completionview | 1                        |
    And the following "activity" exists:
      | activity       | page                     |
      | course         | C4                       |
      | idnumber       | page4                    |
      | name           | Sample page              |
      | intro          | A lesson learned in life |
      | completion     | 2                        |
      | completionview | 1                        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | manager1 | Manager   | 1        | manager1@example.com |
      | viewer1  | Viewer    | 1        | viewer1@example.com  |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
      | student3 | Student   | 3        | student3@example.com |
    And the following "roles" exist:
      | name            | shortname |
      | Program viewer  | pviewer   |
      | Program manager | pmanager  |
    And the following "permission overrides" exist:
      | capability                  | permission | role     | contextlevel | reference |
      | tool/muprog:view            | Allow      | pviewer  | System       |           |
      | tool/muprog:view            | Allow      | pmanager | System       |           |
      | tool/muprog:edit            | Allow      | pmanager | System       |           |
      | tool/muprog:delete          | Allow      | pmanager | System       |           |
      | tool/muprog:allocate        | Allow      | pmanager | System       |           |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | manager1  | pmanager      | System       |           |
      | viewer1   | pviewer       | System       |           |
    And the following "tool_muprog > programs" exist:
      | fullname    | idnumber | category | publicaccess |
      | Program 000 | PR0      |          | 1            |
      | Program 001 | PR1      | Cat 1    | 1            |
      | Program 002 | PR2      | Cat 2    | 1            |
    And the following "tool_muprog > program_items" exist:
      | program     | parent     | training    | fullname   | sequencetype     | minprerequisites |
      | Program 000 |            |             | First set  | All in order     |                  |
      | Program 000 | First set  | Framework 1 |            |                  |                  |
      | Program 001 |            |             | First set  | All in any order |                  |
      | Program 001 | First set  | Framework 2 |            |                  |                  |
    And the following "tool_muprog > program_allocations" exist:
      | program     | user     |
      | Program 000 | student1 |
      | Program 000 | student3 |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
      | student1 | C2     | student |
      | student1 | C3     | student |
      | student1 | C4     | student |
      | student2 | C1     | student |
      | student2 | C2     | student |
      | student2 | C3     | student |
      | student2 | C4     | student |

    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Course completion" in current page administration
    And I set the field "id_overall_aggregation" to "2"
    And I click on "Condition: Activity completion" "link"
    And I set the field "Page - Sample page" to "1"
    And I press "Save changes"
    And I am on "Course 2" course homepage
    And I navigate to "Course completion" in current page administration
    And I set the field "id_overall_aggregation" to "2"
    And I click on "Condition: Activity completion" "link"
    And I set the field "Page - Sample page" to "1"
    And I press "Save changes"
    And I am on "Course 3" course homepage
    And I navigate to "Course completion" in current page administration
    And I set the field "id_overall_aggregation" to "2"
    And I click on "Condition: Activity completion" "link"
    And I set the field "Page - Sample page" to "1"
    And I press "Save changes"
    And I am on "Course 4" course homepage
    And I navigate to "Course completion" in current page administration
    And I set the field "id_overall_aggregation" to "2"
    And I click on "Condition: Activity completion" "link"
    And I set the field "Page - Sample page" to "1"
    And I press "Save changes"
    And I log out

  @javascript
  Scenario: Student may complete a training program without restricted completion
    Given I log in as "student1"

    When I am on the "tool_muprog > My programs" page
    And I follow "Program 000"
    Then I should see "Open" in the "Program status" definition list item
    And I should see "Training progress: 0/5"

    When I am on "Course 1" course homepage
    And I follow "Sample page"
    # The cron job has to be executed twice with a pause.
    And I run the "core\task\completion_regular_task" task
    And I wait "1" seconds
    And I run the "core\task\completion_regular_task" task
    And I am on the "tool_muprog > My programs" page
    And I follow "Program 000"
    Then I should see "Open" in the "Program status" definition list item
    And I should see "Training progress: 4/5"

    And I am on the "tool_muprog > My programs" page
    And I am on "Course 2" course homepage
    And I follow "Sample page"
    # The cron job has to be executed twice with a pause.
    And I run the "core\task\completion_regular_task" task
    And I wait "1" seconds
    And I run the "core\task\completion_regular_task" task

    And I am on the "tool_muprog > My programs" page
    And I follow "Program 000"
    Then I should see "Completed" in the "Program status" definition list item
    And I should see "Training progress: 12/5"

  @javascript
  Scenario: Student may complete a training program with restricted completion
    Given I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Sample page"
    # The cron job has to be executed twice with a pause.
    And I run the "core\task\completion_regular_task" task
    And I wait "1" seconds
    And I run the "core\task\completion_regular_task" task
    And I wait "1" seconds

    When the following "tool_muprog > program_allocations" exist:
      | program     | user     |
      | Program 001 | student2 |
    And I am on the "tool_muprog > My programs" page
    And I follow "Program 001"
    Then I should see "Open" in the "Program status" definition list item
    And I should see "Training progress: 0/5"

    When I am on the "tool_muprog > My programs" page
    And I am on "Course 2" course homepage
    And I follow "Sample page"
    # The cron job has to be executed twice with a pause.
    And I run the "core\task\completion_regular_task" task
    And I wait "1" seconds
    And I run the "core\task\completion_regular_task" task
    And I am on the "tool_muprog > My programs" page
    And I follow "Program 001"
    Then I should see "Open" in the "Program status" definition list item
    And I should see "Training progress: 4/5"

    When I am on the "tool_muprog > My programs" page
    And I am on "Course 3" course homepage
    And I follow "Sample page"
    # The cron job has to be executed twice with a pause.
    And I run the "core\task\completion_regular_task" task
    And I wait "1" seconds
    And I run the "core\task\completion_regular_task" task
    And I am on the "tool_muprog > My programs" page
    And I follow "Program 001"
    Then I should see "Completed" in the "Program status" definition list item
    And I should see "Training progress: 6/5"
