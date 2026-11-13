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
 * Strings for component 'tool_mutenancy', language 'fr'.
 *
 * NOTE: This French translation was produced automatically by an AI language
 * model and has not yet been reviewed by a human translator. Corrections are
 * welcome.
 *
 * @package    tool_mutenancy
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['associate'] = 'Utilisateur associé';
$string['associate_add'] = 'Associer des utilisateurs';
$string['associate_add_info'] = 'Les utilisateurs n’appartenant pas à un locataire peuvent être associés à un locataire en les ajoutant à la cohorte des utilisateurs associés.
Ces utilisateurs peuvent être associés à plusieurs locataires. Plusieurs locataires peuvent partager la même cohorte des utilisateurs associés.';
$string['associate_cohort'] = 'Cohorte des utilisateurs associés';
$string['associate_cohort_create'] = 'Créer une cohorte des utilisateurs associés';
$string['associate_cohort_create_help'] = 'Cochez cette option pour créer une nouvelle cohorte destinée aux utilisateurs associés au locataire.';
$string['associate_cohort_help'] = 'Sélectionnez la cohorte contenant les utilisateurs globaux qui doivent participer au locataire.

Les utilisateurs globaux peuvent être associés à plusieurs locataires, mais les comptes d’un locataire ne peuvent être associés à aucun autre locataire.';
$string['associate_remove'] = 'Dissocier l’utilisateur';
$string['associate_remove_info'] = 'Les utilisateurs sont dissociés du locataire en les retirant de la cohorte des utilisateurs associés.
Cela peut concerner plusieurs locataires lorsqu’ils partagent la même cohorte des utilisateurs associés.';
$string['auth_edit'] = 'Modifier l’authentification';
$string['boost_edit'] = 'Modifier Boost';
$string['bulk_allocate'] = 'Affecter des utilisateurs à un locataire';
$string['bulk_allocate_info'] = 'Les utilisateurs suivants seront affectés au locataire sélectionné :

{$a}

Les attributions de gestionnaire du locataire seront retirées et les utilisateurs perdront l’accès à tous les autres locataires. Les comptes d’administrateur ne seront pas modifiés.';
$string['bulk_deallocate'] = 'Désaffecter des membres de locataires';
$string['bulk_deallocate_info'] = 'Les utilisateurs suivants seront désaffectés de leur locataire et deviendront des utilisateurs ordinaires :

{$a}';
$string['cachedef_config'] = 'Cache de la configuration des locataires';
$string['cachedef_tenant'] = 'Cache des enregistrements de locataires';
$string['config_default'] = 'Valeur par défaut';
$string['config_default_value'] = 'Valeur par défaut ({$a})';
$string['config_override'] = 'Remplacer';
$string['config_override_value'] = 'Remplacer la valeur par défaut ({$a})';
$string['config_value'] = 'Valeur';
$string['environment_corepatch_error'] = 'Le correctif d’architecture multilocataire requis est absent ou sa version n’est pas valide';
$string['environment_corepatch_ok'] = 'Version correcte du correctif détectée';
$string['error:changerequired'] = 'Une modification est requise';
$string['error:memberlimitreached'] = 'Limite de membres atteinte';
$string['event_appearance_updated'] = 'Apparence du locataire mise à jour';
$string['event_auth_updated'] = 'Authentification du locataire mise à jour';
$string['event_tenant_archived'] = 'Locataire archivé';
$string['event_tenant_created'] = 'Locataire créé';
$string['event_tenant_deleted'] = 'Locataire supprimé';
$string['event_tenant_restored'] = 'Locataire restauré';
$string['event_tenant_updated'] = 'Locataire mis à jour';
$string['event_user_allocated'] = 'Affectation de l’utilisateur à un locataire modifiée';
$string['login_tenant_select'] = 'Sélectionner le site';
$string['login_tenant_select_a'] = 'Sélectionner : {$a}';
$string['logos_edit'] = 'Modifier les logos';
$string['member_confirm_info'] = 'Dans la plupart des cas, les comptes des membres ne devraient pas être confirmés ici.

