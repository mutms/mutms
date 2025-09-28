@tool @tool_mucertify @MuTMS
Feature: Certifications behat generator tests

  Background:
    Given unnecessary Admin bookmarks block gets deleted
    And the following "cohorts" exist:
      | name     | idnumber |
      | Cohort 1 | CH1      |
      | Cohort 2 | CH2      |
      | Cohort 3 | CH3      |
    And the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
      | Cat 3 | 0        | CAT3     |
      | Cat 4 | CAT3     | CAT4     |
    And the following "tool_muprog > programs" exist:
      | fullname    | idnumber | category | publicaccess | sources   |
      | Program 000 | PR0      |          | 0            | mucertify |
      | Program 001 | PR1      | Cat 1    | 0            | mucertify |
      | Program 002 | PR2      | Cat 2    | 0            | mucertify |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | viewer1  | Viewer    | 1        | viewer1@example.com  |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
    And the following "roles" exist:
      | name                 | shortname |
      | Certification viewer | cviewer   |
    And the following "permission overrides" exist:
      | capability                     | permission | role    | contextlevel | reference |
      | tool/mucertify:view            | Allow      | cviewer | System       |           |
    And the following "role assigns" exist:
      | user     | role          | contextlevel | reference |
      | viewer1  | cviewer       | System       |           |

  Scenario: Certifications Behat generator creates certifications
    When the following "tool_mucertify > certifications" exist:
      | fullname          | idnumber | category | publicaccess | cohorts            | program1 | sources          |
      | Certification 000 | CT0      |          | 0            | Cohort 1, Cohort 2 | PR0      | manual, approval |
      | Certification 001 | CT1      | Cat 1    | 1            |                    | PR1      |                  |
      | Certification 002 | CT2      | Cat 2    | 0            |                    |          | manual           |

    And I log in as "viewer1"
    And I am on the "tool_mucertify > All certifications management" page
    Then "Certification 000" row "Category" column of "reportbuilder-table" table should contain "System"
    And "Certification 000" row "Certification ID" column of "reportbuilder-table" table should contain "CT0"
    And "Certification 000" row "Public" column of "reportbuilder-table" table should contain "No"
    And "Certification 001" row "Category" column of "reportbuilder-table" table should contain "Cat 1"
    And "Certification 001" row "Certification ID" column of "reportbuilder-table" table should contain "CT1"
    And "Certification 001" row "Public" column of "reportbuilder-table" table should contain "Yes"
    And "Certification 002" row "Category" column of "reportbuilder-table" table should contain "Cat 2"
    And "Certification 002" row "Certification ID" column of "reportbuilder-table" table should contain "CT2"
    And "Certification 002" row "Public" column of "reportbuilder-table" table should contain "No"

    And I follow "Certification 000"
    And I should see "Certification 000" in the "Certification name" definition list item
    And I should see "CT0" in the "Certification ID" definition list item
    And I should see "System" in the "Category" definition list item
    And I should see "No" in the "Archived" definition list item
    And I click on "Period settings" "link" in the ".secondary-navigation" "css_element"
    And I should see "Program 000" in the "Program" definition list item
    And I should see "Not set" in the "Certification due" definition list item
    And I should see "Certification completion date" in the "Valid from" definition list item
    And I should see "Never" in the "Window closing" definition list item
    And I should see "Never" in the "Expiration" definition list item
    And I should see "Standard course purge" in the "Certification program reset" definition list item
    And I should see "No" in the "Re-certify automatically" definition list item
    And I click on "Catalogue visibility" "link" in the ".secondary-navigation" "css_element"
    And I should see "No" in the "Public" definition list item
    And I should see "Cohort 1, Cohort 2" in the "Visible to cohorts" definition list item
    And I should not see "Cohort 3"
    And I click on "Assignment settings" "link" in the ".secondary-navigation" "css_element"
    And I should see "Active" in the "Manual assignment" definition list item
    And I should see "Inactive" in the "Automatic cohort assignment" definition list item
    And I should see "Inactive" in the "Self assignment" definition list item
    And I should see "Active" in the "Requests with approval" definition list item

    And I am on the "tool_mucertify > All certifications management" page
    And I follow "Certification 001"
    And I should see "Certification 001" in the "Certification name" definition list item
    And I should see "CT1" in the "Certification ID" definition list item
    And I should see "Cat 1" in the "Category" definition list item
    And I should see "No" in the "Archived" definition list item
    And I click on "Period settings" "link" in the ".secondary-navigation" "css_element"
    And I should see "Program 001" in the "Program" definition list item
    And I should see "Not set" in the "Certification due" definition list item
    And I should see "Certification completion date" in the "Valid from" definition list item
    And I should see "Never" in the "Window closing" definition list item
    And I should see "Never" in the "Expiration" definition list item
    And I should see "Standard course purge" in the "Certification program reset" definition list item
    And I should see "No" in the "Re-certify automatically" definition list item
    And I click on "Catalogue visibility" "link" in the ".secondary-navigation" "css_element"
    And I should see "Yes" in the "Public" definition list item
    And I should not see "Cohort 1"
    And I should not see "Cohort 2"
    And I should not see "Cohort 3"
    And I click on "Assignment settings" "link" in the ".secondary-navigation" "css_element"
    And I should see "Inactive" in the "Manual assignment" definition list item
    And I should see "Inactive" in the "Automatic cohort assignment" definition list item
    And I should see "Inactive" in the "Self assignment" definition list item
    And I should see "Inactive" in the "Requests with approval" definition list item

  Scenario: Certifications Behat generator creates certification assignments
    Given the following "tool_mucertify > certifications" exist:
      | fullname          | idnumber | program1 |
      | Certification 000 | CT0      | PR0      |
      | Certification 001 | CT1      |          |

    When the following "tool_mucertify > certification_assignments" exist:
      | user     | certification     |
      | student1 | Certification 000 |
    And the following "tool_mucertify > certification_assignments" exist:
      | user     | certification     | timecreated            | timecertifiedtemp      |
      | student2 | Certification 000 | ## 2025-01-01 10:00 ## | ## 2025-03-10 10:00 ## |
    And I log in as "viewer1"
    And I am on the "tool_mucertify > All certifications management" page
    And I follow "Certification 000"
    And I follow "Users"
    Then the following should exist in the "reportbuilder-table" table:
      | First name | Valid from | Expiration | Certification status | Source            | Archived |
      | Student 1  |            |            | Not certified        | Manual assignment | No       |
      | Student 2  | 1/01/25    | 10/03/25   | Expired              | Manual assignment | No       |
