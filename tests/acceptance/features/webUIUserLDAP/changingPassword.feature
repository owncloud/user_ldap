@webUI @insulated @disablePreviews
Feature: changing password

  As an user
  I want to change my LDAP password and be able to use it in owncloud
  So that I do not have to remember multiple passwords

  Scenario Outline: change password on the LDAP server
    Given user "user1" has been created with default attributes and without skeleton files
    When the administrator sets the ldap attribute "userpassword" of the entry "uid=user1,ou=TestUsers" to "<new-password>"
    Then it should not be possible to login with the username "user1" and password "%alt1%" using the WebUI
    But it should be possible to login with the username "user1" and password "<new-password>" using the WebUI
    Examples:
      | new-password |
      | 999          |
      | 0            |
      | пароль       |
      | null         |