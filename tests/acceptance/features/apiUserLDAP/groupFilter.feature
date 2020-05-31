@api
Feature: filter groups
  As an administrator
  I want to be able to filter LDAP groups
  So that only groups meeting specific criteria are available in ownCloud

  Background:
    Given these groups have been created:
      | groupname    |
      | grp1         |
      | grp2         |
      | group1       |
      | group2       |
      | ShareeGroup  |
      | ShareeGroup2 |

  Scenario: single group filter
    When the administrator sets these settings of LDAP config "LDAPTestId" using the occ command
      | key             | value                                        |
      | ldapGroupFilter | (&(\|(objectclass=posixGroup))(\|(cn=grp2))) |
    And the administrator gets the groups in JSON format using the occ command
    Then the command should have been successful
    And the groups returned by the occ command should be
      | group |
      | admin |
      | grp2  |

  Scenario: filter with asterisk
    When the administrator sets these settings of LDAP config "LDAPTestId" using the occ command
      | key             | value                                           |
      | ldapGroupFilter | (&(\|(objectclass=posixGroup))(\|(cn=Sharee*))) |
    And the administrator gets the groups in JSON format using the occ command
    Then the command should have been successful
    And the groups returned by the occ command should be
      | group        |
      | admin        |
      | ShareeGroup  |
      | ShareeGroup2 |

  Scenario: filter for multiple groups
    When the administrator sets these settings of LDAP config "LDAPTestId" using the occ command
      | key             | value                                                     |
      | ldapGroupFilter | (&(\|(objectclass=posixGroup))(\|(cn=group1)(cn=group2))) |
    And the administrator gets the groups in JSON format using the occ command
    Then the command should have been successful
    And the groups returned by the occ command should be
      | group  |
      | admin  |
      | group1 |
      | group2 |

  Scenario: filter groups that are in multiple OUs but have the same CN
    Given the administrator has imported this ldif data:
      """
      dn: cn=grp1,ou=TestUsers,dc=owncloud,dc=com
      cn: grp1
      gidnumber: 500
      objectclass: top
      objectclass: posixGroup
      """
    When the administrator sets these settings of LDAP config "LDAPTestId" using the occ command
      | key             | value                                        |
      | ldapGroupFilter | (&(\|(objectclass=posixGroup))(\|(cn=grp1))) |
    And the administrator gets the groups in JSON format using the occ command
    Then the command should have been successful
    And the groups returned by the occ command should be
      | group |
      | admin |
      | grp1  |