Il est recommandé d’utiliser l’option *Renvoyer le courriel de confirmation* si l’utilisateur n’a pas reçu le courriel de confirmation.';
$string['member_create'] = 'Créer un compte';
$string['member_create_info'] = 'Le nouveau compte de membre du locataire pourra accéder aux cours de son locataire et, si cela est autorisé, aux cours globaux.

Il ne pourra accéder aux cours d’aucun autre locataire.';
$string['member_delete_info'] = 'La suppression du compte d’un membre du locataire est irréversible.';
$string['member_managers_info'] = 'Les gestionnaires du locataire ne sont pas automatiquement visibles dans les locataires. Ils doivent être ajoutés
à la cohorte des utilisateurs associés s’ils sont censés participer au locataire.';
$string['member_resend_info'] = 'Les courriels de confirmation de nouveau compte demandent aux utilisateurs de prouver
qu’ils sont bien titulaires de l’adresse de courriel indiquée lors de la création du compte.

Il s’agit d’une fonction de sécurité importante, la confirmation manuelle des nouveaux comptes n’est pas recommandée.';
$string['member_suspend_info'] = 'La suspension d’un compte a été conçue pour empêcher l’utilisateur de se connecter ;
elle doit aussi arrêter tous les courriels et notifications sortants, rien de plus.

Malheureusement, les utilisateurs et les développeurs comprennent souvent mal la suspension de compte, ce qui peut entraîner
un comportement incohérent de certains plugins et, dans de rares cas, une perte mineure de données.';
$string['member_unlock_info'] = 'Les comptes utilisateur sont bloqués après plusieurs tentatives de connexion infructueuses. Le déblocage du compte permet à l’utilisateur de réessayer.';
$string['member_unsuspend_info'] = 'Une fois le compte réactivé, l’utilisateur devrait pouvoir se connecter à nouveau
et il recommencera à recevoir des courriels et des notifications.';
$string['member_update'] = 'Modifier le compte';
$string['mutenancy:admin'] = 'Administrer les locataires';
$string['mutenancy:allocate'] = 'Affecter des utilisateurs à des locataires';
$string['mutenancy:configappearance'] = 'Configurer le thème et l’identité visuelle du locataire';
$string['mutenancy:configauth'] = 'Configurer l’authentification du locataire';
$string['mutenancy:membercreate'] = 'Créer le compte d’un membre du locataire';
$string['mutenancy:memberdelete'] = 'Supprimer le compte d’un membre du locataire';
$string['mutenancy:memberupdate'] = 'Modifier le compte d’un membre du locataire';
$string['mutenancy:switch'] = 'Basculer vers un locataire';
$string['mutenancy:view'] = 'Voir le locataire';
$string['navigation_category'] = 'Catégorie du locataire';
$string['navigation_top'] = 'Gestion du locataire';
$string['pluginname'] = 'Architecture multilocataire';
$string['privacy:metadata:tool_mutenancy_manager'] = 'Gestionnaires du locataire';
$string['privacy:metadata:tool_mutenancy_manager:tenantid'] = 'Identifiant du locataire';
$string['privacy:metadata:tool_mutenancy_manager:timecreated'] = 'Date de désignation du gestionnaire';
$string['privacy:metadata:tool_mutenancy_manager:usercreated'] = 'Utilisateur ayant désigné le gestionnaire';
$string['privacy:metadata:tool_mutenancy_manager:userid'] = 'Identifiant d’utilisateur du gestionnaire';
$string['role_tenantmanager_archetype'] = 'MODÈLE DE RÔLE : Gestionnaire du locataire';
$string['role_tenantmanager_description'] = 'Le rôle de gestionnaire du locataire est attribué automatiquement à tous les gestionnaires du locataire dans les contextes du locataire et de la catégorie du locataire.

