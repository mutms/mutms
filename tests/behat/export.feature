@tool @tool_muprog @MuTMS @javascript
Feature: Program export tests

  Background:
    Given unnecessary Admin bookmarks block gets deleted
    # Category 1 with no idnumber is expected at the top level - former Miscellaneous category.
    And the following "categories" exist:
      | name       | category | idnumber |
      | Category 2 | 0        | CAT2     |
      | Category 3 | 0        | CAT3     |
    And the following "courses" exist:
      | fullname  | shortname  | category |
      | Course 01 | C01        | CAT2     |
      | Course 02 | C02        | CAT2     |
      | Course 03 | C03        | CAT3     |
      | Course 04 | C04        | CAT3     |
      | Course 05 | C05        | CAT3     |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | manager1 | Manager   | 1        | manager1@example.com |
      | manager2 | Manager   | 2        | manager1@example.com |
    And the following "roles" exist:
      | name            | shortname |
      | Program viewer  | pviewer   |
      | Program manager | pmanager  |
    And the following "permission overrides" exist:
      | capability                     | permission | role     | contextlevel | reference |
      | tool/muprog:view            | Allow      | pmanager | System       |           |
      | tool/muprog:export          | Allow      | pmanager | System       |           |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | manager1  | pmanager      | System       |           |
      | manager2  | pmanager      | Category     | CAT2      |
      | manager2  | pmanager      | Category     | CAT3      |
    When the following "tool_muprog > programs" exist:
      | fullname    | idnumber | category   | publicaccess |
      | Program 000 | PR0      |            | 0            |
      | Program 001 | PR1      | Category 1 | 1            |
      | Program 002 | PR2      | Category 2 | 0            |
      | Program 003 | PR3      | Category 3 | 0            |
      | Program 004 | PR4      | Category 2 | 0            |
      | Program 005 | PR5      | Category 2 | 0            |

  Scenario: Site program manager max export programs
    Given I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page

    When I click on "Export programs" action from "Programs actions" dropdown
    And the following fields match these values:
      | Context     | All programs |
      | archived    | 0            |
      | File format | JSON         |
    Then I press "Export programs"
    And I wait "1" seconds
    When I set the following fields to these values:
      | Context     | Category 1   |
      | archived    | 0            |
      | File format | CSV          |
    Then I press "Export programs"
    And I wait "1" seconds

    When I press "Back"
    And I should see "Program 001"
    And I follow "Program 001"
    And I click on "Export programs" action from "Program actions" dropdown
    Then I press "Export programs"
    And I wait "1" seconds

    When I set the following fields to these values:
      | Programs    | Program 001, Program 002 |
    Then I press "Export programs"
    And I wait "1" seconds

  Scenario: Category program manager max export programs
    Given I log in as "manager1"
    And I am on the "Category 2" "tool_muprog > Program management" page

    When I click on "Export programs" action from "Programs actions" dropdown
    And the following fields match these values:
      | Context     | Category 2   |
      | archived    | 0            |
      | File format | JSON         |
    Then I press "Export programs"
    And I wait "1" seconds
    When I set the following fields to these values:
      | Context     | Category 3   |
      | archived    | 0            |
      | File format | CSV          |
    Then I press "Export programs"
    And I wait "1" seconds

    When I press "Back"
    And I am on the "Category 2" "tool_muprog > Program management" page
    And I should see "Program 002"
    And I follow "Program 002"
    And I click on "Export programs" action from "Program actions" dropdown
    Then I press "Export programs"
    And I wait "1" seconds

    When I set the following fields to these values:
      | Programs    | Program 002, Program 003 |
    Then I press "Export programs"
    And I wait "1" seconds
