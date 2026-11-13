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
 * Strings for component 'tool_mucertify', language 'fr'.
 *
 * NOTE: This French translation was produced automatically by an AI language
 * model and has not yet been reviewed by a human translator. Corrections are
 * welcome.
 *
 * @package    tool_mucertify
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


$string['allcertifications'] = 'Toutes les certifications';
$string['archived'] = 'Archivée';
$string['assignment'] = 'Assignation';
$string['assignment_archive'] = 'Archiver l’assignation';
$string['assignment_delete'] = 'Supprimer l’assignation';
$string['assignment_restore'] = 'Restaurer l’assignation';
$string['assignment_update'] = 'Modifier l’assignation';
$string['assignments'] = 'Assignations';
$string['assignmentsources'] = 'Sources d’assignation';
$string['catalogue'] = 'Catalogue des certifications';
$string['catalogue_actions'] = 'Actions du catalogue';
$string['catalogue_dofilter'] = 'Rechercher';
$string['catalogue_resetfilter'] = 'Réinitialiser';
$string['catalogue_searchtext'] = 'Texte recherché';
$string['catalogue_tag'] = 'Filtrer par tag';
$string['certificates'] = 'Certificats';
$string['certification'] = 'Certification';
$string['certification_actions'] = 'Actions de la certification';
$string['certification_archive'] = 'Archiver la certification';
$string['certification_archive_info'] = 'L’archivage d’une certification :

* archive les affectations aux programmes concernées,
* empêche les modifications de la certification,
* et, de manière générale, masque la certification aux utilisateurs ordinaires.

L’archivage est une étape obligatoire avant de pouvoir supprimer une certification.';
$string['certification_create'] = 'Ajouter une certification';
$string['certification_delete'] = 'Supprimer la certification';
$string['certification_delete_info'] = 'Lors de la suppression d’une certification, toutes ses données sont supprimées et les affectations des utilisateurs aux programmes sont retirées.';
$string['certification_move'] = 'Déplacer la certification';
$string['certification_restore'] = 'Restaurer la certification';
$string['certification_restore_info'] = 'La restauration d’une certification annule les changements effectués lors de son archivage.

Il est toutefois recommandé de vérifier ensuite tous les paramètres de la certification et les utilisateurs assignés.';
$string['certification_update'] = 'Modifier la certification';
$string['certificationidnumber'] = 'Identifiant de la certification';
$string['certificationimage'] = 'Image de la certification';
$string['certificationname'] = 'Nom de la certification';
$string['certifications'] = 'Certifications';
$string['certificationsactive'] = 'Actives';
$string['certificationsarchived'] = 'Archivées';
$string['certificationstatus'] = 'Statut de la certification';
$string['certificationstatus_any'] = 'Tous';
$string['certificationstatus_archived'] = 'Archivée';
$string['certificationstatus_certified'] = 'Certifié';
$string['certificationstatus_expired'] = 'Échue';
$string['certificationstatus_notcertified'] = 'Non certifié';
$string['certificationstatus_temporary'] = 'Temporairement valide';
$string['certificationstatus_valid'] = 'Valide';
$string['certificationurl'] = 'URL de la certification';
$string['certifieddate'] = 'Date d’achèvement de la certification';
$string['certifieduntiltemporary'] = 'Certification temporaire jusqu’au';
$string['cohorts'] = 'Visible pour les cohortes';
$string['cohorts_help'] = 'Les certifications non publiques peuvent être rendues visibles aux membres des cohortes indiquées.

