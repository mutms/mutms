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
 * Strings for component 'tool_mucertify', language 'it'.
 *
 * NOTE: This Italian translation was produced automatically by an AI language
 * model and has not yet been reviewed by a human translator. Corrections are
 * welcome.
 *
 * @package    tool_mucertify
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


$string['allcertifications'] = 'Tutte le certificazioni';
$string['archived'] = 'Archiviata';
$string['assignment'] = 'Assegnazione';
$string['assignment_archive'] = 'Archivia assegnazione';
$string['assignment_delete'] = 'Elimina assegnazione';
$string['assignment_restore'] = 'Ripristina assegnazione';
$string['assignment_update'] = 'Modifica assegnazione';
$string['assignments'] = 'Assegnazioni';
$string['assignmentsources'] = 'Fonti di assegnazione';
$string['catalogue'] = 'Catalogo delle certificazioni';
$string['catalogue_actions'] = 'Azioni del catalogo';
$string['catalogue_dofilter'] = 'Cerca';
$string['catalogue_resetfilter'] = 'Reimposta';
$string['catalogue_searchtext'] = 'Testo da cercare';
$string['catalogue_tag'] = 'Filtra per tag';
$string['certificates'] = 'Certificati';
$string['certification'] = 'Certificazione';
$string['certification_actions'] = 'Azioni della certificazione';
$string['certification_archive'] = 'Archivia certificazione';
$string['certification_archive_info'] = 'L\'archiviazione della certificazione:

* archivia le relative allocazioni ai programmi,
* impedisce le modifiche alla certificazione,
* e in generale nasconde la certificazione agli utenti normali.

L\'archiviazione è un passaggio obbligatorio prima di poter eliminare la certificazione.';
$string['certification_create'] = 'Aggiungi certificazione';
$string['certification_delete'] = 'Elimina certificazione';
$string['certification_delete_info'] = 'Con l\'eliminazione della certificazione vengono eliminati tutti i suoi dati e vengono annullate le allocazioni degli utenti ai programmi.';
$string['certification_move'] = 'Sposta certificazione';
$string['certification_restore'] = 'Ripristina certificazione';
$string['certification_restore_info'] = 'Il ripristino della certificazione annulla le modifiche effettuate durante la sua archiviazione.

Si raccomanda tuttavia di verificare in seguito tutte le impostazioni della certificazione e gli utenti assegnati.';
$string['certification_update'] = 'Modifica certificazione';
$string['certificationidnumber'] = 'Codice identificativo della certificazione';
$string['certificationimage'] = 'Immagine della certificazione';
$string['certificationname'] = 'Nome della certificazione';
$string['certifications'] = 'Certificazioni';
$string['certificationsactive'] = 'Attive';
$string['certificationsarchived'] = 'Archiviate';
$string['certificationstatus'] = 'Stato della certificazione';
$string['certificationstatus_any'] = 'Qualsiasi';
$string['certificationstatus_archived'] = 'Archiviata';
$string['certificationstatus_certified'] = 'Certificata';
$string['certificationstatus_expired'] = 'Scaduta';
$string['certificationstatus_notcertified'] = 'Non certificata';
$string['certificationstatus_temporary'] = 'Temporaneamente valida';
$string['certificationstatus_valid'] = 'Valida';
$string['certificationurl'] = 'URL della certificazione';
$string['certifieddate'] = 'Data di completamento della certificazione';
$string['certifieduntiltemporary'] = 'Certificazione temporanea fino al';
$string['cohorts'] = 'Visibile ai gruppi globali';
$string['cohorts_help'] = 'Le certificazioni non pubbliche possono essere rese visibili ai membri dei gruppi globali indicati.

