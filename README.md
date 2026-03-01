# MuTMS distribution

**MuTMS (Multi-Tenant Management System)** is a GPL 3.0-licensed suite of plugins for Moodle™ LMS
that brings enterprise-grade capabilities to standard Moodle installations — multi-tenancy,
structured learning programs, certifications, and more — with no commercial restrictions and no
vendor lock-in.

This repository is the full MuTMS distribution: all core patches and optional plugins in one place,
assembled via git subtrees for easy deployment.

## Overview

All components are designed to work together as a coherent system, but most plugins can also be
used independently without the core multi-tenancy patch.

### Plugin groups

**Multi-tenancy** — Partition a single Moodle instance into isolated tenants, each with their own
users, roles, courses, appearance, and settings.

**Programs** — Define structured learning paths composed of courses. Manage enrolments, track
learner progress, and automate completion across a program as a whole.

**Certifications** — Issue and manage certifications tied to program completion, including
expiry, renewal cycles, and certification records.

**Training credits** — Allocate and track training credit budgets, controlling access to learning
activities based on available credits via programs.

**Supervisors & teams** — Model organisational relationships between learners and their supervisors,
enabling managers to monitor team progress and compliance.

**Custom home pages** — Configure tenant-specific dashboards and landing pages to give each
organisation a tailored Moodle experience.

**Interactive book** — An enhanced course content module for structured, page-based learning
materials.

**Security utilities** — Includes compromised password blocking (HaveIBeenPwned integration) and
other hardening tools suitable for production deployments.

## Installation

Clone this repository and use it as your Moodle root directory,.

```bash
git clone https://github.com/mutms/mutms.git
```

See individual plugin wikis for detailed configuration instructions.

## Plugins

| Plugin | Description |
|--------|-------------|
| [tool_mutenancy](https://github.com/mutms/moodle-tool_mutenancy) | Multi-tenancy |
| [tool_muprog](https://github.com/mutms/moodle-tool_muprog) | Programs |
| [tool_mucertify](https://github.com/mutms/moodle-tool_mucertify) | Certifications |
| [tool_mutrain](https://github.com/mutms/moodle-tool_mutrain) | Training credits |
| [tool_murelation](https://github.com/mutms/moodle-tool_murelation) | Supervisors & teams |
| [tool_muhome](https://github.com/mutms/moodle-tool_muhome) | Custom home pages |
| [mod_mubook](https://github.com/mutms/moodle-mod_mubook) | Interactive book |
| [tool_musudo](https://github.com/mutms/moodle-tool_musudo) | Privileged sessions |
| [tool_mupwned](https://github.com/mutms/moodle-tool_mupwned) | Compromised password blocking |
| [tool_mulib](https://github.com/mutms/moodle-tool_mulib) | Shared library (required) |

## Documentation

See the GitHub wikis in relevant plugins.

## Support

Community support via GitHub Issues in relevant plugins.
Commercial support available from Q2 2026 — contact [support@mutms.com](mailto:support@mutms.com).

## License

MuTMS is free and open source software, licensed under the GNU General Public License v3.0.

> Moodle™ is a trademark of Moodle Pty Ltd. MuTMS is an independent open-source
> project and is not affiliated with or endorsed by Moodle Pty Ltd.

---

# Moodle

<p align="center"><a href="https://moodle.org" target="_blank" title="Moodle Website">
  <img src="https://raw.githubusercontent.com/moodle/moodle/main/.github/moodlelogo.svg" alt="The Moodle Logo">
</a></p>

[Moodle][1] is the World's Open Source Learning Platform, widely used around the world by countless universities, schools, companies, and all manner of organisations and individuals.

Moodle is designed to allow educators, administrators and learners to create personalised learning environments with a single robust, secure and integrated system.

## Documentation

- Read our [User documentation][3]
- Discover our [developer documentation][5]
- Take a look at our [demo site][4]

## Community

[moodle.org][1] is the central hub for the Moodle Community, with spaces for educators, administrators and developers to meet and work together.

You may also be interested in:

- attending a [Moodle Moot][6]
- our regular series of [developer meetings][7]
- the [Moodle User Association][8]

## Installation and hosting

Moodle is Free, and Open Source software. You can easily [download Moodle][9] and run it on your own web server, however you may prefer to work with one of our experienced [Moodle Partners][10].

Moodle also offers hosting through both [MoodleCloud][11], and our [partner network][10].

## License

Moodle is provided freely as open source software, under version 3 of the GNU General Public License. For more information on our license see

[1]: https://moodle.org
[2]: https://moodle.com
[3]: https://docs.moodle.org/
[4]: https://sandbox.moodledemo.net/
[5]: https://moodledev.io
[6]: https://moodle.com/events/mootglobal/
[7]: https://moodledev.io/general/community/meetings
[8]: https://moodleassociation.org/
[9]: https://download.moodle.org
[10]: https://moodle.com/partners
[11]: https://moodle.com/cloud
[12]: https://moodledev.io/general/license
