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
 * Gentle feedback when a learner marks an activity as done.
 *
 * It reacts only to core_course's "manual completion toggled" event, which
 * Moodle dispatches after the server has confirmed the new state. It updates
 * the progress, "My path" and "Next" by the same step, so the page never claims
 * more than Moodle has recorded, and shows a short message of encouragement
 * that does not take the focus or block the page. A short sound plays only
 * when the learner has turned sounds on in the display settings.
 *
 * @module     theme_chinijo/completion_feedback
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import CourseEvents from 'core_course/events';
import Templates from 'core/templates';
import {add as addToast} from 'core/toast';
import {getString, getStrings} from 'core/str';

const SELECTORS = {
    PROGRESS: '[data-region="theme_chinijo-progress"]',
    LABEL: '[data-region="theme_chinijo-progress-label"]',
    BAR: '[data-region="theme_chinijo-progress-bar"]',
    COURSE_DONE: '.theme-chinijo-progress__done',
    NEXT: '[data-region="theme_chinijo-next"]',
    STOP: '.theme-chinijo-path__stop',
    STOP_STATE: '[data-region="theme_chinijo-path-state"]',
    SECTION_LABEL: '[data-region="theme_chinijo-section-label"]',
    CHEER_CARD: '[data-region="theme_chinijo-cheer-card"]',
    CHEER_CLOSE: '[data-action="theme_chinijo-cheer-close"]',
    SCROLLERS: 'html, #page.drawers',
};

const STATES = ['done', 'current', 'todo'];

let initialised = false;
let liveRegion = null;
let returnFocus = null;

/**
 * Activities on the path of a progress region.
 *
 * @param {HTMLElement} region Progress region.
 * @returns {Object[]}
 */
const getItems = (region) => {
    try {
        return JSON.parse(region.dataset.items || '[]');
    } catch (error) {
        return [];
    }
};

/**
 * Update "My path" and the "Next" card of a region after a confirmed change.
 *
 * @param {HTMLElement} region Progress region of the course page.
 * @param {Object[]} items Activities on the path, already updated.
 * @returns {Promise<Object|null>} The next activity to do.
 */
const updatePath = async(region, items) => {
    const next = items.find((item) => !item.done && item.url) || null;
    const labels = await getStrings(STATES.map((state) => ({key: `path_state_${state}`, component: 'theme_chinijo'})));

    region.querySelectorAll(SELECTORS.STOP).forEach((stop) => {
        const item = items.find((candidate) => Number(candidate.cmid) === Number(stop.dataset.cmid));
        if (!item || (stop.dataset.state === 'locked' && !item.done)) {
            // Availability can only be known again from the server.
            return;
        }
        let state = 'todo';
        if (item.done) {
            state = 'done';
        } else if (next && Number(next.cmid) === Number(item.cmid)) {
            state = 'current';
        }
        stop.dataset.state = state;
        if (state === 'current') {
            stop.setAttribute('aria-current', 'step');
        } else {
            stop.removeAttribute('aria-current');
        }
        stop.querySelector(SELECTORS.STOP_STATE).textContent = labels[STATES.indexOf(state)];
    });

    const sectionLabel = region.querySelector(SELECTORS.SECTION_LABEL);
    if (sectionLabel && region.dataset.section !== undefined) {
        const inSection = items.filter((item) => String(item.section) === region.dataset.section);
        sectionLabel.textContent = await getString('path_section_summary', 'theme_chinijo', {
            completed: inSection.filter((item) => item.done).length,
            total: inSection.length,
        });
    }

    const card = region.querySelector(SELECTORS.NEXT);
    const alldone = items.length > 0 && items.every((item) => item.done);
    if (!next && !alldone) {
        card?.remove();
        return null;
    }
    const {html, js} = await Templates.renderForPromise('theme_chinijo/learning_path_next', {...next, alldone});
    if (card) {
        Templates.replaceNode(card, html, js);
    } else {
        Templates.prependNodeContents(region, html, js);
    }
    return next;
};

/**
 * Update the progress shown on the page after a confirmed manual completion change.
 *
 * @param {number} cmid Course module id.
 * @param {boolean} completed New state.
 * @returns {Promise<Object|null>} The new count (done and total) and the next activity, or null when not counted.
 */
export const updateProgress = async(cmid, completed) => {
    let result = null;
    for (const region of document.querySelectorAll(SELECTORS.PROGRESS)) {
        const counted = JSON.parse(region.dataset.cmids || '[]').map(Number);
        const total = Number(region.dataset.total);
        if (!total || !counted.includes(Number(cmid))) {
            continue;
        }
        const items = getItems(region);
        const item = items.find((candidate) => Number(candidate.cmid) === Number(cmid));
        let done = Number(region.dataset.completed);
        if (!item || item.done !== completed) {
            done = Math.max(0, Math.min(total, done + (completed ? 1 : -1)));
        }
        if (item) {
            item.done = completed;
            region.dataset.items = JSON.stringify(items);
        }
        region.dataset.completed = String(done);
        result = {done, total, next: result?.next || null};

        const label = region.querySelector(SELECTORS.LABEL);
        // Course completion is decided by Moodle's own criteria, not by this page.
        if (label && !label.querySelector(SELECTORS.COURSE_DONE)) {
            label.textContent = await getString('path_summary', 'theme_chinijo', {completed: done, total});
        }
        const bar = region.querySelector(SELECTORS.BAR);
        if (bar) {
            bar.value = done;
        }
        if (items.length) {
            result.next = await updatePath(region, items);
        }
    }
    return result;
};

