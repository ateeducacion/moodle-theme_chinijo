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
 * "Listen": reads the activity page aloud with the browser's speech synthesis.
 *
 * Only voices installed on the device (localService) are used, so no text
 * leaves the device and no external service is involved. The button stays
 * hidden when the browser has no such voice for the page language. It does
 * not replace screen readers or the text-to-speech tools of the platform.
 *
 * @module     theme_chinijo/read_aloud
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getString} from 'core/str';

const SELECTORS = {
    BUTTON: '[data-action="theme_chinijo-read-aloud"]',
    LABEL: '[data-region="theme_chinijo-read-aloud-label"]',
    CONTENT: '#region-main',
    BLOCKS: 'h1, h2, h3, h4, h5, h6, p, li, dt, dd, th, td, div, br',
    SKIP: [
        'script', 'style', 'template', 'noscript', 'form', 'button', 'select', 'textarea', 'nav', '[hidden]',
        '[aria-hidden="true"]', '.accesshide', '.sr-only', '.visually-hidden', '.theme-chinijo-visually-hidden',
        '[data-region="theme_chinijo-activity-bar"]', '[data-region="activity-information"]', '#user-notifications',
        '.tertiary-navigation', '.activity-navigation',
    ].join(', '),
};

/** @var {number} Longest piece of text given to the speech engine at once. */
const MAX_CHUNK = 220;

let initialised = false;

/**
 * A voice installed on the device for a language. Network voices are never chosen.
 *
 * @param {SpeechSynthesisVoice[]} voices Available voices.
 * @param {string} language Two-letter language code, for example "es".
 * @returns {SpeechSynthesisVoice|null}
 */
export const findVoice = (voices, language) => voices.find((voice) =>
    voice.localService && voice.lang.toLowerCase().split(/[-_]/)[0] === language) || null;

/**
 * Text of a region, one line per block, without controls or hidden text.
 *
 * @param {HTMLElement} root The region.
 * @returns {string[]}
 */
export const getReadableLines = (root) => {
    const clone = root.cloneNode(true);
    clone.querySelectorAll(SELECTORS.SKIP).forEach((element) => element.remove());
    clone.querySelectorAll(SELECTORS.BLOCKS).forEach((element) => element.append('\n'));
    return clone.textContent.split('\n').map((line) => line.replace(/\s+/g, ' ').trim()).filter(Boolean);
};

/**
 * Split lines into short pieces at sentence ends, then at spaces, which speech engines read reliably.
 *
 * @param {string[]} lines Lines of text.
 * @param {number} max Longest piece.
 * @returns {string[]}
 */
export const splitText = (lines, max = MAX_CHUNK) => {
    const pieces = [];
    lines.forEach((line) => {
        (line.match(/[^.!?…]+[.!?…]*/g) || [line]).forEach((sentence) => {
            let rest = sentence.trim();
            while (rest.length > max) {
                const cut = rest.lastIndexOf(' ', max) > 0 ? rest.lastIndexOf(' ', max) : max;
                pieces.push(rest.slice(0, cut).trim());
                rest = rest.slice(cut).trim();
            }
            if (rest) {
                pieces.push(rest);
            }
        });
    });
    return pieces;
};

/**
 * Initialise the "Listen" button of the page.
 */
export const init = () => {
    if (initialised) {
        return;
    }
    initialised = true;

    const button = document.querySelector(SELECTORS.BUTTON);
    if (!button || !('speechSynthesis' in window) || typeof window.SpeechSynthesisUtterance !== 'function') {
        return;
    }
    const synth = window.speechSynthesis;
    const label = button.querySelector(SELECTORS.LABEL);
    const language = (document.documentElement.lang || 'en').toLowerCase().split('-')[0];
    let voice = null;
    let speaking = false;

    const setSpeaking = async(value) => {
        speaking = value;
        label.textContent = await getString(value ? 'readaloud_stop' : 'readaloud', 'theme_chinijo');
    };

    const findDeviceVoice = () => {
        voice = findVoice(synth.getVoices(), language);
        button.hidden = !voice;
    };
    findDeviceVoice();
    // Some browsers load their voices after the page.
    if (typeof synth.addEventListener === 'function') {
        synth.addEventListener('voiceschanged', findDeviceVoice);
    } else {
        synth.onvoiceschanged = findDeviceVoice;
    }

    button.addEventListener('click', () => {
        if (speaking) {
            synth.cancel();
            setSpeaking(false);
            return;
        }
        const root = document.querySelector(SELECTORS.CONTENT);
        const pieces = root && voice ? splitText(getReadableLines(root)) : [];
        if (!pieces.length) {
            return;
        }
        synth.cancel();
        setSpeaking(true);
        pieces.forEach((piece, index) => {
            const utterance = new window.SpeechSynthesisUtterance(piece);
            utterance.voice = voice;
            utterance.lang = voice.lang;
            utterance.rate = 0.9;
            if (index === pieces.length - 1) {
                utterance.onend = () => setSpeaking(false);
                utterance.onerror = () => setSpeaking(false);
            }
            synth.speak(utterance);
        });
    });

    window.addEventListener('pagehide', () => synth.cancel());
};
