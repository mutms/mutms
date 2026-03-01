# Log-in-as via Incognito window plugin for Moodle™ LMS

![Moodle Plugin CI](https://github.com/mutms/moodle-tool_muloginas/actions/workflows/moodle-ci.yml/badge.svg)

Enhances the standard Moodle™ LMS "Log in as" feature — fully open source under GPL 3.0, with no
restrictions on commercial use. Part of the [MuTMS suite](https://github.com/mutms).

Instead of switching users within the same browser session, this plugin adds an option to open the
"Log in as" session in a new Incognito window — keeping the admin session active in the main window
and avoiding the need to log back in afterwards.

## Features

* "Log in as" opens in a new Incognito window — no repeated logins for administrators
* Admin session remains active in the main window in parallel
* Incognito mode isolates the "Log in as" session from normal LMS use

## Known limitations

* In Safari, "Open in New Private Window" is only available when already in a private window
* In Chrome and Edge, Incognito windows share a single session — only one "Log in as" session can be active at a time
* Course-level "Log in as" is not supported
* The generated link expires after fifteen seconds, which may be an accessibility concern
* Mobile and tablet browsers may not be fully supported
* Test coverage is minimal due to the inability to test Incognito sessions in Behat

## Requirements

> This plugin is included in the [MuTMS distribution](https://github.com/mutms/mutms) —
> no manual installation needed if you use the distribution.

No other plugins are required.

---

> MuTMS is an independent open-source project, not affiliated with Moodle HQ.
