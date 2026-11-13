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
 * Strings for component 'tool_muprog', language 'it'.
 *
 * NOTE: This Italian translation was produced automatically by an AI language
 * model and has not yet been reviewed by a human translator. Corrections are
 * welcome.
 *
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['allocation'] = 'Allocazione';
$string['allocation_archive'] = 'Archivia allocazione';
$string['allocation_reset'] = 'Reimposta avanzamento nel programma';
$string['allocation_reset_updateallocation'] = 'Modifica allocazione';
$string['allocation_restore'] = 'Ripristina allocazione';
$string['allocation_update'] = 'Modifica allocazione';
$string['allocationdate'] = 'Data di allocazione';
$string['allocationend'] = 'Fine allocazione';
$string['allocationend_help'] = 'Il significato della data di fine allocazione dipende dalle fonti di allocazione abilitate. Di norma, se la data è specificata, dopo di essa non sono possibili nuove allocazioni.';
$string['allocations'] = 'Allocazioni';
$string['allocationsources'] = 'Fonti di allocazione';
$string['allocationstart'] = 'Inizio allocazione';
$string['allocationstart_help'] = 'Il significato della data di inizio allocazione dipende dalle fonti di allocazione abilitate. Di norma, se la data è specificata, le nuove allocazioni sono possibili solo dopo di essa.';
$string['allprograms'] = 'Tutti i programmi';
$string['appendinto'] = 'Accoda nell\'elemento';
$string['appenditem'] = 'Accoda elemento';
$string['archived'] = 'Archiviato';
$string['attendance'] = 'Partecipazione in presenza';
$string['attendance_effective'] = 'Data di decorrenza';
$string['attendance_status'] = 'Stato';
$string['attendance_status_completed'] = 'Completato';
$string['attendance_status_failed'] = 'Non superato';
$string['attendance_status_noshow'] = 'Assente';
$string['attendance_take'] = 'Registra partecipazione';
$string['calendarprogramdue'] = 'Data limite: {$a}';
$string['calendarprogramend'] = 'Fine: {$a}';
$string['calendarprogramstart'] = 'Inizio: {$a}';
$string['catalogue'] = 'Catalogo dei programmi';
$string['catalogue_actions'] = 'Azioni del catalogo';
$string['catalogue_dofilter'] = 'Cerca';
$string['catalogue_resetfilter'] = 'Reimposta';
$string['catalogue_searchtext'] = 'Testo da cercare';
$string['catalogue_tag'] = 'Filtra per tag';
$string['certificatetemplatechoose'] = 'Scegli un modello...';
$string['cohorts'] = 'Visibile ai gruppi globali';
$string['cohorts_help'] = 'I programmi non pubblici possono essere resi visibili ai membri dei gruppi globali indicati.

