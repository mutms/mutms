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
 * Strings for component 'tool_mutenancy', language 'pt'.
 *
 * NOTE: This Portuguese translation was produced automatically by an AI
 * language model and has not yet been reviewed by a human translator. Corrections
 * are welcome.
 *
 * @package    tool_mutenancy
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['associate'] = 'Utilizador associado';
$string['associate_add'] = 'Associar utilizadores';
$string['associate_add_info'] = 'Os utilizadores que não pertencem ao inquilino podem ser associados ao inquilino, adicionando-os ao grupo global de utilizadores associados.
Os utilizadores que não pertencem a um inquilino podem estar associados a vários inquilinos. Vários inquilinos podem partilhar o mesmo grupo global de utilizadores associados.';
$string['associate_cohort'] = 'Grupo global de utilizadores associados';
$string['associate_cohort_create'] = 'Criar grupo global de utilizadores associados';
$string['associate_cohort_create_help'] = 'Selecione esta opção para criar um novo grupo global para os utilizadores associados ao inquilino.';
$string['associate_cohort_help'] = 'Selecione o grupo global que contém os utilizadores globais que devem participar no inquilino.

Os utilizadores globais podem estar associados a vários inquilinos, mas as contas de um inquilino não podem ser associadas a nenhum outro inquilino.';
$string['associate_remove'] = 'Desassociar utilizador';
$string['associate_remove_info'] = 'Os utilizadores são desassociados do inquilino ao serem removidos do grupo global de utilizadores associados.
Isto pode afetar vários inquilinos, quando estes partilham o mesmo grupo global de utilizadores associados.';
$string['auth_edit'] = 'Editar autenticação';
$string['boost_edit'] = 'Editar Boost';
$string['bulk_allocate'] = 'Atribuir utilizadores a um inquilino';
$string['bulk_allocate_info'] = 'Os seguintes utilizadores serão atribuídos ao inquilino selecionado:

{$a}

As atribuições de gestor do inquilino serão removidas e os utilizadores perderão o acesso a todos os outros inquilinos. As contas de administrador não serão modificadas.';
$string['bulk_deallocate'] = 'Anular a atribuição de membros de inquilinos';
$string['bulk_deallocate_info'] = 'A atribuição dos seguintes utilizadores aos inquilinos será anulada e estes passarão a ser utilizadores normais:

{$a}';
$string['cachedef_config'] = 'Cache da configuração dos inquilinos';
$string['cachedef_tenant'] = 'Cache dos registos dos inquilinos';
$string['config_default'] = 'Valor predefinido';
$string['config_default_value'] = 'Valor predefinido ({$a})';
$string['config_override'] = 'Substituir';
$string['config_override_value'] = 'Substituir o valor predefinido ({$a})';
$string['config_value'] = 'Valor';
$string['environment_corepatch_error'] = 'O patch multi-inquilino necessário está em falta ou a sua versão é inválida';
$string['environment_corepatch_ok'] = 'Foi detetada a versão correta do patch';
$string['error:changerequired'] = 'É necessária uma alteração';
$string['error:memberlimitreached'] = 'Foi atingido o limite de membros';
$string['event_appearance_updated'] = 'Aparência do inquilino atualizada';
$string['event_auth_updated'] = 'Autenticação do inquilino atualizada';
$string['event_tenant_archived'] = 'Inquilino arquivado';
$string['event_tenant_created'] = 'Inquilino criado';
$string['event_tenant_deleted'] = 'Inquilino apagado';
$string['event_tenant_restored'] = 'Inquilino restaurado';
$string['event_tenant_updated'] = 'Inquilino atualizado';
$string['event_user_allocated'] = 'Atribuição do utilizador a um inquilino alterada';
$string['login_tenant_select'] = 'Selecionar site';
$string['login_tenant_select_a'] = 'Selecionar: {$a}';
$string['logos_edit'] = 'Editar logótipos';
$string['member_confirm_info'] = 'Na maioria dos casos, as contas dos membros não devem ser confirmadas aqui.

