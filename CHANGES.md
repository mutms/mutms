# Changelog

## mu-5.0.4-02

Release date: 16/12/2025

* No changes.

## mu-5.0.4-01

Release date: 08/12/2025

* Added new setting to allow guest access to tenants.
* Added tenant restriction to get_with_capability_sql().
* Fixed listing of tenant contexts on permissions related pages.
* Tidied up autocomplete web services and improved performance on large sites.
* List of tenants in management UI is sorted by name by default
* Added tenant entity name - it is not necessary to edit language packs to replace "Tenant" and "Tenants" words in UI
* Added tenantid in core WS: core_user_create_users, core_user_get_users_by_field, core_user_get_users 
* Added web services for management of tenants.
* Tenant switching has been simplified: associated users and tenant managers can now switch tenants by default. Internally, the tool/mutenancy:switch capability is now used in the tenant context instead of the system context, and no longer requires the tool/mutenancy:view capability. Existing tenant manager roles need to be updated manually to include the switch permission.

## mu-5.0.3-02

Release date: 08/11/2025

* No changes.

## mu-5.0.3-01

Release date: 06/10/2025

* No changes.

## mu-5.0.2-03

Release date: 24/09/2025

* Added event for user tenant allocation changes.

## mu-5.0.2-02

Release date: 31/08/2025

* Added bulk tenant members allocation and deallocation in Browse list of users.
* Added help icons to tenant forms.
* Added checkbox to create Associated users cohort when creating or updating tenants.
* Added Tenant management section to primary menu.
* Fixed compatibility with unsupported MS SQL databases.

## mu-5.0.2-01

Release date: 09/08/2025

* Internal refactoring.
* Moodle 5.0.2 support.

## mu-5.0.1-01

Release date: 30/06/2025

* Added support for Moodle 5.0