Le statut de visibilité n’a aucune incidence sur les certifications déjà assignées.';
$string['columnusedalready'] = 'La colonne est déjà utilisée';
$string['currentcontextonly'] = 'Exclure les sous-catégories';
$string['customfields'] = 'Champs personnalisés des certifications';
$string['customfields_assignment'] = 'Champs personnalisés des assignations aux certifications';
$string['customfieldsettings'] = 'Paramètres communs des champs personnalisés des certifications';
$string['customfieldvisible:assigned'] = 'Utilisateurs assignés à la certification';
$string['customfieldvisible:assignee'] = 'Utilisateur assigné';
$string['customfieldvisible:everyone'] = 'Toute personne pouvant voir les autres détails de la certification';
$string['customfieldvisible:viewcapability'] = 'Utilisateurs ayant la capacité de voir les certifications';
$string['customfieldvisibleto'] = 'Le contenu du champ est visible pour';
$string['delayafter'] = '{$a->delay} après {$a->after}';
$string['delaybefore'] = '{$a->delay} avant {$a->before}';
$string['errornoassignment'] = 'La certification n’est pas assignée';
$string['errornoassignments'] = 'Aucune assignation à une certification trouvée.';
$string['errornocertifications'] = 'Aucune certification trouvée.';
$string['errornomycertifications'] = 'Aucune certification assignée trouvée.';
$string['errornorequests'] = 'Aucune demande de certification trouvée';
$string['event_assignment_archived'] = 'Assignation de l’utilisateur à la certification archivée';
$string['event_assignment_created'] = 'Utilisateur assigné à la certification';
$string['event_assignment_deleted'] = 'Utilisateur désassigné de la certification';
$string['event_assignment_restored'] = 'Assignation de l’utilisateur à la certification restaurée';
$string['event_assignment_updated'] = 'Assignation de l’utilisateur à la certification mise à jour';
$string['event_assignment_viewed'] = 'Assignation à la certification consultée';
$string['event_certification_archived'] = 'Certification archivée';
$string['event_certification_created'] = 'Certification créée';
$string['event_certification_deleted'] = 'Certification supprimée';
$string['event_certification_restored'] = 'Certification restaurée';
$string['event_certification_updated'] = 'Certification mise à jour';
$string['event_period_certified'] = 'Utilisateur certifié';
$string['event_period_created'] = 'Période de certification créée';
$string['event_period_deleted'] = 'Période de certification supprimée';
$string['event_period_updated'] = 'Période de certification mise à jour';
$string['evidence_default'] = 'Preuve par défaut';
$string['evidence_default_text'] = 'Dépôt de périodes de certification historiques';
$string['evidence_details'] = 'Détails de la preuve';
$string['evidence_details_help'] = 'Les détails de la preuve expliquent pourquoi la certification a été accordée ou révoquée.';
$string['expirationafter'] = 'Échéance après';
$string['fromdate'] = 'Valide à partir du';
$string['graceperiod'] = 'Délai de grâce';
$string['history_upload'] = 'Déposer l’historique';
$string['history_upload_assign'] = 'Créer de nouvelles assignations';
$string['history_upload_evidencecolumn'] = 'Colonne de la preuve';
$string['history_upload_result_assigned'] = 'Utilisateurs assignés à la certification : {$a}';
$string['history_upload_result_errors'] = 'Lignes non valides écartées : {$a}';
$string['history_upload_result_periods'] = 'Périodes de certification importées : {$a}';
$string['history_upload_result_skipped'] = 'Lignes ignorées : {$a}';
$string['history_upload_skipassigned'] = 'Ignorer les utilisateurs déjà assignés';
$string['history_upload_timecertifiedcolumn'] = 'Colonne de la date de certification';
$string['history_upload_timefromcolumn'] = 'Colonne du début de validité de la période';
$string['history_upload_timeuntilcolumn'] = 'Colonne de l’échéance de la période';
$string['management'] = 'Gestion des certifications';
$string['management_certification_general_actions'] = 'Actions de la certification';
$string['management_certification_users_actions'] = 'Actions sur les utilisateurs';
$string['messageprovider:approval_reject_notification'] = 'Notification de rejet d’une demande de certification';
$string['messageprovider:approval_request_notification'] = 'Notification de demande d’approbation de certification';
$string['messageprovider:assignment_notification'] = 'Notification d’assignation à une certification';
$string['messageprovider:cc_supervisor_notification'] = 'Copie des notifications de certification des subordonnés';
$string['messageprovider:unassignment_notification'] = 'Notification de désassignation d’une certification';
$string['messageprovider:valid_notification'] = 'Notification de validité de la certification';
$string['mucertify:admin'] = 'Administration avancée des certifications';
$string['mucertify:assign'] = 'Assigner des utilisateurs à des certifications';
$string['mucertify:configurecustomfields'] = 'Configurer les champs personnalisés des certifications';
$string['mucertify:delete'] = 'Supprimer des certifications';
$string['mucertify:edit'] = 'Ajouter et modifier des certifications';
$string['mucertify:unassign'] = 'Désassigner des utilisateurs de certifications';
$string['mucertify:view'] = 'Voir la gestion des certifications';
$string['mucertify:viewcatalogue'] = 'Accéder au catalogue des certifications';
$string['mucertify:viewusercertifications'] = 'Voir les certifications des autres utilisateurs';
$string['mycertifications'] = 'Mes certifications';
$string['never'] = 'Jamais';
$string['noexpiration'] = 'Sans échéance';
$string['notallocated'] = 'Non affecté';
$string['notification_assignment'] = 'Utilisateur assigné';
$string['notification_assignment_body'] = 'Bonjour {$a->user_fullname},

