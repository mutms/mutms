# Change log

Plugin versioning is derived from Moodle releases, it does not comply with the semantic versioning standard.

The format of this change log follows the advice given at [Keep a CHANGELOG](https://keepachangelog.com).

## [mu-5.1.2-01] - 2026-02-08

- No changes

## [mu-5.1.1-04] - 2026-01-25

### Fixed

- Added internal key to Tenant management primary menu
- Fixed usage of legacy moodle_url class

## [mu-5.1.1-03] - 2025-12-31

### Fixed

- Added "Copy tenant login URL to clipboard" label to copy icons to improve accessibility

### Added

- Added pre_tenant_delete hook
- Added tenant activation and deactivation to Config changes report

### Changed

- Switched to new change log format
- Tenant login URL in PDF and HTML exports was changed to link.
- Removed \tool_mutenancy\output\loginurl renderable and template

## [mu-5.1.1-02] - 2025-12-16

- No changes.

## [mu-5.1.1-01] - 2025-12-08

- Added new setting to allow guest access to tenants.
- Added tenant restriction to get_with_capability_sql().
- Fixed listing of tenant contexts on permissions related pages.
- Tidied up autocomplete web services and improved performance on large sites.
- List of tenants in management UI is sorted by name by default
- Added tenant entity name - it is not necessary to edit language packs to replace "Tenant" and "Tenants" words in UI
- Added tenantid in core WS: core_user_create_users, core_user_get_users_by_field, core_user_get_users 
- Added web services for management of tenants.
- Tenant switching has been simplified: associated users and tenant managers can now switch tenants by default. Internally, the tool/mutenancy:switch capability is now used in the tenant context instead of the system context, and no longer requires the tool/mutenancy:view capability. Existing tenant manager roles need to be updated manually to include the switch permission.

## [mu-5.1.0-02] - 2025-11-08

- No changes.

## [mu-5.1.0-01] - 2025-10-06

- Added support for Moodle 5.1.0 release.
