@tool @tool_mulib @MuTMS
Feature: Test tool_mulib behat steps

  Scenario: tool_mulib behat step: unnecessary Admin bookmarks block gets deleted
    When unnecessary Admin bookmarks block gets deleted
    And I log in as "admin"
    And I navigate to "Users > Accounts > Bulk user actions" in site administration
    Then I should not see "Admin bookmarks"

  Scenario: tool_mulib behat step: I skip tests if plugin is not installed
    Given I skip tests if "tool_mulib" is not installed
    When I skip tests if "local_grgrgrgrgrgr" is not installed
    Then I should see "never reached"

  Scenario: tool_mulib behat step: I skip tests if constant is defined and not empty
    Given I skip tests if "BEHAT_XYZXYZ" is defined and not empty
    When I skip tests if "BEHAT_TEST" is defined and not empty
    Then I should see "never reached"

  Scenario: tool_mulib behat step: List term definition assertion works
    Given I skip tests if "tool_mutenancy" is not installed
    When the following "tool_mutenancy > tenants" exist:
      | name     | idnumber |
      | Tenant 1 | ten1     |
      | Tenant 2 | ten2     |
    And I log in as "admin"
    And I navigate to "Multi-tenancy > Tenants" in site administration
    When I follow "Tenant 1"
    Then I should see "Tenant 1" in the "Tenant name" definition list item
  # Uncomment following to test a failure.
    #And I should see "Tenant 2" in the "Tenant name:" definition list item

  Scenario: tool_mulib behat step: List term definition negative assertion works
    Given I skip tests if "tool_mutenancy" is not installed
    When the following "tool_mutenancy > tenants" exist:
      | name     | idnumber |
      | Tenant 1 | ten1     |
      | Tenant 2 | ten2     |
    And I log in as "admin"
    And I navigate to "Multi-tenancy > Tenants" in site administration
    When I follow "Tenant 1"
    Then I should not see "tenant" in the "Tenant name" definition list item
  # Uncomment following to test a failure.
    #And I should not see "Trogram" in the "Tenant name" definition list item

  Scenario: tool_mulib behat step: I am on the profile page of user
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | First     | Student  | student1@example.com |
      | student2 | Second    | Student  | student2@example.com |
      | student3 | Third     | Student  | student3@example.com |
    And I log in as "admin"
    When I am on the profile page of user "student1"
    Then I should see "First Student"
    And I should see "User details"
    And I should see "Today's logs"

  Scenario: tool_mulib behat step: I confirm changed email for user
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | student  | Some      | Student  | student@example.com |

    When I log in as "student"
    And I open my profile in edit mode
    And I set the following fields to these values:
      | Email address | somestudent@example.com |
    And I press "Update profile"
    And I should see "from student@example.com to somestudent@example.com"
    And I press "Continue"
    And I confirm changed email for "student"
    And I should see "was successfully updated to somestudent@example.com"