Recomenda-se a utilização da opção *Reenviar e-mail de confirmação* se o utilizador não tiver recebido o e-mail de confirmação.';
$string['member_create'] = 'Criar conta';
$string['member_create_info'] = 'A nova conta de membro do inquilino poderá aceder às disciplinas do seu inquilino e, se for permitido, também às disciplinas globais.

Não poderá aceder às disciplinas de nenhum outro inquilino.';
$string['member_delete_info'] = 'A ação de apagar uma conta de membro do inquilino não é reversível.';
$string['member_managers_info'] = 'Os gestores do inquilino não ficam automaticamente visíveis nos inquilinos. Devem ser adicionados ao
grupo global de utilizadores associados, se for esperado que participem no inquilino.';
$string['member_resend_info'] = 'Os e-mails de confirmação de novas contas pedem aos utilizadores que comprovem que
são donos do endereço de e-mail indicado durante o registo.

Trata-se de uma funcionalidade de segurança importante, não se recomenda a confirmação manual de novas contas.';
$string['member_suspend_info'] = 'A suspensão de contas foi concebida para impedir que os utilizadores se autentiquem e
deve também interromper o envio de todos os e-mails e notificações, nada mais.

Infelizmente, os utilizadores e os programadores interpretam muitas vezes mal a suspensão de contas, o que pode resultar num
comportamento inconsistente de alguns módulos e, em casos raros, causar pequenas perdas de dados.';
$string['member_unlock_info'] = 'As contas de utilizador são bloqueadas após várias tentativas de autenticação falhadas. O desbloqueio da conta permite que o utilizador tente de novo.';
$string['member_unsuspend_info'] = 'Depois de a conta ser reativada, o utilizador deverá conseguir autenticar-se novamente
e voltará a receber e-mails e notificações.';
$string['member_update'] = 'Editar conta';
$string['mutenancy:admin'] = 'Administrar inquilinos';
$string['mutenancy:allocate'] = 'Atribuir utilizadores a inquilinos';
$string['mutenancy:configappearance'] = 'Configurar o tema e a imagem de marca do inquilino';
$string['mutenancy:configauth'] = 'Configurar a autenticação do inquilino';
$string['mutenancy:membercreate'] = 'Criar conta de membro do inquilino';
$string['mutenancy:memberdelete'] = 'Apagar conta de membro do inquilino';
$string['mutenancy:memberupdate'] = 'Editar conta de membro do inquilino';
$string['mutenancy:switch'] = 'Mudar para o inquilino';
$string['mutenancy:view'] = 'Ver inquilino';
$string['navigation_category'] = 'Categoria do inquilino';
$string['navigation_top'] = 'Gestão do inquilino';
$string['pluginname'] = 'Multi-inquilino';
$string['privacy:metadata:tool_mutenancy_manager'] = 'Gestores do inquilino';
$string['privacy:metadata:tool_mutenancy_manager:tenantid'] = 'ID do inquilino';
$string['privacy:metadata:tool_mutenancy_manager:timecreated'] = 'Data de atribuição do gestor';
$string['privacy:metadata:tool_mutenancy_manager:usercreated'] = 'Utilizador que atribuiu o gestor';
$string['privacy:metadata:tool_mutenancy_manager:userid'] = 'ID de utilizador do gestor';
$string['role_tenantmanager_archetype'] = 'PAPEL MODELO: Gestor do inquilino';
$string['role_tenantmanager_description'] = 'O papel de gestor do inquilino é atribuído automaticamente a todos os gestores do inquilino nos contextos do inquilino e da categoria do inquilino.

Este papel não deve ser atribuído manualmente, não altere o nome curto do papel.';
$string['role_tenantmanager_name'] = 'Gestor do inquilino';
$string['role_tenantuser_archetype'] = 'PAPEL MODELO: Utilizador do inquilino';
$string['role_tenantuser_description'] = 'O papel de utilizador do inquilino é atribuído automaticamente a todos os utilizadores do inquilino nos contextos da categoria do inquilino.

