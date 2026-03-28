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
 * A helper for embedding of ajax forms in modal dialogs.
 *
 * This code is based on lib/form/amd/src/modalform.js
 *
 * @module     tool_mulib/ajax_form/modal
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

import $ from 'jquery';
import Modal from 'core/modal';
import Fragment from 'core/fragment';
import * as Notification from 'core/notification';
import * as FormEvents from 'core_form/events';
import * as FormChangeChecker from 'core_form/changechecker';
import Pending from 'core/pending';

const STATUSES = {
    CANCELLED: 'cancelled',
    RENDER: 'render',
    SUBMITTED: 'submitted',
};

const ACTIONS = {
    NOTHING: 'nothing',
    REDIRECT: 'redirect',
    RELOAD: 'reload',
};

/**
 * @class tool_mulib/ajax_form/modal
 * @extends module:core/modal
 */
export default class AjaxFormModal extends Modal {
    static TYPE = 'tool_mulib-ajax_form_modal';
    static TEMPLATE = 'tool_mulib/ajax_form/modal';

    /**
     * Constructor for the Modal.
     *
     * @param {HTMLElement} root The root jQuery element for the modal
     */
    constructor(root) {
        super(root);

        this.reloadingForm = false;
        this.formUrl = null;
        this.formSubmittedAction = null;
    }

    /**
     * Configure the modal ajax form.
     *
     * @param {ModalConfig} modalConfig Modal configuration options
     */
    configure(modalConfig) {
        this.formUrl = modalConfig.formUrl;
        this.formSubmittedAction = modalConfig.formSubmittedAction;

        modalConfig.show = false;
        modalConfig.removeOnClose = true;

        super.configure(modalConfig);

        if (modalConfig.formSize === 'lg' || modalConfig.formSize === 'xl') {
            this.getModal().addClass(`modal-${modalConfig.formSize}`);
        }

        this.show();
    }

    /**
     * Register all event listeners.
     *
     * @method registerEventListeners
     */
    registerEventListeners() {
        super.registerEventListeners();

        this.getBody().on('click', 'form input[type=submit]', (e) => {
            this.submitAjaxForm(e);
        });

        // Allow submitting via ENTER key in text field.
        this.getRoot().on('submit', 'form', (e) => {
            this.submitAjaxForm.bind(e);
        });
    }

    /**
     * Returns form element.
     *
     * @returns {HTMLFormElement}
     */
    getForm() {
        return this.getRoot().find('form')[0];
    }

    /**
     * Validates form.
     *
     * @method validateElements
     * @returns {boolean}
     */
    validateElements() {
        FormEvents.notifyFormSubmittedByJavascript(this.getForm());

        const invalid = this.getRoot().find('[aria-invalid="true"], .error');
        if (invalid.length) {
            invalid.first().focus();
            return false;
        }

        return true;
    }

    /**
     * Submit form.
     *
     * @method submitAjaxForm
     * @param {Event} e
     */
    submitAjaxForm(e) {
        e.preventDefault();

        if (e.target.dataset.cancel === '1') {
            FormChangeChecker.resetAllFormDirtyStates();
            this.destroy();
            return;
        }

        if (e.target.dataset.skipValidation !== '1') {
            if (!this.validateElements()) {
                return;
            }
        }

        const form = this.getRoot().find('form');
        let formData = form.serialize();

        if (e.target.type === 'submit') {
            formData = formData + '&' + encodeURIComponent(e.target.name) + '=' + encodeURIComponent(e.target.value);
        }

        FormChangeChecker.resetAllFormDirtyStates();

        this.reloadForm(formData);
    }

    /**
     * Reload form and render it.
     *
     * @method reloadForm
     * @param {String} formData
     * @returns void
     */
    reloadForm(formData) {
        if (this.reloadingForm) {
            return;
        }
        this.reloadingForm = true;
        const pendingPromise = new Pending('tool_mulib/modal_ajax_form:reload');

        this.disableSubmitButtons();

        const settings = {
            async: true,
            data: formData,
            dataType: 'json',
            processData: false,
            timeout: 0,
            type: 'POST',
        };

        const renderPromise = $.Deferred();
        if (formData === '') {
            this.setBody(renderPromise.promise());
        } else {
            this.startSubmitting();
        }

        $.ajax(this.formUrl, settings)
            .done((response) => {
                if (!response) {
                    throw new Error('Invalid server response');
                } else if (response.error) {
                    // Exception in ajax form script.
                    Notification.exception(response);
                } else if (response.data?.status) {
                    if (response.data.status === STATUSES.RENDER) {
                        if (formData !== '') {
                            this.setBody(renderPromise.promise());
                        }
                        renderPromise.resolve(response.data.html, Fragment.processCollectedJavascript(response.data.javascript));
                        if (response.data.dialogtitle !== '') {
                            this.setTitle(response.data.dialogtitle);
                        }
                        this.enableSubmitButtons();
                    } else if (response.data.status === STATUSES.CANCELLED) {
                        // This should not happen because cancel and close buttons are in JS only.
                        this.destroy();
                    } else if (response.data.status === STATUSES.SUBMITTED) {
                        // Remove the form data, the redirect or callback may take a few moments.
                        renderPromise.resolve('', '');
                        if (typeof this.formSubmittedAction === 'function') {
                            const callback = this.formSubmittedAction;
                            this.destroy();
                            callback(response.data.callbackdata);
                        } else if (this.formSubmittedAction === ACTIONS.RELOAD) {
                            FormChangeChecker.disableAllChecks();
                            window.location.reload();
                        } else if (this.formSubmittedAction === ACTIONS.REDIRECT) {
                            FormChangeChecker.disableAllChecks();
                            window.location = response.data.redirecturl;
                        } else {
                            // Option ACTIONS.NOTHING does not do anything.
                            this.destroy();
                        }
                    } else {
                        Notification.exception(new Error('Invalid form data.status value received'));
                    }
                } else {
                    Notification.exception(new Error('Invalid server response'));
                }
            })
            .catch((ex) => {
                Notification.exception(ex);
            })
            .always(() => {
                pendingPromise.resolve();
                this.finishSubmitting();
                this.reloadingForm = false;
            });
    }

    /**
     * Disable the submit buttons.
     *
     * @method disableSubmitButtons
     */
    disableSubmitButtons() {
        const buttons = this.getBody().find('form input[type=submit]');
        buttons.prop('disabled', true);
    }

    /**
     * Re-enable the submit button.
     *
     * @method enableSubmitButtons
     */
    enableSubmitButtons() {
        const buttons = this.getBody().find('form input[type=submit]');
        buttons.prop('disabled', false);
    }

    /**
     * Disable the submit buttons.
     *
     * @method startSubmitting
     */
    startSubmitting() {
        const submitting = this.getBody().find('[data-region="submitting-icon-container"]');
        submitting.removeClass('hidden');
    }

    /**
     * Re-enable the submit button.
     *
     * @method finishSubmitting
     */
    finishSubmitting() {
        const submitting = this.getBody().find('[data-region="submitting-icon-container"]');
        submitting.addClass('hidden');
    }

    /**
     * Show modal.
     *
     * @method show
     * @returns {Promise}
     */
    show() {
        this.reloadForm('');
        return super.show();
    }

    /**
     * Hide modal.
     *
     * @method hide
     * @returns void
     */
    hide() {
        // Prevent any interruptions or autosaving.
        const form = this.getForm();
        FormEvents.notifyFormSubmittedByJavascript(form, true);
        FormChangeChecker.resetFormDirtyState(form);

        super.hide();
    }
}

AjaxFormModal.registerModalType();
