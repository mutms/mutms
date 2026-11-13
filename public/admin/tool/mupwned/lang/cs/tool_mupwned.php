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
 * Strings for component 'tool_mupwned', language 'cs'.
 *
 * @package    tool_mupwned
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['enabled'] = 'Zjišťovat kompromitovaná hesla';
$string['enabled_desc'] = 'Když je zapnuto, kompromitovaná hesla uživatelů se automaticky zjišťují v rámci kontrol zásad bezpečného hesla
pomocí služby [Have I Been Pwned](https://haveibeenpwned.com/) s [modelem k-anonymity](https://en.wikipedia.org/wiki/K-anonymity).

**Před zapnutím se prosím ujistěte, že si správci stránek mohou obnovit heslo e-mailem.**';
$string['enduser_compromisedpassword'] = 'Toto heslo bylo kompromitováno při úniku dat.';
$string['enduser_expiretokens_info'] = 'Možná bude také nutné vytvořit nové tokeny webových služeb a znovu se ověřit v mobilní aplikaci.';
$string['enduser_resetpassword_info'] = 'Vaše heslo se v minulosti objevilo v úniku dat, z bezpečnostních důvodů jej proto nelze použít pro přístup na tyto stránky. Abyste se mohli přihlásit, musíte si heslo ručně obnovit.';
$string['event_user_login_blocked'] = 'Přihlášení kompromitovaným heslem zablokováno';
$string['expiretokens'] = 'Ukončit platnost tokenů webových služeb a mobilních tokenů';
$string['expiretokens_desc'] = 'Když je zapnuto, uživatelé, kteří si musí obnovit kompromitované heslo, si také musí znovu vytvořit všechny tokeny webových služeb
a znovu se přihlásit v mobilních aplikacích.';
$string['passwordpolicy'] = 'heslo se nesmí objevit v předchozích únicích dat';
$string['passwordpolicyinactive'] = '***Není zapnuto buď nastavení „Zásady bezpečného hesla“, nebo „Zkontrolovat heslo při přihlášení“, blokování kompromitovaných hesel NEBUDE fungovat.***';
$string['pluginname'] = 'Blokování kompromitovaných hesel';
$string['privacy:metadata'] = 'Plugin Blokování kompromitovaných hesel neukládá žádné osobní údaje';
$string['resetpassword'] = 'Vyžadovat obnovení hesla';
$string['resetpassword_desc'] = 'Když je zapnuto, uživatelé s kompromitovaným heslem se nemohou přihlásit, dokud si heslo neobnoví.';
$string['warning_passwordpolicy_disabled'] = 'Nastavení „Zásady bezpečného hesla“ je vypnuto, kompromitovaná hesla nebude možné zjišťovat.';
$string['warning_passwordpolicycheckonlogin_disabled'] = 'Nastavení „Zkontrolovat heslo při přihlášení“ je vypnuto, přihlášení kompromitovaným heslem nelze blokovat.';
