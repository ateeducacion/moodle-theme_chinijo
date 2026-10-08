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

namespace theme_chinijo;

use completion_info;
use theme_chinijo\local\course_progress;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/completionlib.php');

#[\PHPUnit\Framework\Attributes\CoversClass(course_progress::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(output\core_renderer::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\theme_chinijo\local\learning_path::class)]
/**
 * Tests for the course progress indicator and the course header that shows it.
 *
 * @package    theme_chinijo
 * @category   test
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_chinijo\local\course_progress
 * @covers     \theme_chinijo\output\core_renderer
 * @covers     \theme_chinijo\local\learning_path
 */
final class course_progress_test extends \advanced_testcase {
    /**
     * Completion is enabled for the site, as Moodle's default settings do.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        set_config('enablecompletion', 1);
    }

    /**
     * Courses without completion tracking show no progress at all.
     */
    public function test_no_completion(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 0]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $this->assertNull(course_progress::get($course, (int) $student->id));
    }

    /**
     * Completion enabled but no activity with completion: nothing to show.
     */
    public function test_no_activities_with_completion(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'completion' => COMPLETION_TRACKING_NONE]);
        $this->assertNull(course_progress::get($course, (int) $student->id));
    }

    /**
     * Teachers are not tracked, so they see no progress.
     */
    public function test_untracked_user(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'completion' => COMPLETION_TRACKING_MANUAL]);
        $this->assertNull(course_progress::get($course, (int) $teacher->id));
    }

    /**
     * The figures are exactly Moodle's own, before and after completing activities, on every branch.
     */
    public function test_matches_core_progress(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['enablecompletion' => 1]);
        $student = $generator->create_and_enrol($course, 'student');
        $pages = [];
        for ($i = 0; $i < 3; $i++) {
            $pages[] = $generator->create_module('page', ['course' => $course->id, 'completion' => COMPLETION_TRACKING_MANUAL]);
        }
        // An activity hidden from students: counted differently by Moodle 4.5 and by later branches.
        $generator->create_module('page', ['course' => $course->id, 'completion' => COMPLETION_TRACKING_MANUAL, 'visible' => 0]);
        // Like the page, core computes visibility for the current user, who is the student.
        $this->setUser($student);

        $progress = course_progress::get($course, (int) $student->id);
        $this->assertSame(0, $progress['completed']);
        $this->assertSame(0, $progress['percentage']);
        $this->assertFalse($progress['coursecomplete']);
        $this->assertEquals(0, \core_completion\progress::get_course_progress_percentage($course, $student->id));

        $completion = new completion_info($course);
        $cm = get_fast_modinfo($course)->get_cm($pages[0]->cmid);
        $completion->update_state($cm, COMPLETION_COMPLETE, $student->id);

        $progress = course_progress::get($course, (int) $student->id);
        $this->assertSame(1, $progress['completed']);
        $this->assertContains((int) $pages[0]->cmid, $progress['cmids']);
        $core = \core_completion\progress::get_course_progress_percentage($course, $student->id);
        $this->assertSame((int) floor($core), $progress['percentage']);
        $this->assertEqualsWithDelta($core, ($progress['completed'] / $progress['total']) * 100, 0.0001);
    }

    /**
     * The web renderer of a course page or of an activity page.
     *
     * @param \stdClass $course The course.
     * @param string $pagetype Page type.
     * @param \cm_info|null $cm The activity, for an activity page.
     * @return output\core_renderer
     */
    protected function get_page_renderer(\stdClass $course, string $pagetype, ?\cm_info $cm = null): output\core_renderer {
        global $PAGE;

        // Enrolment may send the course welcome e-mail, which sets up the global page's theme.
        $PAGE = new \moodle_page();
        if ($cm) {
            $PAGE->set_cm($cm, $course);
            $PAGE->set_url($cm->url);
            $PAGE->set_pagelayout('incourse');
        } else {
            $PAGE->set_course($course);
            $PAGE->set_url(new \moodle_url('/course/view.php', ['id' => $course->id]));
            $PAGE->set_pagelayout('course');
        }
        $PAGE->set_pagetype($pagetype);
        $PAGE->force_theme('chinijo');
        // Tests run on the command line: ask for the web renderer explicitly.
        $output = $PAGE->get_renderer('core', null, RENDERER_TARGET_GENERAL);
        $this->assertInstanceOf(output\core_renderer::class, $output);
        return $output;
    }

    /**
     * The course page greets a tracked learner and shows "Next" and "My path"; teachers get neither path nor card.
     */
    public function test_course_page(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['enablecompletion' => 1]);
        $student = $generator->create_and_enrol($course, 'student', ['firstname' => 'Leo']);
        $teacher = $generator->create_and_enrol($course, 'editingteacher');
        $generator->create_module('page', [
            'course' => $course->id,
            'name' => 'Read the story',
            'completion' => COMPLETION_TRACKING_MANUAL,
        ]);

        $this->setUser($student);
        $output = $this->get_page_renderer($course, 'course-view-topics');
        $this->assertStringContainsString(get_string('greeting', 'theme_chinijo', 'Leo'), $output->course_header());
        $html = $output->course_content_header();
        $this->assertStringContainsString('data-region="theme_chinijo-progress"', $html);
        $this->assertStringContainsString('data-region="theme_chinijo-next"', $html);
        $this->assertStringContainsString('Read the story', $html);
        $this->assertStringContainsString('aria-current="step"', $html);
        $this->assertStringContainsString(
            get_string('path_summary', 'theme_chinijo', ['completed' => 0, 'total' => 1]),
            $html
        );

        $this->setUser($teacher);
        $output = $this->get_page_renderer($course, 'course-view-topics');
        $this->assertStringNotContainsString('theme_chinijo-progress', $output->course_content_header());
    }

    /**
     * Activity pages get the bar with the way back to the course, the progress and the "Listen" button.
     */
    public function test_activity_page(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['enablecompletion' => 1]);
        $student = $generator->create_and_enrol($course, 'student');
        $page = $generator->create_module('page', ['course' => $course->id, 'completion' => COMPLETION_TRACKING_MANUAL]);
        $cm = get_fast_modinfo($course)->get_cm($page->cmid);

        $this->setUser($student);
        $output = $this->get_page_renderer($course, 'mod-page-view', $cm);
        $this->assertStringNotContainsString('theme-chinijo-greeting', $output->course_header());
        $html = $output->course_content_header();
        $this->assertStringContainsString('data-region="theme_chinijo-activity-bar"', $html);
        $this->assertStringContainsString('/course/view.php?id=' . $course->id . '#module-' . $cm->id, $html);
        $this->assertStringContainsString(get_string('activity_back', 'theme_chinijo'), $html);
        $this->assertStringContainsString('data-action="theme_chinijo-read-aloud"', $html);
        $this->assertStringContainsString('<progress', $html);

        // Core prints the content header once; a second request for it adds nothing either.
        $this->assertSame('', $output->course_content_header(true));
    }

    /**
     * The site home has no course header additions.
     */
    public function test_course_header_site_home(): void {
        global $PAGE;
        $this->resetAfterTest();
        $this->setAdminUser();
        $PAGE->set_url(new \moodle_url('/'));
        $PAGE->force_theme('chinijo');
        $output = $PAGE->get_renderer('core', null, RENDERER_TARGET_GENERAL);
        $this->assertSame('', $output->course_header());
        $this->assertStringNotContainsString('theme_chinijo', $output->course_content_header());
    }
}
