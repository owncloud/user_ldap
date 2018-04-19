# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)

## [0.11.0] - 2018-04-19

### Added

- Ability to output ldap configurations (`ldap:show-config`) as json [#185](https://github.com/owncloud/user_ldap/pull/185) 

### Changed

- Frontend routes converted to proper Controllers [#199](https://github.com/owncloud/user_ldap/pull/199)
- Fully leverage core account synchronisation [#156](https://github.com/owncloud/user_ldap/pull/156)
- Improved error log messages [#194](https://github.com/owncloud/user_ldap/pull/194)

### Fixed

- Error with encrypted storage when a unsynchronized user logs in for the first time [#178](https://github.com/owncloud/user_ldap/pull/178)
- Properly use filters when synchronizing mapped users by dn [#168](https://github.com/owncloud/user_ldap/pull/168)
- Fallback to ownClouds default quota, if the provided quota by ldap can not be parsed correctly [#153](https://github.com/owncloud/user_ldap/issues/153)

## [0.10.0] - 2017-12-20

### Fixed
- Rework LDAP app to match account table logic [#125](https://github.com/owncloud/user_ldap/issues/125)
- Use custom uuid attribute if configured - [#158](https://github.com/owncloud/user_ldap/issues/158)
- Sync displayname on login - [#157](https://github.com/owncloud/user_ldap/issues/157)
- Fix working with LDAP replica server - [#138](https://github.com/owncloud/user_ldap/issues/138)
- Allow specifying the prefix for occ ldap:create-empty-config - [#7](https://github.com/owncloud/user_ldap/issues/7)
- Remove fix for ldap installation - [#132](https://github.com/owncloud/user_ldap/issues/132)
- Make the time between needsRefresh configurable - [#120](https://github.com/owncloud/user_ldap/issues/120)
- Keep the current quota if no suitable quota is found - [#123](https://github.com/owncloud/user_ldap/issues/123)
- Only use IndexIgnore if mod_autoindex.c is enabled/loaded - [#112](https://github.com/owncloud/user_ldap/issues/112)
- Remove unneeded account updates during sync - [#109](https://github.com/owncloud/user_ldap/issues/109)
- Fix possible race condition - [#8](https://github.com/owncloud/user_ldap/issues/8)
- Remove automatic enable of a configuration - [#10](https://github.com/owncloud/user_ldap/issues/10)
- Add missing spaces to log message - [#110](https://github.com/owncloud/user_ldap/issues/110)
- Add hint for max search term length - [#105](https://github.com/owncloud/user_ldap/issues/105)
- Allow proxy to check next server - [#101](https://github.com/owncloud/user_ldap/issues/101)

[0.11.0]: https://github.com/owncloud/user_ldap/compare/v0.10.0...v0.11.0
[0.10.0]: https://github.com/owncloud/user_ldap/compare/0.9.1...v0.10.0