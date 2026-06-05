# Change log

Plugin versioning is derived from Moodle releases, it does not comply with the semantic versioning standard.

The format of this change log follows the advice given at [Keep a CHANGELOG](https://keepachangelog.com).

## [v5.0.8.01](https://github.com/mutms/moodle-tool_mutenancy/compare/v5.0.7.01...v5.0.8.01) - 2026-06-05

- No changes

## [v5.0.7.01](https://github.com/mutms/moodle-tool_mutenancy/compare/v5.0.6.06...v5.0.7.01) - 2026-04-19

### Changed

- Compatible with Moodle 5.0.7

## [v5.0.6.06](https://github.com/mutms/moodle-tool_mutenancy/compare/v5.0.6.05...v5.0.6.06) - 2026-03-29

- No changes

## [v5.0.6.05](https://github.com/mutms/moodle-tool_mutenancy/compare/v5.0.6.04...v5.0.6.05) - 2026-03-28

- No changes

## [v5.0.6.04](https://github.com/mutms/moodle-tool_mutenancy/compare/v5.0.6.03...v5.0.6.04) - 2026-03-27

### Added

- Added composer.json for Packagist distribution

## [v5.0.6.03](https://github.com/mutms/moodle-tool_mutenancy/compare/v5.0.6.02...v5.0.6.03) - 2026-03-26

### Fixed

- Fixed error when non-administrators access My courses page

## [v5.0.6.02](https://github.com/mutms/moodle-tool_mutenancy/compare/v5.0.6.01...v5.0.6.02) - 2026-03-01

### Changed

- Patch repository https://github.com/mutms/moodle.git was renamed to https://github.com/mutms/patches.git

## [v5.0.6.01](https://github.com/mutms/moodle-tool_mutenancy/compare/mu-5.0.5-01...v5.0.6.01) - 2026-02-12

### Changed

- Switched to new release number format to prepare for composer support

## [mu-5.0.5-01](https://github.com/mutms/moodle-tool_mutenancy/compare/mu-5.0.4-04...mu-5.0.5-01) - 2026-02-08

- No changes

## [mu-5.0.4-04](https://github.com/mutms/moodle-tool_mutenancy/compare/mu-5.0.4-03...mu-5.0.4-04) - 2026-01-25

### Fixed

- Added internal key to Tenant management primary menu
- Fixed usage of legacy moodle_url class

## [mu-5.0.4-03](https://github.com/mutms/moodle-tool_mutenancy/compare/mu-5.0.4-02...mu-5.0.4-03) - 2025-12-31

### Fixed

- Added "Copy tenant login URL to clipboard" label to copy icons to improve accessibility

### Added

- Added pre_tenant_delete hook
- Added tenant activation and deactivation to Config changes report

### Changed

- Switched to new change log format
- Tenant login URL in PDF and HTML exports was changed to link.
- Removed \tool_mutenancy\output\loginurl renderable and template

## [mu-5.0.4-02](https://github.com/mutms/moodle-tool_mutenancy/compare/mu-5.0.4-01...mu-5.0.4-02) - 2025-12-16

- No changes.

## [mu-5.0.4-01](https://github.com/mutms/moodle-tool_mutenancy/compare/mu-5.0.3-02...mu-5.0.4-01) - 2025-12-08

- Added new setting to allow guest access to tenants.
- Added tenant restriction to get_with_capability_sql().
- Fixed listing of tenant contexts on permissions related pages.
- Tidied up autocomplete web services and improved performance on large sites.
- List of tenants in management UI is sorted by name by default
- Added tenant entity name - it is not necessary to edit language packs to replace "Tenant" and "Tenants" words in UI
- Added tenantid in core WS: core_user_create_users, core_user_get_users_by_field, core_user_get_users 
- Added web services for management of tenants.
- Tenant switching has been simplified: associated users and tenant managers can now switch tenants by default. Internally, the tool/mutenancy:switch capability is now used in the tenant context instead of the system context, and no longer requires the tool/mutenancy:view capability. Existing tenant manager roles need to be updated manually to include the switch permission.

## [mu-5.0.3-02](https://github.com/mutms/moodle-tool_mutenancy/compare/mu-5.0.3-01...mu-5.0.3-02) - 2025-11-08

- No changes.

## [mu-5.0.3-01](https://github.com/mutms/moodle-tool_mutenancy/compare/mu-5.0.2-03...mu-5.0.3-01) - 2025-10-06

- No changes.

## [mu-5.0.2-03](https://github.com/mutms/moodle-tool_mutenancy/compare/mu-5.0.2-02...mu-5.0.2-03) - 2025-09-24

- Added event for user tenant allocation changes.

## [mu-5.0.2-02](https://github.com/mutms/moodle-tool_mutenancy/compare/mu-5.0.2-01...mu-5.0.2-02) - 2025-08-31

- Added bulk tenant members allocation and deallocation in Browse list of users.
- Added help icons to tenant forms.
- Added checkbox to create Associated users cohort when creating or updating tenants.
- Added Tenant management section to primary menu.
- Fixed compatibility with unsupported MS SQL databases.

## [mu-5.0.2-01](https://github.com/mutms/moodle-tool_mutenancy/compare/mu-5.0.1-01...mu-5.0.2-01) - 2025-08-09

- Internal refactoring.
- Moodle 5.0.2 support.

## [mu-5.0.1-01](https://github.com/mutms/moodle-tool_mutenancy/tree/mu-5.0.1-01) - 2025-06-30

- Added support for Moodle 5.0
