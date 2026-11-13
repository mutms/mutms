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
 * Strings for component 'tool_mutenancy', language 'it'.
 *
 * NOTE: This Italian translation was produced automatically by an AI language
 * model and has not yet been reviewed by a human translator. Corrections are
 * welcome.
 *
 * @package    tool_mutenancy
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['associate'] = 'Utente associato';
$string['associate_add'] = 'Associa utenti';
$string['associate_add_info'] = 'Gli utenti che non appartengono al tenant possono essere associati al tenant aggiungendoli al gruppo globale degli utenti associati.
Gli utenti che non appartengono a un tenant possono essere associati a più tenant. Più tenant possono condividere lo stesso gruppo globale degli utenti associati.';
$string['associate_cohort'] = 'Gruppo globale degli utenti associati';
$string['associate_cohort_create'] = 'Crea gruppo globale degli utenti associati';
$string['associate_cohort_create_help'] = 'Selezionare per creare un nuovo gruppo globale per gli utenti associati al tenant.';
$string['associate_cohort_help'] = 'Selezionare il gruppo globale che contiene gli utenti globali che devono partecipare al tenant.

Gli utenti globali possono essere associati a più tenant, mentre gli account di un tenant non possono essere associati a nessun altro tenant.';
$string['associate_remove'] = 'Annulla associazione utente';
$string['associate_remove_info'] = 'L\'associazione degli utenti al tenant viene annullata rimuovendoli dal gruppo globale degli utenti associati.
L\'operazione può interessare più tenant quando i tenant condividono lo stesso gruppo globale degli utenti associati.';
$string['auth_edit'] = 'Modifica autenticazione';
$string['boost_edit'] = 'Modifica Boost';
$string['bulk_allocate'] = 'Assegna utenti al tenant';
$string['bulk_allocate_info'] = 'Gli utenti seguenti verranno assegnati al tenant selezionato:

{$a}

Le assegnazioni come manager del tenant verranno rimosse e gli utenti perderanno l\'accesso a tutti gli altri tenant. Gli account degli amministratori non verranno modificati.';
$string['bulk_deallocate'] = 'Annulla assegnazione dei membri al tenant';
$string['bulk_deallocate_info'] = 'L\'assegnazione ai tenant degli utenti seguenti verrà annullata e gli utenti diventeranno utenti normali:

{$a}';
$string['cachedef_config'] = 'Cache della configurazione dei tenant';
$string['cachedef_tenant'] = 'Cache dei record dei tenant';
$string['config_default'] = 'Valore di default';
$string['config_default_value'] = 'Valore di default ({$a})';
$string['config_override'] = 'Sovrascrivi';
$string['config_override_value'] = 'Sovrascrivi il default ({$a})';
$string['config_value'] = 'Valore';
$string['environment_corepatch_error'] = 'La patch per la multi-tenancy richiesta è assente oppure la sua versione non è valida';
$string['environment_corepatch_ok'] = 'È stata rilevata la versione corretta della patch';
$string['error:changerequired'] = 'È necessaria una modifica';
$string['error:memberlimitreached'] = 'È stato raggiunto il limite di membri';
$string['event_appearance_updated'] = 'Aspetto del tenant aggiornato';
$string['event_auth_updated'] = 'Autenticazione del tenant aggiornata';
$string['event_tenant_archived'] = 'Tenant archiviato';
$string['event_tenant_created'] = 'Tenant creato';
$string['event_tenant_deleted'] = 'Tenant eliminato';
$string['event_tenant_restored'] = 'Tenant ripristinato';
$string['event_tenant_updated'] = 'Tenant aggiornato';
$string['event_user_allocated'] = 'Assegnazione dell\'utente al tenant modificata';
$string['login_tenant_select'] = 'Seleziona sito';
$string['login_tenant_select_a'] = 'Seleziona: {$a}';
$string['logos_edit'] = 'Modifica loghi';
$string['member_confirm_info'] = 'Nella maggior parte dei casi gli account dei membri non dovrebbero essere confermati da qui.