Lo stato di visibilità non influisce sulle certificazioni già assegnate.';
$string['columnusedalready'] = 'La colonna è già utilizzata';
$string['currentcontextonly'] = 'Escludi sottocategorie';
$string['customfields'] = 'Campi personalizzati delle certificazioni';
$string['customfields_assignment'] = 'Campi personalizzati delle assegnazioni alle certificazioni';
$string['customfieldsettings'] = 'Impostazioni comuni dei campi personalizzati delle certificazioni';
$string['customfieldvisible:assigned'] = 'Utenti assegnati alla certificazione';
$string['customfieldvisible:assignee'] = 'Utente assegnato';
$string['customfieldvisible:everyone'] = 'Chiunque possa vedere gli altri dettagli della certificazione';
$string['customfieldvisible:viewcapability'] = 'Utenti con il privilegio di visualizzare le certificazioni';
$string['customfieldvisibleto'] = 'Il contenuto del campo è visibile a';
$string['delayafter'] = '{$a->delay} dopo {$a->after}';
$string['delaybefore'] = '{$a->delay} prima di {$a->before}';
$string['errornoassignment'] = 'La certificazione non è assegnata';
$string['errornoassignments'] = 'Non sono state trovate assegnazioni alle certificazioni.';
$string['errornocertifications'] = 'Non sono state trovate certificazioni.';
$string['errornomycertifications'] = 'Non sono state trovate certificazioni assegnate.';
$string['errornorequests'] = 'Non sono state trovate richieste di certificazione';
$string['event_assignment_archived'] = 'Assegnazione dell\'utente alla certificazione archiviata';
$string['event_assignment_created'] = 'Utente assegnato alla certificazione';
$string['event_assignment_deleted'] = 'Assegnazione dell\'utente alla certificazione annullata';
$string['event_assignment_restored'] = 'Assegnazione dell\'utente alla certificazione ripristinata';
$string['event_assignment_updated'] = 'Assegnazione dell\'utente alla certificazione aggiornata';
$string['event_assignment_viewed'] = 'Assegnazione alla certificazione visualizzata';
$string['event_certification_archived'] = 'Certificazione archiviata';
$string['event_certification_created'] = 'Certificazione creata';
$string['event_certification_deleted'] = 'Certificazione eliminata';
$string['event_certification_restored'] = 'Certificazione ripristinata';
$string['event_certification_updated'] = 'Certificazione aggiornata';
$string['event_period_certified'] = 'Utente certificato';
$string['event_period_created'] = 'Periodo di certificazione creato';
$string['event_period_deleted'] = 'Periodo di certificazione eliminato';
$string['event_period_updated'] = 'Periodo di certificazione aggiornato';
$string['evidence_default'] = 'Attestazione di default';
$string['evidence_default_text'] = 'Caricamento di periodi di certificazione storici';
$string['evidence_details'] = 'Dettagli dell\'attestazione';
$string['evidence_details_help'] = 'I dettagli dell\'attestazione spiegano perché la certificazione è stata concessa o revocata.';
$string['expirationafter'] = 'Scade dopo';
$string['fromdate'] = 'Valida dal';
$string['graceperiod'] = 'Periodo di tolleranza';
$string['history_upload'] = 'Carica storico';
$string['history_upload_assign'] = 'Crea nuove assegnazioni';
$string['history_upload_evidencecolumn'] = 'Colonna dell\'attestazione';
$string['history_upload_result_assigned'] = 'Utenti assegnati alla certificazione: {$a}';
$string['history_upload_result_errors'] = 'Righe non valide ignorate: {$a}';
$string['history_upload_result_periods'] = 'Periodi di certificazione importati: {$a}';
$string['history_upload_result_skipped'] = 'Righe saltate: {$a}';
$string['history_upload_skipassigned'] = 'Salta gli utenti già assegnati';
$string['history_upload_timecertifiedcolumn'] = 'Colonna della data di certificazione';
$string['history_upload_timefromcolumn'] = 'Colonna di inizio validità del periodo';
$string['history_upload_timeuntilcolumn'] = 'Colonna della scadenza del periodo';
$string['management'] = 'Gestione delle certificazioni';
$string['management_certification_general_actions'] = 'Azioni della certificazione';
$string['management_certification_users_actions'] = 'Azioni sugli utenti';
$string['messageprovider:approval_reject_notification'] = 'Notifica di rifiuto della richiesta di certificazione';
$string['messageprovider:approval_request_notification'] = 'Notifica di richiesta di approvazione della certificazione';
$string['messageprovider:assignment_notification'] = 'Notifica di assegnazione alla certificazione';
$string['messageprovider:cc_supervisor_notification'] = 'Copia delle notifiche sulle certificazioni dei subordinati';
$string['messageprovider:unassignment_notification'] = 'Notifica di annullamento dell\'assegnazione alla certificazione';
$string['messageprovider:valid_notification'] = 'Notifica di validità della certificazione';
$string['mucertify:admin'] = 'Amministrazione avanzata delle certificazioni';
$string['mucertify:assign'] = 'Assegnare utenti alle certificazioni';
$string['mucertify:configurecustomfields'] = 'Configurare i campi personalizzati delle certificazioni';
$string['mucertify:delete'] = 'Eliminare certificazioni';
$string['mucertify:edit'] = 'Aggiungere e modificare certificazioni';
$string['mucertify:unassign'] = 'Annullare l\'assegnazione degli utenti alle certificazioni';
$string['mucertify:view'] = 'Visualizzare la gestione delle certificazioni';
$string['mucertify:viewcatalogue'] = 'Accedere al catalogo delle certificazioni';
$string['mucertify:viewusercertifications'] = 'Visualizzare le certificazioni di altri utenti';
$string['mycertifications'] = 'Le mie certificazioni';
$string['never'] = 'Mai';
$string['noexpiration'] = 'Nessuna scadenza';
$string['notallocated'] = 'Non allocato';
$string['notification_assignment'] = 'Utente assegnato';
$string['notification_assignment_body'] = 'Gentile {$a->user_fullname},

