@tool @tool_muhome @javascript @MuTMS
Feature: Guests may access custom home pages
  Background:
    Given the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
      | Cat 3 | CAT2     | CAT3     |
    And the following "tool_muhome > pages" exist:
      | name         | status   | guestvisible | uservisible | title              |
      | Home page A  | archived | 1            | 1           | Home page title A  |
      | Home page B  | active   | 0            | 1           | Home page title B  |
      | Home page 1  | active   | 1            | 1           | Home page title 1  |
      | Other page 2 | active   | 1            | 1           | Other page title 2 |
      | Other page 3 | active   | 1            | 1           | Other page title 3 |

  Scenario: Start page and normal home page for not-logged-in users with defaultpage HOMEPAGE_MY
    Given the following config values are set as admin:
      | replacehome        | 0 | tool_muhome |
      | defaulthomepage    | 1 |             |
      | enabledashboard    | 1 |             |
      | allowguestmymoodle | 1 |             |
      | forcelogin         | 0 |             |
      | autologinguests    | 0 |             |

    When I am on homepage
    Then I should see "Acceptance test site" in the "h1" "css_element"

    When I am on site homepage
    Then I should see "Acceptance test site" in the "h1" "css_element"

  Scenario: Start page and normal home page for not-logged-in users with defaultpage HOMEPAGE_SITE
    Given the following config values are set as admin:
      | replacehome        | 0 | tool_muhome |
      | defaulthomepage    | 0 |             |
      | enabledashboard    | 1 |             |
      | allowguestmymoodle | 1 |             |
      | forcelogin         | 0 |             |
      | autologinguests    | 0 |             |

    When I am on homepage
    Then I should see "Acceptance test site" in the "h1" "css_element"

    When I am on site homepage
    Then I should see "Acceptance test site" in the "h1" "css_element"

  Scenario: Start page and custom home page for not-logged-in users with defaultpage HOMEPAGE_MY
    Given the following config values are set as admin:
      | replacehome        | 1 | tool_muhome |
      | defaulthomepage    | 1 |             |
      | enabledashboard    | 1 |             |
      | allowguestmymoodle | 1 |             |
      | forcelogin         | 0 |             |
      | autologinguests    | 0 |             |

    When I am on homepage
    Then I should see "Home page title 1" in the "h1" "css_element"

    When I am on site homepage
    Then I should see "Home page title 1" in the "h1" "css_element"

  Scenario: Start page and custom home page for not-logged-in users with defaultpage HOMEPAGE_SITE
    Given the following config values are set as admin:
      | replacehome        | 1 | tool_muhome |
      | defaulthomepage    | 0 |             |
      | enabledashboard    | 1 |             |
      | allowguestmymoodle | 1 |             |
      | forcelogin         | 0 |             |
      | autologinguests    | 0 |             |

    When I am on homepage
    Then I should see "Home page title 1" in the "h1" "css_element"

    When I am on site homepage
    Then I should see "Home page title 1" in the "h1" "css_element"

  Scenario: Start page and normal home page for guests with defaultpage HOMEPAGE_MY
    Given the following config values are set as admin:
      | replacehome        | 0 | tool_muhome |
      | defaulthomepage    | 1 |             |
      | enabledashboard    | 1 |             |
      | allowguestmymoodle | 1 |             |
      | forcelogin         | 1 |             |
      | autologinguests    | 1 |             |

    When I am on homepage
    Then I should see "Dashboard" in the "h1" "css_element"

    When I am on site homepage
    Then I should see "Acceptance test site" in the "h1" "css_element"

  Scenario: Start page and normal home page for guests with defaultpage HOMEPAGE_MY and guest dashboard
    Given the following config values are set as admin:
      | replacehome        | 0 | tool_muhome |
      | defaultpage        | 1 |             |
      | enabledashboard    | 1 |             |
      | allowguestmymoodle | 1 |             |
      | forcelogin         | 1 |             |
      | autologinguests    | 1 |             |

    When I am on homepage
    Then I should see "Dashboard (Guest)" in the "h1" "css_element"

    When I am on site homepage
    Then I should see "Acceptance test site" in the "h1" "css_element"

  Scenario: Start page and normal home page for guests with defaultpage HOMEPAGE_MY and disabled guest dashboard
    Given the following config values are set as admin:
      | replacehome        | 0 | tool_muhome |
      | defaultpage        | 1 |             |
      | enabledashboard    | 1 |             |
      | allowguestmymoodle | 0 |             |
      | forcelogin         | 1 |             |
      | autologinguests    | 1 |             |

    When I am on homepage
    Then I should see "Acceptance test site" in the "h1" "css_element"

    When I am on site homepage
    Then I should see "Acceptance test site" in the "h1" "css_element"

  Scenario: Start page and custom home page for guests with defaultpage HOMEPAGE_SITE
    Given the following config values are set as admin:
      | replacehome        | 1 | tool_muhome |
      | defaultpage        | 0 |             |
      | enabledashboard    | 1 |             |
      | allowguestmymoodle | 1 |             |
      | forcelogin         | 1 |             |
      | autologinguests    | 1 |             |

    When I am on homepage
    Then I should see "Home page title 1" in the "h1" "css_element"

    When I am on site homepage
    Then I should see "Home page title 1" in the "h1" "css_element"

  Scenario: Not-logged-in users may access additional custom pages with normal home page
    Given the following config values are set as admin:
      | replacehome        | 0          | tool_muhome |
      | addmenu            | Fancy menu | tool_muhome |
      | defaulthomepage    | 0          |             |
      | forcelogin         | 0          |             |
      | autologinguests    | 0          |             |

    When I am on homepage
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

  Scenario: Not-logged-in users may access additional custom pages with custom home page
    Given the following config values are set as admin:
      | replacehome        | 1          | tool_muhome |
      | addmenu            | Fancy menu | tool_muhome |
      | defaulthomepage    | 0          |             |
      | forcelogin         | 0          |             |
      | autologinguests    | 0          |             |

    When I am on homepage
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

  Scenario: Guests may access additional custom pages with normal home page
    Given the following config values are set as admin:
      | replacehome        | 0          | tool_muhome |
      | addmenu            | Fancy menu | tool_muhome |
      | defaulthomepage    | 0          |             |
      | forcelogin         | 1          |             |
      | autologinguests    | 1          |             |

    When I am on homepage
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

  Scenario: Guests may access additional custom pages with custom home page
    Given the following config values are set as admin:
      | replacehome        | 1          | tool_muhome |
      | addmenu            | Fancy menu | tool_muhome |
      | defaulthomepage    | 0          |             |
      | forcelogin         | 1          |             |
      | autologinguests    | 1          |             |

    When I am on homepage
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
