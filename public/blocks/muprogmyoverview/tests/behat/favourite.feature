@block @block_muprogmyoverview @tool_muprog @javascript @MuTMS
Feature: The My programs page overview block allows users to favourite their programs

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
      | user     | program   |
      | student1 | Program 1 |
      | student1 | Program 2 |
      | student1 | Program 3 |
      | student1 | Program 4 |
      | student1 | Program 5 |

  Scenario: Favourite a program on a program card
    Given I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    When I click on ".programmenubtn" "css_element" in the "//div[contains(@class, 'program-card') and contains(.,'Program 2')]" "xpath_element"
    And I click on "Star this program" "link" in the "//div[contains(@class, 'program-card') and contains(.,'Program 2')]" "xpath_element"
    And I reload the page
    Then "//div[contains(@class, 'program-card') and contains(.,'Program 2')]//span[@data-region='is-favourite' and @aria-hidden='false']" "xpath_element" should exist
    And "//div[contains(@class, 'program-card') and contains(.,'Program 2')]//span[@data-region='is-favourite' and @aria-hidden='true']" "xpath_element" should not exist
    And "//div[contains(@class, 'program-card') and contains(.,'Program 1')]//span[@data-region='is-favourite' and @aria-hidden='true']" "xpath_element" should exist
    And "//div[contains(@class, 'program-card') and contains(.,'Program 3')]//span[@data-region='is-favourite' and @aria-hidden='true']" "xpath_element" should exist

  Scenario: Star a program and switch display to list
    Given I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    When I click on ".programmenubtn" "css_element" in the "//div[contains(@class, 'program-card') and contains(.,'Program 5')]" "xpath_element"
    And I click on "Star this program" "link" in the "//div[contains(@class, 'program-card') and contains(.,'Program 5')]" "xpath_element"
    And I click on "Display drop-down menu" "button" in the "My programs overview page" "block"
    And I click on "List" "link" in the "My programs overview page" "block"
    Then "//li[contains(concat(' ', normalize-space(@class), ' '), 'list-group-item') and contains(.,'Program 5')]//span[@data-region='is-favourite' and @aria-hidden='false']" "xpath_element" should exist
    And "//li[contains(concat(' ', normalize-space(@class), ' '), 'list-group-item') and contains(.,'Program 5')]//span[@data-region='is-favourite' and @aria-hidden='true']" "xpath_element" should not exist
    And "//li[contains(concat(' ', normalize-space(@class), ' '), 'list-group-item') and contains(.,'Program 1')]//span[@data-region='is-favourite' and @aria-hidden='true']" "xpath_element" should exist
    And "//li[contains(concat(' ', normalize-space(@class), ' '), 'list-group-item') and contains(.,'Program 3')]//span[@data-region='is-favourite' and @aria-hidden='true']" "xpath_element" should exist

  Scenario: Star a program and switch display to description
    Given I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    When I click on ".programmenubtn" "css_element" in the "//div[contains(@class, 'program-card') and contains(.,'Program 5')]" "xpath_element"
    And I click on "Star this program" "link" in the "//div[contains(@class, 'program-card') and contains(.,'Program 5')]" "xpath_element"
    And I click on "Display drop-down menu" "button" in the "My programs overview page" "block"
    And I click on "Details" "link" in the "My programs overview page" "block"
    Then "//div[contains(concat(' ', normalize-space(@class), ' '), 'program-descriptionitem') and contains(.,'Program 5')]//span[@data-region='is-favourite' and @aria-hidden='false']" "xpath_element" should exist
    And "//div[contains(concat(' ', normalize-space(@class), ' '), 'program-descriptionitem') and contains(.,'Program 5')]//span[@data-region='is-favourite' and @aria-hidden='true']" "xpath_element" should not exist
    And "//div[contains(concat(' ', normalize-space(@class), ' '), 'program-descriptionitem') and contains(.,'Program 1')]//span[@data-region='is-favourite' and @aria-hidden='true']" "xpath_element" should exist
    And "//div[contains(concat(' ', normalize-space(@class), ' '), 'program-descriptionitem') and contains(.,'Program 3')]//span[@data-region='is-favourite' and @aria-hidden='true']" "xpath_element" should exist