vous avez été assigné à la certification « {$a->certification_fullname} ».';
$string['notification_assignment_description'] = 'Notification envoyée aux utilisateurs lorsqu’ils sont assignés à une certification.';
$string['notification_assignment_subject'] = 'Notification d’assignation à une certification';
$string['notification_cc_supervisor_body'] = 'Bonjour {$a->supervisor_fullname},

une notification a été envoyée à l’utilisateur suivant :

* {$a->subordinate_title} : {$a->user_fullname}
* Type de notification : {$a->notification_name}
* Certification concernée : {$a->certification_fullname}

';
$string['notification_cc_supervisor_subject'] = 'Notification pour {$a->supervisor_title} - {$a->certification_fullname}';
$string['notification_unassignment'] = 'Utilisateur désassigné';
$string['notification_unassignment_body'] = 'Bonjour {$a->user_fullname},

vous avez été désassigné de la certification « {$a->certification_fullname} ».';
$string['notification_unassignment_description'] = 'Notification envoyée aux utilisateurs lorsqu’ils sont désassignés d’une certification.';
$string['notification_unassignment_subject'] = 'Notification de désassignation d’une certification';
$string['notification_valid'] = 'Certification valide';
$string['notification_valid_body'] = 'Bonjour {$a->user_fullname},

votre certification « {$a->certification_fullname} » est désormais valide :

* valide à partir du : {$a->period_fromdate}
* échéance le : {$a->period_untildate}
* ouverture de la recertification le : {$a->period_recertificationdate}
';
$string['notification_valid_description'] = 'Notification envoyée aux utilisateurs lorsque leur certification devient valide.';
$string['notification_valid_subject'] = 'Notification de certification valide';
$string['notificationdates'] = 'Notifications';
$string['notifications'] = 'Notifications des certifications';
$string['notset'] = 'Non défini';
$string['period'] = 'Période de certification';
$string['period_create'] = 'Ajouter une période';
$string['period_delete'] = 'Supprimer la période';
$string['period_update'] = 'Remplacer les dates de la période';
$string['periods'] = 'Périodes de certification';
$string['periodstatus'] = 'Statut';
$string['periodstatus_archived'] = 'Archivée';
$string['periodstatus_certified'] = 'Certifié';
$string['periodstatus_expired'] = 'Échue';
$string['periodstatus_failed'] = 'Échouée';
$string['periodstatus_future'] = 'Future';
$string['periodstatus_overdue'] = 'En retard';
$string['periodstatus_pending'] = 'En attente';
$string['periodstatus_revoked'] = 'Révoquée';
$string['pluginname'] = 'Certifications';
$string['pluginname_desc'] = 'Outil de certification et de recertification';
$string['privacy:metadata:field:archived'] = 'Indicateur d’archivage';
$string['privacy:metadata:field:assignmentid'] = 'Identifiant de l’assignation';
$string['privacy:metadata:field:certificationid'] = 'Identifiant de la certification';
$string['privacy:metadata:field:datajson'] = 'Données JSON';
$string['privacy:metadata:field:programid'] = 'Identifiant du programme';
$string['privacy:metadata:field:quantity'] = 'Quantité';
$string['privacy:metadata:field:rejectedby'] = 'Rejetée par';
$string['privacy:metadata:field:sourceid'] = 'Identifiant de la source';
$string['privacy:metadata:field:timecertified'] = 'Date de certification';
$string['privacy:metadata:field:timecertifiedtemp'] = 'Date de fin de la certification temporaire';
$string['privacy:metadata:field:timefrom'] = 'Date de début de la certification';
$string['privacy:metadata:field:timerejected'] = 'Date du rejet';
$string['privacy:metadata:field:timerequested'] = 'Date de la demande';
$string['privacy:metadata:field:timerevoked'] = 'Date de révocation de la certification';
$string['privacy:metadata:field:timeuntil'] = 'Date d’échéance de la certification';
$string['privacy:metadata:field:timewindowdue'] = 'Date limite dans la fenêtre de certification';
$string['privacy:metadata:field:timewindowend'] = 'Date de fermeture de la fenêtre de certification';
$string['privacy:metadata:field:timewindowstart'] = 'Date d’ouverture de la fenêtre de certification';
$string['privacy:metadata:field:userid'] = 'Identifiant de l’utilisateur';
$string['privacy:metadata:table:tool_mucertify_assignment'] = 'Table des assignations des utilisateurs';
$string['privacy:metadata:table:tool_mucertify_period'] = 'Table des périodes de certification';
$string['privacy:metadata:table:tool_mucertify_request'] = 'Table des demandes de certification';
$string['program1'] = 'Programme de certification';
$string['program2'] = 'Programme de recertification';
$string['publicaccess'] = 'Publique';
$string['publicaccess_help'] = 'Les certifications publiques sont visibles par tous les utilisateurs.

