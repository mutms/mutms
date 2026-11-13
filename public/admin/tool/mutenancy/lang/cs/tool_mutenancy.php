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
 * Strings for component 'tool_mutenancy', language 'cs'.
 *
 * @package    tool_mutenancy
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['associate'] = 'Přidružený uživatel';
$string['associate_add'] = 'Přidružit uživatele';
$string['associate_add_info'] = 'Uživatele bez klienta lze ke klientovi přidružit přidáním do globální skupiny přidružených uživatelů.
Uživatelé bez klienta mohou být přidruženi k více klientům. Více klientů může sdílet stejnou globální skupinu přidružených uživatelů.';
$string['associate_cohort'] = 'Globální skupina přidružených uživatelů';
$string['associate_cohort_create'] = 'Vytvořit globální skupinu přidružených uživatelů';
$string['associate_cohort_create_help'] = 'Vyberte, pokud chcete vytvořit novou globální skupinu pro uživatele přidružené ke klientovi.';
$string['associate_cohort_help'] = 'Vyberte globální skupinu, která obsahuje globální uživatele, kteří se mají účastnit v klientovi.

Globální uživatelé mohou být přidruženi k více klientům, ale účty klienta nelze přidružit k žádnému jinému klientovi.';
$string['associate_remove'] = 'Zrušit přidružení uživatele';
$string['associate_remove_info'] = 'Přidružení uživatelů ke klientovi se zruší jejich odebráním z globální skupiny přidružených uživatelů.
Může to ovlivnit více klientů, pokud klienti sdílejí stejnou globální skupinu přidružených uživatelů.';
$string['auth_edit'] = 'Upravit ověřování';
$string['boost_edit'] = 'Upravit Boost';
$string['bulk_allocate'] = 'Přiřadit uživatele ke klientovi';
$string['bulk_allocate_info'] = 'Následující uživatelé budou přiřazeni k vybranému klientovi:

{$a}

Přiřazení správce klienta budou odebrána a uživatelé ztratí přístup ke všem ostatním klientům. Účty správců nebudou změněny.';
$string['bulk_deallocate'] = 'Odebrat přiřazení členům klienta';
$string['bulk_deallocate_info'] = 'Následujícím uživatelům bude odebráno přiřazení ke klientům a stanou se běžnými uživateli:

{$a}';
$string['cachedef_config'] = 'Mezipaměť konfigurace klienta';
$string['cachedef_tenant'] = 'Mezipaměť záznamů klientů';
$string['config_default'] = 'Výchozí hodnota';
$string['config_default_value'] = 'Výchozí hodnota ({$a})';
$string['config_override'] = 'Přepsat';
$string['config_override_value'] = 'Přepsat výchozí hodnotu ({$a})';
$string['config_value'] = 'Hodnota';
$string['environment_corepatch_error'] = 'Požadovaný patch pro víceklientský provoz chybí nebo má neplatnou verzi';
$string['environment_corepatch_ok'] = 'Byla zjištěna správná verze patche';
$string['error:changerequired'] = 'Je vyžadována změna';
$string['error:memberlimitreached'] = 'Byl dosažen limit členů';
$string['event_appearance_updated'] = 'Vzhled klienta upraven';
$string['event_auth_updated'] = 'Ověřování klienta upraveno';
$string['event_tenant_archived'] = 'Klient archivován';
$string['event_tenant_created'] = 'Klient vytvořen';
$string['event_tenant_deleted'] = 'Klient odstraněn';
$string['event_tenant_restored'] = 'Klient obnoven';
$string['event_tenant_updated'] = 'Klient upraven';
$string['event_user_allocated'] = 'Přiřazení uživatele ke klientovi změněno';
$string['login_tenant_select'] = 'Vybrat stránky';
$string['login_tenant_select_a'] = 'Vybrat: {$a}';
$string['logos_edit'] = 'Upravit loga';
$string['member_confirm_info'] = 'Ve většině případů by se účty členů neměly potvrzovat zde.

Pokud uživatel nedostal potvrzovací e-mail, doporučujeme použít možnost *Znovu odeslat potvrzovací e-mail*.';
$string['member_create'] = 'Vytvořit účet';
$string['member_create_info'] = 'Nový účet člena klienta bude mít přístup ke kurzům svého klienta a, je-li to povoleno, také ke globálním kurzům.

Nebude mít přístup ke kurzům žádného jiného klienta.';
$string['member_delete_info'] = 'Odstranění účtu člena klienta je nevratné.';
$string['member_managers_info'] = 'Správci klienta nejsou v klientech automaticky viditelní. Pokud se od nich očekává účast v klientovi, měli by být přidáni do
globální skupiny přidružených uživatelů.';
$string['member_resend_info'] = 'E-maily s potvrzením nového účtu žádají uživatele, aby prokázali, že
vlastní e-mail uvedený při registraci.

Jde o důležitou bezpečnostní funkci, ruční potvrzování nových účtů se nedoporučuje.';
$string['member_suspend_info'] = 'Pozastavení účtu bylo navrženo tak, aby uživatelům zabránilo v přihlášení a
zastavilo také veškeré odchozí e-maily a oznámení, nic jiného.

