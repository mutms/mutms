# Programs plugin for Moodle™ LMS

![Moodle Plugin CI](https://github.com/mutms/moodle-tool_muprog/actions/workflows/moodle-ci.yml/badge.svg)

Programs is a comprehensive set of plugins designed to implement programs, also known as learning pathways.
This functionality enables educators, administrators, and organizations to create structured, sequential
learning journeys tailored to meet diverse learning goals and requirements. The robust features of Programs
provide enhanced flexibility and automation, making it an indispensable tool for managing complex educational
or training offerings.

Programs enhance Moodle's core functionality by bridging gaps in traditional course management.
They offer solutions for challenges such as organizing courses across categories, managing multi-tenancy for course roles,
and scheduling individualized course access. With seamless integration into Moodle™ LMS, Programs provide a scalable and
efficient way to manage both small-scale and large-scale learning initiatives and training.

## Key features

* program content created as a hierarchy of courses, training frameworks and course sets with flexible sequencing rules,
* multiple sources for allocation of students to programs,
* advanced program scheduling settings,
* efficient course enrolment automation,
* easy-to-use _Program management_ interface,
* _Program catalogue_ where students may browse available programs and related courses,
* dedicated _My programs profile page_,
* _My programs dashboard block_ for quick access to details.

## Requirements

This plugin requires following plugins:

* [Additional tools library plugin](https://github.com/mutms/moodle-tool_mulib)
* [Program enrolment plugin](https://github.com/mutms/moodle-enrol_muprog)

Other recommended plugins:

* [My programs block](https://github.com/mutms/moodle-block_muprog_my)
* [Training plugin](https://github.com/mutms/moodle-tool_mutrain)
* [Training value custom field](https://github.com/mutms/moodle-customfield_mutrain)
* [Certificate plugin](https://github.com/moodleworkplace/moodle-tool_certificate)
* [Program fields for Certificate plugin](https://github.com/mutms/moodle-certificateelement_muprog)
* [Multi-tenancy](https://github.com/mutms/moodle-tool_mutenancy)

## Documentation

See [plugin documentation](https://github.com/mutms/moodle-tool_muprog/blob/MOODLE_405_STABLE/docs/en/index.md)
and [Wiki pages](https://github.com/mutms/moodle-tool_muprog/wiki) for more information.

## Acknowledgement

This plugin is a fork of [Programs by Open LMS](https://github.com/open-lms-open-source/moodle-enrol_programs)
and exists thanks to Open LMS's decision to release it to the public under the GPL 3.0 license.

Note that the current code is still under development and is not suitable for production use.
If you require a stable version for a production environment or commercial support,
please consider [Open LMS Work](https://www.openlms.net/open-lms-work/).
This plugin is not suitable for existing customers of Open LMS due to the lack of upgrade path.

MuTMS suite of plugins is not associated with Moodle HQ or Open LMS in any way.
