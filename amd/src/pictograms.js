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
 * Show course pictograms next to section and activity names.
 *
 * The server only sends the pictograms that the user may see, as JSON in a
 * hidden element. The names of sections and activities are never replaced:
 * without JavaScript, or without pictograms, the course looks exactly as
 * usual. Course formats re-render parts of the page (for example while
 * editing), so the page is decorated again whenever it changes.
 *
 * On the course page the pictogram is an image with its text alternative,
 * placed before the name. In the course index it sits inside the link, next
 * to the name, so it is marked as decorative to avoid reading it twice.
 *
 * @module     theme_chinijo/pictograms
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const DATA_SELECTOR = '[data-region="theme_chinijo-pictograms"]';
const MARKER = 'data-chinijo-pictogram';

/**
 * Create the image of a pictogram.
 *
 * @param {Object} item Pictogram with type, id, url and alt.
 * @param {string} variant One of 'cm', 'section' or 'index'.
 * @returns {HTMLImageElement}
 */
const createImage = (item, variant) => {
    const image = document.createElement('img');
    image.src = item.url;
    image.alt = variant === 'index' ? '' : item.alt;
    image.className = `theme-chinijo-pictogram theme-chinijo-pictogram--${variant}`;
    image.decoding = 'async';
    image.setAttribute(MARKER, `${item.type}:${item.id}`);
    return image;
};

/**
 * Insert an image before an element, unless the container already has it.
 *
 * @param {Element|null} container Element that receives the image only once.
 * @param {Element|null} reference Element before which the image is inserted.
 * @param {Function} factory Function returning the image.
 */
const insertOnce = (container, reference, factory) => {
    if (!container || !reference || container.querySelector(`img[${MARKER}]`)) {
        return;
    }
    reference.parentNode.insertBefore(factory(), reference);
};

/**
 * Decorate every place where an item appears.
 *
 * @param {Object} item Pictogram with type, id, url and alt.
 */
const decorateItem = (item) => {
    const id = String(Number(item.id));
    if (item.type === 'cm') {
        document.querySelectorAll(`[data-for="cmitem"][data-id="${id}"]`).forEach((cmitem) => {
            // Inside the title block, before the name, so it shares the activity's stretched link area.
            insertOnce(cmitem, cmitem.querySelector('.activitytitle .activityname'), () => createImage(item, 'cm'));
        });
        document.querySelectorAll(`[data-for="cm"][data-id="${id}"] [data-for="cm_name"]`).forEach((name) => {
            if (name.closest('[data-for="cm"]').dataset.id !== id) {
                return;
            }
            insertOnce(name, name.firstChild, () => createImage(item, 'index'));
        });
    } else if (item.type === 'section') {
        document.querySelectorAll(`[data-for="section"][data-id="${id}"] [data-for="section_title"]`).forEach((title) => {
            // Skip wrappers of the real title and the titles of subsections nested in this section.
            if (title.querySelector('[data-for="section_title"]') || title.closest('[data-for="section"]').dataset.id !== id) {
                return;
            }
            if (title.closest('#courseindex, .courseindex')) {
                // Inside the course index link: decorative, next to the name.
                insertOnce(title, title.firstChild, () => createImage(item, 'index'));
            } else {
                // Before the section heading, inside the header that wraps it.
                insertOnce(title.parentElement, title, () => createImage(item, 'section'));
            }
        });
    }
};

/**
 * Initialise the pictograms of the course page.
 */
export const init = () => {
    const dataElement = document.querySelector(DATA_SELECTOR);
    if (!dataElement) {
        return;
    }
    let items = [];
    try {
        items = JSON.parse(dataElement.dataset.pictograms || '[]');
    } catch (error) {
        return;
    }
    if (!Array.isArray(items) || !items.length) {
        return;
    }

    const decorate = () => items.forEach(decorateItem);
    decorate();

    let scheduled = false;
    const observer = new MutationObserver(() => {
        if (scheduled) {
            return;
        }
        scheduled = true;
        window.requestAnimationFrame(() => {
            scheduled = false;
            decorate();
        });
    });
    observer.observe(document.body, {childList: true, subtree: true});
};
