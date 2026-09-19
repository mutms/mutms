# Change log

Plugin versioning is derived from Moodle releases, it does not comply with the semantic versioning standard.

The format of this change log follows the advice given at [Keep a CHANGELOG](https://keepachangelog.com).

## [v4.5.14.03](https://github.com/mutms/moodle-tool_mutrain/compare/v4.5.14.02...v4.5.14.03) - 2026-09-19

- version pinned to 20260919xx

## [v4.5.14.02](https://github.com/mutms/moodle-tool_mutrain/compare/v4.5.14.01...v4.5.14.02) - 2026-09-18

### Added

- CAMP registry support

### Changed

- Security only fixes for Moodle 4.5 – 5.2, version pinned to 2026091845.xx

## [v4.5.14.01](https://github.com/mutms/moodle-tool_mutrain/compare/v4.5.13.01...v4.5.14.01) - 2026-09-13

- No changes

## [v4.5.13.01](https://github.com/mutms/moodle-tool_mutrain/compare/v4.5.12.01...v4.5.13.01) - 2026-06-05

- No changes

## [v4.5.12.01](https://github.com/mutms/moodle-tool_mutrain/compare/v4.5.11.01...v4.5.12.01) - 2026-06-05

- No changes

## [v4.5.11.01](https://github.com/mutms/moodle-tool_mutrain/compare/v4.5.10.06...v4.5.11.01) - 2026-04-18

- No changes

## [v4.5.10.06](https://github.com/mutms/moodle-tool_mutrain/compare/v4.5.10.05...v4.5.10.06) - 2026-03-29

- No changes

## [v4.5.10.05](https://github.com/mutms/moodle-tool_mutrain/compare/v4.5.10.04...v4.5.10.05) - 2026-03-28

- No changes

## [v4.5.10.04](https://github.com/mutms/moodle-tool_mutrain/compare/v4.5.10.03...v4.5.10.04) - 2026-03-27

### Added

- Added composer.json for Packagist distribution

## [v4.5.10.03](https://github.com/mutms/moodle-tool_mutrain/compare/v4.5.10.02...v4.5.10.03) - 2026-03-26

- No changes

## [v4.5.10.02](https://github.com/mutms/moodle-tool_mutrain/compare/v4.5.10.01...v4.5.10.02) - 2026-03-01

- No changes

## [v4.5.10.01](https://github.com/mutms/moodle-tool_mutrain/compare/mu-4.5.9-01...v4.5.10.01) - 2026-02-12

### Changed

- Switched to new release number format to prepare for composer support

## [mu-4.5.9-01](https://github.com/mutms/moodle-tool_mutrain/compare/mu-4.5.8-04...mu-4.5.9-01) - 2026-02-08

- No changes

## [mu-4.5.8-04](https://github.com/mutms/moodle-tool_mutrain/compare/mu-4.5.8-03...mu-4.5.8-04) - 2026-01-25

### Added

- Added link to detailed report with completion credits

### Changed

- Added separate "Move framework" action for moving of credit frameworks into different contexts to match other MuTMS plugins
- Improved navigation to start with "Credit frameworks" instead of "System"

### Fixed

- Frameworks from deleted categories will be automatically marked as archived and moved to parent context
- Fixed usage of legacy moodle_url class

## [mu-4.5.8-03](https://github.com/mutms/moodle-tool_mutrain/compare/mu-4.5.8-02...mu-4.5.8-03) - 2025-12-31

### Changed

- Switched to new change log format
- Improved performance of Certifications management page on sites with large number of contexts
- Fixed category selection autocomplete element in framework editing forms

## [mu-4.5.8-02](https://github.com/mutms/moodle-tool_mutrain/compare/mu-4.5.8-01...mu-4.5.8-02) - 2025-12-16

- Plugin name changed to _Training credits_.
- Added _Required credits reached_ event.
- Decimals are used instead of integers to match industry standards.
- Credit frameworks UI is using Category label instead of Context.
- Added credits overview to user profile page.
- New credit frameworks are visible by default.

## [mu-4.5.8-01](https://github.com/mutms/moodle-tool_mutrain/compare/mu-4.5.7-02...mu-4.5.8-01) - 2025-12-08

- No changes.

## [mu-4.5.7-02](https://github.com/mutms/moodle-tool_mutrain/compare/mu-4.5.7-01...mu-4.5.7-02) - 2025-11-08

- No changes.

## [mu-4.5.7-01](https://github.com/mutms/moodle-tool_mutrain/compare/mu-4.5.6-03...mu-4.5.7-01) - 2025-10-06

- No changes.

## [mu-4.5.6-03](https://github.com/mutms/moodle-tool_mutrain/compare/mu-4.5.6-02...mu-4.5.6-03) - 2025-09-24

- No changes.

## [mu-4.5.6-02](https://github.com/mutms/moodle-tool_mutrain/compare/mu-4.5.6-01...mu-4.5.6-02) - 2025-08-31

- Improved naming to use "Training points".
- Added support custom training fields in programs - programs may now depend indirectly on completion of other programs.
- Courses with disabled completion tracking are now ignored.
- Fixed compatibility with unsupported MS SQL databases.

## [mu-4.5.6-01](https://github.com/mutms/moodle-tool_mutrain/compare/mu-4.5.5-02...mu-4.5.6-01) - 2025-08-09

- Internal refactoring.
- Moodle 4.5.6 support.

## [mu-4.5.5-02](https://github.com/mutms/moodle-tool_mutrain/compare/mu-4.5.5-01...mu-4.5.5-02) - 2025-06-30

- New plugin versioning.

## [mu-4.5.5-01](https://github.com/mutms/moodle-tool_mutrain/tree/mu-4.5.5-01) - 2025-06-09

- Improved docs and added acknowledgements.
- Fixed ordering of frameworks by name.
- Standardised admin settings.
