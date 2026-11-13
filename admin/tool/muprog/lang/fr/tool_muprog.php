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
 * Strings for component 'tool_muprog', language 'fr'.
 *
 * NOTE: This French translation was produced automatically by an AI language
 * model and has not yet been reviewed by a human translator. Corrections are
 * welcome.
 *
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['allocation'] = 'Affectation';
$string['allocation_archive'] = 'Archiver l’affectation';
$string['allocation_reset'] = 'Réinitialiser la progression dans le programme';
$string['allocation_reset_updateallocation'] = 'Modifier l’affectation';
$string['allocation_restore'] = 'Restaurer l’affectation';
$string['allocation_update'] = 'Modifier l’affectation';
$string['allocationdate'] = 'Date d’affectation';
$string['allocationend'] = 'Fin des affectations';
$string['allocationend_help'] = 'La signification de la date de fin des affectations dépend des sources d’affectation activées. En général, si elle est indiquée, aucune nouvelle affectation n’est possible après cette date.';
$string['allocations'] = 'Affectations';
$string['allocationsources'] = 'Sources d’affectation';
$string['allocationstart'] = 'Début des affectations';
$string['allocationstart_help'] = 'La signification de la date de début des affectations dépend des sources d’affectation activées. En général, si elle est indiquée, les nouvelles affectations ne sont possibles qu’après cette date.';
$string['allprograms'] = 'Tous les programmes';
$string['appendinto'] = 'Ajouter dans l’élément';
$string['appenditem'] = 'Ajouter un élément';
$string['archived'] = 'Archivé';
$string['attendance'] = 'Présence hors ligne';
$string['attendance_effective'] = 'Date d’effet';
$string['attendance_status'] = 'Statut';
$string['attendance_status_completed'] = 'Terminé';
$string['attendance_status_failed'] = 'Échoué';
$string['attendance_status_noshow'] = 'Absent';
$string['attendance_take'] = 'Saisir les présences';
$string['calendarprogramdue'] = 'Date limite de « {$a} »';
$string['calendarprogramend'] = 'Fin de « {$a} »';
$string['calendarprogramstart'] = 'Début de « {$a} »';
$string['catalogue'] = 'Catalogue des programmes';
$string['catalogue_actions'] = 'Actions du catalogue';
$string['catalogue_dofilter'] = 'Rechercher';
$string['catalogue_resetfilter'] = 'Réinitialiser';
$string['catalogue_searchtext'] = 'Texte recherché';
$string['catalogue_tag'] = 'Filtrer par tag';
$string['certificatetemplatechoose'] = 'Choisir un modèle…';
$string['cohorts'] = 'Visible pour les cohortes';
$string['cohorts_help'] = 'Les programmes non publics peuvent être rendus visibles aux membres des cohortes indiquées.

