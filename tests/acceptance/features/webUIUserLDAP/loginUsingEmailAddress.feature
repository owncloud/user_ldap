@webUI @insulated @disablePreviews
Feature: login users

  As an admin
  I want users to login with their email address stored on a LDAP server
  So that they only need to remember one username and password (SSO)

  Scenario: login with default settings
    When the LDAP users have been resynced
    Then it should be possible to login with the username "user1@example.org" and password "1234" using the WebUI

  Scenario: using ldap filter including email field
    When the LDAP config "LDAPTestId" has these settings:
      | key                | value                                                                            |
      | ldapLoginFilter    | (&(objectclass=inetOrgPerson)(\|(uid=%uid)(mailPrimaryAddress=%uid)(mail=%uid))) |
      | ldapEmailAttribute |                                                                                  |
    Then it should be possible to login with the username "user1@example.org" and password "1234" using the WebUI

  Scenario: using ldapEmailAttribute but loginFilter lacks email field
    When the LDAP config "LDAPTestId" has these settings:
      | key                | value                                    |
      | ldapLoginFilter    | (&(objectclass=inetOrgPerson)(uid=%uid)) |
      | ldapEmailAttribute | mail                                     |
    Then it should be possible to login with the username "user1@example.org" and password "1234" using the WebUI

  Scenario: no ldapEmailAttribute and loginFilter lacks email field
    When the LDAP config "LDAPTestId" has these settings:
      | key                | value                                    |
      | ldapLoginFilter    | (&(objectclass=inetOrgPerson)(uid=%uid)) |
      | ldapEmailAttribute |                                          |
    And the LDAP users have been resynced
    Then it should not be possible to login with the username "user1@example.org" and password "1234" using the WebUI

  Scenario: change Email address on LDAP server
    When the administrator sets the ldap attribute "mail" of the entry "uid=user1,ou=TestUsers" to "user1-change@example.org"
    And the LDAP users have been resynced
    #need a sync here because the automatic sync only happens after login
    #so after changing the email address one last login is possible with the old address
    Then it should not be possible to login with the username "user1@example.org" and password "1234" using the WebUI
    But it should be possible to login with the username "user1-change@example.org" and password "1234" using the WebUI

  Scenario: change Email address on LDAP server, do not sync
    When the administrator sets the ldap attribute "mail" of the entry "uid=user1,ou=TestUsers" to "user1-change@example.org"
    Then it should be possible to login with the username "user1-change@example.org" and password "1234" using the WebUI

  Scenario: add a second Email address
    When the administrator adds "user1-change@example.org" to the ldap attribute "mail" of the entry "uid=user1,ou=TestUsers"
    Then it should be possible to login with the username "user1@example.org" and password "1234" using the WebUI
    When the user logs out of the webUI
    Then it should be possible to login with the username "user1-change@example.org" and password "1234" using the WebUI

  Scenario: delete the Email address
    When the administrator sets the ldap attribute "mail" of the entry "uid=user1,ou=TestUsers" to ""
    And the LDAP users have been resynced
    Then it should not be possible to login with the username "user1@example.org" and password "1234" using the WebUI
    But it should be possible to login with the username "user1" and password "1234" using the WebUI
    When the user logs out of the webUI
    Then it should be possible to login with the username "user2@example.org" and password "AaBb2Cc3Dd4" using the WebUI

  Scenario: login with a new user
    When the administrator imports this ldif data:
      """
      dn: uid=newuser,ou=TestUsers,dc=owncloud,dc=com

      cn: newuser
      sn: newuser
      displayname: New User
      gecos: newuser
      gidnumber: 5000
      givenname: New User
      homedirectory: /home/openldap/newuser
      loginshell: /bin/bash
      mail: newuser@example.org
      objectclass: posixAccount
      objectclass: inetOrgPerson
      uid: newuser
      uidnumber: 50005
      userpassword: 123456
      """
    Then it should be possible to login with the username "newuser@example.org" and password "123456" using the WebUI
