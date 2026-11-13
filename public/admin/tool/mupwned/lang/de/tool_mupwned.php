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
 * Strings for component 'tool_mupwned', language 'de'.
 *
 * NOTE: This German translation was produced automatically by an AI language
 * model and has not yet been reviewed by a human translator. Corrections are
 * welcome.
 *
 * @package    tool_mupwned
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['enabled'] = 'Kompromittierte Kennwörter erkennen';
$string['enabled_desc'] = 'Wenn aktiviert, werden kompromittierte Kennwörter von Nutzer/innen im Rahmen der Prüfung der Kennwortregeln automatisch erkannt.
Dazu wird der Dienst [Have I Been Pwned](https://haveibeenpwned.com/) mit dem [k-Anonymitätsmodell](https://en.wikipedia.org/wiki/K-anonymity) verwendet.

**Stellen Sie vor dem Aktivieren sicher, dass Administrator/innen der Website ihre Kennwörter per E-Mail zurücksetzen können.**';
$string['enduser_compromisedpassword'] = 'Dieses Kennwort wurde bei einem Datenleck kompromittiert.';
$string['enduser_expiretokens_info'] = 'Möglicherweise müssen Sie auch neue Webservice-Token erstellen und sich in Ihrer mobilen App erneut anmelden.';
$string['enduser_resetpassword_info'] = 'Ihr Kennwort ist bereits in einem Datenleck aufgetaucht. Aus Sicherheitsgründen kann es nicht für den Zugang zu dieser Website verwendet werden. Sie müssen Ihr Kennwort manuell zurücksetzen, um sich anzumelden.';
$string['event_user_login_blocked'] = 'Anmeldung mit kompromittiertem Kennwort blockiert';
$string['expiretokens'] = 'Webservice- und Mobile-Token ungültig machen';
$string['expiretokens_desc'] = 'Wenn aktiviert, müssen Nutzer/innen, die ein kompromittiertes Kennwort zurücksetzen müssen, auch alle Webservice-Token neu erstellen
und sich erneut in ihren mobilen Apps anmelden.';
$string['passwordpolicy'] = 'nicht in bekannten Datenlecks enthalten';
$string['passwordpolicyinactive'] = '***Entweder „Kennwortregeln“ oder „Kennwort beim Anmelden überprüfen“ ist nicht aktiviert, das Blockieren kompromittierter Kennwörter funktioniert NICHT.***';
$string['pluginname'] = 'Blockieren kompromittierter Kennwörter';
$string['privacy:metadata'] = 'Das Plugin zum Blockieren kompromittierter Kennwörter speichert keine personenbezogenen Daten.';
$string['resetpassword'] = 'Zurücksetzen des Kennworts erzwingen';
$string['resetpassword_desc'] = 'Wenn aktiviert, können sich Nutzer/innen mit kompromittierten Kennwörtern erst anmelden, nachdem sie ihr Kennwort zurückgesetzt haben.';
$string['warning_passwordpolicy_disabled'] = 'Die Einstellung „Kennwortregeln“ ist deaktiviert, kompromittierte Kennwörter können nicht erkannt werden.';
$string['warning_passwordpolicycheckonlogin_disabled'] = 'Die Einstellung „Kennwort beim Anmelden überprüfen“ ist deaktiviert, Anmeldungen mit kompromittiertem Kennwort können nicht blockiert werden.';
