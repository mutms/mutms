# Change log

Plugin versioning is derived from Moodle releases, it does not comply with the semantic versioning standard.

The format of this change log follows the advice given at [Keep a CHANGELOG](https://keepachangelog.com).

## [v4.5.14.03](https://github.com/mutms/moodle-tool_mulib/compare/v4.5.14.02...v4.5.14.03) - 2026-09-19

- version pinned to 20260919xx

## [v4.5.14.02](https://github.com/mutms/moodle-tool_mulib/compare/v4.5.14.01...v4.5.14.02) - 2026-09-18

### Added

- CAMP registry support

### Changed

- Security only fixes for Moodle 4.5 – 5.2, version pinned to 2026091845.xx

## [v4.5.14.01](https://github.com/mutms/moodle-tool_mulib/compare/v4.5.13.01...v4.5.14.01) - 2026-09-13

### Fixed

- Added uninstallation of materialized default user role assignment

## [v4.5.13.01](https://github.com/mutms/moodle-tool_mulib/compare/v4.5.12.01...v4.5.13.01) - 2026-06-05

- No changes

## [v4.5.12.01](https://github.com/mutms/moodle-tool_mulib/compare/v4.5.11.01...v4.5.12.01) - 2026-06-05

- No changes

## [v4.5.11.01](https://github.com/mutms/moodle-tool_mulib/compare/v4.5.10.06...v4.5.11.01) - 2026-04-18

- No changes

## [v4.5.10.06](https://github.com/mutms/moodle-tool_mulib/compare/v4.5.10.05...v4.5.10.06) - 2026-03-29

- No changes

## [v4.5.10.05](https://github.com/mutms/moodle-tool_mulib/compare/v4.5.10.04...v4.5.10.05) - 2026-03-28

- No changes

## [v4.5.10.04](https://github.com/mutms/moodle-tool_mulib/compare/v4.5.10.03...v4.5.10.04) - 2026-03-27

### Added

- Added composer.json for Packagist distribution
- Added behat steps to work around custom field changes in 5.2

## [v4.5.10.03](https://github.com/mutms/moodle-tool_mulib/compare/v4.5.10.02...v4.5.10.03) - 2026-03-26

### Added

- **\tool_mulib\local\sql** class now supports "mdl_xyz" database table name placeholders 

## [v4.5.10.02](https://github.com/mutms/moodle-tool_mulib/compare/v4.5.10.01...v4.5.10.02) - 2026-03-01

### Added

- New method for ensuring no comments are left in SQL queries
- Added new default form field name method to ajax autocomplete elements

## [v4.5.10.01](https://github.com/mutms/moodle-tool_mulib/compare/mu-4.5.9-01...v4.5.10.01) - 2026-02-12

### Changed

- Switched to new release number format to prepare for composer support

## [mu-4.5.9-01](https://github.com/mutms/moodle-tool_mulib/compare/mu-4.5.8-04...mu-4.5.9-01) - 2026-02-08

- No changes

## [mu-4.5.8-04](https://github.com/mutms/moodle-tool_mulib/compare/mu-4.5.8-03...mu-4.5.8-04) - 2026-01-25

### Added

- Added composer metadata
- Added Universal catalogue helpers

### Changed

- Updated required libraries

## [mu-4.5.8-03](https://github.com/mutms/moodle-tool_mulib/compare/mu-4.5.8-02...mu-4.5.8-03) - 2025-12-31

### Added

- Added Certification availability helpers
- Added Custom home pages availability helpers
- Added \tool_muhome\output\url_clipboard renderable element for links with "copy to clipboard" action icon

### Changed

- Switched to new change log format
- Changed returned 'where' from \tool_mulib\local\context_map::get_contexts_by_capability_join() to be a sql instance
- Improved \tool_mulib\external\form_autocomplete\categorycontext base class
- Fixed category selection in external PDO query editing
- Description lists created via entity_details renderable are responsive on small screens

## [mu-4.5.8-02](https://github.com/mutms/moodle-tool_mulib/compare/mu-4.5.8-01...mu-4.5.8-02) - 2025-12-16

- Added \tool_mulib\local\mudb::upsert_record() helper.
- Updated MuTMS plugin helpers.

## [mu-4.5.8-01](https://github.com/mutms/moodle-tool_mulib/compare/mu-4.5.7-02...mu-4.5.8-01) - 2025-12-08

- Changed \tool_mulib\external\form_autocomplete\user API to use sql fragments.
- Changed \tool_mulib\local\sql methods to never modify existing instance.
- Added get_contexts_by_capability_join() implementing fast user permissions lookup via database query. 
- Added context parents and map database table for fast context relationship lookups.
- Fixed custom notification editor.
- Added option to send copy of subordinate notifications to supervisors.
- Added management of reusable external PDO databases.

## [mu-4.5.7-02](https://github.com/mutms/moodle-tool_mulib/compare/mu-4.5.7-01...mu-4.5.7-02) - 2025-11-08

- Added \tool_mulib\local\mulib::clean_string() to help with Mustache double encoding
- Plugin documentation was move to GitHub wikis and removed Parsedown library
- Added support for outline AJAX form buttons. 
- Fixed rendering of actions dropdown.

## [mu-4.5.7-01](https://github.com/mutms/moodle-tool_mulib/compare/mu-4.5.6-03...mu-4.5.7-01) - 2025-10-06

- Added support for creation of buttons and icons from action links.

## [mu-4.5.6-03](https://github.com/mutms/moodle-tool_mulib/compare/mu-4.5.6-02...mu-4.5.6-03) - 2025-09-24

- Added support for dropdown action icon and class.
- Added SQL fragments. 

## [mu-4.5.6-02](https://github.com/mutms/moodle-tool_mulib/compare/mu-4.5.6-01...mu-4.5.6-02) - 2025-08-31

- Fixed compatibility with unsupported MS SQL databases.

## [mu-4.5.6-01](https://github.com/mutms/moodle-tool_mulib/compare/mu-4.5.5-02...mu-4.5.6-01) - 2025-08-09

- New modal ajax forms helper replacing dialog forms.
- Moodle 4.5.6 support.

## [mu-4.5.5-02](https://github.com/mutms/moodle-tool_mulib/compare/mu-4.5.5-01...mu-4.5.5-02) - 2025-06-30

- New plugin versioning.

## [mu-4.5.5-01](https://github.com/mutms/moodle-tool_mulib/tree/mu-4.5.5-01) - 2025-06-09

- Added shared "Not set" string.
- Added role_util helper.
- Added support for user autocomplete errors.
- Added entity details output component.
- Improved docs and added acknowledgements.