Ce rôle n’est pas destiné à être attribué manuellement, ne modifiez pas le nom abrégé du rôle.';
$string['role_tenantmanager_name'] = 'Gestionnaire du locataire';
$string['role_tenantuser_archetype'] = 'MODÈLE DE RÔLE : Utilisateur du locataire';
$string['role_tenantuser_description'] = 'Le rôle d’utilisateur du locataire est attribué automatiquement à tous les utilisateurs du locataire dans les contextes de la catégorie du locataire.

Ce rôle n’est pas destiné à être attribué manuellement, ne modifiez pas le nom abrégé du rôle.';
$string['role_tenantuser_name'] = 'Utilisateur du locataire';
$string['secondary_tenant_appearance'] = 'Apparence';
$string['secondary_tenant_auth'] = 'Authentification';
$string['secondary_tenant_details'] = 'Détails';
$string['secondary_tenant_users'] = 'Utilisateurs';
$string['setting_allowguests'] = 'Autoriser l’accès anonyme aux locataires';
$string['setting_allowguests_desc'] = 'Activez ce réglage si vous souhaitez autoriser l’accès anonyme aux cours des locataires.

Notez que cette fonctionnalité nécessite que l’accès anonyme soit activé au niveau du site. En outre, l’accès anonyme doit être activé dans les cours des locataires, et les utilisateurs devraient être associés aux locataires plutôt que d’en être membres.';
$string['setting_tenantentities'] = 'Nom de l’entité locataire au pluriel';
$string['setting_tenantentities_desc'] = 'S’il est renseigné, ce nom personnalisé remplace le mot « Locataires » au pluriel. Par exemple : Unités, Facultés ou Événements';
$string['setting_tenantentity'] = 'Nom de l’entité locataire au singulier';
$string['setting_tenantentity_desc'] = 'S’il est renseigné, ce nom personnalisé remplace le mot « Locataire » au singulier. Par exemple : Unité, Faculté ou Événement';
$string['setting_tenantlimit'] = 'Limite de locataires';
$string['setting_tenantlimit_desc'] = 'Indique combien de locataires peuvent être créés. Notez que les sites comptant plus de 100 locataires peuvent rencontrer des problèmes de performance et d’ergonomie.';
$string['setting_tenantprimarynav'] = 'Ajouter la gestion du locataire au menu principal';
$string['setting_tenantprimarynav_desc'] = 'Si ce réglage est activé, la section de gestion du locataire actuel est ajoutée au menu principal. Il est destiné aux gestionnaires dédiés d’un locataire ou aux utilisateurs qui peuvent basculer vers des locataires.';
$string['settings'] = 'Paramètres de l’architecture multilocataire';
$string['taskcron'] = 'Tâche de nettoyage de l’architecture multilocataire';
$string['tenancy_activate'] = 'Activer l’architecture multilocataire';
$string['tenancy_activate_info'] = 'De nouveaux rôles pour les gestionnaires du locataire et les utilisateurs du locataire seront créés lors de l’activation de l’architecture multilocataire.

L’architecture multilocataire ne peut être désactivée qu’après la suppression de tous les locataires.';
$string['tenancy_deactivate'] = 'Désactiver l’architecture multilocataire';
$string['tenancy_deactivate_info'] = 'Le rôle de gestionnaire du locataire sera supprimé lors de la désactivation de l’architecture multilocataire.';
$string['tenant'] = 'Locataire';
$string['tenant_actions'] = 'Actions';
$string['tenant_archive'] = 'Archiver le locataire';
$string['tenant_archive_a'] = 'Archiver : {$a}';
$string['tenant_archive_info'] = 'L’archivage du locataire :

* empêche les membres du locataire de se connecter et arrête tous les courriels et notifications sortants,
* cache la catégorie du locataire.

