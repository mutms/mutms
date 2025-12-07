@mod @mod_mubook @MuTMS @javascript
Feature: Editors may manage chapters in mod_mubook

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

  Scenario: Editing teachers may create Interactive book chapters without content from TOC
    Given I am on the "Test book 1" "mubook activity" page logged in as "teacher1"
    And I should see "This is an intro"

    And I should see "Turn on edit mode to create book chapters"
    And I should not see "No content has been added to this book yet."
    And I should not see "Add chapter"
    And edit mode should be available on the current page

    When I turn editing mode on
    Then I should see "No content has been added to this book yet."
    And I should not see "Turn on edit mode to create book chapters"
    And I should see "Add chapter"

    When I press "Add chapter"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Chapter title | Treti kapitola |
      | Add content   | None           |
    And I click on "Add chapter" "button" in the ".modal-dialog" "css_element"
    Then I should see "This is an intro"
    And I should see "Treti kapitola"

    When I press "Add chapter"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Chapter position | First chapter   |
      | Chapter title    | Prvni kapitola  |
      | Add content      | HTML text       |
    And I click on "Add chapter" "button" in the ".modal-dialog" "css_element"
    And I press "Cancel"
    Then I should see "This is an intro"
    And I should see "Prvni kapitola"
    And I should see "Treti kapitola"
    And "Treti kapitola" "text" should appear after "Prvni kapitola" "text"

    When I press "Add chapter"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Chapter position | After 1 Prvni kapitola |
      | Chapter title    | Druha kapitola         |
      | Add content      | None                   |
    And I click on "Add chapter" "button" in the ".modal-dialog" "css_element"
    Then I should see "This is an intro"
    And I should see "Prvni kapitola"
    And I should see "Druha kapitola"
    And I should see "Treti kapitola"
    And "Druha kapitola" "text" should appear after "Prvni kapitola" "text"
    And "Treti kapitola" "text" should appear after "Druha kapitola" "text"

    When I press "Add chapter"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Chapter title    | Pata kapitola          |
      | Add content      | None                   |
    And I click on "Add chapter" "button" in the ".modal-dialog" "css_element"
    Then I should see "This is an intro"
    And I should see "Prvni kapitola"
    And I should see "Druha kapitola"
    And I should see "Treti kapitola"
    And I should see "Pata kapitola"
    And "Druha kapitola" "text" should appear after "Prvni kapitola" "text"
    And "Treti kapitola" "text" should appear after "Druha kapitola" "text"
    And "Pata kapitola" "text" should appear after "Treti kapitola" "text"

    When I click on "Chapter actions: Treti kapitola" "link_or_button"
    And I click on "Add chapter" "link" in the ".dropdown-menu.show" "css_element"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Chapter title    | Ctvrta kapitola        |
      | Add content      | None                   |
    And I click on "Add chapter" "button" in the ".modal-dialog" "css_element"
    Then I should see "This is an intro"
    And I should see "Prvni kapitola"
    And I should see "Druha kapitola"
    And I should see "Treti kapitola"
    And I should see "Ctvrta kapitola"
    And I should see "Pata kapitola"
    And "Druha kapitola" "text" should appear after "Prvni kapitola" "text"
    And "Treti kapitola" "text" should appear after "Druha kapitola" "text"
    And "Ctvrta kapitola" "text" should appear after "Treti kapitola" "text"
    And "Pata kapitola" "text" should appear after "Ctvrta kapitola" "text"

    When I click on "Chapter actions: Druha kapitola" "link_or_button"
    And I click on "Add sub-chapter" "link" in the ".dropdown-menu.show" "css_element"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Sub-chapter title | Podkapitola 2         |
      | Add content       | None                  |
    And I click on "Add sub-chapter" "button" in the ".modal-dialog" "css_element"
    Then I should see "This is an intro"
    And I should see "Prvni kapitola"
    And I should see "Druha kapitola"
    And I should see "Podkapitola 2"
    And I should see "Treti kapitola"
    And I should see "Ctvrta kapitola"
    And I should see "Pata kapitola"
    And "Druha kapitola" "text" should appear after "Prvni kapitola" "text"
    And "Podkapitola 2" "text" should appear after "Druha kapitola" "text"
    And "Treti kapitola" "text" should appear after "Podkapitola 2" "text"
    And "Ctvrta kapitola" "text" should appear after "Treti kapitola" "text"
    And "Pata kapitola" "text" should appear after "Ctvrta kapitola" "text"

    When I click on "Chapter actions: Druha kapitola" "link_or_button"
    And I click on "Add sub-chapter" "link" in the ".dropdown-menu.show" "css_element"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Sub-chapter position | First in 2 Druha kapitola |
      | Sub-chapter title    | Podkapitola 1             |
      | Add content          | None                      |
    And I click on "Add sub-chapter" "button" in the ".modal-dialog" "css_element"
    Then I should see "This is an intro"
    And I should see "Prvni kapitola"
    And I should see "Druha kapitola"
    And I should see "Podkapitola 1"
    And I should see "Podkapitola 2"
    And I should see "Treti kapitola"
    And I should see "Ctvrta kapitola"
    And I should see "Pata kapitola"
    And "Druha kapitola" "text" should appear after "Prvni kapitola" "text"
    And "Podkapitola 1" "text" should appear after "Druha kapitola" "text"
    And "Podkapitola 2" "text" should appear after "Podkapitola 1" "text"
    And "Treti kapitola" "text" should appear after "Podkapitola 2" "text"
    And "Ctvrta kapitola" "text" should appear after "Treti kapitola" "text"
    And "Pata kapitola" "text" should appear after "Ctvrta kapitola" "text"

    When I click on "Chapter actions: Druha kapitola" "link_or_button"
    And I click on "Add sub-chapter" "link" in the ".dropdown-menu.show" "css_element"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Sub-chapter title    | Podkapitola 4             |
      | Add content          | HTML text                 |
    And I click on "Add sub-chapter" "button" in the ".modal-dialog" "css_element"
    And I press "Cancel"
    Then I should see "This is an intro"
    And I should see "Prvni kapitola"
    And I should see "Druha kapitola"
    And I should see "Podkapitola 1"
    And I should see "Podkapitola 2"
    And I should see "Podkapitola 4"
    And I should see "Treti kapitola"
    And I should see "Ctvrta kapitola"
    And I should see "Pata kapitola"
    And "Druha kapitola" "text" should appear after "Prvni kapitola" "text"
    And "Podkapitola 1" "text" should appear after "Druha kapitola" "text"
    And "Podkapitola 2" "text" should appear after "Podkapitola 1" "text"
    And "Podkapitola 4" "text" should appear after "Podkapitola 2" "text"
    And "Treti kapitola" "text" should appear after "Podkapitola 4" "text"
    And "Ctvrta kapitola" "text" should appear after "Treti kapitola" "text"
    And "Pata kapitola" "text" should appear after "Ctvrta kapitola" "text"

    When I click on "Chapter actions: Treti kapitola" "link_or_button"
    And I click on "Add sub-chapter" "link" in the ".dropdown-menu.show" "css_element"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Sub-chapter position | After 2.2 Podkapitola 2 |
      | Sub-chapter title    | Podkapitola 3           |
      | Add content          | None                    |
    And I click on "Add sub-chapter" "button" in the ".modal-dialog" "css_element"
    Then I should see "This is an intro"
    And I should see "Prvni kapitola"
    And I should see "Druha kapitola"
    And I should see "Podkapitola 1"
    And I should see "Podkapitola 2"
    And I should see "Podkapitola 3"
    And I should see "Podkapitola 4"
    And I should see "Treti kapitola"
    And I should see "Ctvrta kapitola"
    And I should see "Pata kapitola"
    And "Druha kapitola" "text" should appear after "Prvni kapitola" "text"
    And "Podkapitola 1" "text" should appear after "Druha kapitola" "text"
    And "Podkapitola 2" "text" should appear after "Podkapitola 1" "text"
    And "Podkapitola 3" "text" should appear after "Podkapitola 2" "text"
    And "Podkapitola 4" "text" should appear after "Podkapitola 3" "text"
    And "Treti kapitola" "text" should appear after "Podkapitola 4" "text"
    And "Ctvrta kapitola" "text" should appear after "Treti kapitola" "text"
    And "Pata kapitola" "text" should appear after "Ctvrta kapitola" "text"

  Scenario: Editing teachers may create Interactive book chapters without content from one chapter page
    Given I am on the "Test book 1" "mubook activity" page logged in as "teacher1"
    And I turn editing mode on
    And I press "Add chapter"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Chapter title | Prvni kapitola |
      | Add content   | None           |
    And I click on "Add chapter" "button" in the ".modal-dialog" "css_element"
    And I press "Add chapter"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Chapter title | Druha kapitola |
      | Add content   | None           |
    And I click on "Add chapter" "button" in the ".modal-dialog" "css_element"

    When I follow "Prvni kapitola"
    And I should see "Add sub-chapter"

    When I press "Add sub-chapter"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Sub-chapter title    | Podkapitola 2 |
      | Add content          | None          |
    And I click on "Add sub-chapter" "button" in the ".modal-dialog" "css_element"
    Then I should see "Podkapitola 2"

    When I press "Add sub-chapter"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Sub-chapter position | First in 1 Prvni kapitola |
      | Sub-chapter title    | Podkapitola 1             |
      | Add content          | HTML text                 |
    And I click on "Add sub-chapter" "button" in the ".modal-dialog" "css_element"
    And I press "Cancel"
    Then I should see "Podkapitola 1"
    And "Podkapitola 2" "text" should appear after "Podkapitola 1" "text"

    When I press "Add sub-chapter"
    And the following fields in the ".modal-dialog" "css_element" match these values:
      | Sub-chapter position | After 1.2 Podkapitola 2 |
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Sub-chapter title    | Podkapitola 3  |
      | Add content          | None           |
    And I click on "Add sub-chapter" "button" in the ".modal-dialog" "css_element"
    Then I should see "Podkapitola 1"
    And "Podkapitola 2" "text" should appear after "Podkapitola 1" "text"
    And "Podkapitola 3" "text" should appear after "Podkapitola 2" "text"

  Scenario: Editing teachers may update Interactive book chapters
    Given the following "mod_mubook > chapters" exist:
      | mubook      | title          | subchapter | positionafter  |
      | mubook1     | Prvni kapitola | 0          |                |
      | mubook1     | Druha kapitola | 0          |                |
      | mubook1     | Podkapitola 1  | 1          | Druha kapitola |
      | mubook1     | Podkapitola 2  | 1          | Podkapitola 1  |
      | mubook1     | Treti kapitola | 0          |                |
    And I am on the "Test book 1" "mubook activity" page logged in as "teacher1"
    And I turn editing mode on

    When I click on "Chapter actions: Prvni kapitola" "link_or_button"
    And I click on "Update chapter" "link" in the ".dropdown-menu.show" "css_element"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Chapter title    | Erste Kapitel         |
    And I click on "Update chapter" "button" in the ".modal-dialog" "css_element"
    Then I should see "This is an intro"
    And I should see "Erste Kapitel"
    And "Druha kapitola" "text" should appear after "Erste Kapitel" "text"
    And "Podkapitola 1" "text" should appear after "Druha kapitola" "text"
    And "Podkapitola 2" "text" should appear after "Podkapitola 1" "text"
    And "Treti kapitola" "text" should appear after "Podkapitola 2" "text"

    When I click on "Sub-chapter actions: Podkapitola 2" "link_or_button"
    And I click on "Update sub-chapter" "link" in the ".dropdown-menu.show" "css_element"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Sub-chapter title    | Unterkapitel 2         |
    And I click on "Update sub-chapter" "button" in the ".modal-dialog" "css_element"
    Then I should see "This is an intro"
    And I should see "Erste Kapitel"
    And "Druha kapitola" "text" should appear after "Erste Kapitel" "text"
    And "Podkapitola 1" "text" should appear after "Druha kapitola" "text"
    And "Unterkapitel 2" "text" should appear after "Podkapitola 1" "text"
    And "Treti kapitola" "text" should appear after "Unterkapitel 2" "text"

    And I follow "Druha kapitola"

    When I click on "Sub-chapter actions: Podkapitola 1" "link_or_button"
    And I click on "Update sub-chapter" "link" in the ".dropdown-menu.show" "css_element"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Sub-chapter title    | Unterkapitel 1         |
    And I click on "Update sub-chapter" "button" in the ".modal-dialog" "css_element"
    Then I should not see "This is an intro"
    And I should see "Druha kapitola"
    And "Unterkapitel 1" "text" should appear after "Druha kapitola" "text"
    And "Unterkapitel 2" "text" should appear after "Unterkapitel 1" "text"

    When I click on "Chapter actions" "link_or_button"
    And I click on "Update chapter" "link" in the ".dropdown-menu.show" "css_element"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Chapter title    | Zweite Kapitel        |
    And I click on "Update chapter" "button" in the ".modal-dialog" "css_element"
    Then I should not see "This is an intro"
    And I should see "Zweite Kapitel"
    And "Unterkapitel 1" "text" should appear after "Zweite Kapitel" "text"
    And "Unterkapitel 2" "text" should appear after "Unterkapitel 1" "text"

  Scenario: Editing teachers may delete Interactive book chapters from TOC
    Given the following "mod_mubook > chapters" exist:
      | mubook      | title          | subchapter | positionafter  |
      | mubook1     | Prvni kapitola | 0          |                |
      | mubook1     | Druha kapitola | 0          |                |
      | mubook1     | Podkapitola 1  | 1          | Druha kapitola |
      | mubook1     | Podkapitola 2  | 1          | Podkapitola 1  |
      | mubook1     | Podkapitola 3  | 1          | Podkapitola 2  |
      | mubook1     | Treti kapitola | 0          |                |
      | mubook1     | Podkapitola X  | 1          | Treti kapitola  |
    And I am on the "Test book 1" "mubook activity" page logged in as "teacher1"
    And I turn editing mode on

    When I click on "Chapter actions: Prvni kapitola" "link_or_button"
    And I click on "Delete chapter" "link" in the ".dropdown-menu.show" "css_element"
    And I click on "Delete chapter" "button" in the ".modal-dialog" "css_element"
    Then I should see "This is an intro"
    And I should not see "Prvni kapitola"
    And "Podkapitola 1" "text" should appear after "Druha kapitola" "text"
    And "Podkapitola 2" "text" should appear after "Podkapitola 1" "text"
    And "Podkapitola 3" "text" should appear after "Podkapitola 2" "text"
    And "Treti kapitola" "text" should appear after "Podkapitola 3" "text"
    And "Podkapitola X" "text" should appear after "Treti kapitola" "text"

    When I click on "Sub-chapter actions: Podkapitola 2" "link_or_button"
    And I click on "Delete sub-chapter" "link" in the ".dropdown-menu.show" "css_element"
    And I click on "Delete sub-chapter" "button" in the ".modal-dialog" "css_element"
    Then I should see "This is an intro"
    And I should not see "Prvni kapitola"
    And I should not see "Podkapitola 2"
    And "Podkapitola 1" "text" should appear after "Druha kapitola" "text"
    And "Podkapitola 3" "text" should appear after "Podkapitola 1" "text"
    And "Treti kapitola" "text" should appear after "Podkapitola 3" "text"
    And "Podkapitola X" "text" should appear after "Treti kapitola" "text"

    When I click on "Chapter actions: Druha kapitola" "link_or_button"
    And I click on "Delete chapter" "link" in the ".dropdown-menu.show" "css_element"
    And I click on "Delete chapter" "button" in the ".modal-dialog" "css_element"
    And I should see "Required"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Confirm deletion of 2 sub-chapters | 1 |
    And I click on "Delete chapter" "button" in the ".modal-dialog" "css_element"
    Then I should see "This is an intro"
    And I should not see "Prvni kapitola"
    And I should not see "Druha kapitola"
    And I should not see "Podkapitola 1"
    And I should not see "Podkapitola 2"
    And I should not see "Podkapitola 3"
    And "Podkapitola X" "text" should appear after "Treti kapitola" "text"

  Scenario: Editing teachers may delete Interactive book chapters from chapter page
    Given the following "mod_mubook > chapters" exist:
      | mubook      | title          | subchapter | positionafter  |
      | mubook1     | Prvni kapitola | 0          |                |
      | mubook1     | Druha kapitola | 0          |                |
      | mubook1     | Podkapitola 1  | 1          | Druha kapitola |
      | mubook1     | Podkapitola 2  | 1          | Podkapitola 1  |
      | mubook1     | Podkapitola 3  | 1          | Podkapitola 2  |
      | mubook1     | Treti kapitola | 0          |                |
      | mubook1     | Podkapitola X  | 1          | Treti kapitola  |
    And I am on the "Test book 1" "mubook activity" page logged in as "teacher1"
    And I turn editing mode on

    And I follow "Druha kapitola"
    When I click on "Sub-chapter actions: Podkapitola 2" "link_or_button"
    And I click on "Delete sub-chapter" "link" in the ".dropdown-menu.show" "css_element"
    And I click on "Delete sub-chapter" "button" in the ".modal-dialog" "css_element"
    Then I should not see "This is an intro"
    And I should not see "Prvni kapitola"
    And I should not see "Treti kapitola"
    And "Podkapitola 1" "text" should appear after "Druha kapitola" "text"
    And "Podkapitola 3" "text" should appear after "Podkapitola 1" "text"

    When I click on "Chapter actions" "link_or_button"
    And I click on "Delete chapter" "link" in the ".dropdown-menu.show" "css_element"
    And I click on "Delete chapter" "button" in the ".modal-dialog" "css_element"
    And I should see "Required"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Confirm deletion of 2 sub-chapters | 1 |
    And I click on "Delete chapter" "button" in the ".modal-dialog" "css_element"
    Then I should see "This is an intro"
    And I should see "Prvni kapitola"
    And I should not see "Druha kapitola"
    And I should see "Treti kapitola"

    And I follow "Podkapitola X"
    When I click on "Sub-chapter actions" "link_or_button"
    And I click on "Delete sub-chapter" "link" in the ".dropdown-menu.show" "css_element"
    And I click on "Delete sub-chapter" "button" in the ".modal-dialog" "css_element"
    Then I should not see "This is an intro"
    And I should see "Treti kapitola"
    And I should not see "Podkapitola X"

  Scenario: Editing teachers may move Interactive book chapters from TOC
    Given the following "mod_mubook > chapters" exist:
      | mubook      | title          | subchapter | positionafter  |
      | mubook1     | Prvni kapitola | 0          |                |
      | mubook1     | Druha kapitola | 0          |                |
      | mubook1     | Podkapitola 1  | 1          | Druha kapitola |
      | mubook1     | Podkapitola 2  | 1          | Podkapitola 1  |
      | mubook1     | Podkapitola 3  | 1          | Podkapitola 2  |
      | mubook1     | Treti kapitola | 0          |                |
      | mubook1     | Podkapitola X  | 1          | Treti kapitola  |
    And I am on the "Test book 1" "mubook activity" page logged in as "teacher1"
    And I turn editing mode on

    When I click on "Chapter actions: Prvni kapitola" "link_or_button"
    And I click on "Move chapter" "link" in the ".dropdown-menu.show" "css_element"
    And the following fields in the ".modal-dialog" "css_element" match these values:
      | Sub-chapter      | 0         |
      | Chapter position | Choose... |
    And I click on "Move chapter" "button" in the ".modal-dialog" "css_element"
    And I should see "Required"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Chapter position | After 2 Druha kapitola |
    And I click on "Move chapter" "button" in the ".modal-dialog" "css_element"
    Then I should see "This is an intro"
    And I should see "Druha kapitola"
    And "Podkapitola 1" "text" should appear after "Druha kapitola" "text"
    And "Podkapitola 2" "text" should appear after "Podkapitola 1" "text"
    And "Podkapitola 3" "text" should appear after "Podkapitola 2" "text"
    And "Prvni kapitola" "text" should appear after "Podkapitola 3" "text"
    And "Treti kapitola" "text" should appear after "Prvni kapitola" "text"
    And "Podkapitola X" "text" should appear after "Treti kapitola" "text"

    When I click on "Chapter actions: Prvni kapitola" "link_or_button"
    And I click on "Move chapter" "link" in the ".dropdown-menu.show" "css_element"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Chapter position | First chapter |
    And I click on "Move chapter" "button" in the ".modal-dialog" "css_element"
    Then I should see "This is an intro"
    And I should see "Prvni kapitola"
    And "Druha kapitola" "text" should appear after "Prvni kapitola" "text"
    And "Podkapitola 1" "text" should appear after "Druha kapitola" "text"
    And "Podkapitola 2" "text" should appear after "Podkapitola 1" "text"
    And "Podkapitola 3" "text" should appear after "Podkapitola 2" "text"
    And "Treti kapitola" "text" should appear after "Podkapitola 3" "text"
    And "Podkapitola X" "text" should appear after "Treti kapitola" "text"

    When I click on "Chapter actions: Prvni kapitola" "link_or_button"
    And I click on "Move chapter" "link" in the ".dropdown-menu.show" "css_element"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Sub-chapter          | 1                       |
      | Sub-chapter position | After 2.2 Podkapitola 2 |
    And I click on "Move chapter" "button" in the ".modal-dialog" "css_element"
    Then I should see "This is an intro"
    And I should see "Druha kapitola"
    And "Podkapitola 1" "text" should appear after "Druha kapitola" "text"
    And "Podkapitola 2" "text" should appear after "Podkapitola 1" "text"
    And "Prvni kapitola" "text" should appear after "Podkapitola 2" "text"
    And "Podkapitola 3" "text" should appear after "Prvni kapitola" "text"
    And "Treti kapitola" "text" should appear after "Podkapitola 3" "text"
    And "Podkapitola X" "text" should appear after "Treti kapitola" "text"

    When I click on "Sub-chapter actions: Prvni kapitola" "link_or_button"
    And I click on "Move sub-chapter" "link" in the ".dropdown-menu.show" "css_element"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Sub-chapter position | First in Treti kapitola |
    And I click on "Move sub-chapter" "button" in the ".modal-dialog" "css_element"
    Then I should see "This is an intro"
    And I should see "Druha kapitola"
    And "Podkapitola 1" "text" should appear after "Druha kapitola" "text"
    And "Podkapitola 2" "text" should appear after "Podkapitola 1" "text"
    And "Podkapitola 3" "text" should appear after "Podkapitola 2" "text"
    And "Treti kapitola" "text" should appear after "Podkapitola 3" "text"
    And "Prvni kapitola" "text" should appear after "Treti kapitola" "text"
    And "Podkapitola X" "text" should appear after "Prvni kapitola" "text"

    When I click on "Sub-chapter actions: Prvni kapitola" "link_or_button"
    And I click on "Move sub-chapter" "link" in the ".dropdown-menu.show" "css_element"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Sub-chapter      | 0             |
      | Chapter position | First chapter |
    And I click on "Move sub-chapter" "button" in the ".modal-dialog" "css_element"
    Then I should see "This is an intro"
    And I should see "Prvni kapitola"
    And "Druha kapitola" "text" should appear after "Prvni kapitola" "text"
    And "Podkapitola 1" "text" should appear after "Druha kapitola" "text"
    And "Podkapitola 2" "text" should appear after "Podkapitola 1" "text"
    And "Podkapitola 3" "text" should appear after "Podkapitola 2" "text"
    And "Treti kapitola" "text" should appear after "Podkapitola 3" "text"
    And "Podkapitola X" "text" should appear after "Treti kapitola" "text"

    When I click on "Chapter actions: Druha kapitola" "link_or_button"
    And I click on "Move chapter" "link" in the ".dropdown-menu.show" "css_element"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Chapter position | After 3 Treti kapitola |
    And I click on "Move chapter" "button" in the ".modal-dialog" "css_element"
    Then I should see "This is an intro"
    And I should see "Prvni kapitola"
    And "Treti kapitola" "text" should appear after "Prvni kapitola" "text"
    And "Podkapitola X" "text" should appear after "Treti kapitola" "text"
    And "Druha kapitola" "text" should appear after "Podkapitola X" "text"
    And "Podkapitola 1" "text" should appear after "Druha kapitola" "text"
    And "Podkapitola 2" "text" should appear after "Podkapitola 1" "text"
    And "Podkapitola 3" "text" should appear after "Podkapitola 2" "text"

  Scenario: Editing teachers may move Interactive book chapters from chapter page
    Given the following "mod_mubook > chapters" exist:
      | mubook      | title          | subchapter | positionafter  |
      | mubook1     | Prvni kapitola | 0          |                |
      | mubook1     | Druha kapitola | 0          |                |
      | mubook1     | Podkapitola 1  | 1          | Druha kapitola |
      | mubook1     | Podkapitola 2  | 1          | Podkapitola 1  |
      | mubook1     | Podkapitola 3  | 1          | Podkapitola 2  |
      | mubook1     | Treti kapitola | 0          |                |
      | mubook1     | Podkapitola X  | 1          | Treti kapitola  |
    And I am on the "Test book 1" "mubook activity" page logged in as "teacher1"
    And I turn editing mode on
    And I follow "Druha kapitola"

    When I click on "Sub-chapter actions: Podkapitola 1" "link_or_button"
    And I click on "Move sub-chapter" "link" in the ".dropdown-menu.show" "css_element"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Sub-chapter position | After 2.2 Podkapitola 2 |
    And I click on "Move sub-chapter" "button" in the ".modal-dialog" "css_element"
    Then I should not see "This is an intro"
    And I should see "Druha kapitola"
    And "Podkapitola 2" "text" should appear after "Druha kapitola" "text"
    And "Podkapitola 1" "text" should appear after "Podkapitola 2" "text"
    And "Podkapitola 3" "text" should appear after "Podkapitola 1" "text"

    When I click on "Sub-chapter actions: Podkapitola 1" "link_or_button"
    And I click on "Move sub-chapter" "link" in the ".dropdown-menu.show" "css_element"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Sub-chapter position | First in Druha kapitola |
    And I click on "Move sub-chapter" "button" in the ".modal-dialog" "css_element"
    Then I should not see "This is an intro"
    And I should see "Druha kapitola"
    And "Podkapitola 1" "text" should appear after "Druha kapitola" "text"
    And "Podkapitola 2" "text" should appear after "Podkapitola 1" "text"
    And "Podkapitola 3" "text" should appear after "Podkapitola 2" "text"

    When I click on "Sub-chapter actions: Podkapitola 1" "link_or_button"
    And I click on "Move sub-chapter" "link" in the ".dropdown-menu.show" "css_element"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Sub-chapter position | First in Prvni kapitola |
    And I click on "Move sub-chapter" "button" in the ".modal-dialog" "css_element"
    Then I should not see "This is an intro"
    And I should see "Prvni kapitola"
    And "Podkapitola 1" "text" should appear after "Prvni kapitola" "text"

    When I click on "Chapter actions" "link_or_button"
    And I click on "Move chapter" "link" in the ".dropdown-menu.show" "css_element"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Chapter position | After 3 Treti kapitola |
    And I click on "Move chapter" "button" in the ".modal-dialog" "css_element"
    Then I should see "This is an intro"
    And I should see "Druha kapitola"
    And "Podkapitola 2" "text" should appear after "Druha kapitola" "text"
    And "Podkapitola 3" "text" should appear after "Podkapitola 2" "text"
    And "Treti kapitola" "text" should appear after "Podkapitola 3" "text"
    And "Podkapitola X" "text" should appear after "Treti kapitola" "text"
    And "Prvni kapitola" "text" should appear after "Podkapitola X" "text"
    And "Podkapitola 1" "text" should appear after "Prvni kapitola" "text"

  Scenario: Students are not allowed to edit Interactive book chapters by default
    When I am on the "Test book 1" "mubook activity" page logged in as "student1"
    And I should see "This is an intro"
    Then I should see "No content has been added to this book yet."
    And edit mode should not be available on the current page

  Scenario: Students may be allowed to edit Interactive book chapters
    Given the following "permission overrides" exist:
      | capability             | permission | role    | contextlevel | reference |
      | mod/mubook:editchapter | Allow      | student | Course       | C1        |
    And I am on the "Test book 1" "mubook activity" page logged in as "student1"

    And I should see "Turn on edit mode to create book chapters"
    And I should not see "No content has been added to this book yet."
    And I should not see "Add chapter"
    And edit mode should be available on the current page

    When I turn editing mode on
    Then I should see "No content has been added to this book yet."
    And I should not see "Turn on edit mode to create book chapters"
    And I should see "Add chapter"

    When I press "Add chapter"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Chapter title    | Prvni kapitola  |
      | Add content      | None            |
    And I click on "Add chapter" "button" in the ".modal-dialog" "css_element"
    Then I should see "This is an intro"
    And I should see "Prvni kapitola"

    When I click on "Chapter actions: Prvni kapitola" "link_or_button"
    And I click on "Update chapter" "link" in the ".dropdown-menu.show" "css_element"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Chapter title    | Erste Kapitel         |
    And I click on "Update chapter" "button" in the ".modal-dialog" "css_element"
    Then I should see "This is an intro"
    And I should see "Erste Kapitel"

    When I click on "Chapter actions: Erste Kapitel" "link_or_button"
    And I click on "Delete chapter" "link" in the ".dropdown-menu.show" "css_element"
    And I click on "Delete chapter" "button" in the ".modal-dialog" "css_element"
    Then I should see "This is an intro"
    And I should not see "Erste Kapitel"
