@tool @tool_mutenancy @MuTMS @javascript
Feature: Tenant authentication setting allowemailaddresses
  Background:
    Given unnecessary Admin bookmarks block gets deleted
    And the following "tool_mutenancy > tenants" exist:
      | name     | idnumber | loginshow | sitefullname     | siteshortname |
      | Tenant 1 | TEN1     | 1         | Tent Site full 1 | TSS1          |
      | Tenant 2 | TEN2     | 1         | Tent Site full 2 | TSS2          |
      | Tenant 3 | TEN3     | 1         | Tent Site full 3 | TSS3          |
    And the following config values are set as admin:
      | registerauth        | email           |
      | passwordpolicy      | 0               |
      | allowemailaddresses | xxx.example.com |
    And I log in as "admin"
    And I am on the "TEN2" "tool_mutenancy > Tenant authentication" page
    And I should see "Default value (xxx.example.com)" in the "Allowed email domains" definition list item
    And I press "Update authentication"
    And I set the following fields to these values:
      | allowemailaddresses_override   | 1                   |
      | allowemailaddresses            | yyy.example.com     |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I should see "yyy.example.com" in the "Allowed email domains" definition list item
    And I am on the "TEN3" "tool_mutenancy > Tenant authentication" page
    And I should see "Default value (xxx.example.com)" in the "Allowed email domains" definition list item
    And I press "Update authentication"
    And I set the following fields to these values:
      | allowemailaddresses_override   | 1                   |
      | allowemailaddresses            |                     |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I should see "Empty" in the "Allowed email domains" definition list item
    And I log out

  Scenario: Users may self register with email from allowemailaddresses overridden for tenant
    When I am on the "0" "tool_mutenancy > Tenant login" page
    And I click on "Create new account" "link"
    And I should see "Acceptance test site"
    And I set the following fields to these values:
      | Username      | siteuser0                 |
      | Password      | siteuser0                 |
      | Email address | siteuser0@zzz.example.com |
      | Email (again) | siteuser0@zzz.example.com |
      | First name    | Nulty                     |
      | Last name     | Siteuser                  |
    And I press "Create my new account"
    Then I should see "This email cannot be used. Allowed email domains are: xxx.example.com."
    And I set the following fields to these values:
      | Email address | siteuser0@xxx.example.com |
      | Email (again) | siteuser0@xxx.example.com |
    And I press "Create my new account"
    And I should see "An email should have been sent to your address at siteuser0@xxx.example.com"
    And I should see "Acceptance test site"
    And I confirm email for "siteuser0"
    Then I should see "Thanks, Nulty Siteuser"
    And I should see "Your registration has been confirmed"
    And I log in as "siteuser0"
    And I should see "Welcome, Nulty!"
    And I should see "Acceptance test site" in the ".navbar" "css_element"
    And I log out

    When I am on the "TEN1" "tool_mutenancy > Tenant login" page
    And I click on "Create new account" "link"
    And I should see "Tent Site full 1"
    And I set the following fields to these values:
      | Username      | tenantuser1                 |
      | Password      | tenantuser1                 |
      | Email address | tenantuser1@zzz.example.com |
      | Email (again) | tenantuser1@zzz.example.com |
      | First name    | Prvni                       |
      | Last name     | tenantuser                  |
    And I press "Create my new account"
    Then I should see "This email cannot be used. Allowed email domains are: xxx.example.com."
    And I set the following fields to these values:
      | Email address | tenantuser1@xxx.example.com |
      | Email (again) | tenantuser1@xxx.example.com |
    And I press "Create my new account"
    And I should see "An email should have been sent to your address at tenantuser1@xxx.example.com"
    And I should see "Tent Site full 1"
    And I confirm email for "tenantuser1"
    Then I should see "Thanks, Prvni tenantuser"
    And I should see "Your registration has been confirmed"
    And I log in as "tenantuser1"
    And I should see "Welcome, Prvni!"
    And I should see "TSS1" in the ".navbar" "css_element"
    And I log out

    When I am on the "TEN2" "tool_mutenancy > Tenant login" page
    And I click on "Create new account" "link"
    And I should see "Tent Site full 2"
    And I set the following fields to these values:
      | Username      | tenantuser2                 |
      | Password      | tenantuser2                 |
      | Email address | tenantuser2@zzz.example.com |
      | Email (again) | tenantuser2@zzz.example.com |
      | First name    | Druhy                       |
      | Last name     | tenantuser                  |
    And I press "Create my new account"
    Then I should see "This email cannot be used. Allowed email domains are: yyy.example.com."
    And I set the following fields to these values:
      | Email address | tenantuser2@yyy.example.com |
      | Email (again) | tenantuser2@yyy.example.com |
    And I press "Create my new account"
    And I should see "An email should have been sent to your address at tenantuser2@yyy.example.com"
    And I should see "Tent Site full 2"
    And I confirm email for "tenantuser2"
    Then I should see "Thanks, Druhy tenantuser"
    And I should see "Your registration has been confirmed"
    And I log in as "tenantuser2"
    And I should see "Welcome, Druhy!"
    And I should see "TSS2" in the ".navbar" "css_element"
    And I log out

    When I am on the "TEN3" "tool_mutenancy > Tenant login" page
    And I click on "Create new account" "link"
    And I should see "Tent Site full 3"
    And I set the following fields to these values:
      | Username      | tenantuser3                 |
      | Password      | tenantuser3                 |
      | Email address | tenantuser3@zzz.example.com |
      | Email (again) | tenantuser3@zzz.example.com |
      | First name    | Treti                       |
      | Last name     | tenantuser                  |
    And I press "Create my new account"
    And I should see "An email should have been sent to your address at tenantuser3@zzz.example.com"
    And I should see "Tent Site full 3"
    And I confirm email for "tenantuser3"
    Then I should see "Thanks, Treti tenantuser"
    And I should see "Your registration has been confirmed"
    And I log in as "tenantuser3"
    And I should see "Welcome, Treti!"
    And I should see "TSS3" in the ".navbar" "css_element"
    And I log out

  Scenario: Users may change email to value from allowemailaddresses overridden for tenant
    Given the following "users" exist:
      | username | firstname | lastname | email                | tenant |
      | student0 | Nulty     | Student  | student0@example.com |        |
      | student1 | Prvni     | Student  | student1@example.com | TEN1   |
      | student2 | Druhy     | Student  | student2@example.com | TEN2   |
      | student3 | Treti     | Student  | student3@example.com | TEN3   |

    When I log in as "student0"
    And I open my profile in edit mode
    And I set the following fields to these values:
      | Email address | student0@zzz.example.com |
    And I press "Update profile"
    Then I should see "This email cannot be used. Allowed email domains are: xxx.example.com."
    And I set the following fields to these values:
      | Email address | student0@xxx.example.com |
    And I press "Update profile"
    And I should see "from student0@example.com to student0@xxx.example.com"
    And I press "Continue"
    And I confirm changed email for "student0"
    And I should see "was successfully updated to student0@xxx.example.com"

    When I log in as "student1"
    And I open my profile in edit mode
    And I set the following fields to these values:
      | Email address | student1@zzz.example.com |
    And I press "Update profile"
    Then I should see "This email cannot be used. Allowed email domains are: xxx.example.com."
    And I set the following fields to these values:
      | Email address | student1@xxx.example.com |
    And I press "Update profile"
    And I should see "from student1@example.com to student1@xxx.example.com"
    And I press "Continue"
    And I confirm changed email for "student1"
    And I should see "was successfully updated to student1@xxx.example.com"

    When I log in as "student2"
    And I open my profile in edit mode
    And I set the following fields to these values:
      | Email address | student2@zzz.example.com |
    And I press "Update profile"
    Then I should see "This email cannot be used. Allowed email domains are: yyy.example.com."
    And I set the following fields to these values:
      | Email address | student2@yyy.example.com |
    And I press "Update profile"
    And I should see "from student2@example.com to student2@yyy.example.com"
    And I press "Continue"
    And I confirm changed email for "student2"
    And I should see "was successfully updated to student2@yyy.example.com"

    When I log in as "student3"
    And I open my profile in edit mode
    And I set the following fields to these values:
      | Email address | student3@zzz.example.com |
    And I press "Update profile"
    And I should see "from student3@example.com to student3@zzz.example.com"
    And I press "Continue"
    And I confirm changed email for "student3"
    And I should see "was successfully updated to student3@zzz.example.com"

  Scenario: Tenant managers may change emails ignoring allowemailaddresses
    Given the following "users" exist:
      | username | firstname | lastname | email                | tenant |
      | student1 | Prvni     | Student  | student1@example.com | TEN1   |
      | manager1 | Prvni     | Manager  | manager1@example.com | TEN1   |
    And the following "tool_mutenancy > tenant managers" exist:
      | tenant | user     |
      | TEN1   | manager1 |
    And I log in as "manager1"
    And I am on the "TEN1" "tool_mutenancy > Tenant users" page
    And I click on "Actions" "link" in the "Prvni Student" "table_row"
    And I click on "Edit" "link" in the "Prvni Student" "table_row"
    When I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Email address | student1@zzz.example.com |
    And I click on "Update account" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | First name    | Email address            | Tenant member |
      | Prvni Student | student1@zzz.example.com | Yes           |