L’archivage est une étape obligatoire avant la suppression d’un locataire.';
$string['tenant_archived'] = 'Archivé';
$string['tenant_associates'] = 'Utilisateurs associés';
$string['tenant_category'] = 'Catégorie du locataire';
$string['tenant_categoryidnumber'] = 'Numéro d’identification de la catégorie du locataire';
$string['tenant_categoryname'] = 'Nom de la catégorie du locataire';
$string['tenant_cohort'] = 'Cohorte du locataire';
$string['tenant_cohortidnumber'] = 'Identifiant de la cohorte du locataire';
$string['tenant_cohortname'] = 'Nom de la cohorte du locataire';
$string['tenant_create'] = 'Ajouter un locataire';
$string['tenant_create_a'] = 'Ajouter : {$a}';
$string['tenant_delete'] = 'Supprimer le locataire';
$string['tenant_delete_a'] = 'Supprimer : {$a}';
$string['tenant_delete_info'] = 'Lors de la suppression du locataire :

* les permissions des gestionnaires du locataire seront révoquées
* la cohorte du locataire sera convertie en cohorte ordinaire
* tous les membres du locataire seront affectés au site global

Les sous-catégories, les cours et les données des utilisateurs ne seront pas modifiés.';
$string['tenant_delete_movetotenant'] = 'Affecter les membres à';
$string['tenant_idnumber'] = 'Identifiant du locataire';
$string['tenant_loginshow'] = 'Afficher le locataire sur la page de connexion';
$string['tenant_loginurl'] = 'URL de connexion du locataire';
$string['tenant_loginurl_copy'] = 'Copier l’URL de connexion du locataire dans le presse-papiers';
$string['tenant_manager'] = 'Gestionnaire du locataire';
$string['tenant_managers'] = 'Gestionnaires du locataire';
$string['tenant_member'] = 'Membre du locataire';
$string['tenant_member_a'] = 'Membre : {$a}';
$string['tenant_memberlimit'] = 'Limite de membres du locataire';
$string['tenant_memberlimit_help'] = 'Indique le nombre maximal de comptes de membres pouvant être créés dans le locataire.

Notez que les utilisateurs associés ne sont pas pris en compte dans cette limite.';
$string['tenant_name'] = 'Nom du locataire';
$string['tenant_restore'] = 'Restaurer le locataire archivé';
$string['tenant_restore_a'] = 'Restaurer depuis l’archive : {$a}';
$string['tenant_restore_info'] = 'La restauration du locataire devrait annuler la plupart des modifications effectuées lors de son archivage.

Il est toutefois recommandé de vérifier ensuite tous les paramètres du locataire et la visibilité de la catégorie.';
$string['tenant_sitefullname'] = 'Nom du site du locataire';
$string['tenant_siteshortname'] = 'Nom abrégé du site du locataire';
$string['tenant_switch'] = 'Changer de locataire';
$string['tenant_switch_a'] = 'Changer : {$a}';
$string['tenant_switch_info'] = 'Le basculement vers un locataire modifie l’apparence du site et restreint la liste des utilisateurs candidats disponibles pour certaines actions.

Notez que basculer vers un locataire n’équivaut pas à en être membre, des restrictions supplémentaires s’appliquent aux membres du locataire.';
$string['tenant_switch_my'] = 'Mes locataires';
$string['tenant_switch_my_a'] = 'Mes {$a}';
$string['tenant_switch_notenant'] = 'Aucun locataire';
$string['tenant_switch_notenant_a'] = 'Sans {$a}';
$string['tenant_switch_other'] = 'Autres locataires';
$string['tenant_switch_other_a'] = 'Autres {$a}';
$string['tenant_update'] = 'Modifier le locataire';
$string['tenant_update_a'] = 'Modifier : {$a}';
$string['tenant_users'] = 'Utilisateurs du locataire';
$string['tenant_users_a'] = 'Utilisateurs : {$a}';
$string['tenants'] = 'Locataires';
$string['user_allocate'] = 'Affecter l’utilisateur';
$string['user_allocate_info'] = 'Lorsqu’un utilisateur est affecté à un locataire, ses attributions de gestionnaire du locataire sont retirées
et il perd l’accès à tous les autres locataires.';
$string['user_tenants'] = 'Associé aux locataires';
$string['user_tenants_a'] = 'Associé à : {$a}';
