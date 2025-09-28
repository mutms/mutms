@tool @tool_muprog @MuTMS
Feature: Program content management tests

  Background:
    Given unnecessary Admin bookmarks block gets deleted
    And the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
      | Cat 3 | CAT2     | CAT3     |
    And the following "courses" exist:
      | fullname | shortname | format | category |
      | Course 1 | C1        | topics | CAT1     |
      | Course 2 | C2        | topics | CAT2     |
      | Course 3 | C3        | topics | CAT3     |
      | Course 4 | C4        | topics | CAT1     |
      | Course 5 | C5        | topics | CAT1     |
      | Course 6 | C6        | topics | CAT1     |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | manager  | Site      | Manager  | manager@example.com  |
      | manager1 | Manager   | 1        | manager1@example.com |
      | manager2 | Manager   | 2        | manager2@example.com |
      | viewer1  | Viewer    | 1        | viewer1@example.com  |
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
      | tool/muprog:addcourse       | Allow      | pmanager | System       |           |
      | tool/muprog:allocate        | Allow      | pmanager | System       |           |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | manager   | manager       | System       |           |
      | manager1  | pmanager      | System       |           |
      | manager2  | pmanager      | Category     | CAT2      |
      | manager2  | pmanager      | Category     | CAT3      |
      | viewer1   | pviewer       | System       |           |
    And the following "tool_muprog > programs" exist:
      | fullname    | idnumber | category |
      | Program 000 | PR0      |          |
      | Program 001 | PR1      | Cat 1    |
      | Program 002 | PR2      | Cat 2    |
      | Program 003 | PR3      | Cat 3    |

  @javascript
  Scenario: Manager may edit program content
    Given I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 000"
    And I click on "Content" "link" in the ".nav-tabs" "css_element"
    And I should see "All in any order" in the "Program 000" "table_row"

    # Add courses and sets
    When I click on "Append item" "link" in the "Program 000" "table_row"
    And the following fields in the ".modal-dialog" "css_element" match these values:
      | Points                    | 1            |
      | completiondelay[enabled]  | 0            |
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Courses                   | Course 1     |
      | Points                    | 123          |
      | completiondelay[enabled]  | 1            |
      | completiondelay[number]   | 3            |
      | completiondelay[timeunit] | days         |
    Then I click on "Append item" "button" in the ".modal-dialog" "css_element"
    And I should see "123" in the "Course 1" "table_row"
    And I should see "Completion delay: 3 days" in the "Course 1" "table_row"

    When I click on "Append item" "link" in the "Program 000" "table_row"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Add new set               | 1            |
      | Full name                 | First set    |
      | Completion type           | All in order |
      | Points                    | 321          |
      | completiondelay[enabled]  | 1            |
      | completiondelay[number]   | 7            |
      | completiondelay[timeunit] | days         |
    And I click on "Append item" "button" in the ".modal-dialog" "css_element"
    Then I should see "All in order" in the "First set" "table_row"
    And I should see "321" in the "First set" "table_row"
    And I should see "Completion delay: 7 days" in the "First set" "table_row"

    When I click on "Append item" "link" in the "First set" "table_row"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Courses                   | Course 2, Course 3, Course 4 |
      | Add new set               | 1            |
      | Full name                 | Second set   |
      | Completion type           | At least X   |
      | At least X                | 2            |
      | completiondelay[enabled]  | 1            |
      | completiondelay[number]   | 5            |
      | completiondelay[timeunit] | days         |
    And I click on "Append item" "button" in the ".modal-dialog" "css_element"
    Then I should see "At least 2" in the "Second set" "table_row"
    And I should see "Completion delay: 5 days" in the "Second set" "table_row"

    When I click on "Append item" "link" in the "Program 000" "table_row"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Courses         | Course 5         |
      | Add new set     | 1                |
      | Full name       | Third set        |
      | Completion type | All in any order |
    And I click on "Append item" "button" in the ".modal-dialog" "css_element"
    Then I should see "All in any order" in the "Third set" "table_row"

    When I click on "Append item" "link" in the "Program 000" "table_row"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Add new set      | 1                |
      | Full name        | Fourth set       |
      | Completion type  | Minimum X points |
      | Minimum X points | 7                |
      | Points           | 456              |
    And I click on "Append item" "button" in the ".modal-dialog" "css_element"
    Then I should see "Minimum 7 points" in the "Fourth set" "table_row"
    And I should see "456" in the "Fourth set" "table_row"

    # Update sets
    When I click on "Update set" "link" in the "Program 000" "table_row"
    And the following fields in the ".modal-dialog" "css_element" match these values:
      | Completion type           | All in any order |
      | completiondelay[enabled]  | 0            |
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Completion type           | All in order |
      | completiondelay[enabled]  | 1            |
      | completiondelay[number]   | 2            |
      | completiondelay[timeunit] | days         |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    Then I should see "All in order" in the "Program 000" "table_row"
    And I should see "Completion delay: 2 days" in the "Program 000" "table_row"

    When I click on "Update set" "link" in the "Third set" "table_row"
    And the following fields in the ".modal-dialog" "css_element" match these values:
      | Completion type           | All in any order |
      | Full name                 | Third set        |
      | Completion type           | All in any order |
      | Points                    | 1                |
      | completiondelay[enabled]  | 0                |
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Full name                 | Treti set        |
      | Completion type           | All in order     |
      | Points                    | 77               |
      | completiondelay[enabled]  | 1                |
      | completiondelay[number]   | 11               |
      | completiondelay[timeunit] | days             |
    And I click on "Update set" "button" in the ".modal-dialog" "css_element"
    Then I should see "All in order" in the "Treti set" "table_row"
    And I should see "77" in the "Treti set" "table_row"
    And I should see "Completion delay: 11 days" in the "Treti set" "table_row"

    When I click on "Update set" "link" in the "Treti set" "table_row"
    And the following fields in the ".modal-dialog" "css_element" match these values:
      | Full name                 | Treti set        |
      | Completion type           | All in order     |
      | Points                    | 77               |
      | completiondelay[enabled]  | 1                |
      | completiondelay[number]   | 11               |
      | completiondelay[timeunit] | days             |
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Full name                 | Third set        |
      | Completion type           | At least X       |
      | At least X                | 3                |
      | Points                    | 0                |
      | completiondelay[enabled]  | 0                |
    And I click on "Update set" "button" in the ".modal-dialog" "css_element"
    Then I should see "At least 3" in the "Third set" "table_row"
    And I should see "0" in the "Third set" "table_row"
    And I should not see "Completion delay" in the "Third set" "table_row"

    When I click on "Update set" "link" in the "Third set" "table_row"
    And the following fields in the ".modal-dialog" "css_element" match these values:
      | Full name       | Third set        |
      | Completion type | At least X       |
      | At least X      | 3                |
      | Points          | 0                |
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Completion type | Minimum X points |
      | Minimum X points| 10               |
      | Points          | 11               |
    And I click on "Update set" "button" in the ".modal-dialog" "css_element"
    Then I should see "Minimum 10 points" in the "Third set" "table_row"
    And I should see "11" in the "Third set" "table_row"

    When I click on "Update course" "link" in the "Course 1" "table_row"
    And the following fields in the ".modal-dialog" "css_element" match these values:
      | Points                    | 123              |
      | completiondelay[enabled]  | 1                |
      | completiondelay[number]   | 3                |
      | completiondelay[timeunit] | days             |
    And I set the following fields to these values:
      | Points                    | 789              |
      | completiondelay[enabled]  | 0                |
    And I click on "Update course" "button" in the ".modal-dialog" "css_element"
    Then I should see "789" in the "Course 1" "table_row"
    And I should not see "Completion delay" in the "Course 1" "table_row"

    When I click on "Update course" "link" in the "Course 1" "table_row"
    And the following fields in the ".modal-dialog" "css_element" match these values:
      | Points                    | 789              |
      | completiondelay[enabled]  | 0                |
    And I set the following fields to these values:
      | completiondelay[enabled]  | 1                |
      | completiondelay[number]   | 4                |
      | completiondelay[timeunit] | days             |
    And I click on "Update course" "button" in the ".modal-dialog" "css_element"
    Then I should see "789" in the "Course 1" "table_row"
    And I should see "Completion delay: 4 days" in the "Course 1" "table_row"

    # Move items

    When I click on "Move item" "link" in the "Course 1" "table_row"
    And I press "Cancel moving"
    Then I should see "Actions"

    When I click on "Move item" "link" in the "Course 1" "table_row"
    And I click on "Move \"Course 1\" after \"Fourth set\"" "link"
    Then I should see "Actions"

    When I click on "Move item" "link" in the "Course 1" "table_row"
    And I click on "Move \"Course 1\" before \"Course 3\"" "link"
    Then I should see "Actions"

    When I click on "Move item" "link" in the "Course 1" "table_row"
    And I click on "Move \"Course 1\" before \"First set\"" "link"
    Then I should see "Actions"

    When I click on "Move item" "link" in the "Course 5" "table_row"
    And I click on "Move \"Course 5\" before \"Course 1\"" "link"
    Then I should see "Actions"

    When I click on "Move item" "link" in the "Course 5" "table_row"
    And I click on "Move \"Course 5\" into \"Third set\"" "link"
    Then I should see "Actions"

    When I click on "Move item" "link" in the "First set" "table_row"
    And I click on "Move \"First set\" before \"Course 1\"" "link"
    Then I should see "Actions"

    When I click on "Move item" "link" in the "First set" "table_row"
    And I click on "Move \"First set\" after \"Course 5\"" "link"
    Then I should see "Actions"

    When I click on "Move item" "link" in the "First set" "table_row"
    And I click on "Move \"First set\" before \"Third set\"" "link"
    Then I should see "Actions"

    # Deleting of items

    When I click on "Remove course" "link" in the "Course 5" "table_row"
    And I click on "Cancel" "button" in the ".modal-dialog" "css_element"
    Then I should see "Course 5"

    When I click on "Remove course" "link" in the "Course 5" "table_row"
    And I click on "Remove course" "button" in the ".modal-dialog" "css_element"
    Then I should not see "Course 5"

    When I click on "Delete set" "link" in the "Fourth set" "table_row"
    And I click on "Delete set" "button" in the ".modal-dialog" "css_element"
    Then I should not see "Fourth set"

    When I click on "Delete set" "link" in the "Third set" "table_row"
    And I click on "Cancel" "button" in the ".modal-dialog" "css_element"
    Then I should see "Third set"

    When I click on "Delete set" "link" in the "Third set" "table_row"
    And I click on "Delete set" "button" in the ".modal-dialog" "css_element"
    Then I should not see "Third set"

    When I click on "Remove course" "link" in the "Course 3" "table_row"
    And I click on "Remove course" "button" in the ".modal-dialog" "css_element"

    When I click on "Remove course" "link" in the "Course 4" "table_row"
    And I click on "Remove course" "button" in the ".modal-dialog" "css_element"

    When I click on "Remove course" "link" in the "Course 1" "table_row"
    And I click on "Remove course" "button" in the ".modal-dialog" "css_element"

    When I click on "Remove course" "link" in the "Course 2" "table_row"
    And I click on "Remove course" "button" in the ".modal-dialog" "css_element"

    When I click on "Delete set" "link" in the "Second set" "table_row"
    And I click on "Delete set" "button" in the ".modal-dialog" "css_element"

    When I click on "Delete set" "link" in the "First set" "table_row"
    And I click on "Delete set" "button" in the ".modal-dialog" "css_element"

  @javascript
  Scenario: Manager may add deleted references to missing courses from program
    Given the following "tool_muprog > program_items" exist:
      | program     | parent     | course   | fullname   | sequencetype     | minprerequisites |
      | Program 001 |            | Course 1 |            |                  |                  |
      | Program 001 |            | Course 2 |            |                  |                  |
      | Program 001 |            | Course 3 |            |                  |                  |
    And I log in as "admin"
    And I go to the courses management page
    And I should see the "Course categories and courses" management page
    And I click on category "Cat 1" in the management interface
    And I click on "delete" action for "Course 1" in management course listing
    And I press "Delete"
    And I log out

    When I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 001"
    And I click on "Content" "link" in the ".nav-tabs" "css_element"
    Then I should see "Course is missing" in the "Course 1" "table_row"
    And I should not see "Course is missing" in the "Course 2" "table_row"
    And I should not see "Course is missing" in the "Course 3" "table_row"

    When I click on "Remove course" "link" in the "Course 1" "table_row"
    And I click on "Remove course" "button" in the ".modal-dialog" "css_element"
    Then I should not see "Course 1"
    And I should see "Course 2"
    And I should see "Course 3"

  @javascript @tool_mutrain
  Scenario: Manager may add, update and delete training in program
    Given I skip tests if "tool_mutrain" is not installed
    And the following "custom field categories" exist:
      | name              | component   | area   | itemid |
      | Category for test | core_course | course | 0      |
    And the following "custom fields" exist:
      | name             | category           | type    | shortname | configdata            |
      | Training Field 1 | Category for test  | mutrain | training1 |                       |
      | Training Field 2 | Category for test  | mutrain | training2 |                       |
      | Training Field 3 | Category for test  | mutrain | training3 |                       |
    And the following "tool_mutrain > frameworks" exist:
      | name    | fields    | category | publicaccess | requiredtraining | restrictedcompletion |
      | TFR 001 | training1 |          | 1            | 10               | 0                    |
      | TFR 002 | training2 | Cat 2    | 1            | 20               | 1                    |
      | TFR 003 | training1 |          | 0            | 30               | 0                    |
    And the following "courses" exist:
      | fullname | shortname | format | category | customfield_training1 | customfield_training2 |
      | Course 7 | C7        | topics | CAT2     | 7                     | 1                     |
      | Course 8 | C8        | topics | CAT2     | 13                    | 2                     |
      | Course 9 | C9        | topics | CAT2     | 29                    | 3                     |
    And I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 000"
    And I click on "Content" "link" in the ".nav-tabs" "css_element"

    When I click on "Append item" "link" in the "Program 000" "table_row"
    And I set the following fields to these values:
      | Training                  | TFR 001   |
    And I click on "Append item" "button" in the ".modal-dialog" "css_element"
    Then I should see "Required training: 10" in the "TFR 001" "table_row"

    When I click on "Update training" "link" in the "TFR 001" "table_row"
    And I set the following fields to these values:
      | Points                    | 789              |
      | completiondelay[enabled]  | 1                |
      | completiondelay[number]   | 3                |
      | completiondelay[timeunit] | days             |
    And I click on "Update training" "button" in the ".modal-dialog" "css_element"
    Then I should see "789" in the "TFR 001" "table_row"
    And I should see "Completion delay: 3 days" in the "TFR 001" "table_row"
    And I should see "Required training: 10" in the "TFR 001" "table_row"

    When I click on "Remove training" "link" in the "TFR 001" "table_row"
    And I click on "Remove training" "button" in the ".modal-dialog" "css_element"
    Then I should not see "TFR 001"

  @javascript @tool_mutenancy
  Scenario: Tenant manager may add program courses from non-conflicting tenants
    Given I skip tests if "tool_mutenancy" is not installed
    And the following "tool_mutenancy > tenants" exist:
      | name     | idnumber | category |
      | Tenant 1 | ten1     | CAT1     |
      | Tenant 2 | ten2     | CAT2     |
    And I log in as "manager"
    And I click on "Switch tenant" "link"
    And I set the following fields to these values:
      | Tenant      | Tenant 1         |
    And I click on "Switch tenant" "button" in the ".modal-dialog" "css_element"

    When I am on the "tool_muprog > All programs management" page
    And I follow "Program 000"
    And I click on "Content" "link" in the ".secondary-navigation" "css_element"
    And I click on "Append item" "link" in the "Program 000" "table_row"
    And I set the following fields to these values:
      | Courses | Course 1 |
    And I click on "Append item" "button" in the ".modal-dialog" "css_element"
    Then I should see "Course 1" in the "#program_content" "css_element"

    When I click on "Append item" "link" in the "Program 000" "table_row"
    And I set the following fields to these values:
      | Courses | Course 4 |
    And I click on "Append item" "button" in the ".modal-dialog" "css_element"
    Then I should see "Course 4" in the "#program_content" "css_element"
