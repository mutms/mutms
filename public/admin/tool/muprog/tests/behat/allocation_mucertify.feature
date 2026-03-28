@tool @tool_muprog @javascript @MuTMS
Feature: Certification program allocation tests

  Background:
    Given I skip tests if "tool_mucertify" is not installed
    And unnecessary Admin bookmarks block gets deleted
    And the following "users" exist:
      | username | firstname | lastname | email                | idnumber |
      | manager  | Site      | Manager  | manager@example.com  | m        |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | manager   | manager       | System       |           |

  Scenario: Manager may enable Certifications when creating program
    Given I log in as "manager"
    And I am on the "tool_muprog > All programs management" page

    When I click on "Add program" "button"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Program name      | Program 001 |
      | Program ID        | PR01        |
      | Certifications    | 1           |
    And I click on "Add program" "button" in the ".modal-dialog" "css_element"
    And I follow "Allocation settings"
    Then I should see "Active" in the "Certifications" definition list item