Este papel não deve ser atribuído manualmente, não altere o nome curto do papel.';
$string['role_tenantuser_name'] = 'Utilizador do inquilino';
$string['secondary_tenant_appearance'] = 'Aparência';
$string['secondary_tenant_auth'] = 'Autenticação';
$string['secondary_tenant_details'] = 'Detalhes';
$string['secondary_tenant_users'] = 'Utilizadores';
$string['setting_allowguests'] = 'Permitir o acesso de visitantes aos inquilinos';
$string['setting_allowguests_desc'] = 'Ative esta configuração se pretender permitir o acesso de visitantes às disciplinas dos inquilinos.

Tenha em atenção que esta funcionalidade exige que o acesso de visitantes esteja ativado ao nível do site. Além disso, as disciplinas dos inquilinos têm de ter o acesso de visitantes ativado e os utilizadores devem estar associados aos inquilinos, em vez de serem membros dos inquilinos.';
$string['setting_tenantentities'] = 'Designação do inquilino no plural';
$string['setting_tenantentities_desc'] = 'Se for indicada, a palavra "Inquilinos" no plural será substituída pela designação personalizada. Por exemplo: Unidades, Faculdades ou Eventos';
$string['setting_tenantentity'] = 'Designação do inquilino no singular';
$string['setting_tenantentity_desc'] = 'Se for indicada, a palavra "Inquilino" no singular será substituída pela designação personalizada. Por exemplo: Unidade, Faculdade ou Evento';
$string['setting_tenantlimit'] = 'Limite de inquilinos';
$string['setting_tenantlimit_desc'] = 'Especifica quantos inquilinos podem ser criados. Tenha em atenção que os sites com mais de 100 inquilinos podem ter problemas de desempenho e de usabilidade.';
$string['setting_tenantprimarynav'] = 'Adicionar a gestão do inquilino ao menu principal';
$string['setting_tenantprimarynav_desc'] = 'Quando ativada, a secção de gestão do inquilino atual é adicionada ao menu principal. Destina-se a gestores dedicados do inquilino ou a utilizadores que podem mudar para inquilinos.';
$string['settings'] = 'Configurações de multi-inquilino';
$string['taskcron'] = 'Tarefa de limpeza de multi-inquilino';
$string['tenancy_activate'] = 'Ativar multi-inquilino';
$string['tenancy_activate_info'] = 'Durante a ativação do multi-inquilino serão criados novos papéis para os gestores do inquilino e para os utilizadores do inquilino.

O multi-inquilino só pode ser desativado depois de todos os inquilinos serem apagados.';
$string['tenancy_deactivate'] = 'Desativar multi-inquilino';
$string['tenancy_deactivate_info'] = 'O papel de gestor do inquilino será apagado durante a desativação do multi-inquilino.';
$string['tenant'] = 'Inquilino';
$string['tenant_actions'] = 'Ações';
$string['tenant_archive'] = 'Arquivar inquilino';
$string['tenant_archive_a'] = 'Arquivar: {$a}';
$string['tenant_archive_info'] = 'O arquivamento do inquilino:

* impede os membros do inquilino de se autenticarem e interrompe o envio de todos os e-mails e notificações,
* oculta a categoria do inquilino.