Le statut de visibilité n’a aucune incidence sur les programmes déjà affectés.';
$string['columnusedalready'] = 'La colonne est déjà utilisée';
$string['completiondate'] = 'Date d’achèvement';
$string['completiondelay'] = 'Délai d’achèvement';
$string['completionoverride'] = 'Outrepasser l’achèvement';
$string['creategroups'] = 'Groupes de cours';
$string['creategroups_help'] = 'Si ce réglage est activé, un groupe sera créé dans chaque cours ajouté au programme et tous les utilisateurs affectés seront ajoutés comme membres de ce groupe.';
$string['credits'] = 'Crédits';
$string['credits_progress'] = 'Crédits actuels : {$a->current}/{$a->total}';
$string['credits_requiredcredits'] = 'Crédits requis : {$a}';
$string['currentcontextonly'] = 'Exclure les sous-catégories';
$string['customfields'] = 'Champs personnalisés de programme';
$string['customfields_allocation'] = 'Champs personnalisés d’affectation à un programme';
$string['customfieldsettings'] = 'Réglages communs des champs personnalisés de programme';
$string['customfieldvisible:allocated'] = 'Utilisateurs affectés à des programmes';
$string['customfieldvisible:allocatee'] = 'Utilisateur affecté';
$string['customfieldvisible:everyone'] = 'Toute personne pouvant voir les autres détails du programme';
$string['customfieldvisible:viewcapability'] = 'Utilisateurs disposant de la capacité de consulter les programmes';
$string['customfieldvisibleto'] = 'Le contenu du champ est visible pour';
$string['deleteallocation'] = 'Supprimer l’affectation au programme';
$string['deleteattendance'] = 'Supprimer la présence hors ligne';
$string['deletecourse'] = 'Retirer le cours';
$string['deletecredits'] = 'Retirer les crédits';
$string['deleteset'] = 'Supprimer l’ensemble';
$string['duedate'] = 'Date limite';
$string['enrolrole'] = 'Rôle dans les cours';
$string['enrolrole_desc'] = 'Sélectionnez le rôle que les programmes utiliseront pour l’inscription aux cours';
$string['errorcontentproblem'] = 'Un problème a été détecté dans la structure du contenu du programme, l’achèvement du programme ne sera pas suivi correctement !';
$string['errorcoursemissing'] = 'Le cours est manquant';
$string['errorcoursesmissing'] = 'Cours manquants : {$a}';
$string['errordifferenttenant'] = 'Impossible d’accéder à un programme d’un autre locataire';
$string['errorinvalidoverridedates'] = 'Dates outrepassées non valides';
$string['errornoallocation'] = 'Le programme n’est pas affecté';
$string['errornoallocations'] = 'Aucune affectation d’utilisateur trouvée';
$string['errornomyprograms'] = 'Vous n’êtes affecté à aucun programme.';
$string['errornoprograms'] = 'Aucun programme trouvé.';
$string['errornorequests'] = 'Aucune demande relative au programme trouvée';
$string['errornotenabled'] = 'Le plugin Programmes n’est pas activé';
$string['event_allocation_archived'] = 'Affectation de l’utilisateur archivée';
$string['event_allocation_completed'] = 'Programme terminé par l’utilisateur';
$string['event_allocation_created'] = 'Utilisateur affecté au programme';
$string['event_allocation_deleted'] = 'Utilisateur désaffecté du programme';
$string['event_allocation_restored'] = 'Affectation de l’utilisateur restaurée';
$string['event_allocation_updated'] = 'Affectation de l’utilisateur au programme mise à jour';
$string['event_allocation_viewed'] = 'Affectation au programme consultée';
$string['event_catalogue_program_viewed'] = 'Catalogue des programmes consulté';
$string['event_program_archived'] = 'Programme archivé';
$string['event_program_created'] = 'Programme créé';
$string['event_program_deleted'] = 'Programme supprimé';
$string['event_program_restored'] = 'Programme restauré';
$string['event_program_updated'] = 'Programme mis à jour';
$string['event_program_viewed'] = 'Programme consulté';
$string['evidence'] = 'Autre preuve';
$string['evidence_details'] = 'Détails';
$string['evidence_detailsdefault'] = 'Détails par défaut';
$string['evidencedate'] = 'Date d’achèvement selon la preuve';
$string['evidenceupdate'] = 'Modifier l’autre preuve';
$string['evidenceupload'] = 'Déposer des preuves d’achèvement';
$string['evidenceupload_csvfile'] = 'Fichier CSV';
$string['evidenceupload_errors'] = 'Nombre de lignes non valides : {$a}';
$string['evidenceupload_skipped'] = 'Nombre de lignes ignorées : {$a}';
$string['evidenceupload_updated'] = 'Preuves d’achèvement mises à jour. Nombre d’utilisateurs : {$a}';
$string['export'] = 'Exporter des programmes';
$string['exportfile_info'] = 'info';
$string['exportfile_programs'] = 'programmes';
$string['exportformat'] = 'Format de fichier';
$string['exportformat_csv'] = 'CSV';
$string['exportformat_json'] = 'JSON';
$string['extdb_query_allocation'] = 'Affectation à un programme';
$string['favourite'] = 'Programme favori';
$string['fixeddate'] = 'À une date fixe';
$string['importallocationend'] = 'Fin des affectations ({$a})';
$string['importallocationstart'] = 'Début des affectations ({$a})';
$string['importprogramallocation'] = 'Importer les réglages d’affectation';
$string['importprogramallocationconfirmation'] = 'Vous importez les réglages d’affectation du programme __{$a->fullname} / {$a->idnumber} / {$a->category}__.

