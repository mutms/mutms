@tool @tool_muprog @MuTMS
Feature: Programs navigation behat steps test

  Background:
    Given unnecessary Admin bookmarks block gets deleted
    And the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
      | Cat 3 | 0        | CAT3     |
      | Cat 4 | CAT3     | CAT4     |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
      | Course 2 | C2        | topics |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | manager1 | Manager   | 1        | manager1@example.com |
      | manager2 | Manager   | 2        | manager2@example.com |
      | admin1   | Admin     | 1        | admin1@example.com   |
      | viewer1  | Viewer    | 1        | viewer1@example.com  |
      | viewer2  | Viewer    | 2        | viewer2@example.com  |
      | student1 | Student   | 1        | student1@example.com |
    And the following "roles" exist:
      | name           | shortname     |
      | Program viewer | pviewer       |
      | Program admin  | padmin        |
    And the following "permission overrides" exist:
      | capability                     | permission | role         | contextlevel | reference |
      | tool/muprog:view               | Allow      | pviewer      | System       |           |
      | moodle/site:configview         | Allow      | pviewer      | System       |           |
      | tool/muprog:admin              | Allow      | padmin       | System       |           |
      | moodle/site:configview         | Allow      | padmin       | System       |           |
    And the following "role assigns" exist:
      | user     | role          | contextlevel | reference |
      | manager1 | manager       | System       |           |
      | manager2 | manager       | Category     | CAT1      |
      | admin1   | padmin        | System       |           |
      | viewer1  | pviewer       | System       |           |
      | viewer2  | pviewer       | Category     | CAT1      |
    And the following "tool_muprog > programs" exist:
      | fullname    | idnumber | category | publicaccess | archived |
      | Program 000 | PR0      |          | 0            | 0        |
      | Program 001 | PR1      | Cat 1    | 1            | 0        |
      | Program 002 | PR2      | Cat 2    | 0            | 0        |
      | Program 003 | PR3      |          | 1            | 1        |

  @javascript
  Scenario: Admin navigates to programs via behat step
    Given I log in as "admin"

    When I am on the "tool_muprog > All programs management" page
    Then I should see "Programs"
    And I should see "Program 000"
    And I should see "Program 001"
    And I should see "Program 002"
    And I should see "Program 003"

    When I click on "Filters" "button"
    And I set the following fields in the "Exclude sub-categories" "core_reportbuilder > Filter" to these values:
      | Exclude sub-categories operator | Yes |
    And I click on "Apply" "button" in the "[data-region='report-filters']" "css_element"
    And I click on "Filters" "button"
    Then I should see "Programs"
    And I should see "Program 000"
    And I should not see "Program 001"
    And I should not see "Program 002"
    And I should see "Program 003"

    When I am on the "System" "tool_muprog > Program management" page
    Then I should see "Programs"
    And I should see "Program 000"
    And I should not see "Program 001"
    And I should not see "Program 002"
    And I should see "Program 003"

    When I am on the "Cat 1" "tool_muprog > Program management" page
    Then I should see "Programs"
    And I should not see "Program 000"
    And I should see "Program 001"
    And I should not see "Program 002"
    And I should not see "Program 003"

  Scenario: Admin navigates to programs the normal way
    Given I log in as "admin"

    When I navigate to "Programs > Program management" in site administration
    Then I should see "Programs"
    And I should see "Program 000"
    And I should see "Program 001"
    And I should see "Program 002"
    And I should see "Program 003"

    When I follow "Cat 1"
    Then I should see "Programs"
    And I should not see "Program 000"
    And I should see "Program 001"
    And I should not see "Program 002"
    And I should not see "Program 003"

  Scenario: Full manager navigates to programs via behat step
    Given I log in as "manager1"

    When I am on the "tool_muprog > All programs management" page
    Then I should see "Programs"
    And I should see "Program 000"
    And I should see "Program 001"
    And I should see "Program 002"
    And I should see "Program 003"

    When I am on the "system" "tool_muprog > Program management" page
    Then I should see "Programs"
    And I should see "Program 000"
    And I should see "Program 001"
    And I should see "Program 002"
    And I should see "Program 003"

    When I am on the "Cat 1" "tool_muprog > Program management" page
    Then I should see "Programs"
    And I should not see "Program 000"
    And I should see "Program 001"
    And I should not see "Program 002"
    And I should not see "Program 003"

  Scenario: Full manager navigates to programs the normal way
    Given I log in as "manager1"

    When I navigate to "Programs > Program management" in site administration
    Then I should see "Programs"
    And I should see "Program 000"
    And I should see "Program 001"
    And I should see "Program 002"
    And I should see "Program 003"

    When I follow "Cat 1"
    Then I should see "Programs"
    And I should not see "Program 000"
    And I should see "Program 001"
    And I should not see "Program 002"
    And I should not see "Program 003"

  Scenario: Category manager navigates to programs via behat step
    Given I log in as "manager2"

    When I am on the "Cat 1" "tool_muprog > Program management" page
    Then I should see "Programs"
    And I should not see "Program 000"
    And I should see "Program 001"
    And I should not see "Program 002"
    And I should not see "Program 003"

  Scenario: Full viewer navigates to programs via behat step
    Given the following "permission overrides" exist:
      | capability                     | permission | role         | contextlevel | reference |
      | moodle/site:configview         | Prohibit   | pviewer      | System       |           |
    And I log in as "viewer1"

    When I am on the "tool_muprog > All programs management" page
    Then I should see "Programs"
    And I should see "Program 000"
    And I should see "Program 001"
    And I should see "Program 002"
    And I should see "Program 003"

    When I am on the "system" "tool_muprog > Program management" page
    Then I should see "Programs"
    And I should see "Program 000"
    And I should see "Program 001"
    And I should see "Program 002"
    And I should see "Program 003"

    When I am on the "Cat 1" "tool_muprog > Program management" page
    Then I should see "Programs"
    And I should not see "Program 000"
    And I should see "Program 001"
    And I should not see "Program 002"
    And I should not see "Program 003"

    When I am on the "Program 000" "tool_muprog > Program" page
    Then I should see "Program 000"
    And I should not see "Program 001"
    And I should not see "Program 002"
    And I should not see "Program 003"

    When I am on the "PR0" "tool_muprog > Program" page
    Then I should see "Program 000"
    And I should not see "Program 001"
    And I should not see "Program 002"
    And I should not see "Program 003"

  Scenario: Full viewer navigates to programs the normal way
    Given I log in as "viewer1"

    When I navigate to "Programs > Program management" in site administration
    Then I should see "Programs"
    And I should see "Program 000"
    And I should see "Program 001"
    And I should see "Program 002"
    And I should see "Program 003"
    And I should see "Program 003"

    When I follow "Cat 1"
    Then I should see "Programs"
    And I should not see "Program 000"
    And I should see "Program 001"
    And I should not see "Program 002"
    And I should not see "Program 003"
    And I should not see "Program 003"

    When I follow "System"
    Then I should see "Programs"
    And I should see "Program 000"
    And I should see "Program 001"
    And I should see "Program 002"
    And I should see "Program 003"
    And I should see "Program 003"

  Scenario: Category viewer navigates to programs via behat step
    Given I log in as "viewer2"

    When I am on the "Cat 1" "tool_muprog > Program management" page
    Then I should see "Programs"
    And I should not see "Program 000"
    And I should see "Program 001"
    And I should not see "Program 002"
    And I should not see "Program 003"

  Scenario: Student navigates to Program catalogue via behat step
    Given I log in as "student1"

    When I am on the "tool_muprog > Program catalogue" page
    Then I should see "Program catalogue"
    And I should see "Program 001"
    And I should not see "Program 000"
    And I should not see "Program 002"
    And I should not see "Program 003"

  Scenario: Student navigates to My programs via behat step
    Given I log in as "student1"

    When I am on the "tool_muprog > My programs" page
    Then I should see "My programs"
    And I should see "You are not allocated to any programs."

  Scenario: Program admin or site config capabilities are needed to see program settings
    Given the following "permission overrides" exist:
      | capability                    | permission | role         | contextlevel | reference |
      | moodle/site:config            | Allow      | manager      | System       |           |

    When I log in as "admin"
    And I navigate to "Programs > Program settings" in site administration
    Then I should see "Allow cohort allocation"
    And I log out

    When I log in as "manager1"
    And I navigate to "Programs > Program settings" in site administration
    Then I should see "Allow cohort allocation"
    And I log out
