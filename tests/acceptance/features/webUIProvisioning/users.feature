@webUI @insulated @disablePreviews
Feature: add users
  As an admin
  I want to add users, delete and manage users with the webUI
  So that I can easily manage users when user LDAP is enabled

  Background:
    Given user admin has logged in using the webUI
    And the administrator has browsed to the users page

  Scenario: use the webUI to create a simple user
    When the administrator creates a user with the name "guiusr1" and the password "%regular%" using the webUI
    And the administrator logs out of the webUI
    And user "guiusr1" logs in using the webUI
    Then the user should be redirected to a webUI page with the title "Files - %productname%"
