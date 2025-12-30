# Change log

Plugin versioning is derived from Moodle releases, it does not comply with the semantic versioning standard.

The format of this change log follows the advice given at [Keep a CHANGELOG](https://keepachangelog.com).

## [mu-5.0.4-03] - 2025-12-31

### Added

- Added setting to control if programs from sub-contexts are included in category export

### Changed

- Switched to new change log format
- Reversed block dependencies to simplify Programs installation and upgrades
- Improved performance of Programs management page on sites with large number of contexts
- Fixed category selection autocomplete element in program editing and export forms
- Standardised program idnumber to be case-insensitively unique

## [mu-5.0.4-02] - 2025-12-16

- Added program progress as percentage of completed non-set items.
- Training points were renamed to Training credits.
- Training item was renamed to Credits item.
- Training credits use decimal values.
- Credits aggregation is now instant, it does not depend on cron anymore.
- Fixed placement of custom fields in program creation form.
- Added support for generated program images.

## [mu-5.0.4-01] - 2025-12-08

- Updated use of SQL fragments API.
- Fixed timezones in notifications.
- Added option to send copy of subordinate notifications to supervisors.
- Added option for enabling of Manual and Certification allocations during program creation.
- Added source for allocation from external database.

## [mu-5.0.3-02] - 2025-11-08

- Improved training item icon - grid icon is used instead of ellipsis.
- Documentation was moved to https://github.com/mutms/moodle-tool_muprog/wiki
- Improved table visuals.

## [mu-5.0.3-01] - 2025-10-06

- Fixed program tags itemtype to match database table name.
- Added support for Moodle 5.1.

## [mu-5.0.2-03] - 2025-09-24

- Certification allocation conflicts are now handled gracefully.

## [mu-5.0.2-02] - 2025-08-31

- Added Program completion allocation source - users may get allocated to a program when they complete another program.
- Fixed automatic cohort allocation source form.
- Empty custom fields are not displayed anymore.
- Triggered missing even allocation_completed event when overriding program completion.
- Fixed validation of tenant restrictions when selecting users.
- Note that "public" program field was renamed to "publicaccess" which affects web services and exports; program uploads can handle both old and new field names. 
- Fixed compatibility with unsupported MS SQL databases.
- Fixed fatal errors when sending deallocation email and SMTP is down, you may need to wait for next cron run to resolve blocking errors for students.

## [mu-5.0.2-01] - 2025-08-09

- Internal refactoring.
- Moodle 5.0.2 support.

## [mu-5.0.1-01] - 2025-06-30

- Added support for Moodle 5.0
