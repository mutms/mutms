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
 * Strings for component 'tool_muprog', language 'cs'.
 *
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['allocation'] = 'Přidělení';
$string['allocation_archive'] = 'Archivovat přidělení';
$string['allocation_reset'] = 'Resetovat průběh programu';
$string['allocation_reset_updateallocation'] = 'Upravit přidělení';
$string['allocation_restore'] = 'Obnovit přidělení';
$string['allocation_update'] = 'Upravit přidělení';
$string['allocationdate'] = 'Datum přidělení';
$string['allocationend'] = 'Konec přidělování';
$string['allocationend_help'] = 'Význam data konce přidělování závisí na povolených zdrojích přidělení. Pokud je datum zadané, po tomto datu obvykle nelze vytvářet nová přidělení.';
$string['allocations'] = 'Přidělení';
$string['allocationsources'] = 'Zdroje přidělení';
$string['allocationstart'] = 'Začátek přidělování';
$string['allocationstart_help'] = 'Význam data začátku přidělování závisí na povolených zdrojích přidělení. Pokud je datum zadané, nová přidělení lze obvykle vytvářet až po tomto datu.';
$string['allprograms'] = 'Všechny programy';
$string['appendinto'] = 'Připojit do položky';
$string['appenditem'] = 'Připojit položku';
$string['archived'] = 'Archivováno';
$string['attendance'] = 'Prezenční docházka';
$string['attendance_effective'] = 'Datum účinnosti';
$string['attendance_status'] = 'Stav';
$string['attendance_status_completed'] = 'Dokončeno';
$string['attendance_status_failed'] = 'Nesplněno';
$string['attendance_status_noshow'] = 'Neúčast';
$string['attendance_take'] = 'Zadat docházku';
$string['calendarprogramdue'] = '{$a} má termín splnění';
$string['calendarprogramend'] = '{$a} končí';
$string['calendarprogramstart'] = '{$a} začíná';
$string['catalogue'] = 'Katalog programů';
$string['catalogue_actions'] = 'Akce katalogu';
$string['catalogue_dofilter'] = 'Hledat';
$string['catalogue_resetfilter'] = 'Vymazat';
$string['catalogue_searchtext'] = 'Hledaný text';
$string['catalogue_tag'] = 'Filtrovat podle štítku';
$string['certificatetemplatechoose'] = 'Vybrat šablonu...';
$string['cohorts'] = 'Viditelné pro globální skupiny';
$string['cohorts_help'] = 'Neveřejné programy lze zpřístupnit členům vybraných globálních skupin.

