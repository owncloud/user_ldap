# LDAP Integration
[![Code Coverage](https://scrutinizer-ci.com/g/owncloud/user_ldap/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/owncloud/user_ldap/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/owncloud/user_ldap/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/owncloud/user_ldap/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/owncloud/user_ldap/badges/build.png?b=master)](https://scrutinizer-ci.com/g/owncloud/user_ldap/build-status/master)

#### Additional configuration options that can be added to config.php

* `'user_ldap.enable_medial_search' => true`

    By default, when you search for a user your input string will match the beginning of the username. For example, if your LDAP server has "erl" and "peter" as users and you search with "er", only "erl" will be shown.

    Enabling this option allows you to overcome this limitation. In the example above, when this option is active, searching for "er" will find both users.

    Before enabling this option take into account the following things:

    * This option affects all LDAP connections. It isn't possible to enable this option for a specific connection.
    * This option could have a performance impact on big LDAP installations. Check your LDAP provider how to enable indexes for medial searches if they're supported but not active.
    * The option will work regardless of whether the LDAP server has an index for this. Small LDAP installations could have an acceptable performance with this option active even if the LDAP doesn't have that index active.

#### Additional configuration options that can be modified via occ configurations 

The user_ldap app  will check for updated attributes at every user login. Attributes like mail oder quota are retrieved from the ldap server. To save ressources on the ldap server, there is a minimum time between two updates for every user. Without modification, the update intervall is not more often then 86400 seconds (1 day). This can be modifed by setting the app config 'updateAttributesInterval' to any number you like. Setting this value to 0 will update on very login request which can be quiet often and stress your ldap server.
Attributes are unlikely to change very often, but waiting a day for a new quota is maybe a little bit long.

To allow modificatons of minimum time between checks to one hour (3600 seconds) you can do this via occ:
```occ config:app:set user_ldap updateAttributesInterval  --value=3600 ```
