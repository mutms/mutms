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
 * Strings for component 'mod_mubook', language 'fr'.
 *
 * NOTE: This French translation was produced automatically by an AI language
 * model and has not yet been reviewed by a human translator. Corrections are
 * welcome.
 *
 * @package    mod_mubook
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['book_actions'] = 'Actions du livre';
$string['book_print'] = 'Imprimer le livre';
$string['book_printedby'] = 'Imprimé par';
$string['book_toc'] = 'Table des matières';
$string['book_viewall'] = 'Voir tous les chapitres';
$string['chapter_actions'] = 'Actions du chapitre';
$string['chapter_actions_a'] = 'Actions du chapitre : {$a}';
$string['chapter_create'] = 'Ajouter un chapitre';
$string['chapter_delete'] = 'Supprimer le chapitre';
$string['chapter_first_a'] = 'Premier chapitre {$a}';
$string['chapter_move'] = 'Déplacer le chapitre';
$string['chapter_next_a'] = 'Chapitre suivant {$a}';
$string['chapter_position'] = 'Position du chapitre';
$string['chapter_position_after'] = 'Après {$a}';
$string['chapter_position_first'] = 'Premier chapitre';
$string['chapter_previous_a'] = 'Chapitre précédent {$a}';
$string['chapter_title'] = 'Titre du chapitre';
$string['chapter_update'] = 'Modifier le chapitre';
$string['chapters_all'] = 'Tous les chapitres';
$string['content_actions_a'] = 'Actions du contenu {$a}';
$string['content_create'] = 'Ajouter du contenu';
$string['content_create_a'] = 'Ajouter : {$a}';
$string['content_delete'] = 'Supprimer le contenu';
$string['content_files'] = 'Fichiers';
$string['content_hidden'] = 'Contenu caché';
$string['content_sortorder'] = 'Position';
$string['content_text'] = 'Texte';
$string['content_type_disclosure'] = 'Bouton Afficher la solution';
$string['content_type_disclosure_hide'] = 'Masquer la solution';
$string['content_type_disclosure_hide_custom'] = 'Libellé personnalisé pour le masquage';
$string['content_type_disclosure_printed'] = 'Solution';
$string['content_type_disclosure_printed_custom'] = 'Libellé personnalisé pour l’impression';
$string['content_type_disclosure_show'] = 'Afficher la solution';
$string['content_type_disclosure_show_custom'] = 'Libellé personnalisé pour l’affichage';
$string['content_type_disclosure_target'] = 'Contenu cible';
$string['content_type_disclosure_target_info'] = 'L’affichage de la solution porte sur le contenu suivant du même chapitre';
$string['content_type_disclosure_target_none'] = 'L’élément suivant n’existe pas, l’affichage de la solution sera désactivé.';
$string['content_type_html'] = 'Texte HTML';
$string['content_type_markdown'] = 'Texte Markdown';
$string['content_type_unknown'] = 'Type de contenu inconnu';
$string['content_type_unsafehtml'] = 'HTML brut non sécurisé';
$string['content_unavailable'] = 'Contenu non disponible';
$string['content_unknowntype'] = 'Le contenu ne peut pas être affiché (type de contenu inconnu « {$a} »).';
$string['content_unsafetrusted'] = 'Contenu non sécurisé de confiance';
$string['content_unsafetrusted_confirmation'] = 'N’activez cette option que si vous faites entièrement confiance à la source de ce HTML et de tous les fichiers associés';
$string['content_update'] = 'Modifier le contenu';
$string['content_update_a'] = 'Modifier : {$a}';
$string['contentdefault'] = 'Type de contenu par défaut';
$string['contentdefault_desc'] = 'Sélectionnez le type de contenu présent dans les nouveaux livres.';
$string['event_all_chapters_viewed'] = 'Page de tous les chapitres consultée';
$string['event_chapter_created'] = 'Chapitre créé';
$string['event_chapter_deleted'] = 'Chapitre supprimé';
$string['event_chapter_moved'] = 'Chapitre déplacé';
$string['event_chapter_updated'] = 'Chapitre mis à jour';
$string['event_chapter_viewed'] = 'Page du chapitre consultée';
$string['event_content_created'] = 'Contenu créé';
$string['event_content_deleted'] = 'Contenu supprimé';
$string['event_content_updated'] = 'Contenu mis à jour';
$string['markdown_alert_caution'] = 'Attention';
$string['markdown_alert_important'] = 'Important';
$string['markdown_alert_note'] = 'Remarque';
$string['markdown_alert_tip'] = 'Astuce';
$string['markdown_alert_warning'] = 'Avertissement';
$string['markdown_html'] = 'Traitement du HTML dans le Markdown';
$string['markdown_html_allow'] = 'Autoriser le HTML';
$string['markdown_html_escape'] = 'Échapper le HTML';
$string['markdown_html_setting'] = 'Traitement par défaut du HTML dans le Markdown';
$string['markdown_html_setting_desc'] = 'Sélectionnez le traitement par défaut du HTML dans le Markdown pour les nouveaux livres interactifs.';
$string['markdown_html_strip'] = 'Retirer le HTML';
$string['markdown_task_completed'] = 'Tâche terminée';
$string['markdown_task_notcompleted'] = 'Tâche non terminée';
$string['modulename'] = 'Livre interactif';
$string['modulename_link'] = 'mod/mubook/view';
$string['modulenameplural'] = 'Livres interactifs';
$string['mubook:addinstance'] = 'Ajouter un livre';
$string['mubook:editchapter'] = 'Ajouter et supprimer des chapitres';
$string['mubook:editcontent'] = 'Modifier le contenu des chapitres';
$string['mubook:usexss'] = 'Utiliser du HTML non sécurisé';
$string['mubook:view'] = 'Voir le contenu du livre';
$string['mubook:viewall'] = 'Voir le livre sur une seule page';
$string['mubook:viewhiddencontent'] = 'Voir le contenu caché';
$string['nochaptercontent'] = 'Le contenu du chapitre n’est pas disponible.';
$string['nocontent'] = 'Aucun contenu n’a encore été ajouté à ce livre.';
$string['nocontent_edit'] = 'Activez le mode d’édition pour créer les chapitres du livre.';
$string['numbering'] = 'Numérotation des chapitres';
$string['numberingdefault'] = 'Numérotation des chapitres par défaut';
$string['page-mod-mubook-viewall'] = 'Tous les chapitres du livre sur une seule page';
$string['page-mod-mubook-viewchapter'] = 'Un chapitre du livre par page';
$string['page-mod-mubook-x'] = 'Toute page d’un livre';
$string['pluginadministration'] = 'Administration du livre';
$string['pluginname'] = 'Livre interactif';
$string['privacy:metadata'] = 'Le module Livre interactif n’enregistre aucune donnée personnelle.';
$string['restoreothertrustunsafe'] = 'Faire confiance au contenu non sécurisé provenant d’autres sites';
$string['restoreothertrustunsafe_desc'] = 'Pour des raisons de sécurité, il n’est pas recommandé de faire confiance au contenu non sécurisé de livres restauré depuis d’autres sites.

