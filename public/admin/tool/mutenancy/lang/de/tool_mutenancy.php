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
 * Strings for component 'tool_mutenancy', language 'de'.
 *
 * NOTE: This German translation was produced automatically by an AI language
 * model and has not yet been reviewed by a human translator. Corrections are
 * welcome.
 *
 * @package    tool_mutenancy
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['associate'] = 'Verbundene/r Nutzer/in';
$string['associate_add'] = 'Nutzer/innen verbinden';
$string['associate_add_info'] = 'Nutzer/innen, die zu keinem Mandanten gehören, können mit einem Mandanten verbunden werden, indem sie zur globalen Gruppe der verbundenen Nutzer/innen hinzugefügt werden.
Nutzer/innen ohne Mandant können mit mehreren Mandanten verbunden sein. Mehrere Mandanten können dieselbe globale Gruppe der verbundenen Nutzer/innen verwenden.';
$string['associate_cohort'] = 'Globale Gruppe der verbundenen Nutzer/innen';
$string['associate_cohort_create'] = 'Globale Gruppe der verbundenen Nutzer/innen anlegen';
$string['associate_cohort_create_help'] = 'Wählen Sie diese Option, um eine neue globale Gruppe für Nutzer/innen anzulegen, die mit dem Mandanten verbunden sind.';
$string['associate_cohort_help'] = 'Wählen Sie die globale Gruppe mit den globalen Nutzer/innen, die am Mandanten teilnehmen sollen.

Globale Nutzer/innen können mit mehreren Mandanten verbunden sein, Konten eines Mandanten können jedoch mit keinem anderen Mandanten verbunden werden.';
$string['associate_remove'] = 'Verbindung der Nutzer/in aufheben';
$string['associate_remove_info'] = 'Die Verbindung von Nutzer/innen mit dem Mandanten wird aufgehoben, indem sie aus der globalen Gruppe der verbundenen Nutzer/innen entfernt werden.
Dies kann mehrere Mandanten betreffen, wenn Mandanten dieselbe globale Gruppe der verbundenen Nutzer/innen verwenden.';
$string['auth_edit'] = 'Authentifizierung bearbeiten';
$string['boost_edit'] = 'Boost bearbeiten';
$string['bulk_allocate'] = 'Nutzer/innen einem Mandanten zuweisen';
$string['bulk_allocate_info'] = 'Die folgenden Nutzer/innen werden dem ausgewählten Mandanten zugewiesen:

{$a}

Zuweisungen als Mandantenmanager/in werden entfernt und die Nutzer/innen verlieren den Zugriff auf alle anderen Mandanten. Administratorkonten werden nicht geändert.';
$string['bulk_deallocate'] = 'Zuweisung der Mitglieder zum Mandanten aufheben';
$string['bulk_deallocate_info'] = 'Die Zuweisung der folgenden Nutzer/innen zu Mandanten wird aufgehoben und sie werden zu regulären Nutzer/innen:

{$a}';
$string['cachedef_config'] = 'Cache der Mandantenkonfiguration';
$string['cachedef_tenant'] = 'Cache der Mandantendatensätze';
$string['config_default'] = 'Standardwert';
$string['config_default_value'] = 'Standardwert ({$a})';
$string['config_override'] = 'Überschreiben';
$string['config_override_value'] = 'Standardwert überschreiben ({$a})';
$string['config_value'] = 'Wert';
$string['environment_corepatch_error'] = 'Der erforderliche Patch für die Mehrmandantenfähigkeit fehlt oder hat eine ungültige Version';
$string['environment_corepatch_ok'] = 'Korrekte Patch-Version erkannt';
$string['error:changerequired'] = 'Änderung erforderlich';
$string['error:memberlimitreached'] = 'Mitgliederlimit erreicht';
$string['event_appearance_updated'] = 'Darstellung des Mandanten aktualisiert';
$string['event_auth_updated'] = 'Authentifizierung des Mandanten aktualisiert';
$string['event_tenant_archived'] = 'Mandant archiviert';
$string['event_tenant_created'] = 'Mandant angelegt';
$string['event_tenant_deleted'] = 'Mandant gelöscht';
$string['event_tenant_restored'] = 'Mandant wiederhergestellt';
$string['event_tenant_updated'] = 'Mandant aktualisiert';
$string['event_user_allocated'] = 'Mandantenzuweisung der Nutzer/in geändert';
$string['login_tenant_select'] = 'Website auswählen';
$string['login_tenant_select_a'] = '{$a} auswählen';
$string['logos_edit'] = 'Logos bearbeiten';
$string['member_confirm_info'] = 'In den meisten Fällen sollten Konten von Mitgliedern nicht hier bestätigt werden.

