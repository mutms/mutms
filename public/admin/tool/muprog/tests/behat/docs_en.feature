@tool @tool_muprog @MuTMS @javascript
Feature: Programs plugin English documentation image generator

  Background:
    Given site is prepared for documentation screenshots
    And the following "categories" exist:
      | name                   | category | idnumber |
      | Health and safety      | 0        | HS       |
      | Mechanical engineering | 0        | ME       |
      | Weekend fun            | 0        | WF       |
    And the following "cohorts" exist:
      | name         | idnumber |
      | Petrol Heads | CH1      |
    And the following "courses" exist:
      | fullname                                  | shortname | category |
      | Course 1                                  | C1        | WF       |
      | Course 2                                  | C2        | WF       |
      | Course 3                                  | C3        | WF       |
      | Course 4                                  | C4        | WF       |
      | Course 5                                  | C5        | WF       |
      | First Aid Fundamentals                    | CFB1      | HS       |
      | Emergency First Aid Toolkit               | CFB2      | HS       |
      | Emergency Preparedness 101                | CFB3      | HS       |
      | Hands-On First Aid Training               | CFB4      | HS       |
      | Critical Care Made Simple                 | CFB5      | HS       |
      | Emergency Response Essentials             | CFB6      | HS       |
      | Beyond the Basics: Advanced First Aid     | CFA1      | HS       |
      | Handling High-Stakes Emergencies          | CFA2      | HS       |
      | Comprehensive Advanced First Aid          | CFA3      | HS       |
      | Life-Saving Techniques Masterclass        | CFA4      | HS       |
      | Pre-ride Checks                           | M1        | ME       |
      | Motorcycle Care 101                       | M2        | ME       |
      | Motorcycle Tyre Changing                  | M3        | ME       |
      | Chain and Sprocket Maintenance            | M4        | ME       |
    And the following "tool_muprog > programs" exist:
      | fullname                             | idnumber | category | publicaccess | archived | description                                     | image                                           | cohorts            |
      | Basic First Aid                      | FA1      | HS       | 1            | 0        | Sample program for basic first aid training.    | admin/tool/muprog/tests/fixtures/docs/bfa.jpeg  |                    |
      | Advanced First Aid                   | FA2      | HS       | 1            | 0        | Sample program for advanced first aid training. | admin/tool/muprog/tests/fixtures/docs/afa.jpeg  |                    |
      | Motorcycle Maintenance for Beginners | ME       | ME       | 1            | 0        | Basics of motorcycle maintenance.               | admin/tool/muprog/tests/fixtures/docs/mm.jpeg   |                    |
      | Motorcycle Track Days                | MTD      | WF       | 0            | 0        | Learn how to become a better track rider.       | admin/tool/muprog/tests/fixtures/docs/td.jpeg   | Petrol Heads       |
      | Horse Riding Trips                   | HRT      | WF       | 1            | 1        | Discontinued horse riding.                      |                                                 |                    |
    And the following "tool_muprog > program_items" exist:
      | program                              | parent            | course                                | fullname          | sequencetype     | minprerequisites |
      | Basic First Aid                      |                   |                                       | Mandatory courses | All in order     |                  |
      | Basic First Aid                      |                   |                                       | Optional courses  | At least X       | 2                |
      | Basic First Aid                      | Mandatory courses | First Aid Fundamentals                |                   |                  |                  |
      | Basic First Aid                      | Mandatory courses | Emergency First Aid Toolkit           |                   |                  |                  |
      | Basic First Aid                      | Optional courses  | Emergency Preparedness 101            |                   |                  |                  |
      | Basic First Aid                      | Optional courses  | Hands-On First Aid Training           |                   |                  |                  |
      | Basic First Aid                      | Optional courses  | Critical Care Made Simple             |                   |                  |                  |
      | Basic First Aid                      | Optional courses  | Emergency Response Essentials         |                   |                  |                  |
      | Advanced First Aid                   |                   |                                       | Mandatory courses | All in order     |                  |
      | Advanced First Aid                   | Mandatory courses | Beyond the Basics: Advanced First Aid |                   |                  |                  |
      | Advanced First Aid                   | Mandatory courses | Handling High-Stakes Emergencies      |                   |                  |                  |
      | Advanced First Aid                   | Mandatory courses | Comprehensive Advanced First Aid      |                   |                  |                  |
      | Advanced First Aid                   | Mandatory courses | Life-Saving Techniques Masterclass    |                   |                  |                  |
      | Motorcycle Maintenance for Beginners |                   | Pre-ride Checks                       |                   |                  |                  |
      | Motorcycle Maintenance for Beginners |                   | Motorcycle Care 101                   |                   |                  |                  |
      | Motorcycle Maintenance for Beginners |                   | Motorcycle Tyre Changing              |                   |                  |                  |
      | Motorcycle Maintenance for Beginners |                   | Chain and Sprocket Maintenance        |                   |                  |                  |
      | Motorcycle Track Days                |                   | Course 1                              |                   |                  |                  |
      | Motorcycle Track Days                |                   | Course 2                              |                   |                  |                  |
      | Horse Riding Trips                   |                   | Course 1                              |                   |                  |                  |
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
    And the following "tool_muprog > program_allocations" exist:
      | program                              | user     | timeallocated          | timedue                |
      | Basic First Aid                      | a        | ## 2025-04-26 10:00 ## | ## 2025-07-26 10:00 ## |
      | Basic First Aid                      | b        | ## 2025-01-01 10:00 ## | ## 2025-03-01 10:00 ## |
      | Basic First Aid                      | c        | ## 2025-04-26 10:00 ## | ## 2025-07-26 10:00 ## |
      | Basic First Aid                      | d        | ## 2025-04-26 10:00 ## | ## 2025-07-26 10:00 ## |
      | Basic First Aid                      | e        | ## 2025-04-26 10:00 ## | ## 2025-07-26 10:00 ## |
      | Basic First Aid                      | f        | ## 2025-04-26 10:00 ## | ## 2025-07-26 10:00 ## |
      | Basic First Aid                      | g        | ## 2025-04-26 10:00 ## | ## 2025-07-26 10:00 ## |
      | Advanced First Aid                   | h        |                        |                        |
      | Advanced First Aid                   | i        |                        |                        |
      | Advanced First Aid                   | j        |                        |                        |
      | Motorcycle Maintenance for Beginners | c        | ## 2025-01-15 08:00 ## |                        |
      | Motorcycle Maintenance for Beginners | e        |                        |                        |
      | Motorcycle Maintenance for Beginners | g        |                        |                        |
      | Motorcycle Maintenance for Beginners | i        |                        |                        |
      | Motorcycle Maintenance for Beginners | k        |                        |                        |

  Scenario: Documentation screenshots for programs management_index page
    Given I log in as "manager"
    And I am on the "tool_muprog > All programs management" page

    Then I make documentation screenshot "programs.png" for "tool_muprog" plugin
    And site is restored after documentation screenshots

  Scenario: Documentation screenshots for management_program page
    Given I log in as "manager"
    And I am on the "tool_muprog > All programs management" page
    And I follow "Advanced First Aid"

    Then I make documentation screenshot "program_general.png" for "tool_muprog" plugin
    And site is restored after documentation screenshots

  Scenario: Documentation screenshots for management_program_content page
    Given I log in as "manager"
    And I am on the "tool_muprog > All programs management" page
    And I follow "Basic First Aid"
    And I click on "Content" "link" in the ".nav-tabs" "css_element"

    Then I make documentation screenshot "program_content.png" for "tool_muprog" plugin
    And site is restored after documentation screenshots

  Scenario: Documentation screenshots for management_program_visibility page
    Given I log in as "manager"
    And I am on the "tool_muprog > All programs management" page
    And I follow "Motorcycle Track Days"
    And I click on "Catalogue visibility" "link" in the ".nav-tabs" "css_element"

    Then I make documentation screenshot "program_visibility.png" for "tool_muprog" plugin
    And site is restored after documentation screenshots

  Scenario: Documentation screenshots for management_program_allocation page
    Given I log in as "manager"
    And I am on the "tool_muprog > All programs management" page
    And I follow "Basic First Aid"
    And I click on "Allocation settings" "link" in the ".nav-tabs" "css_element"
    And I click on "Update allocations" "link"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | timeallocationstart[enabled] | 1    |
      | timeallocationstart[day]     | 1    |
      | timeallocationstart[month]   | 1    |
      | timeallocationstart[year]    | 2025 |
      | timeallocationstart[hour]    | 10   |
      | timeallocationstart[minute]  | 00   |
    And I click on "Update allocations" "button" in the ".modal-dialog" "css_element"
    And I click on "Update scheduling" "link"
    And I set the following fields to these values:
      | Program due             | Due after start |
      | programdue_delay[value] | 3               |
      | programdue_delay[type]  | months          |
    And I click on "Update scheduling" "button" in the ".modal-dialog" "css_element"
    And I change window size to "1208x900"

    Then I make documentation screenshot "program_allocation.png" for "tool_muprog" plugin
    And site is restored after documentation screenshots

  Scenario: Documentation screenshots for management_program_users page
    Given I log in as "manager"
    And I am on the "tool_muprog > All programs management" page
    And I follow "Basic First Aid"
    And I click on "Users" "link" in the ".nav-tabs" "css_element"

    Then I make documentation screenshot "program_users.png" for "tool_muprog" plugin
    And site is restored after documentation screenshots

  Scenario: Documentation screenshots for progams management_allocation page
    Given I log in as "manager"
    And I am on the "tool_muprog > All programs management" page
    And I follow "Basic First Aid"
    And I click on "Users" "link" in the ".nav-tabs" "css_element"
    And I follow "User B"
    And I change window size to "1208x1100"

    Then I make documentation screenshot "allocation.png" for "tool_muprog" plugin
    And site is restored after documentation screenshots

  Scenario: Documentation screenshots for My programs profile page
    Given I log in as "c"
    And I am on the "tool_muprog > My programs" page

    Then I make documentation screenshot "profile_my_programs.png" for "tool_muprog" plugin
    And I follow "Motorcycle Maintenance for Beginners"
    And I change window size to "1208x1000"
    Then I make documentation screenshot "profile_my_program.png" for "tool_muprog" plugin
    And site is restored after documentation screenshots

  Scenario: Documentation screenshots for My programs block
    Given I log in as "c"
    And I skip tests if "block_muprog_my" is not installed
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
    And I add the "My programs" block to the "content" region
    And I turn editing mode off
    Then I make documentation screenshot "dashboard_my_programs.png" for "tool_muprog" plugin
    And site is restored after documentation screenshots

  Scenario: Documentation screenshots for Program catalogue page
    Given I log in as "a"
    And I am on the "tool_muprog > My programs" page
    And I follow "Program catalogue"

    Then I make documentation screenshot "catalogue.png" for "tool_muprog" plugin
    And site is restored after documentation screenshots
