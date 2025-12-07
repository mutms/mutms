# Certifications plugin for Moodle™ LMS

![Moodle Plugin CI](https://github.com/mutms/moodle-tool_mucertify/actions/workflows/moodle-ci.yml/badge.svg)

Certifications is a versatile suite of plugins designed to simplify and optimize the certification process for organizations.
It provides an effective and user-friendly solution to meet compliance training requirements, ensuring that your workforce 
remains aligned with critical industry standards and regulations.

## Key features

* tracking of compliance through certification periods tied to designated programs
* multiple sources for assigning certifications
* advanced recertification rules to match organizational needs
* intuitive _Certification management_ interface
* _Certification catalogue_ where users may browse all available certifications
* dedicated _My certifications profile page_
* _My certifications dashboard block_ for quick access to details
* supervisors may receive copy of notifications sent to subordinates

## Requirements

This plugin requires following plugins:

* [Additional tools library plugin](https://github.com/mutms/moodle-tool_mulib)
* [Programs plugin](https://github.com/mutms/moodle-tool_muprog)
* [Program enrolment plugin](https://github.com/mutms/moodle-enrol_muprog)

Other recommended plugins:

* [My certifications block](https://github.com/mutms/moodle-block_mucertify_my)
* [My programs block](https://github.com/mutms/moodle-block_muprog_my)
* [Supervisors and teams plugin](https://github.com/mutms/moodle-tool_murelation)
* [Training plugin](https://github.com/mutms/moodle-tool_mutrain)
* [Training value custom field](https://github.com/mutms/moodle-customfield_mutrain)
* [Certificate plugin](https://github.com/moodleworkplace/moodle-tool_certificate)
* [Certification fields for Certificate plugin](https://github.com/mutms/moodle-certificateelement_mucertify)
* [Program fields for Certificate plugin](https://github.com/mutms/moodle-certificateelement_muprog)
* [Multi-tenancy](https://github.com/mutms/moodle-tool_mutenancy).

## Documentation

and [Wiki pages](https://github.com/mutms/moodle-tool_mucertify/wiki) for more information.

## Acknowledgement

This plugin is a fork of [Certifications by Open LMS](https://github.com/open-lms-open-source/moodle-tool_certify)
and exists thanks to Open LMS's decision to release it to the public under the GPL 3.0 license.

MuTMS suite of plugins is not associated with Moodle HQ or Open LMS in any way.
This plugin is not suitable for existing customers of Open LMS due to the lack of upgrade path.

## Roadmap

* Target for production release and availability of paid support: Q2 2026
* Planned features:
    * integration of Supervisors and teams plugin for approvals
    * integration of Universal catalogue plugin
