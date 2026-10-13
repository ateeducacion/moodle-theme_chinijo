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
 * Verbs on the activity cards of the course page.
 *
 * Above the name of each activity, one everyday verb says what the learner does
 * in it ("Read", "Hand in"), coloured like the activity icon. Screen readers
 * already announce the type of activity with its name, so the verb is hidden
 * from them. Nothing of Moodle's own card is changed or removed.
 *
 * @module     theme_chinijo/activity_cards
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const SELECTORS = {
    CONTENT: '.course-content',
    ACTIVITY: 'li.activity[data-for="cmitem"]',
    NAME_AREA: '.activity-name-area',
    ICON: '.activityiconcontainer',
    VERB: '.theme-chinijo-verb',
};

/** @var {string[]} Activity purposes that Moodle adds as classes to the icon. */
const PURPOSES = ['administration', 'assessment', 'collaboration', 'communication', 'content', 'interactivecontent'];

/**
 * The module name of an activity of the course page, from its "modtype_" class.
 *
 * @param {HTMLElement} activity Activity list item.
 * @returns {string}
 */
export const getModname = (activity) => {
    const modtype = [...activity.classList].find((name) => name.startsWith('modtype_'));
    return modtype ? modtype.slice('modtype_'.length) : '';
};

/**
 * Add the verb to one activity card, once.
 *
 * @param {HTMLElement} activity Activity list item.
 * @param {Object} verbs Verb by module name.
 */
const addVerb = (activity, verbs) => {
    const verb = verbs[getModname(activity)];
    const area = activity.querySelector(SELECTORS.NAME_AREA);
    if (!verb || !area || area.querySelector(SELECTORS.VERB)) {
        return;
    }
    const icon = activity.querySelector(SELECTORS.ICON);
    const label = document.createElement('span');
    label.className = 'theme-chinijo-verb';
    label.setAttribute('aria-hidden', 'true');
    label.dataset.purpose = PURPOSES.find((purpose) => icon?.classList.contains(purpose)) || '';
    label.textContent = verb;
    area.prepend(label);
};

/**
 * Add the verbs to the activity cards, and to the cards that Moodle draws again later.
 *
 * @param {Object} verbs Verb by module name, for the kinds of activity used in the course.
 */
export const init = (verbs) => {
    const content = document.querySelector(SELECTORS.CONTENT);
    if (!content || document.body.classList.contains('editing') || !verbs || !Object.keys(verbs).length) {
        return;
    }
    const decorate = () => content.querySelectorAll(SELECTORS.ACTIVITY).forEach((activity) => addVerb(activity, verbs));
    decorate();
    new MutationObserver(decorate).observe(content, {childList: true, subtree: true});
};
