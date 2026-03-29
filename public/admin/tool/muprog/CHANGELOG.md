# Change log

Plugin versioning is derived from Moodle releases, it does not comply with the semantic versioning standard.

The format of this change log follows the advice given at [Keep a CHANGELOG](https://keepachangelog.com).

## [v5.0.6.06](https://github.com/mutms/moodle-tool_muprog/compare/v5.0.6.05...v5.0.6.06) - 2026-03-29

- No changes

## [v5.0.6.05](https://github.com/mutms/moodle-tool_muprog/compare/v5.0.6.04...v5.0.6.05) - 2026-03-28

- No changes

## [v5.0.6.04](https://github.com/mutms/moodle-tool_muprog/compare/v5.0.6.03...v5.0.6.04) - 2026-03-27

### Added

- Added composer.json for Packagist distribution
- Added Moodle 5.2 compatibility

## [v5.0.6.03](https://github.com/mutms/moodle-tool_muprog/compare/v5.0.6.02...v5.0.6.03) - 2026-03-26

- No changes

## [v5.0.6.02](https://github.com/mutms/moodle-tool_muprog/compare/v5.0.6.01...v5.0.6.02) - 2026-03-01

### Changes

- Geopattern generated program images are served via standard pluginfile.php
- All images were moved to system context, program context will be used only for management access control and tenant separation
- All tags were moved to system context
- Moved favourites storage to system context

## [v5.0.6.01](https://github.com/mutms/moodle-tool_muprog/compare/mu-5.0.5-01...v5.0.6.01) - 2026-02-12

### Changed

- Switched to new release number format to prepare for composer support

## [mu-5.0.5-01](https://github.com/mutms/moodle-tool_muprog/compare/mu-5.0.4-04...mu-5.0.5-01) - 2026-02-08

### Fixed

- Added missing required icon when selecting new program item type

## [mu-5.0.4-04](https://github.com/mutms/moodle-tool_muprog/compare/mu-5.0.4-03...mu-5.0.4-04) - 2026-01-25

### Added

- Added link to detailed report with completion credits
- New "Offline attendance" program item type
- Added "Course completion" into "Completion type" column in program progress tables
- Added web service for archiving and restoring of program allocations
- Icons added to dropdown actions
- Allocations can be archived/restore directly from the list of all program users
- Added "Program allocation viewed" event, replacing incorrect use of "Program viewed" event in user profiles
- Added new capability "View other users programs" to allow viewing of other users programs via profile pages

### Changed

- Added separate "Move program" action for moving of programs into different contexts to match other MuTMS plugins
- "Course set" is now called "Set" because programs may contain other item types
- Reworked adding of program items to allow addition of new item types
- New column "type" was added to "tool_muprog_item" database table
- Changed web services results to use "deletepossible" and "editpossible" property names
- Other backwards compatible web services API and implementation tidy-up
- Used red colour for "Delete program" action
- Archived status is shown in allocation details
- Icon for program completion overriding is shown in allocation details
- Icon for program reset is shown in allocation details
- Button for updating of allocation is shown in allocation details
- Improved navigation on allocated users management page

### Fixed

- Fixed filtering by program name in reports
- Added missing indication of delayed completions
- Fixed result overflow detection in ajax autocomplete form fields
- Fixed program item behat generators
- Internal allocation source data was removed from web services
- Web service delete_program_allocations was fixed to use tool/muprog:deallocate capability
- Fixed incorrect is_allocation_archive_possible() and is_allocation_restore_possible() methods in allocation sources
- Fixed usage of legacy moodle_url class
- Improved navigation to start with "Programs" instead of "System"
- Programs from deleted categories will be automatically marked as archived when moved to parent context
- Fixed missing program image when moving program to a different context
- To prevent data loss users have to explicitly select "Reset type" option in "Reset program progress" dialog
- Prevented duplicate credit frameworks in programs

## [mu-5.0.4-03](https://github.com/mutms/moodle-tool_muprog/compare/mu-5.0.4-02...mu-5.0.4-03) - 2025-12-31

### Added

- Added setting to control if programs from sub-contexts are included in category export

### Changed

- Switched to new change log format
- Reversed block dependencies to simplify Programs installation and upgrades
- Improved performance of Programs management page on sites with large number of contexts
- Fixed category selection autocomplete element in program editing and export forms
- Standardised program idnumber to be case-insensitively unique

## [mu-5.0.4-02](https://github.com/mutms/moodle-tool_muprog/compare/mu-5.0.4-01...mu-5.0.4-02) - 2025-12-16

- Added program progress as percentage of completed non-set items.
- Training points were renamed to Training credits.
- Training item was renamed to Credits item.
- Training credits use decimal values.
- Credits aggregation is now instant, it does not depend on cron anymore.
- Fixed placement of custom fields in program creation form.
- Added support for generated program images.

## [mu-5.0.4-01](https://github.com/mutms/moodle-tool_muprog/compare/mu-5.0.3-02...mu-5.0.4-01) - 2025-12-08

- Updated use of SQL fragments API.
- Fixed timezones in notifications.
- Added option to send copy of subordinate notifications to supervisors.
- Added option for enabling of Manual and Certification allocations during program creation.
- Added source for allocation from external database.

## [mu-5.0.3-02](https://github.com/mutms/moodle-tool_muprog/compare/mu-5.0.3-01...mu-5.0.3-02) - 2025-11-08

- Improved training item icon - grid icon is used instead of ellipsis.
- Documentation was moved to https://github.com/mutms/moodle-tool_muprog/wiki
- Improved table visuals.

## [mu-5.0.3-01](https://github.com/mutms/moodle-tool_muprog/compare/mu-5.0.2-03...mu-5.0.3-01) - 2025-10-06

- Fixed program tags itemtype to match database table name.
- Added support for Moodle 5.1.

## [mu-5.0.2-03](https://github.com/mutms/moodle-tool_muprog/compare/mu-5.0.2-02...mu-5.0.2-03) - 2025-09-24

- Certification allocation conflicts are now handled gracefully.

## [mu-5.0.2-02](https://github.com/mutms/moodle-tool_muprog/compare/mu-5.0.2-01...mu-5.0.2-02) - 2025-08-31

- Added Program completion allocation source - users may get allocated to a program when they complete another program.
- Fixed automatic cohort allocation source form.
- Empty custom fields are not displayed anymore.
- Triggered missing even allocation_completed event when overriding program completion.
- Fixed validation of tenant restrictions when selecting users.
- Note that "public" program field was renamed to "publicaccess" which affects web services and exports; program uploads can handle both old and new field names. 
- Fixed compatibility with unsupported MS SQL databases.
- Fixed fatal errors when sending deallocation email and SMTP is down, you may need to wait for next cron run to resolve blocking errors for students.

## [mu-5.0.2-01](https://github.com/mutms/moodle-tool_muprog/compare/mu-5.0.1-01...mu-5.0.2-01) - 2025-08-09

- Internal refactoring.
- Moodle 5.0.2 support.

## [mu-5.0.1-01](https://github.com/mutms/moodle-tool_muprog/tree/mu-5.0.1-01) - 2025-06-30

- Added support for Moodle 5.0
