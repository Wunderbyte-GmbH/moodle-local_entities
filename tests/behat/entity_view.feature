@local @local_entities @local_entities_view
Feature: The entity detail page renders through the view-template resolver
  The detail page must keep rendering the classic layout (image / calendar) after the refactor.

  Background:
    Given the following "local_entities > entities" exist:
      | name          | shortname |
      | View Test One | viewtest1 |
    And I log in as "admin"

  @javascript
  Scenario: The classic template shows the entity, its calendar section and edit controls
    # Reach the detail page via the entity's link in the overview (no hard-coded id).
    When I visit "/local/entities/entities.php"
    And I click on "View Test One" "link"
    Then I should see "View Test One"
    And I should see "Calendar"
    And "Edit" "link" should exist

  @javascript
  Scenario: A manager previews a template and saves it as the global view
    When I visit "/local/entities/entities.php"
    And I click on "View Test One" "link"
    Then "[data-viewtemplate='classic']" "css_element" should exist
    # Preview a different template (link carrying the preview parameter).
    And I click on "Compact" "link"
    Then "[data-viewtemplate='compact']" "css_element" should exist
    And I should see "Preview"
    # Saving makes it the active template for this entity's type (reload drops the preview parameter).
    And I click on "[data-action='save-view-template']" "css_element"
    And I wait until the page is ready
    Then "[data-viewtemplate='compact']" "css_element" should exist
    And I should not see "Preview"
