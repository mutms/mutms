@tool @tool_mutenancy @MuTMS @javascript
Feature: Tenant authentication setting registerauth
  Background:
    Given unnecessary Admin bookmarks block gets deleted
    And the following "tool_mutenancy > tenants" exist:
      | name     | idnumber | loginshow | sitefullname     | siteshortname |
      | Tenant 1 | TEN1     | 1         | Tent Site full 1 | TSS1          |
      | Tenant 2 | TEN2     | 1         | Tent Site full 2 | TSS2          |
      | Tenant 3 | TEN3     | 1         | Tent Site full 3 | TSS3          |
    And the following config values are set as admin:
      | registerauth   | email |
      | passwordpolicy | 0     |
    And I log in as "admin"
    And I am on the "TEN2" "tool_mutenancy > Tenant authentication" page
    And I should see "Default value (Email-based self-registration)" in the "Self registration" definition list item
    And I press "Update authentication"
    And I set the following fields to these values:
      | registerauth_override   | 1                   |
      | registerauth            | Disabled            |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I should see "Disabled" in the "Self registration" definition list item
    And I am on the "TEN3" "tool_mutenancy > Tenant authentication" page
    And I should see "Default value (Email-based self-registration)" in the "Self registration" definition list item
    And I press "Update authentication"
    And I set the following fields to these values:
      | registerauth_override   | 1                   |
      | registerauth            | email               |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I should see "Email-based self-registration" in the "Self registration" definition list item
    And I log out

  Scenario: Users may self register as tenant members
    When I am on the "0" "tool_mutenancy > Tenant login" page
    And I click on "Create new account" "link"
    And I should see "Acceptance test site"
    And I set the following fields to these values:
      | Username      | siteuser0             |
      | Password      | siteuser0             |
      | Email address | siteuser0@example.com |
      | Email (again) | siteuser0@example.com |
      | First name    | Nulty                 |
      | Last name     | Siteuser              |
    And I press "Create my new account"
    And I should see "An email should have been sent to your address at siteuser0@example.com"
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
      | Username      | tenantuser1             |
      | Password      | tenantuser1             |
      | Email address | tenantuser1@example.com |
      | Email (again) | tenantuser1@example.com |
      | First name    | Prvni                   |
      | Last name     | Tenantuser              |
    And I press "Create my new account"
    And I should see "An email should have been sent to your address at tenantuser1@example.com"
    And I should see "Tent Site full 1"
    And I confirm email for "tenantuser1"
    Then I should see "Thanks, Prvni Tenantuser"
    And I should see "Your registration has been confirmed"
    And I log in as "tenantuser1"
    And I should see "Welcome, Prvni!"
    And I should see "TSS1" in the ".navbar" "css_element"
    And I log out

    When I am on the "TEN2" "tool_mutenancy > Tenant login" page
    Then I should not see "Create new account"

    When I am on the "TEN3" "tool_mutenancy > Tenant login" page
    And I click on "Create new account" "link"
    And I set the following fields to these values:
      | Username      | tenantuser3             |
      | Password      | tenantuser3             |
      | Email address | tenantuser3@example.com |
      | Email (again) | tenantuser3@example.com |
      | First name    | Treti                   |
      | Last name     | Tenantuser              |
    And I press "Create my new account"
    And I should see "An email should have been sent to your address at tenantuser3@example.com"
    And I should see "Tent Site full 3"
    And I confirm email for "tenantuser3"
    Then I should see "Thanks, Treti Tenantuser"
    And I should see "Your registration has been confirmed"
    And I log in as "tenantuser3"
    And I should see "Welcome, Treti!"
    And I should see "TSS3" in the ".navbar" "css_element"
    And I log out

    When the following config values are set as admin:
      | registerauth   |  |

    When I am on the "0" "tool_mutenancy > Tenant login" page
    Then I should not see "Create new account"

    When I am on the "TEN1" "tool_mutenancy > Tenant login" page
    Then I should not see "Create new account"

    When I am on the "TEN2" "tool_mutenancy > Tenant login" page
    Then I should not see "Create new account"

    When I am on the "TEN3" "tool_mutenancy > Tenant login" page
    And I click on "Create new account" "link"
    And I set the following fields to these values:
      | Username      | tenantuser4             |
      | Password      | tenantuser4             |
      | Email address | tenantuser4@example.com |
      | Email (again) | tenantuser4@example.com |
      | First name    | Ctvrty                  |
      | Last name     | Tenantuser              |
    And I press "Create my new account"
    And I should see "An email should have been sent to your address at tenantuser4@example.com"
    And I should see "Tent Site full 3"
    And I confirm email for "tenantuser4"
    Then I should see "Thanks, Ctvrty Tenantuser"
    And I should see "Your registration has been confirmed"
    And I log in as "tenantuser4"
    And I should see "Welcome, Ctvrty!"
    And I should see "TSS3" in the ".navbar" "css_element"
    And I log out
