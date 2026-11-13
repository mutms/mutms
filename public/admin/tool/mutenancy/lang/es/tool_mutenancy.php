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
 * Strings for component 'tool_mutenancy', language 'es'.
 *
 * NOTE: This Spanish translation was produced automatically by an AI language
 * model and has not yet been reviewed by a human translator. Corrections are
 * welcome.
 *
 * @package    tool_mutenancy
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['associate'] = 'Usuario asociado';
$string['associate_add'] = 'Asociar usuarios';
$string['associate_add_info'] = 'Los usuarios que no pertenecen al inquilino pueden asociarse a él añadiéndolos a la cohorte de usuarios asociados.
Los usuarios que no pertenecen a ningún inquilino pueden estar asociados a varios inquilinos. Varios inquilinos pueden compartir la misma cohorte de usuarios asociados.';
$string['associate_cohort'] = 'Cohorte de usuarios asociados';
$string['associate_cohort_create'] = 'Crear cohorte de usuarios asociados';
$string['associate_cohort_create_help'] = 'Seleccione esta opción para crear una nueva cohorte para los usuarios asociados al inquilino.';
$string['associate_cohort_help'] = 'Seleccione la cohorte que contiene los usuarios globales que deben participar en el inquilino.

Los usuarios globales pueden estar asociados a varios inquilinos, pero las cuentas de un inquilino no pueden asociarse a ningún otro inquilino.';
$string['associate_remove'] = 'Desasociar usuario';
$string['associate_remove_info'] = 'Los usuarios se desasocian del inquilino quitándolos de la cohorte de usuarios asociados.
Esto puede afectar a varios inquilinos cuando comparten la misma cohorte de usuarios asociados.';
$string['auth_edit'] = 'Editar autenticación';
$string['boost_edit'] = 'Editar Boost';
$string['bulk_allocate'] = 'Asignar usuarios a un inquilino';
$string['bulk_allocate_info'] = 'Los siguientes usuarios se asignarán al inquilino seleccionado:

{$a}

Se quitarán sus asignaciones como gestores de inquilinos y los usuarios perderán el acceso a todos los demás inquilinos. Las cuentas de administrador no se modificarán.';
$string['bulk_deallocate'] = 'Anular la asignación de miembros de inquilinos';
$string['bulk_deallocate_info'] = 'Se anulará la asignación de los siguientes usuarios a sus inquilinos y pasarán a ser usuarios normales:

{$a}';
$string['cachedef_config'] = 'Caché de configuración de inquilinos';
$string['cachedef_tenant'] = 'Caché de registros de inquilinos';
$string['config_default'] = 'Valor por defecto';
$string['config_default_value'] = 'Valor por defecto ({$a})';
$string['config_override'] = 'Sustituir';
$string['config_override_value'] = 'Sustituir el valor por defecto ({$a})';
$string['config_value'] = 'Valor';
$string['environment_corepatch_error'] = 'Falta el parche de multiinquilino requerido o su versión no es válida';
$string['environment_corepatch_ok'] = 'Se ha detectado la versión correcta del parche';
$string['error:changerequired'] = 'Es necesario un cambio';
$string['error:memberlimitreached'] = 'Se ha alcanzado el límite de miembros';
$string['event_appearance_updated'] = 'Apariencia del inquilino actualizada';
$string['event_auth_updated'] = 'Autenticación del inquilino actualizada';
$string['event_tenant_archived'] = 'Inquilino archivado';
$string['event_tenant_created'] = 'Inquilino creado';
$string['event_tenant_deleted'] = 'Inquilino eliminado';
$string['event_tenant_restored'] = 'Inquilino restaurado';
$string['event_tenant_updated'] = 'Inquilino actualizado';
$string['event_user_allocated'] = 'Asignación del usuario a un inquilino modificada';
$string['login_tenant_select'] = 'Seleccionar sitio';
$string['login_tenant_select_a'] = 'Seleccionar: {$a}';
$string['logos_edit'] = 'Editar logotipos';
$string['member_confirm_info'] = 'En la mayoría de los casos las cuentas de los miembros no deberían confirmarse aquí.

