<?php
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

namespace theme_chinijo\output;

use theme_chinijo\local\course_progress;
use theme_chinijo\local\pictogram_display;
use theme_chinijo\local\theme;

/**
 * Core renderer for Chinijo.
 *
 * It only extends course_header(), which Boost's full_header() renders on every
 * course page, to add the progress indicator, the pictogram data and the
 * completion feedback script. Everything else is Boost's renderer.
 *
 * @package    theme_chinijo
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_renderer extends \theme_boost\output\core_renderer {
    /**
     * Course header with the user's progress and the course pictograms.
     *
     * @return string HTML.
     */
    public function course_header() {
        global $USER;

        $output = parent::course_header();
        $course = $this->page->course;
        if ((int) $course->id === (int) SITEID || !theme::can_decorate($this->page)) {
            return $output;
        }

        $progress = isloggedin() && !isguestuser() ? course_progress::get($course, (int) $USER->id) : null;
        if ($progress !== null) {
            $progress['courseid'] = (int) $course->id;
            $progress['cmidsjson'] = json_encode($progress['cmids']);
            $output .= $this->render_from_template('theme_chinijo/course_progress', $progress);
        }
        $this->page->requires->js_call_amd('theme_chinijo/completion_feedback', 'init');

        return $output . pictogram_display::render_page_data($this->page);
    }
}
