# Compromised passwords blocking plugin for Moodle™ LMS

![Moodle Plugin CI](https://github.com/mutms/moodle-tool_mupwned/actions/workflows/moodle-ci.yml/badge.svg)

This Moodle plugin strengthens account security by adding a site‑wide setting that checks user passwords both when they are created or updated and optionally during every login. It verifies passwords against the Have I Been Pwned database of known breaches, using the anonymous (k‑Anonymity) API mode so the full password is never sent outside Moodle. If a compromised password is detected at any of these points, the user is blocked from proceeding until they reset their password to a safer alternative. This continuous verification helps prevent account access with credentials exposed in past breaches and reduces the risk of account takeover.

## Configuration steps

1. Install plugin.
2. Log in as admin - make sure you can reset your own administrator password via email if necessary.
3. Enable "Password policy" setting and review password requirements.
4. Enable "Check password on login" setting.
5. Navigate to "Site administration / Plugins / Authentication / Compromised password blocking" settings page.
6. Enable "Detect compromised passwords".
7. If anything goes wrong you can also reset passwords from CLI, see /admin/cli/reset_password.php

## Roadmap

* Target for production release and availability of paid support: Q2 2026
