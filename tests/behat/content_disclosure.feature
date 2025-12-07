@mod @mod_mubook @MuTMS @javascript
Feature: Editors may manage Show solution button in mod_mubook

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "activities" exist:
      | activity | name        | intro             | course | idnumber |
      | mubook   | Test book 1 | This is an intro  | C1     | mubook1  |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  Scenario: Editing teachers may manage Show solution button in chapters of Interactive books
    Given the following "mod_mubook > chapters" exist:
      | mubook      | title          | subchapter | positionafter  |
      | mubook1     | Prvni kapitola | 0          |                |
    And the following "mod_mubook > chapter_contents" exist:
      | chapter        | type     | data1              |
      | Prvni kapitola | html     | <h3>Test heading 1</h3> |
    And I am on the "Test book 1" "mubook activity" page logged in as "teacher1"
    And I follow "Prvni kapitola"
    And I turn editing mode on

    When I click on "Chapter actions" "link_or_button"
    And I click on "Add content" "link" in the ".dropdown-menu.show" "css_element"
    And I set the following fields to these values:
      | Add content | Show solution button |
    And I click on "Continue" "button" in the ".modal-dialog" "css_element"
    And I press "Add content"
    Then I should see "Next element does not exist, disclosure will be disabled."

    When I click on "Chapter actions" "link_or_button"
    And I click on "Add content" "link" in the ".dropdown-menu.show" "css_element"
    And I set the following fields to these values:
      | Add content | HTML text |
    And I click on "Continue" "button" in the ".modal-dialog" "css_element"
    And I set the following fields to these values:
      | Text        | Sample solution text |
    And I press "Add content"
    Then I should see "Test heading 1"
    And I should see "Sample solution text"

    When I press "Hide solution"
    Then I should see "Test heading 1"
    And I should not see "Sample solution text"

    When I press "Show solution"
    Then I should see "Test heading 1"
    And I should see "Sample solution text"

    When I turn editing mode off
    Then I should see "Test heading 1"
    And I should not see "Sample solution text"

    When I press "Show solution"
    Then I should see "Test heading 1"
    And I should see "Sample solution text"

    When I press "Hide solution"
    Then I should see "Test heading 1"
    And I should not see "Sample solution text"
    And I log out

    When I am on the "Test book 1" "mubook activity" page logged in as "student1"
    And I follow "Prvni kapitola"
    Then I should see "Test heading 1"
    And I should not see "Sample solution text"

    When I press "Show solution"
    Then I should see "Test heading 1"
    And I should see "Sample solution text"

    When I press "Hide solution"
    Then I should see "Test heading 1"
    And I should not see "Sample solution text"
