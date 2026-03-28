# Compromised passwords blocking plugin for Moodle™ LMS

![Moodle Plugin CI](https://github.com/mutms/moodle-tool_mupwned/actions/workflows/moodle-ci.yml/badge.svg)

Blocks compromised passwords in standard Moodle™ LMS installations — fully open source under GPL
3.0, with no restrictions on commercial use. Part of the [MuTMS suite](https://github.com/mutms).

Checks passwords against the [Have I Been Pwned](https://haveibeenpwned.com) database of known
breaches when passwords are created, updated, or optionally on every login. Uses the k-Anonymity
API — the full password is never sent outside Moodle. Users with a compromised password are blocked
until they reset it.

## Features

* Checks passwords on creation and update
* Optional check on every login
* k-Anonymity API — no full password ever leaves Moodle
* Blocks access until a compromised password is replaced

## Configuration

1. Install the plugin
2. Log in as admin — ensure you can reset your administrator password via email if needed
3. Enable the Password policy setting and review password requirements
4. Enable the Check password on login setting
5. Go to Site administration / Plugins / Authentication / Compromised password blocking
6. Enable Detect compromised passwords

If anything goes wrong, passwords can be reset from the CLI via `/admin/cli/reset_password.php`.

## Requirements

> This plugin is included in the [MuTMS distribution](https://github.com/mutms/mutms) —
> no manual installation needed if you use the distribution.

No other plugins are required.

## Documentation

See [online documentation](https://docs.mutms.org/mupwned/) for more information.

---

> MuTMS is an independent open-source project, not affiliated with Moodle HQ.