ti è stata assegnata la certificazione "{$a->certification_fullname}".';
$string['notification_assignment_description'] = 'Notifica inviata agli utenti quando vengono assegnati a una certificazione.';
$string['notification_assignment_subject'] = 'Notifica di assegnazione alla certificazione';
$string['notification_cc_supervisor_body'] = 'Gentile {$a->supervisor_fullname},

è stata inviata una notifica al seguente utente:

* {$a->subordinate_title}: {$a->user_fullname}
* Tipo di notifica: {$a->notification_name}
* Certificazione correlata: {$a->certification_fullname}

';
$string['notification_cc_supervisor_subject'] = 'Notifica ({$a->supervisor_title}) - {$a->certification_fullname}';
$string['notification_unassignment'] = 'Assegnazione dell\'utente annullata';
$string['notification_unassignment_body'] = 'Gentile {$a->user_fullname},

la tua assegnazione alla certificazione "{$a->certification_fullname}" è stata annullata.';
$string['notification_unassignment_description'] = 'Notifica inviata agli utenti quando la loro assegnazione a una certificazione viene annullata.';
$string['notification_unassignment_subject'] = 'Notifica di annullamento dell\'assegnazione alla certificazione';
$string['notification_valid'] = 'Certificazione valida';
$string['notification_valid_body'] = 'Gentile {$a->user_fullname},

la tua certificazione "{$a->certification_fullname}" è ora valida:

* valida dal: {$a->period_fromdate}
* scade il: {$a->period_untildate}
* la ricertificazione si apre il: {$a->period_recertificationdate}
';
$string['notification_valid_description'] = 'Notifica inviata agli utenti quando la loro certificazione diventa valida.';
$string['notification_valid_subject'] = 'Notifica di certificazione valida';
$string['notificationdates'] = 'Notifiche';
$string['notifications'] = 'Notifiche delle certificazioni';
$string['notset'] = 'Non impostato';
$string['period'] = 'Periodo di certificazione';
$string['period_create'] = 'Aggiungi periodo';
$string['period_delete'] = 'Elimina periodo';
$string['period_update'] = 'Sovrascrivi le date del periodo';
$string['periods'] = 'Periodi di certificazione';
$string['periodstatus'] = 'Stato';
$string['periodstatus_archived'] = 'Archiviato';
$string['periodstatus_certified'] = 'Certificato';
$string['periodstatus_expired'] = 'Scaduto';
$string['periodstatus_failed'] = 'Non superato';
$string['periodstatus_future'] = 'Futuro';
$string['periodstatus_overdue'] = 'In ritardo';
$string['periodstatus_pending'] = 'In attesa';
$string['periodstatus_revoked'] = 'Revocato';
$string['pluginname'] = 'Certificazioni';
$string['pluginname_desc'] = 'Strumento per certificazioni e ricertificazioni';
$string['privacy:metadata:field:archived'] = 'Flag di archiviazione';
$string['privacy:metadata:field:assignmentid'] = 'Id dell\'assegnazione';
$string['privacy:metadata:field:certificationid'] = 'Id della certificazione';
$string['privacy:metadata:field:datajson'] = 'Dati JSON';
$string['privacy:metadata:field:programid'] = 'Id del programma';
$string['privacy:metadata:field:quantity'] = 'Quantità';
$string['privacy:metadata:field:rejectedby'] = 'Rifiutata da';
$string['privacy:metadata:field:sourceid'] = 'Id della fonte';
$string['privacy:metadata:field:timecertified'] = 'Data di certificazione';
$string['privacy:metadata:field:timecertifiedtemp'] = 'Data di fine della certificazione temporanea';
$string['privacy:metadata:field:timefrom'] = 'Data di inizio della certificazione';
$string['privacy:metadata:field:timerejected'] = 'Data del rifiuto';
$string['privacy:metadata:field:timerequested'] = 'Data della richiesta';
$string['privacy:metadata:field:timerevoked'] = 'Data di revoca della certificazione';
$string['privacy:metadata:field:timeuntil'] = 'Data di fine della certificazione';
$string['privacy:metadata:field:timewindowdue'] = 'Data limite della finestra';
$string['privacy:metadata:field:timewindowend'] = 'Data di chiusura della finestra';
$string['privacy:metadata:field:timewindowstart'] = 'Data di apertura della finestra';
$string['privacy:metadata:field:userid'] = 'Id dell\'utente';
$string['privacy:metadata:table:tool_mucertify_assignment'] = 'Tabella delle assegnazioni degli utenti';
$string['privacy:metadata:table:tool_mucertify_period'] = 'Tabella dei periodi di certificazione';
$string['privacy:metadata:table:tool_mucertify_request'] = 'Tabella delle richieste di certificazione';
$string['program1'] = 'Programma di certificazione';
$string['program2'] = 'Programma di ricertificazione';
$string['publicaccess'] = 'Pubblica';
$string['publicaccess_help'] = 'Le certificazioni pubbliche sono visibili a tutti gli utenti.

