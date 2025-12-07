@mod @mod_mubook @MuTMS @javascript
Feature: Students may navigate chapters in mod_mubook

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

  Scenario: Students may navigate Interactive book chapters
    Given the following "mod_mubook > chapters" exist:
      | mubook      | title          | subchapter | positionafter  |
      | mubook1     | Prvni kapitola | 0          |                |
      | mubook1     | Druha kapitola | 0          |                |
      | mubook1     | Podkapitola 1  | 1          | Druha kapitola |
      | mubook1     | Podkapitola 2  | 1          | Podkapitola 1  |
      | mubook1     | Podkapitola 3  | 1          | Podkapitola 2  |
      | mubook1     | Treti kapitola | 0          |                |
      | mubook1     | Podkapitola X  | 1          | Treti kapitola |
    And the following "mod_mubook > chapter_contents" exist:
      | chapter        | type     | data1                 |
      | Prvni kapitola | markdown | Test jedna text       |
      | Druha kapitola | markdown | Test dva text         |
      | Podkapitola 1  | markdown | Test pod jedna text   |
      | Podkapitola 2  | markdown | Test pod dva text     |
      | Podkapitola 3  | markdown | Test pod tri text     |
      | Treti kapitola | markdown | Test tri text         |
      | Podkapitola X  | markdown | Test pod X text       |

    When I am on the "Test book 1" "mubook activity" page logged in as "student1"
    Then I should see "This is an intro"
    And I should see "Prvni kapitola"
    And "Podkapitola 1" "text" should appear after "Druha kapitola" "text"
    And "Podkapitola 2" "text" should appear after "Podkapitola 1" "text"
    And "Podkapitola 3" "text" should appear after "Podkapitola 2" "text"
    And "Treti kapitola" "text" should appear after "Podkapitola 3" "text"
    And "Podkapitola X" "text" should appear after "Treti kapitola" "text"

    When I follow "First chapter Prvni kapitola"
    Then I should see "Test jedna text"

    When I follow "Next chapter Druha kapitola"
    Then I should see "Test dva text"

    When I follow "Next chapter Podkapitola 1"
    Then I should see "Test pod jedna text"

    When I follow "Next chapter Podkapitola 2"
    Then I should see "Test pod dva text"

    When I follow "Next chapter Podkapitola 3"
    Then I should see "Test pod tri text"

    When I follow "Next chapter Treti kapitola"
    Then I should see "Test tri text"

    When I follow "Next chapter Podkapitola X"
    Then I should see "Test pod X text"

    When I click on "Table of contents" "link" in the ".mubook-page" "css_element"
    Then I should see "This is an intro"
    And I should see "Prvni kapitola"
    And "Podkapitola 1" "text" should appear after "Druha kapitola" "text"
    And "Podkapitola 2" "text" should appear after "Podkapitola 1" "text"
    And "Podkapitola 3" "text" should appear after "Podkapitola 2" "text"
    And "Treti kapitola" "text" should appear after "Podkapitola 3" "text"
    And "Podkapitola X" "text" should appear after "Treti kapitola" "text"

    When I follow "Druha kapitola"
    And I should not see "This is an intro"
    And I click on "Book actions" "link_or_button"
    And I click on "Table of contents" "link" in the ".dropdown-menu.show" "css_element"
    Then I should see "This is an intro"
    And I should see "Prvni kapitola"
    And "Podkapitola 1" "text" should appear after "Druha kapitola" "text"
    And "Podkapitola 2" "text" should appear after "Podkapitola 1" "text"
    And "Podkapitola 3" "text" should appear after "Podkapitola 2" "text"
    And "Treti kapitola" "text" should appear after "Podkapitola 3" "text"
    And "Podkapitola X" "text" should appear after "Treti kapitola" "text"

    When I follow "Podkapitola 1"
    And I should not see "This is an intro"
    And I click on "Book actions" "link_or_button"
    And I click on "Table of contents" "link" in the ".dropdown-menu.show" "css_element"
    Then I should see "This is an intro"
    And I should see "Prvni kapitola"
    And "Podkapitola 1" "text" should appear after "Druha kapitola" "text"
    And "Podkapitola 2" "text" should appear after "Podkapitola 1" "text"
    And "Podkapitola 3" "text" should appear after "Podkapitola 2" "text"
    And "Treti kapitola" "text" should appear after "Podkapitola 3" "text"
    And "Podkapitola X" "text" should appear after "Treti kapitola" "text"

  Scenario: Students may see all chapters in Interactive book
    Given the following "mod_mubook > chapters" exist:
      | mubook      | title          | subchapter | positionafter  |
      | mubook1     | Prvni kapitola | 0          |                |
      | mubook1     | Druha kapitola | 0          |                |
      | mubook1     | Podkapitola 1  | 1          | Druha kapitola |
      | mubook1     | Podkapitola 2  | 1          | Podkapitola 1  |
      | mubook1     | Podkapitola 3  | 1          | Podkapitola 2  |
      | mubook1     | Treti kapitola | 0          |                |
      | mubook1     | Podkapitola X  | 1          | Treti kapitola |
    And the following "mod_mubook > chapter_contents" exist:
      | chapter        | type     | data1                 |
      | Prvni kapitola | markdown | Test jedna text       |
      | Druha kapitola | markdown | Test dva text         |
      | Podkapitola 1  | markdown | Test pod jedna text   |
      | Podkapitola 2  | markdown | Test pod dva text     |
      | Podkapitola 3  | markdown | Test pod tri text     |
      | Treti kapitola | markdown | Test tri text         |
      | Podkapitola X  | markdown | Test pod X text       |
    And I am on the "Test book 1" "mubook activity" page logged in as "student1"

    When I click on "Book actions" "link_or_button"
    And I click on "View all chapters" "link" in the ".dropdown-menu.show" "css_element"
    Then I should see "This is an intro"
    And I should see "Prvni kapitola"
    And "Podkapitola 1" "text" should appear after "Druha kapitola" "text"
    And "Podkapitola 2" "text" should appear after "Podkapitola 1" "text"
    And "Podkapitola 3" "text" should appear after "Podkapitola 2" "text"
    And "Treti kapitola" "text" should appear after "Podkapitola 3" "text"
    And "Podkapitola X" "text" should appear after "Treti kapitola" "text"
    And I should see "Test jedna text"
    And I should see "Test dva text"
    And I should see "Test pod jedna text"
    And I should see "Test pod dva text"
    And I should see "Test pod tri text"
    And I should see "Test tri text"
    And I should see "Test pod X text"

    When I click on "Book actions" "link_or_button"
    And I click on "Table of contents" "link" in the ".dropdown-menu.show" "css_element"
    Then I should see "This is an intro"
    And I should see "Prvni kapitola"
    And "Podkapitola 1" "text" should appear after "Druha kapitola" "text"
    And "Podkapitola 2" "text" should appear after "Podkapitola 1" "text"
    And "Podkapitola 3" "text" should appear after "Podkapitola 2" "text"
    And "Treti kapitola" "text" should appear after "Podkapitola 3" "text"
    And "Podkapitola X" "text" should appear after "Treti kapitola" "text"
    And I should not see "Test jedna text"

    When I follow "Druha kapitola"
    And I click on "Book actions" "link_or_button"
    And I click on "View all chapters" "link" in the ".dropdown-menu.show" "css_element"
    Then I should see "This is an intro"
    And I should see "Prvni kapitola"
    And I should see "Test jedna text"

  Scenario: Students may be forbidden to see all chapters in Interactive book
    Given the following "mod_mubook > chapters" exist:
      | mubook      | title          | subchapter | positionafter  |
      | mubook1     | Prvni kapitola | 0          |                |
      | mubook1     | Druha kapitola | 0          |                |
      | mubook1     | Podkapitola 1  | 1          | Druha kapitola |
      | mubook1     | Podkapitola 2  | 1          | Podkapitola 1  |
      | mubook1     | Podkapitola 3  | 1          | Podkapitola 2  |
      | mubook1     | Treti kapitola | 0          |                |
      | mubook1     | Podkapitola X  | 1          | Treti kapitola |
    And the following "mod_mubook > chapter_contents" exist:
      | chapter        | type     | data1                 |
      | Prvni kapitola | markdown | Test jedna text       |
      | Druha kapitola | markdown | Test dva text         |
      | Podkapitola 1  | markdown | Test pod jedna text   |
      | Podkapitola 2  | markdown | Test pod dva text     |
      | Podkapitola 3  | markdown | Test pod tri text     |
      | Treti kapitola | markdown | Test tri text         |
      | Podkapitola X  | markdown | Test pod X text       |
    And the following "permission overrides" exist:
      | capability         | permission | role    | contextlevel | reference |
      | mod/mubook:viewall | Prohibit   | student | Course       | C1        |
    And I am on the "Test book 1" "mubook activity" page logged in as "student1"

    And I follow "Druha kapitola"
    When I click on "Book actions" "link_or_button"
    Then I should not see "View all chapters" in the ".dropdown-menu.show" "css_element"
