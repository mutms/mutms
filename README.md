# MuTMS distribution

**MuTMS (Multi-Tenant Management System)** is a GPL 3.0-licensed suite of plugins for Moodle™ LMS
that brings additional features to standard Moodle installations — multi-tenancy,
structured learning programs, certifications, and more — with no commercial restrictions and no
vendor lock-in.

This repository is the full MuTMS distribution: all core patches and optional plugins in one place,
assembled via git subtrees for easy deployment.

## Overview

All components are designed to work together as a coherent system, but most plugins can also be
used independently without the core multi-tenancy patch.

## Plugins

- [Multi-tenancy](https://github.com/mutms/moodle-tool_mutenancy) — Partition a single Moodle instance into isolated tenants, each with their own users, courses, and settings.
- [Programs](https://github.com/mutms/moodle-tool_muprog) — Define structured learning paths, manage enrolments, track progress, and automate completion across a program as a whole.
- [Certifications](https://github.com/mutms/moodle-tool_mucertify) — Issue and manage certifications tied to program completion, with expiry and renewal cycle support.
- [Training credits](https://github.com/mutms/moodle-tool_mutrain) — Allocate credit budgets and gate access to learning activities based on available credits.
- [Supervisors & teams](https://github.com/mutms/moodle-tool_murelation) — Model learner–supervisor relationships so managers can monitor team progress and compliance.
- [Custom home pages](https://github.com/mutms/moodle-tool_muhome) — Configure cohort- and tenant-specific dashboards and landing pages.
- [Interactive book](https://github.com/mutms/moodle-mod_mubook) — A structured, page-based content module for course materials.
- [Compromised password blocking](https://github.com/mutms/moodle-tool_mupwned) — Blocks known breached passwords via HaveIBeenPwned, using k-Anonymity so passwords never leave Moodle.
- [Privileged sessions](https://github.com/mutms/moodle-tool_musudo) — Sudo-style privilege escalation for admins, reducing risk during routine work.
- [Log-in-as via Incognito](https://github.com/mutms/moodle-tool_muloginas) — Opens impersonated sessions in a new Incognito window, keeping the admin session active.

## Installation

This repository contains a full Moodle installation with MuTMS patches and plugins already applied. Clone it in place of a standard Moodle checkout.

```bash
git clone https://github.com/mutms/mutms.git
```

See the [installation documentation](https://docs.mutms.org/mutms/installation/) for full setup instructions.

## Documentation

See [online documentation](https://docs.mutms.org/).

## Support

Open an issue in the relevant plugin repository for community support.

For support, see [mutms.org#support](https://www.mutms.org/#support).

## License

MuTMS is free and open source software, licensed under the GNU General Public License v3.0.

> Moodle™ is a trademark of Moodle Pty Ltd. MuTMS is an independent open-source
> project and is not affiliated with or endorsed by Moodle Pty Ltd.
