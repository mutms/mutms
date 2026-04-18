# Supervisors and teams plugin for Moodle™ LMS

[![MDL Shield](https://img.shields.io/endpoint?url=https%3A%2F%2Fmdlshield.com%2Fapi%2Fbadge%2Ftool_murelation)](https://mdlshield.com/plugins/tool_murelation) ![Moodle Plugin CI](https://github.com/mutms/moodle-tool_murelation/actions/workflows/moodle-ci.yml/badge.svg)

Structured supervisor-subordinate relationships for standard Moodle™ LMS installations — fully open
source under GPL 3.0, with no restrictions on commercial use. Part of the [MuTMS suite](https://github.com/mutms).

Allows administrators to define relationship frameworks between users — managers and employees,
teachers and students, parents and children — and use those relationships across other MuTMS plugins
for notifications, approval workflows, and report content restrictions.

## Features

* Flexible framework design supporting both simple supervisor relationships and named teams
* Role assignments in the subordinate's user context based on the defined relationship
* Cohort-based access restrictions for managing relationships
* Team cohorts — subordinates of a team can be automatically added as cohort members
* Multi-tenancy aware — relationships respect tenant boundaries
* Used by other MuTMS plugins for notifications, approvals, and report restrictions

## Supervisors mode

One supervisor per subordinate, organised as a tree hierarchy. Suitable for manager-employee or
teacher-student relationships where workflows start with subordinate selection.

* One supervisor position per subordinate per framework
* No vacant supervisor positions
* No team names or position names
* Access control defined in the subordinate user context with optional cohort restrictions

How to set up:

1. Go to Site administration / Users / User relation frameworks
2. Add a new framework using the Supervisors mode
3. Open a user profile and use the Actions menu to add a supervisor

## Teams mode

Named teams of subordinates managed by a team supervisor. Suitable for classes, project teams, or
organisational units where workflows start with team creation.

* Teams have a name and optional ID number; each member can have a different position name
* Supervisor position may be vacant
* Supervisor can be added as their own team member
* Team cohort can be created automatically — all team members are added as cohort members
* Access control defined at system or tenant level with optional cohort restrictions

How to set up:

1. Go to Site administration / Users / User relation frameworks
2. Add a new framework using the Teams mode
3. Go to the Teams tab
4. Add teams and team members
5. Relationships are visible in user profiles

## Requirements

> This plugin is included in the [MuTMS distribution](https://github.com/mutms/mutms) —
> no manual installation needed if you use the distribution.

Required plugins:

* [Additional tools library plugin](https://github.com/mutms/moodle-tool_mulib)

## Roadmap

* Approval workflows for program allocations and certification assignments
* Additional and temporary supervisors
* Report builder content restrictions

## Documentation

See [online documentation](https://docs.mutms.org/murelation/) for more
information.

---

> MuTMS is an independent open-source project, not affiliated with Moodle HQ.
