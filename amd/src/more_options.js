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
 * "More options" on forum pages, for learners.
 *
 * Forum pages put search, subscription and display controls in the same row as
 * the main action. Here the main action stays in view and the other controls
 * go behind one "More options" button, a disclosure on the same page: nothing
 * is removed, there is no mode to remember, and one press shows everything
 * again. The controls are moved, whole, into a panel inside the same row, so
 * Moodle's own scripts keep working on them.
 *
 * @module     theme_chinijo/more_options
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getStrings} from 'core/str';

const SELECTORS = {
    ROW: '[role="main"] .tertiary-navigation .navitem',
    PRIMARY: '.btn-primary',
    BUTTON: '[data-action="theme_chinijo-more"]',
};

const CHEVRON = '<svg class="theme-chinijo-icon theme-chinijo-more__chevron" viewBox="0 0 24 24" width="22" height="22" ' +
    'aria-hidden="true" focusable="false"><path d="M9 5l7 7-7 7"></path></svg>';

/**
 * Put the controls of a row, except its main action, behind a "More options" button.
 *
 * @param {HTMLElement} row Element that holds the controls.
 * @param {HTMLElement[]} extras Controls to put away.
 * @param {string[]} labels Button text when closed and when open.
 * @returns {HTMLButtonElement}
 */
export const collapse = (row, extras, labels) => {
    const panel = document.createElement('div');
    panel.className = 'theme-chinijo-more__panel';
    panel.id = `theme-chinijo-more-${Date.now().toString(36)}`;
    panel.hidden = true;
    panel.append(...extras);

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn theme-chinijo-more';
    button.dataset.action = 'theme_chinijo-more';
    button.setAttribute('aria-expanded', 'false');
    button.setAttribute('aria-controls', panel.id);
    button.innerHTML = CHEVRON;
    const text = document.createElement('span');
    text.textContent = labels[0];
    button.append(text);

    button.addEventListener('click', () => {
        const open = button.getAttribute('aria-expanded') !== 'true';
        button.setAttribute('aria-expanded', String(open));
        text.textContent = labels[open ? 1 : 0];
        panel.hidden = !open;
    });

    row.append(button, panel);
    return button;
};

/**
 * Set up "More options" on the page.
 */
export const init = async() => {
    const items = [...document.querySelectorAll(SELECTORS.ROW)];
    const row = items[0]?.parentElement;
    if (!row || row.querySelector(SELECTORS.BUTTON)) {
        return;
    }
    const extras = items.filter((item) => item.parentElement === row && !item.querySelector(SELECTORS.PRIMARY));
    if (!extras.length || extras.length === items.length) {
        // Nothing to put away, or no main action to keep in view.
        return;
    }
    const labels = await getStrings([
        {key: 'moreoptions', component: 'theme_chinijo'},
        {key: 'moreoptions_hide', component: 'theme_chinijo'},
    ]);
    collapse(row, extras, labels);
};