Lo stato di visibilità non influisce sui programmi già allocati.';
$string['columnusedalready'] = 'La colonna è già utilizzata';
$string['completiondate'] = 'Data di completamento';
$string['completiondelay'] = 'Ritardo del completamento';
$string['completionoverride'] = 'Sovrascrivi completamento';
$string['creategroups'] = 'Gruppi dei corsi';
$string['creategroups_help'] = 'Se l\'impostazione è abilitata, in ogni corso aggiunto al programma verrà creato un gruppo e tutti gli utenti allocati ne diventeranno membri.';
$string['credits'] = 'Crediti';
$string['credits_progress'] = 'Crediti attuali: {$a->current}/{$a->total}';
$string['credits_requiredcredits'] = 'Crediti richiesti: {$a}';
$string['currentcontextonly'] = 'Escludi sottocategorie';
$string['customfields'] = 'Campi personalizzati dei programmi';
$string['customfields_allocation'] = 'Campi personalizzati delle allocazioni ai programmi';
$string['customfieldsettings'] = 'Impostazioni comuni dei campi personalizzati dei programmi';
$string['customfieldvisible:allocated'] = 'Utenti allocati ai programmi';
$string['customfieldvisible:allocatee'] = 'Utente allocato';
$string['customfieldvisible:everyone'] = 'Chiunque possa vedere gli altri dettagli del programma';
$string['customfieldvisible:viewcapability'] = 'Utenti con il privilegio di visualizzare i programmi';
$string['customfieldvisibleto'] = 'Il contenuto del campo è visibile a';
$string['deleteallocation'] = 'Elimina allocazione al programma';
$string['deleteattendance'] = 'Elimina partecipazione in presenza';
$string['deletecourse'] = 'Rimuovi corso';
$string['deletecredits'] = 'Rimuovi crediti';
$string['deleteset'] = 'Elimina set di corsi';
$string['duedate'] = 'Data limite';
$string['enrolrole'] = 'Ruolo nel corso';
$string['enrolrole_desc'] = 'Selezionare il ruolo che i programmi utilizzeranno per l\'iscrizione ai corsi';
$string['errorcontentproblem'] = 'È stato rilevato un problema nella struttura dei contenuti del programma, il completamento del programma non verrà tracciato correttamente!';
$string['errorcoursemissing'] = 'Il corso è mancante';
$string['errorcoursesmissing'] = 'Corsi mancanti: {$a}';
$string['errordifferenttenant'] = 'Non è possibile accedere a un programma di un altro tenant';
$string['errorinvalidoverridedates'] = 'Date sovrascritte non valide';
$string['errornoallocation'] = 'Il programma non è allocato';
$string['errornoallocations'] = 'Non sono state trovate allocazioni di utenti';
$string['errornomyprograms'] = 'Non hai allocazioni a nessun programma.';
$string['errornoprograms'] = 'Non sono stati trovati programmi.';
$string['errornorequests'] = 'Non sono state trovate richieste relative ai programmi';
$string['errornotenabled'] = 'Il plugin Programmi non è abilitato';
$string['event_allocation_archived'] = 'Allocazione dell\'utente archiviata';
$string['event_allocation_completed'] = 'Programma completato dall\'utente';
$string['event_allocation_created'] = 'Utente allocato al programma';
$string['event_allocation_deleted'] = 'Utente deallocato dal programma';
$string['event_allocation_restored'] = 'Allocazione dell\'utente ripristinata';
$string['event_allocation_updated'] = 'Allocazione dell\'utente al programma aggiornata';
$string['event_allocation_viewed'] = 'Allocazione al programma visualizzata';
$string['event_catalogue_program_viewed'] = 'Catalogo dei programmi visualizzato';
$string['event_program_archived'] = 'Programma archiviato';
$string['event_program_created'] = 'Programma creato';
$string['event_program_deleted'] = 'Programma eliminato';
$string['event_program_restored'] = 'Programma ripristinato';
$string['event_program_updated'] = 'Programma aggiornato';
$string['event_program_viewed'] = 'Programma visualizzato';
$string['evidence'] = 'Altra attestazione';
$string['evidence_details'] = 'Dettagli';
$string['evidence_detailsdefault'] = 'Dettagli di default';
$string['evidencedate'] = 'Data di completamento secondo l\'attestazione';
$string['evidenceupdate'] = 'Modifica altra attestazione';
$string['evidenceupload'] = 'Carica attestazioni di completamento';
$string['evidenceupload_csvfile'] = 'File CSV';
$string['evidenceupload_errors'] = 'Numero di righe non valide: {$a}';
$string['evidenceupload_skipped'] = 'Numero di righe saltate: {$a}';
$string['evidenceupload_updated'] = 'Attestazioni di completamento aggiornate. Numero di utenti: {$a}';
$string['export'] = 'Esporta programmi';
$string['exportfile_info'] = 'info';
$string['exportfile_programs'] = 'programs';
$string['exportformat'] = 'Formato file';
$string['exportformat_csv'] = 'CSV';
$string['exportformat_json'] = 'JSON';
$string['extdb_query_allocation'] = 'Allocazione al programma';
$string['favourite'] = 'Programma preferito';
$string['fixeddate'] = 'A una data fissa';
$string['importallocationend'] = 'Fine allocazione ({$a})';
$string['importallocationstart'] = 'Inizio allocazione ({$a})';
$string['importprogramallocation'] = 'Importa impostazioni di allocazione';
$string['importprogramallocationconfirmation'] = 'Si stanno importando le impostazioni di allocazione dal programma __{$a->fullname} / {$a->idnumber} / {$a->category}__.

