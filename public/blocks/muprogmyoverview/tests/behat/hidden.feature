@block @block_muprogmyoverview @tool_muprog @javascript @MuTMS
Feature: The My programs page overview block allows users to hide their programs

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

  Scenario: Test hide toggle functionality
    Given I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "All" "button" in the "My programs overview page" "block"
    When I click on "All" "link" in the "My programs overview page" "block"
    And I click on ".programmenubtn" "css_element" in the "//div[contains(@class, 'program-card') and contains(.,'Program 2')]" "xpath_element"
    And I click on "Remove from view" "link" in the "//div[contains(@class, 'program-card') and contains(.,'Program 2')]" "xpath_element"
    And I reload the page
    Then I should not see "Program 2" in the "My programs overview page" "block"

  Scenario: Test hide toggle functionality w/ favorites
    Given I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "All" "button" in the "My programs overview page" "block"
    And I click on "All" "link" in the "My programs overview page" "block"
    And I click on ".programmenubtn" "css_element" in the "//div[contains(@class, 'program-card') and contains(.,'Program 2')]" "xpath_element"
    And I click on "Star this program" "link" in the "//div[contains(@class, 'program-card') and contains(.,'Program 2')]" "xpath_element"
    And I click on ".programmenubtn" "css_element" in the "//div[contains(@class, 'program-card') and contains(.,'Program 2')]" "xpath_element"
    And I click on "Remove from view" "link" in the "//div[contains(@class, 'program-card') and contains(.,'Program 2')]" "xpath_element"
    When I reload the page
    And I should not see "Program 2" in the "My programs overview page" "block"
    And I click on "All" "button" in the "My programs overview page" "block"
    And I click on "Starred" "link" in the "My programs overview page" "block"
    Then I should not see "Program 2" in the "My programs overview page" "block"
    And I click on "Starred" "button" in the "My programs overview page" "block"
    And I click on "Removed from view" "link" in the "My programs overview page" "block"
    And I should see "Program 2" in the "My programs overview page" "block"

  Scenario: Test show toggle functionality
    Given I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "All" "button" in the "My programs overview page" "block"
    And I click on "All" "link" in the "My programs overview page" "block"
    And I click on ".programmenubtn" "css_element" in the "//div[contains(@class, 'program-card') and contains(.,'Program 2')]" "xpath_element"
    And I click on "Remove from view" "link" in the "//div[contains(@class, 'program-card') and contains(.,'Program 2')]" "xpath_element"
    And I click on "All" "button" in the "My programs overview page" "block"
    And I click on "Removed from view" "link" in the "My programs overview page" "block"
    And I click on ".programmenubtn" "css_element" in the "//div[contains(@class, 'program-card') and contains(.,'Program 2')]" "xpath_element"
    And I click on "Restore to view" "link" in the "//div[contains(@class, 'program-card') and contains(.,'Program 2')]" "xpath_element"
    And I reload the page
    And I should not see "Program 2" in the "My programs overview page" "block"
    And I click on "Removed from view" "button" in the "My programs overview page" "block"
    When I click on "All" "link" in the "My programs overview page" "block"
    And I reload the page
    Then I should see "Program 2" in the "My programs overview page" "block"

  Scenario: Test star and unstar functionality
    Given I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "All" "button" in the "My programs overview page" "block"
    And I click on "All" "link" in the "My programs overview page" "block"
    And I click on ".programmenubtn" "css_element" in the "//div[contains(@class, 'program-card') and contains(.,'Program 2')]" "xpath_element"
    And I click on "Star this program" "link" in the "//div[contains(@class, 'program-card') and contains(.,'Program 2')]" "xpath_element"
    And I click on ".programmenubtn" "css_element" in the "//div[contains(@class, 'program-card') and contains(.,'Program 2')]" "xpath_element"
    And I click on "Remove from view" "link" in the "//div[contains(@class, 'program-card') and contains(.,'Program 2')]" "xpath_element"
    And I click on "All" "button" in the "My programs overview page" "block"
    And I click on "Removed from view" "link" in the "My programs overview page" "block"
    And I should see "Program 2" in the "My programs overview page" "block"
    And I click on ".programmenubtn" "css_element" in the "//div[contains(@class, 'program-card') and contains(.,'Program 2')]" "xpath_element"
    And I click on "Restore to view" "link" in the "//div[contains(@class, 'program-card') and contains(.,'Program 2')]" "xpath_element"
    When I reload the page
    Then I should not see "Program 2" in the "My programs overview page" "block"
    And I click on "Removed from view" "button" in the "My programs overview page" "block"
    And I click on "All" "link" in the "My programs overview page" "block"
    And I should see "Program 2" in the "My programs overview page" "block"
    And I click on "All" "button" in the "My programs overview page" "block"
    And I click on "Starred" "link" in the "My programs overview page" "block"
    And I should see "Program 2" in the "My programs overview page" "block"

  Scenario: Test a program is hidden directly with "All" programs
    Given I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "All" "button" in the "My programs overview page" "block"
    When I click on "All" "link" in the "My programs overview page" "block"
    And I click on ".programmenubtn" "css_element" in the "//div[contains(@class, 'program-card') and contains(.,'Program 2')]" "xpath_element"
    And I click on "Remove from view" "link" in the "//div[contains(@class, 'program-card') and contains(.,'Program 2')]" "xpath_element"
    Then I should not see "Program 2" in the "My programs overview page" "block"

  Scenario: Test a program is never hidden with "All (including removed from view)" programs
    Given the following config values are set as admin:
      | config                            | value | plugin           |
      | displaygroupingallincludinghidden | 1     | block_muprogmyoverview |
    And I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "All" "button" in the "My programs overview page" "block"
    # We have to click on the data attribute instead of the button element text as we might risk to click on the false positive "All (except hidden)" element instead
    When I click on "[data-value='allincludinghidden']" "css_element" in the "My programs overview page" "block"
    And I click on ".programmenubtn" "css_element" in the "//div[contains(@class, 'program-card') and contains(.,'Program 2')]" "xpath_element"
    And I click on "Remove from view" "link" in the "//div[contains(@class, 'program-card') and contains(.,'Program 2')]" "xpath_element"
    Then I should see "Program 2" in the "My programs overview page" "block"
    And I click on ".programmenubtn" "css_element" in the "//div[contains(@class, 'program-card') and contains(.,'Program 2')]" "xpath_element"
    And I should not see "Remove from view" in the "//div[contains(@class, 'program-card') and contains(.,'Program 2')]" "xpath_element"
    And I should see "Restore to view" in the "//div[contains(@class, 'program-card') and contains(.,'Program 2')]" "xpath_element"
    And I click on "Restore to view" "link" in the "//div[contains(@class, 'program-card') and contains(.,'Program 2')]" "xpath_element"
    And I should see "Program 2" in the "My programs overview page" "block"
    And I click on ".programmenubtn" "css_element" in the "//div[contains(@class, 'program-card') and contains(.,'Program 2')]" "xpath_element"
    And I should see "Remove from view" in the "//div[contains(@class, 'program-card') and contains(.,'Program 2')]" "xpath_element"
    And I should not see "Restore to view" in the "//div[contains(@class, 'program-card') and contains(.,'Program 2')]" "xpath_element"
