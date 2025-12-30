@block @block_muprogmyoverview @tool_muprog @javascript @MuTMS
Feature: My programs overview page block English wiki documentation image generator

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
      | Hands-On First Aid Credits                | CFB4      | HS       |
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
      | Basic First Aid                      | FA1      | HS       | 1            | 0        | Sample program for basic first aid credits.    | admin/tool/muprog/tests/fixtures/docs/bfa.jpeg  |                    |
      | Advanced First Aid                   | FA2      | HS       | 1            | 0        | Sample program for advanced first aid credits. | admin/tool/muprog/tests/fixtures/docs/afa.jpeg  |                    |
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
      | Basic First Aid                      | Optional courses  | Hands-On First Aid Credits            |                   |                  |                  |
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
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | manager   | manager       | System       |           |
    And the following "tool_muprog > program_allocations" exist:
      | program                              | user     | timeallocated          | timedue                |
      | Basic First Aid                      | a        | ##14 days ago##        | ##tomorrow + 30 days## |
      | Motorcycle Maintenance for Beginners | a        | ##30 days ago##        | ##14 days ago##        |

  Scenario: My programs plugins database screenshot
    Given I am on the "block_muprogmyoverview > My programs" page logged in as "a"

    Then I make documentation screenshot "img_screenshot.png" for "block_muprogmyoverview" plugin
    And site is restored after documentation screenshots
