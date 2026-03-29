# Change log

Plugin versioning is derived from Moodle releases, it does not comply with the semantic versioning standard.

The format of this change log follows the advice given at [Keep a CHANGELOG](https://keepachangelog.com).

## [v5.0.6.06](https://github.com/mutms/moodle-tool_mutrain/compare/v5.0.6.05...v5.0.6.06) - 2026-03-29

- No changes

## [v5.0.6.05](https://github.com/mutms/moodle-tool_mutrain/compare/v5.0.6.04...v5.0.6.05) - 2026-03-28

- No changes

## [v5.0.6.04](https://github.com/mutms/moodle-tool_mutrain/compare/v5.0.6.03...v5.0.6.04) - 2026-03-27

### Added

- Added composer.json for Packagist distribution
- Added Moodle 5.2 compatibility

## [v5.0.6.03](https://github.com/mutms/moodle-tool_mutrain/compare/v5.0.6.02...v5.0.6.03) - 2026-03-26

- No changes

## [v5.0.6.02](https://github.com/mutms/moodle-tool_mutrain/compare/v5.0.6.01...v5.0.6.02) - 2026-03-01

- No changes

## [v5.0.6.01](https://github.com/mutms/moodle-tool_mutrain/compare/mu-5.0.5-01...v5.0.6.01) - 2026-02-12

### Changed

- Switched to new release number format to prepare for composer support

## [mu-5.0.5-01](https://github.com/mutms/moodle-tool_mutrain/compare/mu-5.0.4-04...mu-5.0.5-01) - 2026-02-08

- No changes

## [mu-5.0.4-04](https://github.com/mutms/moodle-tool_mutrain/compare/mu-5.0.4-03...mu-5.0.4-04) - 2026-01-25

### Added

- Added link to detailed report with completion credits

### Changed

- Added separate "Move framework" action for moving of credit frameworks into different contexts to match other MuTMS plugins
- Improved navigation to start with "Credit frameworks" instead of "System"

### Fixed

- Frameworks from deleted categories will be automatically marked as archived and moved to parent context
- Fixed usage of legacy moodle_url class

## [mu-5.0.4-03](https://github.com/mutms/moodle-tool_mutrain/compare/mu-5.0.4-02...mu-5.0.4-03) - 2025-12-31

### Changed

- Switched to new change log format
- Improved performance of Certifications management page on sites with large number of contexts
- Fixed category selection autocomplete element in framework editing forms

## [mu-5.0.4-02](https://github.com/mutms/moodle-tool_mutrain/compare/mu-5.0.4-01...mu-5.0.4-02) - 2025-12-16

- Plugin name changed to _Training credits_.
- Added _Required credits reached_ event.
- Decimals are used instead of integers to match industry standards.
- Credit frameworks UI is using Category label instead of Context.
- Added credits overview to user profile page.
- New credit frameworks are visible by default.

## [mu-5.0.4-01](https://github.com/mutms/moodle-tool_mutrain/compare/mu-5.0.3-02...mu-5.0.4-01) - 2025-12-08

- No changes.

## [mu-5.0.3-02](https://github.com/mutms/moodle-tool_mutrain/compare/mu-5.0.3-01...mu-5.0.3-02) - 2025-11-08

- No changes.

## [mu-5.0.3-01](https://github.com/mutms/moodle-tool_mutrain/compare/mu-5.0.2-03...mu-5.0.3-01) - 2025-10-06

- Added support for Moodle 5.1.

## [mu-5.0.2-03](https://github.com/mutms/moodle-tool_mutrain/compare/mu-5.0.2-02...mu-5.0.2-03) - 2025-09-24

- No changes.

## [mu-5.0.2-02](https://github.com/mutms/moodle-tool_mutrain/compare/mu-5.0.2-01...mu-5.0.2-02) - 2025-08-31

- Improved naming to use "Training points".
- Added support custom training fields in programs - programs may now depend indirectly on completion of other programs.
- Courses with disabled completion tracking are now ignored.
- Fixed compatibility with unsupported MS SQL databases.

## [mu-5.0.2-01](https://github.com/mutms/moodle-tool_mutrain/compare/mu-5.0.1-01...mu-5.0.2-01) - 2025-08-09

- Internal refactoring.
- Moodle 5.0.2 support.

## [mu-5.0.1-01](https://github.com/mutms/moodle-tool_mutrain/tree/mu-5.0.1-01) - 2025-06-30

- Added support for Moodle 5.0
