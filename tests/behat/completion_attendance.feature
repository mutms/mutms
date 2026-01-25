@tool @tool_muprog @MuTMS @javascript
Feature: Program offline attendance completion tests

  Background:
    Given unnecessary Admin bookmarks block gets deleted
    And the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
    And the following "courses" exist:
      | fullname | shortname | format | category | enablecompletion | showcompletionconditions |
      | Course 1 | C1        | topics | CAT1     | 1                | 1                        |
      | Course 2 | C2        | topics | CAT2     | 1                | 1                        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | admin1   | Admin     | 1        | admin1@example.com   |
      | viewer1  | Viewer    | 1        | viewer1@example.com  |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
      | student3 | Student   | 3        | student3@example.com |
    And the following "roles" exist:
      | name            | shortname |
      | Program viewer  | pviewer   |
      | Program admin   | padmin    |
    And the following "permission overrides" exist:
      | capability                    | permission | role     | contextlevel | reference |
      | tool/muprog:view              | Allow      | pviewer  | System       |           |
      | tool/muprog:takeattendance    | Allow      | pviewer  | System       |           |
      | tool/muprog:view              | Allow      | padmin   | System       |           |
      | tool/muprog:edit              | Allow      | padmin   | System       |           |
      | tool/muprog:delete            | Allow      | padmin   | System       |           |
      | tool/muprog:manageevidence    | Allow      | padmin   | System       |           |
      | tool/muprog:takeattendance    | Allow      | padmin   | System       |           |
      | tool/muprog:admin             | Allow      | padmin   | System       |           |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | admin1    | padmin        | System       |           |
      | viewer1   | pviewer       | Category     | CAT1      |
    And the following "tool_muprog > programs" exist:
      | fullname    | idnumber | category | publicaccess |
      | Program 001 | PR1      | Cat 1    | 1            |
    And the following "tool_muprog > program_items" exist:
      | program     | type       | fullname             | course   | completiondelay |
      | Program 001 | attendance | Car test drive       |          |                 |
      | Program 001 | attendance | Motorcycle test ride |          | 3600            |
      | Program 001 | course     |                      | Course 1 |                 |
    And the following "tool_muprog > program_allocations" exist:
      | program     | user     |
      | Program 001 | student1 |
      | Program 001 | student2 |
      | Program 001 | student3 |

  Scenario: Program viewer with capability may take offline attendance
    Given I log in as "viewer1"
    And I am on the "Program 001" "tool_muprog > Program" page
    And I follow "Users"
    And I follow "Student 1"

    When I click on "Take attendance" "link" in the "Car test drive" "table_row"
    And I set the following fields to these values:
      | No show                | 1    |
      | timeeffective[day]     | 7    |
      | timeeffective[month]   | 1    |
      | timeeffective[year]    | 2025 |
      | timeeffective[hour]    | 09   |
      | timeeffective[minute]  | 00   |
    And I click on "Take attendance" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "program_content" table:
      | Item                 | Points | Completion type               | Completion date | Other evidence |
      | Program 001          |        | All in any order              |                 |                |
      | Car test drive       | 1      | Offline attendance: No show   |                 |                |
      | Motorcycle test ride | 1      | Offline attendance            |                 |                |
      | Course 1             | 1      | Course completion             |                 |                |
    And I should see "0 %" in the "Progress" definition list item
    And I should not see "7/01/25"

    When I click on "Take attendance" "link" in the "Car test drive" "table_row"
    And I set the following fields to these values:
      | Failed                 | 1    |
    And I click on "Take attendance" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "program_content" table:
      | Item                 | Points | Completion type               | Completion date | Other evidence |
      | Program 001          |        | All in any order              |                 |                |
      | Car test drive       | 1      | Offline attendance: Failed    |                 |                |
      | Motorcycle test ride | 1      | Offline attendance            |                 |                |
      | Course 1             | 1      | Course completion             |                 |                |
    And I should see "0 %" in the "Progress" definition list item
    And I should not see "7/01/25"

    When I click on "Take attendance" "link" in the "Car test drive" "table_row"
    And I set the following fields to these values:
      | Completed              | 1    |
    And I click on "Take attendance" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "program_content" table:
      | Item                 | Points | Completion type               | Completion date | Other evidence |
      | Program 001          |        | All in any order              |                 |                |
      | Car test drive       | 1      | Offline attendance: Completed | 7/01/25, 09:00  |                |
      | Motorcycle test ride | 1      | Offline attendance            |                 |                |
      | Course 1             | 1      | Course completion             |                 |                |
    And I should see "33 %" in the "Progress" definition list item

    When I click on "Take attendance" "link" in the "Car test drive" "table_row"
    And I set the following fields to these values:
      | Not set                | 1    |
    And I click on "Take attendance" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "program_content" table:
      | Item                 | Points | Completion type               | Completion date | Other evidence |
      | Program 001          |        | All in any order              |                 |                |
      | Car test drive       | 1      | Offline attendance            |                 |                |
      | Motorcycle test ride | 1      | Offline attendance            |                 |                |
      | Course 1             | 1      | Course completion             |                 |                |
    And I should not see "7/01/25"
    And I should see "0 %" in the "Progress" definition list item

    When I click on "Take attendance" "link" in the "Car test drive" "table_row"
    And I set the following fields to these values:
      | Completed              | 1    |
      | timeeffective[day]     | 8    |
      | timeeffective[month]   | 1    |
      | timeeffective[year]    | 2025 |
      | timeeffective[hour]    | 08   |
      | timeeffective[minute]  | 00   |
    And I click on "Take attendance" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "program_content" table:
      | Item                 | Points | Completion type               | Completion date | Other evidence |
      | Program 001          |        | All in any order              |                 |                |
      | Car test drive       | 1      | Offline attendance: Completed | 8/01/25, 08:00  |                |
      | Motorcycle test ride | 1      | Offline attendance            |                 |                |
      | Course 1             | 1      | Course completion             |                 |                |
    And I should see "33 %" in the "Progress" definition list item

    When I click on "Take attendance" "link" in the "Motorcycle test ride" "table_row"
    And I set the following fields to these values:
      | Completed              | 1    |
      | timeeffective[day]     | 5    |
      | timeeffective[month]   | 1    |
      | timeeffective[year]    | 2025 |
      | timeeffective[hour]    | 10   |
      | timeeffective[minute]  | 00   |
    And I click on "Take attendance" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "program_content" table:
      | Item                 | Points | Completion type               | Completion date | Other evidence |
      | Program 001          |        | All in any order              |                 |                |
      | Car test drive       | 1      | Offline attendance: Completed | 8/01/25, 08:00  |                |
      | Motorcycle test ride | 1      | Offline attendance: Completed | 5/01/25, 11:00  |                |
      | Course 1             | 1      | Course completion             |                 |                |
    And I should see "67 %" in the "Progress" definition list item

    And I log out
    And I log in as "student1"
    And I am on the "tool_muprog > My programs" page
    When I follow "Program 001"
    Then the following should exist in the "program_content" table:
      | Item                 | Points | Completion type               | Completion date |
      | Program 001          |        | All in any order              |                 |
      | Car test drive       | 1      | Offline attendance: Completed | 8/01/25, 08:00  |
      | Motorcycle test ride | 1      | Offline attendance: Completed | 5/01/25, 11:00  |
      | Course 1             | 1      | Course completion             |                 |
    And I should see "67 %" in the "Progress" definition list item

  Scenario: Program admin may provide other evidence for offline attendance completion
    Given I log in as "admin1"
    And I am on the "Program 001" "tool_muprog > Program" page
    And I follow "Users"
    And I follow "Student 1"
    And I click on "Take attendance" "link" in the "Car test drive" "table_row"
    And I set the following fields to these values:
      | Failed                 | 1    |
      | timeeffective[day]     | 8    |
      | timeeffective[month]   | 1    |
      | timeeffective[year]    | 2025 |
      | timeeffective[hour]    | 08   |
      | timeeffective[minute]  | 00   |
    And I click on "Take attendance" "button" in the ".modal-dialog" "css_element"
    And I click on "Take attendance" "link" in the "Motorcycle test ride" "table_row"
    And I set the following fields to these values:
      | Completed              | 1    |
      | timeeffective[day]     | 5    |
      | timeeffective[month]   | 1    |
      | timeeffective[year]    | 2025 |
      | timeeffective[hour]    | 10   |
      | timeeffective[minute]  | 00   |
    And I click on "Take attendance" "button" in the ".modal-dialog" "css_element"
    And the following should exist in the "program_content" table:
      | Item                 | Points | Completion type               | Completion date | Other evidence |
      | Program 001          |        | All in any order              |                 |                |
      | Car test drive       | 1      | Offline attendance: Failed    |                 |                |
      | Motorcycle test ride | 1      | Offline attendance: Completed | 5/01/25, 11:00  |                |
      | Course 1             | 1      | Course completion             |                 |                |
    And I should see "33 %" in the "Progress" definition list item

    When I click on "Update other evidence" "link" in the "Car test drive" "table_row"
    And I set the following fields to these values:
      | evidencetimecompleted[enabled] | 1        |
      | evidencetimecompleted[day]     | 3        |
      | evidencetimecompleted[month]   | 1        |
      | evidencetimecompleted[year]    | 2025     |
      | evidencetimecompleted[hour]    | 08       |
      | evidencetimecompleted[minute]  | 00       |
      | Details                        | no need! |
      | itemrecalculate                | 1        |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "program_content" table:
      | Item                 | Points | Completion type               | Completion date | Other evidence |
      | Program 001          |        | All in any order              |                 |                |
      | Car test drive       | 1      | Offline attendance: Failed    | 3/01/25, 08:00  | no need!       |
      | Motorcycle test ride | 1      | Offline attendance: Completed | 5/01/25, 11:00  |                |
      | Course 1             | 1      | Course completion             |                 |                |
    And I should see "67 %" in the "Progress" definition list item

    When I click on "Update other evidence" "link" in the "Car test drive" "table_row"
    And I set the following fields to these values:
      | evidencetimecompleted[enabled] | 0        |
      | itemrecalculate                | 1        |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "program_content" table:
      | Item                 | Points | Completion type               | Completion date | Other evidence |
      | Program 001          |        | All in any order              |                 |                |
      | Car test drive       | 1      | Offline attendance: Failed    |                 |                |
      | Motorcycle test ride | 1      | Offline attendance: Completed | 5/01/25, 11:00  |                |
      | Course 1             | 1      | Course completion             |                 |                |
    And I should see "33 %" in the "Progress" definition list item
    And I should not see "3/01/25"
    And I should not see "no need"

    When I click on "Update other evidence" "link" in the "Motorcycle test ride" "table_row"
    And I set the following fields to these values:
      | evidencetimecompleted[enabled] | 1        |
      | evidencetimecompleted[day]     | 3        |
      | evidencetimecompleted[month]   | 1        |
      | evidencetimecompleted[year]    | 2025     |
      | evidencetimecompleted[hour]    | 08       |
      | evidencetimecompleted[minute]  | 00       |
      | Details                        | no need! |
      | itemrecalculate                | 1        |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "program_content" table:
      | Item                 | Points | Completion type               | Completion date | Other evidence |
      | Program 001          |        | All in any order              |                 |                |
      | Car test drive       | 1      | Offline attendance: Failed    |                 |                |
      | Motorcycle test ride | 1      | Offline attendance: Completed | 3/01/25, 08:00  | no need!       |
      | Course 1             | 1      | Course completion             |                 |                |
    And I should see "33 %" in the "Progress" definition list item

    When I click on "Update other evidence" "link" in the "Motorcycle test ride" "table_row"
    And I set the following fields to these values:
      | evidencetimecompleted[enabled] | 0        |
      | itemrecalculate                | 1        |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "program_content" table:
      | Item                 | Points | Completion type               | Completion date | Other evidence |
      | Program 001          |        | All in any order              |                 |                |
      | Car test drive       | 1      | Offline attendance: Failed    |                 |                |
      | Motorcycle test ride | 1      | Offline attendance: Completed | 5/01/25, 11:00  |                |
      | Course 1             | 1      | Course completion             |                 |                |
    And I should see "33 %" in the "Progress" definition list item
    And I should not see "3/01/25"
    And I should not see "no need"
