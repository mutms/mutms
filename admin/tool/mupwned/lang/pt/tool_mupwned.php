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
 * Strings for component 'tool_mupwned', language 'pt'.
 *
 * NOTE: This Portuguese translation was produced automatically by an AI
 * language model and has not yet been reviewed by a human translator. Corrections
 * are welcome.
 *
 * @package    tool_mupwned
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['enabled'] = 'Detetar senhas comprometidas';
$string['enabled_desc'] = 'Se esta opção estiver ativa, as senhas comprometidas dos utilizadores são detetadas automaticamente como parte das verificações das regras para as senhas,
utilizando o serviço [Have I Been Pwned](https://haveibeenpwned.com/) com o [modelo k-Anonymity](https://en.wikipedia.org/wiki/K-anonymity).
**Antes de ativar, certifique-se de que os administradores do site podem repor as suas senhas por e-mail.**';
$string['enduser_compromisedpassword'] = 'Esta senha foi comprometida numa violação de dados.';
$string['enduser_expiretokens_info'] = 'Poderá também ter de criar novos tokens de web services e de se autenticar novamente na aplicação móvel.';
$string['enduser_resetpassword_info'] = 'A sua senha apareceu anteriormente numa violação de dados; por motivos de segurança, não pode ser utilizada para aceder a este site. Tem de repor manualmente a sua senha para poder entrar.';
$string['event_user_login_blocked'] = 'Autenticação com senha comprometida bloqueada';
$string['expiretokens'] = 'Expirar tokens de web services e da aplicação móvel';
$string['expiretokens_desc'] = 'Se esta opção estiver ativa, os utilizadores obrigados a repor senhas comprometidas têm também de recriar todos os tokens de web services
e de se autenticar novamente nas suas aplicações móveis.';
$string['passwordpolicy'] = 'zero ocorrências em violações de dados conhecidas';
$string['passwordpolicyinactive'] = '***A opção "Ativar regras para as senhas" ou "Verificar senha ao autenticar" não está ativa; o bloqueio de senhas comprometidas NÃO funcionará.***';
$string['pluginname'] = 'Bloqueio de senhas comprometidas';
$string['privacy:metadata'] = 'O módulo Bloqueio de senhas comprometidas não armazena quaisquer informações pessoais';
$string['resetpassword'] = 'Exigir a reposição da senha';
$string['resetpassword_desc'] = 'Quando esta opção está ativa, os utilizadores com senhas comprometidas não podem entrar enquanto não repuserem a sua senha.';
$string['warning_passwordpolicy_disabled'] = 'A configuração "Ativar regras para as senhas" está desativada; não será possível detetar senhas comprometidas.';
$string['warning_passwordpolicycheckonlogin_disabled'] = 'A configuração "Verificar senha ao autenticar" está desativada; não é possível bloquear autenticações com senhas comprometidas.';
