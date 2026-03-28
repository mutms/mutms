@mod @mod_mubook @MuTMS @javascript
Feature: Generators for mod_mubook tests

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |

  Scenario: mod_mubook generator creates Interactive books
    When the following "activities" exist:
      | activity | name        | intro             | course | idnumber |
      | mubook   | Test book 1 | This is an intro  | C1     | mubook1  |
    And I am on the "Test book 1" "mubook activity" page logged in as admin
    Then I should see "Turn on edit mode to create book chapters"

  Scenario: mod_mubook generator creates chapters and subchapters
    Given the following "activities" exist:
      | activity | name        | intro             | course | idnumber |
      | mubook   | Test book 1 | This is an intro  | C1     | mubook1  |
    When the following "mod_mubook > chapters" exist:
      | mubook      | title          |
      | mubook1     | First chapter  |
      | Test book 1 | Third chapter  |
    And the following "mod_mubook > chapters" exist:
      | mubook      | title          | subchapter | positionafter |
      | mubook1     | Second chapter | 0          | First chapter |
      | mubook1     | Sub-chapter 1  | 1          | First chapter |
      | mubook1     | Sub-chapter 2  | 1          | Sub-chapter 1 |
      | Test book 1 | Fourth chapter |            |               |
    And I am on the "Test book 1" "mubook activity" page logged in as admin
    Then I should see "First chapter"
    And "Sub-chapter 1" "text" should appear after "First chapter" "text"
    And "Sub-chapter 2" "text" should appear after "Sub-chapter 1" "text"
    And "Second chapter" "text" should appear after "Sub-chapter 2" "text"
    And "Third chapter" "text" should appear after "Second chapter" "text"
    And "Fourth chapter" "text" should appear after "Third chapter" "text"

  Scenario: mod_mubook generator creates chapter contents
    Given the following "activities" exist:
      | activity | name        | intro             | course | idnumber |
      | mubook   | Test book 1 | This is an intro  | C1     | mubook1  |
    And the following "mod_mubook > chapters" exist:
      | mubook      | title          |
      | mubook1     | First chapter  |
      | mubook1     | Second chapter |
    When the following "mod_mubook > chapter_contents" exist:
      | chapter        | type     | data1              |
      | First chapter  | html     | <h3>Test html</h3> |
      | Second chapter | markdown | # Markdown heading |
      | Second chapter | unknown  |                    |
    And I am on the "Test book 1" "mubook activity" page logged in as admin
    And I follow "First chapter"
    Then I should see "Test html"
    And I follow "Next chapter Second chapter"
    And I should see "Markdown heading"
    And I should see "Content cannot be displayed (unknown content type"
