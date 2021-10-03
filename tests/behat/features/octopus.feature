Feature: Test it works
  As a CI system
  I want to test it works

  Background:
    Given the "group" "EDITOR group" has permissions "CMS_ACCESS_LeftAndMain"

  Scenario: Test it works
    Given I am logged in with "EDITOR" permissions
    When I go to "/admin/pages"
