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

/**
 * Shows log-in-as dialog with instructions to right clock to open a new Incognito Window.
 *
 * @module      tool_muloginas/popup
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalEvents from 'core/modal_events';
import CancelModal from 'core/modal_cancel';
import Notification from 'core/notification';
import Ajax from 'core/ajax';
import * as Str from 'core/str';
import Templates from 'core/templates';

/* eslint-disable promise/no-nesting */
/* eslint-disable promise/always-return */

/**
 * Register click to show popup listener.
 *
 * @param {Number} targetUserId
 * @param {String} targetUser
 * @param {String} uniqueId
 */
const registerEventListener = (targetUserId, targetUser, uniqueId) => {
    let link = document.getElementById(uniqueId);
    link.addEventListener('click', function() {
        CancelModal.create({
            large: true,
            title: Str.get_string('loginas_a', 'tool_muloginas', targetUser),
            body: Str.get_string('creatingtoken', 'tool_muloginas')
        })
            .then(modal => {
                let tokenExpired = false;

                modal.setSmall();
                modal.show();

                // Handle hidden event.
                modal.getRoot().on(ModalEvents.hidden, () => modal.destroy());

                const request = {
                    methodname: 'tool_muloginas_token_create',
                    args: {
                        targetuserid: targetUserId,
                    }
                };

                const response = Ajax.call([request])[0];
                response.then(function(data) {
                    const context = {
                        url: M.cfg.wwwroot + '/admin/tool/muloginas/loginas.php?token=' + data.token
                    };
                    Templates.render('tool_muloginas/popup_link', context).then((html) => {
                        if (!tokenExpired) {
                            modal.setBody(html);
                        }
                    }).catch(Notification.exception);

                    setTimeout(function() {
                        tokenExpired = true;
                        Templates.render('tool_muloginas/popup_expired', {}).then((html) => {
                            modal.setBody(html);
                        }).catch(Notification.exception);
                    }, (data.lifetime * 1000) - 500);

                    /**
                     * Close popup if token used.
                     *
                     * @param {string} token
                     * @param {Modal} modal
                     */
                    const checkTokenUsed = (token, modal) => {
                        const request = {
                            methodname: 'tool_muloginas_token_check',
                            args: {
                                token: token,
                            }
                        };

                        const response = Ajax.call([request])[0];
                        response.then(function(data) {
                            if (data.status === 3) {
                                modal.hide();
                            }
                            if (data.status === 1) {
                                setTimeout(function() {
                                    checkTokenUsed(token, modal);
                                }, 3000);
                            }
                        }).catch(Notification.exception);
                    };

                    checkTokenUsed(data.token, modal);
                })
                .catch(Notification.exception);

                return modal;
            })
            .catch(Notification.exception);
    });
};

/**
 * Initialise the popup.
 *
 * @param {object} param
 * @param {Number} param.targetuserid
 * @param {String} param.targetuser
 * @param {String} param.uniqueid
 */
export const init = ({targetuserid, targetuser, uniqueid}) => {
    registerEventListener(targetuserid, targetuser, uniqueid);
};

