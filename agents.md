# agents.md — user_ldap

## Repository Overview

The LDAP Integration app connects ownCloud Server to LDAP and Active Directory for user authentication and group management. Supports multiple LDAP connections, configurable attribute mapping, and medial search.

- **Classification:** Classic (OC10)
- **Activity Status:** Active
- **License:** AGPL-3.0
- **Language:** PHP, JavaScript

## Architecture & Key Paths

- `appinfo/` — ownCloud app metadata (info.xml, routes)
- `lib/` — PHP backend (LDAP connection, user/group providers, attribute mapping)
- `js/` — Frontend JavaScript (settings UI)
- `css/` — Stylesheets
- `l10n/` — Localization/translation files
- `templates/` — PHP templates for LDAP configuration UI
- `tests/` — PHPUnit and acceptance tests
- `Makefile` — Build and test orchestration
- `composer.json` — PHP dependencies
- `package.json` — JavaScript dependencies
- `phpcs.xml` — Code style configuration
- `phpstan.neon` — Static analysis configuration

## Development Conventions

- Standard ownCloud OC10 app structure
- Code style enforced by phpcs
- PHPStan for static analysis
- SonarCloud integration
- Bower for legacy JS dependencies (`bower.json`)

## Build & Test Commands

```bash
make all                    # Install all dependencies (Bower + Composer)
make dist                   # Build distribution package
make clean                  # Clean build artifacts
make test-php-unit          # Run PHP unit tests
make test-php-style         # Check code style (phpcs)
make test-php-style-fix     # Auto-fix style issues
make test-php-phpstan       # Run PHPStan
make test-php-phan          # Run Phan
make test-acceptance-api    # Run LDAP API acceptance tests
make test-acceptance-cli    # Run LDAP CLI acceptance tests
make test-acceptance-webui  # Run LDAP webUI acceptance tests
make test-acceptance-core-api    # Run core API acceptance tests
make test-acceptance-core-cli    # Run core CLI acceptance tests
make test-acceptance-core-webui  # Run core webUI acceptance tests
```

## Important Constraints

- **AGPL-3.0 copyleft license:** The OSPO Apache 2.0 migration requires auditing this copyleft license and all contributor agreements.
- **Not compatible with user_external:** Cannot be used alongside the `user_external` WebDAV authentication backend.
- **LDAP server dependency:** Requires a running LDAP or Active Directory server.
- **Performance considerations:** Medial search (`user_ldap.enable_medial_search`) may impact performance on large LDAP installations without proper indexing.
- **Attribute update interval:** Configurable via `updateAttributesInterval` app config (default: 86400 seconds / 1 day).


## OSPO Policy Constraints

### GitHub Actions
- **Only** use actions owned by `owncloud`, created by GitHub (`actions/*`), verified on the GitHub Marketplace, or verified by the ownCloud Maintainers.
- Pin all actions to their full commit SHA (not tags): `uses: actions/checkout@<SHA> # vX.Y.Z`
- Never introduce actions from unverified third parties.

### Dependency Management
- Dependabot is configured for automated dependency updates.
- Review and merge Dependabot PRs as part of regular maintenance.
- Do not introduce new dependencies without discussion in an issue first.

### Git Workflow
- **Rebase policy**: Always rebase; never create merge commits. Use `git pull --rebase` and `git rebase` before pushing.
- **Signed commits**: All commits **must** be PGP/GPG signed (`git commit -S -s`).
- **DCO sign-off**: Every commit needs a `Signed-off-by` line (`git commit -s`).
- **Conventional Commits & Squash Merge**: Use the [Conventional Commits](https://www.conventionalcommits.org/) format where the repository enforces it. Many repos use squash merge, where the PR title becomes the commit message on the default branch — apply Conventional Commits format to PR titles as well. A reusable GitHub Actions workflow enforces this.

## Context for AI Agents

- This is an ownCloud Server (OC10) app, not an oCIS extension. oCIS has its own LDAP integration.
- The `lib/` directory contains LDAP connection management, user/group providers and attribute mapping.
- Configuration is done through the ownCloud admin settings UI and `config.php`.
- Multiple LDAP server connections are supported.
- The app syncs users and groups from LDAP to the local ownCloud database.
