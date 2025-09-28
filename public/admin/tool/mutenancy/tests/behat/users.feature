@tool @tool_mutenancy @MuTMS @javascript
Feature: Tenant members and associated users section
  Background:
    Given unnecessary Admin bookmarks block gets deleted
    And the following "tool_mutenancy > tenants" exist:
      | name     | idnumber |
      | Tenant 1 | TEN1     |
      | Tenant 2 | TEN2     |
    And the following "users" exist:
      | username  | firstname | lastname  | email                | tenant |
      | manager1  | Tenant 1  | Manager   | manager1@example.com | TEN1   |
    And the following "tool_mutenancy > tenant managers" exist:
      | tenant | user     |
      | TEN1   | manager1 |

  Scenario: Tenant manager may add, update and delete tenant members
    Given I log in as "manager1"
    And I am on the "TEN1" "tool_mutenancy > Tenant users" page

    When I press "Create account"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Username      | member1              |
      | New password  | tEstP_s8             |
      | First name    | First                |
      | Last name     | Member               |
      | Email address | member1@example.com  |
    And I click on "Create account" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | First name    | Email address        | Tenant member |
      | First Member  | member1@example.com  | Yes           |

    And I log out
    And I am on homepage
    And I click on "Log in" "link" in the ".logininfo" "css_element"
    When I set the field "Username" to "member1"
    And I set the field "Password" to "tEstP_s8"
    And I press "Log in"
    Then I should see "Welcome, First!"

    And I log in as "manager1"
    And I am on the "TEN1" "tool_mutenancy > Tenant users" page
    When I click on "Actions" "link" in the "First Member" "table_row"
    And I click on "Edit" "link" in the "First Member" "table_row"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Username      | student1             |
      | First name    | Prvni                |
      | Last name     | Student              |
      | Email address | student1@example.com |
    And I click on "Update account" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | First name    | Email address        | Tenant member |
      | Prvni Student | student1@example.com | Yes           |

    When I click on "Actions" "link" in the "Prvni Student" "table_row"
    And I click on "Suspend user account" "link" in the "Prvni Student" "table_row"
    And I click on "Suspend user account" "button" in the ".modal-dialog" "css_element"
    Then I should see "Suspended" in the "student1@example.com" "table_row"

    When I click on "Actions" "link" in the "Prvni Student" "table_row"
    And I click on "Activate user account" "link" in the "Prvni Student" "table_row"
    And I click on "Activate user account" "button" in the ".modal-dialog" "css_element"
    Then I should not see "Suspended" in the "student1@example.com" "table_row"

    When I click on "Actions" "link" in the "Prvni Student" "table_row"
    And I click on "Delete" "link" in the "Prvni Student" "table_row"
    And I click on "Delete" "button" in the ".modal-dialog" "css_element"
    Then I should not see "Prvni student"