Veuillez sélectionner tous les réglages que vous souhaitez importer.';
$string['importprogramcontent'] = 'Importer le contenu d’un programme';
$string['importprogramcontentconfirmation'] = 'Vous importez le contenu du programme __{$a->fullname} / {$a->idnumber} / {$a->category}__.';
$string['importprogramdue'] = 'Date limite du programme ({$a})';
$string['importprogramend'] = 'Fin du programme ({$a})';
$string['importprogramstart'] = 'Début du programme ({$a})';
$string['importselectprogram'] = 'Sélectionner un programme';
$string['includesubcontexts'] = 'Inclure les sous-contextes';
$string['invalidallocationdates'] = 'Dates d’affectation au programme non valides';
$string['invalidcompletiondate'] = 'Date d’achèvement du programme non valide';
$string['item'] = 'Élément';
$string['item_create_attendance'] = 'Ajouter une présence hors ligne';
$string['item_create_course'] = 'Ajouter des cours';
$string['item_create_credits'] = 'Ajouter des crédits';
$string['item_create_set'] = 'Ajouter un ensemble';
$string['item_type'] = 'Type d’élément';
$string['item_update_attendance'] = 'Modifier la présence hors ligne';
$string['itemcompletion'] = 'Achèvement de l’élément du programme';
$string['itempoints'] = 'Points';
$string['itemrecalculate'] = 'Recalculer l’achèvement de l’élément';
$string['management'] = 'Gestion des programmes';
$string['management_index_actions'] = 'Actions des programmes';
$string['management_program_allocation_actions'] = 'Actions des réglages d’affectation';
$string['management_program_general_actions'] = 'Actions du programme';
$string['management_program_users_actions'] = 'Actions des utilisateurs';
$string['messageprovider:allocation_notification'] = 'Notification d’affectation à un programme';
$string['messageprovider:approval_reject_notification'] = 'Notification de rejet d’une demande relative à un programme';
$string['messageprovider:approval_request_notification'] = 'Notification de demande d’approbation pour un programme';
$string['messageprovider:cc_supervisor_notification'] = 'Copie des notifications de programme des subordonnés';
$string['messageprovider:completion_notification'] = 'Notification de programme terminé';
$string['messageprovider:deallocation_notification'] = 'Notification de désaffectation d’un programme';
$string['messageprovider:due_notification'] = 'Notification de programme en retard';
$string['messageprovider:duesoon_notification'] = 'Notification de date limite du programme proche';
$string['messageprovider:endcompleted_notification'] = 'Notification de fin d’un programme terminé';
$string['messageprovider:endfailed_notification'] = 'Notification de fin d’un programme échoué';
$string['messageprovider:endsoon_notification'] = 'Notification de date de fin du programme proche';
$string['messageprovider:reset_notification'] = 'Notification de réinitialisation du programme';
$string['messageprovider:start_notification'] = 'Notification de début du programme';
$string['moveafter'] = 'Déplacer « {$a->item} » après « {$a->target} »';
$string['movebefore'] = 'Déplacer « {$a->item} » avant « {$a->target} »';
$string['moveinto'] = 'Déplacer « {$a->item} » dans « {$a->target} »';
$string['moveitem'] = 'Déplacer l’élément';
$string['moveitemcancel'] = 'Annuler le déplacement';
$string['muprog:addcourse'] = 'Ajouter un cours à des programmes';
$string['muprog:addtocertifications'] = 'Ajouter un programme à des certifications';
$string['muprog:admin'] = 'Administration avancée des programmes';
$string['muprog:allocate'] = 'Affecter des utilisateurs à des programmes';
$string['muprog:clone'] = 'Cloner le contenu et les réglages d’un programme';
$string['muprog:configurecustomfields'] = 'Configurer les champs personnalisés de programme';
$string['muprog:deallocate'] = 'Désaffecter des utilisateurs de programmes';
$string['muprog:delete'] = 'Supprimer des programmes';
$string['muprog:edit'] = 'Ajouter et modifier des programmes';
$string['muprog:export'] = 'Exporter des programmes';
$string['muprog:manageallocation'] = 'Gérer les affectations des utilisateurs';
$string['muprog:manageevidence'] = 'Gérer les autres preuves d’achèvement';
$string['muprog:reset'] = 'Réinitialiser la progression dans un programme';
$string['muprog:takeattendance'] = 'Saisir les présences hors ligne';
$string['muprog:upload'] = 'Déposer des programmes';
$string['muprog:view'] = 'Consulter la gestion des programmes';
$string['muprog:viewcatalogue'] = 'Accéder au catalogue des programmes';
$string['muprog:viewuserprograms'] = 'Consulter les programmes des autres utilisateurs';
$string['myprograms'] = 'Mes programmes';
$string['notification_allocation'] = 'Utilisateur affecté';
$string['notification_allocation_body'] = 'Bonjour {$a->user_fullname},

