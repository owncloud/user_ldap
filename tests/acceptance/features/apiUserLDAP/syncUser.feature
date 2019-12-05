@api
Feature: sync a user

  Scenario Outline: Trying to sync a user that has not changed
    Given using OCS API version "<ocs-api-version>"
    When the administrator uses the UUID to sync entry "uid=user0,ou=TestUsers" using the OCS API
    Then the HTTP status code should be "200"
    And the OCS status code should be "<ocs-status-code>"
    And the OCS status message should be ""
    Examples:
      | ocs-api-version | ocs-status-code |
      | 1               | 100             |
      | 2               | 200             |