Le statut de visibilité n’a aucune incidence sur les certifications déjà assignées.';
$string['purchaseaccess'] = 'Acheter l’accès';
$string['recertification'] = 'Recertification';
$string['recertifications'] = 'Recertifications';
$string['recertify'] = 'Recertifier automatiquement';
$string['recertifybefore'] = 'Recertifier avant l’échéance';
$string['recertifyifexpired'] = 'Si échue';
$string['resettype1'] = 'Réinitialisation du programme de certification';
$string['resettype2'] = 'Réinitialisation du programme de recertification';
$string['revokeddate'] = 'Date de révocation';
$string['selectcategory'] = 'Sélectionner une catégorie';
$string['settings'] = 'Paramètres de la certification';
$string['source'] = 'Source';
$string['source_approval'] = 'Demandes avec approbation';
$string['source_approval_allownew'] = 'Autoriser les approbations';
$string['source_approval_allownew_desc'] = 'Autoriser l’ajout de nouvelles sources _demandes avec approbation_ aux certifications';
$string['source_approval_allowrequest'] = 'Autoriser les nouvelles demandes';
$string['source_approval_confirm'] = 'Veuillez confirmer que vous souhaitez demander l’assignation à la certification.';
$string['source_approval_daterejected'] = 'Date du rejet';
$string['source_approval_daterequested'] = 'Date de la demande';
$string['source_approval_makerequest'] = 'Demander l’accès';
$string['source_approval_notification_approval_reject_body'] = 'Bonjour {$a->user_fullname},

votre demande d’accès à la certification « {$a->certification_fullname} » a été rejetée.

