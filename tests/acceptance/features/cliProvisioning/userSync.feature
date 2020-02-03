@cli
Feature: sync user using occ command

  As an administrator
  I want to be able to sync user/users via the command line
  So that I can easily manage users when user LDAP is enabled

  Background:
    Given these users have been created with default attributes and without skeleton files:
      | username |
      | user0    |
      | user1    |

  @skipOnOcV10.3
  Scenario: admin deletes ldap users and syncs only one of them
    When the administrator deletes user "user0" using the occ command
    And the administrator deletes user "user1" using the occ command
    Then user "user0" should not exist
    And user "user1" should not exist
    When LDAP user "user0" is resynced
    Then user "user0" should exist
    And user "user1" should not exist

  @skipOnOcV10.3
  Scenario: admin edits ldap users email and syncs only one of them
    When the administrator changes the email of user "user0" to "user0@example0.com" using the occ command
    And the administrator changes the email of user "user1" to "user1@example1.com" using the occ command
    Then user "user0" should exist
    And user "user1" should exist
    When LDAP user "user0" is resynced
    Then the email address of user "user0" should be "user0@example.org"
    And the email address of user "user1" should be "user1@example1.com"

  Scenario: admin lists all the enabled backends
    When the admin lists the enabled user backends using the occ command
    Then the command should have been successful
    And the command output should be:
      """
      OC\User\Database
      OCA\User_LDAP\User_Proxy
      """

  Scenario: admin deletes ldap users and performs full sync
    When the administrator deletes user "user0" using the occ command
    And the administrator deletes user "user1" using the occ command
    Then user "user0" should not exist
    And user "user1" should not exist
    When the LDAP users have been resynced
    Then user "user0" should exist
    And user "user1" should exist

  Scenario: sync a local user
    Given user "local-user" has been created with default attributes in the database user backend
    When the administrator changes the display name of user "local-user" to "Test User" using the occ command
    And LDAP user "local-user" is resynced
    Then the command should have been successful
    And user "local-user" should not exist
