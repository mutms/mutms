@tool @tool_musudo @MuTMS
Feature: Test tool_musudo sudoers management
  Background:
    Given unnecessary Admin bookmarks block gets deleted
    And the following "users" exist:
      | username  | firstname | lastname  | email                |
      | manager1  | First     | Manager   | manager1@example.com |
      | manager2  | Second    | Manager   | manager2@example.com |

  @javascript
  Scenario: Admin may add, update and remove sudoers
    Given I log in as "admin"
    And I navigate to "Users > Permissions > Privileged users" in site administration
    And I should see "No privileged users found."

    When I press "Add privileged user"
    And I set the following fields to these values:
      | User | manager1 |
      | Role | Manager  |
    And I click on "Add privileged user" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | First name    | Email address        | Note | Privileges        |
      | First Manager | manager1@example.com |      | Manager in System |

    When I press "Add privileged user"
    And I set the following fields to these values:
      | User | manager2         |
      | Role | Teacher          |
      | Note | Trusted teacher  |
    And I click on "Add privilege" "button" in the ".modal-dialog" "css_element"
    And I set the following fields to these values:
      | roleid[1]    | Manager |
      | contextid[1] | 3       |
    And I click on "Add privileged user" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | First name     | Email address        | Note            | Privileges                      |
      | First Manager  | manager1@example.com |                 | Manager in System               |
      | Second Manager | manager2@example.com | Trusted teacher | Teacher in System               |
      | Second Manager | manager2@example.com | Trusted teacher | Manager in Category: Category 1 |

    When I click on "Actions" "link" in the "Second Manager" "table_row"
    And I click on "Update privileged user" "link" in the "Second Manager" "table_row"
    And the following fields match these values:
      | Note         | Trusted teacher  |
      | roleid[0]    | Teacher          |
      | contextid[0] | 1                |
      | roleid[1]    | Manager          |
      | contextid[1] | 3                |
    And I set the following fields to these values:
      | Note         | Semi-trusted        |
      | roleid[1]    | Non-editing teacher |
      | contextid[1] | 2                   |
    And I click on "Update privileged user" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | First name     | Email address        | Note            | Privileges                       |
      | First Manager  | manager1@example.com |                 | Manager in System                |
      | Second Manager | manager2@example.com | Semi-trusted    | Teacher in System                |
      | Second Manager | manager2@example.com | Semi-trusted    | Non-editing teacher in Site home |

    When I click on "Actions" "link" in the "Second Manager" "table_row"
    And I click on "Update privileged user" "link" in the "Second Manager" "table_row"
    And I click on "Delete privilege 2" "button" in the ".modal-dialog" "css_element"
    And I click on "Update privileged user" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | First name     | Email address        | Note            | Privileges                       |
      | First Manager  | manager1@example.com |                 | Manager in System                |
      | Second Manager | manager2@example.com | Semi-trusted    | Teacher in System                |
    And I should not see "Non-editing teacher"

    When I click on "Actions" "link" in the "Second Manager" "table_row"
    And I click on "Remove privileged user" "link" in the "Second Manager" "table_row"
    And I click on "Remove privileged user" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | First name     | Email address        | Note            | Privileges                       |
      | First Manager  | manager1@example.com |                 | Manager in System                |
    And I should not see "Second Manager"