vous avez été affecté au programme « {$a->program_fullname} », la date de début est le {$a->program_startdate}.
';
$string['notification_allocation_description'] = 'Notification envoyée aux utilisateurs lorsqu’ils sont affectés à un programme.';
$string['notification_allocation_subject'] = 'Notification d’affectation à un programme';
$string['notification_cc_supervisor_body'] = 'Bonjour {$a->supervisor_fullname},

une notification a été envoyée à l’utilisateur suivant :

* {$a->subordinate_title} : {$a->user_fullname}
* Type de notification : {$a->notification_name}
* Programme concerné : {$a->program_fullname}

';
$string['notification_cc_supervisor_subject'] = '{$a->supervisor_title} : notification - {$a->program_fullname}';
$string['notification_completion'] = 'Programme terminé';
$string['notification_completion_body'] = 'Bonjour {$a->user_fullname},

vous avez terminé le programme « {$a->program_fullname} ».
';
$string['notification_completion_description'] = 'Notification envoyée aux utilisateurs lorsqu’ils terminent leur programme.';
$string['notification_completion_subject'] = 'Programme terminé';
$string['notification_deallocation'] = 'Utilisateur désaffecté';
$string['notification_deallocation_body'] = 'Bonjour {$a->user_fullname},

vous avez été désaffecté du programme « {$a->program_fullname} ».
';
$string['notification_deallocation_description'] = 'Notification envoyée aux utilisateurs lorsqu’ils sont désaffectés d’un programme.';
$string['notification_deallocation_subject'] = 'Notification de désaffectation d’un programme';
$string['notification_due'] = 'Programme en retard';
$string['notification_due_body'] = 'Bonjour {$a->user_fullname},

l’achèvement du programme « {$a->program_fullname} » était attendu avant le {$a->program_duedate}.
';
$string['notification_due_description'] = 'Notification envoyée aux utilisateurs lorsque l’achèvement de leur programme est en retard.';
$string['notification_due_subject'] = 'L’achèvement du programme est en retard';
$string['notification_duesoon'] = 'Date limite du programme proche';
$string['notification_duesoon_body'] = 'Bonjour {$a->user_fullname},

l’achèvement du programme « {$a->program_fullname} » est attendu le {$a->program_duedate}.
';
$string['notification_duesoon_description'] = 'Notification envoyée aux utilisateurs avant la date limite de leur programme, sauf si le programme est déjà terminé.';
$string['notification_duesoon_subject'] = 'L’achèvement du programme est attendu prochainement';
$string['notification_endcompleted'] = 'Fin d’un programme terminé';
$string['notification_endcompleted_body'] = 'Bonjour {$a->user_fullname},

le programme « {$a->program_fullname} » a pris fin, vous l’aviez déjà terminé.
';
$string['notification_endcompleted_description'] = 'Notification envoyée aux utilisateurs lorsqu’un programme qu’ils ont terminé prend fin.';
$string['notification_endcompleted_subject'] = 'Fin d’un programme terminé';
$string['notification_endfailed'] = 'Fin d’un programme échoué';
$string['notification_endfailed_body'] = 'Bonjour {$a->user_fullname},

le programme « {$a->program_fullname} » a pris fin, vous ne l’avez pas terminé.
';
$string['notification_endfailed_description'] = 'Notification envoyée aux utilisateurs lorsqu’un programme qu’ils n’ont pas terminé prend fin.';
$string['notification_endfailed_subject'] = 'Fin d’un programme échoué';
$string['notification_endsoon'] = 'Date de fin du programme proche';
$string['notification_endsoon_body'] = 'Bonjour {$a->user_fullname},