Wenn die Nutzer/in die Bestätigungs-E-Mail nicht erhalten hat, wird empfohlen, die Option *E-Mail zur Bestätigung erneut senden* zu verwenden.';
$string['member_create'] = 'Konto anlegen';
$string['member_create_info'] = 'Ein neues Konto eines Mitglieds des Mandanten hat Zugriff auf die Kurse des eigenen Mandanten und, falls erlaubt, auch auf globale Kurse.

Auf Kurse anderer Mandanten besteht kein Zugriff.';
$string['member_delete_info'] = 'Das Löschen eines Kontos eines Mitglieds des Mandanten kann nicht rückgängig gemacht werden.';
$string['member_managers_info'] = 'Mandantenmanager/innen sind in Mandanten nicht automatisch sichtbar. Sie sollten zur
globalen Gruppe der verbundenen Nutzer/innen hinzugefügt werden, wenn sie am Mandanten teilnehmen sollen.';
$string['member_resend_info'] = 'E-Mails zur Bestätigung neuer Konten fordern Nutzer/innen auf nachzuweisen, dass ihnen
die bei der Registrierung angegebene E-Mail-Adresse gehört.

Dies ist eine wichtige Sicherheitsfunktion, die manuelle Bestätigung neuer Konten wird nicht empfohlen.';
$string['member_suspend_info'] = 'Das Sperren von Konten soll verhindern, dass sich Nutzer/innen anmelden, und
außerdem alle ausgehenden E-Mails und Benachrichtigungen stoppen, sonst nichts.

Leider wird das Sperren von Konten von Nutzer/innen und Entwickler/innen oft missverstanden, was zu inkonsistentem
Verhalten mancher Plugins und in seltenen Fällen zu geringfügigem Datenverlust führen kann.';
$string['member_unlock_info'] = 'Nutzerkonten werden nach wiederholten fehlgeschlagenen Anmeldeversuchen blockiert. Nach dem Freigeben des Kontos kann die Anmeldung erneut versucht werden.';
$string['member_unsuspend_info'] = 'Nach der Aktivierung des Kontos sollte sich die Nutzer/in wieder anmelden können
und erhält wieder E-Mails und Benachrichtigungen.';
$string['member_update'] = 'Konto bearbeiten';
$string['mutenancy:admin'] = 'Mandanten administrieren';
$string['mutenancy:allocate'] = 'Nutzer/innen Mandanten zuweisen';
$string['mutenancy:configappearance'] = 'Design und Branding des Mandanten konfigurieren';
$string['mutenancy:configauth'] = 'Authentifizierung des Mandanten konfigurieren';
$string['mutenancy:membercreate'] = 'Konto für Mitglied des Mandanten anlegen';
$string['mutenancy:memberdelete'] = 'Konto eines Mitglieds des Mandanten löschen';
$string['mutenancy:memberupdate'] = 'Konto eines Mitglieds des Mandanten bearbeiten';
$string['mutenancy:switch'] = 'Zu Mandant wechseln';
$string['mutenancy:view'] = 'Mandant anzeigen';
$string['navigation_category'] = 'Kursbereich des Mandanten';
$string['navigation_top'] = 'Mandantenverwaltung';
$string['pluginname'] = 'Mehrmandantenfähigkeit';
$string['privacy:metadata:tool_mutenancy_manager'] = 'Mandantenmanager/innen';
$string['privacy:metadata:tool_mutenancy_manager:tenantid'] = 'ID des Mandanten';
$string['privacy:metadata:tool_mutenancy_manager:timecreated'] = 'Zeitpunkt der Zuweisung';
$string['privacy:metadata:tool_mutenancy_manager:usercreated'] = 'Nutzer/in, die die Manager/in zugewiesen hat';
$string['privacy:metadata:tool_mutenancy_manager:userid'] = 'Nutzer-ID der Manager/in';
$string['role_tenantmanager_archetype'] = 'Basisrolle: Mandantenmanager/in';
$string['role_tenantmanager_description'] = 'Die Rolle Mandantenmanager/in wird allen Mandantenmanager/innen automatisch im Kontext des Mandanten und seines Kursbereichs zugewiesen.

