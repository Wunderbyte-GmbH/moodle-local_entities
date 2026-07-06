@local @local_entities @local_entities_list
Feature: Entities are shown as a searchable hierarchical list
  As an admin I want the entities overview to show the parent/child hierarchy and let me search it.

  Background:
    Given the following "local_entities > entities" exist:
      | name          | shortname | entitytype | parent        |
      | Main Building | mainbld   | location   |               |
      | Room A        | rooma     | location   | Main Building |
      | Beamer X      | beamerx   | equipment  | Room A        |
      | North Wing    | northwing | location   |               |
    And I log in as "admin"
    And I change viewport size to "1366x10000"

  @javascript
  Scenario: The overview lists every entity with its type
    When I visit "/local/entities/entities.php"
    Then I should see "Main Building"
    And I should see "Room A"
    And I should see "Beamer X"
    And I should see "North Wing"
    # Equipment is shown with its type badge.
    And I should see "Equipment / resource"
    # The hierarchy is shown: the nested equipment carries its parent path as a breadcrumb.
    And I should see "Main Building / Room A"

  @javascript
  Scenario: The overview is paginated at 20 entities per page
    Given the following "local_entities > entities" exist:
      | name      | shortname | entitytype |
      | ENTROW-01 | er01      | location   |
      | ENTROW-02 | er02      | location   |
      | ENTROW-03 | er03      | location   |
      | ENTROW-04 | er04      | location   |
      | ENTROW-05 | er05      | location   |
      | ENTROW-06 | er06      | location   |
      | ENTROW-07 | er07      | location   |
      | ENTROW-08 | er08      | location   |
      | ENTROW-09 | er09      | location   |
      | ENTROW-10 | er10      | location   |
      | ENTROW-11 | er11      | location   |
      | ENTROW-12 | er12      | location   |
      | ENTROW-13 | er13      | location   |
      | ENTROW-14 | er14      | location   |
      | ENTROW-15 | er15      | location   |
      | ENTROW-16 | er16      | location   |
      | ENTROW-17 | er17      | location   |
      | ENTROW-18 | er18      | location   |
      | ENTROW-19 | er19      | location   |
      | ENTROW-20 | er20      | location   |
    When I visit "/local/entities/entities.php"
    # First page shows the first 20 (here: the 4 background + first 16 ENTROW); page 2 holds the rest.
    Then I should see "Main Building"
    And I should not see "ENTROW-20"
    # Navigating to page 2 reveals the entities that did not fit on page 1.
    And I click on "[data-pagenumber='2'] .page-link" "css_element"
    And I should see "ENTROW-20"
