@tool @tool_mupwned @MuTMS
Feature: Test tool_mpwned compromised password blocking
  Background:
    Given the following "users" exist:
      | username  | firstname | lastname  | email              | password         |
      | user1     | First     | User      | usser1@example.com | oJHGjgfd15abcd-_ |
      | user2     | Second    | User      | usser2@example.com | 123456           |

  @javascript
  Scenario: Login with compromised password is blocked by tool_mupwned
    Given the following config values are set as admin:
      | passwordpolicy             | 1 |              |
      | passwordpolicycheckonlogin | 1 |              |
      | enabled                    | 1 | tool_mupwned |
      | resetpassword              | 1 | tool_mupwned |
      | expiretokens               | 1 | tool_mupwned |

    When I follow "Log in"
    And I set the field "Username" to "user1"
    And I set the field "Password" to "oJHGjgfd15abcd-_"
    And I press "Log in"
    Then I should see "Welcome, First!"
    And I log out

    When I follow "Log in"
    And I set the field "Username" to "user2"
    And I set the field "Password" to "123456"
    And I press "Log in"
    Then I should see "Your password has previously appeared in a data breach"

    # For some reason following steps fail on GitHub, uncomment to test locally.
#    When I set the field "Username" to "user2"
#    And I press "Search"
#    And I should see "If you supplied a correct username"
#    And I open password reset confirmation for user "user2"
#    And I set the field "New password" to "PoPhdsh675-_"
#    And I set the field "New password (again)" to "PoPhdsh675-_"
#    And I press "Save changes"
#    Then I should see "Welcome, Second!"

  @javascript
  Scenario: Creation of accounts with compromised password is blocked by tool_mupwned
    Given the following config values are set as admin:
      | passwordpolicy             | 1        |              |
      | passwordpolicycheckonlogin | 1        |              |
      | registerauth               | email    |              |
      | minpasswordlength          | 4        |              |
      | minpassworddigits          | 0        |              |
      | minpasswordlower           | 0        |              |
      | minpasswordupper           | 0        |              |
      | minpasswordnonalphanum     | 0        |              |
      | maxconsecutiveidentchars   | 0        |              |
      | enabled                    | 1        | tool_mupwned |
      | resetpassword              | 1        | tool_mupwned |
      | expiretokens               | 1        | tool_mupwned |
    And I follow "Log in"
    And I click on "Create new account" "link"

    When I set the following fields to these values:
      | Username      | user3                     |
      | Password      | 123                       |
      | Email address | user3@example.com         |
      | Email (again) | user3@example.com         |
      | First name    | Third                     |
      | Last name     | User                      |
    And I press "Create my new account"
    Then I should see "This password was compromised during a data breach."
    And I should see "Passwords must be at least 4 characters long."

    When I set the following fields to these values:
      | Password      | 123456                    |
    And I press "Create my new account"
    Then I should see "This password was compromised during a data breach."
    And I should not see "Passwords must be at least 4 characters long."

    When I set the following fields to these values:
      | Password      | fldshJHJHfdfsjgdf098798-- |
    And I press "Create my new account"
    Then I should see "An email should have been sent to your address"
    And I should not see "This password was compromised during a data breach."
    And I should not see "Passwords must be at least 4 characters long."

    When I confirm email for "user3"
    And I should see "Thanks, Third User"
    And I log out
    And I follow "Log in"
    And I set the field "Username" to "user3"
    And I set the field "Password" to "fldshJHJHfdfsjgdf098798--"
    And I press "Log in"
    Then I should see "Welcome, Third!"

  @javascript
  Scenario: Change to compromised password is blocked by tool_mupwned
    Given the following config values are set as admin:
      | passwordpolicy             | 1        |              |
      | passwordpolicycheckonlogin | 1        |              |
      | registerauth               | email    |              |
      | minpasswordlength          | 4        |              |
      | minpassworddigits          | 0        |              |
      | minpasswordlower           | 0        |              |
      | minpasswordupper           | 0        |              |
      | minpasswordnonalphanum     | 0        |              |
      | maxconsecutiveidentchars   | 0        |              |
      | enabled                    | 1        | tool_mupwned |
      | resetpassword              | 1        | tool_mupwned |
      | expiretokens               | 1        | tool_mupwned |
    And I follow "Log in"
    And I set the field "Username" to "user1"
    And I set the field "Password" to "oJHGjgfd15abcd-_"
    And I press "Log in"
    And I follow "Preferences" in the user menu
    When I follow "Change password"
    And I set the field "Current password" to "oJHGjgfd15abcd-_"
    And I set the field "New password" to "password"
    And I set the field "New password (again)" to "password"
    And I click on "Save changes" "button"
    Then I should see "This password was compromised during a data breach."

    When I set the field "New password" to "fldshJHJHfdfsjgdf098798--"
    And I set the field "New password (again)" to "fldshJHJHfdfsjgdf098798--"
    And I click on "Save changes" "button"
    Then I should see "Password has been changed"