Lo stato di visibilità non influisce sulle certificazioni già assegnate.';
$string['purchaseaccess'] = 'Acquista accesso';
$string['recertification'] = 'Ricertificazione';
$string['recertifications'] = 'Ricertificazioni';
$string['recertify'] = 'Ricertifica automaticamente';
$string['recertifybefore'] = 'Ricertifica prima della scadenza';
$string['recertifyifexpired'] = 'Se scaduta';
$string['resettype1'] = 'Reset del programma di certificazione';
$string['resettype2'] = 'Reset del programma di ricertificazione';
$string['revokeddate'] = 'Data di revoca';
$string['selectcategory'] = 'Seleziona categoria';
$string['settings'] = 'Impostazioni della certificazione';
$string['source'] = 'Fonte';
$string['source_approval'] = 'Richieste con approvazione';
$string['source_approval_allownew'] = 'Consenti approvazioni';
$string['source_approval_allownew_desc'] = 'Consente di aggiungere nuove fonti _richieste con approvazione_ alle certificazioni';
$string['source_approval_allowrequest'] = 'Consenti nuove richieste';
$string['source_approval_confirm'] = 'Conferma di voler richiedere l\'assegnazione alla certificazione.';
$string['source_approval_daterejected'] = 'Data del rifiuto';
$string['source_approval_daterequested'] = 'Data della richiesta';
$string['source_approval_makerequest'] = 'Richiedi accesso';
$string['source_approval_notification_approval_reject_body'] = 'Gentile {$a->user_fullname},

la tua richiesta di accesso alla certificazione "{$a->certification_fullname}" è stata rifiutata.

