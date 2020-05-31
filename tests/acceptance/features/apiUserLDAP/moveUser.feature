@api
Feature: move users between OUs

  Scenario: Moving a user between OUs by deleting an recreating the user
    Given the owncloud log level has been set to "info"
    And the owncloud log backend has been set to "owncloud"
    And the owncloud log has been cleared
    And user "Alice" has been created with default attributes and without skeleton files
    And user "Alice" has uploaded file with content "new file that should still exist" to "textfile_new.txt"
    When the administrator deletes the ldap entry "uid=Alice,ou=TestUsers"
    And the administrator imports this ldif data:
      """
      dn: uid=Alice,ou=TestGroups,dc=owncloud,dc=com
      cn: Alice
      sn: One
      displayname: User Zero
      gecos: Alice
      gidnumber: 5000
      givenname: Alice
      homedirectory: /home/openldap/Alice
      loginshell: /bin/bash
      mail: alice@example.org
      objectclass: posixAccount
      objectclass: inetOrgPerson
      uid: Alice
      uidnumber: 30000
      userpassword: 123456
      """
    Then the content of file "textfile_new.txt" for user "Alice" should be "new file that should still exist"
    And the log file should contain at least one entry matching each of these lines:
      | user | app                                    | method | message                                                                                               |
      | --   | OCA\User_LDAP\User\Manager::resolveUID | GET    | DN changed! Found user Alice by uuid Alice, updating dn to uid=alice,ou=testgroups,dc=owncloud,dc=com |