Uživatelé i vývojáři bohužel pozastavení účtu často špatně chápou, což může vést k nekonzistentnímu
chování některých pluginů a ve vzácných případech i k menší ztrátě dat.';
$string['member_unlock_info'] = 'Uživatelské účty se po opakovaných neúspěšných pokusech o přihlášení uzamknou. Odemknutí účtu jim umožní zkusit to znovu.';
$string['member_unsuspend_info'] = 'Po opětovné aktivaci účtu by se uživatel měl moci znovu přihlásit
a začne dostávat e-maily a oznámení.';
$string['member_update'] = 'Upravit účet';
$string['mutenancy:admin'] = 'Správa klientů';
$string['mutenancy:allocate'] = 'Přiřazovat uživatele ke klientům';
$string['mutenancy:configappearance'] = 'Konfigurovat motiv a vizuální identitu klienta';
$string['mutenancy:configauth'] = 'Konfigurovat ověřování klienta';
$string['mutenancy:membercreate'] = 'Vytvořit účet člena klienta';
$string['mutenancy:memberdelete'] = 'Odstranit účet člena klienta';
$string['mutenancy:memberupdate'] = 'Upravit účet člena klienta';
$string['mutenancy:switch'] = 'Přepnout na klienta';
$string['mutenancy:view'] = 'Zobrazit klienta';
$string['navigation_category'] = 'Kategorie klienta';
$string['navigation_top'] = 'Správa klientů';
$string['pluginname'] = 'Víceklientský provoz';
$string['privacy:metadata:tool_mutenancy_manager'] = 'Správci klienta';
$string['privacy:metadata:tool_mutenancy_manager:tenantid'] = 'ID klienta';
$string['privacy:metadata:tool_mutenancy_manager:timecreated'] = 'Čas přiřazení správce';
$string['privacy:metadata:tool_mutenancy_manager:usercreated'] = 'Uživatel, který správce přiřadil';
$string['privacy:metadata:tool_mutenancy_manager:userid'] = 'ID uživatele správce';
$string['role_tenantmanager_archetype'] = 'ARCHETYP: Správce klienta';
$string['role_tenantmanager_description'] = 'Role správce klienta se všem správcům klienta přiřazuje automaticky v kontextech klienta a kategorie klienta.

Tato role se nemá přiřazovat ručně, neměňte krátký název role.';
$string['role_tenantmanager_name'] = 'Správce klienta';
$string['role_tenantuser_archetype'] = 'ARCHETYP: Uživatel klienta';
$string['role_tenantuser_description'] = 'Role uživatele klienta se všem uživatelům klienta přiřazuje automaticky v kontextech kategorie klienta.

Tato role se nemá přiřazovat ručně, neměňte krátký název role.';
$string['role_tenantuser_name'] = 'Uživatel klienta';
$string['secondary_tenant_appearance'] = 'Vzhled';
$string['secondary_tenant_auth'] = 'Ověřování';
$string['secondary_tenant_details'] = 'Podrobnosti';
$string['secondary_tenant_users'] = 'Uživatelé';
$string['setting_allowguests'] = 'Povolit přístup hostů ke klientům';
$string['setting_allowguests_desc'] = 'Zapněte toto nastavení, pokud chcete povolit přístup hostů ke kurzům klientů.

Tato funkce vyžaduje, aby byl zapnutý přístup hostů na úrovni stránek. Kurzy klientů navíc musí mít zapnutý přístup hostů a uživatelé by měli být ke klientům přidruženi, nikoli být jejich členy.';
$string['setting_tenantentities'] = 'Název klienta v množném čísle';
$string['setting_tenantentities_desc'] = 'Pokud je vyplněno, slovo „Klienti“ v množném čísle se nahradí zadaným vlastním názvem. Například: Jednotky, Fakulty nebo Akce';
$string['setting_tenantentity'] = 'Název klienta v jednotném čísle';
$string['setting_tenantentity_desc'] = 'Pokud je vyplněno, slovo „Klient“ v jednotném čísle se nahradí zadaným vlastním názvem. Například: Jednotka, Fakulta nebo Akce';
$string['setting_tenantlimit'] = 'Limit klientů';
$string['setting_tenantlimit_desc'] = 'Určuje, kolik klientů lze vytvořit. Stránky s více než 100 klienty mohou mít problémy s výkonem a použitelností.';
$string['setting_tenantprimarynav'] = 'Přidat správu klientů do hlavní nabídky';
$string['setting_tenantprimarynav_desc'] = 'Když je zapnuto, přidá se do hlavní nabídky sekce správy aktuálního klienta. Je určeno pro vyhrazené správce klienta nebo uživatele, kteří se mohou přepínat na klienty.';
$string['settings'] = 'Nastavení víceklientského provozu';
$string['taskcron'] = 'Úloha údržby víceklientského provozu';
$string['tenancy_activate'] = 'Aktivovat víceklientský provoz';
$string['tenancy_activate_info'] = 'Při aktivaci víceklientského provozu budou vytvořeny nové role pro správce klienta a uživatele klienta.

