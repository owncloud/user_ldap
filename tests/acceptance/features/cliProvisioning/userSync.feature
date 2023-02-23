@cli
Feature: sync user using occ command

  As an administrator
  I want to be able to sync user/users via the command line
  So that I can easily manage users when user LDAP is enabled

  Background:
    Given these users have been created with default attributes and without skeleton files:
      | username |
      | Alice    |
      | Brian    |

  @skipOnOcV10.3
  Scenario: admin deletes ldap users and syncs only one of them
    When the administrator deletes user "Alice" using the occ command
    And the administrator deletes user "Brian" using the occ command
    Then user "Alice" should not exist
    And user "Brian" should not exist
    When LDAP user "Alice" is resynced
    Then user "Alice" should exist
    And user "Brian" should not exist

  @skipOnOcV10.3
  Scenario: admin edits ldap users email and syncs only one of them
    When the administrator changes the email of user "Alice" to "alice@example0.com" using the occ command
    And the administrator changes the email of user "Brian" to "brian@example1.com" using the occ command
    Then user "Alice" should exist
    And user "Brian" should exist
    When LDAP user "Alice" is resynced
    Then the email address of user "Alice" should be "alice@example.org"
    And the email address of user "Brian" should be "brian@example1.com"


  Scenario: admin lists all the enabled backends
    When the admin lists the enabled user backends using the occ command
    Then the command should have been successful
    And the command output should be:
      """
      OC\User\Database
      OCA\User_LDAP\User_Proxy
      """


  Scenario: admin deletes ldap users and performs full sync
    When the administrator deletes user "Alice" using the occ command
    And the administrator deletes user "Brian" using the occ command
    Then user "Alice" should not exist
    And user "Brian" should not exist
    When the LDAP users have been resynced
    Then user "Alice" should exist
    And user "Brian" should exist

  @issue-511
  Scenario: sync a local user
    Given user "local-user" has been created with default attributes in the database user backend
    When the administrator changes the display name of user "local-user" to "Test User" using the occ command
    And LDAP user "local-user" is resynced
    Then the command should have been successful
    And user "local-user" should not exist

  @skipOnOcV10.3
  Scenario: sync a ldap user that is substring of some other ldap users
    Given these users have been created with default attributes and without skeleton files:
      | username    |
      | regularuser |
      | regular     |
    When the administrator sets the ldap attribute "displayname" of the entry "uid=regular,ou=TestUsers" to "Test User"
    And LDAP user "regular" is resynced
    Then user "regular" should exist
    And user "regularuser" should exist
    And the display name of user "regular" should be "Test User"
    And the display name of user "regularuser" should be "Regular User"

  @skipOnOcV10.3
  Scenario: sync a ldap user that is superstring of some other ldap users
    Given these users have been created with default attributes and without skeleton files:
      | username    |
      | regularuser |
      | regular     |
    When the administrator sets the ldap attribute "displayname" of the entry "uid=regularuser,ou=TestUsers" to "Test User"
    And LDAP user "regularuser" is resynced
    Then user "regular" should exist
    And user "regularuser" should exist
    And the display name of user "regularuser" should be "Test User"
    And the display name of user "regular" should be "Regular User"

  @issue-515
  @skipOnOcV10.3 @skipOnOcV10.4
  Scenario: sync a user that does not exist
    When LDAP user "regularuser" is resynced
    Then the command should have been successful
    And the command output should be:
      """
      Searching for regularuser ...
      Exact match for user regularuser not found in the backend.
      Deleting accounts:
      regularuser, ,  (no longer exists in the backend)
      """
