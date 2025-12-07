@mod @mod_mubook @MuTMS @javascript
Feature: Editors may manage HTML content in mod_mubook

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

  Scenario: Editing teachers may edit HTML content in chapters of Interactive books
    Given I am on the "Test book 1" "mubook activity" page logged in as "teacher1"
    And I turn editing mode on

    When I press "Add chapter"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Chapter title | Prvni kapitola |
      | Add content   | HTML text      |
    And I click on "Add chapter" "button" in the ".modal-dialog" "css_element"
    And I set the following fields to these values:
      | Text | Sample text one |
    And I press "Add content"
    Then I should not see "This is an intro"
    And I should see "Prvni kapitola"
    And I should see "Sample text one"

    When I click on "Content 1 actions" "link_or_button"
    And I click on "Update content" "link" in the ".dropdown-menu.show" "css_element"
    And the following fields match these values:
      | Text           | Sample text one |
      | Hidden content | 0               |
    And I set the following fields to these values:
      | Text           | Sample text ONE |
      | Hidden content | 1               |
    And I press "Update content"
    Then I should not see "This is an intro"
    And I should see "Prvni kapitola"
    And I should see "Sample text ONE"
    And I should see "Sample text ONE" in the ".mubook-hidden" "css_element"

    When I click on "Content 1 actions" "link_or_button"
    And I click on "Update content" "link" in the ".dropdown-menu.show" "css_element"
    And the following fields match these values:
      | Text           | Sample text ONE |
      | Hidden content | 1               |
      | Position       | 1               |
    And I set the following fields to these values:
      | Text           | Sample text one |
      | Hidden content | 0               |
    And I press "Update content"
    Then I should not see "This is an intro"
    And I should see "Prvni kapitola"
    And I should see "Sample text one"

    When I click on "Chapter actions" "link_or_button"
    And I click on "Add content" "link" in the ".dropdown-menu.show" "css_element"
    And the following fields match these values:
      | Add content | HTML text |
    And I click on "Continue" "button" in the ".modal-dialog" "css_element"
    And the following fields match these values:
      | Position       | 2               |
      | Hidden content | 0               |
    And I set the following fields to these values:
      | Text           | Sample hidden two |
      | Hidden content | 1                 |
    And I press "Add content"
    Then I should not see "This is an intro"
    And I should see "Prvni kapitola"
    And I should see "Sample text one"
    And I should see "Sample hidden two" in the ".mubook-hidden" "css_element"

    When I click on "Chapter actions" "link_or_button"
    And I click on "Add content" "link" in the ".dropdown-menu.show" "css_element"
    And the following fields match these values:
      | Add content | HTML text |
    And I click on "Continue" "button" in the ".modal-dialog" "css_element"
    And I set the following fields to these values:
      | Text           | Sample text three |
      | Position       | 2                 |
    And I press "Add content"
    Then I should not see "This is an intro"
    And "Sample text three" "text" should appear after "Sample text one" "text"
    And "Sample hidden two" "text" should appear after "Sample text three" "text"

    When I click on "Content 2 actions" "link_or_button"
    And I click on "Delete content" "link" in the ".dropdown-menu.show" "css_element"
    And I click on "Delete content" "button" in the ".modal-dialog" "css_element"
    Then I should not see "This is an intro"
    And I should not see "Sample text three"
    And "Sample hidden two" "text" should appear after "Sample text one" "text"
    And I log out

    When I am on the "Test book 1" "mubook activity" page logged in as "student1"
    And I follow "Prvni kapitola"
    Then I should see "Sample text one"
    And I should not see "Sample hidden two"

  Scenario: Editing teachers may edit HTML content in sub-chapters of Interactive books
    Given the following "mod_mubook > chapters" exist:
      | mubook      | title          | subchapter | positionafter  |
      | mubook1     | Prvni kapitola | 0          |                |
    And the following "mod_mubook > chapter_contents" exist:
      | chapter        | type     | data1              |
      | Prvni kapitola | html     | <h3>Test heading 1</h3> |
    And I am on the "Test book 1" "mubook activity" page logged in as "teacher1"
    And I turn editing mode on
    And I follow "Prvni kapitola"

    When I press "Add sub-chapter"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Sub-chapter title | Podkapitola |
      | Add content       | HTML text      |
    And I click on "Add sub-chapter" "button" in the ".modal-dialog" "css_element"
    And I set the following fields to these values:
      | Text | Sample text one |
    And I press "Add content"
    And I should see "Podkapitola"
    And I should see "Sample text one"

    When I click on "Content 1 actions" "link_or_button"
    And I click on "Update content" "link" in the ".dropdown-menu.show" "css_element"
    And the following fields match these values:
      | Text           | Sample text one |
    And I set the following fields to these values:
      | Text           | Sample text ONE |
    And I press "Update content"
    Then I should not see "This is an intro"
    And I should see "Podkapitola"
    And I should see "Sample text ONE"

    And I press "Add content"
    And I set the following fields to these values:
      | Add content | HTML text |
    And I click on "Continue" "button" in the ".modal-dialog" "css_element"
    And I set the following fields to these values:
      | Text           | Sample text two |
    And I press "Add content"
    Then I should not see "This is an intro"
    And I should see "Podkapitola"
    And I should see "Sample text ONE"
    And I should see "Sample text two"

    When I click on "Content 1 actions" "link_or_button"
    And I click on "Delete content" "link" in the ".dropdown-menu.show" "css_element"
    And I click on "Delete content" "button" in the ".modal-dialog" "css_element"
    Then I should not see "This is an intro"
    And I should not see "Sample text ONE"

  Scenario: Students may be allowed to edit HTML content in Interactive book
    Given the following "permission overrides" exist:
      | capability             | permission | role    | contextlevel | reference |
      | mod/mubook:editcontent | Allow      | student | Course       | C1        |
    And the following "mod_mubook > chapters" exist:
      | mubook      | title          | subchapter | positionafter  |
      | mubook1     | Prvni kapitola | 0          |                |
    And I am on the "Test book 1" "mubook activity" page logged in as "student1"
    And I turn editing mode on
    And I follow "Prvni kapitola"

    When I click on "Chapter actions" "link_or_button"
    And I click on "Add content" "link" in the ".dropdown-menu.show" "css_element"
    And the following fields match these values:
      | Add content | HTML text |
    And I click on "Continue" "button" in the ".modal-dialog" "css_element"
    And I set the following fields to these values:
      | Text           | Sample text one |
    And I press "Add content"
    Then I should not see "This is an intro"
    And I should see "Prvni kapitola"
    And I should see "Sample text one"

    When I click on "Content 1 actions" "link_or_button"
    And I click on "Update content" "link" in the ".dropdown-menu.show" "css_element"
    And I set the following fields to these values:
      | Text           | Sample text ONE |
    And I press "Update content"
    Then I should not see "This is an intro"
    And I should see "Prvni kapitola"
    And I should see "Sample text ONE"

    When I click on "Content 1 actions" "link_or_button"
    And I click on "Delete content" "link" in the ".dropdown-menu.show" "css_element"
    And I click on "Delete content" "button" in the ".modal-dialog" "css_element"
    Then I should not see "This is an intro"
    And I should not see "Sample text ONE"
