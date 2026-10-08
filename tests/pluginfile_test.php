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

use theme_chinijo\local\pictograms;

/**
 * Security tests for the theme's file serving (pluginfile callback).
 *
 * The success path streams the file and ends the script, so these tests cover
 * every refusal: unknown areas, records of other courses, items hidden from the
 * user and users who are not allowed in the course. Each test runs in its own
 * process, because sending a 404 sets HTTP headers, which only works before
 * PHPUnit prints anything.
 *
 * @package    theme_chinijo
 * @category   test
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     ::theme_chinijo_pluginfile
 * @covers     \theme_chinijo\local\pictogram_display
 * @runTestsInSeparateProcesses
 */
#[\PHPUnit\Framework\Attributes\CoversFunction('theme_chinijo_pluginfile')]
#[\PHPUnit\Framework\Attributes\CoversClass(local\pictogram_display::class)]
#[\PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses]
final class pluginfile_test extends \advanced_testcase {
    /**
     * Load the theme's lib.php.
     */
    public static function setUpBeforeClass(): void {
        global $CFG;
        parent::setUpBeforeClass();
        require_once($CFG->dirroot . '/theme/chinijo/lib.php');
    }

    /**
     * Call the callback and return the exception it throws.
     *
     * @param \stdClass $course Course.
     * @param \context $context Context.
     * @param string $filearea File area.
     * @param array $args Arguments.
     * @return \Throwable|null
     */
    private function serve(\stdClass $course, \context $context, string $filearea, array $args): ?\Throwable {
        try {
            theme_chinijo_pluginfile($course, null, $context, $filearea, $args, false);
        } catch (\Throwable $e) {
            return $e;
        }
        return null;
    }

    /**
     * Unknown file areas and a missing FEDER emblem are not found.
     */
    public function test_unknown_areas(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $system = \context_system::instance();

        $e = $this->serve(get_site(), $system, 'secrets', ['0', 'x.png']);
        $this->assertInstanceOf(\moodle_exception::class, $e);
        $this->assertSame('filenotfound', $e->errorcode);

        $e = $this->serve(get_site(), $system, 'federlogo', ['0', 'emblem.png']);
        $this->assertSame('filenotfound', $e->errorcode);

        $e = $this->serve($course, \context_course::instance($course->id), 'other', ['1', 'x.png']);
        $this->assertSame('filenotfound', $e->errorcode);
    }

    /**
     * A record of another course is never served, even with a valid id (no IDOR).
     */
    public function test_record_of_other_course(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $other = $generator->create_course();
        $page = $generator->create_module('page', ['course' => $other->id]);
        $record = $generator->get_plugin_generator('theme_chinijo')->create_pictogram([
            'courseid' => $other->id, 'itemtype' => pictograms::TYPE_CM, 'itemid' => $page->cmid, 'alttext' => 'Book',
        ]);

        $this->setAdminUser();
        $e = $this->serve(
            $course,
            \context_course::instance($course->id),
            pictograms::FILEAREA,
            [$record->id, 'pictogram-book.png']
        );
        $this->assertSame('filenotfound', $e->errorcode);
    }

    /**
     * Learners do not get the pictogram of an activity hidden from them, nor a file that does not exist.
     */
    public function test_hidden_item_and_missing_file(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $hidden = $generator->create_module('page', ['course' => $course->id, 'visible' => 0]);
        $visible = $generator->create_module('page', ['course' => $course->id]);
        $chinijo = $generator->get_plugin_generator('theme_chinijo');
        $hiddenrecord = $chinijo->create_pictogram(['courseid' => $course->id, 'itemtype' => pictograms::TYPE_CM,
            'itemid' => $hidden->cmid, 'alttext' => 'Secret']);
        $visiblerecord = $chinijo->create_pictogram(['courseid' => $course->id, 'itemtype' => pictograms::TYPE_CM,
            'itemid' => $visible->cmid, 'alttext' => 'Book']);
        $context = \context_course::instance($course->id);

        $this->setUser($student);
        $e = $this->serve($course, $context, pictograms::FILEAREA, [$hiddenrecord->id, 'pictogram-book.png']);
        $this->assertSame('filenotfound', $e->errorcode);

        $e = $this->serve($course, $context, pictograms::FILEAREA, [$visiblerecord->id, 'other-name.png']);
        $this->assertSame('filenotfound', $e->errorcode);
    }

    /**
     * Users who cannot enter the course get no pictogram.
     */
    public function test_not_enrolled(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $page = $generator->create_module('page', ['course' => $course->id]);
        $record = $generator->get_plugin_generator('theme_chinijo')->create_pictogram([
            'courseid' => $course->id, 'itemtype' => pictograms::TYPE_CM, 'itemid' => $page->cmid, 'alttext' => 'Book',
        ]);

        $this->setUser($generator->create_user());
        $e = $this->serve(
            $course,
            \context_course::instance($course->id),
            pictograms::FILEAREA,
            [$record->id, 'pictogram-book.png']
        );
        $this->assertInstanceOf(\require_login_exception::class, $e);
    }
}