Selezionare tutte le impostazioni da importare.';
$string['importprogramcontent'] = 'Importa contenuto del programma';
$string['importprogramcontentconfirmation'] = 'Si sta importando il contenuto dal programma __{$a->fullname} / {$a->idnumber} / {$a->category}__.';
$string['importprogramdue'] = 'Data limite del programma ({$a})';
$string['importprogramend'] = 'Fine del programma ({$a})';
$string['importprogramstart'] = 'Inizio del programma ({$a})';
$string['importselectprogram'] = 'Seleziona programma';
$string['includesubcontexts'] = 'Includi sottocontesti';
$string['invalidallocationdates'] = 'Date di allocazione al programma non valide';
$string['invalidcompletiondate'] = 'Data di completamento del programma non valida';
$string['item'] = 'Elemento';
$string['item_create_attendance'] = 'Aggiungi partecipazione in presenza';
$string['item_create_course'] = 'Aggiungi corsi';
$string['item_create_credits'] = 'Aggiungi crediti';
$string['item_create_set'] = 'Aggiungi set di corsi';
$string['item_type'] = 'Tipo di elemento';
$string['item_update_attendance'] = 'Modifica partecipazione in presenza';
$string['itemcompletion'] = 'Completamento dell\'elemento del programma';
$string['itempoints'] = 'Punti';
$string['itemrecalculate'] = 'Ricalcola completamento dell\'elemento';
$string['management'] = 'Gestione programmi';
$string['management_index_actions'] = 'Azioni sui programmi';
$string['management_program_allocation_actions'] = 'Azioni sulle impostazioni di allocazione';
$string['management_program_general_actions'] = 'Azioni sul programma';
$string['management_program_users_actions'] = 'Azioni sugli utenti';
$string['messageprovider:allocation_notification'] = 'Notifica di allocazione al programma';
$string['messageprovider:approval_reject_notification'] = 'Notifica di rifiuto della richiesta relativa al programma';
$string['messageprovider:approval_request_notification'] = 'Notifica di richiesta di approvazione per il programma';
$string['messageprovider:cc_supervisor_notification'] = 'Copia delle notifiche dei subordinati relative ai programmi';
$string['messageprovider:completion_notification'] = 'Notifica di programma completato';
$string['messageprovider:deallocation_notification'] = 'Notifica di deallocazione dal programma';
$string['messageprovider:due_notification'] = 'Notifica di programma in ritardo';
$string['messageprovider:duesoon_notification'] = 'Notifica di data limite del programma imminente';
$string['messageprovider:endcompleted_notification'] = 'Notifica di fine del programma completato';
$string['messageprovider:endfailed_notification'] = 'Notifica di fine del programma non superato';
$string['messageprovider:endsoon_notification'] = 'Notifica di data di fine del programma imminente';
$string['messageprovider:reset_notification'] = 'Notifica di reimpostazione del programma';
$string['messageprovider:start_notification'] = 'Notifica di programma iniziato';
$string['moveafter'] = 'Sposta "{$a->item}" dopo "{$a->target}"';
$string['movebefore'] = 'Sposta "{$a->item}" prima di "{$a->target}"';
$string['moveinto'] = 'Sposta "{$a->item}" in "{$a->target}"';
$string['moveitem'] = 'Sposta elemento';
$string['moveitemcancel'] = 'Annulla spostamento';
$string['muprog:addcourse'] = 'Aggiungere corsi ai programmi';
$string['muprog:addtocertifications'] = 'Aggiungere programmi alle certificazioni';
$string['muprog:admin'] = 'Amministrazione avanzata dei programmi';
$string['muprog:allocate'] = 'Allocare utenti ai programmi';
$string['muprog:clone'] = 'Clonare contenuto e impostazioni dei programmi';
$string['muprog:configurecustomfields'] = 'Configurare i campi personalizzati dei programmi';
$string['muprog:deallocate'] = 'Deallocare utenti dai programmi';
$string['muprog:delete'] = 'Eliminare programmi';
$string['muprog:edit'] = 'Aggiungere e modificare programmi';
$string['muprog:export'] = 'Esportare programmi';
$string['muprog:manageallocation'] = 'Gestire le allocazioni degli utenti';
$string['muprog:manageevidence'] = 'Gestire altre attestazioni di completamento';
$string['muprog:reset'] = 'Reimpostare l\'avanzamento nei programmi';
$string['muprog:takeattendance'] = 'Registrare la partecipazione in presenza';
$string['muprog:upload'] = 'Caricare programmi';
$string['muprog:view'] = 'Visualizzare la gestione dei programmi';
$string['muprog:viewcatalogue'] = 'Accedere al catalogo dei programmi';
$string['muprog:viewuserprograms'] = 'Visualizzare i programmi di altri utenti';
$string['myprograms'] = 'I miei programmi';
$string['notification_allocation'] = 'Utente allocato';
$string['notification_allocation_body'] = 'Buongiorno {$a->user_fullname},

