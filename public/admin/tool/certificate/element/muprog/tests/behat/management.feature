@tool @tool_certificate @certificateelement @certificateelement_muprog @MuTMS @javascript @tool_muprog
Feature: Being able to manage programs elements in a certificate template

  Background:
    Given the following certificate templates exist:
      | name          | numberofpages |
      | Certificate 1 | 1             |

  Scenario: Add and edit standard programs elements in a certificate template
    Given I log in as "admin"
    And I change window size to "large"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I follow "Certificate 1"

    When I add the element "Program" to page "1" of the "Certificate 1" site certificate template
    And I should see "Add 'Program field' element" in the ".modal.show .modal-header" "css_element"
    And I set the following fields to these values:
      | Element name  | Nazev            |
      | Program field | Program name     |
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    Then I should see "Nazev" in the "[data-region='elementlist']" "css_element"
    When I click on "Edit 'Nazev'" "link" in the "[data-region='elementlist']" "css_element"
    And the following fields match these values:
      | Element name  | Nazev            |
      | Program field | Program name     |
    And I set the following fields to these values:
      | Element name  | ID programu |
      | Program field | Program ID  |
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    Then I should see "ID programu" in the "[data-region='elementlist']" "css_element"

    When I click on "Edit 'ID programu'" "link" in the "[data-region='elementlist']" "css_element"
    And the following fields match these values:
      | Element name  | ID programu |
      | Program field | Program ID  |
    And I click on "Cancel" "button" in the ".modal.show .modal-footer" "css_element"
    Then I should see "ID programu" in the "[data-region='elementlist']" "css_element"

    When I click on "Edit 'ID programu'" "link" in the "[data-region='elementlist']" "css_element"
    And I set the following fields to these values:
      | Element name  | Dokonceno               |
      | Program field | Program completion date |
      | Date format   | strftimedateshort       |
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    And I click on "Edit 'Dokonceno'" "link" in the "[data-region='elementlist']" "css_element"
    And the following fields match these values:
      | Element name  | Dokonceno               |
      | Program field | Program completion date |
      | Date format   | strftimedateshort       |
    And I set the following fields to these values:
      | Element name  |                         |
      | Date format   | strftimedatetime        |
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    And I click on "Edit 'Program completion date'" "link" in the "[data-region='elementlist']" "css_element"
    Then the following fields match these values:
      | Element name  | Program completion date |
      | Program field | Program completion date |
      | Date format   | strftimedatetime        |
    And I click on "Cancel" "button" in the ".modal.show .modal-footer" "css_element"

  Scenario: Add and edit custom field programs elements in a certificate template
    Given I log in as "admin"
    And I change window size to "large"
    And I navigate to "Programs > Program custom fields" in site administration
    And I press "Add a new category"
    And I click on "Add a new custom field" "link"
    And I click on "Short text" "link"
    And I set the following fields to these values:
      | Name                                | Test field |
      | Short name                          | testfield  |
    And I click on "Save changes" "button" in the "Adding a new Short text" "dialogue"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I follow "Certificate 1"

    When I add the element "Program" to page "1" of the "Certificate 1" site certificate template
    And I set the following fields to these values:
      | Element name  | Some tf          |
      | Program field | Custom field     |
      | Custom field  | Test field       |
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    Then I should see "Some tf" in the "[data-region='elementlist']" "css_element"
    And I click on "Edit 'Some tf'" "link" in the "[data-region='elementlist']" "css_element"
    And the following fields match these values:
      | Element name  | Some tf          |
      | Program field | Custom field     |
      | Custom field  | Test field       |
    And I click on "Cancel" "button" in the ".modal.show .modal-footer" "css_element"

    And I click on "Delete element" "link" in the "[data-region='elementlist']" "css_element"
    And I click on "Delete" "button" in the ".modal.show .modal-footer" "css_element"
    And I should not see "Some tf"

    When I add the element "Program" to page "1" of the "Certificate 1" site certificate template
    And I set the following fields to these values:
      | Program field | Custom field     |
      | Custom field  | Test field       |
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    Then I should see "Test field" in the "[data-region='elementlist']" "css_element"
