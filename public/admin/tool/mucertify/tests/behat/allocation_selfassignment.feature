@tool @tool_mucertify @MuTMS
Feature: Certification self-assignment tests

  Background:
    Given unnecessary Admin bookmarks block gets deleted
    And the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
      | Cat 3 | 0        | CAT3     |
      | Cat 4 | CAT3     | CAT4     |
    And the following "cohorts" exist:
      | name     | idnumber |
      | Cohort 1 | CH1      |
      | Cohort 2 | CH2      |
      | Cohort 3 | CH3      |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | manager1 | Manager   | 1        | manager1@example.com |
      | manager2 | Manager   | 2        | manager2@example.com |
      | viewer1  | Viewer    | 1        | viewer1@example.com  |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
      | student3 | Student   | 3        | student3@example.com |
      | student4 | Student   | 4        | student4@example.com |
      | student5 | Student   | 5        | student5@example.com |
    And the following "cohort members" exist:
      | user     | cohort |
      | student1 | CH1    |
      | student2 | CH1    |
      | student3 | CH1    |
      | student2 | CH2    |
      | student4 | CH2    |
    And the following "roles" exist:
      | name            | shortname |
      | Certification viewer  | pviewer   |
      | Certification manager | pmanager  |
    And the following "permission overrides" exist:
      | capability                     | permission | role     | contextlevel | reference |
      | tool/mucertify:view            | Allow      | pviewer  | System       |           |
      | tool/mucertify:view            | Allow      | pmanager | System       |           |
      | tool/mucertify:edit            | Allow      | pmanager | System       |           |
      | tool/mucertify:delete          | Allow      | pmanager | System       |           |
      | tool/mucertify:assign          | Allow      | pmanager | System       |           |
      | moodle/cohort:view              | Allow      | pmanager | System       |           |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | manager1  | pmanager      | System       |           |
      | manager2  | pmanager      | Category     | CAT2      |
      | manager2  | pmanager      | Category     | CAT3      |
      | viewer1   | pviewer       | System       |           |
    And the following "tool_muprog > programs" exist:
      | fullname    | idnumber | category | sources   |
      | Program 000 | PR0      |          | mucertify |
      | Program 001 | PR1      | Cat 1    | mucertify |
      | Program 002 | PR2      | Cat 2    | mucertify |
      | Program 003 | PR3      | Cat 3    | mucertify |
    And the following "tool_mucertify > certifications" exist:
      | fullname          | idnumber | category | program1 | cohorts  | publicaccess |
      | Certification 000 | CT0      |          | PR0      | Cohort 2 | 0            |
      | Certification 001 | CT1      | Cat 1    | PR1      |          | 1            |
      | Certification 002 | CT2      | Cat 2    | PR2      |          | 0            |
      | Certification 003 | CT3      | Cat 3    | PR3      |          | 0            |

  @javascript
  Scenario: Student may self assign to certification without a key
    When I log in as "manager1"
    And I am on the "tool_mucertify > All certifications management" page
    And I follow "Certification 000"
    And I follow "Assignment settings"
    And I click on "Update Self assignment" "link"
    And I set the following fields to these values:
      | Active             | Yes |
      | Allow new sign ups | No  |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    Then I should see "Active; Sign ups are not allowed" in the "Self assignment" definition list item
    And I log out

    When I log in as "student2"
    And I am on the "tool_mucertify > Certification catalogue" page
    And I should see "Certification 000"
    And I should see "Certification 001"
    And I follow "Certification 000"
    And I should not see "Sign up"
    And I log out

    When I log in as "manager1"
    And I am on the "tool_mucertify > All certifications management" page
    And I follow "Certification 000"
    And I follow "Assignment settings"
    And I click on "Update Self assignment" "link"
    And I set the following fields to these values:
      | Allow new sign ups | Yes |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    Then I should see "Active; Sign ups are allowed" in the "Self assignment" definition list item
    And I log out

    When I log in as "student2"
    And I am on the "tool_mucertify > Certification catalogue" page
    And I should see "Certification 000"
    And I should see "Certification 001"
    And I follow "Certification 000"
    And I press "Sign up"
    And I click on "Cancel" "button" in the ".modal-dialog" "css_element"
    And I press "Sign up"
    And I click on "Sign up" "button" in the ".modal-dialog" "css_element"
    Then I should see "Not certified" in the "Certification status" definition list item
    And I should see "Program 000"

  @javascript
  Scenario: Student may self assign to certification with a key
    Given I log in as "manager1"
    And I am on the "tool_mucertify > All certifications management" page
    And I follow "Certification 000"
    And I follow "Assignment settings"

    When I click on "Update Self assignment" "link"
    And I set the following fields to these values:
      | Active      | Yes   |
      | Sign up key | heslo |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    Then I should see "Active; Sign up key is required; Sign ups are allowed" in the "Self assignment" definition list item
    And I log out

    When I log in as "student2"
    And I am on the "tool_mucertify > Certification catalogue" page
    And I follow "Certification 000"
    And I press "Sign up"
    And I click on "Sign up" "button" in the ".modal-dialog" "css_element"
    And I should see "Required"
    And I set the following fields to these values:
      | Sign up key | hEslo |
    And I click on "Sign up" "button" in the ".modal-dialog" "css_element"
    And I should see "Error"
    And I set the following fields to these values:
      | Sign up key | heslo |
    And I click on "Sign up" "button" in the ".modal-dialog" "css_element"
    Then I should see "Not certified" in the "Certification status" definition list item
    And I should see "Program 000"

  @javascript
  Scenario: Student may self assign to certification with max users limit
    Given I log in as "manager1"
    And I am on the "tool_mucertify > All certifications management" page
    And I follow "Certification 001"
    And I follow "Assignment settings"

    When I click on "Update Self assignment" "link"
    And I set the following fields to these values:
      | Active    | Yes |
      | Max users | 2   |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    Then I should see "Active; Users 0/2; Sign ups are allowed" in the "Self assignment" definition list item
    And I log out

    And I log in as "student1"
    And I am on the "tool_mucertify > Certification catalogue" page
    And I follow "Certification 001"
    And I press "Sign up"
    And I click on "Sign up" "button" in the ".modal-dialog" "css_element"
    And I should see "Not certified" in the "Certification status" definition list item
    And I log out
    And I log in as "student2"
    And I am on the "tool_mucertify > Certification catalogue" page
    And I follow "Certification 001"
    And I press "Sign up"
    And I click on "Sign up" "button" in the ".modal-dialog" "css_element"
    And I log out

    When I log in as "student3"
    And I am on the "tool_mucertify > Certification catalogue" page
    And I follow "Certification 001"
    Then I should see "Maximum number of users self-assigned already"
