# Log-in-as via Incognito window plugin for Moodle™ LMS

![Moodle Plugin CI](https://github.com/mutms/moodle-tool_muloginas/actions/workflows/moodle-ci.yml/badge.svg)

This plugin enhances the standard **"Log in as"** feature in Moodle™ LMS, improving user experience and security.

Instead of switching users within the same session, it introduces an option to launch a **new Incognito window** for a separate **"Log in as"** session.

## Key benefits
- **No repeated logins:** Administrators don’t need to log back in after ending the **"Log in as"** session.
- **Parallel access:** The admin’s session remains active in the main browser window, while the **"Log in as"** session runs separately in Incognito mode.
- **Improved security:** Incognito mode is designed for handling untrusted content, ensuring the **"Log in as"** session does not interfere with normal LMS use.

## Known issues

- In Safari the option to *"Open in New Private Window"* is only available when already using a private window.
- In Chrome and Microsoft Edge the Incognito windows share a single session, meaning only *one "Log in as" session* can be active at a time.
- Course level "Log in as" is not supported.
- For security reasons, the generated log in as link expires after fifteen seconds, which may not be optional for accessibility.
- This feature may not be compatible with mobile phone and tablet browsers.
- Test coverage is minimal due to the inability to test Incognito sessions in Behat.