Nastavení viditelnosti nemá vliv na již přidělené programy.';
$string['columnusedalready'] = 'Sloupec je již použit';
$string['completiondate'] = 'Datum dokončení';
$string['completiondelay'] = 'Odklad dokončení';
$string['completionoverride'] = 'Přepsat dokončení';
$string['creategroups'] = 'Skupiny v kurzech';
$string['creategroups_help'] = 'Pokud je zapnuto, v každém kurzu přidaném do programu se vytvoří skupina a všichni přidělení uživatelé se do ní přidají jako její členové.';
$string['credits'] = 'Kredity';
$string['credits_progress'] = 'Aktuální kredity: {$a->current}/{$a->total}';
$string['credits_requiredcredits'] = 'Požadované kredity: {$a}';
$string['currentcontextonly'] = 'Vyloučit podkategorie';
$string['customfields'] = 'Vlastní pole programů';
$string['customfields_allocation'] = 'Vlastní pole přidělení do programu';
$string['customfieldsettings'] = 'Společná nastavení vlastních polí programů';
$string['customfieldvisible:allocated'] = 'Uživatelé přidělení do programů';
$string['customfieldvisible:allocatee'] = 'Přidělený uživatel';
$string['customfieldvisible:everyone'] = 'Všichni, kdo mohou vidět ostatní podrobnosti programu';
$string['customfieldvisible:viewcapability'] = 'Uživatelé s pravomocí zobrazit programy';
$string['customfieldvisibleto'] = 'Obsah pole mohou vidět';
$string['deleteallocation'] = 'Odstranit přidělení do programu';
$string['deleteattendance'] = 'Odstranit prezenční docházku';
$string['deletecourse'] = 'Odebrat kurz';
$string['deletecredits'] = 'Odebrat kredity';
$string['deleteset'] = 'Odstranit sadu';
$string['duedate'] = 'Termín splnění';
$string['enrolrole'] = 'Role v kurzu';
$string['enrolrole_desc'] = 'Vyberte roli, kterou budou programy používat při zápisu do kurzů';
$string['errorcontentproblem'] = 'Ve struktuře obsahu programu byl zjištěn problém, dokončení programu nebude správně sledováno!';
$string['errorcoursemissing'] = 'Kurz chybí';
$string['errorcoursesmissing'] = 'Chybějící kurzy: {$a}';
$string['errordifferenttenant'] = 'K programu jiného klienta nelze přistupovat';
$string['errorinvalidoverridedates'] = 'Neplatná přepsaná data';
$string['errornoallocation'] = 'Program není přidělen';
$string['errornoallocations'] = 'Nebyla nalezena žádná přidělení uživatelů';
$string['errornomyprograms'] = 'Nejste přiděleni do žádného programu.';
$string['errornoprograms'] = 'Nebyly nalezeny žádné programy.';
$string['errornorequests'] = 'Nebyly nalezeny žádné žádosti o program';
$string['errornotenabled'] = 'Plugin Programy není zapnutý';
$string['event_allocation_archived'] = 'Přidělení uživatele archivováno';
$string['event_allocation_completed'] = 'Uživatel dokončil program';
$string['event_allocation_created'] = 'Uživatel přidělen do programu';
$string['event_allocation_deleted'] = 'Uživateli odebráno přidělení do programu';
$string['event_allocation_restored'] = 'Přidělení uživatele obnoveno';
$string['event_allocation_updated'] = 'Přidělení uživatele do programu upraveno';
$string['event_allocation_viewed'] = 'Přidělení do programu zobrazeno';
$string['event_catalogue_program_viewed'] = 'Katalog programů zobrazen';
$string['event_program_archived'] = 'Program archivován';
$string['event_program_created'] = 'Program vytvořen';
$string['event_program_deleted'] = 'Program odstraněn';
$string['event_program_restored'] = 'Program obnoven';
$string['event_program_updated'] = 'Program upraven';
$string['event_program_viewed'] = 'Program zobrazen';
$string['evidence'] = 'Jiný doklad';
$string['evidence_details'] = 'Podrobnosti';
$string['evidence_detailsdefault'] = 'Výchozí podrobnosti';
$string['evidencedate'] = 'Datum dokončení podle dokladu';
$string['evidenceupdate'] = 'Upravit jiný doklad';
$string['evidenceupload'] = 'Nahrát doklady o dokončení';
$string['evidenceupload_csvfile'] = 'Soubor CSV';
$string['evidenceupload_errors'] = 'Počet neplatných řádků: {$a}';
$string['evidenceupload_skipped'] = 'Počet přeskočených řádků: {$a}';
$string['evidenceupload_updated'] = 'Doklady o dokončení byly aktualizovány. Počet uživatelů: {$a}';
$string['export'] = 'Exportovat programy';
$string['exportfile_info'] = 'info';
$string['exportfile_programs'] = 'programy';
$string['exportformat'] = 'Formát souboru';
$string['exportformat_csv'] = 'CSV';
$string['exportformat_json'] = 'JSON';
$string['extdb_query_allocation'] = 'Přidělení do programu';
$string['favourite'] = 'Program označený hvězdičkou';
$string['fixeddate'] = 'K pevnému datu';
$string['importallocationend'] = 'Konec přidělování ({$a})';
$string['importallocationstart'] = 'Začátek přidělování ({$a})';
$string['importprogramallocation'] = 'Importovat nastavení přidělení';
$string['importprogramallocationconfirmation'] = 'Importujete nastavení přidělení z programu __{$a->fullname} / {$a->idnumber} / {$a->category}__.

