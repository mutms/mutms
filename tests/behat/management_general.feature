@tool @tool_mucertify @MuTMS
Feature: General Certification management tests

  Background:
    Given unnecessary Admin bookmarks block gets deleted
    And the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
      | Cat 3 | 0        | CAT3     |
      | Cat 4 | CAT3     | CAT4     |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | manager1 | Manager   | 1        | manager1@example.com |
      | manager2 | Manager   | 2        | manager2@example.com |
      | manager3 | Manager   | 3        | manager3@example.com |
      | viewer1  | Viewer    | 1        | viewer1@example.com  |
      | editor1  | Editor    | 1        | editor1@example.com  |
    And the following "roles" exist:
      | name                  | shortname |
      | Certification viewer  | pviewer   |
      | Certification manager | pmanager  |
      | Certification editor  | ceditor   |
      | Custom fields manager | cfmanager |
    And the following "permission overrides" exist:
      | capability                           | permission | role     | contextlevel | reference |
      | tool/mucertify:view                  | Allow      | pviewer  | System       |           |
      | tool/mucertify:view                  | Allow      | pmanager | System       |           |
      | tool/mucertify:edit                  | Allow      | pmanager | System       |           |
      | tool/mucertify:delete                | Allow      | pmanager | System       |           |
      | tool/mucertify:assign                | Allow      | pmanager | System       |           |
      | tool/mucertify:edit                  | Allow      | ceditor  | System       |           |
      | tool/mucertify:view                  | Allow      | ceditor  | System       |           |
      | tool/mucertify:admin                 | Allow      | ceditor  | System       |           |
      | moodle/site:configview               | Allow      | cfmanager| System       |           |
      | tool/mucertify:configurecustomfields | Allow      | cfmanager| System       |           |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | manager1  | pmanager      | System       |           |
      | manager2  | pmanager      | Category     | CAT2      |
      | manager2  | pmanager      | Category     | CAT3      |
      | manager3  | cfmanager     | System       |           |
      | viewer1   | pviewer       | System       |           |
      | editor1   | ceditor       | System       |           |

  @javascript
  Scenario: Manager may create a new certification with required settings
    Given I log in as "manager1"
    And I am on the "tool_mucertify > All certifications management" page

    When I press "Add certification"
    And the following fields in the ".modal-dialog" "css_element" match these values:
      | Certification name |             |
      | Certification ID   |             |
      | Description        |             |
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Certification name | Certification 001 |
      | Certification ID   | CT01              |
    And I click on "Add certification" "button" in the ".modal-dialog" "css_element"
    Then I should see "Certification 001" in the "Certification name" definition list item
    And I should see "CT01" in the "Certification ID" definition list item
    And I should see "System" in the "Category" definition list item
    And I should see "No" in the "Archived" definition list item
    And I am on the "tool_mucertify > All certifications management" page
    And "Certification 001" row "Category" column of "reportbuilder-table" table should contain "System"
    And "Certification 001" row "Certification ID" column of "reportbuilder-table" table should contain "CT01"
    And "Certification 001" row "Public" column of "reportbuilder-table" table should contain "No"

  @javascript @_file_upload
  Scenario: Manager may create a new certifications with all settings
    Given I log in as "manager1"
    And I am on the "tool_mucertify > All certifications management" page

    When I press "Add certification"
    And the following fields in the ".modal-dialog" "css_element" match these values:
      | Certification name |             |
      | Certification ID   |             |
      | Description        |             |
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Certification name | Certification 001 |
      | Certification ID   | CT01        |
      | Description        | Nice desc   |
    And I upload "admin/tool/mucertify/tests/fixtures/badge.png" file to "Certification image" filemanager
    And I set the field "Context" to "Cat 2"
    And I set the field "Tags" to "Mathematics, Algebra"
    And I click on "Add certification" "button" in the ".modal-dialog" "css_element"
    Then I should see "Certification 001" in the "Certification name" definition list item
    And I should see "CT01" in the "Certification ID" definition list item
    And I should see "Cat 2" in the "Category" definition list item
    And I should see "No" in the "Archived" definition list item
    And I should see "Mathematics" in the "Tags" definition list item
    And I should see "Algebra" in the "Tags" definition list item
    And I am on the "Cat 2" "tool_mucertify > Certification management" page
    And "CT01" row "Public" column of "reportbuilder-table" table should contain "No"
    And "CT01" row "Assignments" column of "reportbuilder-table" table should contain "0"

  @javascript
  Scenario: Manager may update basic general settings of an existing certification
    Given I log in as "manager1"
    And I am on the "tool_mucertify > All certifications management" page
    And I press "Add certification"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Certification name | Certification 001 |
      | Certification ID   | CT01              |
    And I click on "Add certification" "button" in the ".modal-dialog" "css_element"

    When I press "Edit"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Certification name | Certification 002 |
      | Certification ID   | CT02              |
    And I click on "Update certification" "button" in the ".modal-dialog" "css_element"
    Then I should see "Certification 002" in the "Certification name" definition list item
    And I should see "CT02" in the "Certification ID" definition list item
    And I should see "System" in the "Category" definition list item
    And I should see "No" in the "Archived" definition list item

  @javascript @_file_upload
  Scenario: Manager may update all general settings of an existing certification
    Given I log in as "manager1"
    And I am on the "tool_mucertify > All certifications management" page
    And I press "Add certification"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Certification name | Certification 002 |
      | Certification ID   | CT02              |
    And I set the field "Context" to "Cat 1"
    And I set the field "Tags" to "Logic"
    And I click on "Add certification" "button" in the ".modal-dialog" "css_element"

    When I press "Edit"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Certification name | Certification 001 |
      | Certification ID   | CT01              |
      | Description        | Nice desc         |
    And I upload "admin/tool/mucertify/tests/fixtures/badge.png" file to "Certification image" filemanager
    And I set the field "Context" to "Cat 2"
    And I set the field "Tags" to "Mathematics, Algebra"
    And I click on "Update certification" "button" in the ".modal-dialog" "css_element"
    Then I should see "Certification 001" in the "Certification name" definition list item
    And I should see "CT01" in the "Certification ID" definition list item
    And I should see "Cat 2" in the "Category" definition list item
    And I should see "No" in the "Archived" definition list item
    And I should see "Mathematics" in the "Tags" definition list item
    And I should see "Algebra" in the "Tags" definition list item

  @javascript
  Scenario: Set up and edit custom fields of certifications
    Given I log in as "manager3"
    And I navigate to "Certifications > Certification custom fields" in site administration
    And I press "Add a new category"
    And I click on "Add a new custom field" "link"
    And I click on "Short text" "link"
    And I set the following fields to these values:
      | Name                                     | Test field |
      | Short name                               | testfield  |
      | Users with view certification capability | 1          |
    And I click on "Save changes" "button" in the "Adding a new Short text" "dialogue"
    Then the following should exist in the "generaltable" table:
      | Custom field | Short name | Type       |
      | Test field   | testfield  | Short text |
    When I log in as "editor1"
    And I am on the "tool_mucertify > All certifications management" page
    And I press "Add certification"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Certification name | Certification 002 |
      | Certification ID   | CT02              |
      | Test field         | Test value        |
    And I click on "Add certification" "button" in the ".modal-dialog" "css_element"

  @javascript
  Scenario: Manager may archive and restore certification
    Given I log in as "manager1"
    And I am on the "tool_mucertify > All certifications management" page
    And I click on "Add certification" "button"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Certification name  | Certification 001 |
      | Certification ID    | C01               |
    And I click on "Add certification" "button" in the ".modal-dialog" "css_element"

    When I click on "Archive certification" "link"
    And I click on "Archive certification" "button" in the ".modal-dialog" "css_element"
    Then I should see "Yes" in the "Archived" definition list item

    When I click on "Restore certification" "link"
    And I click on "Restore certification" "button" in the ".modal-dialog" "css_element"
    Then I should see "No" in the "Archived" definition list item

  @javascript
  Scenario: Manager may delete certification
    Given I log in as "manager1"
    And I am on the "tool_mucertify > All certifications management" page
    And I click on "Add certification" "button"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Certification name  | Certification 001 |
      | Certification ID    | C01               |
    And I click on "Add certification" "button" in the ".modal-dialog" "css_element"
    And I click on "Archive certification" "link"
    And I click on "Archive certification" "button" in the ".modal-dialog" "css_element"
    And I should see "Yes" in the "Archived" definition list item

    When I click on "Delete certification" action from "Certification actions" dropdown
    And I click on "Delete certification" "button" in the ".modal-dialog" "css_element"
    Then I should see "No certifications found"