Diese Rolle soll nicht manuell zugewiesen werden. Ändern Sie nicht die Kurzbezeichnung der Rolle.';
$string['role_tenantmanager_name'] = 'Mandantenmanager/in';
$string['role_tenantuser_archetype'] = 'Basisrolle: Nutzer/in des Mandanten';
$string['role_tenantuser_description'] = 'Die Rolle Nutzer/in des Mandanten wird allen Nutzer/innen des Mandanten automatisch im Kontext des Kursbereichs des Mandanten zugewiesen.

Diese Rolle soll nicht manuell zugewiesen werden. Ändern Sie nicht die Kurzbezeichnung der Rolle.';
$string['role_tenantuser_name'] = 'Nutzer/in des Mandanten';
$string['secondary_tenant_appearance'] = 'Darstellung';
$string['secondary_tenant_auth'] = 'Authentifizierung';
$string['secondary_tenant_details'] = 'Details';
$string['secondary_tenant_users'] = 'Nutzer/innen';
$string['setting_allowguests'] = 'Gastzugang zu Mandanten erlauben';
$string['setting_allowguests_desc'] = 'Aktivieren Sie diese Einstellung, wenn Sie den Gastzugang zu Kursen von Mandanten erlauben möchten.

Diese Funktion setzt voraus, dass der Gastzugang auf Ebene der Website aktiviert ist. Außerdem muss in den Kursen der Mandanten der Gastzugang aktiviert sein, und die Nutzer/innen sollten mit den Mandanten verbunden sein, statt Mitglieder der Mandanten zu sein.';
$string['setting_tenantentities'] = 'Bezeichnung für Mandanten im Plural';
$string['setting_tenantentities_desc'] = 'Wenn angegeben, wird das Wort „Mandanten“ im Plural durch die angegebene eigene Bezeichnung ersetzt. Zum Beispiel: Einheiten, Fakultäten oder Veranstaltungen';
$string['setting_tenantentity'] = 'Bezeichnung für Mandant im Singular';
$string['setting_tenantentity_desc'] = 'Wenn angegeben, wird das Wort „Mandant“ im Singular durch die angegebene eigene Bezeichnung ersetzt. Zum Beispiel: Einheit, Fakultät oder Veranstaltung';
$string['setting_tenantlimit'] = 'Maximale Anzahl der Mandanten';
$string['setting_tenantlimit_desc'] = 'Legt fest, wie viele Mandanten angelegt werden können. Websites mit mehr als 100 Mandanten können Probleme mit der Leistung und Bedienbarkeit haben.';
$string['setting_tenantprimarynav'] = 'Mandantenverwaltung zum Hauptmenü hinzufügen';
$string['setting_tenantprimarynav_desc'] = 'Wenn aktiviert, wird der Bereich zur Verwaltung des aktuellen Mandanten zum Hauptmenü hinzugefügt. Dies ist für Mandantenmanager/innen oder Nutzer/innen gedacht, die zu Mandanten wechseln können.';
$string['settings'] = 'Einstellungen der Mehrmandantenfähigkeit';
$string['taskcron'] = 'Bereinigung der Mehrmandantenfähigkeit';
$string['tenancy_activate'] = 'Mehrmandantenfähigkeit aktivieren';
$string['tenancy_activate_info'] = 'Bei der Aktivierung der Mehrmandantenfähigkeit werden neue Rollen für Mandantenmanager/innen und Nutzer/innen des Mandanten angelegt.

Die Mehrmandantenfähigkeit kann erst deaktiviert werden, nachdem alle Mandanten gelöscht wurden.';
$string['tenancy_deactivate'] = 'Mehrmandantenfähigkeit deaktivieren';
$string['tenancy_deactivate_info'] = 'Bei der Deaktivierung der Mehrmandantenfähigkeit wird die Rolle Mandantenmanager/in gelöscht.';
$string['tenant'] = 'Mandant';
$string['tenant_actions'] = 'Aktionen';
$string['tenant_archive'] = 'Mandant archivieren';
$string['tenant_archive_a'] = '{$a} archivieren';
$string['tenant_archive_info'] = 'Das Archivieren eines Mandanten:

* verhindert die Anmeldung der Mitglieder des Mandanten und stoppt alle ausgehenden E-Mails und Benachrichtigungen,
* verbirgt den Kursbereich des Mandanten.