Vyberte všechna nastavení, která chcete importovat.';
$string['importprogramcontent'] = 'Importovat obsah programu';
$string['importprogramcontentconfirmation'] = 'Importujete obsah z programu __{$a->fullname} / {$a->idnumber} / {$a->category}__.';
$string['importprogramdue'] = 'Termín splnění programu ({$a})';
$string['importprogramend'] = 'Konec programu ({$a})';
$string['importprogramstart'] = 'Začátek programu ({$a})';
$string['importselectprogram'] = 'Vybrat program';
$string['includesubcontexts'] = 'Zahrnout podřízené kontexty';
$string['invalidallocationdates'] = 'Neplatná data přidělení do programu';
$string['invalidcompletiondate'] = 'Neplatné datum dokončení programu';
$string['item'] = 'Položka';
$string['item_create_attendance'] = 'Přidat prezenční docházku';
$string['item_create_course'] = 'Přidat kurzy';
$string['item_create_credits'] = 'Přidat kredity';
$string['item_create_set'] = 'Přidat sadu';
$string['item_type'] = 'Typ položky';
$string['item_update_attendance'] = 'Upravit prezenční docházku';
$string['itemcompletion'] = 'Dokončení položky programu';
$string['itempoints'] = 'Body';
$string['itemrecalculate'] = 'Přepočítat dokončení položky';
$string['management'] = 'Správa programů';
$string['management_index_actions'] = 'Akce programů';
$string['management_program_allocation_actions'] = 'Akce nastavení přidělení';
$string['management_program_general_actions'] = 'Akce programu';
$string['management_program_users_actions'] = 'Akce uživatelů';
$string['messageprovider:allocation_notification'] = 'Oznámení o přidělení do programu';
$string['messageprovider:approval_reject_notification'] = 'Oznámení o zamítnutí žádosti o program';
$string['messageprovider:approval_request_notification'] = 'Oznámení o žádosti o schválení programu';
$string['messageprovider:cc_supervisor_notification'] = 'Kopie oznámení o programech podřízených';
$string['messageprovider:completion_notification'] = 'Oznámení o dokončení programu';
$string['messageprovider:deallocation_notification'] = 'Oznámení o odebrání přidělení do programu';
$string['messageprovider:due_notification'] = 'Oznámení o programu po termínu';
$string['messageprovider:duesoon_notification'] = 'Oznámení o blížícím se termínu splnění programu';
$string['messageprovider:endcompleted_notification'] = 'Oznámení o skončení dokončeného programu';
$string['messageprovider:endfailed_notification'] = 'Oznámení o skončení nedokončeného programu';
$string['messageprovider:endsoon_notification'] = 'Oznámení o blížícím se konci programu';
$string['messageprovider:reset_notification'] = 'Oznámení o resetování programu';
$string['messageprovider:start_notification'] = 'Oznámení o začátku programu';
$string['moveafter'] = 'Přesunout „{$a->item}“ za „{$a->target}“';
$string['movebefore'] = 'Přesunout „{$a->item}“ před „{$a->target}“';
$string['moveinto'] = 'Přesunout „{$a->item}“ do „{$a->target}“';
$string['moveitem'] = 'Přesunout položku';
$string['moveitemcancel'] = 'Zrušit přesun';
$string['muprog:addcourse'] = 'Přidat kurz do programů';
$string['muprog:addtocertifications'] = 'Přidat program do certifikací';
$string['muprog:admin'] = 'Pokročilá správa programů';
$string['muprog:allocate'] = 'Přidělit uživatele do programů';
$string['muprog:clone'] = 'Klonovat obsah a nastavení programu';
$string['muprog:configurecustomfields'] = 'Konfigurovat vlastní pole programů';
$string['muprog:deallocate'] = 'Odebrat přidělení uživatelů do programů';
$string['muprog:delete'] = 'Odstranit programy';
$string['muprog:edit'] = 'Přidat a upravit programy';
$string['muprog:export'] = 'Exportovat programy';
$string['muprog:manageallocation'] = 'Správa přidělení uživatelů';
$string['muprog:manageevidence'] = 'Správa jiných dokladů o dokončení';
$string['muprog:reset'] = 'Resetovat průběh programu';
$string['muprog:takeattendance'] = 'Zadat prezenční docházku';
$string['muprog:upload'] = 'Nahrát programy';
$string['muprog:view'] = 'Zobrazit správu programů';
$string['muprog:viewcatalogue'] = 'Přístup ke katalogu programů';
$string['muprog:viewuserprograms'] = 'Zobrazit programy jiných uživatelů';
$string['myprograms'] = 'Moje programy';
$string['notification_allocation'] = 'Uživatel přidělen';
$string['notification_allocation_body'] = 'Dobrý den, {$a->user_fullname},

