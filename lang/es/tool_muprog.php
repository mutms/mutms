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
 * Strings for component 'tool_muprog', language 'es'.
 *
 * NOTE: This Spanish translation was produced automatically by an AI language
 * model and has not yet been reviewed by a human translator. Corrections are
 * welcome.
 *
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['allocation'] = 'Adscripción';
$string['allocation_archive'] = 'Archivar adscripción';
$string['allocation_reset'] = 'Reiniciar el progreso en el programa';
$string['allocation_reset_updateallocation'] = 'Editar adscripción';
$string['allocation_restore'] = 'Restaurar adscripción';
$string['allocation_update'] = 'Editar adscripción';
$string['allocationdate'] = 'Fecha de adscripción';
$string['allocationend'] = 'Fin de la adscripción';
$string['allocationend_help'] = 'El significado de la fecha de fin de la adscripción depende de las fuentes de adscripción habilitadas. Normalmente, si se especifica, no es posible realizar nuevas adscripciones después de esta fecha.';
$string['allocations'] = 'Adscripciones';
$string['allocationsources'] = 'Fuentes de adscripción';
$string['allocationstart'] = 'Inicio de la adscripción';
$string['allocationstart_help'] = 'El significado de la fecha de inicio de la adscripción depende de las fuentes de adscripción habilitadas. Normalmente, si se especifica, solo es posible realizar nuevas adscripciones después de esta fecha.';
$string['allprograms'] = 'Todos los programas';
$string['appendinto'] = 'Añadir dentro del elemento';
$string['appenditem'] = 'Añadir elemento al final';
$string['archived'] = 'Archivado';
$string['attendance'] = 'Asistencia presencial';
$string['attendance_effective'] = 'Fecha efectiva';
$string['attendance_status'] = 'Estado';
$string['attendance_status_completed'] = 'Finalizado';
$string['attendance_status_failed'] = 'No superado';
$string['attendance_status_noshow'] = 'No presentado';
$string['attendance_take'] = 'Registrar asistencia';
$string['calendarprogramdue'] = 'Fecha límite de {$a}';
$string['calendarprogramend'] = 'Fin de {$a}';
$string['calendarprogramstart'] = 'Inicio de {$a}';
$string['catalogue'] = 'Catálogo de programas';
$string['catalogue_actions'] = 'Acciones del catálogo';
$string['catalogue_dofilter'] = 'Buscar';
$string['catalogue_resetfilter'] = 'Reiniciar';
$string['catalogue_searchtext'] = 'Texto de búsqueda';
$string['catalogue_tag'] = 'Filtrar por etiqueta';
$string['certificatetemplatechoose'] = 'Elegir una plantilla...';
$string['cohorts'] = 'Visible para las cohortes';
$string['cohorts_help'] = 'Los programas no públicos pueden hacerse visibles para los miembros de las cohortes indicadas.

