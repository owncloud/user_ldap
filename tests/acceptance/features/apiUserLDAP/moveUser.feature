@api
Feature: move users between OUs

  Scenario: Moving a user between OUs by deleting an recreating the user
    Given the owncloud log level has been set to "info"
    And the owncloud log backend has been set to "owncloud"
    And the owncloud log has been cleared
    And user "user0" has been created with default attributes and without skeleton files
    And user "user0" has uploaded file with content "new file that should still exist" to "textfile_new.txt"
    When the administrator deletes the ldap entry "uid=user0,ou=TestUsers"
    And the administrator imports this ldif data:
      """
      dn: uid=user0,ou=TestGroups,dc=owncloud,dc=com
      cn: User0
      sn: One
      displayname: User Zero
      gecos: User0
      gidnumber: 5000
      givenname: User0
      homedirectory: /home/openldap/user0
      loginshell: /bin/bash
      mail: user0@example.org
      objectclass: posixAccount
      objectclass: inetOrgPerson
      uid: user0
      uidnumber: 30000
      userpassword: 123456
      """
    Then the content of file "textfile_new.txt" for user "user0" should be "new file that should still exist"
    And the log file should contain at least one entry matching each of these lines:
      | user | app                                    | method | message                                                                                               |
      | --   | OCA\User_LDAP\User\Manager::resolveUID | GET    | DN changed! Found user user0 by uuid user0, updating dn to uid=user0,ou=testgroups,dc=owncloud,dc=com |