Das Archivieren ist ein notwendiger Schritt, bevor ein Mandant gelöscht werden kann.';
$string['tenant_archived'] = 'Archiviert';
$string['tenant_associates'] = 'Verbundene Nutzer/innen';
$string['tenant_category'] = 'Kursbereich des Mandanten';
$string['tenant_categoryidnumber'] = 'ID-Nummer des Kursbereichs des Mandanten';
$string['tenant_categoryname'] = 'Name des Kursbereichs des Mandanten';
$string['tenant_cohort'] = 'Globale Gruppe des Mandanten';
$string['tenant_cohortidnumber'] = 'ID-Nummer der globalen Gruppe des Mandanten';
$string['tenant_cohortname'] = 'Name der globalen Gruppe des Mandanten';
$string['tenant_create'] = 'Mandant hinzufügen';
$string['tenant_create_a'] = '{$a} hinzufügen';
$string['tenant_delete'] = 'Mandant löschen';
$string['tenant_delete_a'] = '{$a} löschen';
$string['tenant_delete_info'] = 'Beim Löschen eines Mandanten:

* werden die Rechte der Mandantenmanager/innen entzogen
* wird die globale Gruppe des Mandanten in eine reguläre globale Gruppe umgewandelt
* werden alle Mitglieder des Mandanten der globalen Website zugewiesen

Unterbereiche, Kurse und Nutzerdaten werden nicht geändert.';
$string['tenant_delete_movetotenant'] = 'Mitglieder zuweisen zu';
$string['tenant_idnumber'] = 'Mandanten-ID';
$string['tenant_loginshow'] = 'Mandant auf der Anmeldeseite anzeigen';
$string['tenant_loginurl'] = 'Anmelde-URL des Mandanten';
$string['tenant_loginurl_copy'] = 'Anmelde-URL des Mandanten in die Zwischenablage kopieren';
$string['tenant_manager'] = 'Mandantenmanager/in';
$string['tenant_managers'] = 'Mandantenmanager/innen';
$string['tenant_member'] = 'Mitglied des Mandanten';
$string['tenant_member_a'] = 'Mitglied ({$a})';
$string['tenant_memberlimit'] = 'Maximale Anzahl der Mitglieder des Mandanten';
$string['tenant_memberlimit_help'] = 'Legt die maximale Anzahl der Konten von Mitgliedern fest, die im Mandanten angelegt werden können.

Verbundene Nutzer/innen werden bei diesem Limit nicht mitgezählt.';
$string['tenant_name'] = 'Name des Mandanten';
$string['tenant_restore'] = 'Archivierten Mandanten wiederherstellen';
$string['tenant_restore_a'] = '{$a} aus dem Archiv wiederherstellen';
$string['tenant_restore_info'] = 'Das Wiederherstellen eines Mandanten sollte die meisten Änderungen rückgängig machen, die beim Archivieren des Mandanten vorgenommen wurden.

Es wird jedoch empfohlen, anschließend alle Einstellungen des Mandanten und die Sichtbarkeit des Kursbereichs zu überprüfen.';
$string['tenant_sitefullname'] = 'Name der Website des Mandanten';
$string['tenant_siteshortname'] = 'Kurzbezeichnung der Website des Mandanten';
$string['tenant_switch'] = 'Mandant wechseln';
$string['tenant_switch_a'] = '{$a} wechseln';
$string['tenant_switch_info'] = 'Der Wechsel zu einem Mandanten ändert die Darstellung der Website und schränkt bei manchen Aktionen die Liste der verfügbaren Nutzer/innen ein.

Der Wechsel zu einem Mandanten ist nicht gleichbedeutend mit der Mitgliedschaft im Mandanten, für Mitglieder des Mandanten gelten zusätzliche Einschränkungen.';
$string['tenant_switch_my'] = 'Meine Mandanten';
$string['tenant_switch_my_a'] = 'Meine {$a}';
$string['tenant_switch_notenant'] = 'Kein Mandant';
$string['tenant_switch_notenant_a'] = 'Ohne {$a}';
$string['tenant_switch_other'] = 'Andere Mandanten';
$string['tenant_switch_other_a'] = 'Andere {$a}';
$string['tenant_update'] = 'Mandant bearbeiten';
$string['tenant_update_a'] = '{$a} bearbeiten';
$string['tenant_users'] = 'Nutzer/innen des Mandanten';
$string['tenant_users_a'] = 'Nutzer/innen ({$a})';
$string['tenants'] = 'Mandanten';
$string['user_allocate'] = 'Nutzer/in zuweisen';
$string['user_allocate_info'] = 'Bei der Zuweisung einer Nutzer/in zu einem Mandanten werden ihre Zuweisungen als Mandantenmanager/in entfernt
und sie verliert den Zugriff auf alle anderen Mandanten.';
$string['user_tenants'] = 'Verbunden mit Mandanten';
$string['user_tenants_a'] = 'Verbunden mit {$a}';
