@tool @tool_mutenancy @MuTMS @javascript
Feature: Tenant appearance logos
  Background:
    Given unnecessary Admin bookmarks block gets deleted
    And the following "tool_mutenancy > tenants" exist:
      | name     | idnumber | loginshow | sitefullname     | siteshortname |
      | Tenant 1 | TEN1     | 1         | Tent Site full 1 | TSS1          |
      | Tenant 2 | TEN2     | 1         | Tent Site full 2 | TSS2          |
      | Tenant 3 | TEN3     | 1         | Tent Site full 3 | TSS3          |
    And the following "users" exist:
      | username  | firstname | lastname  | email               |
      | manager   | Tenant    | Manager   | manager@example.com |
    And the following "tool_mutenancy > tenant managers" exist:
      | tenant | user    |
      | TEN1   | manager |
      | TEN2   | manager |
      | TEN3   | manager |

    # Behat does not reset theme CSS properly between tests,
    # try to work around it by resetting caches before any CSS related changes...
    And I log in as "admin"
    And I follow "Purge all caches"
    And I navigate to "Appearance > Theme" in site administration
    And I press "Clear theme caches"
    And I log out

  @_file_upload @_visual_check
  Scenario: Tenant managers may override site logos for tenants
    Given I log in as "manager"

    # NOTE: For some reason behat struggles with uploading multiple files in one form...

    When I am on the "TEN1" "tool_mutenancy > Tenant appearance" page
    And I press "Edit logos"
    And I set the field "logo_override" to "1"
    And I upload "admin/tool/mutenancy/tests/fixtures/logo_red.png" file to "Logo" filemanager
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I press "Edit logos"
    And I set the field "logocompact_override" to "1"
    And I upload "admin/tool/mutenancy/tests/fixtures/logo_green.png" file to "Compact logo" filemanager
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I press "Edit logos"
    And I set the field "favicon_override" to "1"
    And I upload "admin/tool/mutenancy/tests/fixtures/logo_blue.png" file to "Favicon" filemanager
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    Then I should not see "Default" in the "Logo" definition list item
    And I should not see "Default" in the "Compact logo" definition list item
    And I should not see "Default" in the "Favicon" definition list item

    When I am on the "TEN2" "tool_mutenancy > Tenant appearance" page
    And I press "Edit logos"
    And I set the field "logo_override" to "1"
    And I set the field "logocompact_override" to "1"
    And I set the field "favicon_override" to "1"
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    Then I should see "None" in the "Logo" definition list item
    And I should see "None" in the "Compact logo" definition list item
    And I should not see "Default" in the "Logo" definition list item
    And I should not see "Default" in the "Compact logo" definition list item
    And I should not see "Default" in the "Favicon" definition list item
    And I should not see "None" in the "Favicon" definition list item

    When I am on the "TEN3" "tool_mutenancy > Tenant appearance" page
    And I should see "Default value (None)" in the "Logo" definition list item
    And I should see "Default value (None)" in the "Compact logo" definition list item
    And I should see "Default value" in the "Favicon" definition list item
    And I should not see "None" in the "Favicon" definition list item

    And I log out
    And I log in as "admin"

    And I navigate to "Appearance > Logos" in site administration
    And I upload "admin/tool/mutenancy/tests/fixtures/logo_magenta.png" file to "Logo" filemanager
    And I press "Save changes"
    And I upload "admin/tool/mutenancy/tests/fixtures/logo_yellow.png" file to "Compact logo" filemanager
    And I press "Save changes"
    And I upload "admin/tool/mutenancy/tests/fixtures/logo_cyan.png" file to "Favicon" filemanager
    And I press "Save changes"

    And I am on homepage
    And I log out
    And I log in as "manager"

    When I am on the "TEN1" "tool_mutenancy > Tenant appearance" page
    Then I should not see "Default" in the "Logo" definition list item
    And I should not see "Default" in the "Compact logo" definition list item
    And I should not see "Default" in the "Favicon" definition list item
    And I should not see "None" in the "Logo" definition list item
    And I should not see "None" in the "Compact logo" definition list item
    And I should not see "None" in the "Favicon" definition list item

    When I am on the "TEN2" "tool_mutenancy > Tenant appearance" page
    Then I should see "None" in the "Logo" definition list item
    And I should see "None" in the "Compact logo" definition list item
    And I should not see "None" in the "Favicon" definition list item
    And I should not see "Default" in the "Logo" definition list item
    And I should not see "Default" in the "Compact logo" definition list item
    And I should not see "Default" in the "Favicon" definition list item

    When I am on the "TEN3" "tool_mutenancy > Tenant appearance" page
    And I should see "Default value" in the "Logo" definition list item
    And I should see "Default value" in the "Compact logo" definition list item
    And I should see "Default value" in the "Favicon" definition list item
    And I should not see "None" in the "Logo" definition list item
    And I should not see "None" in the "Compact logo" definition list item
    And I should not see "None" in the "Favicon" definition list item

    When I am on the "TEN1" "tool_mutenancy > Tenant appearance" page
    And I perform a visual check "I should see Red, Green, Blue logos in Tenant 1 appearance settings"

    When I am on the "TEN2" "tool_mutenancy > Tenant appearance" page
    Then I perform a visual check "I should not see any logos except favicon in Tenant 2 appearance settings"

    When I am on the "TEN3" "tool_mutenancy > Tenant appearance" page
    Then I perform a visual check "I should see site default Magenta, Yellow and Cyan logos in Tenant 3 appearance settings"

    And I log out
    And I log in as "admin"

    When I am on homepage
    Then I perform a visual check "I should see Yellow logo in navbar and Cyan favicon"

    When I click on "Switch tenant" "link" in the ".navbar" "css_element"
    And I set the following fields to these values:
      | Tenant      | Tenant 1         |
    And I click on "Switch tenant" "button" in the ".modal-dialog" "css_element"
    Then I perform a visual check "I should see Green logo in navbar and Blue favicon"

    When I click on "Switch tenant" "link" in the ".navbar" "css_element"
    And I set the following fields to these values:
      | Tenant      | Tenant 2         |
    And I click on "Switch tenant" "button" in the ".modal-dialog" "css_element"
    Then I perform a visual check "I should see not see logo in navbar and favicon is standard hat"

    When I click on "Switch tenant" "link" in the ".navbar" "css_element"
    And I set the following fields to these values:
      | Tenant      | Tenant 3         |
    And I click on "Switch tenant" "button" in the ".modal-dialog" "css_element"
    Then I perform a visual check "I should see Yellow logo in navbar and Cyan favicon"

    And I log out

    When I am on the "0" "tool_mutenancy > Tenant login" page
    Then I perform a visual check "I should see Magenta logo"

    When I am on the "TEN1" "tool_mutenancy > Tenant login" page
    Then I perform a visual check "I should see Red logo"

    When I am on the "TEN2" "tool_mutenancy > Tenant login" page
    Then I perform a visual check "I should not see any logo"

    When I am on the "TEN3" "tool_mutenancy > Tenant login" page
    Then I perform a visual check "I should see Magenta logo"
