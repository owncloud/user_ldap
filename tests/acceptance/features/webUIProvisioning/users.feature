@webUI @insulated @disablePreviews
Feature: add users
  As an admin
  I want to add users, delete and manage users with the webUI
  So that I can easily manage users when user LDAP is enabled

  Background:
    Given default LDIF user-groups set has been imported, created and synced
    And user admin has logged in using the webUI
    And the administrator has browsed to the users page

  Scenario: use the webUI to create a simple user
    When the administrator creates a user with the name "guiusr1" and the password "%regular%" using the webUI
    And the administrator logs out of the webUI
    And user "guiusr1" logs in using the webUI
    Then the user should be redirected to a webUI page with the title "Files - %productname%"

  Scenario: use the webUI to delete a simple user
    Given user "new-user" has been created with default attributes in the database user backend
    And the administrator has reloaded the users page
    When the administrator deletes user "new-user" using the webUI and confirms the deletion using the webUI
    And the deleted user "new-user" tries to login using the password "%alt1%" using the webUI
    Then the user should be redirected to a webUI page with the title "%productname%"
    And user "new-user" should not exist

  Scenario: Admin changes the display name of the user
    Given user "new-user" has been created with default attributes in the database user backend
    And the administrator has browsed to the users page
    When the administrator changes the display name of user "new-user" to "New User" using the webUI
    And the administrator logs out of the webUI
    And user "new-user" logs in using the webUI
    Then "New User" should be shown as the name of the current user on the WebUI
    And user "new-user" should exist
    And the user attributes returned by the API should include
    | displayname | New User |

  Scenario: Admin changes the password of the user
    Given user "new-user" has been created with default attributes in the database user backend
    And the administrator has browsed to the users page
    When the administrator changes the password of user "new-user" to "new_password" using the webUI
    Then user "new-user" should exist
    And the content of file "textfile0.txt" for user "new-user" using password "new_password" should be "ownCloud test text file 0" plus end-of-line
    But user "new-user" using password "%regular%" should not be able to download file "textfile0.txt"

  Scenario: use the webUI to create a simple user with same username as existing ldap user
    When the administrator creates a user with the name "user1" and the password "%regular%" using the webUI
    Then a notification should be displayed on the webUI with the text "Error creating user: A user with that name already exists."
    And user "user1" should exist
    But user "user1" using password "%regular%" should not be able to download file "textfile0.txt"

  Scenario: admin deletes ldap defined user and syncs again
    When the administrator deletes user "user0" using the webUI and confirms the deletion using the webUI
    Then user "user0" should not exist
    When the deleted user "user0" tries to login using the password "%alt1%" using the webUI
    Then the user should be redirected to a webUI page with the title "%productname%"
    When the LDAP users are resynced
    Then user "user0" should exist
    When the user has browsed to the login page
    And user "user0" logs in using the webUI
    Then the user should be redirected to a webUI page with the title "Files - %productname%"

  @issue-core-33186
  Scenario: admin tries to modify displayname of user for which an LDAP attribute is specified
    When the administrator sets the ldap attribute "displayname" of the entry "uid=user0,ou=TestUsers" to "ldap user"
    And the administrator changes the display name of user "user0" to "New User" using the webUI
    And the administrator logs out of the webUI
    And user "user0" logs in using the webUI
    Then "ldap user" should be shown as the name of the current user on the WebUI
    And user "user0" should exist
    And the user attributes returned by the API should include
    | displayname | ldap user |

  @issue-core-33186
  Scenario: admin tries to modify email of user for which an LDAP attribute is specified
    When the administrator sets the ldap attribute "mail" of the entry "uid=user1,ou=TestUsers" to "ldapuser@oc.com"
    And the administrator changes the email of user "user1" to "webuiemail@oc.com" using the webUI
    And the LDAP users are resynced
    Then user "user1" should exist
    But the email address of user "user1" should be "ldapuser@oc.com"

  @issue-core-33186
  Scenario: admin tries to modify password of user for which an LDAP attribute is specified
    Given the administrator has browsed to the users page
    When the administrator sets the ldap attribute "userpassword" of the entry "uid=user0,ou=TestUsers" to "ldap_password"
    And the administrator changes the password of user "user0" to "webui_password" using the webUI
    Then user "user0" should exist
    And user "user0" using password "webui_password" should not be able to download file "textfile0.txt"
    And the content of file "textfile0.txt" for user "user0" using password "ldap_password" should be "ownCloud test text file 0" plus end-of-line

  @issue-core-33186
  Scenario: admin tries to modify quota of user for which an LDAP attribute is specified
    #to set Quota we can just misuse any LDAP text field
    Given LDAP config "LDAPTestId" has key "ldapQuotaAttribute" set to "employeeNumber"
    When the administrator sets the ldap attribute "employeeNumber" of the entry "uid=user0,ou=TestUsers" to "10 MB"
    And the administrator sets the quota of user "user0" to "13 MB" using the webUI
    Then the quota definition of user "user0" should be "13 MB"
    And the quota of user "user0" should be set to "13 MB" on the webUI
    #And the quota definition of user "user0" should be "10 MB"
    When the LDAP users are resynced
    And the administrator reloads the users page
    Then the quota definition of user "user0" should be "10 MB"
    And the quota of user "user0" should be set to "10 MB" on the webUI

  Scenario: admin sets quota of user for which no LDAP quota attribute is specified
    #to set Quota we can just misuse any LDAP text field
    Given LDAP config "LDAPTestId" has key "ldapQuotaAttribute" set to "employeeNumber"
    And the LDAP users have been resynced
    And the administrator sets the quota of user "user0" to "13 MB" using the webUI
    Then the quota definition of user "user0" should be "13 MB"
    And the quota of user "user0" should be set to "13 MB" on the webUI
    When the LDAP users are resynced
    And the administrator reloads the users page
    Then the quota definition of user "user0" should be "13 MB"
    And the quota of user "user0" should be set to "13 MB" on the webUI

  @issue-core-33186
  Scenario: admin sets quota of user for which no LDAP quota attribute is specified but a default quota is set in the LDAP settings
    #to set Quota we can just misuse any LDAP text field
    Given LDAP config "LDAPTestId" has key "ldapQuotaAttribute" set to "employeeNumber"
    And LDAP config "LDAPTestId" has key "ldapQuotaDefault" set to "10MB"
    And the LDAP users have been resynced
    When the administrator sets the quota of user "user0" to "13 MB" using the webUI
    Then the quota definition of user "user0" should be "13 MB"
    And the quota of user "user0" should be set to "13 MB" on the webUI
    #Then the administrator should not be able to set the quota for user "user0" using the webUI
    When the LDAP users are resynced
    And the administrator reloads the users page
    Then the quota definition of user "user0" should be "10MB"
    And the quota of user "user0" should be set to "10MB" on the webUI

  Scenario: admin sets quota of user in LDAP when a default quota is set in the LDAP settings
    #to set Quota we can just misuse any LDAP text field
    Given LDAP config "LDAPTestId" has key "ldapQuotaAttribute" set to "employeeNumber"
    And LDAP config "LDAPTestId" has key "ldapQuotaDefault" set to "10MB"
    And the LDAP users have been resynced
    When the administrator sets the ldap attribute "employeeNumber" of the entry "uid=user0,ou=TestUsers" to "13 MB"
    And the administrator reloads the users page
    Then the quota of user "user0" should be set to "10MB" on the webUI
    When the LDAP users are resynced
    And the administrator reloads the users page
    Then the quota of user "user0" should be set to "13 MB" on the webUI

  @issue-core-33186
  Scenario: admin sets quota of user when the quota LDAP attribute is specified and a default quota is set in the LDAP settings
    #to set Quota we can just misuse any LDAP text field
    Given LDAP config "LDAPTestId" has key "ldapQuotaAttribute" set to "employeeNumber"
    And LDAP config "LDAPTestId" has key "ldapQuotaDefault" set to "10MB"
    When the administrator sets the ldap attribute "employeeNumber" of the entry "uid=user0,ou=TestUsers" to "11 MB"
    And the LDAP users are resynced
    And the administrator sets the quota of user "user0" to "13 MB" using the webUI
    #Then the administrator should not be able to set the quota for user "user0" using the webUI
    Then the quota definition of user "user0" should be "13 MB"
    And the quota of user "user0" should be set to "13 MB" on the webUI
    When the LDAP users are resynced
    And the administrator reloads the users page
    Then the quota definition of user "user0" should be "11 MB"
    And the quota of user "user0" should be set to "11 MB" on the webUI