ti è stato allocato il programma "{$a->program_fullname}", la data di inizio è {$a->program_startdate}.
';
$string['notification_allocation_description'] = 'Notifica inviata agli utenti quando vengono allocati a un programma.';
$string['notification_allocation_subject'] = 'Notifica di allocazione al programma';
$string['notification_cc_supervisor_body'] = 'Buongiorno {$a->supervisor_fullname},

è stata inviata una notifica al seguente utente:

* {$a->subordinate_title}: {$a->user_fullname}
* Tipo di notifica: {$a->notification_name}
* Programma correlato: {$a->program_fullname}

';
$string['notification_cc_supervisor_subject'] = 'Notifica per {$a->supervisor_title} - {$a->program_fullname}';
$string['notification_completion'] = 'Programma completato';
$string['notification_completion_body'] = 'Buongiorno {$a->user_fullname},

hai completato il programma "{$a->program_fullname}".
';
$string['notification_completion_description'] = 'Notifica inviata agli utenti quando completano il proprio programma.';
$string['notification_completion_subject'] = 'Programma completato';
$string['notification_deallocation'] = 'Utente deallocato';
$string['notification_deallocation_body'] = 'Buongiorno {$a->user_fullname},

la tua allocazione al programma "{$a->program_fullname}" è stata rimossa.
';
$string['notification_deallocation_description'] = 'Notifica inviata agli utenti quando vengono deallocati da un programma.';
$string['notification_deallocation_subject'] = 'Notifica di deallocazione dal programma';
$string['notification_due'] = 'Programma in ritardo';
$string['notification_due_body'] = 'Buongiorno {$a->user_fullname},

il completamento del programma "{$a->program_fullname}" era previsto entro {$a->program_duedate}.
';
$string['notification_due_description'] = 'Notifica inviata agli utenti quando il completamento del loro programma è in ritardo.';
$string['notification_due_subject'] = 'Il completamento del programma è in ritardo';
$string['notification_duesoon'] = 'Data limite del programma imminente';
$string['notification_duesoon_body'] = 'Buongiorno {$a->user_fullname},

il completamento del programma "{$a->program_fullname}" è previsto per {$a->program_duedate}.
';
$string['notification_duesoon_description'] = 'Notifica inviata agli utenti prima della data limite di completamento del loro programma, a meno che il programma non sia già completato.';
$string['notification_duesoon_subject'] = 'Il completamento del programma è previsto a breve';
$string['notification_endcompleted'] = 'Fine del programma completato';
$string['notification_endcompleted_body'] = 'Buongiorno {$a->user_fullname},

il programma "{$a->program_fullname}" è terminato, lo avevi già completato in precedenza.
';
$string['notification_endcompleted_description'] = 'Notifica inviata agli utenti quando termina un programma che hanno completato.';
$string['notification_endcompleted_subject'] = 'Il programma completato è terminato';
$string['notification_endfailed'] = 'Fine del programma non superato';
$string['notification_endfailed_body'] = 'Buongiorno {$a->user_fullname},

il programma "{$a->program_fullname}" è terminato, non sei riuscito a completarlo.
';
$string['notification_endfailed_description'] = 'Notifica inviata agli utenti quando termina un programma che non sono riusciti a completare.';
$string['notification_endfailed_subject'] = 'Il programma non superato è terminato';
$string['notification_endsoon'] = 'Data di fine del programma imminente';
$string['notification_endsoon_body'] = 'Buongiorno {$a->user_fullname},