Se l\'utente non ha ricevuto l\'email di conferma, si consiglia di usare l\'opzione *Invia di nuovo email di conferma*.';
$string['member_create'] = 'Crea account';
$string['member_create_info'] = 'Il nuovo account di membro del tenant potrà accedere ai corsi del proprio tenant e, se consentito, anche ai corsi globali.

Non potrà accedere ai corsi di nessun altro tenant.';
$string['member_delete_info'] = 'L\'eliminazione dell\'account di un membro del tenant non è reversibile.';
$string['member_managers_info'] = 'I manager del tenant non sono automaticamente visibili nei tenant. Se devono partecipare al tenant,
vanno aggiunti al gruppo globale degli utenti associati.';
$string['member_resend_info'] = 'Le email di conferma dei nuovi account chiedono agli utenti di dimostrare
di essere titolari dell\'indirizzo email indicato durante la registrazione.

Si tratta di un\'importante funzione di sicurezza, la conferma manuale dei nuovi account non è consigliata.';
$string['member_suspend_info'] = 'La sospensione dell\'account è stata progettata per impedire agli utenti di effettuare il login e
dovrebbe inoltre bloccare tutte le email e le notifiche in uscita, nient\'altro.

Purtroppo utenti e sviluppatori spesso fraintendono la sospensione dell\'account, il che può causare un comportamento
incoerente di alcuni plugin e, in rari casi, una perdita di dati di lieve entità.';
$string['member_unlock_info'] = 'Gli account utente vengono bloccati dopo ripetuti tentativi di login falliti. Sbloccando l\'account l\'utente potrà riprovare.';
$string['member_unsuspend_info'] = 'Dopo la riattivazione dell\'account l\'utente dovrebbe poter effettuare di nuovo il login
e ricomincerà a ricevere email e notifiche.';
$string['member_update'] = 'Modifica account';
$string['mutenancy:admin'] = 'Amministrare i tenant';
$string['mutenancy:allocate'] = 'Assegnare utenti ai tenant';
$string['mutenancy:configappearance'] = 'Configurare tema e branding del tenant';
$string['mutenancy:configauth'] = 'Configurare l\'autenticazione del tenant';
$string['mutenancy:membercreate'] = 'Creare account per un membro del tenant';
$string['mutenancy:memberdelete'] = 'Eliminare l\'account di un membro del tenant';
$string['mutenancy:memberupdate'] = 'Modificare l\'account di un membro del tenant';
$string['mutenancy:switch'] = 'Passare a un tenant';
$string['mutenancy:view'] = 'Visualizzare il tenant';
$string['navigation_category'] = 'Categoria del tenant';
$string['navigation_top'] = 'Gestione del tenant';
$string['pluginname'] = 'Multi-tenancy';
$string['privacy:metadata:tool_mutenancy_manager'] = 'Manager dei tenant';
$string['privacy:metadata:tool_mutenancy_manager:tenantid'] = 'Id del tenant';
$string['privacy:metadata:tool_mutenancy_manager:timecreated'] = 'Data di assegnazione del manager';
$string['privacy:metadata:tool_mutenancy_manager:usercreated'] = 'Utente che ha assegnato il manager';
$string['privacy:metadata:tool_mutenancy_manager:userid'] = 'Id utente del manager';
$string['role_tenantmanager_archetype'] = 'ARCHETIPO: Manager del tenant';
$string['role_tenantmanager_description'] = 'Il ruolo Manager del tenant viene assegnato automaticamente a tutti i manager del tenant nei contesti del tenant e della categoria del tenant.