byli jste přiděleni do programu „{$a->program_fullname}“, datum začátku je {$a->program_startdate}.
';
$string['notification_allocation_description'] = 'Oznámení odesílané uživatelům, když jsou přiděleni do programu.';
$string['notification_allocation_subject'] = 'Oznámení o přidělení do programu';
$string['notification_cc_supervisor_body'] = 'Dobrý den, {$a->supervisor_fullname},

následujícímu uživateli bylo odesláno oznámení:

* {$a->subordinate_title}: {$a->user_fullname}
* Typ oznámení: {$a->notification_name}
* Související program: {$a->program_fullname}

';
$string['notification_cc_supervisor_subject'] = '{$a->supervisor_title}: oznámení - {$a->program_fullname}';
$string['notification_completion'] = 'Program dokončen';
$string['notification_completion_body'] = 'Dobrý den, {$a->user_fullname},

dokončili jste program „{$a->program_fullname}“.
';
$string['notification_completion_description'] = 'Oznámení odesílané uživatelům, když dokončí svůj program.';
$string['notification_completion_subject'] = 'Program dokončen';
$string['notification_deallocation'] = 'Přidělení uživatele odebráno';
$string['notification_deallocation_body'] = 'Dobrý den, {$a->user_fullname},

bylo vám odebráno přidělení do programu „{$a->program_fullname}“.
';
$string['notification_deallocation_description'] = 'Oznámení odesílané uživatelům, když je jim odebráno přidělení do programu.';
$string['notification_deallocation_subject'] = 'Oznámení o odebrání přidělení do programu';
$string['notification_due'] = 'Program po termínu';
$string['notification_due_body'] = 'Dobrý den, {$a->user_fullname},

dokončení programu „{$a->program_fullname}“ se očekávalo do {$a->program_duedate}.
';
$string['notification_due_description'] = 'Oznámení odesílané uživatelům, když je dokončení jejich programu po termínu.';
$string['notification_due_subject'] = 'Dokončení programu je po termínu';
$string['notification_duesoon'] = 'Blíží se termín splnění programu';
$string['notification_duesoon_body'] = 'Dobrý den, {$a->user_fullname},

dokončení programu „{$a->program_fullname}“ se očekává {$a->program_duedate}.
';
$string['notification_duesoon_description'] = 'Oznámení odesílané uživatelům před datem dokončení jejich programu, pokud už program není dokončený.';
$string['notification_duesoon_subject'] = 'Brzy se očekává dokončení programu';
$string['notification_endcompleted'] = 'Dokončený program skončil';
$string['notification_endcompleted_body'] = 'Dobrý den, {$a->user_fullname},

program „{$a->program_fullname}“ skončil, dokončili jste ho už dříve.
';
$string['notification_endcompleted_description'] = 'Oznámení odesílané uživatelům, když jejich dokončený program skončí.';
$string['notification_endcompleted_subject'] = 'Dokončený program skončil';
$string['notification_endfailed'] = 'Nedokončený program skončil';
$string['notification_endfailed_body'] = 'Dobrý den, {$a->user_fullname},

program „{$a->program_fullname}“ skončil, nepodařilo se vám ho dokončit.
';
$string['notification_endfailed_description'] = 'Oznámení odesílané uživatelům, když skončí program, který se jim nepodařilo dokončit.';
$string['notification_endfailed_subject'] = 'Nedokončený program skončil';
$string['notification_endsoon'] = 'Blíží se konec programu';
$string['notification_endsoon_body'] = 'Dobrý den, {$a->user_fullname},

program „{$a->program_fullname}“ končí {$a->program_enddate}.
';
$string['notification_endsoon_description'] = 'Oznámení odesílané uživatelům před datem konce jejich programu, pokud už program není dokončený.';
$string['notification_endsoon_subject'] = 'Program brzy končí';
$string['notification_reset'] = 'Průběh uživatele resetován';
$string['notification_reset_body'] = 'Dobrý den, {$a->user_fullname},

