@tool @tool_muhome @javascript @MuTMS
Feature: tool_muhome navigation behat steps test
  Background:
    Given the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
      | Cat 3 | CAT2     | CAT3     |
    And the following "cohorts" exist:
      | name       | idnumber | contextlevel | reference |
      | Cohort 1   | CH1      | System       |           |
      | Cohort 2   | CH2      | System       |           |
      | Cohort 3   | CH3      | System       |           |
    And the following "users" exist:
      | username  | firstname | lastname  | email                |
      | manager1  | Manager   | 1         | manager1@example.com |
      | viewer1   | Viewer    | 1         | viewer1@example.com  |
      | viewer2   | Viewer    | 2         | viewer2@example.com  |
    And the following "roles" exist:
      | name         | shortname |
      | Page manager | pmanager  |
      | Page viewer  | pviewer   |
    And the following "permission overrides" exist:
      | capability                       | permission | role     | contextlevel | reference |
      | tool/muhome:view                 | Allow      | pmanager | System       |           |
      | tool/muhome:manage               | Allow      | pmanager | System       |           |
      | moodle/cohort:view               | Allow      | pmanager | System       |           |
      | moodle/site:configview           | Allow      | pmanager | System       |           |
      | tool/muhome:view                 | Allow      | pviewer  | System       |           |
      | moodle/site:configview           | Allow      | pviewer  | System       |           |
    And the following "role assigns" exist:
      | user      | role         | contextlevel | reference |
      | manager1  | pmanager     | System       |           |
      | viewer1   | pviewer      | System       |           |
      | viewer2   | pviewer      | Category     | CAT2      |
    And the following "tool_muhome > pages" exist:
      | name         | title | status   | guestvisible | uservisible | contextlevel | reference |
      | Home page 1  | CHP1  | active   | 1            | 1           |              |           |
      | Other page 2 | CHP2  | draft    | 0            | 0           | Category     | CAT2      |
      | Other page 3 | CHP3  | active   | 1            | 1           | Category     | CAT3      |

  Scenario: System viewer navigates to All home pages management via behat step
    Given I log in as "viewer1"

    When I am on the "tool_muhome > All home pages management" page
    Then I should see "Home pages management"
    And the following should exist in the "reportbuilder-table" table:
      | Page priority | Page name       | Management category | Status   |
      | 1000          | Home page 1     | System              | Active   |
      | 990           | Other page 2    | Cat 2               | Draft    |
      | 980           | Other page 3    | Cat 3               | Active   |

  Scenario: User navigates to start page via behat step
    Given I log in as "viewer1"

    When I am on homepage
    Then I should see "Dashboard" in the "h1" "css_element"

  Scenario: User navigates to default home page via behat step
    Given I log in as "viewer1"

    When I am on site homepage
    Then I should see "Acceptance test site" in the "h1" "css_element"

  Scenario: System viewer navigates to All home pages management the normal way
    Given I log in as "viewer1"

    When I navigate to "Appearance > Custom home pages > Home pages management" in site administration
    Then I should see "Home pages management"
    And the following should exist in the "reportbuilder-table" table:
      | Page priority | Page name       | Management category | Status   |
      | 1000          | Home page 1     | System              | Active   |
      | 990           | Other page 2    | Cat 2               | Draft    |
      | 980           | Other page 3    | Cat 3               | Active   |

  Scenario: Category viewer navigates to Home pages management via behat step
    Given I log in as "viewer2"

    When I am on the "Cat 2" "tool_muhome > Home pages management" page
    Then I should see "Home pages management"
    And the following should exist in the "reportbuilder-table" table:
      | Page priority | Page name       | Management category | Status   |
      | 990           | Other page 2    | Cat 2               | Draft    |
      | 980           | Other page 3    | Cat 3               | Active   |
    And I should not see "Home page 1"

  Scenario: Category viewer navigates to Home pages management the normal way
    Given I log in as "admin"
    And I set the following administration settings values:
      | Site home items when logged in | List of categories |
    And I log out

    And I log in as "viewer2"
    And I click on "Home" "link" in the ".primary-navigation" "css_element"
    And I follow "Cat 2"
    And I click on "More" "link" in the ".secondary-navigation" "css_element"

    When I click on "Home pages management" "link" in the ".secondary-navigation" "css_element"
    Then I should see "Home pages management"
    And the following should exist in the "reportbuilder-table" table:
      | Page priority | Page name       | Management category | Status   |
      | 990           | Other page 2    | Cat 2               | Draft    |
      | 980           | Other page 3    | Cat 3               | Active   |
    And I should not see "Home page 1"

  Scenario: Category viewer navigates to a home page via behat step
    Given I log in as "viewer2"

    When I am on the "Other page 2" "tool_muhome > Home page" page
    Then I should see "CHP2" in the "h1" "css_element"

  Scenario: Category viewer navigates to a home pages the normal way
    Given I log in as "admin"
    And I set the following administration settings values:
      | Site home items when logged in | List of categories |
    And I log out

    And I log in as "viewer2"
    And I click on "Home" "link" in the ".primary-navigation" "css_element"
    And I follow "Cat 2"
    And I click on "More" "link" in the ".secondary-navigation" "css_element"
    And I click on "Home pages management" "link" in the ".secondary-navigation" "css_element"

    When I follow "Other page 2"
    Then I should see "CHP2" in the "h1" "css_element"