le programme « {$a->program_fullname} » prendra fin le {$a->program_enddate}.
';
$string['notification_endsoon_description'] = 'Notification envoyée aux utilisateurs avant la date de fin de leur programme, sauf si le programme est déjà terminé.';
$string['notification_endsoon_subject'] = 'Le programme prend bientôt fin';
$string['notification_reset'] = 'Progression de l’utilisateur réinitialisée';
$string['notification_reset_body'] = 'Bonjour {$a->user_fullname},

votre progression dans le programme « {$a->program_fullname} » a été réinitialisée.
';
$string['notification_reset_description'] = 'Notification envoyée aux utilisateurs lorsque leur progression dans le programme est réinitialisée.';
$string['notification_reset_subject'] = 'Notification de réinitialisation du programme';
$string['notification_start'] = 'Programme commencé';
$string['notification_start_body'] = 'Bonjour {$a->user_fullname},

le programme « {$a->program_fullname} » a commencé.
';
$string['notification_start_description'] = 'Notification envoyée aux utilisateurs lorsque leur programme a commencé.';
$string['notification_start_subject'] = 'Programme commencé';
$string['notificationdates'] = 'Dates des notifications';
$string['notset'] = 'Non défini';
$string['plugindisabled'] = 'Le plugin Inscription par programme est désactivé, les programmes ne fonctionneront pas.

[Activer le plugin maintenant]({$a->url})';
$string['pluginname'] = 'Programmes';
$string['pluginname_desc'] = 'Les programmes sont conçus pour permettre la création d’ensembles de cours.';
$string['privacy:metadata:field:allocationid'] = 'Identifiant de l’affectation au programme';
$string['privacy:metadata:field:archived'] = 'Indique si l’enregistrement est archivé';
$string['privacy:metadata:field:createdby'] = 'Auteur de la preuve';
$string['privacy:metadata:field:datajson'] = 'Informations sur la demande';
$string['privacy:metadata:field:evidencejson'] = 'Informations sur la preuve d’achèvement';
$string['privacy:metadata:field:explanation'] = 'Explication';
$string['privacy:metadata:field:issueid'] = 'Identifiant de la délivrance';
$string['privacy:metadata:field:itemid'] = 'Identifiant de l’élément';
$string['privacy:metadata:field:programid'] = 'Identifiant du programme';
$string['privacy:metadata:field:quantity'] = 'Quantité';
$string['privacy:metadata:field:reason'] = 'Motif';
$string['privacy:metadata:field:rejectedby'] = 'Auteur du rejet de la demande';
$string['privacy:metadata:field:sourcedatajson'] = 'Informations sur la source de l’affectation';
$string['privacy:metadata:field:sourceid'] = 'Source de l’affectation';
$string['privacy:metadata:field:status'] = 'Statut';
$string['privacy:metadata:field:takenby'] = 'Auteur de la saisie de la présence';
$string['privacy:metadata:field:timeallocated'] = 'Date d’affectation au programme';
$string['privacy:metadata:field:timecompleted'] = 'Date d’achèvement';
$string['privacy:metadata:field:timecreated'] = 'Date de création';
$string['privacy:metadata:field:timedue'] = 'Date limite';
$string['privacy:metadata:field:timeeffective'] = 'Date d’effet';
$string['privacy:metadata:field:timeend'] = 'Date de fin';
$string['privacy:metadata:field:timerejected'] = 'Date du rejet';
$string['privacy:metadata:field:timerequested'] = 'Date de la demande';
$string['privacy:metadata:field:timestart'] = 'Date de début';
$string['privacy:metadata:field:userid'] = 'Identifiant de l’utilisateur';
$string['privacy:metadata:table:tool_muprog_allocation'] = 'Informations sur les affectations aux programmes';
$string['privacy:metadata:table:tool_muprog_attendance'] = 'Présence hors ligne';
$string['privacy:metadata:table:tool_muprog_cert_issue'] = 'Certificats délivrés pour les affectations aux programmes';
$string['privacy:metadata:table:tool_muprog_completion'] = 'Achèvements des affectations aux programmes';
$string['privacy:metadata:table:tool_muprog_evidence'] = 'Informations sur les autres preuves d’achèvement';
$string['privacy:metadata:table:tool_muprog_request'] = 'Informations sur les demandes d’affectation';
$string['program'] = 'Programme';
$string['program_actions'] = 'Actions du programme';
$string['program_allocations_edit'] = 'Modifier les affectations';
$string['program_archive'] = 'Archiver le programme';
$string['program_archive_info'] = 'L’archivage d’un programme :

