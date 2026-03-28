@tool @tool_mucertify @MuTMS
Feature: Certification completion by students tests

  Background:
    Given unnecessary Admin bookmarks block gets deleted
    And the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
    And the following "courses" exist:
      | fullname | shortname | format | category | enablecompletion | showcompletionconditions |
      | Course 1 | C1        | topics | CAT1     | 1                | 1                        |
      | Course 2 | C2        | topics | CAT2     | 1                | 1                        |
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
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | manager1 | Manager   | 1        | manager1@example.com |
      | viewer1  | Viewer    | 1        | viewer1@example.com  |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
      | student3 | Student   | 3        | student3@example.com |
    And the following "roles" exist:
      | name                  | shortname |
      | Certification viewer  | pviewer   |
      | Certification manager | pmanager  |
    And the following "permission overrides" exist:
      | capability                     | permission | role     | contextlevel | reference |
      | tool/mucertify:view            | Allow      | pviewer  | System       |           |
      | tool/mucertify:view            | Allow      | pmanager | System       |           |
      | tool/mucertify:edit            | Allow      | pmanager | System       |           |
      | tool/mucertify:assign          | Allow      | pmanager | System       |           |
      | tool/mucertify:delete          | Allow      | pmanager | System       |           |
      | tool/mucertify:admin           | Allow      | pmanager | System       |           |
      | tool/muprog:view               | Allow      | pmanager | System       |           |
      | tool/muprog:edit               | Allow      | pmanager | System       |           |
      | tool/muprog:admin              | Allow      | pmanager | System       |           |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | manager1  | pmanager      | System       |           |
      | viewer1   | pviewer       | System       |           |
    And the following "tool_muprog > programs" exist:
      | fullname    | idnumber | category | sources |
      | Program 001 | PR1      |          | mucertify |
      | Program 002 | PR2      |          | mucertify |
    And the following "tool_muprog > program_items" exist:
      | program     | parent     | course   | fullname   | sequencetype     | minprerequisites |
      | Program 001 |            | Course 1 |            |                  |                  |
      | Program 002 |            | Course 2 |            |                  |                  |
    And the following "tool_mucertify > certifications" exist:
      | fullname          | idnumber | category | program1 | sources |
      | Certification 001 | CT1      |          | PR1      | manual  |
      | Certification 002 | CT2      |          | PR2      | manual  |

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
    And I log out

  @javascript
  Scenario: Student may complete a certification
    Given I log in as "manager1"
    And I am on the "tool_mucertify > All certifications management" page
    And I follow "Certification 001"
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"
    And I press "Assign users"
    And I set the following fields to these values:
      | Users                    | Student 1 |
      | timewindowstart[day]     | 5         |
      | timewindowstart[month]   | 11        |
      | timewindowstart[year]    | 2022      |
      | timewindowstart[hour]    | 09        |
      | timewindowstart[minute]  | 00        |
    And I click on "Assign users" "button" in the ".modal-dialog" "css_element"
    And I press "Assign users"
    And I set the following fields to these values:
      | Users                    | Student 2 |
      | timewindowstart[month]   | 11        |
      | timewindowstart[year]    | 2022      |
      | timewindowstart[day]     | 5         |
      | timewindowstart[hour]    | 09        |
      | timewindowstart[minute]  | 00        |
    And I click on "Assign users" "button" in the ".modal-dialog" "css_element"
    And I log out

    When I log in as "student1"
    And I am on the "tool_muprog > My programs" page
    And I follow "Program 001"
    And I follow "Course 1"
    And I follow "Sample page"
    # The cron job has to be executed twice with a pause.
    And I run the "core\task\completion_regular_task" task
    And I wait "1" seconds
    And I run the "core\task\completion_regular_task" task
    And I am on the "tool_mucertify > My certifications" page
    And I follow "Certification 001"
    Then I should see "Valid" in the "Certification status" definition list item
    And I log out

    When I log in as "manager1"
    And I am on the "tool_mucertify > All certifications management" page
    And I follow "Certification 001"
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | First name  | Certification status |
      | Student 1   | Valid                |
      | Student 2   | Not certified        |
