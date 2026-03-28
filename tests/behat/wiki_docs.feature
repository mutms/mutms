@tool @tool_mucertify @MuTMS @javascript
Feature: Certifications plugin English wiki documentation image generator

  Background:
    Given site is prepared for documentation screenshots
    And the following "categories" exist:
      | name                    | category | idnumber |
      | Health and safety       | 0        | HS       |
      | IT                      | 0        | IT       |
      | Employee certifications | 0        | EC       |
    And the following "cohorts" exist:
      | name          | idnumber  |
      | IT staff      | itstaff   |
      | All employees | everybody |
    And the following "tool_muprog > programs" exist:
      | fullname                             | idnumber | category | publicaccess | sources   |
      | Health and safety - new employees    | HS1      | HS       | 1            | mucertify |
      | Health and safety - recertification  | HS2      | HS       | 1            | mucertify |
      | GDPR basics                          | GDPR1    | IT       | 1            | mucertify |
      | Cybersecurity                        | CS1      | IT       | 1            | mucertify |
    And the following "tool_mucertify > certifications" exist:
      | fullname          | idnumber | category | publicaccess | program1 | program2| recertify | sources  | image                                            | cohorts  | description                                                  |
      | Health and safety | CFHS     | EC       | 1            | HS1      | HS2     | 2592000   | manual   | admin/tool/mucertify/tests/fixtures/docs/hs.jpeg |          | Mandatory Health and Safety certification for all employees. |
      | Customer privacy  | CP       | EC       | 0            | GDPR1    | GDPR1   | 2592000   | manual   |                                                  |          |                                                              |
      | Cybersecurity     | CS       | EC       | 0            | CS1      | CS1     | 2592000   | manual   | admin/tool/mucertify/tests/fixtures/docs/cs.jpeg | IT staff | IT security certification.                                                             |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | manager  | Site      | Manager  | manager@example.com  |
      | a        | User      | A        | a@example.com        |
      | b        | User      | B        | b@example.com        |
      | c        | User      | C        | c@example.com        |
      | d        | User      | D        | d@example.com        |
      | e        | User      | E        | e@example.com        |
      | f        | User      | F        | f@example.com        |
      | g        | User      | G        | g@example.com        |
      | h        | User      | H        | h@example.com        |
      | i        | User      | I        | i@example.com        |
      | j        | User      | J        | j@example.com        |
      | k        | User      | K        | k@example.com        |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | manager   | manager       | System       |           |
    And the following "tool_mucertify > certification_assignments" exist:
      | user    | certification     | timecreated            | timecertifiedtemp      | noperiod |
      | manager | Health and safety | ## 2022-03-01 10:00 ## | ## 2026-03-01 10:00 ## |          |
      | a       | Health and safety |                        |                        | 1        |
      | b       | Health and safety |                        |                        |          |
      | c       | Health and safety |                        |                        |          |
      | d       | Health and safety |                        |                        |          |
      | e       | Health and safety |                        |                        |          |
      | f       | Health and safety |                        |                        |          |
      | g       | Health and safety |                        |                        |          |
      | h       | Health and safety |                        |                        |          |
      | i       | Health and safety |                        |                        |          |
      | j       | Health and safety |                        |                        |          |
      | k       | Health and safety |                        |                        |          |
      | manager | Customer privacy  |                        |                        |          |
      | b       | Customer privacy  |                        |                        |          |
      | c       | Customer privacy  |                        |                        |          |
      | d       | Customer privacy  |                        |                        |          |
      | manager | Cybersecurity     |                        |                        |          |
      | a       | Cybersecurity     |                        |                        |          |

  Scenario: Documentation screenshots for certifications management_index page
    Given I log in as "manager"
    And I am on the "tool_mucertify > All certifications management" page

    Then I make documentation screenshot "img_certifications.png" for "tool_mucertify" plugin
    And site is restored after documentation screenshots

  Scenario: Documentation screenshots for management_certification page
    Given I log in as "manager"
    And I am on the "tool_mucertify > All certifications management" page
    And I follow "Health and safety"

    Then I make documentation screenshot "img_certification_general.png" for "tool_mucertify" plugin
    And site is restored after documentation screenshots

  Scenario: Documentation screenshots for management_certification_settings page
    Given I log in as "manager"
    And I am on the "tool_mucertify > All certifications management" page
    And I follow "Health and safety"
    And I follow "Period settings"
    And I click on "Update certification" "link"
    And I set the following fields to these values:
      | expiration1[since]    | Certification completion date         |
      | expiration1[number]   | 12                                    |
      | expiration1[timeunit] | Months                                |
    And I click on "Update certification" "button" in the ".modal-dialog" "css_element"
    And I click on "Update re-certification" "link"
    And I set the following fields to these values:
      | expiration2[since]    | Certification completion date         |
      | expiration2[number]   | 12                                    |
      | expiration2[timeunit] | Months                                |
      | resettype2            | Full course purge                     |
    And I click on "Update re-certification" "button" in the ".modal-dialog" "css_element"
    And I change window size to "1208x1000"

    Then I make documentation screenshot "img_certification_settings.png" for "tool_mucertify" plugin
    And site is restored after documentation screenshots

  Scenario: Documentation screenshots for management_certification_visibility page
    Given I log in as "manager"
    And I am on the "tool_mucertify > All certifications management" page
    And I follow "Cybersecurity"
    And I follow "Catalogue visibility"

    Then I make documentation screenshot "img_certification_visibility.png" for "tool_mucertify" plugin
    And site is restored after documentation screenshots

  Scenario: Documentation screenshots for management_certification_assignment page
    Given I log in as "manager"
    And I am on the "tool_mucertify > All certifications management" page
    And I follow "Health and safety"
    And I follow "Assignment settings"
    And I click on "Update Automatic cohort assignment" "link"
    And I set the following fields to these values:
      | Active            | Yes           |
      | Assign to cohorts | All employees |
    And I click on "Update" "button" in the ".modal-dialog" "css_element"

    Then I make documentation screenshot "img_certification_assignment.png" for "tool_mucertify" plugin
    And site is restored after documentation screenshots

  Scenario: Documentation screenshots for management_certification_users page
    Given I log in as "manager"
    And I am on the "tool_mucertify > All certifications management" page
    And I follow "Health and safety"
    And I follow "Users"
    And I follow "User A"
    And I press "Add period"
    And I set the following fields to these values:
      | timewindowstart[day]     | 5           |
      | timewindowstart[month]   | 3           |
      | timewindowstart[year]    | 2024        |
      | timewindowstart[hour]    | 09          |
      | timewindowstart[minute]  | 00          |
      | timefrom[enabled]        | 1           |
      | timefrom[day]            | 7           |
      | timefrom[month]          | 3           |
      | timefrom[year]           | 2024        |
      | timefrom[hour]           | 09          |
      | timefrom[minute]         | 00          |
      | timeuntil[enabled]       | 1           |
      | timeuntil[day]           | 7           |
      | timeuntil[month]         | 3           |
      | timeuntil[year]          | 2025        |
      | timeuntil[hour]          | 09          |
      | timeuntil[minute]        | 00          |
    And I click on "Add period" "button" in the ".modal-dialog" "css_element"
    And I follow "5/03/24"
    And I press "Override period dates"
    And I set the following fields to these values:
      | timecertified[enabled] | 1         |
      | timecertified[day]     | 7         |
      | timecertified[month]   | 3         |
      | timecertified[year]    | 2024      |
      | timecertified[hour]    | 09        |
      | timecertified[minute]  | 00        |
    And I click on "Override period dates" "button" in the ".modal-dialog" "css_element"

    When I follow "Users"
    Then I make documentation screenshot "img_certification_users.png" for "tool_mucertify" plugin

    When I follow "User A"
    And I change window size to "1208x1000"
    Then I make documentation screenshot "img_assignment.png" for "tool_mucertify" plugin
    And I change window size to "1208x780"

    When I log in as "a"
    And I am on the "tool_mucertify > My certifications" page
    Then I make documentation screenshot "img_profile_my_certifications.png" for "tool_mucertify" plugin

    When I follow "Health and safety"
    And I change window size to "1208x1000"
    Then I make documentation screenshot "img_profile_my_certification.png" for "tool_mucertify" plugin
    And I change window size to "1208x780"

    When I am on the "tool_mucertify > My certifications" page
    And I follow "Certification catalogue"
    Then I make documentation screenshot "img_catalogue.png" for "tool_mucertify" plugin

    When I skip tests if "block_mucertify_my" is not installed
    And I follow "Dashboard"
    And I turn editing mode on
    And I open the "Recently accessed items" blocks action menu
    And I follow "Delete Recently accessed items block"
    And I click on "Delete" "button" in the "Delete block?" "dialogue"
    And I open the "Timeline" blocks action menu
    And I follow "Delete Timeline block"
    And I click on "Delete" "button" in the "Delete block?" "dialogue"
    And I open the "Calendar" blocks action menu
    And I follow "Delete Calendar block"
    And I click on "Delete" "button" in the "Delete block?" "dialogue"
    And I add the "My certifications" block to the "content" region
    And I turn editing mode off
    Then I make documentation screenshot "img_dashboard_my_certifications.png" for "tool_mucertify" plugin

    And site is restored after documentation screenshots