Se recomienda utilizar la opción *Reenviar correo de confirmación* si el usuario no ha recibido el correo de confirmación.';
$string['member_create'] = 'Crear cuenta';
$string['member_create_info'] = 'La nueva cuenta de miembro del inquilino podrá acceder a los cursos de su inquilino y, si está permitido, también a los cursos globales.

No podrá acceder a los cursos de ningún otro inquilino.';
$string['member_delete_info'] = 'La eliminación de la cuenta de un miembro del inquilino no se puede deshacer.';
$string['member_managers_info'] = 'Los gestores del inquilino no son visibles automáticamente en los inquilinos. Deben añadirse a
la cohorte de usuarios asociados si se espera que participen en el inquilino.';
$string['member_resend_info'] = 'Los correos de confirmación de cuentas nuevas piden a los usuarios que demuestren que
son titulares de la dirección de correo indicada durante el registro.

Se trata de una medida de seguridad importante; no se recomienda confirmar manualmente las cuentas nuevas.';
$string['member_suspend_info'] = 'La suspensión de cuentas se diseñó para impedir que los usuarios inicien sesión y
también debería detener todos los correos y notificaciones salientes, nada más.

Por desgracia, los usuarios y los desarrolladores malinterpretan a menudo la suspensión de cuentas, lo que puede dar lugar a un comportamiento
incoherente de algunos plugins y, en casos poco frecuentes, puede causar pequeñas pérdidas de datos.';
$string['member_unlock_info'] = 'Las cuentas de usuario se bloquean tras varios intentos fallidos de inicio de sesión. Al desbloquear la cuenta, el usuario puede volver a intentarlo.';
$string['member_unsuspend_info'] = 'Una vez reactivada la cuenta, el usuario debería poder iniciar sesión de nuevo
y empezará a recibir correos y notificaciones.';
$string['member_update'] = 'Editar cuenta';
$string['mutenancy:admin'] = 'Administrar inquilinos';
$string['mutenancy:allocate'] = 'Asignar usuarios a inquilinos';
$string['mutenancy:configappearance'] = 'Configurar el tema y la imagen de marca del inquilino';
$string['mutenancy:configauth'] = 'Configurar la autenticación del inquilino';
$string['mutenancy:membercreate'] = 'Crear cuenta de miembro del inquilino';
$string['mutenancy:memberdelete'] = 'Eliminar cuenta de miembro del inquilino';
$string['mutenancy:memberupdate'] = 'Editar cuenta de miembro del inquilino';
$string['mutenancy:switch'] = 'Cambiar a un inquilino';
$string['mutenancy:view'] = 'Ver inquilino';
$string['navigation_category'] = 'Categoría del inquilino';
$string['navigation_top'] = 'Gestión de inquilinos';
$string['pluginname'] = 'Multiinquilino';
$string['privacy:metadata:tool_mutenancy_manager'] = 'Gestores de inquilinos';
$string['privacy:metadata:tool_mutenancy_manager:tenantid'] = 'ID del inquilino';
$string['privacy:metadata:tool_mutenancy_manager:timecreated'] = 'Fecha de asignación del gestor';
$string['privacy:metadata:tool_mutenancy_manager:usercreated'] = 'Usuario que asignó al gestor';
$string['privacy:metadata:tool_mutenancy_manager:userid'] = 'ID de usuario del gestor';
$string['role_tenantmanager_archetype'] = 'ARQUETIPO: Gestor del inquilino';
$string['role_tenantmanager_description'] = 'El rol de gestor del inquilino se asigna automáticamente a todos los gestores del inquilino en los contextos del inquilino y de la categoría del inquilino.

