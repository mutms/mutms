@tool @tool_mucertify @MuTMS
Feature: Certification completion by managers tests

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
      | Program 001 |            | Course 2 |            |                  |                  |
    And the following "tool_mucertify > certifications" exist:
      | fullname          | idnumber | category | program1 | sources |
      | Certification 001 | CT1      |          | PR1      | manual  |
      | Certification 002 | CT2      |          | PR2      | manual  |

  @javascript
  Scenario: Manager may mark the whole certification as complete
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
    And I follow "Student 1"
    And I click on "5/11/22" "link" in the "Program 001" "table_row"

    When I press "Override period dates"
    And I set the following fields to these values:
      | timefrom[enabled]      | 1         |
      | timefrom[day]          | 5         |
      | timefrom[month]        | 11        |
      | timefrom[year]         | 2022      |
      | timefrom[hour]         | 09        |
      | timefrom[minute]       | 00        |
      | timecertified[enabled] | 1         |
      | timecertified[day]     | 1         |
      | timecertified[month]   | 11        |
      | timecertified[year]    | 2023      |
      | timecertified[hour]    | 09        |
      | timecertified[minute]  | 00        |
    And I click on "Override period dates" "button" in the ".modal-dialog" "css_element"
    Then I should see "Valid" in the "Certification status" definition list item

  @javascript
  Scenario: Manager may mark certification program as completed
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
    And I follow "Student 1"
    And I click on "5/11/22" "link" in the "Program 001" "table_row"
    And I follow "Program 001"

    When I click on "Override completion" "link" in the "Program 001" "table_row"
    And I set the following fields to these values:
      | timecompleted[enabled] | 1    |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I am on the "tool_mucertify > All certifications management" page
    And I follow "Certification 001"
    And I click on "Users" "link" in the ".secondary-navigation" "css_element"
    And I follow "Student 1"
    Then I should see "Valid" in the "Certification status" definition list item
