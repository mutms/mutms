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
 * Strings for component 'tool_mupwned', language 'es'.
 *
 * NOTE: This Spanish translation was produced automatically by an AI language
 * model and has not yet been reviewed by a human translator. Corrections are
 * welcome.
 *
 * @package    tool_mupwned
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['enabled'] = 'Detectar contraseñas comprometidas';
$string['enabled_desc'] = 'Si se habilita, las contraseñas de usuario comprometidas se detectan automáticamente como parte de las comprobaciones de la política de contraseñas
mediante el servicio [Have I Been Pwned](https://haveibeenpwned.com/) con el [modelo de k-anonimato](https://en.wikipedia.org/wiki/K-anonymity).
**Antes de habilitarlo, asegúrese de que los administradores del sitio pueden restablecer sus contraseñas por correo electrónico.**';
$string['enduser_compromisedpassword'] = 'Esta contraseña se ha visto comprometida en una filtración de datos.';
$string['enduser_expiretokens_info'] = 'Es posible que también tenga que crear nuevos tokens de servicios web y volver a autenticarse en su aplicación móvil.';
$string['enduser_resetpassword_info'] = 'Su contraseña ha aparecido anteriormente en una filtración de datos; por motivos de seguridad no puede utilizarse para acceder a este sitio. Debe restablecer manualmente su contraseña para poder entrar.';
$string['event_user_login_blocked'] = 'Inicio de sesión con contraseña comprometida bloqueado';
$string['expiretokens'] = 'Hacer caducar los tokens de servicios web y de la aplicación móvil';
$string['expiretokens_desc'] = 'Si se habilita, los usuarios obligados a restablecer contraseñas comprometidas también deben volver a crear todos los tokens de servicios web
e iniciar sesión de nuevo en sus aplicaciones móviles.';
$string['passwordpolicy'] = 'ninguna aparición en filtraciones de datos conocidas';
$string['passwordpolicyinactive'] = '***"Política de contraseñas" o "Comprobar contraseña al iniciar sesión" no están habilitadas; el bloqueo de contraseñas comprometidas NO funcionará.***';
$string['pluginname'] = 'Bloqueo de contraseñas comprometidas';
$string['privacy:metadata'] = 'El plugin Bloqueo de contraseñas comprometidas no almacena ninguna información personal';
$string['resetpassword'] = 'Exigir el restablecimiento de la contraseña';
$string['resetpassword_desc'] = 'Si se habilita, los usuarios con contraseñas comprometidas no pueden iniciar sesión hasta que restablezcan su contraseña.';
$string['warning_passwordpolicy_disabled'] = 'El ajuste "Política de contraseñas" está deshabilitado; no será posible detectar contraseñas comprometidas.';
$string['warning_passwordpolicycheckonlogin_disabled'] = 'El ajuste "Comprobar contraseña al iniciar sesión" está deshabilitado; no es posible bloquear los inicios de sesión con contraseñas comprometidas.';