El estado de visibilidad no afecta a los programas ya adscritos.';
$string['columnusedalready'] = 'La columna ya está en uso';
$string['completiondate'] = 'Fecha de finalización';
$string['completiondelay'] = 'Demora de la finalización';
$string['completionoverride'] = 'Sobrescribir la finalización';
$string['creategroups'] = 'Grupos en los cursos';
$string['creategroups_help'] = 'Si se habilita, se creará un grupo en cada curso añadido al programa y todos los usuarios adscritos se añadirán como miembros del grupo.';
$string['credits'] = 'Créditos';
$string['credits_progress'] = 'Créditos actuales: {$a->current}/{$a->total}';
$string['credits_requiredcredits'] = 'Créditos requeridos: {$a}';
$string['currentcontextonly'] = 'Excluir subcategorías';
$string['customfields'] = 'Campos personalizados de programa';
$string['customfields_allocation'] = 'Campos personalizados de adscripción a programa';
$string['customfieldsettings'] = 'Ajustes comunes de los campos personalizados de programas';
$string['customfieldvisible:allocated'] = 'Usuarios adscritos a programas';
$string['customfieldvisible:allocatee'] = 'Usuario adscrito';
$string['customfieldvisible:everyone'] = 'Todos los que pueden ver otros detalles del programa';
$string['customfieldvisible:viewcapability'] = 'Usuarios con la capacidad de ver programas';
$string['customfieldvisibleto'] = 'El contenido del campo es visible para';
$string['deleteallocation'] = 'Eliminar adscripción al programa';
$string['deleteattendance'] = 'Eliminar asistencia presencial';
$string['deletecourse'] = 'Quitar curso';
$string['deletecredits'] = 'Quitar créditos';
$string['deleteset'] = 'Eliminar conjunto';
$string['duedate'] = 'Fecha límite';
$string['enrolrole'] = 'Rol en el curso';
$string['enrolrole_desc'] = 'Seleccione el rol que utilizarán los programas para la matriculación en los cursos';
$string['errorcontentproblem'] = 'Se ha detectado un problema en la estructura del contenido del programa, la finalización del programa no se rastreará correctamente.';
$string['errorcoursemissing'] = 'Falta el curso';
$string['errorcoursesmissing'] = 'Cursos que faltan: {$a}';
$string['errordifferenttenant'] = 'No se puede acceder a un programa de otro inquilino';
$string['errorinvalidoverridedates'] = 'Fechas sobrescritas no válidas';
$string['errornoallocation'] = 'El programa no está adscrito';
$string['errornoallocations'] = 'No se han encontrado adscripciones de usuarios';
$string['errornomyprograms'] = 'No está adscrito a ningún programa.';
$string['errornoprograms'] = 'No se han encontrado programas.';
$string['errornorequests'] = 'No se han encontrado solicitudes de programa';
$string['errornotenabled'] = 'El plugin Programas no está habilitado';
$string['event_allocation_archived'] = 'Adscripción de usuario archivada';
$string['event_allocation_completed'] = 'El usuario ha finalizado el programa';
$string['event_allocation_created'] = 'Usuario adscrito al programa';
$string['event_allocation_deleted'] = 'Adscripción del usuario al programa anulada';
$string['event_allocation_restored'] = 'Adscripción de usuario restaurada';
$string['event_allocation_updated'] = 'Adscripción del usuario al programa actualizada';
$string['event_allocation_viewed'] = 'Adscripción al programa vista';
$string['event_catalogue_program_viewed'] = 'Catálogo de programas visto';
$string['event_program_archived'] = 'Programa archivado';
$string['event_program_created'] = 'Programa creado';
$string['event_program_deleted'] = 'Programa eliminado';
$string['event_program_restored'] = 'Programa restaurado';
$string['event_program_updated'] = 'Programa actualizado';
$string['event_program_viewed'] = 'Programa visto';
$string['evidence'] = 'Otra evidencia';
$string['evidence_details'] = 'Detalles';
$string['evidence_detailsdefault'] = 'Detalles por defecto';
$string['evidencedate'] = 'Fecha de finalización según la evidencia';
$string['evidenceupdate'] = 'Editar otra evidencia';
$string['evidenceupload'] = 'Subir evidencias de finalización';
$string['evidenceupload_csvfile'] = 'Archivo CSV';
$string['evidenceupload_errors'] = 'Número de filas no válidas: {$a}';
$string['evidenceupload_skipped'] = 'Número de filas omitidas: {$a}';
$string['evidenceupload_updated'] = 'Evidencias de finalización actualizadas. Número de usuarios: {$a}';
$string['export'] = 'Exportar programas';
$string['exportfile_info'] = 'info';
$string['exportfile_programs'] = 'programas';
$string['exportformat'] = 'Formato de archivo';
$string['exportformat_csv'] = 'CSV';
$string['exportformat_json'] = 'JSON';
$string['extdb_query_allocation'] = 'Adscripción a programa';
$string['favourite'] = 'Programa destacado';
$string['fixeddate'] = 'En una fecha fija';
$string['importallocationend'] = 'Fin de la adscripción ({$a})';
$string['importallocationstart'] = 'Inicio de la adscripción ({$a})';
$string['importprogramallocation'] = 'Importar ajustes de adscripción';
$string['importprogramallocationconfirmation'] = 'Está importando los ajustes de adscripción del programa __{$a->fullname} / {$a->idnumber} / {$a->category}__.