il programma "{$a->program_fullname}" terminerà il {$a->program_enddate}.
';
$string['notification_endsoon_description'] = 'Notifica inviata agli utenti prima della data di fine del loro programma, a meno che il programma non sia già completato.';
$string['notification_endsoon_subject'] = 'Il programma termina a breve';
$string['notification_reset'] = 'Avanzamento dell\'utente reimpostato';
$string['notification_reset_body'] = 'Buongiorno {$a->user_fullname},

il tuo avanzamento nel programma "{$a->program_fullname}" è stato reimpostato.
';
$string['notification_reset_description'] = 'Notifica inviata agli utenti quando il loro avanzamento nel programma viene reimpostato.';
$string['notification_reset_subject'] = 'Notifica di reimpostazione del programma';
$string['notification_start'] = 'Programma iniziato';
$string['notification_start_body'] = 'Buongiorno {$a->user_fullname},

il programma "{$a->program_fullname}" è iniziato.
';
$string['notification_start_description'] = 'Notifica inviata agli utenti quando il loro programma è iniziato.';
$string['notification_start_subject'] = 'Programma iniziato';
$string['notificationdates'] = 'Date delle notifiche';
$string['notset'] = 'Non impostato';
$string['plugindisabled'] = 'Il plugin Iscrizione tramite programmi è disabilitato, i programmi non funzioneranno.

[Abilita il plugin ora]({$a->url})';
$string['pluginname'] = 'Programmi';
$string['pluginname_desc'] = 'I programmi sono concepiti per consentire la creazione di set di corsi.';
$string['privacy:metadata:field:allocationid'] = 'Id dell\'allocazione al programma';
$string['privacy:metadata:field:archived'] = 'Indica se il record è archiviato';
$string['privacy:metadata:field:createdby'] = 'Attestazione creata da';
$string['privacy:metadata:field:datajson'] = 'Informazioni sulla richiesta';
$string['privacy:metadata:field:evidencejson'] = 'Informazioni sull\'attestazione di completamento';
$string['privacy:metadata:field:explanation'] = 'Spiegazione';
$string['privacy:metadata:field:issueid'] = 'ID del rilascio';
$string['privacy:metadata:field:itemid'] = 'ID dell\'elemento';
$string['privacy:metadata:field:programid'] = 'Id del programma';
$string['privacy:metadata:field:quantity'] = 'Quantità';
$string['privacy:metadata:field:reason'] = 'Motivo';
$string['privacy:metadata:field:rejectedby'] = 'Richiesta rifiutata da';
$string['privacy:metadata:field:sourcedatajson'] = 'Informazioni sulla fonte dell\'allocazione';
$string['privacy:metadata:field:sourceid'] = 'Fonte dell\'allocazione';
$string['privacy:metadata:field:status'] = 'Stato';
$string['privacy:metadata:field:takenby'] = 'Partecipazione registrata da';
$string['privacy:metadata:field:timeallocated'] = 'Data di allocazione al programma';
$string['privacy:metadata:field:timecompleted'] = 'Data di completamento';
$string['privacy:metadata:field:timecreated'] = 'Data di creazione';
$string['privacy:metadata:field:timedue'] = 'Data limite';
$string['privacy:metadata:field:timeeffective'] = 'Data di decorrenza';
$string['privacy:metadata:field:timeend'] = 'Data di fine';
$string['privacy:metadata:field:timerejected'] = 'Data del rifiuto';
$string['privacy:metadata:field:timerequested'] = 'Data della richiesta';
$string['privacy:metadata:field:timestart'] = 'Data di inizio';
$string['privacy:metadata:field:userid'] = 'Id dell\'utente';
$string['privacy:metadata:table:tool_muprog_allocation'] = 'Informazioni sulle allocazioni ai programmi';
$string['privacy:metadata:table:tool_muprog_attendance'] = 'Partecipazione in presenza';
$string['privacy:metadata:table:tool_muprog_cert_issue'] = 'Certificati rilasciati per le allocazioni ai programmi';
$string['privacy:metadata:table:tool_muprog_completion'] = 'Completamenti delle allocazioni ai programmi';
$string['privacy:metadata:table:tool_muprog_evidence'] = 'Informazioni sulle altre attestazioni di completamento';
$string['privacy:metadata:table:tool_muprog_request'] = 'Informazioni sulla richiesta di allocazione';
$string['program'] = 'Programma';
$string['program_actions'] = 'Azioni sul programma';
$string['program_allocations_edit'] = 'Modifica allocazioni';
$string['program_archive'] = 'Archivia programma';
$string['program_archive_info'] = 'L\'archiviazione del programma:

