@api @provisioning_api-app-required
Feature: Manage users using the Provisioning API
  As an administrator
  I want to be able to add, delete and modify users via the Provisioning API
  So that I can easily manage users when user LDAP is enabled

  Scenario Outline: Admin creates a regular user
    Given using OCS API version "<ocs-api-version>"
    And user "brand-new-user" has been deleted
    When the administrator sends a user creation request for user "brand-new-user" password "%alt1%" using the provisioning API
    Then the OCS status code should be "<ocs-status-code>"
    And the HTTP status code should be "<http-status-code>"
    And user "brand-new-user" should exist
    And user "brand-new-user" should be able to access a skeleton file
    Examples:
      | ocs-api-version | ocs-status-code | http-status-code |
      | 1               | 100             | 200              |
      | 2               | 200             | 200              |

  Scenario Outline: Admin deletes a regular user
    Given using OCS API version "<ocs-api-version>"
    And user "brand-new-user" has been created with default attributes in the database user backend
    When the administrator deletes user "brand-new-user" using the provisioning API
    Then the OCS status code should be "<ocs-status-code>"
    And the HTTP status code should be "<http-status-code>"
    And user "brand-new-user" should not exist
    Examples:
      | ocs-api-version | ocs-status-code | http-status-code |
      | 1               | 100             | 200              |
      | 2               | 200             | 200              |

  Scenario Outline: Administrator can edit a user email
    Given using OCS API version "<ocs-api-version>"
    And user "brand-new-user" has been created with default attributes in the database user backend
    When the administrator changes the email of user "brand-new-user" to "brand-new-user@example.com" using the provisioning API
    Then the OCS status code should be "<ocs-status-code>"
    And the HTTP status code should be "<http-status-code>"
    And the email address of user "brand-new-user" should be "brand-new-user@example.com"
    Examples:
      | ocs-api-version | ocs-status-code | http-status-code |
      | 1               | 100             | 200              |
      | 2               | 200             | 200              |

  Scenario Outline: the administrator can edit a user display (the API allows editing the "display name" by using the key word "display")
    Given using OCS API version "<ocs-api-version>"
    And user "brand-new-user" has been created with default attributes in the database user backend
    When the administrator changes the display of user "brand-new-user" to "A New User" using the provisioning API
    Then the OCS status code should be "<ocs-status-code>"
    And the HTTP status code should be "<http-status-code>"
    And the display name of user "brand-new-user" should be "A New User"
    Examples:
      | ocs-api-version | ocs-status-code | http-status-code |
      | 1               | 100             | 200              |
      | 2               | 200             | 200              |

  Scenario Outline: the administrator can edit a user display name
    Given using OCS API version "<ocs-api-version>"
    And user "brand-new-user" has been created with default attributes in the database user backend
    When the administrator changes the display name of user "brand-new-user" to "A New User" using the provisioning API
    Then the OCS status code should be "<ocs-status-code>"
    And the HTTP status code should be "<http-status-code>"
    And the display name of user "brand-new-user" should be "A New User"
    Examples:
      | ocs-api-version | ocs-status-code | http-status-code |
      | 1               | 100             | 200              |
      | 2               | 200             | 200              |

  Scenario Outline: the administrator can edit a user quota
    Given using OCS API version "<ocs-api-version>"
    And user "brand-new-user" has been created with default attributes in the database user backend
    When the administrator changes the quota of user "brand-new-user" to "12MB" using the provisioning API
    Then the OCS status code should be "<ocs-status-code>"
    And the HTTP status code should be "<http-status-code>"
    And the quota definition of user "brand-new-user" should be "12 MB"
    Examples:
      | ocs-api-version | ocs-status-code | http-status-code |
      | 1               | 100             | 200              |
      | 2               | 200             | 200              |

  @issue-core-33186
  Scenario Outline: admin tries to modify displayname of user for which an LDAP attribute is specified
    Given using OCS API version "<ocs-api-version>"
    When the administrator sets the ldap attribute "displayname" of the entry "uid=user1,ou=TestUsers" to "ldap user"
    And the LDAP users are resynced
    When the administrator changes the display of user "user1" to "A New User" using the provisioning API
    Then the OCS status code should be "<ocs-status-code>"
    And the HTTP status code should be "<http-status-code>"
    And the display name of user "user1" should be "ldap user"
    Examples:
      | ocs-api-version | ocs-status-code | http-status-code |
      | 1               | 100             | 200              |
      | 2               | 200             | 200              |
    # | 1               | 102             | 200              |
    # | 2               | 400             | 400              |

  @issue-core-33186
  Scenario Outline: admin tries to modify password of user for which an LDAP attribute is specified
    Given using OCS API version "<ocs-api-version>"
    When the administrator sets the ldap attribute "userpassword" of the entry "uid=user1,ou=TestUsers" to "ldap_password"
    And the LDAP users are resynced
    And the administrator resets the password of user "user1" to "api_password" using the provisioning API
    Then the OCS status code should be "<ocs-status-code>"
    And the HTTP status code should be "<http-status-code>"
    And the content of file "textfile0.txt" for user "user1" using password "ldap_password" should be "ownCloud test text file 0" plus end-of-line
    But user "brand-new-user" using password "api_password" should not be able to download file "textfile0.txt"
    Examples:
      | ocs-api-version | ocs-status-code | http-status-code |
      | 1               | 100             | 200              |
      | 2               | 200             | 200              |
    # | 1               | 102             | 200              |
    # | 2               | 400             | 400              |

  @issue-core-33186
  Scenario Outline: admin tries to modify mail of user for which an LDAP attribute is specified
    Given using OCS API version "<ocs-api-version>"
    When the administrator sets the ldap attribute "mail" of the entry "uid=user1,ou=TestUsers" to "ldapuser@oc.com"
    And the LDAP users are resynced
    And the administrator changes the email of user "user1" to "apiuser@example.com" using the provisioning API
    Then the OCS status code should be "<ocs-status-code>"
    And the HTTP status code should be "<http-status-code>"
    # And the email address of user "user1" should be "ldapuser@oc.com"
    And the email address of user "user1" should be "apiuser@example.com"
    And the LDAP users are resynced
    And the email address of user "user1" should be "ldapuser@oc.com"
    Examples:
      | ocs-api-version | ocs-status-code | http-status-code |
      | 1               | 100             | 200              |
      | 2               | 200             | 200              |
    # | 1               | 102             | 200              |
    # | 2               | 400             | 400              |

  @issue-core-33186
  Scenario Outline: admin tries to modify quota of user for which an LDAP attribute is specified
    Given using OCS API version "<ocs-api-version>"
    #to set Quota we can just misuse any LDAP text field
    And LDAP config "LDAPTestId" has key "ldapQuotaAttribute" set to "employeeNumber"
    When the administrator sets the ldap attribute "employeeNumber" of the entry "uid=user1,ou=TestUsers" to "10 MB"
    And the LDAP users are resynced
    And the administrator changes the quota of user "user1" to "13MB" using the provisioning API
    Then the OCS status code should be "<ocs-status-code>"
    And the HTTP status code should be "<http-status-code>"
    And the quota definition of user "user1" should be "13 MB"
    #And the quota definition of user "user1" should be "10 MB"
    When the LDAP users are resynced
    Then the quota definition of user "user1" should be "10 MB"
    Examples:
      | ocs-api-version | ocs-status-code | http-status-code |
      | 1               | 100             | 200              |
      | 2               | 200             | 200              |
    # | 1               | 102             | 200              |
    # | 2               | 400             | 400              |

  Scenario Outline: admin sets quota of user for which no LDAP quota attribute is specified
    Given using OCS API version "<ocs-api-version>"
    #to set Quota we can just misuse any LDAP text field
    And LDAP config "LDAPTestId" has key "ldapQuotaAttribute" set to "employeeNumber"
    And the LDAP users have been resynced
    When the administrator changes the quota of user "user1" to "13MB" using the provisioning API
    Then the OCS status code should be "<ocs-status-code>"
    And the HTTP status code should be "<http-status-code>"
    And the quota definition of user "user1" should be "13 MB"
    When the LDAP users are resynced
    Then the quota definition of user "user1" should be "13 MB"
    Examples:
      | ocs-api-version | ocs-status-code | http-status-code |
      | 1               | 100             | 200              |
      | 2               | 200             | 200              |

  @issue-core-33186
  Scenario Outline: admin sets quota of user for which no LDAP quota attribute is specified but a default quota is set in the LDAP settings
    Given using OCS API version "<ocs-api-version>"
    #to set Quota we can just misuse any LDAP text field
    And LDAP config "LDAPTestId" has key "ldapQuotaAttribute" set to "employeeNumber"
    And LDAP config "LDAPTestId" has key "ldapQuotaDefault" set to "10MB"
    And the LDAP users have been resynced
    When the administrator changes the quota of user "user1" to "13MB" using the provisioning API
    Then the OCS status code should be "<ocs-status-code>"
    And the HTTP status code should be "<http-status-code>"
    And the quota definition of user "user1" should be "13 MB"
    #And the quota definition of user "user1" should be "10MB"
    And the LDAP users are resynced
    And the quota definition of user "user1" should be "10MB"
    Examples:
      | ocs-api-version | ocs-status-code | http-status-code |
      | 1               | 100             | 200              |
      | 2               | 200             | 200              |
    # | 1               | 102             | 200              |
    # | 2               | 400             | 400              |

  Scenario Outline: admin sets quota of user in LDAP when a default quota is set in the LDAP settings
    Given using OCS API version "<ocs-api-version>"
    #to set Quota we can just misuse any LDAP text field
    And LDAP config "LDAPTestId" has key "ldapQuotaAttribute" set to "employeeNumber"
    And LDAP config "LDAPTestId" has key "ldapQuotaDefault" set to "10MB"
    And the LDAP users have been resynced
    When the administrator sets the ldap attribute "employeeNumber" of the entry "uid=user1,ou=TestUsers" to "13 MB"
    Then the quota definition of user "user1" should be "10MB"
    And the LDAP users are resynced
    And the quota definition of user "user1" should be "13 MB"
    Examples:
      | ocs-api-version | ocs-status-code | http-status-code |
      | 1               | 100             | 200              |
      | 2               | 200             | 200              |

  @issue-core-33186
  Scenario Outline: admin sets quota of user when the quota LDAP attribute is specified and a default quota is set in the LDAP settings
    Given using OCS API version "<ocs-api-version>"
    #to set Quota we can just misuse any LDAP text field
    And LDAP config "LDAPTestId" has key "ldapQuotaAttribute" set to "employeeNumber"
    And LDAP config "LDAPTestId" has key "ldapQuotaDefault" set to "10MB"
    When the administrator sets the ldap attribute "employeeNumber" of the entry "uid=user1,ou=TestUsers" to "11 MB"
    And the LDAP users are resynced
    And the administrator changes the quota of user "user1" to "13MB" using the provisioning API
    Then the OCS status code should be "<ocs-status-code>"
    And the HTTP status code should be "<http-status-code>"
    And the quota definition of user "user1" should be "13 MB"
    #And the quota definition of user "user1" should be "11 MB"
    And the LDAP users are resynced
    And the quota definition of user "user1" should be "11 MB"
    Examples:
      | ocs-api-version | ocs-status-code | http-status-code |
      | 1               | 100             | 200              |
      | 2               | 200             | 200              |
    # | 1               | 102             | 200              |
    # | 2               | 400             | 400              |

  Scenario Outline: Administrator deletes a ldap user and resyncs again
    Given using OCS API version "<ocs-api-version>"
    And user "user0" has uploaded file with content "new file that should be overwritten after user deletion" to "textfile0.txt"
    When the administrator deletes user "user0" using the provisioning API
    Then the OCS status code should be "<ocs-status-code>"
    And the HTTP status code should be "<http-status-code>"
    And user "user0" should not exist
    When the LDAP users are resynced
    Then user "user0" should exist
    And the content of file "textfile0.txt" for user "user0" using password "123456" should be "ownCloud test text file 0" plus end-of-line
    Examples:
      | ocs-api-version | ocs-status-code | http-status-code |
      | 1               | 100             | 200              |
      | 2               | 200             | 200              |

  Scenario Outline: Administrator tries to create a user with same name as existing ldap user
    Given using OCS API version "<ocs-api-version>"
    When the administrator sends a user creation request for user "user0" password "%alt1%" using the provisioning API
    Then the OCS status code should be "<ocs-status-code>"
    And the HTTP status code should be "<http-status-code>"
    And the API should not return any data
    And user "user0" should exist
    And the content of file "textfile0.txt" for user "user0" using password "123456" should be "ownCloud test text file 0" plus end-of-line
    But user "user0" using password "%alt1%" should not be able to download file "textfile0.txt"
    Examples:
      | ocs-api-version | ocs-status-code | http-status-code |
      | 1               | 102             | 200              |
      | 2               | 400             | 400              |