Seleccione todos los ajustes que desea importar.';
$string['importprogramcontent'] = 'Importar contenido de programa';
$string['importprogramcontentconfirmation'] = 'Está importando el contenido del programa __{$a->fullname} / {$a->idnumber} / {$a->category}__.';
$string['importprogramdue'] = 'Fecha límite del programa ({$a})';
$string['importprogramend'] = 'Fin del programa ({$a})';
$string['importprogramstart'] = 'Inicio del programa ({$a})';
$string['importselectprogram'] = 'Seleccionar programa';
$string['includesubcontexts'] = 'Incluir subcontextos';
$string['invalidallocationdates'] = 'Fechas de adscripción al programa no válidas';
$string['invalidcompletiondate'] = 'Fecha de finalización del programa no válida';
$string['item'] = 'Elemento';
$string['item_create_attendance'] = 'Añadir asistencia presencial';
$string['item_create_course'] = 'Añadir cursos';
$string['item_create_credits'] = 'Añadir créditos';
$string['item_create_set'] = 'Añadir conjunto';
$string['item_type'] = 'Tipo de elemento';
$string['item_update_attendance'] = 'Editar asistencia presencial';
$string['itemcompletion'] = 'Finalización de elemento de programa';
$string['itempoints'] = 'Puntos';
$string['itemrecalculate'] = 'Recalcular la finalización del elemento';
$string['management'] = 'Gestión de programas';
$string['management_index_actions'] = 'Acciones de programas';
$string['management_program_allocation_actions'] = 'Acciones de los ajustes de adscripción';
$string['management_program_general_actions'] = 'Acciones del programa';
$string['management_program_users_actions'] = 'Acciones de usuarios';
$string['messageprovider:allocation_notification'] = 'Notificación de adscripción a programa';
$string['messageprovider:approval_reject_notification'] = 'Notificación de rechazo de solicitud de programa';
$string['messageprovider:approval_request_notification'] = 'Notificación de solicitud de aprobación de programa';
$string['messageprovider:cc_supervisor_notification'] = 'Copia de las notificaciones de programas de los subordinados';
$string['messageprovider:completion_notification'] = 'Notificación de programa finalizado';
$string['messageprovider:deallocation_notification'] = 'Notificación de anulación de adscripción a programa';
$string['messageprovider:due_notification'] = 'Notificación de programa atrasado';
$string['messageprovider:duesoon_notification'] = 'Notificación de fecha límite de programa próxima';
$string['messageprovider:endcompleted_notification'] = 'Notificación de fin de programa finalizado';
$string['messageprovider:endfailed_notification'] = 'Notificación de fin de programa no superado';
$string['messageprovider:endsoon_notification'] = 'Notificación de fecha de fin de programa próxima';
$string['messageprovider:reset_notification'] = 'Notificación de reinicio de programa';
$string['messageprovider:start_notification'] = 'Notificación de inicio de programa';
$string['moveafter'] = 'Mover "{$a->item}" después de "{$a->target}"';
$string['movebefore'] = 'Mover "{$a->item}" antes de "{$a->target}"';
$string['moveinto'] = 'Mover "{$a->item}" dentro de "{$a->target}"';
$string['moveitem'] = 'Mover elemento';
$string['moveitemcancel'] = 'Cancelar el movimiento';
$string['muprog:addcourse'] = 'Añadir curso a programas';
$string['muprog:addtocertifications'] = 'Añadir programa a certificaciones';
$string['muprog:admin'] = 'Administración avanzada de programas';
$string['muprog:allocate'] = 'Adscribir usuarios a programas';
$string['muprog:clone'] = 'Clonar contenido y ajustes de programas';
$string['muprog:configurecustomfields'] = 'Configurar campos personalizados de programas';
$string['muprog:deallocate'] = 'Anular la adscripción de usuarios a programas';
$string['muprog:delete'] = 'Eliminar programas';
$string['muprog:edit'] = 'Añadir y editar programas';
$string['muprog:export'] = 'Exportar programas';
$string['muprog:manageallocation'] = 'Gestionar adscripciones de usuarios';
$string['muprog:manageevidence'] = 'Gestionar otras evidencias de finalización';
$string['muprog:reset'] = 'Reiniciar el progreso en programas';
$string['muprog:takeattendance'] = 'Registrar asistencia presencial';
$string['muprog:upload'] = 'Subir programas';
$string['muprog:view'] = 'Ver la gestión de programas';
$string['muprog:viewcatalogue'] = 'Acceder al catálogo de programas';
$string['muprog:viewuserprograms'] = 'Ver los programas de otros usuarios';
$string['myprograms'] = 'Mis programas';
$string['notification_allocation'] = 'Usuario adscrito';
$string['notification_allocation_body'] = 'Hola, {$a->user_fullname}:

