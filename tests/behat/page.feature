@block @block_muprogmyoverview @tool_muprog @javascript @MuTMS
Feature: The My programs overview page allows users to easily access their programs

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                | idnumber |
      | student1 | Student   | X        | student1@example.com | S1       |
    And the following "categories" exist:
      | name        | category | idnumber |
      | Category 1  | 0        | CAT1     |
    And the following "tool_muprog > programs" exist:
      | fullname  | idnumber  | category |
      | Program 1 | C1        | 0        |
      | Program 2 | C2        | 0        |
      | Program 3 | C3        | 0        |
      | Program 4 | C4        | CAT1     |
      | Program 5 | C5        | 0        |
    And the following "tool_muprog > program_allocations" exist:
      | user     | program   | timestart                   | timeend                    |
      | student1 | Program 1 | ##1 month ago##             | ##15 days ago##            |
      | student1 | Program 2 | ##yesterday##               | ##tomorrow##               |
      | student1 | Program 3 | ##yesterday##               | ##tomorrow##               |
      | student1 | Program 4 | ##yesterday##               | ##tomorrow##               |
      | student1 | Program 5 | ##first day of next month## | ##last day of next month## |

  Scenario: View past programs in My programs overview
    Given I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "All" "button" in the "My programs overview page" "block"
    When I click on "Past" "link" in the "My programs overview page" "block"
    Then I should see "Program 1" in the "My programs overview page" "block"
    And I should not see "Program 2" in the "My programs overview page" "block"
    And I should not see "Program 3" in the "My programs overview page" "block"
    And I should not see "Program 4" in the "My programs overview page" "block"
    And I should not see "Program 5" in the "My programs overview page" "block"

  Scenario: View future programs in My programs overview
    Given I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "All" "button" in the "My programs overview page" "block"
    When I click on "Future" "link" in the "My programs overview page" "block"
    Then I should see "Program 5" in the "My programs overview page" "block"
    And I should not see "Program 1" in the "My programs overview page" "block"
    And I should not see "Program 2" in the "My programs overview page" "block"
    And I should not see "Program 3" in the "My programs overview page" "block"
    And I should not see "Program 4" in the "My programs overview page" "block"

  Scenario: View inprogress programs in My programs overview
    Given I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "All" "button" in the "My programs overview page" "block"
    When I click on "In progress" "link" in the "My programs overview page" "block"
    Then I should see "Program 2" in the "My programs overview page" "block"
    Then I should see "Program 3" in the "My programs overview page" "block"
    Then I should see "Program 4" in the "My programs overview page" "block"
    And I should not see "Program 1" in the "My programs overview page" "block"
    And I should not see "Program 5" in the "My programs overview page" "block"

  Scenario: View all (except removed) programs in My programs overview
    Given I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "All" "button" in the "My programs overview page" "block"
    When I click on "All" "link" in the "My programs overview page" "block"
    Then I should see "Program 1" in the "My programs overview page" "block"
    Then I should see "Program 2" in the "My programs overview page" "block"
    Then I should see "Program 3" in the "My programs overview page" "block"
    Then I should see "Program 4" in the "My programs overview page" "block"
    Then I should see "Program 5" in the "My programs overview page" "block"

  Scenario: View all (including removed from view) programs in My programs overview
    Given the following config values are set as admin:
      | config                            | value | plugin           |
      | displaygroupingallincludinghidden | 1     | block_muprogmyoverview |
    And I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "All" "button" in the "My programs overview page" "block"
    # We have to click on the data attribute instead of the button element text as we might risk to click on the false positive "All (including removed from view)" element instead
    When I click on "[data-value='allincludinghidden']" "css_element" in the "My programs overview page" "block"
    Then I should see "Program 1" in the "My programs overview page" "block"
    Then I should see "Program 2" in the "My programs overview page" "block"
    Then I should see "Program 3" in the "My programs overview page" "block"
    Then I should see "Program 4" in the "My programs overview page" "block"
    Then I should see "Program 5" in the "My programs overview page" "block"

  Scenario: View inprogress programs in My programs overview - test persistence
    Given I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "All" "button" in the "My programs overview page" "block"
    And I click on "In progress" "link" in the "My programs overview page" "block"
    And I reload the page
    Then I should see "In progress" in the "My programs overview page" "block"
    Then I should see "Program 2" in the "My programs overview page" "block"
    Then I should see "Program 3" in the "My programs overview page" "block"
    Then I should see "Program 4" in the "My programs overview page" "block"
    And I should not see "Program 1" in the "My programs overview page" "block"
    And I should not see "Program 5" in the "My programs overview page" "block"

  Scenario: View all (except removed) programs in My programs overview - w/ persistence
    Given I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "All" "button" in the "My programs overview page" "block"
    When I click on "All" "link" in the "My programs overview page" "block"
    And I reload the page
    Then I should see "All" in the "My programs overview page" "block"
    Then I should see "Program 1" in the "My programs overview page" "block"
    Then I should see "Program 2" in the "My programs overview page" "block"
    Then I should see "Program 3" in the "My programs overview page" "block"
    Then I should see "Program 4" in the "My programs overview page" "block"
    Then I should see "Program 5" in the "My programs overview page" "block"

  Scenario: View past programs in My programs overview - w/ persistence
    Given I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "All" "button" in the "My programs overview page" "block"
    When I click on "Past" "link" in the "My programs overview page" "block"
    And I reload the page
    Then I should see "Past" in the "My programs overview page" "block"
    Then I should see "Program 1" in the "My programs overview page" "block"
    And I should not see "Program 2" in the "My programs overview page" "block"
    And I should not see "Program 3" in the "My programs overview page" "block"
    And I should not see "Program 4" in the "My programs overview page" "block"
    And I should not see "Program 5" in the "My programs overview page" "block"

  Scenario: View future programs in My programs overview - w/ persistence
    Given I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "All" "button" in the "My programs overview page" "block"
    When I click on "Future" "link" in the "My programs overview page" "block"
    And I reload the page
    Then I should see "Future" in the "My programs overview page" "block"
    Then I should see "Program 5" in the "My programs overview page" "block"
    And I should not see "Program 1" in the "My programs overview page" "block"
    And I should not see "Program 2" in the "My programs overview page" "block"
    And I should not see "Program 3" in the "My programs overview page" "block"
    And I should not see "Program 4" in the "My programs overview page" "block"

  Scenario: View favourite programs in My programs overview - w/ persistence
    Given I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on ".programmenubtn" "css_element" in the "//div[contains(@class, 'program-card') and contains(.,'Program 2')]" "xpath_element"
    And I click on "Star this program" "link" in the "//div[contains(@class, 'program-card') and contains(.,'Program 2')]" "xpath_element"
    And I click on "All" "button" in the "My programs overview page" "block"
    When I click on "Starred" "link" in the "My programs overview page" "block"
    And I reload the page
    Then I should see "Starred" in the "My programs overview page" "block"
    And I should see "Program 2" in the "My programs overview page" "block"
    And I should not see "Program 1" in the "My programs overview page" "block"
    And I should not see "Program 3" in the "My programs overview page" "block"
    And I should not see "Program 4" in the "My programs overview page" "block"
    And I should not see "Program 5" in the "My programs overview page" "block"

  Scenario: List display in My programs overview persistence
    Given I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "Display drop-down menu" "button" in the "My programs overview page" "block"
    And I click on "List" "link" in the "My programs overview page" "block"
    And I reload the page
    Then I should see "List" in the "My programs overview page" "block"
    And "[data-display='list']" "css_element" in the "My programs overview page" "block" should be visible

  Scenario: Cards display in My programs overview persistence
    Given I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "Display drop-down menu" "button" in the "My programs overview page" "block"
    And I click on "Card" "link" in the "My programs overview page" "block"
    And I reload the page
    Then I should see "Card" in the "My programs overview page" "block"
    And "[data-display='card']" "css_element" in the "My programs overview page" "block" should be visible

  Scenario: Description display in My programs overview persistence
    Given I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "Display drop-down menu" "button" in the "My programs overview page" "block"
    And I click on "Details" "link" in the "My programs overview page" "block"
    And I reload the page
    Then I should see "Details" in the "My programs overview page" "block"
    And "[data-display='description']" "css_element" in the "My programs overview page" "block" should be visible

  Scenario: Program name sort in My programs overview persistence
    Given I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "sortingdropdown" "button" in the "My programs overview page" "block"
    And I click on "Sort by program name" "link" in the "My programs overview page" "block"
    And I reload the page
    Then I should see "Sort by program name" in the "My programs overview page" "block"
    And "[data-sort='title']" "css_element" in the "My programs overview page" "block" should be visible

  Scenario: Due date sort in My programs overview persistence
    Given I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "sortingdropdown" "button" in the "My programs overview page" "block"
    And I click on "Sort by due date" "link" in the "My programs overview page" "block"
    And I reload the page
    Then I should see "Sort by due date" in the "My programs overview page" "block"
    And "[data-sort='duedate']" "css_element" in the "My programs overview page" "block" should be visible

  Scenario: ID number name sort in My programs overview persistence
    Given I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    When I click on "sortingdropdown" "button" in the "My programs overview page" "block"
    And I click on "Sort by ID number" "link" in the "My programs overview page" "block"
    And I reload the page
    Then I should see "Sort by ID number" in the "My programs overview page" "block"
    And "[data-sort='idnumber']" "css_element" in the "My programs overview page" "block" should be visible

  Scenario: View inprogress programs with hide in My programs overview persistent functionality
    Given I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "All" "button" in the "My programs overview page" "block"
    When I click on "In progress" "link" in the "My programs overview page" "block"
    And I click on ".programmenubtn" "css_element" in the "//div[contains(@class, 'program-card') and contains(.,'Program 2')]" "xpath_element"
    And I click on "Remove from view" "link" in the "//div[contains(@class, 'program-card') and contains(.,'Program 2')]" "xpath_element"
    And I reload the page
    Then I should see "Program 3" in the "My programs overview page" "block"
    Then I should see "Program 4" in the "My programs overview page" "block"
    And I should not see "Program 2" in the "My programs overview page" "block"
    And I should not see "Program 1" in the "My programs overview page" "block"
    And I should not see "Program 5" in the "My programs overview page" "block"

  Scenario: View past programs with hide in My programs overview persistent functionality
    Given I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "All" "button" in the "My programs overview page" "block"
    When I click on "Past" "link" in the "My programs overview page" "block"
    And I click on ".programmenubtn" "css_element" in the "//div[contains(@class, 'program-card') and contains(.,'Program 1')]" "xpath_element"
    And I click on "Remove from view" "link" in the "//div[contains(@class, 'program-card') and contains(.,'Program 1')]" "xpath_element"
    And I reload the page
    Then I should not see "Program 1" in the "My programs overview page" "block"
    And I should not see "Program 2" in the "My programs overview page" "block"
    And I should not see "Program 3" in the "My programs overview page" "block"
    And I should not see "Program 4" in the "My programs overview page" "block"
    And I should not see "Program 5" in the "My programs overview page" "block"

  Scenario: View future programs with hide in My programs overview persistent functionality
    Given I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "All" "button" in the "My programs overview page" "block"
    When I click on "Future" "link" in the "My programs overview page" "block"
    And I click on ".programmenubtn" "css_element" in the "//div[contains(@class, 'program-card') and contains(.,'Program 5')]" "xpath_element"
    And I click on "Remove from view" "link" in the "//div[contains(@class, 'program-card') and contains(.,'Program 5')]" "xpath_element"
    And I reload the page
    Then I should not see "Program 5" in the "My programs overview page" "block"
    And I should not see "Program 1" in the "My programs overview page" "block"
    And I should not see "Program 2" in the "My programs overview page" "block"
    And I should not see "Program 3" in the "My programs overview page" "block"
    And I should not see "Program 4" in the "My programs overview page" "block"

  Scenario: View all (except hidden) programs with hide in My programs overview persistent functionality
    Given I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "All" "button" in the "My programs overview page" "block"
    When I click on "All" "link" in the "My programs overview page" "block"
    And I click on ".programmenubtn" "css_element" in the "//div[contains(@class, 'program-card') and contains(.,'Program 5')]" "xpath_element"
    And I click on "Remove from view" "link" in the "//div[contains(@class, 'program-card') and contains(.,'Program 5')]" "xpath_element"
    And I reload the page
    Then I should not see "Program 5" in the "My programs overview page" "block"
    And I should see "Program 1" in the "My programs overview page" "block"
    And I should see "Program 2" in the "My programs overview page" "block"
    And I should see "Program 3" in the "My programs overview page" "block"
    And I should see "Program 4" in the "My programs overview page" "block"

  Scenario: View all (including removed from view) programs with hide in My programs overview persistent functionality
    Given the following config values are set as admin:
      | config                            | value | plugin           |
      | displaygroupingallincludinghidden | 1     | block_muprogmyoverview |
    And I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "All" "button" in the "My programs overview page" "block"
    # We have to click on the data attribute instead of the button element text as we might risk to click on the false positive "All (including removed from view)" element instead
    When I click on "[data-value='allincludinghidden']" "css_element" in the "My programs overview page" "block"
    And I click on ".programmenubtn" "css_element" in the "//div[contains(@class, 'program-card') and contains(.,'Program 5')]" "xpath_element"
    And I click on "Remove from view" "link" in the "//div[contains(@class, 'program-card') and contains(.,'Program 5')]" "xpath_element"
    And I reload the page
    Then I should see "Program 5" in the "My programs overview page" "block"
    And I should see "Program 1" in the "My programs overview page" "block"
    And I should see "Program 2" in the "My programs overview page" "block"
    And I should see "Program 3" in the "My programs overview page" "block"
    And I should see "Program 4" in the "My programs overview page" "block"

  Scenario: Show program category in cards in My programs overview display
    Given the following config values are set as admin:
      | displaycategories | 1 | block_muprogmyoverview |
    And I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "Display drop-down menu" "button" in the "My programs overview page" "block"
    When I click on "Card" "link" in the "My programs overview page" "block"
    Then I should see "Category 1" in the "My programs overview page" "block"

  Scenario: Show program category in list in My programs overview display
    Given the following config values are set as admin:
      | displaycategories | 1 | block_muprogmyoverview |
    And I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "Display drop-down menu" "button" in the "My programs overview page" "block"
    When I click on "List" "link" in the "My programs overview page" "block"
    Then I should see "Category 1" in the "My programs overview page" "block"

  Scenario: Show program category in description in My programs overview display
    Given the following config values are set as admin:
      | displaycategories | 1 | block_muprogmyoverview |
    And I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "Display drop-down menu" "button" in the "My programs overview page" "block"
    When I click on "Details" "link" in the "My programs overview page" "block"
    Then I should see "Category 1" in the "My programs overview page" "block"

  Scenario: Hide program category in cards in My programs overview display
    Given the following config values are set as admin:
      | displaycategories | 0 | block_muprogmyoverview |
    And I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "Display drop-down menu" "button" in the "My programs overview page" "block"
    When I click on "Card" "link" in the "My programs overview page" "block"
    Then I should not see "Category 1" in the "My programs overview page" "block"

  Scenario: Hide program category in list in My programs overview display
    Given the following config values are set as admin:
      | displaycategories | 0 | block_muprogmyoverview |
    And I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "Display drop-down menu" "button" in the "My programs overview page" "block"
    When I click on "List" "link" in the "My programs overview page" "block"
    Then I should not see "Category 1" in the "My programs overview page" "block"

  Scenario: Show program category in My programs details display
    Given the following config values are set as admin:
      | displaycategories | 0 | block_muprogmyoverview |
    And I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "Display drop-down menu" "button" in the "My programs overview page" "block"
    When I click on "Details" "link" in the "My programs overview page" "block"
    Then I should not see "Category 1" in the "My programs overview page" "block"

  @accessibility
  Scenario: The My programs overview page must have sufficient colour contrast
    When I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    Then the page should meet "wcag143" accessibility standards
