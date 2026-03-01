# Privileged sessions (aka sudo) plugin for Moodle™ LMS

![Moodle Plugin CI](https://github.com/mutms/moodle-tool_musudo/actions/workflows/moodle-ci.yml/badge.svg)

Privileged session management for standard Moodle™ LMS installations — fully open source under GPL
3.0, with no restrictions on commercial use. Part of the [MuTMS suite](https://github.com/mutms).

Allows administrators and privileged users to log in with a low-privilege account and switch to a
privileged session only when needed — similar to sudo on Linux. Privileged sessions can be
protected with existing MFA factors for additional security.

## Features

* Low-privilege daily accounts with on-demand privilege escalation
* Configurable roles and contexts per privileged user
* Optional MFA verification before starting a privileged session
* Useful for working around bugs that appear when a user holds both teacher and student roles in the same course

## Configuration

1. Log in as admin
2. Go to Site administration / Users / Permissions / Privileged users
3. Press Add privileged user
4. Select the user to grant sudo access
5. Define the roles and contexts where the user will have privileged access
6. Optionally enforce MFA for additional security

## Starting a privileged session

1. Log in with your regular low-privilege account
2. Click the user menu in the top right
3. Select Start privileged session
4. Press Continue or supply your MFA verification code
5. End the privileged session once your management tasks are complete

## Known limitations

* This plugin uses the Switch role feature internally — course-level privileges appear as "Switched roles" in the Moodle UI

## Requirements

> This plugin is included in the [MuTMS distribution](https://github.com/mutms/mutms) —
> no manual installation needed if you use the distribution.

Required plugins:

* [Additional tools library plugin](https://github.com/mutms/moodle-tool_mulib)

---

> MuTMS is an independent open-source project, not affiliated with Moodle HQ.
