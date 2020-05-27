@api @provisioning_api-app-required @skipOnOcV10.3 @skipOnOcis
Feature: Single user sync using the OCS API

  Background:
    Given these users have been created with default attributes and without skeleton files:
      | username |
      | user0    |
      | user1    |

  Scenario Outline: admin deletes ldap users and syncs only one of them
    Given using OCS API version "<ocs-api-version>"
    When the administrator deletes user "user0" using the provisioning API
    And the administrator deletes user "user1" using the provisioning API
    Then user "user0" should not exist
    And user "user1" should not exist
    When the administrator tries to sync user "user0" using the OCS API
    Then the HTTP status code should be "200"
    And the OCS status code should be "<ocs-status-code>"
    And user "user0" should exist
    And user "user1" should not exist
    Examples:
      | ocs-api-version | ocs-status-code |
      | 1               | 100             |
      | 2               | 200             |

  Scenario Outline: admin syncs after changing display name of a ldap user
    Given using OCS API version "<ocs-api-version>"
    When the administrator sets the ldap attribute "displayname" of the entry "uid=user0,ou=TestUsers" to "ldap user zero"
    When the administrator sets the ldap attribute "displayname" of the entry "uid=user1,ou=TestUsers" to "ldap user one"
    And the administrator tries to sync user "user0" using the OCS API
    Then the HTTP status code should be "200"
    And the OCS status code should be "<ocs-status-code>"
    And user "user0" should exist
    And the display name of user "user0" should be "ldap user zero"
    And the display name of user "user1" should be "User One"
    Examples:
      | ocs-api-version | ocs-status-code |
      | 1               | 100             |
      | 2               | 200             |

  Scenario Outline: admin syncs after changing email address of a ldap user
    Given using OCS API version "<ocs-api-version>"
    When the administrator sets the ldap attribute "mail" of the entry "uid=user0,ou=TestUsers" to "ldapuser0@example.com"
    When the administrator sets the ldap attribute "mail" of the entry "uid=user1,ou=TestUsers" to "ldapuser1@example.com"
    And the administrator tries to sync user "user0" using the OCS API
    Then the HTTP status code should be "200"
    And the OCS status code should be "<ocs-status-code>"
    And user "user0" should exist
    And the email address of user "user0" should be "ldapuser0@example.com"
    And the email address of user "user1" should be "user1@example.org"
    Examples:
      | ocs-api-version | ocs-status-code |
      | 1               | 100             |
      | 2               | 200             |