{$a->reason}
';
$string['source_approval_notification_approval_reject_subject'] = 'Notification de rejet d’une demande de certification';
$string['source_approval_notification_approval_request_body'] = '
L’utilisateur {$a->user_fullname} a demandé l’accès à la certification « {$a->certification_fullname} ».
';
$string['source_approval_notification_approval_request_subject'] = 'Notification de demande de certification';
$string['source_approval_rejectionreason'] = 'Motif du rejet';
$string['source_approval_request'] = 'Demande';
$string['source_approval_requestallowed'] = 'Les demandes sont autorisées';
$string['source_approval_requestapprove'] = 'Approuver la demande';
$string['source_approval_requestdelete'] = 'Supprimer la demande';
$string['source_approval_requestnotallowed'] = 'Les demandes ne sont pas autorisées';
$string['source_approval_requestpending'] = 'Demande d’accès en attente';
$string['source_approval_requestreject'] = 'Rejeter la demande';
$string['source_approval_requestrejected'] = 'La demande d’accès a été rejetée';
$string['source_approval_requests'] = 'Demandes';
$string['source_cohort'] = 'Assignation automatique par cohorte';
$string['source_cohort_allownew'] = 'Autoriser l’assignation par cohorte';
$string['source_cohort_allownew_desc'] = 'Autoriser l’ajout de nouvelles sources _assignation automatique par cohorte_ aux certifications';
$string['source_cohort_cohortstoassign'] = 'Assigner des cohortes';
$string['source_manual'] = 'Assignation manuelle';
$string['source_manual_assignusers'] = 'Assigner des utilisateurs';
$string['source_manual_hasheaders'] = 'La première ligne contient les noms des colonnes';
$string['source_manual_result_assigned'] = 'Nombre d’utilisateurs assignés à la certification : {$a}';
$string['source_manual_result_errors'] = 'Nombre d’erreurs lors de l’assignation à la certification : {$a}';
$string['source_manual_result_skipped'] = 'Nombre d’utilisateurs déjà assignés à la certification : {$a}';
$string['source_manual_timecertifiedtempcolumn'] = 'Colonne de la fin de la certification temporaire';
$string['source_manual_timeduecolumn'] = 'Colonne de la date limite de certification';
$string['source_manual_timeendcolumn'] = 'Colonne de la fermeture de la fenêtre';
$string['source_manual_timestartcolumn'] = 'Colonne de l’ouverture de la fenêtre';
$string['source_manual_timeuntilcolumn'] = 'Colonne de l’échéance';
$string['source_manual_uploadusers'] = 'Déposer des assignations';
$string['source_manual_usercolumn'] = 'Colonne d’identification de l’utilisateur';
$string['source_manual_usermapping'] = 'Identifier les utilisateurs par';
$string['source_selfassignment'] = 'Auto-assignation';
$string['source_selfassignment_allownew'] = 'Autoriser l’auto-assignation';
$string['source_selfassignment_allownew_desc'] = 'Autoriser l’ajout de nouvelles sources _auto-assignation_ aux certifications';
$string['source_selfassignment_allowsignup'] = 'Autoriser les nouvelles adhésions';
$string['source_selfassignment_assign'] = 'Adhérer';
$string['source_selfassignment_confirm'] = 'Veuillez confirmer que vous souhaitez être assigné à la certification.';
$string['source_selfassignment_enable'] = 'Activer l’auto-assignation';
$string['source_selfassignment_key'] = 'Clef d’accès';
$string['source_selfassignment_keyrequired'] = 'La clef d’accès est requise';
$string['source_selfassignment_maxusers'] = 'Nombre maximal d’utilisateurs';
$string['source_selfassignment_maxusers_status'] = 'Utilisateurs : {$a->count}/{$a->max}';
$string['source_selfassignment_maxusersreached'] = 'Le nombre maximal d’utilisateurs auto-assignés est déjà atteint';
$string['source_selfassignment_signupallowed'] = 'Les adhésions sont autorisées';
$string['source_selfassignment_signupnotallowed'] = 'Les adhésions ne sont pas autorisées';
$string['stoprecertify'] = 'Recertification arrêtée';
$string['tabassignment'] = 'Paramètres d’assignation';
$string['tabgeneral'] = 'Général';
$string['tabsettings'] = 'Paramètres des périodes';
$string['tabusers'] = 'Utilisateurs';
$string['tabvisibility'] = 'Visibilité dans le catalogue';
$string['tagarea_tool_mucertify_certification'] = 'Certifications';
$string['taskcron'] = 'Tâche cron des certifications';
$string['tasktriggercertificate'] = 'Déclencher dès que possible le cron de délivrance des certificats';
$string['untildate'] = 'Échéance';
$string['updateassignments'] = 'Modifier les paramètres d’assignation';
$string['updatecertificatetemplate'] = 'Modifier le modèle de certificat';
$string['updaterecertification'] = 'Modifier la recertification';
$string['updatesource'] = 'Modifier : {$a}';
$string['upload_csvfile'] = 'Fichier CSV';
$string['validfrom'] = 'Valide à partir du';
$string['windowdueafter'] = 'Date limite après';
$string['windowduedate'] = 'Date limite de certification';
$string['windowendafter'] = 'Fermeture de la fenêtre après';
$string['windowenddate'] = 'Fermeture de la fenêtre';
$string['windowstartdate'] = 'Ouverture de la fenêtre';
