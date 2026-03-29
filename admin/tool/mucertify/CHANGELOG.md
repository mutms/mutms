# Change log

Plugin versioning is derived from Moodle releases, it does not comply with the semantic versioning standard.

The format of this change log follows the advice given at [Keep a CHANGELOG](https://keepachangelog.com).

## [v5.0.6.06](https://github.com/mutms/moodle-tool_mucertify/compare/v5.0.6.05...v5.0.6.06) - 2026-03-29

- No changes

## [v5.0.6.05](https://github.com/mutms/moodle-tool_mucertify/compare/v5.0.6.04...v5.0.6.05) - 2026-03-28

- No changes

## [v5.0.6.04](https://github.com/mutms/moodle-tool_mucertify/compare/v5.0.6.03...v5.0.6.04) - 2026-03-27

### Added

- Added composer.json for Packagist distribution
- Added Moodle 5.2 compatibility

## [v5.0.6.03](https://github.com/mutms/moodle-tool_mucertify/compare/v5.0.6.02...v5.0.6.03) - 2026-03-26

### Added

- Added support for "Temporary certification until column" in CSV upload

## [v5.0.6.02](https://github.com/mutms/moodle-tool_mucertify/compare/v5.0.6.01...v5.0.6.02) - 2026-03-01

### Changes

- Refactored certification image support
- All images were moved to system context, certification context will be used only for management access control and tenant separation
- All tags were moved to system context
- Added all Programs dependencies to simplify installation from Plugins database

## [v5.0.6.01](https://github.com/mutms/moodle-tool_mucertify/compare/mu-5.0.5-01...v5.0.6.01) - 2026-02-12

### Changed

- Switched to new release number format to prepare for composer support

## [mu-5.0.5-01](https://github.com/mutms/moodle-tool_mucertify/compare/mu-5.0.4-04...mu-5.0.5-01) - 2026-02-08

### Fixed

- Fixed certification history upload action to not be visible when manual assignment source is disabled

## [mu-5.0.4-04](https://github.com/mutms/moodle-tool_mucertify/compare/mu-4.5.8-03...mu-5.0.4-04) - 2026-01-25

### Added

- New web services for fetching of certifications, certification assignments and periods 
- Icons added to dropdown actions
- Added "Certification assignment viewed" event
- Added new capability "View other users certifications" to allow viewing of other users certifications via profile pages

### Changed

- Added separate "Move certification" action for moving of certifications into different contexts to match other MuTMS plugins
- Used red colour for "Delete certification" action
- Improved navigation on assigned users management page

### Fixed

- Fixed usage of legacy moodle_url class
- Certifications from deleted categories will be automatically marked as archived when moved to parent context
- Fixed missing certification image when moving certification to a different context
- Improved navigation to start with "Certifications" instead of "System"

## [mu-4.5.8-03](https://github.com/mutms/moodle-tool_mucertify/compare/mu-5.0.4-02...mu-4.5.8-03) - 2025-12-31

### Changed

- Switched to new change log format
- Reversed block dependencies to simplify Certifications installation and upgrades
- Switched to new mulib Certification helpers
- Improved performance of Certifications management page on sites with large number of contexts
- Fixed category selection autocomplete element in certification editing form
- Standardised certification idnumber to be case-insensitively unique

## [mu-5.0.4-02](https://github.com/mutms/moodle-tool_mucertify/compare/mu-5.0.4-01...mu-5.0.4-02) - 2025-12-16

- Fixed placement of custom fields in certification creation form.

## [mu-5.0.4-01](https://github.com/mutms/moodle-tool_mucertify/compare/mu-5.0.3-02...mu-5.0.4-01) - 2025-12-08

- After creating new certification user is now redirected to Period settings page because they need to add program. 
- Fixed timezones in notifications.
- Added supervisor notifications.
- Added support for Expiration column into certification assignment uploads.
- Added Expiration to certification assignment form - this can be used to align employee re-certifications to fixed dates. 
- Added option for enabling of Manual allocations during certification creation.

## [mu-5.0.3-02](https://github.com/mutms/moodle-tool_mucertify/compare/mu-5.0.3-01...mu-5.0.3-02) - 2025-11-08

- Documentation was moved to https://github.com/mutms/moodle-tool_mucertify/wiki
- Improved table visuals.

## [mu-5.0.3-01](https://github.com/mutms/moodle-tool_mucertify/compare/mu-5.0.2-03...mu-5.0.3-01) - 2025-10-06

- Fixed certification tags itemtype to match database table name.
- Added support for Moodle 5.1.

## [mu-5.0.2-03](https://github.com/mutms/moodle-tool_mucertify/compare/mu-5.0.2-02...mu-5.0.2-03) - 2025-09-24

- Program allocation conflicts are now handled gracefully. However, mixing certification allocation sources with other types of allocations within a single program remains discouraged.

## [mu-5.0.2-02](https://github.com/mutms/moodle-tool_mucertify/compare/mu-5.0.2-01...mu-5.0.2-02) - 2025-08-31

- Fixed automatic cohort assignment source form.
- Empty custom fields are not displayed anymore.
- Fixed validation of tenant restrictions when selecting users.
- Fixed compatibility with unsupported MS SQL databases.
- Fixed error when sending unassignment email and SMTP is down.

## [mu-5.0.2-01](https://github.com/mutms/moodle-tool_mucertify/compare/mu-5.0.1-01...mu-5.0.2-01) - 2025-08-09

- Internal refactoring.
- Moodle 5.0.2 support.

## [mu-5.0.1-01](https://github.com/mutms/moodle-tool_mucertify/tree/mu-5.0.1-01) - 2025-06-30

- Added support for Moodle 5.0