se le ha adscrito al programa "{$a->program_fullname}", la fecha de inicio es {$a->program_startdate}.
';
$string['notification_allocation_description'] = 'Notificación enviada a los usuarios cuando se les adscribe a un programa.';
$string['notification_allocation_subject'] = 'Notificación de adscripción a programa';
$string['notification_cc_supervisor_body'] = 'Hola, {$a->supervisor_fullname}:

se ha enviado una notificación al siguiente usuario:

* {$a->subordinate_title}: {$a->user_fullname}
* Tipo de notificación: {$a->notification_name}
* Programa relacionado: {$a->program_fullname}

';
$string['notification_cc_supervisor_subject'] = 'Notificación para {$a->supervisor_title} - {$a->program_fullname}';
$string['notification_completion'] = 'Programa finalizado';
$string['notification_completion_body'] = 'Hola, {$a->user_fullname}:

ha finalizado el programa "{$a->program_fullname}".
';
$string['notification_completion_description'] = 'Notificación enviada a los usuarios cuando finalizan su programa.';
$string['notification_completion_subject'] = 'Programa finalizado';
$string['notification_deallocation'] = 'Adscripción de usuario anulada';
$string['notification_deallocation_body'] = 'Hola, {$a->user_fullname}:

se ha anulado su adscripción al programa "{$a->program_fullname}".
';
$string['notification_deallocation_description'] = 'Notificación enviada a los usuarios cuando se anula su adscripción a un programa.';
$string['notification_deallocation_subject'] = 'Notificación de anulación de adscripción a programa';
$string['notification_due'] = 'Programa atrasado';
$string['notification_due_body'] = 'Hola, {$a->user_fullname}:

se esperaba que finalizara el programa "{$a->program_fullname}" antes del {$a->program_duedate}.
';
$string['notification_due_description'] = 'Notificación enviada a los usuarios cuando la finalización de su programa está atrasada.';
$string['notification_due_subject'] = 'La finalización del programa está atrasada';
$string['notification_duesoon'] = 'Fecha límite del programa próxima';
$string['notification_duesoon_body'] = 'Hola, {$a->user_fullname}:

se espera que finalice el programa "{$a->program_fullname}" el {$a->program_duedate}.
';
$string['notification_duesoon_description'] = 'Notificación enviada a los usuarios antes de la fecha de finalización de su programa, salvo que el programa ya esté finalizado.';
$string['notification_duesoon_subject'] = 'Se espera pronto la finalización del programa';
$string['notification_endcompleted'] = 'Fin de programa finalizado';
$string['notification_endcompleted_body'] = 'Hola, {$a->user_fullname}:

el programa "{$a->program_fullname}" ha terminado, usted ya lo había finalizado anteriormente.
';
$string['notification_endcompleted_description'] = 'Notificación enviada a los usuarios cuando termina un programa que han finalizado.';
$string['notification_endcompleted_subject'] = 'Fin de programa finalizado';
$string['notification_endfailed'] = 'Fin de programa no superado';
$string['notification_endfailed_body'] = 'Hola, {$a->user_fullname}:

el programa "{$a->program_fullname}" ha terminado y usted no ha conseguido finalizarlo.
';
$string['notification_endfailed_description'] = 'Notificación enviada a los usuarios cuando termina un programa que no han conseguido finalizar.';
$string['notification_endfailed_subject'] = 'Fin de programa no superado';
$string['notification_endsoon'] = 'Fecha de fin del programa próxima';
$string['notification_endsoon_body'] = 'Hola, {$a->user_fullname}:

el programa "{$a->program_fullname}" termina el {$a->program_enddate}.
';
$string['notification_endsoon_description'] = 'Notificación enviada a los usuarios antes de la fecha de fin de su programa, salvo que el programa ya esté finalizado.';
$string['notification_endsoon_subject'] = 'El programa termina pronto';
$string['notification_reset'] = 'Progreso del usuario reiniciado';
$string['notification_reset_body'] = 'Hola, {$a->user_fullname}:

se ha reiniciado su progreso en el programa "{$a->program_fullname}".
';
$string['notification_reset_description'] = 'Notificación enviada a los usuarios cuando se reinicia su progreso en el programa.';
$string['notification_reset_subject'] = 'Notificación de reinicio de programa';
$string['notification_start'] = 'Programa iniciado';
$string['notification_start_body'] = 'Hola, {$a->user_fullname}:

el programa "{$a->program_fullname}" ha comenzado.
';
$string['notification_start_description'] = 'Notificación enviada a los usuarios cuando comienza su programa.';
$string['notification_start_subject'] = 'Programa iniciado';
$string['notificationdates'] = 'Fechas de notificación';
$string['notset'] = 'Sin definir';
$string['plugindisabled'] = 'El plugin Matriculación por programa está deshabilitado, los programas no funcionarán.

[Habilitar el plugin ahora]({$a->url})';
$string['pluginname'] = 'Programas';
$string['pluginname_desc'] = 'Los programas están diseñados para permitir la creación de conjuntos de cursos.';
$string['privacy:metadata:field:allocationid'] = 'ID de la adscripción al programa';
$string['privacy:metadata:field:archived'] = 'Indica si el registro está archivado';
$string['privacy:metadata:field:createdby'] = 'Evidencia creada por';
$string['privacy:metadata:field:datajson'] = 'Información sobre la solicitud';
$string['privacy:metadata:field:evidencejson'] = 'Información sobre la evidencia de finalización';
$string['privacy:metadata:field:explanation'] = 'Explicación';
$string['privacy:metadata:field:issueid'] = 'ID de la emisión';
$string['privacy:metadata:field:itemid'] = 'ID del elemento';
$string['privacy:metadata:field:programid'] = 'ID del programa';
$string['privacy:metadata:field:quantity'] = 'Cantidad';
$string['privacy:metadata:field:reason'] = 'Motivo';
$string['privacy:metadata:field:rejectedby'] = 'Solicitud rechazada por';
$string['privacy:metadata:field:sourcedatajson'] = 'Información sobre la fuente de la adscripción';
$string['privacy:metadata:field:sourceid'] = 'Fuente de la adscripción';
$string['privacy:metadata:field:status'] = 'Estado';
$string['privacy:metadata:field:takenby'] = 'Asistencia registrada por';
$string['privacy:metadata:field:timeallocated'] = 'Fecha de adscripción al programa';
$string['privacy:metadata:field:timecompleted'] = 'Fecha de finalización';
$string['privacy:metadata:field:timecreated'] = 'Fecha de creación';
$string['privacy:metadata:field:timedue'] = 'Fecha límite';
$string['privacy:metadata:field:timeeffective'] = 'Fecha efectiva';
$string['privacy:metadata:field:timeend'] = 'Fecha de fin';
$string['privacy:metadata:field:timerejected'] = 'Fecha de rechazo';
$string['privacy:metadata:field:timerequested'] = 'Fecha de solicitud';
$string['privacy:metadata:field:timestart'] = 'Fecha de inicio';
$string['privacy:metadata:field:userid'] = 'ID del usuario';
$string['privacy:metadata:table:tool_muprog_allocation'] = 'Información sobre las adscripciones a programas';
$string['privacy:metadata:table:tool_muprog_attendance'] = 'Asistencia presencial';
$string['privacy:metadata:table:tool_muprog_cert_issue'] = 'Emisiones de certificados de adscripciones a programas';
$string['privacy:metadata:table:tool_muprog_completion'] = 'Finalizaciones de adscripciones a programas';
$string['privacy:metadata:table:tool_muprog_evidence'] = 'Información sobre otras evidencias de finalización';
$string['privacy:metadata:table:tool_muprog_request'] = 'Información sobre la solicitud de adscripción';
$string['program'] = 'Programa';
$string['program_actions'] = 'Acciones del programa';
$string['program_allocations_edit'] = 'Editar adscripciones';
$string['program_archive'] = 'Archivar programa';
$string['program_archive_info'] = 'Archivar el programa:

