# Programs plugin for Moodle™ LMS

![Moodle Plugin CI](https://github.com/mutms/moodle-tool_muprog/actions/workflows/moodle-ci.yml/badge.svg)

Structured learning programs for standard Moodle™ LMS installations — fully open source under GPL 3.0,
with no restrictions on commercial use. Part of the [MuTMS suite](https://github.com/mutms).

Programs allow educators and administrators to define structured learning paths composed of courses,
offline activities, and credit frameworks — with flexible sequencing, automated enrolments, and
dedicated learner-facing pages. Designed for organisations managing complex training or educational
offerings at scale.

## Features

* Program content built as a hierarchy of courses, credit frameworks, offline activities, and nested
  sets with flexible sequencing rules
* Multiple allocation sources, including allocation from an external database
* Advanced scheduling settings per program
* Automated course enrolment
* Program catalogue — learners can browse available programs and related courses
* My programs profile page and overview page (card, list, and details views), accessible from the
  main menu
* My programs dashboard block for quick access
* Configurable notifications, including supervisors receiving copies of learner notifications

## Requirements

> This plugin is included in the [MuTMS distribution](https://github.com/mutms/mutms) —
> no manual installation needed if you use the distribution.

Required plugins:

* [Additional tools library plugin](https://github.com/mutms/moodle-tool_mulib)
* [Program enrolment plugin](https://github.com/mutms/moodle-enrol_muprog)
* [My programs block](https://github.com/mutms/moodle-block_muprog_my)
* [My programs overview page plugin](https://github.com/mutms/moodle-block_muprogmyoverview)

Recommended plugins:

* [Supervisors and teams plugin](https://github.com/mutms/moodle-tool_murelation)
* [Training credits plugin](https://github.com/mutms/moodle-tool_mutrain)
* [Training credits custom field plugin](https://github.com/mutms/moodle-customfield_mutrain)
* [Certificate plugin](https://github.com/moodleworkplace/moodle-tool_certificate)
* [Program fields for Certificate plugin](https://github.com/mutms/moodle-certificateelement_muprog)
* [Multi-tenancy plugin](https://github.com/mutms/moodle-tool_mutenancy)

## Roadmap

* Universal catalogue plugin replacing current Program catalogue
* Supervisor approval workflows via Supervisors and teams plugin
* Script for migration from Programs by Open LMS

## Documentation

See [online documentation](https://docs.mutms.org/muprog/) for more information.

---

> This plugin is a fork of [Programs by Open LMS](https://github.com/open-lms-open-source/moodle-enrol_programs).
> MuTMS is an independent open-source project, not affiliated with Moodle HQ or Open LMS.
