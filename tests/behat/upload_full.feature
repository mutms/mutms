@tool @tool_muprog @MuTMS @javascript
Feature: Program upload full tests

  Background:
    Given unnecessary Admin bookmarks block gets deleted
    # Category 1 with no idnumber is expected at the top level - former Miscellaneous category.
    And the following "categories" exist:
      | name       | category | idnumber |
      | Category 2 | 0        | CAT2     |
      | Category 3 | 0        | CAT3     |
    And the following "courses" exist:
      | fullname  | shortname  | category |
      | Course 01 | C01        | CAT2     |
      | Course 02 | C02        | CAT2     |
      | Course 03 | C03        | CAT3     |
      | Course 04 | C04        | CAT3     |
      | Course 05 | C05        | CAT3     |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | manager1 | Manager   | 1        | manager1@example.com |
      | manager2 | Manager   | 2        | manager1@example.com |
    And the following "roles" exist:
      | name            | shortname |
      | Program viewer  | pviewer   |
      | Program manager | pmanager  |
    And the following "permission overrides" exist:
      | capability                     | permission | role     | contextlevel | reference |
      | tool/muprog:view            | Allow      | pmanager | System       |           |
      | tool/muprog:upload          | Allow      | pmanager | System       |           |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | manager1  | pmanager      | System       |           |
      | manager2  | pmanager      | Category     | CAT2      |
      | manager2  | pmanager      | Category     | CAT3      |

  @_file_upload
  Scenario: System manager can upload all programs into original categories using zipped JSON
    Given I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page

    When I click on "Upload programs" action from "Programs actions" dropdown
    And I upload "admin/tool/muprog/tests/fixtures/upload/programs_json.zip" file to "Files" filemanager
    And I press "Continue"
    And the following fields match these values:
      | usecategory | 1 |
    And the following should exist in the "upload_preview" table:
      | idnumber | Status | fullname   | category   | description  | publicaccess | creategroups | allocationstart           | allocationend             | startdate                             | duedate                   | enddate                    |
      | P00      | OK     | Program 00 | System     | Test program | Yes          | No           | 2023-10-30T17:57:00+00:00 | 2029-10-30T17:57:00+00:00 | Delay start after allocation - 3 days | Due after start - 1 month | End after start - 6 months |
      | P01      | OK     | Program 01 | Category 1 |              | No           | Yes          |                           |                           | Start immediately after allocation    | Not set                   | Not set                    |
      | P02      | OK     | Program 02 | Category 2 |              | No           | No           |                           |                           | 2024-10-01T18:09:00+01:00             | 2024-11-01T18:09:00+00:00 | 2024-12-01T18:09:00+00:00  |
    And I press "Upload programs"
    Then the following should exist in the "reportbuilder-table" table:
      | Program name | Category   | Program ID | Courses | Allocations | Public |
      | Program 00   | System     | P00        | 5       | 0           | Yes    |
      | Program 01   | Category 1 | P01        | 3       | 0           | No     |
      | Program 02   | Category 2 | P02        | 0       | 0           | No     |
    And I follow "Program 00"
    And I should see "Program 00" in the "Program name" definition list item
    And I should see "No" in the "Archived" definition list item
    And I follow "Content"
    And the following should exist in the "program_content" table:
      | Item       | Points | Completion type         |
      | Program 00 |        | All in order            |
      | Course 01  | 1      | Completion delay: 1 day |
      | First set  | 1      | At least 2              |
      | Course 02  | 1      |                         |
      | Course 03  | 1      |                         |
      | Course 04  | 1      |                         |
      | Second set | 2      | Minimum 3 points        |
      | Course 05  | 4      |                         |
    And I follow "Allocation settings"
    And I should see "Tuesday, 31 October 2023, 1:57 AM" in the "Allocation start" definition list item
    And I should see "Wednesday, 31 October 2029, 1:57 AM" in the "Allocation end" definition list item
    And I should see "Delay start after allocation - 3 days" in the "Program start" definition list item
    And I should see "Due after start - 1 month" in the "Program due" definition list item
    And I should see "End after start - 6 months" in the "Program end" definition list item
    And I should see "Active" in the "Manual allocation" definition list item
    And I should see "Active; Sign up key is required; Users 0/7; Sign ups are allowed" in the "Self allocation" definition list item
    And I should see "Active; Requests are allowed" in the "Requests with approval" definition list item
    And I should see "Inactive" in the "Automatic cohort allocation" definition list item

    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 01"
    And I follow "Content"
    And the following should exist in the "program_content" table:
      | Item       | Points | Completion type         |
      | Program 01 |        | All in any order        |
      | Course 01  | 1      |                         |
      | Course 02  | 1      |                         |
      | Course 05  | 1      |                         |
    And I follow "Allocation settings"
    And I should see "Not set" in the "Allocation start" definition list item
    And I should see "Not set" in the "Allocation end" definition list item
    And I should see "Start immediately after allocation" in the "Program start" definition list item
    And I should see "Not set" in the "Program due" definition list item
    And I should see "Not set" in the "Program end" definition list item
    And I should see "Inactive" in the "Manual allocation" definition list item
    And I should see "Inactive" in the "Self allocation" definition list item
    And I should see "Inactive" in the "Requests with approval" definition list item
    And I should see "Inactive" in the "Automatic cohort allocation" definition list item

    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 02"
    And I follow "Content"
    And the following should exist in the "program_content" table:
      | Item         | Points | Completion type          |
    And I follow "Allocation settings"
    And I should see "Not set" in the "Allocation start" definition list item
    And I should see "Not set" in the "Allocation end" definition list item
    And I should see "Wednesday, 2 October 2024, 1:09 AM" in the "Program start" definition list item
    And I should see "Saturday, 2 November 2024, 2:09 AM" in the "Program due" definition list item
    And I should see "Monday, 2 December 2024, 2:09 AM" in the "Program end" definition list item
    And I should see "Inactive" in the "Manual allocation" definition list item
    And I should see "Active; Sign ups are not allowed" in the "Self allocation" definition list item
    And I should see "Active; Requests are not allowed" in the "Requests with approval" definition list item
    And I should see "Inactive" in the "Automatic cohort allocation" definition list item

  @_file_upload
  Scenario: System manager can upload all programs into original categories using normal JSON without training
    Given I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page

    When I click on "Upload programs" action from "Programs actions" dropdown
    And I upload "admin/tool/muprog/tests/fixtures/upload/programs.json" file to "Files" filemanager
    And I press "Continue"
    And the following fields match these values:
      | usecategory | 1 |
    And the following should exist in the "upload_preview" table:
      | idnumber | Status | fullname   | category   | description  | publicaccess | creategroups | allocationstart           | allocationend             | startdate                             | duedate                   | enddate                    |
      | P00      | OK     | Program 00 | System     | Test program | Yes          | No           | 2023-10-30T17:57:00+00:00 | 2029-10-30T17:57:00+00:00 | Delay start after allocation - 3 days | Due after start - 1 month | End after start - 6 months |
      | P01      | OK     | Program 01 | Category 1 |              | No           | Yes          |                           |                           | Start immediately after allocation    | Not set                   | Not set                    |
      | P02      | OK     | Program 02 | Category 2 |              | No           | No           |                           |                           | 2024-10-01T18:09:00+01:00             | 2024-11-01T18:09:00+00:00 | 2024-12-01T18:09:00+00:00  |
    And I press "Upload programs"
    Then the following should exist in the "reportbuilder-table" table:
      | Program name | Category   | Program ID | Courses | Allocations | Public |
      | Program 00   | System     | P00        | 5       | 0           | Yes    |
      | Program 01   | Category 1 | P01        | 3       | 0           | No     |
      | Program 02   | Category 2 | P02        | 0       | 0           | No     |
    And I follow "Program 00"
    And I should see "Program 00" in the "Program name" definition list item
    And I should see "No" in the "Archived" definition list item
    And I follow "Content"
    And the following should exist in the "program_content" table:
      | Item       | Points | Completion type         |
      | Program 00 |        | All in order            |
      | Course 01  | 1      | Completion delay: 1 day |
      | First set  | 1      | At least 2              |
      | Course 02  | 1      |                         |
      | Course 03  | 1      |                         |
      | Course 04  | 1      |                         |
      | Second set | 2      | Minimum 3 points        |
      | Course 05  | 4      |                         |
    And I follow "Allocation settings"
    And I should see "Tuesday, 31 October 2023, 1:57 AM" in the "Allocation start" definition list item
    And I should see "Wednesday, 31 October 2029, 1:57 AM" in the "Allocation end" definition list item
    And I should see "Delay start after allocation - 3 days" in the "Program start" definition list item
    And I should see "Due after start - 1 month" in the "Program due" definition list item
    And I should see "End after start - 6 months" in the "Program end" definition list item
    And I should see "Active" in the "Manual allocation" definition list item
    And I should see "Active; Sign up key is required; Users 0/7; Sign ups are allowed" in the "Self allocation" definition list item
    And I should see "Active; Requests are allowed" in the "Requests with approval" definition list item
    And I should see "Inactive" in the "Automatic cohort allocation" definition list item

    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 01"
    And I follow "Content"
    And the following should exist in the "program_content" table:
      | Item       | Points | Completion type         |
      | Program 01 |        | All in any order        |
      | Course 01  | 1      |                         |
      | Course 02  | 1      |                         |
      | Course 05  | 1      |                         |
    And I follow "Allocation settings"
    And I should see "Not set" in the "Allocation start" definition list item
    And I should see "Not set" in the "Allocation end" definition list item
    And I should see "Start immediately after allocation" in the "Program start" definition list item
    And I should see "Not set" in the "Program due" definition list item
    And I should see "Not set" in the "Program end" definition list item
    And I should see "Inactive" in the "Manual allocation" definition list item
    And I should see "Inactive" in the "Self allocation" definition list item
    And I should see "Inactive" in the "Requests with approval" definition list item
    And I should see "Inactive" in the "Automatic cohort allocation" definition list item

    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 02"
    And I follow "Content"
    And the following should exist in the "program_content" table:
      | Item         | Points | Completion type          |
      | Program 02   |        | All in any order         |
    And I follow "Allocation settings"
    And I should see "Not set" in the "Allocation start" definition list item
    And I should see "Not set" in the "Allocation end" definition list item
    And I should see "Wednesday, 2 October 2024, 1:09 AM" in the "Program start" definition list item
    And I should see "Saturday, 2 November 2024, 2:09 AM" in the "Program due" definition list item
    And I should see "Monday, 2 December 2024, 2:09 AM" in the "Program end" definition list item
    And I should see "Inactive" in the "Manual allocation" definition list item
    And I should see "Active; Sign ups are not allowed" in the "Self allocation" definition list item
    And I should see "Active; Requests are not allowed" in the "Requests with approval" definition list item
    And I should see "Inactive" in the "Automatic cohort allocation" definition list item

  @_file_upload
  Scenario: System manager can upload all programs into original categories using normal old JSON with public
    Given I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page

    When I click on "Upload programs" action from "Programs actions" dropdown
    And I upload "admin/tool/muprog/tests/fixtures/upload/programs_old.json" file to "Files" filemanager
    And I press "Continue"
    And the following fields match these values:
      | usecategory | 1 |
    And the following should exist in the "upload_preview" table:
      | idnumber | Status | fullname   | category   | description  | publicaccess | creategroups | allocationstart           | allocationend             | startdate                             | duedate                   | enddate                    |
      | P00      | OK     | Program 00 | System     | Test program | Yes          | No           | 2023-10-30T17:57:00+00:00 | 2029-10-30T17:57:00+00:00 | Delay start after allocation - 3 days | Due after start - 1 month | End after start - 6 months |
      | P01      | OK     | Program 01 | Category 1 |              | No           | Yes          |                           |                           | Start immediately after allocation    | Not set                   | Not set                    |
      | P02      | OK     | Program 02 | Category 2 |              | No           | No           |                           |                           | 2024-10-01T18:09:00+01:00             | 2024-11-01T18:09:00+00:00 | 2024-12-01T18:09:00+00:00  |
    And I press "Upload programs"
    Then the following should exist in the "reportbuilder-table" table:
      | Program name | Category   | Program ID | Courses | Allocations | Public |
      | Program 00   | System     | P00        | 5       | 0           | Yes    |
      | Program 01   | Category 1 | P01        | 3       | 0           | No     |
      | Program 02   | Category 2 | P02        | 0       | 0           | No     |
    And I follow "Program 00"

  @_file_upload @tool_mutrain
  Scenario: System manager can upload all programs into original categories using normal JSON with training
    Given I skip tests if "tool_mutrain" is not installed
    And the following "tool_mutrain > frameworks" exist:
      | name         | idnumber | publicaccess | requiredtraining |
      | Training FW1 | TFW1     | 1            | 10               |
      | Training FW2 |          | 1            | 20               |
    And I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page

    When I click on "Upload programs" action from "Programs actions" dropdown
    And I upload "admin/tool/muprog/tests/fixtures/upload/programs_training.json" file to "Files" filemanager
    And I press "Continue"
    And the following fields match these values:
      | usecategory | 1 |
    And the following should exist in the "upload_preview" table:
      | idnumber | Status | fullname   | category   | description  | publicaccess | creategroups | allocationstart           | allocationend             | startdate                             | duedate                   | enddate                    |
      | P00      | OK     | Program 00 | System     | Test program | Yes          | No           | 2023-10-30T17:57:00+00:00 | 2029-10-30T17:57:00+00:00 | Delay start after allocation - 3 days | Due after start - 1 month | End after start - 6 months |
      | P01      | OK     | Program 01 | Category 1 |              | No           | Yes          |                           |                           | Start immediately after allocation    | Not set                   | Not set                    |
      | P02      | OK     | Program 02 | Category 2 |              | No           | No           |                           |                           | 2024-10-01T18:09:00+01:00             | 2024-11-01T18:09:00+00:00 | 2024-12-01T18:09:00+00:00  |
    And I press "Upload programs"
    Then the following should exist in the "reportbuilder-table" table:
      | Program name | Category   | Program ID | Courses | Allocations | Public |
      | Program 00   | System     | P00        | 5       | 0           | Yes    |
      | Program 01   | Category 1 | P01        | 3       | 0           | No     |
      | Program 02   | Category 2 | P02        | 0       | 0           | No     |
    And I follow "Program 00"
    And I should see "Program 00" in the "Program name" definition list item
    And I should see "No" in the "Archived" definition list item
    And I follow "Content"
    And the following should exist in the "program_content" table:
      | Item       | Points | Completion type         |
      | Program 00 |        | All in order            |
      | Course 01  | 1      | Completion delay: 1 day |
      | First set  | 1      | At least 2              |
      | Course 02  | 1      |                         |
      | Course 03  | 1      |                         |
      | Course 04  | 1      |                         |
      | Second set | 2      | Minimum 3 points        |
      | Course 05  | 4      |                         |
    And I follow "Allocation settings"
    And I should see "Tuesday, 31 October 2023, 1:57 AM" in the "Allocation start" definition list item
    And I should see "Wednesday, 31 October 2029, 1:57 AM" in the "Allocation end" definition list item
    And I should see "Delay start after allocation - 3 days" in the "Program start" definition list item
    And I should see "Due after start - 1 month" in the "Program due" definition list item
    And I should see "End after start - 6 months" in the "Program end" definition list item
    And I should see "Active" in the "Manual allocation" definition list item
    And I should see "Active; Sign up key is required; Users 0/7; Sign ups are allowed" in the "Self allocation" definition list item
    And I should see "Active; Requests are allowed" in the "Requests with approval" definition list item
    And I should see "Inactive" in the "Automatic cohort allocation" definition list item

    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 01"
    And I follow "Content"
    And the following should exist in the "program_content" table:
      | Item       | Points | Completion type         |
      | Program 01 |        | All in any order        |
      | Course 01  | 1      |                         |
      | Course 02  | 1      |                         |
      | Course 05  | 1      |                         |
    And I follow "Allocation settings"
    And I should see "Not set" in the "Allocation start" definition list item
    And I should see "Not set" in the "Allocation end" definition list item
    And I should see "Start immediately after allocation" in the "Program start" definition list item
    And I should see "Not set" in the "Program due" definition list item
    And I should see "Not set" in the "Program end" definition list item
    And I should see "Inactive" in the "Manual allocation" definition list item
    And I should see "Inactive" in the "Self allocation" definition list item
    And I should see "Inactive" in the "Requests with approval" definition list item
    And I should see "Inactive" in the "Automatic cohort allocation" definition list item

    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 02"
    And I follow "Content"
    And the following should exist in the "program_content" table:
      | Item         | Points | Completion type          |
      | Program 02   |        | All in any order         |
      | Training FW1 | 1      | Required training: 10    |
      | Training FW2 | 2      | Required training: 20    |
      | Training FW2 | 2      | Completion delay: 1 day  |
    And I follow "Allocation settings"
    And I should see "Not set" in the "Allocation start" definition list item
    And I should see "Not set" in the "Allocation end" definition list item
    And I should see "Wednesday, 2 October 2024, 1:09 AM" in the "Program start" definition list item
    And I should see "Saturday, 2 November 2024, 2:09 AM" in the "Program due" definition list item
    And I should see "Monday, 2 December 2024, 2:09 AM" in the "Program end" definition list item
    And I should see "Inactive" in the "Manual allocation" definition list item
    And I should see "Active; Sign ups are not allowed" in the "Self allocation" definition list item
    And I should see "Active; Requests are not allowed" in the "Requests with approval" definition list item
    And I should see "Inactive" in the "Automatic cohort allocation" definition list item

  @_file_upload
  Scenario: System manager can upload all programs into original categories using zipped CSV comma
    Given I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page

    When I click on "Upload programs" action from "Programs actions" dropdown
    And I upload "admin/tool/muprog/tests/fixtures/upload/programs_csv_comma.zip" file to "Files" filemanager
    And I press "Continue"
    And the following fields match these values:
      | usecategory | 1 |
    And the following should exist in the "upload_preview" table:
      | idnumber | Status | fullname   | category   | description  | publicaccess | creategroups | allocationstart           | allocationend             | startdate                             | duedate                   | enddate                    |
      | P00      | OK     | Program 00 | System     | Test program | Yes          | No           | 2023-10-30T17:57:00+00:00 | 2029-10-30T17:57:00+00:00 | Delay start after allocation - 3 days | Due after start - 1 month | End after start - 6 months |
      | P01      | OK     | Program 01 | Category 1 |              | No           | Yes          |                           |                           | Start immediately after allocation    | Not set                   | Not set                    |
      | P02      | OK     | Program 02 | Category 2 |              | No           | No           |                           |                           | 2024-10-01T18:09:00+01:00             | 2024-11-01T18:09:00+00:00 | 2024-12-01T18:09:00+00:00  |
    And I press "Upload programs"
    Then the following should exist in the "reportbuilder-table" table:
      | Program name | Category   | Program ID | Courses | Allocations | Public |
      | Program 00   | System     | P00        | 5       | 0           | Yes    |
      | Program 01   | Category 1 | P01        | 3       | 0           | No     |
      | Program 02   | Category 2 | P02        | 0       | 0           | No     |
    And I follow "Program 00"
    And I should see "Program 00" in the "Program name" definition list item
    And I should see "No" in the "Archived" definition list item
    And I follow "Content"
    And the following should exist in the "program_content" table:
      | Item       | Points | Completion type         |
      | Program 00 |        | All in order            |
      | Course 01  | 1      | Completion delay: 1 day |
      | First set  | 1      | At least 2              |
      | Course 02  | 1      |                         |
      | Course 03  | 1      |                         |
      | Course 04  | 1      |                         |
      | Second set | 2      | Minimum 3 points        |
      | Course 05  | 4      |                         |
    And I follow "Allocation settings"
    And I should see "Tuesday, 31 October 2023, 1:57 AM" in the "Allocation start" definition list item
    And I should see "Wednesday, 31 October 2029, 1:57 AM" in the "Allocation end" definition list item
    And I should see "Delay start after allocation - 3 days" in the "Program start" definition list item
    And I should see "Due after start - 1 month" in the "Program due" definition list item
    And I should see "End after start - 6 months" in the "Program end" definition list item
    And I should see "Active" in the "Manual allocation" definition list item
    And I should see "Active; Sign up key is required; Users 0/7; Sign ups are allowed" in the "Self allocation" definition list item
    And I should see "Active; Requests are allowed" in the "Requests with approval" definition list item
    And I should see "Inactive" in the "Automatic cohort allocation" definition list item

    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 01"
    And I follow "Content"
    And the following should exist in the "program_content" table:
      | Item       | Points | Completion type         |
      | Program 01 |        | All in any order        |
      | Course 01  | 1      |                         |
      | Course 02  | 1      |                         |
      | Course 05  | 1      |                         |
    And I follow "Allocation settings"
    And I should see "Not set" in the "Allocation start" definition list item
    And I should see "Not set" in the "Allocation end" definition list item
    And I should see "Start immediately after allocation" in the "Program start" definition list item
    And I should see "Not set" in the "Program due" definition list item
    And I should see "Not set" in the "Program end" definition list item
    And I should see "Inactive" in the "Manual allocation" definition list item
    And I should see "Inactive" in the "Self allocation" definition list item
    And I should see "Inactive" in the "Requests with approval" definition list item
    And I should see "Inactive" in the "Automatic cohort allocation" definition list item

    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 02"
    And I follow "Content"
    And the following should exist in the "program_content" table:
      | Item         | Points | Completion type          |
      | Program 02   |        | All in any order         |
    And I follow "Allocation settings"
    And I should see "Not set" in the "Allocation start" definition list item
    And I should see "Not set" in the "Allocation end" definition list item
    And I should see "Wednesday, 2 October 2024, 1:09 AM" in the "Program start" definition list item
    And I should see "Saturday, 2 November 2024, 2:09 AM" in the "Program due" definition list item
    And I should see "Monday, 2 December 2024, 2:09 AM" in the "Program end" definition list item
    And I should see "Inactive" in the "Manual allocation" definition list item
    And I should see "Active; Sign ups are not allowed" in the "Self allocation" definition list item
    And I should see "Active; Requests are not allowed" in the "Requests with approval" definition list item
    And I should see "Inactive" in the "Automatic cohort allocation" definition list item

  @_file_upload
  Scenario: System manager can upload all programs into original categories using zipped CSV semicolon
    Given I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page

    When I click on "Upload programs" action from "Programs actions" dropdown
    And I upload "admin/tool/muprog/tests/fixtures/upload/programs_csv_semicolon.zip" file to "Files" filemanager
    And I press "Continue"
    And the following fields match these values:
      | usecategory | 1 |
    And the following should exist in the "upload_preview" table:
      | idnumber | Status | fullname   | category   | description  | publicaccess | creategroups | allocationstart           | allocationend             | startdate                             | duedate                   | enddate                    |
      | P00      | OK     | Program 00 | System     | Test program | Yes          | No           | 2023-10-30T17:57:00+00:00 | 2029-10-30T17:57:00+00:00 | Delay start after allocation - 3 days | Due after start - 1 month | End after start - 6 months |
      | P01      | OK     | Program 01 | Category 1 |              | No           | Yes          |                           |                           | Start immediately after allocation    | Not set                   | Not set                    |
      | P02      | OK     | Program 02 | Category 2 |              | No           | No           |                           |                           | 2024-10-01T18:09:00+01:00             | 2024-11-01T18:09:00+00:00 | 2024-12-01T18:09:00+00:00  |
    And I press "Upload programs"
    Then the following should exist in the "reportbuilder-table" table:
      | Program name | Category   | Program ID | Courses | Allocations | Public |
      | Program 00   | System     | P00        | 5       | 0           | Yes    |
      | Program 01   | Category 1 | P01        | 3       | 0           | No     |
      | Program 02   | Category 2 | P02        | 0       | 0           | No     |
    And I follow "Program 00"
    And I should see "Program 00" in the "Program name" definition list item
    And I should see "No" in the "Archived" definition list item
    And I follow "Content"
    And the following should exist in the "program_content" table:
      | Item       | Points | Completion type         |
      | Program 00 |        | All in order            |
      | Course 01  | 1      | Completion delay: 1 day |
      | First set  | 1      | At least 2              |
      | Course 02  | 1      |                         |
      | Course 03  | 1      |                         |
      | Course 04  | 1      |                         |
      | Second set | 2      | Minimum 3 points        |
      | Course 05  | 4      |                         |
    And I follow "Allocation settings"
    And I should see "Tuesday, 31 October 2023, 1:57 AM" in the "Allocation start" definition list item
    And I should see "Wednesday, 31 October 2029, 1:57 AM" in the "Allocation end" definition list item
    And I should see "Delay start after allocation - 3 days" in the "Program start" definition list item
    And I should see "Due after start - 1 month" in the "Program due" definition list item
    And I should see "End after start - 6 months" in the "Program end" definition list item
    And I should see "Active" in the "Manual allocation" definition list item
    And I should see "Active; Sign up key is required; Users 0/7; Sign ups are allowed" in the "Self allocation" definition list item
    And I should see "Active; Requests are allowed" in the "Requests with approval" definition list item
    And I should see "Inactive" in the "Automatic cohort allocation" definition list item

    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 01"
    And I follow "Content"
    And the following should exist in the "program_content" table:
      | Item       | Points | Completion type         |
      | Program 01 |        | All in any order        |
      | Course 01  | 1      |                         |
      | Course 02  | 1      |                         |
      | Course 05  | 1      |                         |
    And I follow "Allocation settings"
    And I should see "Not set" in the "Allocation start" definition list item
    And I should see "Not set" in the "Allocation end" definition list item
    And I should see "Start immediately after allocation" in the "Program start" definition list item
    And I should see "Not set" in the "Program due" definition list item
    And I should see "Not set" in the "Program end" definition list item
    And I should see "Inactive" in the "Manual allocation" definition list item
    And I should see "Inactive" in the "Self allocation" definition list item
    And I should see "Inactive" in the "Requests with approval" definition list item
    And I should see "Inactive" in the "Automatic cohort allocation" definition list item

    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 02"
    And I follow "Content"
    And the following should exist in the "program_content" table:
      | Item         | Points | Completion type          |
      | Program 02   |        | All in any order         |
    And I follow "Allocation settings"
    And I should see "Not set" in the "Allocation start" definition list item
    And I should see "Not set" in the "Allocation end" definition list item
    And I should see "Wednesday, 2 October 2024, 1:09 AM" in the "Program start" definition list item
    And I should see "Saturday, 2 November 2024, 2:09 AM" in the "Program due" definition list item
    And I should see "Monday, 2 December 2024, 2:09 AM" in the "Program end" definition list item
    And I should see "Inactive" in the "Manual allocation" definition list item
    And I should see "Active; Sign ups are not allowed" in the "Self allocation" definition list item
    And I should see "Active; Requests are not allowed" in the "Requests with approval" definition list item
    And I should see "Inactive" in the "Automatic cohort allocation" definition list item

  @_file_upload
  Scenario: System manager can upload all programs into original categories using zipped CSV tab
    Given I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page

    When I click on "Upload programs" action from "Programs actions" dropdown
    And I upload "admin/tool/muprog/tests/fixtures/upload/programs_csv_tab.zip" file to "Files" filemanager
    And I press "Continue"
    And the following fields match these values:
      | usecategory | 1 |
    And the following should exist in the "upload_preview" table:
      | idnumber | Status | fullname   | category   | description  | publicaccess | creategroups | allocationstart           | allocationend             | startdate                             | duedate                   | enddate                    |
      | P00      | OK     | Program 00 | System     | Test program | Yes          | No           | 2023-10-30T17:57:00+00:00 | 2029-10-30T17:57:00+00:00 | Delay start after allocation - 3 days | Due after start - 1 month | End after start - 6 months |
      | P01      | OK     | Program 01 | Category 1 |              | No           | Yes          |                           |                           | Start immediately after allocation    | Not set                   | Not set                    |
      | P02      | OK     | Program 02 | Category 2 |              | No           | No           |                           |                           | 2024-10-01T18:09:00+01:00             | 2024-11-01T18:09:00+00:00 | 2024-12-01T18:09:00+00:00  |
    And I press "Upload programs"
    Then the following should exist in the "reportbuilder-table" table:
      | Program name | Category   | Program ID | Courses | Allocations | Public |
      | Program 00   | System     | P00        | 5       | 0           | Yes    |
      | Program 01   | Category 1 | P01        | 3       | 0           | No     |
      | Program 02   | Category 2 | P02        | 0       | 0           | No     |
    And I follow "Program 00"
    And I should see "Program 00" in the "Program name" definition list item
    And I should see "No" in the "Archived" definition list item
    And I follow "Content"
    And the following should exist in the "program_content" table:
      | Item       | Points | Completion type         |
      | Program 00 |        | All in order            |
      | Course 01  | 1      | Completion delay: 1 day |
      | First set  | 1      | At least 2              |
      | Course 02  | 1      |                         |
      | Course 03  | 1      |                         |
      | Course 04  | 1      |                         |
      | Second set | 2      | Minimum 3 points        |
      | Course 05  | 4      |                         |
    And I follow "Allocation settings"
    And I should see "Tuesday, 31 October 2023, 1:57 AM" in the "Allocation start" definition list item
    And I should see "Wednesday, 31 October 2029, 1:57 AM" in the "Allocation end" definition list item
    And I should see "Delay start after allocation - 3 days" in the "Program start" definition list item
    And I should see "Due after start - 1 month" in the "Program due" definition list item
    And I should see "End after start - 6 months" in the "Program end" definition list item
    And I should see "Active" in the "Manual allocation" definition list item
    And I should see "Active; Sign up key is required; Users 0/7; Sign ups are allowed" in the "Self allocation" definition list item
    And I should see "Active; Requests are allowed" in the "Requests with approval" definition list item
    And I should see "Inactive" in the "Automatic cohort allocation" definition list item

    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 01"
    And I follow "Content"
    And the following should exist in the "program_content" table:
      | Item       | Points | Completion type         |
      | Program 01 |        | All in any order        |
      | Course 01  | 1      |                         |
      | Course 02  | 1      |                         |
      | Course 05  | 1      |                         |
    And I follow "Allocation settings"
    And I should see "Not set" in the "Allocation start" definition list item
    And I should see "Not set" in the "Allocation end" definition list item
    And I should see "Start immediately after allocation" in the "Program start" definition list item
    And I should see "Not set" in the "Program due" definition list item
    And I should see "Not set" in the "Program end" definition list item
    And I should see "Inactive" in the "Manual allocation" definition list item
    And I should see "Inactive" in the "Self allocation" definition list item
    And I should see "Inactive" in the "Requests with approval" definition list item
    And I should see "Inactive" in the "Automatic cohort allocation" definition list item

    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 02"
    And I follow "Content"
    And the following should exist in the "program_content" table:
      | Item         | Points | Completion type          |
      | Program 02   |        | All in any order         |
    And I follow "Allocation settings"
    And I should see "Not set" in the "Allocation start" definition list item
    And I should see "Not set" in the "Allocation end" definition list item
    And I should see "Wednesday, 2 October 2024, 1:09 AM" in the "Program start" definition list item
    And I should see "Saturday, 2 November 2024, 2:09 AM" in the "Program due" definition list item
    And I should see "Monday, 2 December 2024, 2:09 AM" in the "Program end" definition list item
    And I should see "Inactive" in the "Manual allocation" definition list item
    And I should see "Active; Sign ups are not allowed" in the "Self allocation" definition list item
    And I should see "Active; Requests are not allowed" in the "Requests with approval" definition list item
    And I should see "Inactive" in the "Automatic cohort allocation" definition list item

  @_file_upload
  Scenario: System manager can upload all programs into original categories using extracted CSV without training
    Given I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page

    When I click on "Upload programs" action from "Programs actions" dropdown
    And I upload "admin/tool/muprog/tests/fixtures/upload/programs.csv" file to "Files" filemanager
    And I upload "admin/tool/muprog/tests/fixtures/upload/programs_contents.csv" file to "Files" filemanager
    And I upload "admin/tool/muprog/tests/fixtures/upload/programs_sources.csv" file to "Files" filemanager
    And I press "Continue"
    And the following fields match these values:
      | usecategory | 1 |
    And the following should exist in the "upload_preview" table:
      | idnumber | Status | fullname   | category   | description  | publicaccess | creategroups | allocationstart           | allocationend             | startdate                             | duedate                   | enddate                    |
      | P00      | OK     | Program 00 | System     | Test program | Yes          | No           | 2023-10-30T17:57:00+00:00 | 2029-10-30T17:57:00+00:00 | Delay start after allocation - 3 days | Due after start - 1 month | End after start - 6 months |
      | P01      | OK     | Program 01 | Category 1 |              | No           | Yes          |                           |                           | Start immediately after allocation    | Not set                   | Not set                    |
      | P02      | OK     | Program 02 | Category 2 |              | No           | No           |                           |                           | 2024-10-01T18:09:00+01:00             | 2024-11-01T18:09:00+00:00 | 2024-12-01T18:09:00+00:00  |
    And I press "Upload programs"
    Then the following should exist in the "reportbuilder-table" table:
      | Program name | Category   | Program ID | Courses | Allocations | Public |
      | Program 00   | System     | P00        | 5       | 0           | Yes    |
      | Program 01   | Category 1 | P01        | 3       | 0           | No     |
      | Program 02   | Category 2 | P02        | 0       | 0           | No     |
    And I follow "Program 00"
    And I should see "Program 00" in the "Program name" definition list item
    And I should see "No" in the "Archived" definition list item
    And I follow "Content"
    And the following should exist in the "program_content" table:
      | Item       | Points | Completion type         |
      | Program 00 |        | All in order            |
      | Course 01  | 1      | Completion delay: 1 day |
      | First set  | 1      | At least 2              |
      | Course 02  | 1      |                         |
      | Course 03  | 1      |                         |
      | Course 04  | 1      |                         |
      | Second set | 2      | Minimum 3 points        |
      | Course 05  | 4      |                         |
    And I follow "Allocation settings"
    And I should see "Tuesday, 31 October 2023, 1:57 AM" in the "Allocation start" definition list item
    And I should see "Wednesday, 31 October 2029, 1:57 AM" in the "Allocation end" definition list item
    And I should see "Delay start after allocation - 3 days" in the "Program start" definition list item
    And I should see "Due after start - 1 month" in the "Program due" definition list item
    And I should see "End after start - 6 months" in the "Program end" definition list item
    And I should see "Active" in the "Manual allocation" definition list item
    And I should see "Active; Sign up key is required; Users 0/7; Sign ups are allowed" in the "Self allocation" definition list item
    And I should see "Active; Requests are allowed" in the "Requests with approval" definition list item
    And I should see "Inactive" in the "Automatic cohort allocation" definition list item

    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 01"
    And I follow "Content"
    And the following should exist in the "program_content" table:
      | Item       | Points | Completion type         |
      | Program 01 |        | All in any order        |
      | Course 01  | 1      |                         |
      | Course 02  | 1      |                         |
      | Course 05  | 1      |                         |
    And I follow "Allocation settings"
    And I should see "Not set" in the "Allocation start" definition list item
    And I should see "Not set" in the "Allocation end" definition list item
    And I should see "Start immediately after allocation" in the "Program start" definition list item
    And I should see "Not set" in the "Program due" definition list item
    And I should see "Not set" in the "Program end" definition list item
    And I should see "Inactive" in the "Manual allocation" definition list item
    And I should see "Inactive" in the "Self allocation" definition list item
    And I should see "Inactive" in the "Requests with approval" definition list item
    And I should see "Inactive" in the "Automatic cohort allocation" definition list item

    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 02"
    And I follow "Content"
    And the following should exist in the "program_content" table:
      | Item         | Points | Completion type          |
      | Program 02   |        | All in any order         |
    And I follow "Allocation settings"
    And I should see "Not set" in the "Allocation start" definition list item
    And I should see "Not set" in the "Allocation end" definition list item
    And I should see "Wednesday, 2 October 2024, 1:09 AM" in the "Program start" definition list item
    And I should see "Saturday, 2 November 2024, 2:09 AM" in the "Program due" definition list item
    And I should see "Monday, 2 December 2024, 2:09 AM" in the "Program end" definition list item
    And I should see "Inactive" in the "Manual allocation" definition list item
    And I should see "Active; Sign ups are not allowed" in the "Self allocation" definition list item
    And I should see "Active; Requests are not allowed" in the "Requests with approval" definition list item
    And I should see "Inactive" in the "Automatic cohort allocation" definition list item

  @_file_upload
  Scenario: System manager can upload all programs into original categories using extracted CSV with old public
    Given I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page

    When I click on "Upload programs" action from "Programs actions" dropdown
    And I upload "admin/tool/muprog/tests/fixtures/upload/programs_old.csv" file to "Files" filemanager
    And I upload "admin/tool/muprog/tests/fixtures/upload/programs_contents.csv" file to "Files" filemanager
    And I upload "admin/tool/muprog/tests/fixtures/upload/programs_sources.csv" file to "Files" filemanager
    And I press "Continue"
    And the following fields match these values:
      | usecategory | 1 |
    And the following should exist in the "upload_preview" table:
      | idnumber | Status | fullname   | category   | description  | publicaccess | creategroups | allocationstart           | allocationend             | startdate                             | duedate                   | enddate                    |
      | P00      | OK     | Program 00 | System     | Test program | Yes          | No           | 2023-10-30T17:57:00+00:00 | 2029-10-30T17:57:00+00:00 | Delay start after allocation - 3 days | Due after start - 1 month | End after start - 6 months |
      | P01      | OK     | Program 01 | Category 1 |              | No           | Yes          |                           |                           | Start immediately after allocation    | Not set                   | Not set                    |
      | P02      | OK     | Program 02 | Category 2 |              | No           | No           |                           |                           | 2024-10-01T18:09:00+01:00             | 2024-11-01T18:09:00+00:00 | 2024-12-01T18:09:00+00:00  |
    And I press "Upload programs"
    Then the following should exist in the "reportbuilder-table" table:
      | Program name | Category   | Program ID | Courses | Allocations | Public |
      | Program 00   | System     | P00        | 5       | 0           | Yes    |
      | Program 01   | Category 1 | P01        | 3       | 0           | No     |
      | Program 02   | Category 2 | P02        | 0       | 0           | No     |

  @_file_upload @tool_mutrain
  Scenario: System manager can upload all programs into original categories using extracted CSV with training
    Given I skip tests if "tool_mutrain" is not installed
    And the following "tool_mutrain > frameworks" exist:
      | name         | idnumber | publicaccess | requiredtraining |
      | Training FW1 | TFW1     | 1            | 10               |
      | Training FW2 |          | 1            | 20               |
    And I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page

    When I click on "Upload programs" action from "Programs actions" dropdown
    And I upload "admin/tool/muprog/tests/fixtures/upload/programs.csv" file to "Files" filemanager
    And I upload "admin/tool/muprog/tests/fixtures/upload/programs_contents_training.csv" file to "Files" filemanager
    And I upload "admin/tool/muprog/tests/fixtures/upload/programs_sources.csv" file to "Files" filemanager
    And I press "Continue"
    And the following fields match these values:
      | usecategory | 1 |
    And the following should exist in the "upload_preview" table:
      | idnumber | Status | fullname   | category   | description  | publicaccess | creategroups | allocationstart           | allocationend             | startdate                             | duedate                   | enddate                    |
      | P00      | OK     | Program 00 | System     | Test program | Yes          | No           | 2023-10-30T17:57:00+00:00 | 2029-10-30T17:57:00+00:00 | Delay start after allocation - 3 days | Due after start - 1 month | End after start - 6 months |
      | P01      | OK     | Program 01 | Category 1 |              | No           | Yes          |                           |                           | Start immediately after allocation    | Not set                   | Not set                    |
      | P02      | OK     | Program 02 | Category 2 |              | No           | No           |                           |                           | 2024-10-01T18:09:00+01:00             | 2024-11-01T18:09:00+00:00 | 2024-12-01T18:09:00+00:00  |
    And I press "Upload programs"
    Then the following should exist in the "reportbuilder-table" table:
      | Program name | Category   | Program ID | Courses | Allocations | Public |
      | Program 00   | System     | P00        | 5       | 0           | Yes    |
      | Program 01   | Category 1 | P01        | 3       | 0           | No     |
      | Program 02   | Category 2 | P02        | 0       | 0           | No     |
    And I follow "Program 00"
    And I should see "Program 00" in the "Program name" definition list item
    And I should see "No" in the "Archived" definition list item
    And I follow "Content"
    And the following should exist in the "program_content" table:
      | Item       | Points | Completion type         |
      | Program 00 |        | All in order            |
      | Course 01  | 1      | Completion delay: 1 day |
      | First set  | 1      | At least 2              |
      | Course 02  | 1      |                         |
      | Course 03  | 1      |                         |
      | Course 04  | 1      |                         |
      | Second set | 2      | Minimum 3 points        |
      | Course 05  | 4      |                         |
    And I follow "Allocation settings"
    And I should see "Tuesday, 31 October 2023, 1:57 AM" in the "Allocation start" definition list item
    And I should see "Wednesday, 31 October 2029, 1:57 AM" in the "Allocation end" definition list item
    And I should see "Delay start after allocation - 3 days" in the "Program start" definition list item
    And I should see "Due after start - 1 month" in the "Program due" definition list item
    And I should see "End after start - 6 months" in the "Program end" definition list item
    And I should see "Active" in the "Manual allocation" definition list item
    And I should see "Active; Sign up key is required; Users 0/7; Sign ups are allowed" in the "Self allocation" definition list item
    And I should see "Active; Requests are allowed" in the "Requests with approval" definition list item
    And I should see "Inactive" in the "Automatic cohort allocation" definition list item

    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 01"
    And I follow "Content"
    And the following should exist in the "program_content" table:
      | Item       | Points | Completion type         |
      | Program 01 |        | All in any order        |
      | Course 01  | 1      |                         |
      | Course 02  | 1      |                         |
      | Course 05  | 1      |                         |
    And I follow "Allocation settings"
    And I should see "Not set" in the "Allocation start" definition list item
    And I should see "Not set" in the "Allocation end" definition list item
    And I should see "Start immediately after allocation" in the "Program start" definition list item
    And I should see "Not set" in the "Program due" definition list item
    And I should see "Not set" in the "Program end" definition list item
    And I should see "Inactive" in the "Manual allocation" definition list item
    And I should see "Inactive" in the "Self allocation" definition list item
    And I should see "Inactive" in the "Requests with approval" definition list item
    And I should see "Inactive" in the "Automatic cohort allocation" definition list item

    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 02"
    And I follow "Content"
    And the following should exist in the "program_content" table:
      | Item         | Points | Completion type          |
      | Program 02   |        | All in any order         |
      | Training FW1 | 1      | Required training: 10    |
      | Training FW2 | 2      | Required training: 20    |
      | Training FW2 | 2      | Completion delay: 1 day  |
    And I follow "Allocation settings"
    And I should see "Not set" in the "Allocation start" definition list item
    And I should see "Not set" in the "Allocation end" definition list item
    And I should see "Wednesday, 2 October 2024, 1:09 AM" in the "Program start" definition list item
    And I should see "Saturday, 2 November 2024, 2:09 AM" in the "Program due" definition list item
    And I should see "Monday, 2 December 2024, 2:09 AM" in the "Program end" definition list item
    And I should see "Inactive" in the "Manual allocation" definition list item
    And I should see "Active; Sign ups are not allowed" in the "Self allocation" definition list item
    And I should see "Active; Requests are not allowed" in the "Requests with approval" definition list item
    And I should see "Inactive" in the "Automatic cohort allocation" definition list item
