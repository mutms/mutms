# Changelog

## mu-5.1.1-02

Release date: 16/11/2025

* No changes.

## mu-5.1.1-01

Release date: 08/11/2025

* Added new setting to allow guest access to tenants.
* Added tenant restriction to get_with_capability_sql().
* Fixed listing of tenant contexts on permissions related pages.
* Tidied up autocomplete web services and improved performance on large sites.
* List of tenants in management UI is sorted by name by default
* Added tenant entity name - it is not necessary to edit language packs to replace "Tenant" and "Tenants" words in UI
* Added tenantid in core WS: core_user_create_users, core_user_get_users_by_field, core_user_get_users 
* Added web services for management of tenants.
* Tenant switching has been simplified: associated users and tenant managers can now switch tenants by default. Internally, the tool/mutenancy:switch capability is now used in the tenant context instead of the system context, and no longer requires the tool/mutenancy:view capability. Existing tenant manager roles need to be updated manually to include the switch permission.

## mu-5.1.0-02

Release date: 08/11/2025

* No changes.

## mu-5.1.0-01

Release date: 06/10/2025

* Added support for Moodle 5.1.0 release.
