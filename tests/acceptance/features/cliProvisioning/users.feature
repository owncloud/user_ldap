@cli
Feature: add a user using the using the occ command

  As an administrator
  I want to be able to add, delete and modify users via the command line
  So that I can easily manage users when user LDAP is enabled

  Background:
    And these users have been created with default attributes and small skeleton files:
      | username |
      | Alice    |
      | Brian    |

  Scenario: admin creates an ordinary user using the occ command
    When the administrator creates this user using the occ command:
      | username  | displayname |
      | justauser | Just User   |
    Then the command should have been successful
    And the command output should contain the text 'The user "justauser" was created successfully'
    And user "justauser" should exist
    And user "justauser" should be able to access a skeleton file

  Scenario: admin tries to create an existing user
    Given this user has been created using the occ command:
      | username       | displayname    |
      | brand-new-user | Brand New User |
    When the administrator tries to create a user "brand-new-user" using the occ command
    Then the command should have failed with exit code 1
    And the command output should contain the text 'The user "brand-new-user" already exists.'

  Scenario: admin deletes a user
    Given this user has been created using the occ command:
      | username       | displayname    |
      | brand-new-user | Brand New User |
    When the administrator deletes user "brand-new-user" using the occ command
    Then the command should have been successful
    And the command output should contain the text "User with uid 'brand-new-user', display name 'Brand New User', email '' was deleted"
    And user "brand-new-user" should not exist

  Scenario: the administrator can edit a user email
    Given this user has been created using the occ command:
      | username       | displayname    |
      | brand-new-user | Brand New User |
    When the administrator changes the email of user "brand-new-user" to "brand-new-user@example.com" using the occ command
    Then the command should have been successful
    And the command output should contain the text 'The email address of brand-new-user updated to brand-new-user@example.com'
    And user "brand-new-user" should exist
    And the user attributes returned by the API should include
      | email | brand-new-user@example.com |

  Scenario: the administrator can edit a user display name
    Given this user has been created using the occ command:
      | username       | displayname    |
      | brand-new-user | Brand New User |
    When the administrator changes the display name of user "brand-new-user" to "A New User" using the occ command
    Then the command should have been successful
    And the command output should contain the text 'The displayname of brand-new-user updated to A New User'
    And user "brand-new-user" should exist
    And the user attributes returned by the API should include
      | displayname | A New User |

  Scenario: admin deletes ldap defined user and syncs again
    When the administrator deletes user "Alice" using the occ command
    Then the command should have been successful
    And the command output should contain the text "User with uid 'Alice', display name 'Alice Hansen', email 'alice@example.org' was deleted"
    And user "Alice" should not exist
    When the LDAP users are resynced
    Then user "Alice" should exist

  Scenario: admin tries to modify displayname of user for which an LDAP attribute is specified
    When the administrator sets the ldap attribute "displayname" of the entry "uid=Brian,ou=TestUsers" to "ldap user"
    And the LDAP users are resynced
    And the administrator changes the display name of user "Brian" to "occ user" using the occ command
    Then the command should have failed with exit code 1
    And user "Brian" should exist
    And the user attributes returned by the API should include
      | displayname | ldap user |

  Scenario: admin tries to modify password of user for which an LDAP attribute is specified
    When the administrator sets the ldap attribute "userpassword" of the entry "uid=Brian,ou=TestUsers" to "ldap_password"
    And the LDAP users are resynced
    And the administrator resets the password of user "Brian" to "occ_password" using the occ command
    Then the command should have failed with exit code 1
    And user "Brian" should exist
    And the content of file "textfile0.txt" for user "Brian" using password "ldap_password" should be "ownCloud test text file 0" plus end-of-line
    But user "Brian" using password "occ_password" should not be able to download file "textfile0.txt"

  @issue-core-33186
  Scenario: admin tries to modify mail of user for which an LDAP attribute is specified
    When the administrator sets the ldap attribute "mail" of the entry "uid=Brian,ou=TestUsers" to "ldapuser@oc.com"
    And the LDAP users are resynced
    And the administrator changes the email of user "Brian" to "occuser@oc.com" using the occ command
    Then the command should have been successful
    #Then the command should have failed with exit code 1
    And user "Brian" should exist
    And the user attributes returned by the API should include
    #| email | ldapuser@oc.com|
      | email | occuser@oc.com |
