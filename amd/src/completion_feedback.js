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
 * Moodle dispatches after the server has confirmed the new state. It shows a
 * short, polite toast (no sound, no animation beyond core's own) and updates
 * the progress indicator by the same step, so the page never claims more
 * than Moodle has recorded.
 *
 * @module     theme_chinijo/completion_feedback
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import CourseEvents from 'core_course/events';
import {add as addToast} from 'core/toast';
import {getString} from 'core/str';

const SELECTORS = {
    PROGRESS: '[data-region="theme_chinijo-progress"]',
    LABEL: '[data-region="theme_chinijo-progress-label"]',
    BAR: '[data-region="theme_chinijo-progress-bar"]',
    COURSE_DONE: '.theme-chinijo-progress__done',
};

let initialised = false;

/**
 * Escape text for the toast, which renders its message as HTML.
 *
 * @param {string} text Plain text.
 * @returns {string}
 */
const escapeHtml = (text) => {
    const element = document.createElement('span');
    element.textContent = text;
    return element.innerHTML;
};

/**
 * Update the progress indicator after a confirmed manual completion change.
 *
 * @param {number} cmid Course module id.
 * @param {boolean} completed New state.
 */
export const updateProgress = async(cmid, completed) => {
    const region = document.querySelector(SELECTORS.PROGRESS);
    if (!region) {
        return;
    }
    const counted = JSON.parse(region.dataset.cmids || '[]').map(Number);
    const total = Number(region.dataset.total);
    if (!total || !counted.includes(Number(cmid))) {
        return;
    }
    const done = Math.max(0, Math.min(total, Number(region.dataset.completed) + (completed ? 1 : -1)));
    const percentage = Math.floor((done / total) * 100);
    region.dataset.completed = String(done);

    const label = region.querySelector(SELECTORS.LABEL);
    if (!label || label.querySelector(SELECTORS.COURSE_DONE)) {
        // Course completion is decided by Moodle's own criteria, not by this page.
        return;
    }
    label.textContent = await getString('progress_summary', 'theme_chinijo', {completed: done, total, percentage});
    const bar = region.querySelector(SELECTORS.BAR);
    if (bar) {
        bar.value = done;
        bar.textContent = `${percentage}%`;
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

    document.addEventListener(CourseEvents.manualCompletionToggled, async(event) => {
        const {cmid, activityname, completed} = event.detail || {};
        const message = await getString(completed ? 'completion_done' : 'completion_undone', 'theme_chinijo',
            escapeHtml(activityname || ''));
        await addToast(message);
        await updateProgress(cmid, Boolean(completed));
    });
};
