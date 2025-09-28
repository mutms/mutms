@tool @tool_mucertify @MuTMS
Feature: Certifications navigation behat steps test

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
      | viewer1  | Viewer    | 1        | viewer1@example.com  |
      | viewer2  | Viewer    | 2        | viewer2@example.com  |
      | student1 | Student   | 1        | student1@example.com |
      | admin1   | Admin     | 1        | admin1@example.com   |
    And the following "roles" exist:
      | name                 | shortname |
      | Certification viewer | pviewer   |
      | Program admin        | cadmin    |
    And the following "permission overrides" exist:
      | capability                   | permission | role    | contextlevel | reference |
      | tool/mucertify:view          | Allow      | pviewer | System       |           |
      | tool/mucertify:admin         | Allow      | cadmin  | System       |           |
      | moodle/site:configview       | Allow      | cadmin  | System       |           |
    And the following "role assigns" exist:
      | user     | role          | contextlevel | reference |
      | manager1 | manager       | System       |           |
      | manager2 | manager       | Category     | CAT1      |
      | viewer1  | pviewer       | System       |           |
      | viewer2  | pviewer       | Category     | CAT1      |
      | admin1   | cadmin        | System       |           |
    And the following "tool_mucertify > certifications" exist:
      | fullname          | idnumber | category | publicaccess | archived |
      | Certification 000 | CR0      |          | 0            | 0        |
      | Certification 001 | CR1      | Cat 1    | 1            | 0        |
      | Certification 002 | CR2      | Cat 2    | 0            | 0        |
      | Certification 003 | CR3      |          | 1            | 1        |

  Scenario: Admin navigates to certifications via behat step
    Given I log in as "admin"

    When I am on the "tool_mucertify > All certifications management" page
    Then I should see "Certification 000"
    And I should see "Certification 001"
    And I should see "Certification 002"
    And I should see "Certification 003"

    When I am on the "System" "tool_mucertify > Certification management" page
    Then I should see "Certification 000"
    And I should see "Certification 001"
    And I should see "Certification 002"
    And I should see "Certification 003"

    When I am on the "Cat 1" "tool_mucertify > Certification management" page
    Then I should not see "Certification 000"
    And I should see "Certification 001"
    And I should not see "Certification 002"
    And I should not see "Certification 003"

  @javascript
  Scenario: Admin navigates to certifications the normal way
    Given I log in as "admin"

    When I navigate to "Certifications > Certification management" in site administration
    Then I should see "Certification 000"
    And I should see "Certification 001"
    And I should see "Certification 002"
    And I should see "Certification 003"

    When I click on "Filters" "button"
    And I set the following fields in the "Exclude sub-categories" "core_reportbuilder > Filter" to these values:
      | Exclude sub-categories operator | Yes |
    And I click on "Apply" "button" in the "[data-region='report-filters']" "css_element"
    And I click on "Filters" "button"
    Then I should see "Certification 000"
    And I should not see "Certification 001"
    And I should not see "Certification 002"
    And I should see "Certification 003"

    When I click on "Filters" "button"
    And I set the following fields in the "Exclude sub-categories" "core_reportbuilder > Filter" to these values:
      | Exclude sub-categories operator | Is any value |
    And I click on "Apply" "button" in the "[data-region='report-filters']" "css_element"
    And I click on "Filters" "button"
    Then I should see "Certification 000"
    And I should see "Certification 001"
    And I should see "Certification 002"
    And I should see "Certification 003"

    When I follow "Cat 1"
    Then I should not see "Certification 000"
    And I should see "Certification 001"
    And I should not see "Certification 002"
    And I should not see "Certification 003"

  Scenario: Full manager navigates to certifications via behat step
    Given I log in as "manager1"

    When I am on the "tool_mucertify > All certifications management" page
    Then I should see "Certification 000"
    And I should see "Certification 001"
    And I should see "Certification 002"
    And I should see "Certification 003"

    When I am on the "System" "tool_mucertify > Certification management" page
    Then I should see "Certification 000"
    And I should see "Certification 001"
    And I should see "Certification 002"
    And I should see "Certification 003"

    When I am on the "Cat 1" "tool_mucertify > Certification management" page
    Then I should not see "Certification 000"
    And I should see "Certification 001"
    And I should not see "Certification 002"
    And I should not see "Certification 003"

  Scenario: Category manager navigates to certifications via behat step
    Given I log in as "manager2"

    When I am on the "Cat 1" "tool_mucertify > Certification management" page
    Then I should not see "Certification 000"
    And I should see "Certification 001"
    And I should not see "Certification 002"
    And I should not see "Certification 003"

  Scenario: Full viewer navigates to certifications via behat step
    Given I log in as "viewer1"

    When I am on the "tool_mucertify > All certifications management" page
    Then I should see "Certification 000"
    And I should see "Certification 001"
    And I should see "Certification 002"
    And I should see "Certification 003"

    When I am on the "System" "tool_mucertify > Certification management" page
    Then I should see "Certification 000"
    And I should see "Certification 001"
    And I should see "Certification 002"
    And I should see "Certification 003"

    When I am on the "Cat 1" "tool_mucertify > Certification management" page
    Then I should not see "Certification 000"
    And I should see "Certification 001"
    And I should not see "Certification 002"
    And I should not see "Certification 003"

    When I am on the "Certification 000" "tool_mucertify > Certification" page
    Then I should see "Certification 000"
    And I should not see "Certification 001"
    And I should not see "Certification 002"
    And I should not see "Certification 003"

    When I am on the "CR0" "tool_mucertify > Certification" page
    Then I should see "Certification 000"
    And I should not see "Certification 001"
    And I should not see "Certification 002"
    And I should not see "Certification 003"

  Scenario: Category viewer navigates to certifications via behat step
    Given I log in as "viewer2"

    When I am on the "Cat 1" "tool_mucertify > Certification management" page
    Then I should not see "Certification 000"
    And I should see "Certification 001"
    And I should not see "Certification 002"
    And I should not see "Certification 003"

  Scenario: Student navigates to Certification catalogue via behat step
    Given I log in as "student1"

    When I am on the "tool_mucertify > Certification catalogue" page
    Then I should see "Certification catalogue"
    And I should see "Certification 001"
    And I should not see "Certification 000"
    And I should not see "Certification 002"
    And I should not see "Certification 003"

  Scenario: Student navigates to My certifications via behat step
    Given I log in as "student1"

    When I am on the "tool_mucertify > My certifications" page
    Then I should see "My certifications"
    And I should see "No assigned certifications found."

  @javascript
  Scenario: Certification admin or site config capabilities are needed to see certification settings
    Given the following "permission overrides" exist:
      | capability                    | permission | role         | contextlevel | reference |
      | moodle/site:config            | Allow      | manager      | System       |           |

    When I log in as "manager1"
    And I navigate to "Certifications > Certification settings" in site administration
    Then I should see "Allow cohort allocation"
    And I log out

    When I log in as "admin"
    And I am on the "tool_mucertify > All certifications management" page
    And I follow "Certification 000"
    And I click on "Assignment settings" "link" in the ".secondary-navigation" "css_element"
    Then I should see "Requests with approval"
    Then I navigate to "Certifications > Certification settings" in site administration
    Then I should see "Allow cohort allocation"
    And I set the following fields to these values:
      | Allow approvals              |  0  |
    And I press "Save changes"
    Then I am on the "tool_mucertify > All certifications management" page
    And I follow "Certification 000"
    And I click on "Assignment settings" "link" in the ".secondary-navigation" "css_element"
    Then I should not see "Requests with approval"
    And I log out