* suspend les inscriptions aux cours du programme,
* supprime du calendrier les événements du programme,
* empêche les modifications du programme,
* et, de manière générale, masque le programme aux utilisateurs ordinaires.

L’archivage est une étape obligatoire avant de pouvoir supprimer un programme.';
$string['program_create'] = 'Ajouter un programme';
$string['program_delete'] = 'Supprimer le programme';
$string['program_delete_info'] = 'Lors de la suppression d’un programme, toutes les données du programme sont supprimées et les utilisateurs sont désinscrits des cours du programme.';
$string['program_move'] = 'Déplacer le programme';
$string['program_restore'] = 'Restaurer le programme';
$string['program_restore_info'] = 'La restauration d’un programme annule les modifications effectuées lors de son archivage.

Il est toutefois recommandé de vérifier ensuite tous les réglages du programme et les utilisateurs affectés.';
$string['program_update'] = 'Modifier le programme';
$string['programallocations'] = 'Affectations au programme';
$string['programautofix'] = 'Réparer automatiquement le programme';
$string['programcategory'] = 'Catégorie du programme';
$string['programcompletion'] = 'Date d’achèvement du programme';
$string['programcompletionoverride'] = 'Outrepasser l’achèvement du programme';
$string['programdue'] = 'Date limite du programme';
$string['programdue_date'] = 'Date limite';
$string['programdue_delay'] = 'Date limite après le début';
$string['programdue_help'] = 'La date limite du programme indique quand les utilisateurs sont censés avoir terminé le programme.';
$string['programend'] = 'Fin du programme';
$string['programend_date'] = 'Date de fin du programme';
$string['programend_delay'] = 'Fin après le début';
$string['programend_help'] = 'Les utilisateurs ne peuvent plus entrer dans les cours du programme après la fin du programme.';
$string['programidnumber'] = 'N° d’identification du programme';
$string['programimage'] = 'Image du programme';
$string['programname'] = 'Nom du programme';
$string['programprogress'] = 'Progression';
$string['programs'] = 'Programmes';
$string['programstart'] = 'Début du programme';
$string['programstart_allocation'] = 'Commencer immédiatement après l’affectation';
$string['programstart_date'] = 'Date de début du programme';
$string['programstart_delay'] = 'Différer le début après l’affectation';
$string['programstart_help'] = 'Les utilisateurs ne peuvent pas entrer dans les cours du programme avant le début du programme.';
$string['programstatus'] = 'Statut du programme';
$string['programstatus_any'] = 'Tout statut de programme';
$string['programstatus_archived'] = 'Archivé';
$string['programstatus_archivedcompleted'] = 'Archivé terminé';
$string['programstatus_completed'] = 'Terminé';
$string['programstatus_failed'] = 'Échoué';
$string['programstatus_future'] = 'Pas encore ouvert';
$string['programstatus_open'] = 'Ouvert';
$string['programstatus_overdue'] = 'En retard';
$string['programurl'] = 'URL du programme';
$string['publicaccess'] = 'Public';
$string['publicaccess_help'] = 'Les programmes publics sont visibles par tous les utilisateurs.