* suspende las matriculaciones en los cursos del programa,
* elimina del calendario los eventos del programa,
* impide las modificaciones del programa,
* y, en general, oculta el programa a los usuarios normales.

Archivar es un paso necesario antes de poder eliminar el programa.';
$string['program_create'] = 'Añadir programa';
$string['program_delete'] = 'Eliminar programa';
$string['program_delete_info'] = 'Al eliminar el programa se eliminan todos sus datos y se da de baja a los usuarios de los cursos del programa.';
$string['program_move'] = 'Mover programa';
$string['program_restore'] = 'Restaurar programa';
$string['program_restore_info'] = 'Restaurar el programa revierte los cambios realizados al archivarlo.

No obstante, se recomienda verificar después todos los ajustes del programa y los usuarios adscritos.';
$string['program_update'] = 'Editar programa';
$string['programallocations'] = 'Adscripciones al programa';
$string['programautofix'] = 'Reparar el programa automáticamente';
$string['programcategory'] = 'Categoría del programa';
$string['programcompletion'] = 'Fecha de finalización del programa';
$string['programcompletionoverride'] = 'Sobrescribir la finalización del programa';
$string['programdue'] = 'Fecha límite del programa';
$string['programdue_date'] = 'Fecha límite';
$string['programdue_delay'] = 'Fecha límite tras el inicio';
$string['programdue_help'] = 'La fecha límite del programa indica cuándo se espera que los usuarios finalicen el programa.';
$string['programend'] = 'Fin del programa';
$string['programend_date'] = 'Fecha de fin del programa';
$string['programend_delay'] = 'Fin tras el inicio';
$string['programend_help'] = 'Los usuarios no pueden entrar en los cursos del programa después del fin del programa.';
$string['programidnumber'] = 'ID del programa';
$string['programimage'] = 'Imagen del programa';
$string['programname'] = 'Nombre del programa';
$string['programprogress'] = 'Progreso';
$string['programs'] = 'Programas';
$string['programstart'] = 'Inicio del programa';
$string['programstart_allocation'] = 'Iniciar inmediatamente después de la adscripción';
$string['programstart_date'] = 'Fecha de inicio del programa';
$string['programstart_delay'] = 'Retrasar el inicio tras la adscripción';
$string['programstart_help'] = 'Los usuarios no pueden entrar en los cursos del programa antes del inicio del programa.';
$string['programstatus'] = 'Estado del programa';
$string['programstatus_any'] = 'Cualquier estado del programa';
$string['programstatus_archived'] = 'Archivado';
$string['programstatus_archivedcompleted'] = 'Archivado, finalizado';
$string['programstatus_completed'] = 'Finalizado';
$string['programstatus_failed'] = 'No superado';
$string['programstatus_future'] = 'Aún no abierto';
$string['programstatus_open'] = 'Abierto';
$string['programstatus_overdue'] = 'Atrasado';
$string['programurl'] = 'URL del programa';
$string['publicaccess'] = 'Público';
$string['publicaccess_help'] = 'Los programas públicos son visibles para todos los usuarios.

El estado de visibilidad no afecta a los programas ya adscritos.';
$string['purchaseaccess'] = 'Comprar acceso';
$string['resettype'] = 'Tipo de reinicio';
$string['resettype_deallocate'] = 'Solo anulación de la adscripción al programa';
$string['resettype_full'] = 'Purga completa de los cursos';
$string['resettype_none'] = 'Ninguno';
$string['resettype_standard'] = 'Purga estándar de los cursos';
$string['scheduling'] = 'Planificación';
$string['sequencetype'] = 'Tipo de finalización';
$string['sequencetype_allinanyorder'] = 'Todos en cualquier orden';
$string['sequencetype_allinorder'] = 'Todos en orden';
$string['sequencetype_atleast'] = 'Al menos {$a->min}';
$string['sequencetype_minpoints'] = 'Mínimo de puntos: {$a->minpoints}';
$string['set'] = 'Conjunto';
$string['settings'] = 'Ajustes de programas';
$string['source'] = 'Fuente';
$string['source_approval'] = 'Solicitudes con aprobación';
$string['source_approval_allownew'] = 'Permitir aprobaciones';
$string['source_approval_allownew_desc'] = 'Permitir añadir nuevas fuentes de _solicitudes con aprobación_ a los programas';
$string['source_approval_allowrequest'] = 'Permitir nuevas solicitudes';
$string['source_approval_confirm'] = 'Confirme que desea solicitar la adscripción al programa.';
$string['source_approval_daterejected'] = 'Fecha de rechazo';
$string['source_approval_daterequested'] = 'Fecha de solicitud';
$string['source_approval_makerequest'] = 'Solicitar acceso';
$string['source_approval_notification_approval_reject_body'] = 'Hola, {$a->user_fullname}:

