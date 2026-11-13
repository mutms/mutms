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
 * Strings for component 'tool_murelation', language 'cs'.
 *
 * @package    tool_murelation
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['actions_a'] = 'Akce: {$a}';
$string['error_maxsubordinates'] = 'Dosažen limit počtu podřízených';
$string['error_noframeworks'] = 'Nebyly nalezeny žádné rámce vztahů uživatelů';
$string['error_nosubordinates'] = 'Nebyli nalezeni žádní podřízení';
$string['error_nosubordinates_a'] = 'Nenalezeno: {$a}';
$string['error_nosupervisors'] = 'Nebyli nalezeni žádní nadřízení';
$string['error_noteams'] = 'Nebyly nalezeny žádné týmy';
$string['error_nouserroles'] = 'Na úrovni kontextu uživatele nelze přiřadit žádné role';
$string['framework'] = 'Rámec vztahů';
$string['framework_alltenants'] = 'Dostupný ve všech klientech';
$string['framework_create'] = 'Přidat rámec';
$string['framework_delete'] = 'Odstranit rámec';
$string['framework_details'] = 'Podrobnosti rámce';
$string['framework_idnumber'] = 'ID rámce';
$string['framework_managecohort'] = 'Správa omezena na globální skupinu';
$string['framework_name'] = 'Název rámce';
$string['framework_subordinatecohort'] = 'Globální skupina kandidátů na podřízené';
$string['framework_supervisorcohort'] = 'Globální skupina kandidátů na nadřízené';
$string['framework_supervisorrole'] = 'Role nadřízeného';
$string['framework_uimode'] = 'Režim rámce';
$string['framework_uimode_supervisors'] = 'Nadřízení';
$string['framework_uimode_teams'] = 'Týmy';
$string['framework_update'] = 'Upravit rámec';
$string['framework_visibility'] = 'Viditelnost pozic';
$string['framework_visibility_0'] = 'Skryté';
$string['framework_visibility_1'] = 'Správci pozic';
$string['framework_visibility_2'] = 'Správci pozic, nadřízení a učitelé kurzů';
$string['framework_visibility_3'] = 'Správci pozic, nadřízení, učitelé kurzů a podřízení';
$string['framework_visibility_4'] = 'Všichni';
$string['frameworks'] = 'Rámce vztahů uživatelů';
$string['management_framework_actions'] = 'Akce rámce';
$string['management_frameworks'] = 'Rámce vztahů uživatelů';
$string['management_team_actions'] = 'Akce týmu';
$string['management_user_actions'] = 'Akce souvisejících uživatelů';
$string['member_delete_a'] = 'Odebrat: {$a}';
$string['member_update_a'] = 'Upravit: {$a}';
$string['members_add_cohort_a'] = 'Přidat z globální skupiny: {$a}';
$string['members_create_a'] = 'Přidat: {$a}';
$string['murelation:manageframeworks'] = 'Správa rámců vztahů uživatelů';
$string['murelation:managepositions'] = 'Správa nadřízených a týmů';
$string['murelation:viewframeworks'] = 'Zobrazit rámce vztahů uživatelů';
$string['murelation:viewpositions'] = 'Zobrazit nadřízené a týmy';
$string['pluginname'] = 'Nadřízení a týmy';
$string['privacy:metadata:field:frameworkid'] = 'Rámec';
$string['privacy:metadata:field:supervisorid'] = 'Nadřízený';
$string['privacy:metadata:field:teamidnumber'] = 'ID týmu';
$string['privacy:metadata:field:teamname'] = 'Název týmu';
$string['privacy:metadata:field:teamposition'] = 'Pozice v týmu';
$string['privacy:metadata:field:userid'] = 'Uživatel';
$string['privacy:metadata:table:tool_murelation_subordinate'] = 'Podřízení';
$string['privacy:metadata:table:tool_murelation_supervisor'] = 'Nadřízení';
$string['settings'] = 'Nastavení nadřízených a týmů';
$string['settings_roles'] = 'Povolené role nadřízených';
$string['settings_roles_desc'] = 'Vyberte role, které lze v definicích rámců použít jako role přiřazované nadřízeným v kontextech podřízených uživatelů.';
$string['subordinate'] = 'Podřízený';
$string['subordinates'] = 'Podřízení';
$string['subordinates_create_a'] = 'Přidat: {$a}';
$string['subordinates_my'] = 'Moji podřízení';
$string['subordinatestitle'] = 'Označení podřízených (množné číslo)';
$string['subordinatetitle'] = 'Označení podřízeného';
$string['supervisor'] = 'Nadřízený';
$string['supervisor_create_a'] = 'Přidat: {$a}';
$string['supervisor_delete'] = 'Odebrat nadřízeného';
$string['supervisor_delete_a'] = 'Odebrat: {$a}';
$string['supervisor_update'] = 'Upravit nadřízeného';
$string['supervisor_update_a'] = 'Upravit: {$a}';
$string['supervisor_vacant'] = 'Neobsazená pozice';
$string['supervisors'] = 'Nadřízení';
$string['supervisors_my'] = 'Moji nadřízení';
$string['supervisorstitle'] = 'Označení nadřízených (množné číslo)';
$string['supervisortitle'] = 'Označení nadřízeného';
$string['taskcron'] = 'Úloha údržby nadřízených a týmů';
$string['team'] = 'Tým';
$string['team_cohort'] = 'Globální skupina týmu';
$string['team_cohort_create'] = 'Vytvořit globální skupinu týmu';
$string['team_cohort_name'] = 'Název globální skupiny týmu';
$string['team_create'] = 'Vytvořit tým';
$string['team_delete'] = 'Odstranit tým';
$string['team_delete_confirm'] = 'Zaškrtnutím políčka potvrďte odstranění týmu (počet podřízených: {$a})';
$string['team_details'] = 'Podrobnosti týmu';
$string['team_idnumber'] = 'ID týmu';
$string['team_maxsubordinates'] = 'Max. počet členů týmu';
$string['team_name'] = 'Název týmu';
$string['team_position'] = 'Pozice v týmu';
$string['team_supmanaged'] = 'Tým spravovaný nadřízeným';
$string['team_update'] = 'Upravit tým';
$string['team_view'] = 'Zobrazit tým';
$string['teams'] = 'Týmy';
$string['teams_supervised'] = 'Vedené týmy';
$string['user_subordinates_add'] = 'Přidat podřízené';
$string['user_supervisor_appoint'] = 'Jmenovat nadřízeného';
$string['user_supervisor_new'] = 'Vytvořit nového nadřízeného/tým';
