@api
Feature: connect to a backup LDAP serer

  As an administrator
  I want to be able to set up a backup LDAP server
  So that user authentication still works when the main LDAP server is not reachable

  Background:
    Given the owncloud log level has been set to "warning"
    And the owncloud log has been cleared
    And user "Alice" has been created with default attributes and small skeleton files

  Scenario: authentication works when the main server is not reachable but the backup server is
    Given LDAP config "LDAPTestId" has key "ldapHost" set to "not-existent"
    And LDAP config "LDAPTestId" has key "ldapBackupHost" set to "%ldap_host%"
    And LDAP config "LDAPTestId" has key "ldapBackupPort" set to "%ldap_port%"
    When user "Alice" requests "/index.php/apps/files" with "GET" using basic auth
    Then the HTTP status code should be "200"

  Scenario: authentication works when the main server and the backup server are reachable
    Given LDAP config "LDAPTestId" has key "ldapBackupHost" set to "%ldap_host%"
    And LDAP config "LDAPTestId" has key "ldapBackupPort" set to "%ldap_port%"
    When user "Alice" requests "/index.php/apps/files" with "GET" using basic auth
    Then the HTTP status code should be "200"

  Scenario: authentication works when the backup server is not reachable but the main server is
    Given LDAP config "LDAPTestId" has key "ldapBackupHost" set to "not-existent"
    And LDAP config "LDAPTestId" has key "ldapBackupPort" set to "%ldap_port%"
    When user "Alice" requests "/index.php/apps/files" with "GET" using basic auth
    Then the HTTP status code should be "200"

  Scenario Outline: authentication fails when the main server and backup server is not reachable and works again when one server comes back
    Given LDAP config "LDAPTestId" has key "ldapHost" set to "not-existent"
    And LDAP config "LDAPTestId" has key "ldapBackupHost" set to "not-existent"
    And LDAP config "LDAPTestId" has key "ldapBackupPort" set to "%ldap_port%"
    When user "Alice" requests "/index.php/apps/files" with "GET" using basic auth
    Then the HTTP status code should be "401"
    And the last lines of the log file should contain log-entries containing these attributes:
      | app       | message                                                 |
      | user_ldap | Error when searching: Can't contact LDAP server code -1 |
      | user_ldap | Attempt for Paging?                                     |
      | core      | Login failed: 'Alice' (Remote IP:                       |
    When the administrator sets the LDAP config "LDAPTestId" key "<server-that-comes-back>" to "%ldap_host%" using the occ command
    And user "Alice" requests "/index.php/apps/files" with "GET" using basic auth
    Then the HTTP status code should be "200"
    Examples:
      | server-that-comes-back |
      | ldapHost               |
      | ldapBackupHost         |

  Scenario: password changes on the backup server are applied to oC when the main server is not reachable
    Given LDAP config "LDAPTestId" has key "ldapHost" set to "not-existent"
    And LDAP config "LDAPTestId" has key "ldapBackupHost" set to "%ldap_host%"
    And LDAP config "LDAPTestId" has key "ldapBackupPort" set to "%ldap_port%"
    When the administrator sets the ldap attribute "userpassword" of the entry "uid=Alice,ou=TestUsers" to "new-password"
    Then user "Alice" using password "%regular%" should not be able to download file "textfile0.txt"
    But the content of file "textfile0.txt" for user "Alice" using password "new-password" should be "ownCloud test text file 0" plus end-of-line
