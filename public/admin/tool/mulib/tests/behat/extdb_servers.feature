@tool @tool_mulib @MuTMS @javascript
Feature: Test external database servers management
  Background:
    Given the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
      | Cat 3 | CAT2     | CAT3     |

  Scenario: Administrator may create, update and delete external database servers
    Given unnecessary Admin bookmarks block gets deleted
    And I log in as "admin"
    And I navigate to "Server > External databases > External database servers" in site administration

    When I press "Add server"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Name               | Test server 1                   |
      | PDO DSN            | pgsql:host=127.0.0.1;dbname=edb |
      | Database user      | root                            |
      | Database password  | secret                          |
    And I click on "Add server" "button" in the ".modal-dialog" "css_element"
    And I press "Add server"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Name               | Test server 2                   |
      | PDO DSN            | pgsql:host=127.0.0.2;dbname=edb |
      | Database user      | root                            |
      | Database password  | secret                          |
      | PDO options (JSON) | {"3":2}                         |
      | Note               | Some note                       |
    And I click on "Check connection" "button" in the ".modal-dialog" "css_element"
    And I click on "Add server" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | Name          | PDO DSN                         | Database user | PDO options (JSON) | Note      |
      | Test server 1 | pgsql:host=127.0.0.1;dbname=edb | root          |                    |           |
      | Test server 2 | pgsql:host=127.0.0.2;dbname=edb | root          | {"3":2}            | Some note |

    When I click on "Actions" "link_or_button" in the "Test server 2" "table_row"
    And I click on "Edit" "link" in the ".dropdown-menu.show" "css_element"
    And the following fields in the ".modal-dialog" "css_element" match these values:
      | Name               | Test server 2                   |
      | PDO DSN            | pgsql:host=127.0.0.2;dbname=edb |
      | Database user      | root                            |
      | PDO options (JSON) | {"3":2}                         |
      | Note               | Some note                       |
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Name               | Test server 3                   |
      | PDO DSN            | pgsql:host=127.0.0.3;dbname=edb |
      | Database user      | root3                           |
      | Use different password | 1                           |
      | Database password  | secret3                         |
      | PDO options (JSON) | {"3":3}                         |
      | Note               | Note 3                          |
    And I click on "Check connection" "button" in the ".modal-dialog" "css_element"
    And I click on "Update server" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | Name          | PDO DSN                         | Database user | PDO options (JSON) | Note      |
      | Test server 1 | pgsql:host=127.0.0.1;dbname=edb | root          |                    |           |
      | Test server 3 | pgsql:host=127.0.0.3;dbname=edb | root3         | {"3":3}            | Note 3    |

    When I click on "Actions" "link_or_button" in the "Test server 3" "table_row"
    And I click on "Delete" "link" in the ".dropdown-menu.show" "css_element"
    And I click on "Delete server" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | Name          | PDO DSN                         | Database user | PDO options (JSON) | Note      |
      | Test server 1 | pgsql:host=127.0.0.1;dbname=edb | root          |                    |           |
    And I should not see "Test server 3"
