@api
Feature: connect to LDAP serer

  Background:
    Given the owncloud log level has been set to "warning"
    And the owncloud log has been cleared

  Scenario: authentication fails when the configuration does not contain an ldap port
    Given LDAP config "LDAPTestId" has key "ldapPort" set to ""
    When user "user0" requests "/index.php/apps/files" with "GET" using basic auth
    Then the HTTP status code should be "401"
    And the last lines of the log file should contain log-entries with these attributes:
     | app       | message                                                      |
     | user_ldap | Configuration Error (prefix LDAPTestId): No LDAP Port given! |
    When the administrator sets the LDAP config "LDAPTestId" key "ldapPort" to "389" using the occ command
    And user "user0" requests "/index.php/apps/files" with "GET" using basic auth
    Then the HTTP status code should be "200"

  Scenario: authentication fails when the configuration has a wrong hostname
    Given LDAP config "LDAPTestId" has key "ldapHost" set to "not-existent"
    When user "user0" requests "/index.php/apps/files" with "GET" using basic auth
    Then the HTTP status code should be "401"
    And the last lines of the log file should contain log-entries containing these attributes:
     | app       | message                                                 |
     | user_ldap | Error when searching: Can't contact LDAP server code -1 |
     | user_ldap | Attempt for Paging?                                     |
     | core      | Login failed: 'user0' (Remote IP:                       |
    When the administrator sets the LDAP config "LDAPTestId" key "ldapHost" to "%ldap_host_without_scheme%" using the occ command
    And user "user0" requests "/index.php/apps/files" with "GET" using basic auth
    Then the HTTP status code should be "200"

  Scenario: authentication works when the hostname contains the protocol
    Given LDAP config "LDAPTestId" has key "ldapHost" set to "ldap://%ldap_host_without_scheme%"
    When user "user0" requests "/index.php/apps/files" with "GET" using basic auth
    Then the HTTP status code should be "200"

  Scenario: authentication works when the hostname does not contain the protocol
    Given LDAP config "LDAPTestId" has key "ldapHost" set to "%ldap_host_without_scheme%"
    When user "user0" requests "/index.php/apps/files" with "GET" using basic auth
    Then the HTTP status code should be "200"

  Scenario: authentication does not work when the hostname contains a wrong protocol
    Given LDAP config "LDAPTestId" has key "ldapHost" set to "http://%ldap_host_without_scheme%"
    When user "user0" requests "/index.php/apps/files" with "GET" using basic auth
    Then the HTTP status code should be "401"
    And the last lines of the log file should contain log-entries containing these attributes:
     | app       | message                                                                              |
     | PHP       | ldap_connect(): Could not create session handle: Bad parameter to an ldap routine at |
     | PHP       | ldap_set_option(): supplied argument is not a valid ldap link resource at            |

  @issue-49
  Scenario: authentication works when second of multiple configurations has an unreachable host configured
    Given a new LDAP config with the name "wrongLdapConfig" has been created
    And LDAP config "wrongLdapConfig" has these settings:
     | key                          | value |
     | ldapHost                     | notexisting |
     | ldapAgentName                | cn=admin,dc=owncloud,dc=com |
     | ldapAgentPassword            | admin |
     | ldapBase                     | dc=owncloud,dc=com |
     | ldapBaseGroups               | dc=owncloud,dc=com |
     | ldapBaseUsers                | dc=owncloud,dc=com |
     | ldapEmailAttribute           | mail |
     | ldapExpertUUIDUserAttr       | uid |
     | ldapGroupDisplayName         | cn |
     | ldapGroupFilter              | (&(\|(objectclass=posixGroup))) |
     | ldapGroupFilterObjectclass   | posixGroup |
     | ldapGroupMemberAssocAttr     | memberUid |
     | ldapLoginFilter              | (&(\|(objectclass=inetOrgPerson))(\|(uid=%uid)(\|(mailPrimaryAddress=%uid)(mail=%uid)))) |
     | ldapLoginFilterEmail         | 1 |
     | ldapLoginFilterMode          | 0 |
     | ldapLoginFilterUsername      | 1 |
     | ldapNestedGroups             | 0 |
     | ldapPagingSize               | 500 |
     | ldapPort                     | 389 |
     | ldapTLS                      | 0 |
     | ldapUserDisplayName          | displayName |
     | ldapUserFilter               | (\|(objectclass=inetOrgPerson)) |
     | ldapUserFilterMode           | 0 |
     | ldapUserFilterObjectclass    | inetOrgPerson |
     | ldapUuidGroupAttribute       | auto |
     | ldapUuidUserAttribute        | auto |
     | turnOffCertCheck             | 0 |
     | useMemberOfToDetectMembership| 1 |
     | ldapConfigurationActive      | 1 |
    When user "user0" requests "/index.php/apps/files" with "GET" using basic auth
    Then the HTTP status code should be "500"
    #Then the HTTP status code should be "200"

  @issue-49
  Scenario: authentication works when first of multiple configurations has an unreachable host configured
    Given LDAP config "LDAPTestId" has key "ldapHost" set to "not-existent"
    And a new LDAP config with the name "SecondaryLdapConfig" has been created
    And LDAP config "SecondaryLdapConfig" has these settings:
     | key                          | value |
     | ldapHost                     | %ldap_host_without_scheme% |
     | ldapAgentName                | cn=admin,dc=owncloud,dc=com |
     | ldapAgentPassword            | admin |
     | ldapBase                     | dc=owncloud,dc=com |
     | ldapBaseGroups               | dc=owncloud,dc=com |
     | ldapBaseUsers                | dc=owncloud,dc=com |
     | ldapEmailAttribute           | mail |
     | ldapExpertUUIDUserAttr       | uid |
     | ldapGroupDisplayName         | cn |
     | ldapGroupFilter              | (&(\|(objectclass=posixGroup))) |
     | ldapGroupFilterObjectclass   | posixGroup |
     | ldapGroupMemberAssocAttr     | memberUid |
     | ldapLoginFilter              | (&(\|(objectclass=inetOrgPerson))(\|(uid=%uid)(\|(mailPrimaryAddress=%uid)(mail=%uid)))) |
     | ldapLoginFilterEmail         | 1 |
     | ldapLoginFilterMode          | 0 |
     | ldapLoginFilterUsername      | 1 |
     | ldapNestedGroups             | 0 |
     | ldapPagingSize               | 500 |
     | ldapPort                     | 389 |
     | ldapTLS                      | 0 |
     | ldapUserDisplayName          | displayName |
     | ldapUserFilter               | (\|(objectclass=inetOrgPerson)) |
     | ldapUserFilterMode           | 0 |
     | ldapUserFilterObjectclass    | inetOrgPerson |
     | ldapUuidGroupAttribute       | auto |
     | ldapUuidUserAttribute        | auto |
     | turnOffCertCheck             | 0 |
     | useMemberOfToDetectMembership| 1 |
     | ldapConfigurationActive      | 1 |
    When user "user0" requests "/index.php/apps/files" with "GET" using basic auth
    Then the HTTP status code should be "500"
    #Then the HTTP status code should be "200"

  Scenario: authentication works when there are multiple configurations and both connect correctly to the same host
    Given a new LDAP config with the name "SecondaryLdapConfig" has been created
    And LDAP config "SecondaryLdapConfig" has these settings:
     | key                          | value |
     | ldapHost                     | %ldap_host_without_scheme% |
     | ldapAgentName                | cn=admin,dc=owncloud,dc=com |
     | ldapAgentPassword            | admin |
     | ldapBase                     | dc=owncloud,dc=com |
     | ldapBaseGroups               | dc=owncloud,dc=com |
     | ldapBaseUsers                | dc=owncloud,dc=com |
     | ldapEmailAttribute           | mail |
     | ldapExpertUUIDUserAttr       | uid |
     | ldapGroupDisplayName         | cn |
     | ldapGroupFilter              | (&(\|(objectclass=posixGroup))) |
     | ldapGroupFilterObjectclass   | posixGroup |
     | ldapGroupMemberAssocAttr     | memberUid |
     | ldapLoginFilter              | (&(\|(objectclass=inetOrgPerson))(\|(uid=%uid)(\|(mailPrimaryAddress=%uid)(mail=%uid)))) |
     | ldapLoginFilterEmail         | 1 |
     | ldapLoginFilterMode          | 0 |
     | ldapLoginFilterUsername      | 1 |
     | ldapNestedGroups             | 0 |
     | ldapPagingSize               | 500 |
     | ldapPort                     | 389 |
     | ldapTLS                      | 0 |
     | ldapUserDisplayName          | displayName |
     | ldapUserFilter               | (\|(objectclass=inetOrgPerson)) |
     | ldapUserFilterMode           | 0 |
     | ldapUserFilterObjectclass    | inetOrgPerson |
     | ldapUuidGroupAttribute       | auto |
     | ldapUuidUserAttribute        | auto |
     | turnOffCertCheck             | 0 |
     | useMemberOfToDetectMembership| 1 |
     | ldapConfigurationActive      | 1 |
    When user "user0" requests "/index.php/apps/files" with "GET" using basic auth
    Then the HTTP status code should be "200"

  Scenario: authentication fails when both configurations have an unreachable host configured
    Given LDAP config "LDAPTestId" has key "ldapHost" set to "not-existent"
    And a new LDAP config with the name "SecondaryLdapConfig" has been created
    And LDAP config "SecondaryLdapConfig" has these settings:
     | key                          | value |
     | ldapHost                     | also-not-there |
     | ldapAgentName                | cn=admin,dc=owncloud,dc=com |
     | ldapAgentPassword            | admin |
     | ldapBase                     | dc=owncloud,dc=com |
     | ldapBaseGroups               | dc=owncloud,dc=com |
     | ldapBaseUsers                | dc=owncloud,dc=com |
     | ldapEmailAttribute           | mail |
     | ldapExpertUUIDUserAttr       | uid |
     | ldapGroupDisplayName         | cn |
     | ldapGroupFilter              | (&(\|(objectclass=posixGroup))) |
     | ldapGroupFilterObjectclass   | posixGroup |
     | ldapGroupMemberAssocAttr     | memberUid |
     | ldapLoginFilter              | (&(\|(objectclass=inetOrgPerson))(\|(uid=%uid)(\|(mailPrimaryAddress=%uid)(mail=%uid)))) |
     | ldapLoginFilterEmail         | 1 |
     | ldapLoginFilterMode          | 0 |
     | ldapLoginFilterUsername      | 1 |
     | ldapNestedGroups             | 0 |
     | ldapPagingSize               | 500 |
     | ldapPort                     | 389 |
     | ldapTLS                      | 0 |
     | ldapUserDisplayName          | displayName |
     | ldapUserFilter               | (\|(objectclass=inetOrgPerson)) |
     | ldapUserFilterMode           | 0 |
     | ldapUserFilterObjectclass    | inetOrgPerson |
     | ldapUuidGroupAttribute       | auto |
     | ldapUuidUserAttribute        | auto |
     | turnOffCertCheck             | 0 |
     | useMemberOfToDetectMembership| 1 |
     | ldapConfigurationActive      | 1 |
    When user "user0" requests "/index.php/apps/files" with "GET" using basic auth
    Then the HTTP status code should be "401"
    And the last lines of the log file should contain log-entries containing these attributes:
     | app       | message                                                 |
     | user_ldap | Error when searching: Can't contact LDAP server code -1 |
     | user_ldap | Attempt for Paging?                                     |
     | core      | Login failed: 'user0' (Remote IP:                       |   
