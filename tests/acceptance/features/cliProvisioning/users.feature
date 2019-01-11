@cli
Feature: add a user using the using the occ command

  As an administrator
  I want to be able to add, delete and modify users via the command line
  So that I can easily manage users when user LDAP is enabled

  Scenario: admin creates an ordinary user using the occ command
    When the administrator creates this user using the occ command:
      | username  |
      | justauser |
    Then the command should have been successful
    And the command output should contain the text 'The user "justauser" was created successfully'
    And user "justauser" should exist
    And user "justauser" should be able to access a skeleton file

  Scenario: admin tries to create an existing user
    Given this user has been created using the occ command:
      | username       |
      | brand-new-user |
    When the administrator tries to create a user "brand-new-user" using the occ command
    Then the command should have failed with exit code 1
    And the command output should contain the text 'The user "brand-new-user" already exists.'

  Scenario: admin deletes a user
    Given this user has been created using the occ command:
    | username       |
    | brand-new-user |
    When the administrator deletes user "brand-new-user" using the occ command
    Then the command should have been successful
    And the command output should contain the text "User with uid 'brand-new-user', display name 'brand-new-user', email '' was deleted"
    And user "brand-new-user" should not exist

  Scenario: the administrator can edit a user email
    Given this user has been created using the occ command:
    | username       |
    | brand-new-user |
    When the administrator changes the email of user "brand-new-user" to "brand-new-user@example.com" using the occ command
    Then the command should have been successful
    And the command output should contain the text 'The email address of brand-new-user updated to brand-new-user@example.com'
    And user "brand-new-user" should exist
    And the user attributes returned by the API should include
      | email | brand-new-user@example.com |

  Scenario: the administrator can edit a user display name
    Given this user has been created using the occ command:
    | username       |
    | brand-new-user |
    When the administrator changes the display name of user "brand-new-user" to "A New User" using the occ command
    Then the command should have been successful
    And the command output should contain the text 'The displayname of brand-new-user updated to A New User'
    And user "brand-new-user" should exist
    And the user attributes returned by the API should include
      | displayname | A New User |

  Scenario: admin deletes ldap defined user and syncs again
    When the administrator deletes user "user0" using the occ command
    Then the command should have been successful
    And the command output should contain the text "User with uid 'user0', display name 'User Zero', email 'user0@example.org' was deleted"
    And user "user0" should not exist
    When the LDAP users are resynced
    Then user "user0" should exist

