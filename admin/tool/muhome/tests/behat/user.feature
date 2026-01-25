@tool @tool_muhome @javascript @MuTMS
Feature: Users may access custom home pages
  Background:
    Given the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
      | Cat 3 | CAT2     | CAT3     |
    And the following "tool_muhome > pages" exist:
      | name         | status   | guestvisible | uservisible | title              |
      | Home page A  | archived | 1            | 1           | Home page title A  |
      | Home page B  | active   | 0            | 0           | Home page title B  |
      | Home page 1  | active   | 1            | 1           | Home page title 1  |
      | Other page 2 | active   | 1            | 1           | Other page title 2 |
      | Other page 3 | active   | 1            | 1           | Other page title 3 |
    And the following "users" exist:
      | username  | firstname | lastname  | email                |
      | user1     | User      | 1         | user1@example.com    |

  Scenario: Start page and normal home page for users with defaultpage HOMEPAGE_MY and enabled dashboard
    Given the following config values are set as admin:
      | replacehome        | 0 | tool_muhome |
      | defaulthomepage    | 1 |             |
      | enabledashboard    | 1 |             |

    When I log in as "user1"
    Then I should see "Dashboard" in the "h1" "css_element"

    When I am on site homepage
    Then I should see "Acceptance test site" in the "h1" "css_element"

  Scenario: Start page and custom home page for users with defaultpage HOMEPAGE_MY and enabled dashboard
    Given the following config values are set as admin:
      | replacehome        | 1 | tool_muhome |
      | defaulthomepage    | 1 |             |
      | enabledashboard    | 1 |             |

    When I log in as "user1"
    Then I should see "Dashboard" in the "h1" "css_element"

    When I am on site homepage
    Then I should see "Home page title 1" in the "h1" "css_element"

  Scenario: Start page and normal home page for users with defaultpage HOMEPAGE_MY and disabled dashboard
    Given the following config values are set as admin:
      | replacehome        | 0 | tool_muhome |
      | defaulthomepage    | 1 |             |
      | enabledashboard    | 0 |             |

    When I log in as "user1"
    Then I should see "My courses" in the "h1" "css_element"

    When I am on site homepage
    Then I should see "Acceptance test site" in the "h1" "css_element"

  Scenario: Start page and custom home page for users with defaultpage HOMEPAGE_MY and disabled dashboard
    Given the following config values are set as admin:
      | replacehome        | 1 | tool_muhome |
      | defaulthomepage    | 1 |             |
      | enabledashboard    | 0 |             |

    When I log in as "user1"
    Then I should see "My courses" in the "h1" "css_element"

    When I am on site homepage
    Then I should see "Home page title 1" in the "h1" "css_element"

  Scenario: Start page and normal home page for users with defaultpage HOMEPAGE_MYCOURSES
    Given the following config values are set as admin:
      | replacehome        | 0 | tool_muhome |
      | defaulthomepage    | 3 |             |
      | enabledashboard    | 1 |             |

    When I log in as "user1"
    Then I should see "My courses" in the "h1" "css_element"

    When I am on site homepage
    Then I should see "Acceptance test site" in the "h1" "css_element"

  Scenario: Start page and custom home page for users with defaultpage HOMEPAGE_MYCOURSES
    Given the following config values are set as admin:
      | replacehome        | 1 | tool_muhome |
      | defaulthomepage    | 3 |             |
      | enabledashboard    | 1 |             |

    When I log in as "user1"
    Then I should see "My courses" in the "h1" "css_element"

    When I am on site homepage
    Then I should see "Home page title 1" in the "h1" "css_element"

  Scenario: Start page and normal home page for users with defaultpage HOMEPAGE_SITE
    Given the following config values are set as admin:
      | replacehome        | 0 | tool_muhome |
      | defaulthomepage    | 0 |             |
      | enabledashboard    | 1 |             |

    When I log in as "user1"
    Then I should see "Acceptance test site" in the "h1" "css_element"

    When I am on site homepage
    Then I should see "Acceptance test site" in the "h1" "css_element"

  Scenario: Start page and custom home page for users with defaultpage HOMEPAGE_SITE
    Given the following config values are set as admin:
      | replacehome        | 1 | tool_muhome |
      | defaulthomepage    | 0 |             |
      | enabledashboard    | 1 |             |

    When I log in as "user1"
    Then I should see "Home page title 1" in the "h1" "css_element"

    When I am on site homepage
    Then I should see "Home page title 1" in the "h1" "css_element"

  Scenario: Start page and normal home page for users with defaultpage HOMEPAGE_USER
    Given the following config values are set as admin:
      | replacehome        | 0 | tool_muhome |
      | defaulthomepage    | 2 |             |
      | enabledashboard    | 1 |             |

    And I log in as "user1"
    And I follow "Preferences" in the user menu
    And I follow "Start page"
    And I set the following fields to these values:
      | Start page | Dashboard |
    And I press "Save changes"
    And I log out

    When I log in as "user1"
    Then I should see "Dashboard" in the "h1" "css_element"

    When I am on site homepage
    Then I should see "Acceptance test site" in the "h1" "css_element"

    And I follow "Preferences" in the user menu
    And I follow "Start page"
    And I set the following fields to these values:
      | Start page | My courses |
    And I press "Save changes"
    And I log out

    When I log in as "user1"
    Then I should see "My courses" in the "h1" "css_element"

    When I am on site homepage
    Then I should see "Acceptance test site" in the "h1" "css_element"

    And I follow "Preferences" in the user menu
    And I follow "Start page"
    And I set the following fields to these values:
      | Start page | Home |
    And I press "Save changes"
    And I log out

    When I log in as "user1"
    Then I should see "Acceptance test site" in the "h1" "css_element"

    When I am on site homepage
    Then I should see "Acceptance test site" in the "h1" "css_element"

  Scenario: Start page and custom home page for users with defaultpage HOMEPAGE_USER
    Given the following config values are set as admin:
      | replacehome        | 1 | tool_muhome |
      | defaulthomepage    | 2 |             |
      | enabledashboard    | 1 |             |

    And I log in as "user1"
    And I follow "Preferences" in the user menu
    And I follow "Start page"
    And I set the following fields to these values:
      | Start page | Dashboard |
    And I press "Save changes"
    And I log out

    When I log in as "user1"
    Then I should see "Dashboard" in the "h1" "css_element"

    When I am on site homepage
    Then I should see "Home page title 1" in the "h1" "css_element"

    And I follow "Preferences" in the user menu
    And I follow "Start page"
    And I set the following fields to these values:
      | Start page | My courses |
    And I press "Save changes"
    And I log out

    When I log in as "user1"
    Then I should see "My courses" in the "h1" "css_element"

    When I am on site homepage
    Then I should see "Home page title 1" in the "h1" "css_element"

    And I follow "Preferences" in the user menu
    And I follow "Start page"
    And I set the following fields to these values:
      | Start page | Home |
    And I press "Save changes"
    And I log out

    When I log in as "user1"
    Then I should see "Home page title 1" in the "h1" "css_element"

    When I am on site homepage
    Then I should see "Home page title 1" in the "h1" "css_element"

  Scenario: Users may access additional custom pages with normal home page
    Given the following config values are set as admin:
      | replacehome        | 0          | tool_muhome |
      | addmenu            | Fancy menu | tool_muhome |
      | defaulthomepage    | 0          |             |

    When I log in as "user1"
    Then I should see "Acceptance test site" in the "h1" "css_element"

    When I am on site homepage
    Then I should see "Acceptance test site" in the "h1" "css_element"

    When I click on "Fancy menu" "link" in the ".primary-navigation" "css_element"
    And  I click on "Home page 1" "link" in the ".primary-navigation" "css_element"
    Then I should see "Home page title 1" in the "h1" "css_element"

    When I click on "Fancy menu" "link" in the ".primary-navigation" "css_element"
    And  I click on "Other page 2" "link" in the ".primary-navigation" "css_element"
    Then I should see "Other page title 2" in the "h1" "css_element"

    When I click on "Fancy menu" "link" in the ".primary-navigation" "css_element"
    And  I click on "Other page 3" "link" in the ".primary-navigation" "css_element"
    Then I should see "Other page title 3" in the "h1" "css_element"

  Scenario: Users may access additional custom pages with custom home page
    Given the following config values are set as admin:
      | replacehome        | 1          | tool_muhome |
      | addmenu            | Fancy menu | tool_muhome |
      | defaulthomepage    | 0          |             |

    When I log in as "user1"
    Then I should see "Home page title 1" in the "h1" "css_element"

    When I am on site homepage
    Then I should see "Home page title 1" in the "h1" "css_element"

    When I click on "Fancy menu" "link" in the ".primary-navigation" "css_element"
    And  I click on "Other page 2" "link" in the ".primary-navigation" "css_element"
    Then I should see "Other page title 2" in the "h1" "css_element"

    When I click on "Fancy menu" "link" in the ".primary-navigation" "css_element"
    And  I click on "Other page 3" "link" in the ".primary-navigation" "css_element"
    Then I should see "Other page title 3" in the "h1" "css_element"

    When I click on "Fancy menu" "link" in the ".primary-navigation" "css_element"
    Then I should not see "Home page 1" in the ".primary-navigation" "css_element"
