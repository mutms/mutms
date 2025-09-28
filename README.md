# Privileged sessions (aka sudo) plugin for Moodle™ LMS

![Moodle Plugin CI](https://github.com/mutms/moodle-tool_musudo/actions/workflows/moodle-ci.yml/badge.svg)

Like all other software, administrator accounts in Moodle should not be used for daily tasks. The privileged sessions feature (also known as web sudo) allows administrators to log in with their low-privilege accounts and switch to a privileged session mode when they need to manage the site.

Another use case is working around bugs in plugins and core that appear when a user has both teacher and student accounts in the same course. In these cases, it is possible to enrol the user as a normal student and switch to a privileged session only when they need to act as an editing teacher.

To further improve security, the privileged session can be protected with existing MFA factors.

## Documentation

### Configuration steps

1. Log in as admin. (Only administrators may configure which users can use privileged sessions.)
2. Navigate to "Site administration / Users / Permissions / Privileged users".
3. Press "Add privileged user".
4. Select the user that should be granted sudo access.
5. Define the roles and contexts where the user will have privileged access. (You may find the context ID numbers in page URLs when overriding permissions.)
6. Enforce multi-factor authentication for additional security, if desired.

### Starting privileged session

1. Log in as privileged user - use your regular, low-privilege account to log in.
2. Click on user menu in top right.
3. Select "Start privileged session".
4. Press "Continue" or supply MFA verification code.
5. Always remember to end the privileged session once your management tasks are complete.

## Known issues

* This plugin is internally using the Switch role feature, due to that course level privileges appear as "Switched roles" in Moodle UI.
