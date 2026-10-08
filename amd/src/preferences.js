// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Display settings dialogue.
 *
 * The control in the navbar is a link to the stand-alone page. This module
 * turns it into a button that opens the same form in a dialogue, previews each
 * choice on the page straight away and saves the choices with the Save button.
 * Logged-in users save through core_user's preference API, which validates the
 * values against theme_chinijo_user_preferences() and only lets people change
 * their own preferences. Guests and visitors who are not logged in submit the
 * form to the stand-alone page, which keeps the choices for their session.
 *
 * @module     theme_chinijo/preferences
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Modal from 'core/modal';
import ModalEvents from 'core/modal_events';
import Pending from 'core/pending';
import {add as addToast} from 'core/toast';
import {getString} from 'core/str';
import {setUserPreferences} from 'core_user/repository';

const SELECTORS = {
    CONTROL: '[data-region="theme_chinijo-preferences-control"]',
    TRIGGER: '[data-action="theme_chinijo-open-preferences"]',
    TEMPLATE: 'template[data-region="theme_chinijo-preferences-form"]',
    FORM: 'form[data-region="theme_chinijo-preferences"]',
    STATUS: '[data-region="theme_chinijo-preferences-status"]',
    CHECKED: 'input[type="radio"]:checked',
};

const ATTRIBUTE_PREFIX = 'data-chinijo-';
const PREFERENCE_PREFIX = 'theme_chinijo_';
const DEFAULT_VALUE = 'default';
const COLOUR_MODE_BACKUP = 'data-chinijo-colourmode-backup';

let config = {canpersist: false};
let initialised = false;

/**
 * Names of the preferences found in a form.
 *
 * @param {HTMLFormElement} form The display settings form.
 * @returns {string[]}
 */
const getNames = (form) => [...new Set([...form.querySelectorAll('input[type="radio"]')].map((radio) => radio.name))];

/**
 * Values currently selected in a form.
 *
 * @param {HTMLFormElement} form The display settings form.
 * @returns {Object<string, string>}
 */
const getFormValues = (form) => {
    const values = {};
    form.querySelectorAll(SELECTORS.CHECKED).forEach((radio) => {
        values[radio.name] = radio.value;
    });
    return values;
};

/**
 * Values currently applied to the page.
 *
 * @param {string[]} names Preference names.
 * @returns {Object<string, string>}
 */
export const getAppliedValues = (names) => {
    const values = {};
    names.forEach((name) => {
        values[name] = document.documentElement.getAttribute(ATTRIBUTE_PREFIX + name) || DEFAULT_VALUE;
    });
    return values;
};

/**
 * Apply values to the page, keeping Boost's colour mode consistent with high contrast.
 *
 * @param {Object<string, string>} values Preference name => value.
 */
export const applyValues = (values) => {
    const root = document.documentElement;
    Object.entries(values).forEach(([name, value]) => {
        root.setAttribute(ATTRIBUTE_PREFIX + name, value);
    });

    if (!root.hasAttribute('data-bs-theme') || !('contrast' in values)) {
        return;
    }
    if (values.contrast === 'high') {
        if (!root.hasAttribute(COLOUR_MODE_BACKUP)) {
            root.setAttribute(COLOUR_MODE_BACKUP, root.getAttribute('data-bs-theme'));
        }
        root.setAttribute('data-bs-theme', 'light');
    } else if (root.hasAttribute(COLOUR_MODE_BACKUP)) {
        root.setAttribute('data-bs-theme', root.getAttribute(COLOUR_MODE_BACKUP));
        root.removeAttribute(COLOUR_MODE_BACKUP);
    }
};

/**
 * Select the radio buttons matching the given values.
 *
 * @param {HTMLFormElement} form The display settings form.
 * @param {Object<string, string>} values Preference name => value.
 */
