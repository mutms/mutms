@mod @mod_mubook @MuTMS @javascript
Feature: Admins may manage Unsafe raw HTML content in mod_mubook

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

  Scenario: Admins may edit Unsafe raw HTML content in chapters of Interactive books
    Given I am on the "Test book 1" "mubook activity" page logged in as "admin"
    And I turn editing mode on

    When I press "Add chapter"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Chapter title | Prvni kapitola  |
      | Add content   | Unsafe raw HTML |
    And I click on "Add chapter" "button" in the ".modal-dialog" "css_element"
    And I set the following fields to these values:
      | Unsafe raw HTML | Sample text one |
    And I press "Add content"
    Then I should not see "This is an intro"
    And I should see "Prvni kapitola"
    And I should see "Sample text one"

    When I click on "Content 1 actions" "link_or_button"
    And I click on "Update content" "link" in the ".dropdown-menu.show" "css_element"
    And the following fields match these values:
      | Unsafe raw HTML           | Sample text one |
    And I set the following fields to these values:
      | Unsafe raw HTML           | Sample text ONE |
    And I press "Update content"
    Then I should not see "This is an intro"
    And I should see "Prvni kapitola"
    And I should see "Sample text ONE"

    When I click on "Content 1 actions" "link_or_button"
    And I click on "Update content" "link" in the ".dropdown-menu.show" "css_element"
    And the following fields match these values:
      | Unsafe raw HTML | Sample text ONE |
      | Position        | 1               |
    And I set the following fields to these values:
      | Unsafe raw HTML | Sample text one |
    And I press "Update content"
    Then I should not see "This is an intro"
    And I should see "Prvni kapitola"
    And I should see "Sample text one"

    When I click on "Chapter actions" "link_or_button"
    And I click on "Add content" "link" in the ".dropdown-menu.show" "css_element"
    And I set the following fields to these values:
      | Add content | Unsafe raw HTML |
    And I click on "Continue" "button" in the ".modal-dialog" "css_element"
    And I set the following fields to these values:
      | Unsafe raw HTML | Sample text two |
    And I press "Add content"
    Then I should not see "This is an intro"
    And I should see "Prvni kapitola"
    And I should see "Sample text one"
    And I should see "Sample text two"

    When I click on "Chapter actions" "link_or_button"
    And I click on "Add content" "link" in the ".dropdown-menu.show" "css_element"
    And I set the following fields to these values:
      | Add content | Unsafe raw HTML |
    And I click on "Continue" "button" in the ".modal-dialog" "css_element"
    And I set the following fields to these values:
      | Unsafe raw HTML | Sample text three |
      | Position        | 2                 |
    And I press "Add content"
    Then I should not see "This is an intro"
    And "Sample text three" "text" should appear after "Sample text one" "text"
    And "Sample text two" "text" should appear after "Sample text three" "text"

    When I click on "Content 2 actions" "link_or_button"
    And I click on "Delete content" "link" in the ".dropdown-menu.show" "css_element"
    And I click on "Delete content" "button" in the ".modal-dialog" "css_element"
    Then I should not see "This is an intro"
    And I should not see "Sample text three"
    And "Sample text two" "text" should appear after "Sample text one" "text"
