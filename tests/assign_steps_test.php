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

use theme_chinijo\local\assign_steps;
use theme_chinijo\local\learning_path;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/locallib.php');

#[\PHPUnit\Framework\Attributes\CoversClass(assign_steps::class)]
/**
 * Tests for the steps of an assignment (read, do the work, hand it in).
 *
 * @package    theme_chinijo
 * @category   test
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_chinijo\local\assign_steps
 */
final class assign_steps_test extends \advanced_testcase {
    /**
     * Submission statuses map to the learner's status; anything not handed in counts as nothing yet.
     */
    public function test_get_status(): void {
        $this->assertSame(assign_steps::STATUS_NONE, assign_steps::get_status(null));
        $this->assertSame(assign_steps::STATUS_NONE, assign_steps::get_status(ASSIGN_SUBMISSION_STATUS_NEW));
        $this->assertSame(assign_steps::STATUS_NONE, assign_steps::get_status(ASSIGN_SUBMISSION_STATUS_REOPENED));
        $this->assertSame(assign_steps::STATUS_DRAFT, assign_steps::get_status(ASSIGN_SUBMISSION_STATUS_DRAFT));
        $this->assertSame(assign_steps::STATUS_SUBMITTED, assign_steps::get_status(ASSIGN_SUBMISSION_STATUS_SUBMITTED));
    }

    /**
     * The current step moves from reading to handing in, and everything is done once handed in.
     */
    public function test_get_steps(): void {
        $states = fn(string $status): array => array_column(assign_steps::get_steps($status), 'state', 'key');
        $this->assertSame(
            ['read' => learning_path::STATE_CURRENT, 'do' => learning_path::STATE_TODO, 'handin' => learning_path::STATE_TODO],
            $states(assign_steps::STATUS_NONE)
        );
        $this->assertSame(
            ['read' => learning_path::STATE_DONE, 'do' => learning_path::STATE_DONE, 'handin' => learning_path::STATE_CURRENT],
            $states(assign_steps::STATUS_DRAFT)
        );
        $this->assertSame(
            ['read' => learning_path::STATE_DONE, 'do' => learning_path::STATE_DONE, 'handin' => learning_path::STATE_DONE],
            $states(assign_steps::STATUS_SUBMITTED)
        );
        $this->assertSame([1, 2, 3], array_column(assign_steps::get_steps(assign_steps::STATUS_NONE), 'number'));
    }

    /**
     * A learner follows their own submission; teachers, other activities and offline tasks get no steps.
     */
    public function test_get(): void {
        global $DB;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $teacher = $generator->create_and_enrol($course, 'editingteacher');
        $assign = $generator->create_module('assign', [
            'course' => $course->id,
            'assignsubmission_onlinetext_enabled' => 1,
            'submissiondrafts' => 1,
        ]);
        $offline = $generator->create_module('assign', [
            'course' => $course->id,
            'assignsubmission_onlinetext_enabled' => 0,
            'assignsubmission_file_enabled' => 0,
        ]);
        $page = $generator->create_module('page', ['course' => $course->id]);
        $modinfo = get_fast_modinfo($course);
        $cm = $modinfo->get_cm($assign->cmid);

        $steps = assign_steps::get($course, $cm, (int) $student->id);
        $this->assertSame((int) $cm->id, $steps['cmid']);
        $this->assertSame(assign_steps::STATUS_NONE, $steps['status']);
        $this->assertCount(3, $steps['steps']);

        $instance = new \assign(\context_module::instance($cm->id), $cm, $course);
        $submission = $instance->get_user_submission($student->id, true);
        $DB->set_field('assign_submission', 'status', ASSIGN_SUBMISSION_STATUS_DRAFT, ['id' => $submission->id]);
        $this->assertSame(assign_steps::STATUS_DRAFT, assign_steps::get($course, $cm, (int) $student->id)['status']);
        $DB->set_field('assign_submission', 'status', ASSIGN_SUBMISSION_STATUS_SUBMITTED, ['id' => $submission->id]);
        $this->assertSame(assign_steps::STATUS_SUBMITTED, assign_steps::get($course, $cm, (int) $student->id)['status']);

        $this->assertNull(assign_steps::get($course, $cm, (int) $teacher->id));
        $this->assertNull(assign_steps::get($course, $modinfo->get_cm($offline->cmid), (int) $student->id));
        $this->assertNull(assign_steps::get($course, $modinfo->get_cm($page->cmid), (int) $student->id));
    }

    /**
     * In group assignments the steps follow the submission of the learner's group.
     */
    public function test_get_team_submission(): void {
        global $DB;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $classmate = $generator->create_and_enrol($course, 'student');
        $group = $generator->create_group(['courseid' => $course->id]);
        $generator->create_group_member(['groupid' => $group->id, 'userid' => $student->id]);
        $generator->create_group_member(['groupid' => $group->id, 'userid' => $classmate->id]);
        $assign = $generator->create_module('assign', [
            'course' => $course->id,
            'assignsubmission_onlinetext_enabled' => 1,
            'teamsubmission' => 1,
        ]);
        $cm = get_fast_modinfo($course)->get_cm($assign->cmid);

        $instance = new \assign(\context_module::instance($cm->id), $cm, $course);
        $submission = $instance->get_group_submission($classmate->id, $group->id, true);
        $DB->set_field('assign_submission', 'status', ASSIGN_SUBMISSION_STATUS_SUBMITTED, ['id' => $submission->id]);

        $this->assertSame(assign_steps::STATUS_SUBMITTED, assign_steps::get($course, $cm, (int) $student->id)['status']);
    }
}