* sospende le iscrizioni ai corsi del programma,
* elimina dal calendario gli eventi del programma,
* impedisce le modifiche del programma,
* e in generale nasconde il programma agli utenti normali.

L\'archiviazione è un passaggio obbligatorio prima che il programma possa essere eliminato.';
$string['program_create'] = 'Aggiungi programma';
$string['program_delete'] = 'Elimina programma';
$string['program_delete_info'] = 'Durante l\'eliminazione del programma vengono eliminati tutti i dati del programma e gli utenti vengono disiscritti dai corsi del programma.';
$string['program_move'] = 'Sposta programma';
$string['program_restore'] = 'Ripristina programma';
$string['program_restore_info'] = 'Il ripristino del programma annulla le modifiche effettuate durante l\'archiviazione del programma.

Si raccomanda tuttavia di verificare in seguito tutte le impostazioni del programma e gli utenti allocati.';
$string['program_update'] = 'Modifica programma';
$string['programallocations'] = 'Allocazioni al programma';
$string['programautofix'] = 'Ripara automaticamente il programma';
$string['programcategory'] = 'Categoria del programma';
$string['programcompletion'] = 'Data di completamento del programma';
$string['programcompletionoverride'] = 'Sovrascrivi completamento del programma';
$string['programdue'] = 'Data limite del programma';
$string['programdue_date'] = 'Data limite';
$string['programdue_delay'] = 'Data limite dopo l\'inizio';
$string['programdue_help'] = 'La data limite del programma indica entro quando gli utenti devono completare il programma.';
$string['programend'] = 'Fine del programma';
$string['programend_date'] = 'Data di fine del programma';
$string['programend_delay'] = 'Fine dopo l\'inizio';
$string['programend_help'] = 'Dopo la fine del programma gli utenti non possono entrare nei corsi del programma.';
$string['programidnumber'] = 'Codice identificativo del programma';
$string['programimage'] = 'Immagine del programma';
$string['programname'] = 'Nome del programma';
$string['programprogress'] = 'Avanzamento';
$string['programs'] = 'Programmi';
$string['programstart'] = 'Inizio del programma';
$string['programstart_allocation'] = 'Inizio subito dopo l\'allocazione';
$string['programstart_date'] = 'Data di inizio del programma';
$string['programstart_delay'] = 'Inizio ritardato dopo l\'allocazione';
$string['programstart_help'] = 'Prima dell\'inizio del programma gli utenti non possono entrare nei corsi del programma.';
$string['programstatus'] = 'Stato del programma';
$string['programstatus_any'] = 'Qualsiasi stato del programma';
$string['programstatus_archived'] = 'Archiviato';
$string['programstatus_archivedcompleted'] = 'Archiviato completato';
$string['programstatus_completed'] = 'Completato';
$string['programstatus_failed'] = 'Non superato';
$string['programstatus_future'] = 'Non ancora aperto';
$string['programstatus_open'] = 'Aperto';
$string['programstatus_overdue'] = 'In ritardo';
$string['programurl'] = 'URL del programma';
$string['publicaccess'] = 'Pubblico';
$string['publicaccess_help'] = 'I programmi pubblici sono visibili a tutti gli utenti.