váš průběh v programu „{$a->program_fullname}“ byl resetován.
';
$string['notification_reset_description'] = 'Oznámení odesílané uživatelům, když je jejich průběh v programu resetován.';
$string['notification_reset_subject'] = 'Oznámení o resetování programu';
$string['notification_start'] = 'Program začal';
$string['notification_start_body'] = 'Dobrý den, {$a->user_fullname},

program „{$a->program_fullname}“ začal.
';
$string['notification_start_description'] = 'Oznámení odesílané uživatelům, když jejich program začal.';
$string['notification_start_subject'] = 'Program začal';
$string['notificationdates'] = 'Data oznámení';
$string['notset'] = 'Nenastaveno';
$string['plugindisabled'] = 'Plugin pro zápis do programu je vypnutý, programy nebudou fungovat.

[Zapnout plugin]({$a->url})';
$string['pluginname'] = 'Programy';
$string['pluginname_desc'] = 'Programy umožňují vytvářet sady kurzů.';
$string['privacy:metadata:field:allocationid'] = 'ID přidělení do programu';
$string['privacy:metadata:field:archived'] = 'Zda je záznam archivovaný';
$string['privacy:metadata:field:createdby'] = 'Kdo doklad vytvořil';
$string['privacy:metadata:field:datajson'] = 'Informace o žádosti';
$string['privacy:metadata:field:evidencejson'] = 'Informace o dokladu o dokončení';
$string['privacy:metadata:field:explanation'] = 'Vysvětlení';
$string['privacy:metadata:field:issueid'] = 'ID vydání';
$string['privacy:metadata:field:itemid'] = 'ID položky';
$string['privacy:metadata:field:programid'] = 'ID programu';
$string['privacy:metadata:field:quantity'] = 'Množství';
$string['privacy:metadata:field:reason'] = 'Důvod';
$string['privacy:metadata:field:rejectedby'] = 'Kdo žádost zamítl';
$string['privacy:metadata:field:sourcedatajson'] = 'Informace o zdroji přidělení';
$string['privacy:metadata:field:sourceid'] = 'Zdroj přidělení';
$string['privacy:metadata:field:status'] = 'Stav';
$string['privacy:metadata:field:takenby'] = 'Kdo zadal docházku';
$string['privacy:metadata:field:timeallocated'] = 'Datum přidělení do programu';
$string['privacy:metadata:field:timecompleted'] = 'Datum dokončení';
$string['privacy:metadata:field:timecreated'] = 'Datum vytvoření';
$string['privacy:metadata:field:timedue'] = 'Termín splnění';
$string['privacy:metadata:field:timeeffective'] = 'Datum účinnosti';
$string['privacy:metadata:field:timeend'] = 'Datum konce';
$string['privacy:metadata:field:timerejected'] = 'Datum zamítnutí';
$string['privacy:metadata:field:timerequested'] = 'Datum žádosti';
$string['privacy:metadata:field:timestart'] = 'Datum začátku';
$string['privacy:metadata:field:userid'] = 'ID uživatele';
$string['privacy:metadata:table:tool_muprog_allocation'] = 'Informace o přiděleních do programů';
$string['privacy:metadata:table:tool_muprog_attendance'] = 'Prezenční docházka';
$string['privacy:metadata:table:tool_muprog_cert_issue'] = 'Certifikáty vydané k přidělením do programu';
$string['privacy:metadata:table:tool_muprog_completion'] = 'Dokončení přidělení do programu';
$string['privacy:metadata:table:tool_muprog_evidence'] = 'Informace o jiných dokladech o dokončení';
$string['privacy:metadata:table:tool_muprog_request'] = 'Informace o žádosti o přidělení';
$string['program'] = 'Program';
$string['program_actions'] = 'Akce programu';
$string['program_allocations_edit'] = 'Upravit přidělení';
$string['program_archive'] = 'Archivovat program';
$string['program_archive_info'] = 'Archivace programu:

* pozastaví zápisy v kurzech programu,
* odstraní události programu z kalendáře,
* zabrání úpravám programu,
* a obecně skryje program před běžnými uživateli.