Questo ruolo non va assegnato manualmente, non modificare il nome abbreviato del ruolo.';
$string['role_tenantmanager_name'] = 'Manager del tenant';
$string['role_tenantuser_archetype'] = 'ARCHETIPO: Utente del tenant';
$string['role_tenantuser_description'] = 'Il ruolo Utente del tenant viene assegnato automaticamente a tutti gli utenti del tenant nei contesti della categoria del tenant.

Questo ruolo non va assegnato manualmente, non modificare il nome abbreviato del ruolo.';
$string['role_tenantuser_name'] = 'Utente del tenant';
$string['secondary_tenant_appearance'] = 'Aspetto';
$string['secondary_tenant_auth'] = 'Autenticazione';
$string['secondary_tenant_details'] = 'Dettagli';
$string['secondary_tenant_users'] = 'Utenti';
$string['setting_allowguests'] = 'Consenti l\'accesso degli ospiti ai tenant';
$string['setting_allowguests_desc'] = 'Abilitare questa impostazione per consentire l\'accesso degli ospiti ai corsi dei tenant.

Questa funzione richiede che l\'accesso degli ospiti sia abilitato a livello di sito. Inoltre l\'accesso degli ospiti deve essere abilitato nei corsi dei tenant e gli utenti dovrebbero essere associati ai tenant anziché esserne membri.';
$string['setting_tenantentities'] = 'Nome dell\'entità tenant al plurale';
$string['setting_tenantentities_desc'] = 'Se indicato, la parola al plurale "Tenant" verrà sostituita dal nome personalizzato fornito. Ad esempio: Unità, Facoltà o Eventi';
$string['setting_tenantentity'] = 'Nome dell\'entità tenant al singolare';
$string['setting_tenantentity_desc'] = 'Se indicato, la parola al singolare "Tenant" verrà sostituita dal nome personalizzato fornito. Ad esempio: Unità, Facoltà o Evento';
$string['setting_tenantlimit'] = 'Limite di tenant';
$string['setting_tenantlimit_desc'] = 'Specifica quanti tenant possono essere creati. I siti con più di 100 tenant possono presentare problemi di prestazioni e di usabilità.';
$string['setting_tenantprimarynav'] = 'Aggiungi la gestione del tenant al menu principale';
$string['setting_tenantprimarynav_desc'] = 'Se abilitata, la sezione di gestione del tenant corrente viene aggiunta al menu principale. È pensata per i manager dedicati a un tenant o per gli utenti che possono passare ai tenant.';
$string['settings'] = 'Impostazioni multi-tenancy';
$string['taskcron'] = 'Pulizia della multi-tenancy';
$string['tenancy_activate'] = 'Attiva multi-tenancy';
$string['tenancy_activate_info'] = 'Durante l\'attivazione della multi-tenancy verranno creati nuovi ruoli per i manager del tenant e gli utenti del tenant.

La multi-tenancy può essere disattivata solo dopo l\'eliminazione di tutti i tenant.';
$string['tenancy_deactivate'] = 'Disattiva multi-tenancy';
$string['tenancy_deactivate_info'] = 'Durante la disattivazione della multi-tenancy il ruolo Manager del tenant verrà eliminato.';
$string['tenant'] = 'Tenant';
$string['tenant_actions'] = 'Azioni';
$string['tenant_archive'] = 'Archivia tenant';
$string['tenant_archive_a'] = 'Archivia: {$a}';
$string['tenant_archive_info'] = 'L\'archiviazione del tenant:

* impedisce ai membri del tenant di effettuare il login e blocca tutte le email e le notifiche in uscita,
* nasconde la categoria del tenant.

