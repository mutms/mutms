# Change log

Plugin versioning is derived from Moodle releases, it does not comply with the semantic versioning standard.

The format of this change log follows the advice given at [Keep a CHANGELOG](https://keepachangelog.com).

## [v5.0.9.01](https://github.com/mutms/moodle-tool_mulib/compare/v5.0.8.01...v5.0.9.01) - 2026-06-05

- No changes

## [v5.0.8.01](https://github.com/mutms/moodle-tool_mulib/compare/v5.0.7.01...v5.0.8.01) - 2026-06-05

- No changes

## [v5.0.7.01](https://github.com/mutms/moodle-tool_mulib/compare/v5.0.6.06...v5.0.7.01) - 2026-04-19

- No changes

## [v5.0.6.06](https://github.com/mutms/moodle-tool_mulib/compare/v5.0.6.05...v5.0.6.06) - 2026-03-29

- No changes

## [v5.0.6.05](https://github.com/mutms/moodle-tool_mulib/compare/v5.0.6.04...v5.0.6.05) - 2026-03-28

- No changes

## [v5.0.6.04](https://github.com/mutms/moodle-tool_mulib/compare/v5.0.6.03...v5.0.6.04) - 2026-03-27

### Added

- Added composer.json for Packagist distribution
- Added behat steps to work around custom field changes in 5.2

## [v5.0.6.03](https://github.com/mutms/moodle-tool_mulib/compare/v5.0.6.02...v5.0.6.03) - 2026-03-26

### Added

- **\tool_mulib\local\sql** class now supports "mdl_xyz" database table name placeholders 

## [v5.0.6.02](https://github.com/mutms/moodle-tool_mulib/compare/v5.0.6.01...v5.0.6.02) - 2026-03-01

### Added

- New method for ensuring no comments are left in SQL queries
- Added new default form field name method to ajax autocomplete elements

## [v5.0.6.01](https://github.com/mutms/moodle-tool_mulib/compare/mu-5.0.5-01...v5.0.6.01) - 2026-02-12

### Changed

- Switched to new release number format to prepare for composer support

## [mu-5.0.5-01](https://github.com/mutms/moodle-tool_mulib/compare/mu-5.0.4-04...mu-5.0.5-01) - 2026-02-08

- No changes

## [mu-5.0.4-04](https://github.com/mutms/moodle-tool_mulib/compare/mu-5.0.4-03...mu-5.0.4-04) - 2026-01-25

### Added

- Added composer metadata
- Added Universal catalogue helpers

### Changed

- Updated required libraries

## [mu-5.0.4-03](https://github.com/mutms/moodle-tool_mulib/compare/mu-5.0.4-02...mu-5.0.4-03) - 2025-12-31

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

## [mu-5.0.4-02](https://github.com/mutms/moodle-tool_mulib/compare/mu-5.0.4-01...mu-5.0.4-02) - 2025-12-16

- Added \tool_mulib\local\mudb::upsert_record() helper.
- Updated MuTMS plugin helpers.

## [mu-5.0.4-01](https://github.com/mutms/moodle-tool_mulib/compare/mu-5.0.3-02...mu-5.0.4-01) - 2025-12-08

- Changed \tool_mulib\external\form_autocomplete\user API to use sql fragments.
- Changed \tool_mulib\local\sql methods to never modify existing instance.
- Added get_contexts_by_capability_join() implementing fast user permissions lookup via database query. 
- Added context parents and map database table for fast context relationship lookups.
- Fixed custom notification editor.
- Added option to send copy of subordinate notifications to supervisors.
- Added management of reusable external PDO databases.

## [mu-5.0.3-02](https://github.com/mutms/moodle-tool_mulib/compare/mu-5.0.3-01...mu-5.0.3-02) - 2025-11-08

- Added \tool_mulib\local\mulib::clean_string() to help with Mustache double encoding
- Plugin documentation was move to GitHub wikis and removed Parsedown library
- Added support for outline AJAX form buttons. 
- Fixed rendering of actions dropdown.

## [mu-5.0.3-01](https://github.com/mutms/moodle-tool_mulib/compare/mu-5.0.2-03...mu-5.0.3-01) - 2025-10-06

- Added support for Moodle 5.1.
- Added support for creation of buttons and icons from action links.

## [mu-5.0.2-03](https://github.com/mutms/moodle-tool_mulib/compare/mu-5.0.2-02...mu-5.0.2-03) - 2025-09-24

- Added support for dropdown action icon and class.
- Added SQL fragments. 

## [mu-5.0.2-02](https://github.com/mutms/moodle-tool_mulib/compare/mu-5.0.2-01...mu-5.0.2-02) - 2025-08-31

- Fixed compatibility with unsupported MS SQL databases.

## [mu-5.0.2-01](https://github.com/mutms/moodle-tool_mulib/compare/mu-5.0.1-01...mu-5.0.2-01) - 2025-08-09

- New modal ajax forms helper replacing dialog forms.
- Internal refactoring.
- Moodle 5.0.2 support.

## [mu-5.0.1-01](https://github.com/mutms/moodle-tool_mulib/tree/mu-5.0.1-01) - 2025-06-30

- Fixed compatibility with Moodle 5.0.1 release.