Lo stato di visibilità non influisce sui programmi già allocati.';
$string['purchaseaccess'] = 'Acquista accesso';
$string['resettype'] = 'Tipo di reimpostazione';
$string['resettype_deallocate'] = 'Solo deallocazione dal programma';
$string['resettype_full'] = 'Svuotamento completo dei corsi';
$string['resettype_none'] = 'Nessuno';
$string['resettype_standard'] = 'Svuotamento standard dei corsi';
$string['scheduling'] = 'Pianificazione';
$string['sequencetype'] = 'Tipo di completamento';
$string['sequencetype_allinanyorder'] = 'Tutti in qualsiasi ordine';
$string['sequencetype_allinorder'] = 'Tutti in ordine';
$string['sequencetype_atleast'] = 'Almeno {$a->min}';
$string['sequencetype_minpoints'] = 'Punti minimi: {$a->minpoints}';
$string['set'] = 'Set di corsi';
$string['settings'] = 'Impostazioni dei programmi';
$string['source'] = 'Fonte';
$string['source_approval'] = 'Richieste con approvazione';
$string['source_approval_allownew'] = 'Consenti approvazioni';
$string['source_approval_allownew_desc'] = 'Consente di aggiungere ai programmi nuove fonti _richieste con approvazione_';
$string['source_approval_allowrequest'] = 'Consenti nuove richieste';
$string['source_approval_confirm'] = 'Confermare di voler richiedere l\'allocazione al programma.';
$string['source_approval_daterejected'] = 'Data del rifiuto';
$string['source_approval_daterequested'] = 'Data della richiesta';
$string['source_approval_makerequest'] = 'Richiedi accesso';
$string['source_approval_notification_approval_reject_body'] = 'Buongiorno {$a->user_fullname},

la tua richiesta di accesso al programma "{$a->program_fullname}" è stata rifiutata.

