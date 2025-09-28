@tool @tool_mutenancy @MuTMS @javascript
Feature: Tenant Boost theme settings
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
  Scenario: Tenant managers may override safe Boost theme settings for tenants
    Given I log in as "manager"

      # NOTE: For some reason behat struggles with uploading multiple files in one form...

    When I am on the "TEN1" "tool_mutenancy > Tenant appearance" page
    And I press "Edit Boost"
    And I set the field "preset_override" to "1"
    And I set the field "preset" to "plain.scss"
    And I set the field "backgroundimage_override" to "1"
    And I upload "admin/tool/mutenancy/tests/fixtures/logo_red.png" file to "Background image" filemanager
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    And I press "Edit Boost"
    And I set the field "loginbackgroundimage_override" to "1"
    And I upload "admin/tool/mutenancy/tests/fixtures/logo_green.png" file to "Login page background image" filemanager
    And I set the field "brandcolor_override" to "1"
    And I set the field "brandcolor" to "blue"
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    Then I should see "plain.scss" in the "Theme preset" definition list item
    And I should not see "Default" in the "Theme preset" definition list item
    And I should see "blue" in the "Brand colour" definition list item
    And I should not see "Default" in the "Brand colour" definition list item
    And I should see "Default value (None)" in the "Raw initial SCSS" definition list item
    And I should see "Default value (None)" in the "Raw SCSS" definition list item

    When I am on the "TEN2" "tool_mutenancy > Tenant appearance" page
    And I press "Edit Boost"
    And I set the field "preset_override" to "1"
    And I set the field "backgroundimage_override" to "1"
    And I set the field "loginbackgroundimage_override" to "1"
    And I set the field "brandcolor_override" to "1"
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    Then I should see "default.scss" in the "Theme preset" definition list item
    And I should not see "Default" in the "Theme preset" definition list item
    And I should see "None" in the "Background image" definition list item
    And I should not see "Default" in the "Background image" definition list item
    And I should see "None" in the "Login page background image" definition list item
    And I should not see "Default" in the "Login page background image" definition list item
    And I should see "None" in the "Brand colour" definition list item
    And I should not see "Default" in the "Brand colour" definition list item

    And I am on the "TEN3" "tool_mutenancy > Tenant appearance" page
    And I should see "Default value (default.scss)" in the "Theme preset" definition list item
    And I should see "Default value (None)" in the "Background image" definition list item
    And I should see "Default value (None)" in the "Login page background image" definition list item
    And I should see "Default value (None)" in the "Brand colour" definition list item
    And I should see "Default value (None)" in the "Raw initial SCSS" definition list item
    And I should see "Default value (None)" in the "Raw SCSS" definition list item

    And I log out
    And I log in as "admin"

    And I navigate to "Appearance > Theme" in site administration
    And I follow "Edit theme settings 'Boost'"
    And I upload "admin/tool/mutenancy/tests/fixtures/logo_magenta.png" file to "Background image" filemanager
    And I press "Save changes"
    And I upload "admin/tool/mutenancy/tests/fixtures/logo_yellow.png" file to "Login page background image" filemanager
    And I press "Save changes"
    And I set the following fields to these values:
      | Theme preset | plain.scss |
      | Brand colour | cyan       |
    And I press "Save changes"

    And I am on homepage
    And I log out
    And I log in as "manager"

    When I am on the "TEN1" "tool_mutenancy > Tenant appearance" page
    Then I should see "plain.scss" in the "Theme preset" definition list item
    And I should not see "Default" in the "Theme preset" definition list item
    And I should see "blue" in the "Brand colour" definition list item
    And I should not see "Default" in the "Brand colour" definition list item
    And I should see "Default value (None)" in the "Raw initial SCSS" definition list item
    And I should see "Default value (None)" in the "Raw SCSS" definition list item

    When I am on the "TEN2" "tool_mutenancy > Tenant appearance" page
    Then I should see "default.scss" in the "Theme preset" definition list item
    And I should not see "Default" in the "Theme preset" definition list item
    And I should see "None" in the "Background image" definition list item
    And I should not see "Default" in the "Background image" definition list item
    And I should see "None" in the "Login page background image" definition list item
    And I should not see "Default" in the "Login page background image" definition list item
    And I should see "None" in the "Brand colour" definition list item
    And I should not see "Default" in the "Brand colour" definition list item

    And I am on the "TEN3" "tool_mutenancy > Tenant appearance" page
    And I should see "Default value (plain.scss)" in the "Theme preset" definition list item
    And I should see "Default value" in the "Background image" definition list item
    And I should not see "Empty" in the "Background image" definition list item
    And I should see "Default value" in the "Login page background image" definition list item
    And I should not see "Empty" in the "Login page background image" definition list item
    And I should see "cyan" in the "Brand colour" definition list item
    And I should see "Default value (None)" in the "Raw initial SCSS" definition list item
    And I should see "Default value (None)" in the "Raw SCSS" definition list item

    And I log out
    And I log in as "admin"

    When I am on homepage
    Then I perform a visual check "I should see Magenta background, Cyan brand colour and plain preset"

    When I click on "Switch tenant" "link" in the ".navbar" "css_element"
    And I set the following fields to these values:
      | Tenant      | Tenant 1         |
    And I click on "Switch tenant" "button" in the ".modal-dialog" "css_element"
    Then I perform a visual check "I should see Red background, Blue brand colour and plain preset"

    When I click on "Switch tenant" "link" in the ".navbar" "css_element"
    And I set the following fields to these values:
      | Tenant      | Tenant 2         |
    And I click on "Switch tenant" "button" in the ".modal-dialog" "css_element"
    Then I perform a visual check "I should see no background, no brand colour and standard preset"

    When I click on "Switch tenant" "link" in the ".navbar" "css_element"
    And I set the following fields to these values:
      | Tenant      | Tenant 3         |
    And I click on "Switch tenant" "button" in the ".modal-dialog" "css_element"
    Then I perform a visual check "I should see Magenta background, Cyan brand colour and plain preset"

    And I log out

    When I am on the "0" "tool_mutenancy > Tenant login" page
    Then I perform a visual check "I should see Yellow background"

    When I am on the "TEN1" "tool_mutenancy > Tenant login" page
    Then I perform a visual check "I should see Green background"

    When I am on the "TEN2" "tool_mutenancy > Tenant login" page
    Then I perform a visual check "I should not see any background"

    When I am on the "TEN3" "tool_mutenancy > Tenant login" page
    Then I perform a visual check "I should see Yellow background"

  @_file_upload @_visual_check
  Scenario: Admins may override unsafe Boost theme settings for tenants
    Given I log in as "admin"

    When I am on the "TEN1" "tool_mutenancy > Tenant appearance" page
    And I press "Edit Boost"
    And I set the field "scsspre_override" to "1"
    And I set the field "Raw initial SCSS" to "$body-color: pink;"
    And I set the field "scss_override" to "1"
    And I set the field "Raw SCSS" to "body {background-color: red}"
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    Then I should see "$body-color: pink;" in the "Raw initial SCSS" definition list item
    And I should not see "Default" in the "Raw initial SCSS" definition list item
    And I should see "body {background-color: red}" in the "Raw SCSS" definition list item
    And I should not see "Default" in the "Raw SCSS" definition list item

    When I am on the "TEN2" "tool_mutenancy > Tenant appearance" page
    And I press "Edit Boost"
    And I set the field "scsspre_override" to "1"
    And I set the field "scss_override" to "1"
    And I click on "Update" "button" in the ".modal-dialog" "css_element"
    Then I should see "None" in the "Raw initial SCSS" definition list item
    And I should not see "Default" in the "Raw initial SCSS" definition list item
    And I should see "None" in the "Raw SCSS" definition list item
    And I should not see "Default" in the "Raw SCSS" definition list item

    When I am on the "TEN3" "tool_mutenancy > Tenant appearance" page
    Then I should see "Default value (None)" in the "Raw initial SCSS" definition list item
    And I should see "Default value (None)" in the "Raw SCSS" definition list item

    And I navigate to "Appearance > Theme" in site administration
    And I follow "Edit theme settings 'Boost'"
    And I follow "Advanced settings"
    And I set the field "Raw initial SCSS" to "$body-color: orange;"
    And I set the field "Raw SCSS" to "body {background-color: green}"
    And I press "Save changes"

    When I am on the "TEN1" "tool_mutenancy > Tenant appearance" page
    Then I should see "$body-color: pink;" in the "Raw initial SCSS" definition list item
    And I should not see "Default" in the "Raw initial SCSS" definition list item
    And I should see "body {background-color: red}" in the "Raw SCSS" definition list item
    And I should not see "Default" in the "Raw SCSS" definition list item

    When I am on the "TEN2" "tool_mutenancy > Tenant appearance" page
    Then I should see "None" in the "Raw initial SCSS" definition list item
    And I should not see "Default" in the "Raw initial SCSS" definition list item
    And I should see "None" in the "Raw SCSS" definition list item
    And I should not see "Default" in the "Raw SCSS" definition list item

    When I am on the "TEN3" "tool_mutenancy > Tenant appearance" page
    Then I should see "Default value ($body-color: orange;)" in the "Raw initial SCSS" definition list item
    And I should see "Default value (body {background-color: green})" in the "Raw SCSS" definition list item

    When I am on homepage
    Then I perform a visual check "I should see Green background and Orange text"

    When I click on "Switch tenant" "link" in the ".navbar" "css_element"
    And I set the following fields to these values:
      | Tenant      | Tenant 1         |
    And I click on "Switch tenant" "button" in the ".modal-dialog" "css_element"
    Then I perform a visual check "I should see Red background and Pink text"

    When I click on "Switch tenant" "link" in the ".navbar" "css_element"
    And I set the following fields to these values:
      | Tenant      | Tenant 2         |
    And I click on "Switch tenant" "button" in the ".modal-dialog" "css_element"
    Then I perform a visual check "I should see no background, and normal text"

    When I click on "Switch tenant" "link" in the ".navbar" "css_element"
    And I set the following fields to these values:
      | Tenant      | Tenant 3         |
    And I click on "Switch tenant" "button" in the ".modal-dialog" "css_element"
    Then I perform a visual check "I should see Green background and Orange text"
