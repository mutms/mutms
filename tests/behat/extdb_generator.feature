@tool @tool_mulib @MuTMS @javascript
Feature: Test tool_mulib external database generator
  Background:
    Given the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
      | Cat 3 | CAT2     | CAT3     |

  Scenario: tool_mulib generator creates external database servers
    Given unnecessary Admin bookmarks block gets deleted

    When the following "tool_mulib > extdb_servers" exist:
      | name          |
      | Test server 1 |
    And the following "tool_mulib > extdb_servers" exist:
      | name          | dsn                             | dbuser | dbpass | dboptions | note      |
      | Test server 2 | pgsql:host=127.0.0.1;dbname=edb | root   | secret | {"3":2}   | Some note |

    And I log in as "admin"
    And I navigate to "Server > External databases > External database servers" in site administration
    Then the following should exist in the "reportbuilder-table" table:
      | Name          | PDO DSN                         | Database user | PDO options (JSON) | Note      |
      | Test server 1 |                                 |               |                    |           |
      | Test server 2 | pgsql:host=127.0.0.1;dbname=edb | root          | {"3":2}            | Some note |

  @tool_muprog
  Scenario: tool_mulib generator creates external database queries
    Given I skip tests if "tool_muprog" is not installed
    And unnecessary Admin bookmarks block gets deleted
    And the following "tool_mulib > extdb_servers" exist:
      | name          |
      | Test server 1 |
      | Test server 2 |
    When the following "tool_mulib > extdb_queries" exist:
      | name         | server        | component   | type       | sqlquery               |
      | Test query 1 | Test server 1 | tool_muprog | allocation | SELECT * FROM m_user   |
    And the following "tool_mulib > extdb_queries" exist:
      | name         | server        | component   | type       | sqlquery               | contextlevel | reference | note      |
      | Test query 2 | Test server 2 | tool_muprog | allocation | SELECT * FROM m_course | Category     | CAT1      | Some note |

    And I log in as "admin"
    And I navigate to "Server > External databases > External database queries" in site administration
    Then the following should exist in the "reportbuilder-table" table:
      | Name         | Category | External database server | SQL query              | Note      |
      | Test query 1 | System   | Test server 1            | SELECT * FROM m_user   |           |
      | Test query 2 | Cat 1    | Test server 2            | SELECT * FROM m_course | Some note |
