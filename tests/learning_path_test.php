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
use theme_chinijo\local\learning_path;
use theme_chinijo\local\pictograms;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/completionlib.php');

#[\PHPUnit\Framework\Attributes\CoversClass(learning_path::class)]
/**
 * Tests for the learner's path through a course.
 *
 * @package    theme_chinijo
 * @category   test
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_chinijo\local\learning_path
 */
final class learning_path_test extends \advanced_testcase {
    /**
     * Completion is enabled for the site, as Moodle's default settings do.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        set_config('enablecompletion', 1);
    }

    /**
     * Create a page with manual completion in a section of a course.
     *
     * @param \stdClass $course The course.
     * @param int $section Section number.
     * @param string $name Activity name.
     * @param array $extra Other module settings.
     * @return \stdClass The page.
     */
    protected function create_page(\stdClass $course, int $section, string $name, array $extra = []): \stdClass {
        return $this->getDataGenerator()->create_module('page', $extra + [
            'course' => $course->id,
            'section' => $section,
            'name' => $name,
            'completion' => COMPLETION_TRACKING_MANUAL,
        ]);
    }

    /**
     * Activities follow the course page order, done ones are marked and the first one to do is next.
     */
    public function test_order_states_and_next(): void {
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1, 'numsections' => 2]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->create_page($course, 2, 'Third');
        $first = $this->create_page($course, 1, 'First');
        $this->create_page($course, 1, 'Second');
        $this->create_page($course, 1, 'Not tracked', ['completion' => COMPLETION_TRACKING_NONE]);

        $cm = get_fast_modinfo($course)->get_cm($first->cmid);
        (new completion_info($course))->update_state($cm, COMPLETION_COMPLETE, $student->id);
        $this->setUser($student);

        $path = learning_path::get($course, (int) $student->id);
        $this->assertSame(['First', 'Second', 'Third'], array_column($path['items'], 'name'));
        $this->assertSame(
            [learning_path::STATE_DONE, learning_path::STATE_CURRENT, learning_path::STATE_TODO],
            array_column($path['items'], 'state')
        );
        $this->assertSame('Second', $path['next']['name']);
        $this->assertStringContainsString('/mod/page/view.php', $path['next']['url']);
        $this->assertSame(1, $path['completed']);
        $this->assertSame(3, $path['total']);
        $this->assertSame('', $path['items'][0]['pictogramurl']);
        $this->assertStringContainsString('page', $path['items'][0]['iconurl']);

        // The drawn path is the current section: the one of the next activity.
        $this->assertSame(['First', 'Second'], array_column($path['window'], 'name'));
        $this->assertSame(1, $path['section']['number']);
        $this->assertSame(1, $path['section']['completed']);
        $this->assertSame(2, $path['section']['total']);
        $this->assertStringEndsWith('#section-1', $path['section']['url']);
        $this->assertNotEmpty($path['section']['name']);
        $this->assertSame(0, $path['before']);
        $this->assertSame(0, $path['after']);

        // Once that section is done, the path moves on to the next section.
        $second = get_fast_modinfo($course)->get_cm($path['items'][1]['cmid']);
        (new completion_info($course))->update_state($second, COMPLETION_COMPLETE, $student->id);
        $path = learning_path::get($course, (int) $student->id);
        $this->assertSame('Third', $path['next']['name']);
        $this->assertSame(2, $path['section']['number']);
        $this->assertSame(['Third'], array_column($path['window'], 'name'));
    }

    /**
     * Activities hidden from the learner are never named on the path.
     */
    public function test_hidden_activity_left_out(): void {
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->create_page($course, 0, 'Visible');
        $this->create_page($course, 0, 'Hidden', ['visible' => 0]);
        $this->setUser($student);

        $path = learning_path::get($course, (int) $student->id);
        $this->assertSame(['Visible'], array_column($path['items'], 'name'));
    }

    /**
     * When everything is done there is no next activity.
     */
    public function test_all_done(): void {
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $page = $this->create_page($course, 0, 'Only one');
        $cm = get_fast_modinfo($course)->get_cm($page->cmid);
        (new completion_info($course))->update_state($cm, COMPLETION_COMPLETE, $student->id);
        $this->setUser($student);

        $path = learning_path::get($course, (int) $student->id);
        $this->assertNull($path['next']);
        $this->assertSame([learning_path::STATE_DONE], array_column($path['items'], 'state'));
        // The last section stays on the path, complete.
        $this->assertSame(0, $path['section']['number']);
        $this->assertSame(1, $path['section']['completed']);
        $this->assertSame(['Only one'], array_column($path['window'], 'name'));
    }

    /**
     * The activity's pictogram is used on the path; users who are not tracked have no path.
     */
    public function test_pictogram_and_untracked_user(): void {
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $page = $this->create_page($course, 0, 'Read the story');
        $this->getDataGenerator()->get_plugin_generator('theme_chinijo')->create_pictogram([
            'courseid' => $course->id,
            'itemtype' => pictograms::TYPE_CM,
            'itemid' => $page->cmid,
            'alttext' => 'Book',
        ]);
        $section = get_fast_modinfo($course)->get_section_info(0);
        $this->getDataGenerator()->get_plugin_generator('theme_chinijo')->create_pictogram([
            'courseid' => $course->id,
            'itemtype' => pictograms::TYPE_SECTION,
            'itemid' => $section->id,
            'alttext' => 'Sun',
            'filepath' => 'pictogram-sun.png',
        ]);
        $this->setUser($student);

        $path = learning_path::get($course, (int) $student->id);
        $item = $path['items'][0];
        $this->assertStringContainsString('/theme_chinijo/pictogram/', $item['pictogramurl']);
        $this->assertSame('Book', $item['pictogramalt']);
        $this->assertStringContainsString('/theme_chinijo/pictogram/', $path['section']['pictogramurl']);

        $this->setUser($teacher);
        $this->assertNull(learning_path::get($course, (int) $teacher->id));
    }

    /**
     * Long courses show up to WINDOW activities, starting two before the next one.
     */
    public function test_window(): void {
        $items = array_map(fn(int $i): array => ['cmid' => $i], range(0, 11));

        $window = learning_path::get_window($items, 6);
        $this->assertSame(range(4, 10), array_column($window['window'], 'cmid'));
        $this->assertSame(4, $window['before']);
        $this->assertSame(1, $window['after']);

        $window = learning_path::get_window($items, 0);
        $this->assertSame(range(0, 6), array_column($window['window'], 'cmid'));
        $this->assertSame(0, $window['before']);
        $this->assertSame(5, $window['after']);

        // Everything done: the end of the path.
        $window = learning_path::get_window($items, null);
        $this->assertSame(range(5, 11), array_column($window['window'], 'cmid'));
        $this->assertSame(5, $window['before']);
        $this->assertSame(0, $window['after']);

        $window = learning_path::get_window(array_slice($items, 0, 3), 2);
        $this->assertSame([0, 1, 2], array_column($window['window'], 'cmid'));
        $this->assertSame(0, $window['before'] + $window['after']);
    }
}