{$a->reason}
';
$string['source_approval_notification_approval_reject_subject'] = 'Notifica di rifiuto della richiesta di certificazione';
$string['source_approval_notification_approval_request_body'] = '
L\'utente {$a->user_fullname} ha richiesto l\'accesso alla certificazione "{$a->certification_fullname}".
';
$string['source_approval_notification_approval_request_subject'] = 'Notifica di richiesta di certificazione';
$string['source_approval_rejectionreason'] = 'Motivo del rifiuto';
$string['source_approval_request'] = 'Richiesta';
$string['source_approval_requestallowed'] = 'Le richieste sono consentite';
$string['source_approval_requestapprove'] = 'Approva richiesta';
$string['source_approval_requestdelete'] = 'Elimina richiesta';
$string['source_approval_requestnotallowed'] = 'Le richieste non sono consentite';
$string['source_approval_requestpending'] = 'Richiesta di accesso in attesa';
$string['source_approval_requestreject'] = 'Rifiuta richiesta';
$string['source_approval_requestrejected'] = 'La richiesta di accesso è stata rifiutata';
$string['source_approval_requests'] = 'Richieste';
$string['source_cohort'] = 'Assegnazione automatica dei gruppi globali';
$string['source_cohort_allownew'] = 'Consenti l\'assegnazione dei gruppi globali';
$string['source_cohort_allownew_desc'] = 'Consente di aggiungere nuove fonti _assegnazione automatica dei gruppi globali_ alle certificazioni';
$string['source_cohort_cohortstoassign'] = 'Assegna gruppi globali';
$string['source_manual'] = 'Assegnazione manuale';
$string['source_manual_assignusers'] = 'Assegna utenti';
$string['source_manual_hasheaders'] = 'La prima riga contiene i nomi delle colonne';
$string['source_manual_result_assigned'] = 'Numero di utenti assegnati alla certificazione: {$a}';
$string['source_manual_result_errors'] = 'Numero di errori durante l\'assegnazione alla certificazione: {$a}';
$string['source_manual_result_skipped'] = 'Numero di utenti già assegnati alla certificazione: {$a}';
$string['source_manual_timecertifiedtempcolumn'] = 'Colonna della fine della certificazione temporanea';
$string['source_manual_timeduecolumn'] = 'Colonna della data limite della certificazione';
$string['source_manual_timeendcolumn'] = 'Colonna della chiusura della finestra';
$string['source_manual_timestartcolumn'] = 'Colonna dell\'apertura della finestra';
$string['source_manual_timeuntilcolumn'] = 'Colonna della scadenza';
$string['source_manual_uploadusers'] = 'Carica assegnazioni';
$string['source_manual_usercolumn'] = 'Colonna di identificazione dell\'utente';
$string['source_manual_usermapping'] = 'Identifica gli utenti tramite';
$string['source_selfassignment'] = 'Autoassegnazione';
$string['source_selfassignment_allownew'] = 'Consenti l\'autoassegnazione';
$string['source_selfassignment_allownew_desc'] = 'Consente di aggiungere nuove fonti _autoassegnazione_ alle certificazioni';
$string['source_selfassignment_allowsignup'] = 'Consenti nuove adesioni';
$string['source_selfassignment_assign'] = 'Aderisci';
$string['source_selfassignment_confirm'] = 'Conferma di voler essere assegnato alla certificazione.';
$string['source_selfassignment_enable'] = 'Abilita l\'autoassegnazione';
$string['source_selfassignment_key'] = 'Chiave di accesso';
$string['source_selfassignment_keyrequired'] = 'La chiave di accesso è obbligatoria';
$string['source_selfassignment_maxusers'] = 'Numero massimo di utenti';
$string['source_selfassignment_maxusers_status'] = 'Utenti {$a->count}/{$a->max}';
$string['source_selfassignment_maxusersreached'] = 'È già stato raggiunto il numero massimo di utenti autoassegnati';
$string['source_selfassignment_signupallowed'] = 'Le adesioni sono consentite';
$string['source_selfassignment_signupnotallowed'] = 'Le adesioni non sono consentite';
$string['stoprecertify'] = 'Ricertificazione interrotta';
$string['tabassignment'] = 'Impostazioni di assegnazione';
$string['tabgeneral'] = 'Generale';
$string['tabsettings'] = 'Impostazioni dei periodi';
$string['tabusers'] = 'Utenti';
$string['tabvisibility'] = 'Visibilità nel catalogo';
$string['tagarea_tool_mucertify_certification'] = 'Certificazioni';
$string['taskcron'] = 'Elaborazione cron delle certificazioni';
$string['tasktriggercertificate'] = 'Avvia il prima possibile il cron di rilascio dei certificati';
$string['untildate'] = 'Scadenza';
$string['updateassignments'] = 'Modifica impostazioni di assegnazione';
$string['updatecertificatetemplate'] = 'Modifica modello di certificato';
$string['updaterecertification'] = 'Modifica ricertificazione';
$string['updatesource'] = 'Modifica: {$a}';
$string['upload_csvfile'] = 'File CSV';
$string['validfrom'] = 'Valida dal';
$string['windowdueafter'] = 'Data limite dopo';
$string['windowduedate'] = 'Data limite della certificazione';
$string['windowendafter'] = 'Chiusura della finestra dopo';
$string['windowenddate'] = 'Chiusura della finestra';
$string['windowstartdate'] = 'Apertura della finestra';