{$a->reason}
';
$string['source_approval_notification_approval_reject_subject'] = 'Notifica di rifiuto della richiesta relativa al programma';
$string['source_approval_notification_approval_request_body'] = '
L\'utente {$a->user_fullname} ha richiesto l\'accesso al programma "{$a->program_fullname}".
';
$string['source_approval_notification_approval_request_subject'] = 'Notifica di richiesta relativa al programma';
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
$string['source_cohort'] = 'Allocazione automatica dei gruppi globali';
$string['source_cohort_allownew'] = 'Consenti allocazione dei gruppi globali';
$string['source_cohort_allownew_desc'] = 'Consente di aggiungere ai programmi nuove fonti _allocazione automatica dei gruppi globali_';
$string['source_cohort_cohortstoallocate'] = 'Alloca gruppi globali';
$string['source_extdb'] = 'Allocazione da database esterno';
$string['source_extdb_allownew'] = 'Consenti sincronizzazione con database esterno';
$string['source_extdb_allownew_desc'] = 'La sincronizzazione con database esterno richiede la selezione di una query predefinita.
Per utilizzare questa fonte di allocazione, gli amministratori del sito devono configurare il server del database esterno e le query in _Amministrazione del sito / Server / Database esterni_.';
$string['source_extdb_archiveremoved'] = 'Archivia utenti mancanti';
$string['source_extdb_lastsync'] = 'Ultimo aggiornamento delle allocazioni';
$string['source_extdb_pendingsync'] = 'Aggiornamento delle allocazioni in attesa';
$string['source_extdb_sync'] = 'Elabora allocazioni';
$string['source_extdb_sync_notqueued'] = 'Creazione dell\'elaborazione ad hoc per l\'allocazione da database esterno non riuscita';
$string['source_extdb_sync_queued'] = 'Elaborazione ad hoc per l\'allocazione da database esterno creata';
$string['source_manual'] = 'Allocazione manuale';
$string['source_manual_allocateusers'] = 'Alloca utenti';
$string['source_manual_csvfile'] = 'File CSV';
$string['source_manual_hasheaders'] = 'La prima riga contiene i nomi delle colonne';
$string['source_manual_potusers'] = 'Candidati all\'allocazione';
$string['source_manual_potusersmatching'] = 'Candidati all\'allocazione corrispondenti';
$string['source_manual_result_assigned'] = 'Numero di utenti allocati al programma: {$a}';
$string['source_manual_result_errors'] = 'Numero di errori durante l\'allocazione al programma: {$a}';
$string['source_manual_result_skipped'] = 'Numero di utenti già allocati al programma: {$a}';
$string['source_manual_timeduecolumn'] = 'Colonna della data limite';
$string['source_manual_timeendcolumn'] = 'Colonna della fine del programma';
$string['source_manual_timestartcolumn'] = 'Colonna dell\'inizio del programma';
$string['source_manual_uploadusers'] = 'Carica allocazioni';
$string['source_manual_usercolumn'] = 'Colonna di identificazione dell\'utente';
$string['source_manual_usermapping'] = 'Identifica gli utenti tramite';
$string['source_manual_userupload_allocated'] = 'Allocato a \'{$a}\'';
$string['source_manual_userupload_alreadyallocated'] = 'Già allocato a \'{$a}\'';
$string['source_manual_userupload_invalidprogram'] = 'Impossibile allocare a \'{$a}\'';
$string['source_mucertify'] = 'Certificazioni';
$string['source_mucertify_allownew'] = 'Consenti allocazione tramite certificazioni';
$string['source_mucertify_allownew_desc'] = 'Consente di aggiungere ai programmi nuove fonti _certificazione_';
$string['source_program'] = 'Programma completato';
$string['source_program_allownew'] = 'Consenti allocazione al completamento di un programma';
$string['source_program_allownew_desc'] = 'Consente di aggiungere ai programmi nuove fonti _completamento di un programma_';
$string['source_program_completedprogram'] = 'Programma da completare';
$string['source_selfallocation'] = 'Autoallocazione';
$string['source_selfallocation_allocate'] = 'Aderisci';
$string['source_selfallocation_allownew'] = 'Consenti autoallocazione';
$string['source_selfallocation_allownew_desc'] = 'Consente di aggiungere ai programmi nuove fonti _autoallocazione_';
$string['source_selfallocation_allowsignup'] = 'Consenti nuove adesioni';
$string['source_selfallocation_confirm'] = 'Confermare di voler essere allocati al programma.';
$string['source_selfallocation_enable'] = 'Abilita autoallocazione';
$string['source_selfallocation_key'] = 'Chiave di accesso';
$string['source_selfallocation_keyrequired'] = 'La chiave di accesso è obbligatoria';
$string['source_selfallocation_maxusers'] = 'Numero massimo di utenti';
$string['source_selfallocation_maxusers_status'] = 'Utenti {$a->count}/{$a->max}';
$string['source_selfallocation_maxusersreached'] = 'È già stato raggiunto il numero massimo di utenti autoallocati';
$string['source_selfallocation_signupallowed'] = 'Le adesioni sono consentite';
$string['source_selfallocation_signupnotallowed'] = 'Le adesioni non sono consentite';
$string['taballocation'] = 'Impostazioni di allocazione';
$string['tabcontent'] = 'Contenuto';
$string['tabgeneral'] = 'Generale';
$string['tabusers'] = 'Utenti';
$string['tabvisibility'] = 'Visibilità nel catalogo';
$string['tagarea_tool_muprog_program'] = 'Programmi';
$string['task_extdb_cron'] = 'Cron dell\'allocazione ai programmi da database esterno';
$string['task_extdb_sync'] = 'Allocazione ai programmi da database esterno';
$string['taskcertificate'] = 'Cron del rilascio dei certificati dei programmi';
$string['taskcron'] = 'Cron del plugin Programmi';
$string['unlinkeditems'] = 'Elementi non collegati';
$string['updateattendance'] = 'Modifica partecipazione in presenza';
$string['updatecourse'] = 'Modifica corso';
$string['updatecredits'] = 'Modifica crediti';
$string['updatescheduling'] = 'Modifica pianificazione';
$string['updateset'] = 'Modifica set di corsi';
$string['updatesource'] = 'Modifica {$a}';
$string['upload'] = 'Carica programmi';
$string['upload_files'] = 'File';
$string['upload_files_error'] = 'Sono attesi più file CSV, un file JSON oppure un archivio Zip';
$string['upload_invalidcount'] = 'Record non validi';
$string['upload_preview'] = 'Anteprima dei dati';
$string['upload_status'] = 'Stato';
$string['upload_status_invalid'] = 'Non valido';
$string['upload_targetcontext'] = 'Aggiungi programmi nel contesto';
$string['upload_uploadcount'] = 'Programmi da caricare';
$string['upload_usecategory'] = 'Usa la colonna categoria per i contesti';
$string['userupload_completion_error'] = 'Il completamento del programma non può essere aggiornato';
$string['userupload_completion_updated'] = 'Il completamento del programma è stato aggiornato';
