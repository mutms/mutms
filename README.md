# Multi-tenancy plugin for Moodle™ LMS

[![MDL Shield](https://img.shields.io/endpoint?url=https%3A%2F%2Fmdlshield.com%2Fapi%2Fbadge%2Ftool_mutenancy)](https://mdlshield.com/plugins/tool_mutenancy) ![Moodle Plugin CI](https://github.com/mutms/moodle-tool_mutenancy/actions/workflows/moodle-ci.yml/badge.svg)

Introduces multi-tenancy to standard Moodle™ LMS installations — fully open source under GPL 3.0,
with no restrictions on commercial use. Part of the [MuTMS suite](https://github.com/mutms).

Multi-tenancy allows a single Moodle instance to be partitioned into isolated tenants, each with
their own users, roles, courses, appearance, and settings — making it possible to serve multiple
independent business units or client organisations from one installation.

This plugin requires a small core patch to Moodle™ LMS. The patch and all MuTMS plugins are
included in the [MuTMS distribution](https://github.com/mutms/mutms) for easy deployment. In the
[Moodle plugins database](https://moodle.org/plugins/tool_mutenancy) it is listed as Experimental
solely due to this core patch requirement — it is production ready.

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

> This plugin is included in the [MuTMS distribution](https://github.com/mutms/mutms) —
> no manual installation needed if you use the distribution.

For manual installation:

* Moodle™ LMS 5.2.0
* [MuTMS core patch](https://github.com/mutms/patches/tree/patch/mutenancy/MOODLE_502_STABLE)
* [Additional tools library plugin](https://github.com/mutms/moodle-tool_mulib)

## Documentation

See the [online documentation](https://docs.mutms.org/mutenancy/) for installation
instructions and configuration reference.

---

> MuTMS is an independent open-source project, not affiliated with Moodle HQ.
