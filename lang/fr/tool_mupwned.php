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
 * Strings for component 'tool_mupwned', language 'fr'.
 *
 * NOTE: This French translation was produced automatically by an AI language
 * model and has not yet been reviewed by a human translator. Corrections are
 * welcome.
 *
 * @package    tool_mupwned
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['enabled'] = 'Détecter les mots de passe compromis';
$string['enabled_desc'] = 'Si ce paramètre est activé, les mots de passe compromis des utilisateurs sont détectés automatiquement lors des vérifications de la politique de mots de passe,
à l’aide du service [Have I Been Pwned](https://haveibeenpwned.com/) avec le [modèle de k-anonymat](https://en.wikipedia.org/wiki/K-anonymity).

**Avant l’activation, assurez-vous que les administrateurs du site peuvent réinitialiser leur mot de passe par courriel.**';
$string['enduser_compromisedpassword'] = 'Ce mot de passe a été compromis lors d’une fuite de données.';
$string['enduser_expiretokens_info'] = 'Vous devrez peut-être aussi créer de nouveaux jetons de services web et vous authentifier à nouveau dans votre application mobile.';
$string['enduser_resetpassword_info'] = 'Votre mot de passe est déjà apparu dans une fuite de données, pour des raisons de sécurité il ne peut pas être utilisé pour accéder à ce site. Vous devez réinitialiser manuellement votre mot de passe pour vous connecter.';
$string['event_user_login_blocked'] = 'Connexion avec un mot de passe compromis bloquée';
$string['expiretokens'] = 'Faire expirer les jetons de services web et de l’application mobile';
$string['expiretokens_desc'] = 'Si ce paramètre est activé, les utilisateurs contraints de réinitialiser un mot de passe compromis doivent aussi recréer tous leurs jetons de services web
et se reconnecter à leurs applications mobiles.';
$string['passwordpolicy'] = 'aucune occurrence dans les fuites de données connues';
$string['passwordpolicyinactive'] = '***Le paramètre « Politique de mots de passe » ou « Vérifier le mot de passe lors de la connexion » n’est pas activé, le blocage des mots de passe compromis ne fonctionnera PAS.***';
$string['pluginname'] = 'Blocage des mots de passe compromis';
$string['privacy:metadata'] = 'Le plugin Blocage des mots de passe compromis n’enregistre aucune information personnelle';
$string['resetpassword'] = 'Exiger la réinitialisation du mot de passe';
$string['resetpassword_desc'] = 'Si ce paramètre est activé, les utilisateurs dont le mot de passe est compromis ne peuvent pas se connecter tant qu’ils ne l’ont pas réinitialisé.';
$string['warning_passwordpolicy_disabled'] = 'Le paramètre « Politique de mots de passe » est désactivé, la détection des mots de passe compromis ne sera pas possible.';
$string['warning_passwordpolicycheckonlogin_disabled'] = 'Le paramètre « Vérifier le mot de passe lors de la connexion » est désactivé, les connexions avec un mot de passe compromis ne peuvent pas être bloquées.';