/**
 * A short, soft chime made by the browser itself (no sound file, no network).
 */
const playChime = () => {
    const AudioContext = window.AudioContext || window.webkitAudioContext;
    if (!AudioContext) {
        return;
    }
    try {
        const context = new AudioContext();
        const start = context.currentTime + 0.05;
        [523.25, 659.25, 783.99].forEach((frequency, index) => {
            const oscillator = context.createOscillator();
            const gain = context.createGain();
            const at = start + index * 0.12;
            oscillator.type = 'sine';
            oscillator.frequency.value = frequency;
            gain.gain.setValueAtTime(0.0001, at);
            gain.gain.exponentialRampToValueAtTime(0.12, at + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, at + 0.4);
            oscillator.connect(gain);
            gain.connect(context.destination);
            oscillator.start(at);
            oscillator.stop(at + 0.45);
        });
        setTimeout(() => context.close(), 1200);
    } catch (error) {
        // Without sound the visual message is enough.
    }
};

/**
 * Keep focused elements clear of the message (WCAG 2.4.11), or release that space.
 *
 * @param {number} height Height to keep free at the bottom, or 0 to release it.
 */
const reserveSpace = (height) => {
    document.querySelectorAll(SELECTORS.SCROLLERS).forEach((scroller) => {
        if (height) {
            scroller.style.setProperty('scroll-padding-bottom', `${height + 32}px`);
        } else {
            scroller.style.removeProperty('scroll-padding-bottom');
        }
    });
};

/**
 * Remove the message, giving the focus back if it was inside.
 */
const closeCheer = () => {
    const hadFocus = liveRegion.contains(document.activeElement);
    liveRegion.replaceChildren();
    reserveSpace(0);
    if (hadFocus && returnFocus && document.contains(returnFocus)) {
        returnFocus.focus();
    }
};

/**
 * The live region that holds the message. It exists from the start, so screen readers announce what is added to it.
 *
 * @returns {HTMLElement}
 */
const getLiveRegion = () => {
    if (!liveRegion) {
        liveRegion = document.createElement('div');
        liveRegion.className = 'theme-chinijo-cheer';
        liveRegion.setAttribute('role', 'status');
        liveRegion.dataset.region = 'theme_chinijo-cheer';
        // Inside the main landmark, like the content it refers to; CSS places it in the corner of the window.
        (document.querySelector('[role="main"]') || document.querySelector('main') || document.body).append(liveRegion);
        liveRegion.addEventListener('click', (event) => {
            if (event.target.closest(SELECTORS.CHEER_CLOSE)) {
                closeCheer();
            }
        });
        liveRegion.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeCheer();
            }
        });
    }
    return liveRegion;
};

/**
 * Show the message of encouragement.
 *
 * @param {string} name Name of the activity just done.
 * @param {Object|null} progress New count and next activity, from updateProgress().
 */
const showCheer = async(name, progress) => {
    const firstname = document.querySelector(`${SELECTORS.PROGRESS}[data-firstname]`)?.dataset.firstname || '';
    const next = progress?.next || null;
    const [title, text] = await Promise.all([
        firstname ? getString('completion_title', 'theme_chinijo', firstname)
            : getString('completion_title_noname', 'theme_chinijo'),
        progress ? getString('completion_text', 'theme_chinijo', {name, completed: progress.done, total: progress.total})
            : getString('completion_text_nocount', 'theme_chinijo', name),
    ]);
    const {html, js} = await Templates.renderForPromise('theme_chinijo/completion_message', {
        title,
        text,
        nexturl: next ? next.url : '',
        nextname: next ? next.name : '',
    });

    const region = getLiveRegion();
    returnFocus = document.activeElement;
    Templates.replaceNodeContents(region, html, js);
    reserveSpace(region.querySelector(SELECTORS.CHEER_CARD)?.offsetHeight || 0);
    if (document.documentElement.getAttribute('data-chinijo-sound') === 'on') {
        playChime();
    }
};

/**
 * Initialise the completion feedback.
 */
export const init = () => {
    if (initialised) {
        return;
    }
    initialised = true;
    getLiveRegion();

    document.addEventListener(CourseEvents.manualCompletionToggled, async(event) => {
        const {cmid, activityname, completed} = event.detail || {};
        const progress = await updateProgress(cmid, Boolean(completed));
        if (completed) {
            await showCheer(activityname || '', progress);
        } else {
            // The toast renders its message as HTML, so the name is passed as text.
            const element = document.createElement('span');
            element.textContent = activityname || '';
            await addToast(await getString('completion_undone', 'theme_chinijo', element.innerHTML));
        }
    });
};
