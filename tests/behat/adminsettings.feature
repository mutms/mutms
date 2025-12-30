@block @block_muprogmyoverview @tool_muprog @javascript @MuTMS
Feature: The My programs overview block allows admins to easily configure the students' list

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

  Scenario: Enable 'All (including removed from view)' My programs filter option
    Given I log in as "admin"
    And I navigate to "Plugins > Blocks > My programs overview page" in site administration
    And I set the field "All (including removed from view)" to "1"
    And I press "Save"
    And I log out
    Then I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "All" "button" in the "My programs overview page" "block"
    # We have to check for the data attribute instead of the list element text as we would get false positives from the "All" element otherwise
    Then "All (including removed from view)" "list_item" should exist in the ".block_muprogmyoverview .dropdown-menu" "css_element"

  Scenario: Disable 'All (including removed from view)' My programs filter option
    Given I log in as "admin"
    And I navigate to "Plugins > Blocks > My programs overview page" in site administration
    And I set the field "All (including removed from view)" to "0"
    And I press "Save"
    And I log out
    Then I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "All" "button" in the "My programs overview page" "block"
    Then "All (including removed from view)" "list_item" should not exist in the ".block_muprogmyoverview .dropdown-menu" "css_element"

  Scenario: Enable 'All' My programs filter option
    Given I log in as "admin"
    And I navigate to "Plugins > Blocks > My programs overview page" in site administration
    And I set the field "All" in the "//*[@id=\"admin-displaygroupingall\"]" "xpath_element" to "1"
    And I press "Save"
    And I log out
    Then I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "All" "button" in the "My programs overview page" "block"
    Then "[data-value='all']" "css_element" should exist in the ".block_muprogmyoverview .dropdown-menu" "css_element"

  Scenario: Disable 'All' My programs filter option
    Given I log in as "admin"
    And I navigate to "Plugins > Blocks > My programs overview page" in site administration
    And I set the field "All" in the "//*[@id=\"admin-displaygroupingall\"]" "xpath_element" to "0"
    And I press "Save"
    And I log out
    Then I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    # 'All' option has been disabled, so the button is falling back to the 'In progress' option which is the next enabled option.
    And I click on "In progress" "button" in the "My programs overview page" "block"
    Then "[data-value='all']" "css_element" should not exist in the ".block_muprogmyoverview .dropdown-menu" "css_element"

  Scenario: Enable 'In progress' My programs filter option
    Given I log in as "admin"
    And I navigate to "Plugins > Blocks > My programs overview page" in site administration
    And I set the field "In progress" to "1"
    And I press "Save"
    And I log out
    Then I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "All" "button" in the "My programs overview page" "block"
    # We have to check for the data attribute instead of the list element text as we would get false negatives "All (including removed from view)" element otherwise
    Then "In progress" "list_item" should exist in the ".block_muprogmyoverview .dropdown-menu" "css_element"

  Scenario: Disable 'In progress' My programs filter option
    Given I log in as "admin"
    And I navigate to "Plugins > Blocks > My programs overview page" in site administration
    And I set the field "In progress" to "0"
    And I press "Save"
    And I log out
    Then I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "All" "button" in the "My programs overview page" "block"
    Then "In progress" "list_item" should not exist in the ".block_muprogmyoverview .dropdown-menu" "css_element"

  Scenario: Enable 'Future' My programs filter option
    Given I log in as "admin"
    And I navigate to "Plugins > Blocks > My programs overview page" in site administration
    And I set the field "Future" to "1"
    And I press "Save"
    And I log out
    Then I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "All" "button" in the "My programs overview page" "block"
    Then "Future" "list_item" should exist in the ".block_muprogmyoverview .dropdown-menu" "css_element"

  Scenario: Disable 'Future' My programs filter option
    Given I log in as "admin"
    And I navigate to "Plugins > Blocks > My programs overview page" in site administration
    And I set the field "Future" to "0"
    And I press "Save"
    And I log out
    Then I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "All" "button" in the "My programs overview page" "block"
    Then "Future" "list_item" should not exist in the ".block_muprogmyoverview .dropdown-menu" "css_element"

  Scenario: Enable 'Past' My programs filter option
    Given I log in as "admin"
    And I navigate to "Plugins > Blocks > My programs overview page" in site administration
    And I set the field "Past" to "1"
    And I press "Save"
    And I log out
    Then I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "All" "button" in the "My programs overview page" "block"
    Then "Past" "list_item" should exist in the ".block_muprogmyoverview .dropdown-menu" "css_element"

  Scenario: Disable 'Past' My programs filter option
    Given I log in as "admin"
    And I navigate to "Plugins > Blocks > My programs overview page" in site administration
    And I set the field "Past" to "0"
    And I press "Save"
    And I log out
    Then I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "All" "button" in the "My programs overview page" "block"
    Then "Past" "list_item" should not exist in the ".block_muprogmyoverview .dropdown-menu" "css_element"

  Scenario: Enable 'Starred' My programs filter option
    Given I log in as "admin"
    And I navigate to "Plugins > Blocks > My programs overview page" in site administration
    And I set the field "Starred" to "1"
    And I press "Save"
    And I log out
    Then I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "All" "button" in the "My programs overview page" "block"
    Then "Starred" "list_item" should exist in the ".block_muprogmyoverview .dropdown-menu" "css_element"

  Scenario: Disable 'Starred' My programs filter option
    Given I log in as "admin"
    And I navigate to "Plugins > Blocks > My programs overview page" in site administration
    And I set the field "Starred" to "0"
    And I press "Save"
    And I log out
    Then I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "All" "button" in the "My programs overview page" "block"
    Then "Starred" "list_item" should not exist in the ".block_muprogmyoverview .dropdown-menu" "css_element"

  Scenario: Enable 'Removed programs' My programs filter option
    Given I log in as "admin"
    And I navigate to "Plugins > Blocks > My programs overview page" in site administration
    And I set the field "Removed from view" to "1"
    And I press "Save"
    And I log out
    Then I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "All" "button" in the "My programs overview page" "block"
    Then "Removed from view" "list_item" should exist in the ".block_muprogmyoverview .dropdown-menu" "css_element"

  Scenario: Disable 'Removed programs' My programs filter option
    Given I log in as "admin"
    And I navigate to "Plugins > Blocks > My programs overview page" in site administration
    And I set the field "Removed from view" to "0"
    And I press "Save"
    And I log out
    Then I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    And I click on "All" "button" in the "My programs overview page" "block"
    Then "Removed from view" "list_item" should not exist in the ".block_muprogmyoverview .dropdown-menu" "css_element"

  Scenario: Disable all My programs filter options
    Given I log in as "admin"
    And I navigate to "Plugins > Blocks > My programs overview page" in site administration
    And I set the field "All (including removed from view)" to "0"
    And I set the field "All" in the "//*[@id=\"admin-displaygroupingall\"]" "xpath_element" to "0"
    And I set the field "In progress" to "0"
    And I set the field "Future" to "0"
    And I set the field "Past" to "0"
    And I set the field "Starred" to "0"
    And I set the field "Removed from view" to "0"
    And I press "Save"
    And I log out
    And I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    Then "button#groupingdropdown" "css_element" should not exist in the ".block_muprogmyoverview" "css_element"
    And I should see "Program 1" in the "My programs overview page" "block"
    And I should see "Program 2" in the "My programs overview page" "block"
    And I should see "Program 3" in the "My programs overview page" "block"
    And I should see "Program 4" in the "My programs overview page" "block"
    And I should see "Program 5" in the "My programs overview page" "block"

  Scenario: Disable all but one My programs filter option
    Given I log in as "admin"
    And I navigate to "Plugins > Blocks > My programs overview page" in site administration
    And I set the field "All (including removed from view)" to "0"
    And I set the field "All" in the "//*[@id=\"admin-displaygroupingall\"]" "xpath_element" to "0"
    And I set the field "In progress" to "1"
    And I set the field "Future" to "0"
    And I set the field "Past" to "0"
    And I set the field "Starred" to "0"
    And I set the field "Removed from view" to "0"
    And I press "Save"
    And I log out
    And I am on the "block_muprogmyoverview > My programs" page logged in as "student1"
    Then "button#groupingdropdown" "css_element" should not exist in the ".block_muprogmyoverview" "css_element"
    And I should see "Program 2" in the "My programs overview page" "block"
    And I should see "Program 3" in the "My programs overview page" "block"
    And I should see "Program 4" in the "My programs overview page" "block"
    And I should not see "Program 1" in the "My programs overview page" "block"
    And I should not see "Program 5" in the "My programs overview page" "block"