Le statut de visibilité n’a aucune incidence sur les programmes déjà affectés.';
$string['purchaseaccess'] = 'Acheter l’accès';
$string['resettype'] = 'Type de réinitialisation';
$string['resettype_deallocate'] = 'Désaffectation du programme uniquement';
$string['resettype_full'] = 'Purge complète des cours';
$string['resettype_none'] = 'Aucun';
$string['resettype_standard'] = 'Purge standard des cours';
$string['scheduling'] = 'Planification';
$string['sequencetype'] = 'Type d’achèvement';
$string['sequencetype_allinanyorder'] = 'Tous dans n’importe quel ordre';
$string['sequencetype_allinorder'] = 'Tous dans l’ordre';
$string['sequencetype_atleast'] = 'Au moins {$a->min}';
$string['sequencetype_minpoints'] = 'Minimum de points : {$a->minpoints}';
$string['set'] = 'Ensemble';
$string['settings'] = 'Réglages des programmes';
$string['source'] = 'Source';
$string['source_approval'] = 'Demandes avec approbation';
$string['source_approval_allownew'] = 'Autoriser les approbations';
$string['source_approval_allownew_desc'] = 'Autoriser l’ajout de nouvelles sources _demandes avec approbation_ aux programmes';
$string['source_approval_allowrequest'] = 'Autoriser les nouvelles demandes';
$string['source_approval_confirm'] = 'Veuillez confirmer que vous souhaitez demander votre affectation au programme.';
$string['source_approval_daterejected'] = 'Date du rejet';
$string['source_approval_daterequested'] = 'Date de la demande';
$string['source_approval_makerequest'] = 'Demander l’accès';
$string['source_approval_notification_approval_reject_body'] = 'Bonjour {$a->user_fullname},

votre demande d’accès au programme « {$a->program_fullname} » a été rejetée.

