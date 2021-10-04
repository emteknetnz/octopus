Feature: Test it works
  As a CI system
  I want to test it works

  Background:
    #Given the "group" "EDITOR group" has permissions "CMS_ACCESS_LeftAndMain"
    Given a "page" "My page" has the "Content" "<p>My awesome content</p>"

    # requires behat-extension pr to be permsed first
    #Given I take a screenshot after every step
    #Given I dump the rendered HTML after every step

  Scenario: Test it works
    Given I am logged in with "ADMIN" permissions
    And I go to "/admin/pages"
    And I follow "My page"
    And I press the "Publish" button
    When I go to "/my-page"
    # Trigger screenshot
    Then I press the "Missing" button
    When I go to "/admin/pages"
    # Uncomment to trigger screenshot, will fail in ci
    Then I press the "Missing" button
