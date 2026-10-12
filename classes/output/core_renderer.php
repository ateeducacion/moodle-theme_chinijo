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

use moodle_url;
use theme_chinijo\local\activity_verbs;
use theme_chinijo\local\assign_steps;
use theme_chinijo\local\course_progress;
use theme_chinijo\local\learning_path;
use theme_chinijo\local\pictogram_display;
use theme_chinijo\local\theme;

/**
 * Core renderer for Chinijo.
 *
 * It extends two methods that Boost's layouts print on every course page:
 * course_header(), above the page heading, and course_content_header(), at the
 * top of the main region. Everything else is Boost's renderer.
 *
 * - Course page: a greeting above the heading, then "Up next" (the next activity
 *   to do) and "My path" (the activities in order with what is done), and the
 *   verb of each activity ("Read", "Hand in") on its card.
 * - Activity pages: a bar with a link back to the course, the progress and a
 *   button that reads the page aloud; on assignments, the steps read, do and
 *   hand in; on forums, the less used controls behind "More options".
 *
 * @package    theme_chinijo
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_renderer extends \theme_boost\output\core_renderer {
    /**
     * Course header: the greeting on the course page, and the course pictograms.
     *
     * @return string HTML.
     */
    public function course_header() {
        global $USER;

        $output = parent::course_header();
        if (!$this->is_decorated_course_page()) {
            return $output;
        }

        if ($this->is_course_home() && isloggedin() && !isguestuser()) {
            $output .= $this->render_from_template('theme_chinijo/greeting', [
                'greeting' => get_string('greeting', 'theme_chinijo', $USER->firstname),
            ]);
        }
        if ($this->is_course_home() && !$this->page->user_is_editing()) {
            $modnames = array_keys(get_fast_modinfo($this->page->course)->get_used_module_names());
            $this->page->requires->js_call_amd('theme_chinijo/activity_cards', 'init', [
                activity_verbs::get_labels($modnames),
            ]);
        }
        $this->page->requires->js_call_amd('theme_chinijo/completion_feedback', 'init');

        return $output . pictogram_display::render_page_data($this->page);
    }

    /**
     * Top of the main region: the learner's path on the course page, the activity bar on activity pages.
     *
     * @param bool $onlyifnotcalledbefore Output content only if it has not already been output.
     * @return string HTML.
     */
    public function course_content_header($onlyifnotcalledbefore = false) {
        $output = parent::course_content_header($onlyifnotcalledbefore);
        if ($output === '' || !$this->is_decorated_course_page()) {
            // An empty result means core has already printed it on this page.
            return $output;
        }

        if ($this->is_course_home()) {
            return $output . $this->render_learning_path();
        }
        if ($this->page->cm) {
            return $output . $this->render_activity_bar() . $this->render_activity_extras();
        }
        return $output;
    }

    /**
     * Whether the page belongs to a real course and Chinijo decorates it.
     *
     * @return bool
     */
    protected function is_decorated_course_page(): bool {
        return (int) $this->page->course->id !== (int) SITEID && theme::can_decorate($this->page);
    }

    /**
     * Whether the page is the course page itself (or one of its section pages).
     *
     * @return bool
     */
    protected function is_course_home(): bool {
        return strpos($this->page->pagetype, 'course-view-') === 0;
    }

    /**
     * Progress of the current user in the page's course, or null for guests and untracked users.
     *
     * @param bool $withpath True to include the activities of the path.
     * @return array|null
     */
    protected function get_user_progress(bool $withpath): ?array {
        global $USER;

        if (!isloggedin() || isguestuser()) {
            return null;
        }
        $course = $this->page->course;
        return $withpath ? learning_path::get($course, (int) $USER->id) : course_progress::get($course, (int) $USER->id);
    }

    /**
     * The "Next" card and the "My path" list of the course page.
     *
     * @return string HTML.
     */
    protected function render_learning_path(): string {
        global $USER;

        $path = $this->get_user_progress(true);
        if ($path === null) {
            return '';
        }

        foreach ($path['window'] as $index => $item) {
            $path['window'][$index] += [
                'statelabel' => get_string('path_state_' . $item['state'], 'theme_chinijo'),
                'is' . $item['state'] => true,
            ];
        }

        return $this->render_from_template('theme_chinijo/learning_path', $path + [
            'courseid' => (int) $this->page->course->id,
            'firstname' => $USER->firstname,
            'cmidsjson' => json_encode($path['cmids']),
            'itemsjson' => json_encode(array_map(fn(array $item): array => [
                'cmid' => $item['cmid'],
                'name' => $item['name'],
                'url' => $item['url'],
                'modname' => $item['modname'],
                'verb' => $item['verb'],
                'iconurl' => $item['iconurl'],
                'pictogramurl' => $item['pictogramurl'],
                'section' => $item['section'],
                'done' => $item['state'] === learning_path::STATE_DONE,
            ], $path['items'])),
            'hasitems' => !empty($path['items']),
            'alldone' => $path['next'] === null && $path['total'] > 0 && $path['completed'] >= $path['total'],
        ]);
    }

    /**
     * What some kinds of activity add below the activity bar.
     *
     * Assignments show their steps to learners who can hand them in. Forums
     * keep their less used controls behind "More options" for learners;
     * teachers, who manage the activity, see every control as usual.
     *
     * @return string HTML.
     */
    protected function render_activity_extras(): string {
        global $USER;

        $cm = $this->page->cm;
        if ($this->page->pagetype === 'mod-assign-view' && isloggedin() && !isguestuser()) {
            $steps = assign_steps::get($this->page->course, $cm, (int) $USER->id);
            return $steps === null ? '' : $this->render_assign_steps($steps);
        }
        if (
            in_array($this->page->pagetype, ['mod-forum-view', 'mod-forum-discuss'], true)
            && !has_capability('moodle/course:manageactivities', $this->page->context)
        ) {
            $this->page->requires->js_call_amd('theme_chinijo/more_options', 'init');
        }
        return '';
    }

    /**
     * The steps of an assignment.
     *
     * @param array $steps As returned by assign_steps::get().
     * @return string HTML.
     */
    protected function render_assign_steps(array $steps): string {
        foreach ($steps['steps'] as $index => $step) {
            $steps['steps'][$index] += [
                'label' => get_string('step_' . $step['key'], 'theme_chinijo'),
                'statelabel' => get_string('path_state_' . $step['state'], 'theme_chinijo'),
                'iscurrent' => $step['state'] === learning_path::STATE_CURRENT,
            ];
        }
        return $this->render_from_template('theme_chinijo/assign_steps', $steps + [
            'statustext' => get_string('steps_status_' . $steps['status'], 'theme_chinijo'),
            'issubmitted' => $steps['status'] === assign_steps::STATUS_SUBMITTED,
        ]);
    }

    /**
     * The bar at the top of activity pages: back to the course, the progress and "Listen".
     *
     * @return string HTML.
     */
    protected function render_activity_bar(): string {
        global $USER;

        $course = $this->page->course;
        $backurl = new moodle_url('/course/view.php', ['id' => $course->id], 'module-' . $this->page->cm->id);
        $this->page->requires->js_call_amd('theme_chinijo/read_aloud', 'init');

        $progress = $this->get_user_progress(false);
        if ($progress !== null) {
            $progress['courseid'] = (int) $course->id;
            $progress['cmidsjson'] = json_encode($progress['cmids']);
            $progress['firstname'] = $USER->firstname;
        }

        return $this->render_from_template('theme_chinijo/activity_bar', [
            'backurl' => $backurl->out(false),
            'progress' => $progress,
        ]);
    }
}