Este rol no debe asignarse manualmente; no cambie el nombre corto del rol.';
$string['role_tenantmanager_name'] = 'Gestor del inquilino';
$string['role_tenantuser_archetype'] = 'ARQUETIPO: Usuario del inquilino';
$string['role_tenantuser_description'] = 'El rol de usuario del inquilino se asigna automáticamente a todos los usuarios del inquilino en los contextos de la categoría del inquilino.

Este rol no debe asignarse manualmente; no cambie el nombre corto del rol.';
$string['role_tenantuser_name'] = 'Usuario del inquilino';
$string['secondary_tenant_appearance'] = 'Apariencia';
$string['secondary_tenant_auth'] = 'Autenticación';
$string['secondary_tenant_details'] = 'Detalles';
$string['secondary_tenant_users'] = 'Usuarios';
$string['setting_allowguests'] = 'Permitir el acceso de invitados a los inquilinos';
$string['setting_allowguests_desc'] = 'Habilite este ajuste si desea permitir el acceso de invitados a los cursos de los inquilinos.

Tenga en cuenta que esta función requiere que el acceso de invitados esté habilitado a nivel del sitio. Además, los cursos del inquilino deben tener habilitado el acceso de invitados, y los usuarios deberían estar asociados a los inquilinos en lugar de ser miembros de ellos.';
$string['setting_tenantentities'] = 'Nombre de la entidad inquilino en plural';
$string['setting_tenantentities_desc'] = 'Si se indica, la palabra en plural "Inquilinos" se sustituirá por el nombre personalizado indicado. Por ejemplo: Unidades, Facultades o Eventos';
$string['setting_tenantentity'] = 'Nombre de la entidad inquilino en singular';
$string['setting_tenantentity_desc'] = 'Si se indica, la palabra en singular "Inquilino" se sustituirá por el nombre personalizado indicado. Por ejemplo: Unidad, Facultad o Evento';
$string['setting_tenantlimit'] = 'Límite de inquilinos';
$string['setting_tenantlimit_desc'] = 'Especifica cuántos inquilinos se pueden crear. Tenga en cuenta que los sitios con más de 100 inquilinos pueden sufrir problemas de rendimiento y de usabilidad.';
$string['setting_tenantprimarynav'] = 'Añadir la gestión de inquilinos al menú principal';
$string['setting_tenantprimarynav_desc'] = 'Si se habilita, la sección de gestión del inquilino actual se añade al menú principal. Está pensado para gestores dedicados de inquilinos o para usuarios que pueden cambiar a inquilinos.';
$string['settings'] = 'Configuración de multiinquilino';
$string['taskcron'] = 'Tarea de limpieza de multiinquilino';
$string['tenancy_activate'] = 'Activar el modo multiinquilino';
$string['tenancy_activate_info'] = 'Durante la activación del modo multiinquilino se crearán nuevos roles para los gestores del inquilino y los usuarios del inquilino.

El modo multiinquilino solo se puede desactivar después de eliminar todos los inquilinos.';
$string['tenancy_deactivate'] = 'Desactivar el modo multiinquilino';
$string['tenancy_deactivate_info'] = 'El rol de gestor del inquilino se eliminará durante la desactivación del modo multiinquilino.';
$string['tenant'] = 'Inquilino';
$string['tenant_actions'] = 'Acciones';
$string['tenant_archive'] = 'Archivar inquilino';
$string['tenant_archive_a'] = 'Archivar: {$a}';
$string['tenant_archive_info'] = 'Archivar el inquilino:

* impide que los miembros del inquilino inicien sesión y detiene todos los correos y notificaciones salientes,
* oculta la categoría del inquilino.

