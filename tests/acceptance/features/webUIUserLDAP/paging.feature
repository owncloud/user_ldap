@insulated @disablePreviews @webUI
Feature: paging

  As an admin
  I want syncing to work correctly despite the paging setting
  So that all LDAP users get synced over to owncloud

  Background:
    Given LDAP config "LDAPTestId" has key "ldapPagingSize" set to "10"
    And the administrator has created "200" LDAP users with the prefix "my-user-" in the OU "NEWZombies"

  Scenario: login to a system with a lot of users
    Then it should be possible to login with the username "my-user-000" and password "my-user-000" using the WebUI
    And the user logs out of the webUI
    Then it should be possible to login with the username "my-user-100" and password "my-user-100" using the WebUI
    And the user logs out of the webUI
    Then it should be possible to login with the username "my-user-199" and password "my-user-199" using the WebUI

  Scenario: change password on a system with a lot of users
    And these users have been initialized:
      | username    | password    |
      | my-user-000 | my-user-000 |
      | my-user-100 | my-user-100 |
      | my-user-199 | my-user-199 |
    When the administrator sets the ldap attribute "userpassword" of the entry "uid=my-user-000,ou=NEWZombies" to "new-password000"
    And the administrator sets the ldap attribute "userpassword" of the entry "uid=my-user-100,ou=NEWZombies" to "new-password100"
    And the administrator sets the ldap attribute "userpassword" of the entry "uid=my-user-199,ou=NEWZombies" to "new-password199"
    Then it should be possible to login with the username "my-user-000" and password "new-password000" using the WebUI
    And the user logs out of the webUI
    Then it should be possible to login with the username "my-user-100" and password "new-password100" using the WebUI
    And the user logs out of the webUI
    Then it should be possible to login with the username "my-user-199" and password "new-password199" using the WebUI

  Scenario: autocompletion of synced users
    And the LDAP users have been resynced
    And the user has browsed to the login page
    And the user has logged in with username "my-user-000" and password "my-user-000" using the webUI
    And the user has opened the share dialog for folder "simple-folder"
    When the user types "12" in the share-with-field
    Then all users and groups that contain the string "12" in their name should be listed in the autocomplete list on the webUI
    And the users own name should not be listed in the autocomplete list on the webUI
