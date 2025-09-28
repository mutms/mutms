@tool @tool_muprog @MuTMS
Feature: Program completion by students tests

  Background:
    Given unnecessary Admin bookmarks block gets deleted
    And the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
      | Cat 3 | CAT2     | CAT3     |
    And the following "courses" exist:
      | fullname | shortname | format | category | enablecompletion | showcompletionconditions |
      | Course 1 | C1        | topics | CAT1     | 1                | 1                        |
      | Course 2 | C2        | topics | CAT2     | 1                | 1                        |
      | Course 3 | C3        | topics | CAT3     | 1                | 1                        |
      | Course 4 | C4        | topics | CAT1     | 1                | 1                        |
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
    And the following "roles" exist:
      | name            | shortname |
      | Program viewer  | pviewer   |
      | Program manager | pmanager  |
    And the following "permission overrides" exist:
      | capability                     | permission | role     | contextlevel | reference |
      | tool/muprog:view            | Allow      | pviewer  | System       |           |
      | tool/muprog:view            | Allow      | pmanager | System       |           |
      | tool/muprog:edit            | Allow      | pmanager | System       |           |
      | tool/muprog:delete          | Allow      | pmanager | System       |           |
      | tool/muprog:addcourse       | Allow      | pmanager | System       |           |
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
      | program     | parent     | course   | fullname   | sequencetype     | minprerequisites |
      | Program 000 |            |          | First set  | All in order     |                  |
      | Program 000 | First set  | Course 1 |            |                  |                  |
      | Program 000 | First set  | Course 2 |            |                  |                  |
      | Program 000 |            |          | Second set | At least X       | 1                |
      | Program 000 | Second set | Course 3 |            |                  |                  |
      | Program 000 | Second set | Course 4 |            |                  |                  |
    And the following "tool_muprog > program_allocations" exist:
      | program     | user     |
      | Program 000 | student1 |
      | Program 000 | student2 |

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
  Scenario: Student may complete a program
    When I log in as "student1"
    And I am on the "tool_muprog > My programs" page
    And I follow "Program 000"
    And I follow "Course 1"
    And I follow "Sample page"
    # The cron job has to be executed twice with a pause.
    And I run the "core\task\completion_regular_task" task
    And I wait "1" seconds
    And I run the "core\task\completion_regular_task" task

    And I am on the "tool_muprog > My programs" page
    And I follow "Program 000"
    And I should see "Open" in the "Program status" definition list item
    And I follow "Course 2"
    And I follow "Sample page"
    # The cron job has to be executed twice with a pause.
    And I run the "core\task\completion_regular_task" task
    And I wait "1" seconds
    And I run the "core\task\completion_regular_task" task

    And I am on the "tool_muprog > My programs" page
    And I follow "Program 000"
    And I should see "Open" in the "Program status" definition list item
    And I follow "Course 3"
    And I follow "Sample page"
    # The cron job has to be executed twice with a pause.
    And I run the "core\task\completion_regular_task" task
    And I wait "1" seconds
    And I run the "core\task\completion_regular_task" task

    And I am on the "tool_muprog > My programs" page
    And I follow "Program 000"
    Then I should see "Completed" in the "Program status" definition list item

  @javascript
  Scenario: Student may see that course is missing which prevents completion
    Given I log in as "admin"
    And I go to the courses management page
    And I should see the "Course categories and courses" management page
    And I click on category "Cat 1" in the management interface
    And I click on "delete" action for "Course 1" in management course listing
    And I press "Delete"
    And I log out

    When I log in as "student1"
    And I am on the "tool_muprog > My programs" page
    And I follow "Program 000"
    Then I should see "Course is missing" in the "Course 1" "table_row"
    And I should not see "Course is missing" in the "Course 2" "table_row"