Archivar el inquilino es un paso obligatorio antes de poder eliminarlo.';
$string['tenant_archived'] = 'Archivado';
$string['tenant_associates'] = 'Usuarios asociados';
$string['tenant_category'] = 'Categoría del inquilino';
$string['tenant_categoryidnumber'] = 'Número de ID de la categoría del inquilino';
$string['tenant_categoryname'] = 'Nombre de la categoría del inquilino';
$string['tenant_cohort'] = 'Cohorte del inquilino';
$string['tenant_cohortidnumber'] = 'Número de ID de la cohorte del inquilino';
$string['tenant_cohortname'] = 'Nombre de la cohorte del inquilino';
$string['tenant_create'] = 'Añadir inquilino';
$string['tenant_create_a'] = 'Añadir: {$a}';
$string['tenant_delete'] = 'Eliminar inquilino';
$string['tenant_delete_a'] = 'Eliminar: {$a}';
$string['tenant_delete_info'] = 'Durante la eliminación del inquilino:

* se revocarán los permisos de gestor del inquilino
* la cohorte del inquilino se convertirá en una cohorte normal
* todos los miembros del inquilino se asignarán al sitio global

Las subcategorías, los cursos y los datos de los usuarios no se modificarán.';
$string['tenant_delete_movetotenant'] = 'Asignar los miembros a';
$string['tenant_idnumber'] = 'ID del inquilino';
$string['tenant_loginshow'] = 'Mostrar el inquilino en la página de inicio de sesión';
$string['tenant_loginurl'] = 'URL de inicio de sesión del inquilino';
$string['tenant_loginurl_copy'] = 'Copiar al portapapeles la URL de inicio de sesión del inquilino';
$string['tenant_manager'] = 'Gestor del inquilino';
$string['tenant_managers'] = 'Gestores del inquilino';
$string['tenant_member'] = 'Miembro del inquilino';
$string['tenant_member_a'] = 'Miembro: {$a}';
$string['tenant_memberlimit'] = 'Límite de miembros del inquilino';
$string['tenant_memberlimit_help'] = 'Especifica el número máximo de cuentas de miembros que se pueden crear en el inquilino.

Tenga en cuenta que los usuarios asociados no se contabilizan en este límite.';
$string['tenant_name'] = 'Nombre del inquilino';
$string['tenant_restore'] = 'Restaurar inquilino archivado';
$string['tenant_restore_a'] = 'Restaurar del archivo: {$a}';
$string['tenant_restore_info'] = 'La restauración del inquilino debería revertir la mayoría de los cambios realizados al archivarlo.

No obstante, se recomienda comprobar después todos los ajustes del inquilino y la visibilidad de la categoría.';
$string['tenant_sitefullname'] = 'Nombre del sitio del inquilino';
$string['tenant_siteshortname'] = 'Nombre corto del sitio del inquilino';
$string['tenant_switch'] = 'Cambiar de inquilino';
$string['tenant_switch_a'] = 'Cambiar: {$a}';
$string['tenant_switch_info'] = 'Al cambiar a un inquilino se modifica la apariencia del sitio y se restringe la lista de usuarios candidatos disponibles para algunas acciones.

Tenga en cuenta que cambiar a un inquilino no equivale a ser miembro de él; existen restricciones adicionales que afectan a los miembros del inquilino.';
$string['tenant_switch_my'] = 'Mis inquilinos';
$string['tenant_switch_my_a'] = 'Mis {$a}';
$string['tenant_switch_notenant'] = 'Sin inquilino';
$string['tenant_switch_notenant_a'] = 'Sin {$a}';
$string['tenant_switch_other'] = 'Otros inquilinos';
$string['tenant_switch_other_a'] = 'Resto de {$a}';
$string['tenant_update'] = 'Editar inquilino';
$string['tenant_update_a'] = 'Editar: {$a}';
$string['tenant_users'] = 'Usuarios del inquilino';
$string['tenant_users_a'] = 'Usuarios: {$a}';
$string['tenants'] = 'Inquilinos';
$string['user_allocate'] = 'Asignar usuario';
$string['user_allocate_info'] = 'Al asignar un usuario a un inquilino se quitan sus asignaciones como gestor de inquilinos
y perderá el acceso a todos los demás inquilinos.';
$string['user_tenants'] = 'Asociación con inquilinos';
$string['user_tenants_a'] = 'Asociación: {$a}';