su solicitud de acceso al programa "{$a->program_fullname}" ha sido rechazada.

{$a->reason}
';
$string['source_approval_notification_approval_reject_subject'] = 'Notificación de rechazo de solicitud de programa';
$string['source_approval_notification_approval_request_body'] = '
El usuario {$a->user_fullname} ha solicitado acceso al programa "{$a->program_fullname}".
';
$string['source_approval_notification_approval_request_subject'] = 'Notificación de solicitud de programa';
$string['source_approval_rejectionreason'] = 'Motivo del rechazo';
$string['source_approval_request'] = 'Solicitud';
$string['source_approval_requestallowed'] = 'Se permiten solicitudes';
$string['source_approval_requestapprove'] = 'Aprobar solicitud';
$string['source_approval_requestdelete'] = 'Eliminar solicitud';
$string['source_approval_requestnotallowed'] = 'No se permiten solicitudes';
$string['source_approval_requestpending'] = 'Solicitud de acceso pendiente';
$string['source_approval_requestreject'] = 'Rechazar solicitud';
$string['source_approval_requestrejected'] = 'La solicitud de acceso ha sido rechazada';
$string['source_approval_requests'] = 'Solicitudes';
$string['source_cohort'] = 'Adscripción automática por cohorte';
$string['source_cohort_allownew'] = 'Permitir la adscripción por cohorte';
$string['source_cohort_allownew_desc'] = 'Permitir añadir nuevas fuentes de _adscripción automática por cohorte_ a los programas';
$string['source_cohort_cohortstoallocate'] = 'Adscribir cohortes';
$string['source_extdb'] = 'Adscripción por base de datos externa';
$string['source_extdb_allownew'] = 'Permitir la sincronización con base de datos externa';
$string['source_extdb_allownew_desc'] = 'La sincronización con base de datos externa requiere seleccionar una consulta predefinida.
Para utilizar esta fuente de adscripción, los administradores del sitio deben configurar el servidor de base de datos externa y las consultas en _Administración del sitio / Servidor / Bases de datos externas_.';
$string['source_extdb_archiveremoved'] = 'Archivar los usuarios que faltan';
$string['source_extdb_lastsync'] = 'Última actualización de adscripciones';
$string['source_extdb_pendingsync'] = 'Actualización de adscripciones pendiente';
$string['source_extdb_sync'] = 'Procesar adscripciones';
$string['source_extdb_sync_notqueued'] = 'No se ha podido crear la tarea ad hoc de adscripción por base de datos externa';
$string['source_extdb_sync_queued'] = 'Se ha creado la tarea ad hoc de adscripción por base de datos externa';
$string['source_manual'] = 'Adscripción manual';
$string['source_manual_allocateusers'] = 'Adscribir usuarios';
$string['source_manual_csvfile'] = 'Archivo CSV';
$string['source_manual_hasheaders'] = 'La primera fila contiene los nombres de las columnas';
$string['source_manual_potusers'] = 'Candidatos a la adscripción';
$string['source_manual_potusersmatching'] = 'Candidatos a la adscripción coincidentes';
$string['source_manual_result_assigned'] = 'Número de usuarios adscritos al programa: {$a}';
$string['source_manual_result_errors'] = 'Número de errores al adscribir al programa: {$a}';
$string['source_manual_result_skipped'] = 'Número de usuarios ya adscritos al programa: {$a}';
$string['source_manual_timeduecolumn'] = 'Columna de la fecha límite';
$string['source_manual_timeendcolumn'] = 'Columna del fin del programa';
$string['source_manual_timestartcolumn'] = 'Columna del inicio del programa';
$string['source_manual_uploadusers'] = 'Subir adscripciones';
$string['source_manual_usercolumn'] = 'Columna de identificación del usuario';
$string['source_manual_usermapping'] = 'Identificar a los usuarios por';
$string['source_manual_userupload_allocated'] = 'Adscrito a \'{$a}\'';
$string['source_manual_userupload_alreadyallocated'] = 'Ya adscrito a \'{$a}\'';
$string['source_manual_userupload_invalidprogram'] = 'No se puede adscribir a \'{$a}\'';
$string['source_mucertify'] = 'Certificaciones';
$string['source_mucertify_allownew'] = 'Permitir la adscripción por certificaciones';
$string['source_mucertify_allownew_desc'] = 'Permitir añadir nuevas fuentes de _certificación_ a los programas';
$string['source_program'] = 'Programa finalizado';
$string['source_program_allownew'] = 'Permitir la adscripción por finalización de programa';
$string['source_program_allownew_desc'] = 'Permitir añadir nuevas fuentes de _finalización de programa_ a los programas';
$string['source_program_completedprogram'] = 'Programa que se debe finalizar';
$string['source_selfallocation'] = 'Autoadscripción';
$string['source_selfallocation_allocate'] = 'Inscribirse';
$string['source_selfallocation_allownew'] = 'Permitir la autoadscripción';
$string['source_selfallocation_allownew_desc'] = 'Permitir añadir nuevas fuentes de _autoadscripción_ a los programas';
$string['source_selfallocation_allowsignup'] = 'Permitir nuevas inscripciones';
$string['source_selfallocation_confirm'] = 'Confirme que desea adscribirse al programa.';
$string['source_selfallocation_enable'] = 'Habilitar la autoadscripción';
$string['source_selfallocation_key'] = 'Clave de acceso';
$string['source_selfallocation_keyrequired'] = 'Se requiere la clave de acceso';
$string['source_selfallocation_maxusers'] = 'Máximo de usuarios';
$string['source_selfallocation_maxusers_status'] = 'Usuarios {$a->count}/{$a->max}';
$string['source_selfallocation_maxusersreached'] = 'Ya se ha alcanzado el número máximo de usuarios autoadscritos';
$string['source_selfallocation_signupallowed'] = 'Se permiten inscripciones';
$string['source_selfallocation_signupnotallowed'] = 'No se permiten inscripciones';
$string['taballocation'] = 'Ajustes de adscripción';
$string['tabcontent'] = 'Contenido';
$string['tabgeneral'] = 'General';
$string['tabusers'] = 'Usuarios';
$string['tabvisibility'] = 'Visibilidad en el catálogo';
$string['tagarea_tool_muprog_program'] = 'Programas';
$string['task_extdb_cron'] = 'Cron de adscripción a programas por base de datos externa';
$string['task_extdb_sync'] = 'Adscripción a programas por base de datos externa';
$string['taskcertificate'] = 'Cron de emisión de certificados de programas';
$string['taskcron'] = 'Cron del plugin Programas';
$string['unlinkeditems'] = 'Elementos no enlazados';
$string['updateattendance'] = 'Editar asistencia presencial';
$string['updatecourse'] = 'Editar curso';
$string['updatecredits'] = 'Editar créditos';
$string['updatescheduling'] = 'Editar planificación';
$string['updateset'] = 'Editar conjunto';
$string['updatesource'] = 'Editar fuente "{$a}"';
$string['upload'] = 'Subir programas';
$string['upload_files'] = 'Archivos';
$string['upload_files_error'] = 'Se esperan varios archivos CSV, un archivo JSON o un archivo Zip';
$string['upload_invalidcount'] = 'Registros no válidos';
$string['upload_preview'] = 'Vista previa de los datos';
$string['upload_status'] = 'Estado';
$string['upload_status_invalid'] = 'No válido';
$string['upload_targetcontext'] = 'Añadir programas al contexto';
$string['upload_uploadcount'] = 'Programas que se subirán';
$string['upload_usecategory'] = 'Usar la columna de categoría para los contextos';
$string['userupload_completion_error'] = 'No se puede actualizar la finalización del programa';
$string['userupload_completion_updated'] = 'Se ha actualizado la finalización del programa';
