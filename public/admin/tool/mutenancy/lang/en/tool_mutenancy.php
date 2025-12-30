<?php
// This file is part of MuTMS suite of plugins for Moodle™ LMS.
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

/**
 * Multi-tenancy language strings.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['associate'] = 'Associated user';
$string['associate_add'] = 'Associate users';
$string['associate_add_info'] = 'Non-tenant users can be associated with tenant by adding them to the associated users cohort.
It is possible for non-tenant users to be associated with multiple tenants. Multiple tenants may share the same associated users cohort.';
$string['associate_cohort'] = 'Associated users cohort';
$string['associate_cohort_create'] = 'Create associated users cohort';
$string['associate_cohort_create_help'] = 'Select to create a new cohort for users that are associated with the tenant.';
$string['associate_cohort_help'] = 'Select cohort that contains global users that are supposed to participate in the tenant.

Global users can be associated with multiple tenants, but tenant accounts cannot be associated with any other tenant.';
$string['associate_remove'] = 'Disassociate user';
$string['associate_remove_info'] = 'Users are disassociated from tenant by removing them from the associated users cohort.
This may affect multiple tenants when tenants share the same associated users cohort.';
$string['auth_edit'] = 'Update authentication';
$string['boost_edit'] = 'Edit Boost';
$string['bulk_allocate'] = 'Allocate users to tenant';
$string['bulk_allocate_info'] = 'Following users will be allocated to the selected tenant:

{$a}

Tenant manager assignments will be removed and users will loose access to all other tenants. Administrator accounts will not modified.';
$string['bulk_deallocate'] = 'Deallocate tenant members';
$string['bulk_deallocate_info'] = 'Following users will be deallocated from tenants and will become regular users:

{$a}';
$string['cachedef_config'] = 'Tenant config cache';
$string['cachedef_tenant'] = 'Tenant record cache';
$string['config_default'] = 'Default value';
$string['config_default_value'] = 'Default value ({$a})';
$string['config_override'] = 'Override';
$string['config_override_value'] = 'Override default ({$a})';
$string['config_value'] = 'Value';
$string['environment_corepatch_error'] = 'Required Multi-tenancy patch is missing or version is invalid';
$string['environment_corepatch_ok'] = 'Correct patch version detected';
$string['error:changerequired'] = 'Change required';
$string['error:memberlimitreached'] = 'Member limit reached';
$string['event_appearance_updated'] = 'Tenant appearance updated';
$string['event_auth_updated'] = 'Tenant authentication updated';
$string['event_tenant_archived'] = 'Tenant archived';
$string['event_tenant_created'] = 'Tenant created';
$string['event_tenant_deleted'] = 'Tenant deleted';
$string['event_tenant_restored'] = 'Tenant restored';
$string['event_tenant_updated'] = 'Tenant updated';
$string['event_user_allocated'] = 'User tenant allocation changed';
$string['login_tenant_select'] = 'Select site';
$string['login_tenant_select_a'] = 'Select {$a}';
$string['logos_edit'] = 'Edit logos';
$string['member_confirm_info'] = 'In most cases member accounts should not be confirmed here.

It is recommended to use *Resend confirmation email* option if user did not receive the confirmation email.';
$string['member_create'] = 'Create account';
$string['member_create_info'] = 'New tenant member account will be able to access their tenant courses and if allowed also global courses.

They will not be able to access courses from any other tenant';
$string['member_delete_info'] = 'Deleting of tenant member account is not reversible.';
$string['member_managers_info'] = 'Tenant managers are not automatically visible in tenants. They should be added to
the associated users cohort if they are expected to participate in the tenant.';
$string['member_resend_info'] = 'New account confirmation emails instruct users to prove they
own the email provided during sign up.

This is an important security feature, manual confirmation of new accounts is not recommended.';
$string['member_suspend_info'] = 'Account suspension was designed to prevent users from logging in and
it should also stop all outgoing emails and notifications, nothing else.

Unfortunately users and developers often misunderstand account suspension which may result in inconsistent
behaviour of some plugins and in some rare cases it may cause minor data loss.';
$string['member_unlock_info'] = 'User accounts get locked after repeated failed login attempts. Unlocking account lets them try again.';
$string['member_unsuspend_info'] = 'After account is re-activated user should be able to log in again
and they will start receiving emails and notifications.';
$string['member_update'] = 'Update account';
$string['mutenancy:admin'] = 'Administer tenants';
$string['mutenancy:allocate'] = 'Allocate users to tenants';
$string['mutenancy:configappearance'] = 'Configure tenant theme and branding';
$string['mutenancy:configauth'] = 'Configure tenant authentication';
$string['mutenancy:membercreate'] = 'Create account for tenant member';
$string['mutenancy:memberdelete'] = 'Delete account of tenant member';
$string['mutenancy:memberupdate'] = 'Update account of tenant member';
$string['mutenancy:switch'] = 'Switch to tenant';
$string['mutenancy:view'] = 'View tenant';
$string['navigation_category'] = 'Tenant category';
$string['navigation_top'] = 'Tenant management';
$string['pluginname'] = 'Multi-tenancy';
$string['privacy:metadata:tool_mutenancy_manager'] = 'Tenant managers';
$string['privacy:metadata:tool_mutenancy_manager:tenantid'] = 'Tenant id';
$string['privacy:metadata:tool_mutenancy_manager:timecreated'] = 'Time of creation';
$string['privacy:metadata:tool_mutenancy_manager:usercreated'] = 'User who created tenant';
$string['privacy:metadata:tool_mutenancy_manager:userid'] = 'Manager user id';
$string['role_tenantmanager_archetype'] = 'ARCHETYPE: Tenant manager';
$string['role_tenantmanager_description'] = 'Tenant manager role gets assigned to all tenant mangers automatically in tenant and tenant category contexts.

This role is not supposed to be assigned manually, do not change the role short name.';
$string['role_tenantmanager_name'] = 'Tenant manager';
$string['role_tenantuser_archetype'] = 'ARCHETYPE: Tenant user';
$string['role_tenantuser_description'] = 'Tenant user role gets assigned to all tenant users automatically in tenant category contexts.

This role is not supposed to be assigned manually, do not change the role short name.';
$string['role_tenantuser_name'] = 'Tenant user';
$string['secondary_tenant_appearance'] = 'Appearance';
$string['secondary_tenant_auth'] = 'Authentication';
$string['secondary_tenant_details'] = 'Details';
$string['secondary_tenant_users'] = 'Users';
$string['setting_allowguests'] = 'Allow guest access to tenants';
$string['setting_allowguests_desc'] = 'Enable this setting if you want to allow guest access to tenant courses.

Note that this feature requires site‑level guest access to be enabled. In addition, tenant courses must have guest access enabled, and users should be associated with tenants rather than being tenant members.';
$string['setting_tenantentities'] = 'Tenant entity plural';
$string['setting_tenantentities_desc'] = 'If provided the plural "Tenants" word will be replaced with given custom name. For example: Units, Faculties or Events';
$string['setting_tenantentity'] = 'Tenant entity singular';
$string['setting_tenantentity_desc'] = 'If provided the singular "Tenant" word will be replaced with given custom name. For example: Unit, Faculty or Event';
$string['setting_tenantlimit'] = 'Tenant limit';
$string['setting_tenantlimit_desc'] = 'Specifies how many tenants can be created. Note that sites with more than 100 tenants may encounter performance and usability problems.';
$string['setting_tenantprimarynav'] = 'Add tenant management to primary menu';
$string['setting_tenantprimarynav_desc'] = 'When enabled current tenant management section is added to primary menu. This is intended for dedicated tenant managers or users that can switch to tenants.';
$string['settings'] = 'Multi-tenancy settings';
$string['taskcron'] = 'Multi-tenancy cleanup task';
$string['tenancy_activate'] = 'Activate multi-tenancy';
$string['tenancy_activate_info'] = 'New roles for Tenant managers and Tenant users will be created during multi-tenancy activation.

Multi-tenancy can be de-activated only after all tenants are deleted.';
$string['tenancy_deactivate'] = 'De-activate multi-tenancy';
$string['tenancy_deactivate_info'] = 'Tenant manager role will be deleted during multi-tenancy de-activation.';
$string['tenant'] = 'Tenant';
$string['tenant_actions'] = 'Actions';
$string['tenant_archive'] = 'Archive tenant';
$string['tenant_archive_a'] = 'Archive {$a}';
$string['tenant_archive_info'] = 'Archiving tenant:

* prevents tenant members from logging in and stops all outgoing emails and notifications,
* hides tenant category.

Archiving is a required step before tenant can be deleted.';
$string['tenant_archived'] = 'Archived';
$string['tenant_associates'] = 'Associated users';
$string['tenant_category'] = 'Tenant category';
$string['tenant_categoryidnumber'] = 'Tenant category ID number';
$string['tenant_categoryname'] = 'Tenant category name';
$string['tenant_cohort'] = 'Tenant cohort';
$string['tenant_cohortidnumber'] = 'Tenant cohort ID number';
$string['tenant_cohortname'] = 'Tenant cohort name';
$string['tenant_create'] = 'Add tenant';
$string['tenant_create_a'] = 'Add {$a}';
$string['tenant_delete'] = 'Delete tenant';
$string['tenant_delete_a'] = 'Delete {$a}';
$string['tenant_delete_info'] = 'During tenant deletion:

* tenant manager permissions will be revoked
* tenant cohort will be migrated to a regular cohort
* all tenant members will be allocated to the global site

Subcategories, courses and user data will not be changed.';
$string['tenant_delete_movetotenant'] = 'Allocate members to';
$string['tenant_idnumber'] = 'Tenant ID';
$string['tenant_loginshow'] = 'Show tenant on login page';
$string['tenant_loginurl'] = 'Tenant login URL';
$string['tenant_loginurl_copy'] = 'Copy tenant login URL to clipboard';
$string['tenant_manager'] = 'Tenant manager';
$string['tenant_managers'] = 'Tenant managers';
$string['tenant_member'] = 'Tenant member';
$string['tenant_member_a'] = '{$a} member';
$string['tenant_memberlimit'] = 'Tenant members limit';
$string['tenant_memberlimit_help'] = 'Specifies maximum number of tenant member accounts that can be created in the tenant.

Note that associated users are not counted towards this limit.';
$string['tenant_name'] = 'Tenant name';
$string['tenant_restore'] = 'Restore archived tenant';
$string['tenant_restore_a'] = 'Restore archived {$a}';
$string['tenant_restore_info'] = 'Restoring of tenant should revert most changes done during tenant archiving.

It is however recommended to verify all tenant settings and category visibility afterwards.';
$string['tenant_sitefullname'] = 'Tenant site name';
$string['tenant_siteshortname'] = 'Tenant site short name';
$string['tenant_switch'] = 'Switch tenant';
$string['tenant_switch_a'] = 'Switch {$a}';
$string['tenant_switch_info'] = 'Switching to a tenant changes site appearance and it restricts list of candidate available users for some actions.

Note that switching to tenant is not equivalent to being a tenant member, there are additional restrictions affecting tenant members.';
$string['tenant_switch_my'] = 'My tenants';
$string['tenant_switch_my_a'] = 'My {$a}';
$string['tenant_switch_notenant'] = 'No tenant';
$string['tenant_switch_notenant_a'] = 'No {$a}';
$string['tenant_switch_other'] = 'Other tenants';
$string['tenant_switch_other_a'] = 'Other {$a}';
$string['tenant_update'] = 'Update tenant';
$string['tenant_update_a'] = 'Update {$a}';
$string['tenant_users'] = 'Tenant users';
$string['tenant_users_a'] = '{$a} users';
$string['tenants'] = 'Tenants';
$string['user_allocate'] = 'Allocate user';
$string['user_allocate_info'] = 'When allocating user to a tenant their tenant manager assignments are removed
and they will loose access to all other tenants.';
$string['user_tenants'] = 'Associated with tenants';
$string['user_tenants_a'] = 'Associated with {$a}';