Archivace je povinný krok před odstraněním programu.';
$string['program_create'] = 'Přidat program';
$string['program_delete'] = 'Odstranit program';
$string['program_delete_info'] = 'Při odstraňování programu se smažou všechna jeho data a uživatelé budou vyškrtnuti z kurzů programu.';
$string['program_move'] = 'Přesunout program';
$string['program_restore'] = 'Obnovit program';
$string['program_restore_info'] = 'Obnovení programu vrátí změny provedené při jeho archivaci.

Poté ale doporučujeme zkontrolovat všechna nastavení programu a přidělené uživatele.';
$string['program_update'] = 'Upravit program';
$string['programallocations'] = 'Přidělení do programu';
$string['programautofix'] = 'Automaticky opravit program';
$string['programcategory'] = 'Kategorie programu';
$string['programcompletion'] = 'Datum dokončení programu';
$string['programcompletionoverride'] = 'Přepsat dokončení programu';
$string['programdue'] = 'Termín splnění programu';
$string['programdue_date'] = 'Termín splnění';
$string['programdue_delay'] = 'Termín splnění po začátku';
$string['programdue_help'] = 'Termín splnění programu určuje, kdy se očekává, že uživatelé program dokončí.';
$string['programend'] = 'Konec programu';
$string['programend_date'] = 'Datum konce programu';
$string['programend_delay'] = 'Konec po začátku';
$string['programend_help'] = 'Po konci programu nemohou uživatelé vstupovat do kurzů programu.';
$string['programidnumber'] = 'ID programu';
$string['programimage'] = 'Obrázek programu';
$string['programname'] = 'Název programu';
$string['programprogress'] = 'Průběh';
$string['programs'] = 'Programy';
$string['programstart'] = 'Začátek programu';
$string['programstart_allocation'] = 'Začít ihned po přidělení';
$string['programstart_date'] = 'Datum začátku programu';
$string['programstart_delay'] = 'Odložit začátek po přidělení';
$string['programstart_help'] = 'Před začátkem programu nemohou uživatelé vstupovat do kurzů programu.';
$string['programstatus'] = 'Stav programu';
$string['programstatus_any'] = 'Jakýkoli stav programu';
$string['programstatus_archived'] = 'Archivovaný';
$string['programstatus_archivedcompleted'] = 'Archivovaný dokončený';
$string['programstatus_completed'] = 'Dokončený';
$string['programstatus_failed'] = 'Neúspěšný';
$string['programstatus_future'] = 'Zatím neotevřený';
$string['programstatus_open'] = 'Otevřený';
$string['programstatus_overdue'] = 'Po termínu';
$string['programurl'] = 'URL programu';
$string['publicaccess'] = 'Veřejný';
$string['publicaccess_help'] = 'Veřejné programy jsou viditelné pro všechny uživatele.

Nastavení viditelnosti nemá vliv na již přidělené programy.';
$string['purchaseaccess'] = 'Koupit přístup';
$string['resettype'] = 'Typ resetování';
$string['resettype_deallocate'] = 'Pouze odebrání přidělení do programu';
$string['resettype_full'] = 'Úplné vymazání kurzů';
$string['resettype_none'] = 'Žádný';
$string['resettype_standard'] = 'Standardní vymazání kurzů';
$string['scheduling'] = 'Plánování';
$string['sequencetype'] = 'Typ dokončení';
$string['sequencetype_allinanyorder'] = 'Vše v libovolném pořadí';
$string['sequencetype_allinorder'] = 'Vše v daném pořadí';
$string['sequencetype_atleast'] = 'Alespoň {$a->min}';
$string['sequencetype_minpoints'] = 'Minimální počet bodů: {$a->minpoints}';
$string['set'] = 'Sada';
$string['settings'] = 'Nastavení programu';
$string['source'] = 'Zdroj';
$string['source_approval'] = 'Žádosti se schválením';
$string['source_approval_allownew'] = 'Povolit schvalování';
$string['source_approval_allownew_desc'] = 'Povolit přidávání nových zdrojů _žádosti se schválením_ do programů';
$string['source_approval_allowrequest'] = 'Povolit nové žádosti';
$string['source_approval_confirm'] = 'Potvrďte, že chcete požádat o přidělení do programu.';
$string['source_approval_daterejected'] = 'Datum zamítnutí';
$string['source_approval_daterequested'] = 'Datum žádosti';
$string['source_approval_makerequest'] = 'Požádat o přístup';
$string['source_approval_notification_approval_reject_body'] = 'Dobrý den, {$a->user_fullname},

