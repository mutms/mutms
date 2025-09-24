# Supervisors and teams plugin for Moodle™ LMS

![Moodle Plugin CI](https://github.com/mutms/moodle-tool_murelation/actions/workflows/moodle-ci.yml/badge.svg)

The Supervisors and teams plugin enables administrators to establish structured relationships between users in Moodle™ LMS, such as parents, homeroom teachers, or managers. These connections are organized as supervisor-subordinate relationships, allowing institutions to manage global and even project based user roles efficiently. This plugin also allows managers to create teams of subordinates that share the same supervisor, for example students in homeroom classes or long term project teams.

Administrators can define relationship frameworks that assign specific roles to supervisors in the user context of their subordinates. Access to managing these relationships can be restricted based on cohort membership, ensuring precise control over assignments. If needed, position eligibility within frameworks can also be limited to specific cohort members.

Each user may hold only one subordinate position within a framework while having multiple supervisor positions. This structural limitation allows relationships to be represented as a tree hierarchy. When multiple supervisors of the same type are required, separate frameworks can be created, such as Parent A and Parent B.

Beyond role assignments, the plugin enhances LMS functionality by enabling relationships to be utilized across other plugins for notifications, approval processes, report content restrictions, etc. To support real-world scenarios, it also allows administrators to designate subordinate teams, such as "Class A 2025," even before assigning a supervisor. This approach ensures uninterrupted transitions when changes in management hierarchy occur.

With its flexible framework design, this plugin makes relationship management in Moodle™ LMS more structured, adaptable, and aligned with diverse educational and organizational needs.

To use this plugin you need to install [Additional MuTMS libraries plugin for Moodle™ LMS](https://github.com/mutms/moodle-tool_mulib).

## Supervisors mode

- Frameworks using this simple mode allow you to define one supervisor for each subordinate.
- It is not possible to have unassigned positions of supervisors.
- Supervisors cannot supervise themselves.
- Subordinates that share the same supervisor are not supposed to have any team interactions.
- Workflows usually start with subordinate selection.
- Access control is defined in the subordinate user context with optional cohort restrictions.
- If multi-tenancy is active then relationship belongs into the same tenant as the subordinate.
- There are no team or position names.
- Cohorts cannot be created automatically.
- When changing tenant memberships supervisors may be deleted to respect tenant separation, this is done at a later time from cron task.

## Teams mode

- In the Teams mode the emphasis is on groups of subordinates managed by a team supervisor.
- The team name is required, team ID number is option, each team member may have different position name.
- It is possible to add supervisor as own subordinate (aka team member).
- Unlike in Supervisors mode, the team supervisor position may be vacant.
- Workflows usually start with team creation.
- Access control is defined on the system or tenant context level with optional cohort restrictions. 
- If multi-tenancy is active then new teams are created in the current tenant.
- Team name is required.
- Team cohort may be created, all subordinates of the team are then automatically added as cohort members.
- When changing tenant memberships user may be removed from teams to respect tenant separation, this is done at a later time from cron task.

## Planned features

* Notification of related users in programs and certifications
* Approvals by related users in programs allocations and certification assignments
* Additional/temporary supervisors
* Report builder content restrictions
