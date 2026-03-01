# Multi-tenancy plugin for Moodle™ LMS

![Moodle Plugin CI](https://github.com/mutms/moodle-tool_mutenancy/actions/workflows/moodle-ci.yml/badge.svg)

Introduces multi-tenancy to standard Moodle™ LMS installations — fully open source under GPL 3.0,
with no restrictions on commercial use. Requires a small core patch included in the MuTMS distribution.

Multi-tenancy allows a single Moodle instance to be partitioned into isolated tenants, each with
their own users, roles, courses, appearance, and settings — making it possible to serve multiple
independent business units or client organisations from one installation.

## Features

* Tenant management — create, configure, and delete tenants
* User isolation — users are scoped to their tenant and cannot access other tenants
* Tenant-specific roles and permissions
* Tenant-specific appearance and branding
* Non-intrusive design — standard Moodle features and workflows remain fully functional
* Fully uninstallable — removing the plugin does not affect the rest of your Moodle installation

## Roadmap

* Universal catalogue — tenant-specific course catalogue
* Tenant separation improvements
* Migration scripts — automated migration from other Moodle-based multi-tenancy systems
* Moodle Mobile App support improvements

## Requirements

* Moodle™ LMS
* MuTMS core patch (included in the [MuTMS distribution](https://github.com/mutms/mutms))

## Documentation

See the [online documentation](https://github.com/mutms/moodle-tool_mutenancy/wiki) for installation
instructions and configuration reference.
