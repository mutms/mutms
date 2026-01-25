@tool @tool_mutrain @javascript @MuTMS
Feature: Course completion awards credits
  Background:
    Given the following "custom field categories" exist:
      | name              | component   | area   | itemid |
      | Category for test | core_course | course | 0      |
    And the following "custom fields" exist:
      | name       | category           | type    | shortname | description | configdata            |
      | CreditsF 1 | Category for test  | mutrain | credits1  | tf1         |                       |
    And the following "courses" exist:
      | fullname | shortname | format | enablecompletion | showcompletionconditions | customfield_credits1  |
      | Course 1 | C1        | topics | 1                | 1                        | 4                     |
      | Course 2 | C2        | topics | 1                | 1                        | 8                     |
    And the following "tool_mutrain > frameworks" exist:
      | name        | publicaccess | requiredcredits  | restrictcontext | fields   |
      | Framework 1 | 1            | 5                | 0               | credits1 |
    And the following "activity" exists:
      | activity       | page                     |
      | course         | C1                       |
      | idnumber       | page1                    |
      | name           | Sample page              |
      | intro          | A lesson learned in life |
      | completion     | 2                        |
      | completionview | 1                        |
    And the following "activity" exists:
      | activity       | page                     |
      | course         | C2                       |
      | idnumber       | page2                    |
      | name           | Sample page              |
      | intro          | A lesson learned in life |
      | completion     | 2                        |
      | completionview | 1                        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | viewer1  | Viewer    | 1        | viewer1@example.com  |
      | student1 | Student   | 1        | student1@example.com |
    And the following "roles" exist:
      | name            | shortname |
      | Credits viewer  | cviewer   |
    And the following "permission overrides" exist:
      | capability                   | permission | role     | contextlevel | reference |
      | tool/mutrain:viewusercredits | Allow      | cviewer  | System       |           |
      | moodle/user:viewalldetails   | Allow      | cviewer  | System       |           |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | viewer1   | cviewer       | System       |           |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
      | student1 | C2     | student |

    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Course completion" in current page administration
    And I set the field "id_overall_aggregation" to "2"
    And I click on "Condition: Activity completion" "link"
    And I set the field "Page - Sample page" to "1"
    And I press "Save changes"
    And I am on "Course 2" course homepage
    And I navigate to "Course completion" in current page administration
    And I set the field "id_overall_aggregation" to "2"
    And I click on "Condition: Activity completion" "link"
    And I set the field "Page - Sample page" to "1"
    And I press "Save changes"
    And I log out

  Scenario: User obtains credits for course completion
    Given I log in as "student1"

    When I am on the "student1" "user > profile" page
    And I follow "My credits"
    Then I should see "No credits were obtained yet"

    When I am on "Course 1" course homepage
    And I follow "Sample page"
    And I am on the "tool_muprog > My programs" page
    And I am on the "student1" "user > profile" page
    And I follow "My credits"
    Then the following should exist in the "reportbuilder-table" table:
      | Framework name | Restricted to category | Only obtained after | Required credits | Current credits |
      | Framework 1    | No                     | Not set             | 5                | 4               |

    When I click on "4" "link" in the "Framework 1" "table_row"
    Then the following should exist in the "reportbuilder-table" table:
      | Type              | Name     | Credits |
      | Course completion | Course 1 | 4       |

    And I log out

    When I am on the "student1" "user > profile" page logged in as "viewer1"
    And I follow "Credits"
    Then the following should exist in the "reportbuilder-table" table:
      | Framework name | Restricted to category | Only obtained after | Required credits | Current credits |
      | Framework 1    | No                     | Not set             | 5                | 4               |

    When I click on "4" "link" in the "Framework 1" "table_row"
    Then the following should exist in the "reportbuilder-table" table:
      | Type              | Name     | Credits |
      | Course completion | Course 1 | 4       |