vaše žádost o přístup k programu „{$a->program_fullname}“ byla zamítnuta.

{$a->reason}
';
$string['source_approval_notification_approval_reject_subject'] = 'Oznámení o zamítnutí žádosti o program';
$string['source_approval_notification_approval_request_body'] = '
Uživatel {$a->user_fullname} požádal o přístup k programu „{$a->program_fullname}“.
';
$string['source_approval_notification_approval_request_subject'] = 'Oznámení o žádosti o program';
$string['source_approval_rejectionreason'] = 'Důvod zamítnutí';
$string['source_approval_request'] = 'Žádost';
$string['source_approval_requestallowed'] = 'Žádosti jsou povoleny';
$string['source_approval_requestapprove'] = 'Schválit žádost';
$string['source_approval_requestdelete'] = 'Odstranit žádost';
$string['source_approval_requestnotallowed'] = 'Žádosti nejsou povoleny';
$string['source_approval_requestpending'] = 'Žádost o přístup čeká na vyřízení';
$string['source_approval_requestreject'] = 'Zamítnout žádost';
$string['source_approval_requestrejected'] = 'Žádost o přístup byla zamítnuta';
$string['source_approval_requests'] = 'Žádosti';
$string['source_cohort'] = 'Automatické přidělení z globální skupiny';
$string['source_cohort_allownew'] = 'Povolit přidělení z globálních skupin';
$string['source_cohort_allownew_desc'] = 'Povolit přidávání nových zdrojů _automatické přidělení z globální skupiny_ do programů';
$string['source_cohort_cohortstoallocate'] = 'Přidělit globální skupiny';
$string['source_extdb'] = 'Přidělení z externí databáze';
$string['source_extdb_allownew'] = 'Povolit synchronizaci s externí databází';
$string['source_extdb_allownew_desc'] = 'Synchronizace s externí databází vyžaduje výběr předem definovaného dotazu.
Aby bylo možné tento zdroj přidělení používat, musí správci stránek nakonfigurovat server externí databáze a dotazy v části _Správa stránek / Server / Externí databáze_.';
$string['source_extdb_archiveremoved'] = 'Archivovat chybějící uživatele';
$string['source_extdb_lastsync'] = 'Poslední aktualizace přidělení';
$string['source_extdb_pendingsync'] = 'Aktualizace přidělení čeká na zpracování';
$string['source_extdb_sync'] = 'Zpracovat přidělení';
$string['source_extdb_sync_notqueued'] = 'Nepodařilo se vytvořit ad-hoc úlohu přidělení z externí databáze';
$string['source_extdb_sync_queued'] = 'Ad-hoc úloha přidělení z externí databáze byla vytvořena';
$string['source_manual'] = 'Ruční přidělení';
$string['source_manual_allocateusers'] = 'Přidělit uživatele';
$string['source_manual_csvfile'] = 'Soubor CSV';
$string['source_manual_hasheaders'] = 'První řádek obsahuje názvy sloupců';
$string['source_manual_potusers'] = 'Kandidáti na přidělení';
$string['source_manual_potusersmatching'] = 'Odpovídající kandidáti na přidělení';
$string['source_manual_result_assigned'] = 'Počet uživatelů přidělených do programu: {$a}';
$string['source_manual_result_errors'] = 'Počet chyb při přidělování do programu: {$a}';
$string['source_manual_result_skipped'] = 'Počet uživatelů, kteří již do programu byli přiděleni: {$a}';
$string['source_manual_timeduecolumn'] = 'Sloupec s termínem splnění';
$string['source_manual_timeendcolumn'] = 'Sloupec s koncem programu';
$string['source_manual_timestartcolumn'] = 'Sloupec se začátkem programu';
$string['source_manual_uploadusers'] = 'Nahrát přidělení';
$string['source_manual_usercolumn'] = 'Sloupec s identifikací uživatele';
$string['source_manual_usermapping'] = 'Identifikovat uživatele podle';
$string['source_manual_userupload_allocated'] = 'Přiděleno do programu „{$a}“';
$string['source_manual_userupload_alreadyallocated'] = 'Už přiděleno do programu „{$a}“';
$string['source_manual_userupload_invalidprogram'] = 'Nelze přidělit do programu „{$a}“';
$string['source_mucertify'] = 'Certifikace';
$string['source_mucertify_allownew'] = 'Povolit přidělení z certifikací';
$string['source_mucertify_allownew_desc'] = 'Povolit přidávání nových zdrojů _certifikace_ do programů';
$string['source_program'] = 'Dokončený program';
$string['source_program_allownew'] = 'Povolit přidělení po dokončení programu';
$string['source_program_allownew_desc'] = 'Povolit přidávání nových zdrojů _dokončení programu_ do programů';
$string['source_program_completedprogram'] = 'Program k dokončení';
$string['source_selfallocation'] = 'Samoobslužné přidělení';
$string['source_selfallocation_allocate'] = 'Přihlásit se';
$string['source_selfallocation_allownew'] = 'Povolit samoobslužné přidělení';
$string['source_selfallocation_allownew_desc'] = 'Povolit přidávání nových zdrojů _samoobslužné přidělení_ do programů';
$string['source_selfallocation_allowsignup'] = 'Povolit nová přihlášení';
$string['source_selfallocation_confirm'] = 'Potvrďte, že chcete být přiděleni do programu.';
$string['source_selfallocation_enable'] = 'Zapnout samoobslužné přidělení';
$string['source_selfallocation_key'] = 'Klíč k přihlášení';
$string['source_selfallocation_keyrequired'] = 'Klíč k přihlášení je povinný';
$string['source_selfallocation_maxusers'] = 'Maximální počet uživatelů';
$string['source_selfallocation_maxusers_status'] = 'Uživatelé {$a->count}/{$a->max}';
$string['source_selfallocation_maxusersreached'] = 'Už bylo dosaženo maximálního počtu samoobslužně přidělených uživatelů';
$string['source_selfallocation_signupallowed'] = 'Přihlášení jsou povolena';
$string['source_selfallocation_signupnotallowed'] = 'Přihlášení nejsou povolena';
$string['taballocation'] = 'Nastavení přidělení';
$string['tabcontent'] = 'Obsah';
$string['tabgeneral'] = 'Obecné';
$string['tabusers'] = 'Uživatelé';
$string['tabvisibility'] = 'Viditelnost v katalogu';
$string['tagarea_tool_muprog_program'] = 'Programy';
$string['task_extdb_cron'] = 'Cron přidělení do programů z externí databáze';
$string['task_extdb_sync'] = 'Přidělení do programů z externí databáze';
$string['taskcertificate'] = 'Cron vydávání certifikátů programů';
$string['taskcron'] = 'Cron pluginu Programy';
$string['unlinkeditems'] = 'Nepropojené položky';
$string['updateattendance'] = 'Upravit prezenční docházku';
$string['updatecourse'] = 'Upravit kurz';
$string['updatecredits'] = 'Upravit kredity';
$string['updatescheduling'] = 'Upravit plánování';
$string['updateset'] = 'Upravit sadu';
$string['updatesource'] = 'Upravit zdroj „{$a}“';
$string['upload'] = 'Nahrát programy';
$string['upload_files'] = 'Soubory';
$string['upload_files_error'] = 'Očekává se více souborů CSV, jeden soubor JSON nebo jeden archiv Zip';
$string['upload_invalidcount'] = 'Neplatné záznamy';
$string['upload_preview'] = 'Náhled dat';
$string['upload_status'] = 'Stav';
$string['upload_status_invalid'] = 'Neplatný';
$string['upload_targetcontext'] = 'Přidat programy do kontextu';
$string['upload_uploadcount'] = 'Programy k nahrání';
$string['upload_usecategory'] = 'Použít pro kontexty sloupec kategorie';
$string['userupload_completion_error'] = 'Dokončení programu nelze aktualizovat';
$string['userupload_completion_updated'] = 'Dokončení programu bylo aktualizováno';