{$a->reason}
';
$string['source_approval_notification_approval_reject_subject'] = 'Notification de rejet d’une demande relative à un programme';
$string['source_approval_notification_approval_request_body'] = '
L’utilisateur {$a->user_fullname} a demandé l’accès au programme « {$a->program_fullname} ».
';
$string['source_approval_notification_approval_request_subject'] = 'Notification de demande relative à un programme';
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
$string['source_cohort'] = 'Affectation automatique par cohorte';
$string['source_cohort_allownew'] = 'Autoriser l’affectation par cohorte';
$string['source_cohort_allownew_desc'] = 'Autoriser l’ajout de nouvelles sources _affectation automatique par cohorte_ aux programmes';
$string['source_cohort_cohortstoallocate'] = 'Cohortes à affecter';
$string['source_extdb'] = 'Affectation par base de données externe';
$string['source_extdb_allownew'] = 'Autoriser la synchronisation avec une base de données externe';
$string['source_extdb_allownew_desc'] = 'La synchronisation avec une base de données externe nécessite la sélection d’une requête prédéfinie.
Pour utiliser cette source d’affectation, les administrateurs du site doivent configurer un serveur de base de données externe et des requêtes dans _Administration du site / Serveur / Bases de données externes_.';
$string['source_extdb_archiveremoved'] = 'Archiver les utilisateurs manquants';
$string['source_extdb_lastsync'] = 'Dernière mise à jour des affectations';
$string['source_extdb_pendingsync'] = 'Mise à jour des affectations en attente';
$string['source_extdb_sync'] = 'Traiter les affectations';
$string['source_extdb_sync_notqueued'] = 'Échec de la création de la tâche ad hoc d’affectation par base de données externe';
$string['source_extdb_sync_queued'] = 'Tâche ad hoc d’affectation par base de données externe créée';
$string['source_manual'] = 'Affectation manuelle';
$string['source_manual_allocateusers'] = 'Affecter des utilisateurs';
$string['source_manual_csvfile'] = 'Fichier CSV';
$string['source_manual_hasheaders'] = 'La première ligne contient les noms des colonnes';
$string['source_manual_potusers'] = 'Candidats à l’affectation';
$string['source_manual_potusersmatching'] = 'Candidats à l’affectation correspondants';
$string['source_manual_result_assigned'] = 'Nombre d’utilisateurs affectés au programme : {$a}';
$string['source_manual_result_errors'] = 'Nombre d’erreurs lors de l’affectation au programme : {$a}';
$string['source_manual_result_skipped'] = 'Nombre d’utilisateurs déjà affectés au programme : {$a}';
$string['source_manual_timeduecolumn'] = 'Colonne de la date limite';
$string['source_manual_timeendcolumn'] = 'Colonne de la fin du programme';
$string['source_manual_timestartcolumn'] = 'Colonne du début du programme';
$string['source_manual_uploadusers'] = 'Déposer des affectations';
$string['source_manual_usercolumn'] = 'Colonne d’identification de l’utilisateur';
$string['source_manual_usermapping'] = 'Identifier les utilisateurs par';
$string['source_manual_userupload_allocated'] = 'Affecté à « {$a} »';
$string['source_manual_userupload_alreadyallocated'] = 'Déjà affecté à « {$a} »';
$string['source_manual_userupload_invalidprogram'] = 'Impossible d’affecter à « {$a} »';
$string['source_mucertify'] = 'Certifications';
$string['source_mucertify_allownew'] = 'Autoriser l’affectation par les certifications';
$string['source_mucertify_allownew_desc'] = 'Autoriser l’ajout de nouvelles sources _certification_ aux programmes';
$string['source_program'] = 'Programme terminé';
$string['source_program_allownew'] = 'Autoriser l’affectation par achèvement d’un programme';
$string['source_program_allownew_desc'] = 'Autoriser l’ajout de nouvelles sources _achèvement d’un programme_ aux programmes';
$string['source_program_completedprogram'] = 'Programme à terminer';
$string['source_selfallocation'] = 'Auto-affectation';
$string['source_selfallocation_allocate'] = 'Adhérer';
$string['source_selfallocation_allownew'] = 'Autoriser l’auto-affectation';
$string['source_selfallocation_allownew_desc'] = 'Autoriser l’ajout de nouvelles sources _auto-affectation_ aux programmes';
$string['source_selfallocation_allowsignup'] = 'Autoriser les nouvelles adhésions';
$string['source_selfallocation_confirm'] = 'Veuillez confirmer que vous souhaitez être affecté au programme.';
$string['source_selfallocation_enable'] = 'Activer l’auto-affectation';
$string['source_selfallocation_key'] = 'Clef d’accès';
$string['source_selfallocation_keyrequired'] = 'La clef d’accès est requise';
$string['source_selfallocation_maxusers'] = 'Nombre maximal d’utilisateurs';
$string['source_selfallocation_maxusers_status'] = 'Utilisateurs : {$a->count}/{$a->max}';
$string['source_selfallocation_maxusersreached'] = 'Le nombre maximal d’utilisateurs auto-affectés est déjà atteint';
$string['source_selfallocation_signupallowed'] = 'Les adhésions sont autorisées';
$string['source_selfallocation_signupnotallowed'] = 'Les adhésions ne sont pas autorisées';
$string['taballocation'] = 'Réglages d’affectation';
$string['tabcontent'] = 'Contenu';
$string['tabgeneral'] = 'Général';
$string['tabusers'] = 'Utilisateurs';
$string['tabvisibility'] = 'Visibilité dans le catalogue';
$string['tagarea_tool_muprog_program'] = 'Programmes';
$string['task_extdb_cron'] = 'Cron d’affectation aux programmes par base de données externe';
$string['task_extdb_sync'] = 'Affectation aux programmes par base de données externe';
$string['taskcertificate'] = 'Cron de délivrance des certificats des programmes';
$string['taskcron'] = 'Cron du plugin Programmes';
$string['unlinkeditems'] = 'Éléments non liés';
$string['updateattendance'] = 'Modifier la présence hors ligne';
$string['updatecourse'] = 'Modifier le cours';
$string['updatecredits'] = 'Modifier les crédits';
$string['updatescheduling'] = 'Modifier la planification';
$string['updateset'] = 'Modifier l’ensemble';
$string['updatesource'] = 'Modifier la source « {$a} »';
$string['upload'] = 'Déposer des programmes';
$string['upload_files'] = 'Fichiers';
$string['upload_files_error'] = 'Plusieurs fichiers CSV, un fichier JSON ou une archive Zip sont attendus';
$string['upload_invalidcount'] = 'Enregistrements non valides';
$string['upload_preview'] = 'Aperçu des données';
$string['upload_status'] = 'Statut';
$string['upload_status_invalid'] = 'Non valide';
$string['upload_targetcontext'] = 'Ajouter les programmes dans le contexte';
$string['upload_uploadcount'] = 'Programmes à déposer';
$string['upload_usecategory'] = 'Utiliser la colonne de catégorie pour les contextes';
$string['userupload_completion_error'] = 'L’achèvement du programme ne peut pas être mis à jour';
$string['userupload_completion_updated'] = 'L’achèvement du programme a été mis à jour';