L\'archiviazione è un passaggio obbligatorio prima che il tenant possa essere eliminato.';
$string['tenant_archived'] = 'Archiviato';
$string['tenant_associates'] = 'Utenti associati';
$string['tenant_category'] = 'Categoria del tenant';
$string['tenant_categoryidnumber'] = 'Codice identificativo della categoria del tenant';
$string['tenant_categoryname'] = 'Nome della categoria del tenant';
$string['tenant_cohort'] = 'Gruppo globale del tenant';
$string['tenant_cohortidnumber'] = 'Codice identificativo del gruppo globale del tenant';
$string['tenant_cohortname'] = 'Nome del gruppo globale del tenant';
$string['tenant_create'] = 'Aggiungi tenant';
$string['tenant_create_a'] = 'Aggiungi: {$a}';
$string['tenant_delete'] = 'Elimina tenant';
$string['tenant_delete_a'] = 'Elimina: {$a}';
$string['tenant_delete_info'] = 'Durante l\'eliminazione del tenant:

* le autorizzazioni dei manager del tenant verranno revocate
* il gruppo globale del tenant verrà convertito in un normale gruppo globale
* tutti i membri del tenant verranno assegnati al sito globale

Le sottocategorie, i corsi e i dati degli utenti non verranno modificati.';
$string['tenant_delete_movetotenant'] = 'Assegna i membri a';
$string['tenant_idnumber'] = 'ID del tenant';
$string['tenant_loginshow'] = 'Visualizza il tenant nella pagina di login';
$string['tenant_loginurl'] = 'URL di login del tenant';
$string['tenant_loginurl_copy'] = 'Copia l\'URL di login del tenant negli appunti';
$string['tenant_manager'] = 'Manager del tenant';
$string['tenant_managers'] = 'Manager del tenant';
$string['tenant_member'] = 'Membro del tenant';
$string['tenant_member_a'] = 'Membro: {$a}';
$string['tenant_memberlimit'] = 'Limite di membri del tenant';
$string['tenant_memberlimit_help'] = 'Specifica il numero massimo di account di membri del tenant che possono essere creati nel tenant.

Gli utenti associati non vengono conteggiati ai fini di questo limite.';
$string['tenant_name'] = 'Nome del tenant';
$string['tenant_restore'] = 'Ripristina tenant archiviato';
$string['tenant_restore_a'] = 'Ripristina dall\'archivio: {$a}';
$string['tenant_restore_info'] = 'Il ripristino del tenant dovrebbe annullare la maggior parte delle modifiche apportate durante l\'archiviazione del tenant.

Si consiglia tuttavia di verificare in seguito tutte le impostazioni del tenant e la visibilità della categoria.';
$string['tenant_sitefullname'] = 'Nome del sito del tenant';
$string['tenant_siteshortname'] = 'Nome abbreviato del sito del tenant';
$string['tenant_switch'] = 'Cambia tenant';
$string['tenant_switch_a'] = 'Cambia: {$a}';
$string['tenant_switch_info'] = 'Il passaggio a un tenant cambia l\'aspetto del sito e limita l\'elenco degli utenti candidati disponibili per alcune azioni.

Passare a un tenant non equivale a essere membro del tenant, ai membri del tenant si applicano ulteriori restrizioni.';
$string['tenant_switch_my'] = 'I miei tenant';
$string['tenant_switch_my_a'] = 'Personali: {$a}';
$string['tenant_switch_notenant'] = 'Nessun tenant';
$string['tenant_switch_notenant_a'] = 'Senza: {$a}';
$string['tenant_switch_other'] = 'Altri tenant';
$string['tenant_switch_other_a'] = 'Altro: {$a}';
$string['tenant_update'] = 'Modifica tenant';
$string['tenant_update_a'] = 'Modifica: {$a}';
$string['tenant_users'] = 'Utenti del tenant';
$string['tenant_users_a'] = 'Utenti: {$a}';
$string['tenants'] = 'Tenant';
$string['user_allocate'] = 'Assegna utente';
$string['user_allocate_info'] = 'Quando un utente viene assegnato a un tenant, le sue assegnazioni come manager del tenant vengono rimosse
e l\'utente perde l\'accesso a tutti gli altri tenant.';
$string['user_tenants'] = 'Associato ai tenant';
$string['user_tenants_a'] = 'Associato a: {$a}';
