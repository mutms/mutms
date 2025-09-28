@tool @tool_mucertify @MuTMS
Feature: Certification assignment management tests

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
      | viewer1  | Viewer    | 1        | viewer1@example.com  |
    And the following "roles" exist:
      | name                  | shortname |
      | Certification viewer  | pviewer   |
      | Certification manager | pmanager  |
    And the following "permission overrides" exist:
      | capability                     | permission | role     | contextlevel | reference |
      | tool/mucertify:view            | Allow      | pviewer  | System       |           |
      | tool/mucertify:view            | Allow      | pmanager | System       |           |
      | tool/mucertify:edit            | Allow      | pmanager | System       |           |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | manager1  | pmanager      | System       |           |
      | manager2  | pmanager      | Category     | CAT2      |
      | manager2  | pmanager      | Category     | CAT3      |
      | viewer1   | pviewer       | System       |           |

  @javascript
  Scenario: Manager creates certifications with expected default assignment settings
    Given I log in as "manager1"
    And I am on the "tool_mucertify > All certifications management" page

    And I press "Add certification"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Certification name | Certification 001 |
      | Certification ID   | PR01              |
    And I click on "Add certification" "button" in the ".modal-dialog" "css_element"
    And I click on "Assignment settings" "link" in the ".secondary-navigation" "css_element"
    And I should see "Inactive" in the "Manual assignment" definition list item
    And I should see "Inactive" in the "Self assignment" definition list item
    And I should see "Inactive" in the "Requests with approval" definition list item
    And I should see "Inactive" in the "Automatic cohort assignment" definition list item