O arquivamento é um passo obrigatório antes de o inquilino poder ser apagado.';
$string['tenant_archived'] = 'Arquivado';
$string['tenant_associates'] = 'Utilizadores associados';
$string['tenant_category'] = 'Categoria do inquilino';
$string['tenant_categoryidnumber'] = 'Número de identificação (ID) da categoria do inquilino';
$string['tenant_categoryname'] = 'Nome da categoria do inquilino';
$string['tenant_cohort'] = 'Grupo global do inquilino';
$string['tenant_cohortidnumber'] = 'Número de identificação (ID) do grupo global do inquilino';
$string['tenant_cohortname'] = 'Nome do grupo global do inquilino';
$string['tenant_create'] = 'Adicionar inquilino';
$string['tenant_create_a'] = 'Adicionar: {$a}';
$string['tenant_delete'] = 'Apagar inquilino';
$string['tenant_delete_a'] = 'Apagar: {$a}';
$string['tenant_delete_info'] = 'Ao apagar o inquilino:

* as permissões dos gestores do inquilino serão revogadas
* o grupo global do inquilino será convertido num grupo global normal
* todos os membros do inquilino serão atribuídos ao site global

As subcategorias, as disciplinas e os dados dos utilizadores não serão alterados.';
$string['tenant_delete_movetotenant'] = 'Atribuir os membros a';
$string['tenant_idnumber'] = 'ID do inquilino';
$string['tenant_loginshow'] = 'Mostrar o inquilino na página de autenticação';
$string['tenant_loginurl'] = 'URL de autenticação do inquilino';
$string['tenant_loginurl_copy'] = 'Copiar o URL de autenticação do inquilino para a área de transferência';
$string['tenant_manager'] = 'Gestor do inquilino';
$string['tenant_managers'] = 'Gestores do inquilino';
$string['tenant_member'] = 'Membro do inquilino';
$string['tenant_member_a'] = 'Membro: {$a}';
$string['tenant_memberlimit'] = 'Limite de membros do inquilino';
$string['tenant_memberlimit_help'] = 'Especifica o número máximo de contas de membros do inquilino que podem ser criadas no inquilino.

Tenha em atenção que os utilizadores associados não contam para este limite.';
$string['tenant_name'] = 'Nome do inquilino';
$string['tenant_restore'] = 'Restaurar inquilino arquivado';
$string['tenant_restore_a'] = 'Restaurar do arquivo: {$a}';
$string['tenant_restore_info'] = 'O restauro do inquilino deverá reverter a maioria das alterações efetuadas durante o arquivamento do inquilino.

Recomenda-se, no entanto, que verifique depois todas as configurações do inquilino e a visibilidade da categoria.';
$string['tenant_sitefullname'] = 'Nome do site do inquilino';
$string['tenant_siteshortname'] = 'Nome curto do site do inquilino';
$string['tenant_switch'] = 'Mudar de inquilino';
$string['tenant_switch_a'] = 'Mudar: {$a}';
$string['tenant_switch_info'] = 'A mudança para um inquilino altera a aparência do site e restringe a lista de utilizadores candidatos disponíveis para algumas ações.

Tenha em atenção que mudar para um inquilino não equivale a ser membro do inquilino, existem restrições adicionais que afetam os membros do inquilino.';
$string['tenant_switch_my'] = 'Os meus inquilinos';
$string['tenant_switch_my_a'] = 'A minha lista: {$a}';
$string['tenant_switch_notenant'] = 'Sem inquilino';
$string['tenant_switch_notenant_a'] = 'Sem: {$a}';
$string['tenant_switch_other'] = 'Outros inquilinos';
$string['tenant_switch_other_a'] = 'Restantes: {$a}';
$string['tenant_update'] = 'Editar inquilino';
$string['tenant_update_a'] = 'Editar: {$a}';
$string['tenant_users'] = 'Utilizadores do inquilino';
$string['tenant_users_a'] = 'Utilizadores: {$a}';
$string['tenants'] = 'Inquilinos';
$string['user_allocate'] = 'Atribuir utilizador';
$string['user_allocate_info'] = 'Ao atribuir um utilizador a um inquilino, as suas atribuições de gestor do inquilino são removidas
e o utilizador perde o acesso a todos os outros inquilinos.';
$string['user_tenants'] = 'Associado a inquilinos';
$string['user_tenants_a'] = 'Associado a: {$a}';
