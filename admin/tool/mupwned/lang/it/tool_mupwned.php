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
 * Strings for component 'tool_mupwned', language 'it'.
 *
 * NOTE: This Italian translation was produced automatically by an AI language
 * model and has not yet been reviewed by a human translator. Corrections are
 * welcome.
 *
 * @package    tool_mupwned
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['enabled'] = 'Rileva le password compromesse';
$string['enabled_desc'] = 'Se l\'impostazione è abilitata, le password compromesse degli utenti vengono rilevate automaticamente nell\'ambito dei controlli delle regole password
tramite il servizio [Have I Been Pwned](https://haveibeenpwned.com/) con il [modello k-Anonymity](https://en.wikipedia.org/wiki/K-anonymity).

**Prima di abilitarla, assicurarsi che gli amministratori del sito possano reimpostare la propria password via email.**';
$string['enduser_compromisedpassword'] = 'Questa password è stata compromessa in una violazione dei dati.';
$string['enduser_expiretokens_info'] = 'Potrebbe inoltre essere necessario creare nuovi token dei web service e autenticarsi di nuovo nell\'app mobile.';
$string['enduser_resetpassword_info'] = 'La tua password è già comparsa in una violazione dei dati, per motivi di sicurezza non può essere utilizzata per accedere a questo sito. Per effettuare il login devi reimpostare manualmente la password.';
$string['event_user_login_blocked'] = 'Login con password compromessa bloccato';
$string['expiretokens'] = 'Fai scadere i token dei web service e dell\'app mobile';
$string['expiretokens_desc'] = 'Se l\'impostazione è abilitata, gli utenti obbligati a reimpostare una password compromessa devono anche ricreare tutti i token dei web service
ed effettuare nuovamente il login nelle app mobile.';
$string['passwordpolicy'] = 'non comparire in violazioni dei dati note';
$string['passwordpolicyinactive'] = '***"Regole password" oppure "Verifica password durante l\'autenticazione" non è abilitata, il blocco delle password compromesse NON funzionerà.***';
$string['pluginname'] = 'Blocco delle password compromesse';
$string['privacy:metadata'] = 'Il plugin Blocco delle password compromesse non memorizza dati personali';
$string['resetpassword'] = 'Richiedi la reimpostazione della password';
$string['resetpassword_desc'] = 'Se l\'impostazione è abilitata, gli utenti con password compromesse non possono effettuare il login finché non reimpostano la password.';
$string['warning_passwordpolicy_disabled'] = 'L\'impostazione "Regole password" è disabilitata, il rilevamento delle password compromesse non sarà possibile.';
$string['warning_passwordpolicycheckonlogin_disabled'] = 'L\'impostazione "Verifica password durante l\'autenticazione" è disabilitata, i login con password compromessa non possono essere bloccati.';
