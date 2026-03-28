# Certifications plugin for Moodle™ LMS

![Moodle Plugin CI](https://github.com/mutms/moodle-tool_mucertify/actions/workflows/moodle-ci.yml/badge.svg)

Certification management for standard Moodle™ LMS installations — fully open source under GPL 3.0,
with no restrictions on commercial use. Part of the [MuTMS suite](https://github.com/mutms).

Allows organisations to define certifications tied to [Programs](https://github.com/mutms/moodle-tool_muprog), track compliance through
certification periods, and automate recertification — making it straightforward to manage workforce
compliance with industry standards and regulations.

## Features

* Certification periods tied to designated programs for compliance tracking
* Multiple sources for assigning certifications to users
* Advanced recertification rules to match organisational needs
* Certification catalogue — users can browse available certifications
* My certifications profile page and dashboard block for quick access
* Configurable notifications, including supervisors receiving copies of learner notifications

## Requirements

> This plugin is included in the [MuTMS distribution](https://github.com/mutms/mutms) —
> no manual installation needed if you use the distribution.

Required plugins:

* [Additional tools library plugin](https://github.com/mutms/moodle-tool_mulib)
* [My certifications block](https://github.com/mutms/moodle-block_mucertify_my)
* [Programs plugin](https://github.com/mutms/moodle-tool_muprog)
* [Program enrolment plugin](https://github.com/mutms/moodle-enrol_muprog)
* [My programs block](https://github.com/mutms/moodle-block_muprog_my)
* [My programs overview page plugin](https://github.com/mutms/moodle-block_muprogmyoverview)

Recommended plugins:

* [Supervisors and teams plugin](https://github.com/mutms/moodle-tool_murelation)
* [Training credits plugin](https://github.com/mutms/moodle-tool_mutrain)
* [Training credits custom field plugin](https://github.com/mutms/moodle-customfield_mutrain)
* [Certificate plugin](https://github.com/moodleworkplace/moodle-tool_certificate)
* [Program fields for Certificate plugin](https://github.com/mutms/moodle-certificateelement_muprog)
* [Certification fields for Certificate plugin](https://github.com/mutms/moodle-certificateelement_mucertify)
* [Multi-tenancy plugin](https://github.com/mutms/moodle-tool_mutenancy)

## Roadmap

* Universal catalogue plugin replacing current Certification catalogue
* Supervisor approval workflows via Supervisors and teams plugin
* Script for migration from Certifications by Open LMS

## Documentation

See [online documentation](https://docs.mutms.org/mucertify/) for more information.

---

> This plugin is a fork of [Certifications by Open LMS](https://github.com/open-lms-open-source/moodle-tool_certify).
> MuTMS is an independent open-source project, not affiliated with Moodle HQ or Open LMS.