const selectValues = (form, values) => {
    Object.entries(values).forEach(([name, value]) => {
        const radio = form.querySelector(`input[type="radio"][name="${name}"][value="${value}"]`);
        if (radio) {
            radio.checked = true;
        }
    });
};

/**
 * Save values for a logged-in user.
 *
 * Defaults are sent as the explicit "default" value: core's REST preference
 * route answers a null value (meaning "remove") with a server error on 5.3.
 *
 * @param {Object<string, string>} values Preference name => value.
 * @returns {Promise}
 */
const saveValues = (values) => setUserPreferences(Object.entries(values).map(([name, value]) => ({
    name: PREFERENCE_PREFIX + name,
    value,
    // Zero means the current user; core refuses any other user id.
    userid: 0,
})));

/**
 * Open the display settings dialogue.
 *
 * @param {HTMLElement} trigger The control that opens it.
 */
const openDialogue = async(trigger) => {
    const template = trigger.closest(SELECTORS.CONTROL)?.querySelector(SELECTORS.TEMPLATE);
    if (!template) {
        window.location.href = trigger.href;
        return;
    }
    const pending = new Pending('theme_chinijo/preferences:open');

    const modal = await Modal.create({
        title: getString('displaysettings', 'theme_chinijo'),
        body: template.innerHTML,
        large: true,
        removeOnClose: true,
        returnElement: trigger,
    });
    const form = modal.getBody()[0].querySelector(SELECTORS.FORM);
    const status = form.querySelector(SELECTORS.STATUS);
    const names = getNames(form);
    let saved = getAppliedValues(names);
    selectValues(form, saved);

    form.addEventListener('change', async(event) => {
        applyValues(getFormValues(form));
        const label = form.querySelector(`label[for="${event.target.id}"] .theme-chinijo-prefs__text`);
        if (label) {
            status.textContent = await getString('prefs_previewing', 'theme_chinijo', label.textContent.trim());
        }
    });

    form.addEventListener('submit', async(event) => {
        const reset = event.submitter && event.submitter.dataset.action === 'reset';
        if (!config.canpersist) {
            // Guests: the stand-alone page stores the choices for the session and comes back here.
            return;
        }
        event.preventDefault();
        const savePending = new Pending('theme_chinijo/preferences:save');
        if (reset) {
            const defaults = {};
            names.forEach((name) => {
                defaults[name] = DEFAULT_VALUE;
            });
            selectValues(form, defaults);
        }
        const values = getFormValues(form);
        applyValues(values);
        try {
            await saveValues(values);
            saved = values;
            const message = await getString(reset ? 'prefs_resetdone' : 'prefs_saved', 'theme_chinijo');
            modal.hide();
            await addToast(message);
        } catch (error) {
            status.textContent = await getString('prefs_saveerror', 'theme_chinijo');
        }
        savePending.resolve();
    });

    // Closing the dialogue without saving brings back the saved settings.
    modal.getRoot().on(ModalEvents.hidden, () => {
        applyValues(saved);
    });

    modal.show();
    pending.resolve();
};

/**
 * Initialise the display settings control.
 *
 * @param {Object} options Options.
 * @param {boolean} options.canpersist Whether the user can store preferences permanently.
 */
export const init = (options) => {
    config = {...config, ...options};
    if (initialised) {
        return;
    }
    initialised = true;

    document.querySelectorAll(SELECTORS.TRIGGER).forEach((trigger) => {
        trigger.setAttribute('role', 'button');
        trigger.setAttribute('aria-haspopup', 'dialog');
    });

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest(SELECTORS.TRIGGER);
        if (trigger) {
            event.preventDefault();
            openDialogue(trigger);
        }
    });

    // A link turned into a button must also respond to the space bar.
    document.addEventListener('keydown', (event) => {
        const trigger = event.target.closest && event.target.closest(SELECTORS.TRIGGER);
        if (trigger && event.key === ' ') {
            event.preventDefault();
            openDialogue(trigger);
        }
    });
};
