@tool @tool_mutrain @javascript @MuTMS
Feature: Managers can manage training frameworks

  Background:
    Given the following "custom field categories" exist:
      | name              | component   | area   | itemid |
      | Category for test | core_course | course | 0      |
    And the following "custom fields" exist:
      | name             | category           | type    | shortname | configdata            |
      | Training Field 1 | Category for test  | mutrain | training1 |                       |
      | Training Field 2 | Category for test  | mutrain | training2 |                       |
      | Training Field 3 | Category for test  | mutrain | training3 |                       |
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
      | name             | shortname |
      | Training viewer  | tviewer   |
      | Training manager | tmanager  |
    And the following "permission overrides" exist:
      | capability                     | permission | role     | contextlevel | reference |
      | moodle/site:configview         | Allow      | tviewer  | System       |           |
      | tool/mutrain:viewframeworks    | Allow      | tviewer  | System       |           |
      | moodle/site:configview         | Allow      | tmanager | System       |           |
      | moodle/site:config             | Allow      | tmanager | System       |           |
      | tool/mutrain:viewframeworks    | Allow      | tmanager | System       |           |
      | tool/mutrain:manageframeworks  | Allow      | tmanager | System       |           |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | manager1  | tmanager      | System       |           |
      | manager2  | tmanager      | Category     | CAT2      |
      | manager2  | tmanager      | Category     | CAT3      |
      | viewer1   | tviewer       | System       |           |

  Scenario: Create, update and delete training framework as manager
    Given I log in as "manager1"
    And I navigate to "Training points > Training frameworks" in site administration

    When I press "Add framework"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Framework name          | Framework 1 |
      | Required training total | 33          |
    And I click on "Add framework" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | Framework name | Framework ID | Custom fields | Public | Required training total | Restricted completion validity |
      | Framework 1    |              | 0             | No     | 33                      | No                             |

    When I press "Add framework"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Framework name                 | Framework 2 |
      | Framework ID                   | fwid2       |
      | Description                    | Blah        |
      | Public                         | 1           |
      | Required training total        | 13          |
      | Restricted completion validity | 1           |
    And I click on "Add framework" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | Framework name | Framework ID | Custom fields | Public | Required training total | Restricted completion validity |
      | Framework 1    |              | 0             | No     | 33                      | No                             |
      | Framework 2    | fwid2        | 0             | Yes    | 13                      | Yes                            |

    When I follow "Framework 2"
    And I should see "Blah"
    And I should see "fwid2" in the "Framework ID" definition list item
    And I should see "Yes" in the "Public" definition list item
    And I should see "System" in the "Context" definition list item
    And I should see "13" in the "Required training total" definition list item
    And I should see "Yes" in the "Restricted completion validity" definition list item
    And I should see "No" in the "Archived" definition list item
    And I press "Update framework"
    And the following fields in the ".modal-dialog" "css_element" match these values:
      | Framework name                 | Framework 2 |
      | Framework ID                   | fwid2       |
      | Description                    | Blah        |
      | Public                         | 1           |
      | Required training total        | 13          |
      | Restricted completion validity | 1           |
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Framework name                 | Framework X |
      | Framework ID                   | fwidx       |
      | Description                    | Argh        |
      | Public                         | 0           |
      | Required training total        | 31          |
      | Restricted completion validity | 0           |
      | Context                        | Cat 1       |
    And I click on "Update framework" "button" in the ".modal-dialog" "css_element"
    Then I should see "Framework X"
    And I should see "Argh"
    And I should see "fwidx" in the "Framework ID" definition list item
    And I should see "No" in the "Public" definition list item
    And I should see "Cat 1" in the "Context" definition list item
    And I should see "31" in the "Required training total" definition list item
    And I should see "No" in the "Restricted completion validity" definition list item
    And I should see "No" in the "Archived" definition list item

    And I navigate to "Training points > Training frameworks" in site administration
    When I follow "Framework X"
    And I press "Update framework"
    And the following fields in the ".modal-dialog" "css_element" match these values:
      | Framework name                 | Framework X |
      | Framework ID                   | fwidx       |
      | Description                    | Argh        |
      | Public                         | 0           |
      | Required training total        | 31          |
      | Restricted completion validity | 0           |
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Framework name                 | Framework 2 |
      | Framework ID                   | fwid2       |
      | Description                    | Blah        |
      | Public                         | 1           |
      | Required training total        | 13          |
      | Restricted completion validity | 1           |
      | Context                        | System      |
    And I click on "Update framework" "button" in the ".modal-dialog" "css_element"
    Then I should see "Framework 2"
    And I should see "Blah"
    And I should see "fwid2" in the "Framework ID" definition list item
    And I should see "Yes" in the "Public" definition list item
    And I should see "System" in the "Context" definition list item
    And I should see "13" in the "Required training total" definition list item
    And I should see "Yes" in the "Restricted completion validity" definition list item
    And I should see "No" in the "Archived" definition list item

    When I click on "Archive framework" "link"
    And I click on "Archive framework" "button" in the ".modal-dialog" "css_element"
    Then I should see "Yes" in the "Archived" definition list item

    When I click on "Restore framework" "link"
    And I click on "Restore framework" "button" in the ".modal-dialog" "css_element"
    Then I should see "No" in the "Archived" definition list item

    And I click on "Archive framework" "link"
    And I click on "Archive framework" "button" in the ".modal-dialog" "css_element"
    And I should see "Yes" in the "Archived" definition list item

    When I click on "Delete framework" action from "Framework actions" dropdown
    And I click on "Delete framework" "button" in the ".modal-dialog" "css_element"
    Then I should see "Framework 1"
    And I should not see "Framework 2"

  Scenario: Add and remove training framework fields
    Given I log in as "manager1"
    And I navigate to "Training points > Training frameworks" in site administration
    And I press "Add framework"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Framework name          | Framework 1 |
      | Required training total | 33          |
    And I click on "Add framework" "button" in the ".modal-dialog" "css_element"
    And I follow "Framework 1"

    When I press "Add field"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Custom field | Training Field 1 |
    And I click on "Add field" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "tool_mutrain_field_table" table:
      | Name             | Short name | Component   | Area   |
      | Training Field 1 | training1  | core_course | course |

  Scenario: Add training frameworks via generator
    When the following "tool_mutrain > frameworks" exist:
      | name           | fields               |
      | Framework 001  | training1, training2 |
    And the following "tool_mutrain > frameworks" exist:
      | name           | idnumber | publicaccess | requiredtraining | restrictedcompletion |
      | Framework 002  | fwid002  | 1            | 77               | 0                    |
      | Framework 003  |          | 0            | 99               | 1                    |
    And the following "tool_mutrain > frameworks" exist:
      | name           | category | fields    |
      | Framework 004  | Cat 2    | training3 |
    And I log in as "manager1"
    And I navigate to "Training points > Training frameworks" in site administration
    Then the following should exist in the "reportbuilder-table" table:
      | Framework name | Framework ID | Custom fields | Public | Required training total | Restricted completion validity | Category |
      | Framework 001  |              | 2             | No     | 100                     | No                             | System   |
      | Framework 002  | fwid002      | 0             | Yes    | 77                      | No                             | System   |
      | Framework 003  |              | 0             | No     | 99                      | Yes                            | System   |
      | Framework 004  |              | 1             | No     | 100                     | No                             | Cat 2    |