Víceklientský provoz lze deaktivovat až po odstranění všech klientů.';
$string['tenancy_deactivate'] = 'Deaktivovat víceklientský provoz';
$string['tenancy_deactivate_info'] = 'Při deaktivaci víceklientského provozu bude role správce klienta odstraněna.';
$string['tenant'] = 'Klient';
$string['tenant_actions'] = 'Akce';
$string['tenant_archive'] = 'Archivovat klienta';
$string['tenant_archive_a'] = 'Archivovat: {$a}';
$string['tenant_archive_info'] = 'Archivace klienta:

* zabrání členům klienta v přihlášení a zastaví veškeré odchozí e-maily a oznámení,
* skryje kategorii klienta.

Archivace je povinný krok před odstraněním klienta.';
$string['tenant_archived'] = 'Archivováno';
$string['tenant_associates'] = 'Přidružení uživatelé';
$string['tenant_category'] = 'Kategorie klienta';
$string['tenant_categoryidnumber'] = 'Identifikátor kategorie klienta';
$string['tenant_categoryname'] = 'Název kategorie klienta';
$string['tenant_cohort'] = 'Globální skupina klienta';
$string['tenant_cohortidnumber'] = 'Identifikátor globální skupiny klienta';
$string['tenant_cohortname'] = 'Název globální skupiny klienta';
$string['tenant_create'] = 'Přidat klienta';
$string['tenant_create_a'] = 'Přidat: {$a}';
$string['tenant_delete'] = 'Odstranit klienta';
$string['tenant_delete_a'] = 'Odstranit: {$a}';
$string['tenant_delete_info'] = 'Při odstranění klienta:

* budou odebrána oprávnění správců klienta
* globální skupina klienta bude převedena na běžnou globální skupinu
* všichni členové klienta budou přiřazeni ke globálním stránkám

Podkategorie, kurzy a uživatelská data se nezmění.';
$string['tenant_delete_movetotenant'] = 'Kam přiřadit členy';
$string['tenant_idnumber'] = 'ID klienta';
$string['tenant_loginshow'] = 'Zobrazit klienta na přihlašovací stránce';
$string['tenant_loginurl'] = 'Přihlašovací URL klienta';
$string['tenant_loginurl_copy'] = 'Kopírovat přihlašovací URL klienta do schránky';
$string['tenant_manager'] = 'Správce klienta';
$string['tenant_managers'] = 'Správci klienta';
$string['tenant_member'] = 'Člen klienta';
$string['tenant_member_a'] = 'Člen: {$a}';
$string['tenant_memberlimit'] = 'Limit členů klienta';
$string['tenant_memberlimit_help'] = 'Určuje maximální počet účtů členů klienta, které lze v klientovi vytvořit.

Přidružení uživatelé se do tohoto limitu nezapočítávají.';
$string['tenant_name'] = 'Název klienta';
$string['tenant_restore'] = 'Obnovit archivovaného klienta';
$string['tenant_restore_a'] = 'Obnovit z archivu: {$a}';
$string['tenant_restore_info'] = 'Obnovení klienta by mělo vrátit většinu změn provedených při archivaci klienta.

Doporučujeme však poté zkontrolovat všechna nastavení klienta a viditelnost kategorie.';
$string['tenant_sitefullname'] = 'Název stránek klienta';
$string['tenant_siteshortname'] = 'Krátký název stránek klienta';
$string['tenant_switch'] = 'Přepnout klienta';
$string['tenant_switch_a'] = 'Přepnout: {$a}';
$string['tenant_switch_info'] = 'Přepnutí na klienta změní vzhled stránek a u některých akcí omezí seznam dostupných uživatelů.

Přepnutí na klienta není totéž jako být členem klienta, pro členy klienta platí další omezení.';
$string['tenant_switch_my'] = 'Moji klienti';
$string['tenant_switch_my_a'] = 'Moje: {$a}';
$string['tenant_switch_notenant'] = 'Bez klienta';
$string['tenant_switch_notenant_a'] = 'Bez: {$a}';
$string['tenant_switch_other'] = 'Ostatní klienti';
$string['tenant_switch_other_a'] = 'Ostatní: {$a}';
$string['tenant_update'] = 'Upravit klienta';
$string['tenant_update_a'] = 'Upravit: {$a}';
$string['tenant_users'] = 'Uživatelé klienta';
$string['tenant_users_a'] = 'Uživatelé: {$a}';
$string['tenants'] = 'Klienti';
$string['user_allocate'] = 'Přiřadit uživatele';
$string['user_allocate_info'] = 'Při přiřazení uživatele ke klientovi přestane být správcem klienta
a ztratí přístup ke všem ostatním klientům.';
$string['user_tenants'] = 'Přidružení ke klientům';
$string['user_tenants_a'] = 'Přidružení: {$a}';