Si ce paramètre est désactivé, chaque contenu non sécurisé restauré à partir de sauvegardes d’autres sites devra être marqué manuellement comme étant de confiance.';
$string['subchapter'] = 'Sous-chapitre';
$string['subchapter_actions'] = 'Actions du sous-chapitre';
$string['subchapter_actions_a'] = 'Actions du sous-chapitre : {$a}';
$string['subchapter_create'] = 'Ajouter un sous-chapitre';
$string['subchapter_delete'] = 'Supprimer le sous-chapitre';
$string['subchapter_move'] = 'Déplacer le sous-chapitre';
$string['subchapter_position'] = 'Position du sous-chapitre';
$string['subchapter_position_after'] = 'Après {$a}';
$string['subchapter_position_first'] = 'En premier dans {$a}';
$string['subchapter_title'] = 'Titre du sous-chapitre';
$string['subchapter_update'] = 'Modifier le sous-chapitre';
$string['subchapters'] = 'Sous-chapitres';
$string['subchapters_delete_a'] = 'Confirmer la suppression des sous-chapitres (nombre : {$a})';
$string['subchapters_orphaned'] = 'Sous-chapitres orphelins';
$string['tagarea_mubook_chapter'] = 'Chapitres de livres interactifs';
$string['toc'] = 'Table des matières';
