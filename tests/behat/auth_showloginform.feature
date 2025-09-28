@tool @tool_mutenancy @MuTMS @javascript
Feature: Tenant authentication setting showloginform
  Background:
    Given unnecessary Admin bookmarks block gets deleted
    And the following "tool_mutenancy > tenants" exist:
      | name     | idnumber | loginshow | sitefullname     | siteshortname |
      | Tenant 1 | TEN1     | 1         | Tent Site full 1 | TSS1          |
      | Tenant 2 | TEN2     | 1         | Tent Site full 2 | TSS2          |
      | Tenant 3 | TEN3     | 1         | Tent Site full 3 | TSS3          |
    And the following config values are set as admin:
      | showloginform   | 1 |
      | passwordpolicy  | 0 |
    And I log in as "admin"
    And I am on the "TEN2" "tool_mutenancy > Tenant authentication" page
    And I should see "Default value (Yes)" in the "Display manual login form" definition list item
    And I press "Update authentication"
    And I set the following fields to these values:
      | showloginform_override   | 1 |
      | showloginform            | 0 |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I should see "No" in the "Display manual login form" definition list item
    And I am on the "TEN3" "tool_mutenancy > Tenant authentication" page
    And I should see "Default value (Yes)" in the "Display manual login form" definition list item
    And I press "Update authentication"
    And I set the following fields to these values:
      | showloginform_override   | 1 |
      | showloginform            | 1 |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I should see "Yes" in the "Display manual login form" definition list item
    And I log out

  Scenario: Users can see login form
    When I am on the "0" "tool_mutenancy > Tenant login" page
    Then I should see "Log in" in the "#loginbtn" "css_element"
    And I should see "Lost password?"

    When I am on the "TEN1" "tool_mutenancy > Tenant login" page
    Then I should see "Log in" in the "#loginbtn" "css_element"
    And I should see "Lost password?"

    When I am on the "TEN2" "tool_mutenancy > Tenant login" page
    And I should not see "Lost password?"

    When I am on the "TEN3" "tool_mutenancy > Tenant login" page
    Then I should see "Log in" in the "#loginbtn" "css_element"
    And I should see "Lost password?"

    And the following config values are set as admin:
      | showloginform   | 0 |

    When I am on the "0" "tool_mutenancy > Tenant login" page
    Then I should not see "Lost password?"

    When I am on the "TEN1" "tool_mutenancy > Tenant login" page
    Then I should not see "Lost password?"

    When I am on the "TEN2" "tool_mutenancy > Tenant login" page
    And I should not see "Lost password?"

    When I am on the "TEN3" "tool_mutenancy > Tenant login" page
    Then I should see "Log in" in the "#loginbtn" "css_element"
    And I should see "Lost password?"
