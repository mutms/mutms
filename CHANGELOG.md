# Change log

Plugin versioning is derived from Moodle releases, it does not comply with the semantic versioning standard.

The format of this change log follows the advice given at [Keep a CHANGELOG](https://keepachangelog.com).

## [mu-5.0.4-04] - 2026-01-25

### Added

- Added composer metadata
- Added Universal catalogue helpers

### Changed

- Updated required libraries

## [mu-5.0.4-03] - 2025-12-31

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

## [mu-5.0.4-02] - 2025-12-16

- Added \tool_mulib\local\mudb::upsert_record() helper.
- Updated MuTMS plugin helpers.

## [mu-5.0.4-01] - 2025-12-08

- Changed \tool_mulib\external\form_autocomplete\user API to use sql fragments.
- Changed \tool_mulib\local\sql methods to never modify existing instance.
- Added get_contexts_by_capability_join() implementing fast user permissions lookup via database query. 
- Added context parents and map database table for fast context relationship lookups.
- Fixed custom notification editor.
- Added option to send copy of subordinate notifications to supervisors.
- Added management of reusable external PDO databases.

## [mu-5.0.3-02] - 2025-11-08

- Added \tool_mulib\local\mulib::clean_string() to help with Mustache double encoding
- Plugin documentation was move to GitHub wikis and removed Parsedown library
- Added support for outline AJAX form buttons. 
- Fixed rendering of actions dropdown.

## [mu-5.0.3-01] - 2025-10-06

- Added support for Moodle 5.1.
- Added support for creation of buttons and icons from action links.

## [mu-5.0.2-03] - 2025-09-24

- Added support for dropdown action icon and class.
- Added SQL fragments. 

## [mu-5.0.2-02] - 2025-08-31

- Fixed compatibility with unsupported MS SQL databases.

## [mu-5.0.2-01] - 2025-08-09

- New modal ajax forms helper replacing dialog forms.
- Internal refactoring.
- Moodle 5.0.2 support.

## [mu-5.0.1-01] - 2025-06-30

- Fixed compatibility with Moodle 5.0.1 release.
