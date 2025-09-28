@tool @tool_muprog @MuTMS
Feature: General programs management tests

  Background:
    Given unnecessary Admin bookmarks block gets deleted
    And the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
      | Cat 3 | 0        | CAT3     |
      | Cat 4 | CAT3     | CAT4     |
    And the following "courses" exist:
      | fullname | shortname | format | category |
      | Course 1 | C1        | topics | CAT1     |
      | Course 2 | C2        | topics | CAT2     |
      | Course 3 | C3        | topics | CAT3     |
      | Course 4 | C4        | topics | CAT4     |
      | Course 5 | C5        | topics | CAT4     |
      | Course 6 | C6        | topics | CAT4     |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | manager1 | Manager   | 1        | manager1@example.com |
      | manager2 | Manager   | 2        | manager2@example.com |
      | manager3 | Manager   | 3        | manager3@example.com |
      | viewer1  | Viewer    | 1        | viewer1@example.com  |
      | editor1  | Editor    | 1        | editor1@example.com  |
    And the following "roles" exist:
      | name            | shortname |
      | Program viewer  | pviewer   |
      | Program manager | pmanager  |
      | Program editor  | peditor   |
      | Fields manager  | cfmanager |

    And the following "permission overrides" exist:
      | capability                     | permission | role     | contextlevel | reference |
      | tool/muprog:view            | Allow      | pviewer  | System       |           |
      | tool/muprog:view            | Allow      | pmanager | System       |           |
      | tool/muprog:edit            | Allow      | pmanager | System       |           |
      | tool/muprog:delete          | Allow      | pmanager | System       |           |
      | tool/muprog:addcourse       | Allow      | pmanager | System       |           |
      | tool/muprog:allocate        | Allow      | pmanager | System       |           |
      | tool/muprog:edit            | Allow      | peditor  | System       |           |
      | tool/muprog:view            | Allow      | peditor  | System       |           |
      | tool/muprog:admin          | Allow      | peditor  | System       |           |
      | moodle/site:configview         | Allow      | cfmanager| System       |           |
      | tool/muprog:configurecustomfields   | Allow      | cfmanager| System       |           |

    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | manager1  | pmanager      | System       |           |
      | manager2  | pmanager      | Category     | CAT2      |
      | manager2  | pmanager      | Category     | CAT3      |
      | manager3  | cfmanager     | System       |           |
      | viewer1   | pviewer       | System       |           |
      | editor1   | peditor       | System       |           |

  @javascript
  Scenario: Manager may create a new program with required settings
    Given I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page

    When I click on "Add program" "button"
    And the following fields in the ".modal-dialog" "css_element" match these values:
      | Program name  |             |
      | Program ID    |             |
      | Course groups | No          |
      | Description   |             |
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Program name  | Program 001 |
      | Program ID    | PR01        |
    And I click on "Add program" "button" in the ".modal-dialog" "css_element"
    Then I should see "Program 001" in the "Program name" definition list item
    And I should see "PR01" in the "Program ID" definition list item
    And I should see "System" in the "Category" definition list item
    And I should see "No" in the "Course groups" definition list item
    And I should see "No" in the "Archived" definition list item
    And I am on the "tool_muprog > All programs management" page
    And "Program 001" row "Category" column of "reportbuilder-table" table should contain "System"
    And "Program 001" row "Program ID" column of "reportbuilder-table" table should contain "PR01"
    And "Program 001" row "Public" column of "reportbuilder-table" table should contain "No"

  @javascript @_file_upload
  Scenario: Manager may create a new programs with all settings
    Given I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page

    When I click on "Add program" "button"
    And the following fields in the ".modal-dialog" "css_element" match these values:
      | Program name  |             |
      | Program ID    |             |
      | Course groups | No          |
      | Description   |             |
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Program name  | Program 001 |
      | Program ID    | PR01        |
      | Course groups | Yes         |
      | Description   | Nice desc   |
    And I upload "admin/tool/muprog/tests/fixtures/badge.png" file to "Program image" filemanager
    And I set the field "Context" to "Cat 2"
    And I set the field "Tags" to "Mathematics, Algebra"
    And I click on "Add program" "button" in the ".modal-dialog" "css_element"
    Then I should see "Program 001" in the "Program name" definition list item
    And I should see "PR01" in the "Program ID" definition list item
    And I should see "Cat 2" in the "Category" definition list item
    And I should see "Yes" in the "Course groups" definition list item
    And I should see "No" in the "Archived" definition list item
    And I should see "Mathematics" in the "Tags" definition list item
    And I should see "Algebra" in the "Tags" definition list item
    And I am on the "Cat 2" "tool_muprog > Program management" page
    And "PR01" row "Program name" column of "reportbuilder-table" table should contain "Program 001"
    And "PR01" row "Public" column of "reportbuilder-table" table should contain "No"
    And "PR01" row "Courses" column of "reportbuilder-table" table should contain "0"
    And "PR01" row "Allocations" column of "reportbuilder-table" table should contain "0"

  @javascript
  Scenario: Manager may update basic general settings of an existing program
    Given I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page
    And I click on "Add program" "button"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Program name  | Program 001 |
      | Program ID    | PR01        |
    And I click on "Add program" "button" in the ".modal-dialog" "css_element"

    When I press "Edit"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Program name  | Program 002 |
      | Program ID    | PR02        |
    And I click on "Update program" "button" in the ".modal-dialog" "css_element"
    Then I should see "Program 002" in the "Program name" definition list item
    And I should see "PR02" in the "Program ID" definition list item
    And I should see "System" in the "Category" definition list item
    And I should see "No" in the "Course groups" definition list item
    And I should see "No" in the "Archived" definition list item

  @javascript
  Scenario: Manager may archive and restore program
    Given I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page
    And I click on "Add program" "button"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Program name  | Program 001 |
      | Program ID    | PR01        |
    And I click on "Add program" "button" in the ".modal-dialog" "css_element"

    When I click on "Archive program" "link"
    And I click on "Archive program" "button" in the ".modal-dialog" "css_element"
    Then I should see "Yes" in the "Archived" definition list item

    When I click on "Restore program" "link"
    And I click on "Restore program" "button" in the ".modal-dialog" "css_element"
    Then I should see "No" in the "Archived" definition list item

  @javascript
  Scenario: Manager may delete program
    Given I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page
    And I click on "Add program" "button"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Program name  | Program 001 |
      | Program ID    | PR01        |
    And I click on "Add program" "button" in the ".modal-dialog" "css_element"
    And I click on "Archive program" "link"
    And I click on "Archive program" "button" in the ".modal-dialog" "css_element"
    And I should see "Yes" in the "Archived" definition list item

    When I click on "Delete program" action from "Program actions" dropdown
    And I click on "Delete program" "button" in the ".modal-dialog" "css_element"
    Then I should see "No programs found"

  @javascript @_file_upload
  Scenario: Manager may update all general settings of an existing program
    Given I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page
    And I click on "Add program" "button"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Program name  | Program 002 |
      | Program ID    | PR02        |
    And I set the field "Context" to "Cat 1"
    And I set the field "Tags" to "Logic"
    And I click on "Add program" "button" in the ".modal-dialog" "css_element"

    When I press "Edit"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Program name  | Program 001 |
      | Program ID    | PR01        |
      | Course groups | Yes         |
      | Description   | Nice desc   |
    And I upload "admin/tool/muprog/tests/fixtures/badge.png" file to "Program image" filemanager
    And I set the field "Context" to "Cat 2"
    And I set the field "Tags" to "Mathematics, Algebra"
    And I click on "Update program" "button" in the ".modal-dialog" "css_element"
    Then I should see "Program 001" in the "Program name" definition list item
    And I should see "PR01" in the "Program ID" definition list item
    And I should see "Cat 2" in the "Category" definition list item
    And I should see "Yes" in the "Course groups" definition list item
    And I should see "No" in the "Archived" definition list item
    And I should see "Mathematics" in the "Tags" definition list item
    And I should see "Algebra" in the "Tags" definition list item

  @javascript
  Scenario: Set up and edit custom fields of programs
    Given I log in as "manager3"
    And I navigate to "Programs > Program custom fields" in site administration
    And I press "Add a new category"
    And I click on "Add a new custom field" "link"
    And I click on "Short text" "link"
    And I set the following fields to these values:
      | Name                                | Test field |
      | Short name                          | testfield  |
      | Users with view programs capability | 1          |
    And I click on "Save changes" "button" in the "Adding a new Short text" "dialogue"
    Then the following should exist in the "generaltable" table:
      | Custom field | Short name | Type       |
      | Test field   | testfield  | Short text |
    When I log in as "editor1"
    And I am on the "tool_muprog > All programs management" page
    And I click on "Add program" "button"
    And I expand all fieldsets
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Program name       | Program 007       |
      | Program ID         | P007              |
      | Test field         | Test value        |
    And I click on "Add program" "button" in the ".modal-dialog" "css_element"

  @javascript
  Scenario: Manager may see there are deleted courses in program in list of programs
    Given the following "tool_muprog > programs" exist:
      | fullname    | idnumber |
      | Program 001 | PR01      |
      | Program 002 | PR02      |
      | Program 003 | PR03      |
    And the following "tool_muprog > program_items" exist:
      | program     | parent     | course   | fullname   | sequencetype     | minprerequisites |
      | Program 001 |            | Course 1 |            |                  |                  |
      | Program 001 |            | Course 2 |            |                  |                  |
      | Program 001 |            | Course 3 |            |                  |                  |
      | Program 002 |            | Course 1 |            |                  |                  |
      | Program 002 |            | Course 3 |            |                  |                  |
      | Program 003 |            | Course 4 |            |                  |                  |
      | Program 003 |            | Course 5 |            |                  |                  |
    And I log in as "admin"
    And I go to the courses management page
    And I should see the "Course categories and courses" management page
    And I click on category "Cat 1" in the management interface
    And I click on "delete" action for "Course 1" in management course listing
    And I press "Delete"
    And I go to the courses management page
    And I should see the "Course categories and courses" management page
    And I click on category "Cat 2" in the management interface
    And I click on "delete" action for "Course 2" in management course listing
    And I press "Delete"
    And I log out

    When I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page
    Then "PR01" row "Courses" column of "reportbuilder-table" table should contain "Missing courses: 2"
    And "PR02" row "Courses" column of "reportbuilder-table" table should contain "Missing courses: 1"
    And "PR03" row "Courses" column of "reportbuilder-table" table should not contain "Missing courses"
