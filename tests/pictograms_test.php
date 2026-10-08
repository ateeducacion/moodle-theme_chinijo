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

use context_course;
use invalid_parameter_exception;
use required_capability_exception;
use theme_chinijo\local\pictogram_display;
use theme_chinijo\local\pictogram_rules;
use theme_chinijo\local\pictograms;

#[\PHPUnit\Framework\Attributes\CoversClass(pictograms::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(pictogram_rules::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(pictogram_display::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(observer::class)]
/**
 * Tests for course pictograms.
 *
 * @package    theme_chinijo
 * @category   test
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_chinijo\local\pictograms
 * @covers     \theme_chinijo\local\pictogram_rules
 * @covers     \theme_chinijo\local\pictogram_display
 * @covers     \theme_chinijo\observer
 */
final class pictograms_test extends \advanced_testcase {
    /** @var string A 1x1 PNG. */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    /** @var \stdClass Course. */
    private $course;

    /** @var \stdClass Editing teacher. */
    private $teacher;

    /** @var \stdClass Student. */
    private $student;

    /** @var \stdClass A page activity. */
    private $page;

    /**
     * Create a course with a teacher, a student and an activity.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $this->course = $generator->create_course(['numsections' => 2]);
        $this->teacher = $generator->create_and_enrol($this->course, 'editingteacher');
        $this->student = $generator->create_and_enrol($this->course, 'student');
        $this->page = $generator->create_module('page', ['course' => $this->course->id, 'section' => 1, 'name' => 'Story']);
    }

    /**
     * Put a file in the current user's draft area.
     *
     * @param string $filename File name.
     * @param string|null $content File content, a small PNG by default.
     * @return int Draft item id.
     */
    private function create_draft(string $filename = 'picto.png', ?string $content = null): int {
        global $USER;
        $draftitemid = file_get_unused_draft_itemid();
        get_file_storage()->create_file_from_string([
            'contextid' => \context_user::instance($USER->id)->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => $filename,
        ], $content ?? base64_decode(self::PNG));
        return $draftitemid;
    }

    /**
     * Hide a section with the API of the running branch (set_section_visible() is deprecated since 5.2).
     *
     * @param int $sectionnum Section number.
     */
    private function hide_section(int $sectionnum): void {
        global $CFG;
        $section = get_fast_modinfo($this->course->id)->get_section_info($sectionnum);
        $actions = \core_courseformat\formatactions::section($this->course->id);
        if (method_exists($actions, 'set_visibility')) {
            $actions->set_visibility($section, false);
        } else {
            require_once($CFG->dirroot . '/course/lib.php');
            set_section_visible($this->course->id, $sectionnum, 0);
        }
    }

    /**
     * Delete an activity with the API of the running branch (course_delete_module() is deprecated since 5.2).
     *
     * @param int $cmid Course module id.
     */
    private function delete_activity(int $cmid): void {
        global $CFG;
        $actions = \core_courseformat\formatactions::cm($this->course->id);
        if (method_exists($actions, 'delete')) {
            $actions->delete($cmid);
        } else {
            require_once($CFG->dirroot . '/course/lib.php');
            course_delete_module($cmid);
        }
    }

    /**
     * Form data for a pictogram.
     *
     * @param int $draftitemid Draft item id.
     * @param string $alttext Text alternative.
     * @return \stdClass
     */
    private function data(int $draftitemid, string $alttext = 'Book'): \stdClass {
        return (object) ['pictogram' => $draftitemid, 'alttext' => $alttext, 'author' => 'ARASAAC', 'license' => 'cc-nc-sa-4.0'];
    }

    /**
     * A teacher associates a pictogram with an activity: a record and a file in the course context.
     */
    public function test_save_activity_pictogram(): void {
        $this->setUser($this->teacher);
        $record = pictograms::save($this->course, pictograms::TYPE_CM, (int) $this->page->cmid, $this->data($this->create_draft()));

        $this->assertSame('Book', $record->alttext);
        $this->assertSame('ARASAAC', $record->author);
        $file = pictograms::get_file($record);
        $this->assertSame('image/png', $file->get_mimetype());
        $this->assertEquals(context_course::instance($this->course->id)->id, $file->get_contextid());
        $this->assertStringContainsString(
            '/theme_chinijo/pictogram/' . $record->id . '/picto.png',
            pictograms::get_file_url($file)->out(false)
        );

        // Saving again updates the same record.
        $again = pictograms::save(
            $this->course,
            pictograms::TYPE_CM,
            (int) $this->page->cmid,
            $this->data($this->create_draft('other.png'), 'Open book')
        );
        $this->assertEquals($record->id, $again->id);
        $this->assertSame('other.png', pictograms::get_file($again)->get_filename());
        $this->assertCount(1, pictograms::get_records($this->course->id));
    }

    /**
     * Sections can have pictograms too.
     */
    public function test_save_section_pictogram(): void {
        $this->setUser($this->teacher);
        $section = get_fast_modinfo($this->course)->get_section_info(1);
        $record = pictograms::save($this->course, pictograms::TYPE_SECTION, (int) $section->id, $this->data($this->create_draft()));
        $this->assertSame(pictograms::TYPE_SECTION, $record->itemtype);
        $this->assertSame(
            get_section_name($this->course, $section),
            pictogram_rules::get_item_name($this->course, pictograms::TYPE_SECTION, (int) $section->id)
        );
    }

    /**
     * Students cannot manage pictograms.
     */
    public function test_student_cannot_save(): void {
        $this->setUser($this->student);
        $this->expectException(required_capability_exception::class);
        pictograms::save($this->course, pictograms::TYPE_CM, (int) $this->page->cmid, $this->data($this->create_draft()));
    }

    /**
     * Guests cannot manage pictograms either.
     */
    public function test_guest_cannot_delete(): void {
        $this->setGuestUser();
        $this->expectException(required_capability_exception::class);
        pictograms::delete($this->course, pictograms::TYPE_CM, (int) $this->page->cmid);
    }

    /**
     * A teacher of one course cannot attach a pictogram to an activity of another course.
     */
    public function test_items_of_other_courses_are_rejected(): void {
        $other = $this->getDataGenerator()->create_course();
        $otherpage = $this->getDataGenerator()->create_module('page', ['course' => $other->id]);
        $this->assertFalse(pictogram_rules::item_belongs_to_course($this->course, pictograms::TYPE_CM, (int) $otherpage->cmid));
        $othersection = get_fast_modinfo($other)->get_section_info(0);
        $othersectionid = (int) $othersection->id;
        $this->assertFalse(pictogram_rules::item_belongs_to_course($this->course, pictograms::TYPE_SECTION, $othersectionid));
        $this->assertFalse(pictogram_rules::item_belongs_to_course($this->course, 'block', 1));

        $this->setUser($this->teacher);
        $this->expectException(invalid_parameter_exception::class);
        pictograms::save($this->course, pictograms::TYPE_CM, (int) $otherpage->cmid, $this->data($this->create_draft()));
    }

    /**
     * Text alternatives are required, cleaned and limited in length.
     */
    public function test_alttext_validation(): void {
        $this->assertSame('Book', pictogram_rules::clean_alttext('  <b>Book</b> '));
        $longest = str_repeat('a', pictogram_rules::ALT_MAXLENGTH);
        $this->assertSame($longest, pictogram_rules::clean_alttext($longest));

        foreach (['', '   ', str_repeat('a', pictogram_rules::ALT_MAXLENGTH + 1)] as $invalid) {
            try {
                pictogram_rules::clean_alttext($invalid);
                $this->fail('Invalid text alternative accepted');
            } catch (invalid_parameter_exception $e) {
                $this->assertInstanceOf(invalid_parameter_exception::class, $e);
            }
        }
    }

    /**
     * SVG and other non-raster files are rejected, and nothing is kept.
     */
    public function test_rejects_svg(): void {
        global $DB;
        // The rollback of the theme's own transaction must be observable.
        $this->preventResetByRollback();
        $this->setUser($this->teacher);
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';
        try {
            pictograms::save(
                $this->course,
                pictograms::TYPE_CM,
                (int) $this->page->cmid,
                $this->data($this->create_draft('evil.svg', $svg))
            );
            $this->fail('An SVG file was accepted');
        } catch (invalid_parameter_exception $e) {
            $this->assertFalse($DB->record_exists(pictograms::TABLE, ['courseid' => $this->course->id]));
        }
    }

    /**
     * A file whose name says PNG but whose content is not an image is rejected.
     */
    public function test_rejects_disguised_file(): void {
        global $DB;
        $this->preventResetByRollback();
        $this->setUser($this->teacher);
        try {
            pictograms::save(
                $this->course,
                pictograms::TYPE_CM,
                (int) $this->page->cmid,
                $this->data($this->create_draft('fake.png', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'))
            );
            $this->fail('A file that is not an image was accepted');
        } catch (invalid_parameter_exception $e) {
            $this->assertFalse($DB->record_exists(pictograms::TABLE, ['courseid' => $this->course->id]));
        }
    }

    /**
     * A pictogram needs an image.
     */
    public function test_requires_image(): void {
        $this->setUser($this->teacher);
        $this->expectException(invalid_parameter_exception::class);
        pictograms::save($this->course, pictograms::TYPE_CM, (int) $this->page->cmid, $this->data(file_get_unused_draft_itemid()));
    }

    /**
     * Only licences known to the site are kept.
     */
    public function test_clean_license(): void {
        $this->assertSame('cc-nc-sa-4.0', pictogram_rules::clean_license('cc-nc-sa-4.0'));
        $this->assertSame('', pictogram_rules::clean_license('made-up-licence'));
        $this->assertSame('', pictogram_rules::clean_license(''));
    }

    /**
     * Teachers can remove pictograms, with their files.
     */
    public function test_delete(): void {
        $this->setUser($this->teacher);
        $record = pictograms::save($this->course, pictograms::TYPE_CM, (int) $this->page->cmid, $this->data($this->create_draft()));
        pictograms::delete($this->course, pictograms::TYPE_CM, (int) $this->page->cmid);

        $this->assertNull(pictograms::get_record($this->course->id, pictograms::TYPE_CM, (int) $this->page->cmid));
        $this->assertTrue(get_file_storage()->is_area_empty(
            context_course::instance($this->course->id)->id,
            'theme_chinijo',
            pictograms::FILEAREA,
            $record->id
        ));
        // Removing a missing pictogram is harmless.
        pictograms::delete($this->course, pictograms::TYPE_CM, (int) $this->page->cmid);
    }

    /**
     * Students only receive the pictograms of what they can see; teachers receive all of them.
     */
    public function test_render_data_respects_visibility(): void {
        $hidden = $this->getDataGenerator()->create_module('page', ['course' => $this->course->id, 'section' => 1,
            'visible' => 0]);
        $this->setUser($this->teacher);
        pictograms::save($this->course, pictograms::TYPE_CM, (int) $this->page->cmid, $this->data($this->create_draft()));
        pictograms::save($this->course, pictograms::TYPE_CM, (int) $hidden->cmid, $this->data($this->create_draft(), 'Secret'));

        $this->assertCount(2, pictogram_display::get_render_data($this->course));

        $this->setUser($this->student);
        $items = pictogram_display::get_render_data($this->course);
        $this->assertCount(1, $items);
        $this->assertSame((int) $this->page->cmid, $items[0]['id']);
        $this->assertSame('Book', $items[0]['alt']);
        $this->assertSame([], array_filter(pictogram_display::get_credits($this->course), fn($c) => $c['alt'] === 'Secret'));
    }

    /**
     * Hidden sections hide their pictograms from students.
     */
    public function test_hidden_section(): void {
        $section = get_fast_modinfo($this->course)->get_section_info(2);
        $this->setUser($this->teacher);
        $record = pictograms::save($this->course, pictograms::TYPE_SECTION, (int) $section->id, $this->data($this->create_draft()));
        $this->hide_section(2);

        $this->setUser($this->student);
        $this->assertFalse(pictogram_display::is_visible_to_user($record, get_course($this->course->id)));
        $this->setUser($this->teacher);
        $this->assertTrue(pictogram_display::is_visible_to_user($record, get_course($this->course->id)));
    }

    /**
     * Credits list the author and the licence, once per distinct attribution.
     */
    public function test_credits(): void {
        $second = $this->getDataGenerator()->create_module('page', ['course' => $this->course->id]);
        $this->setUser($this->teacher);
        pictograms::save($this->course, pictograms::TYPE_CM, (int) $this->page->cmid, $this->data($this->create_draft()));
        pictograms::save($this->course, pictograms::TYPE_CM, (int) $second->cmid, $this->data($this->create_draft()));

        $credits = pictogram_display::get_credits($this->course);
        $this->assertCount(1, $credits);
        $this->assertSame('ARASAAC', $credits[0]['author']);
        $this->assertStringContainsString('4.0', $credits[0]['license']);
    }

    /**
     * Deleting an activity, a section or a whole course removes its pictograms.
     */
    public function test_observers_clean_up(): void {
        global $DB;
        $this->setUser($this->teacher);
        $section = get_fast_modinfo($this->course)->get_section_info(2);
        pictograms::save($this->course, pictograms::TYPE_CM, (int) $this->page->cmid, $this->data($this->create_draft()));
        pictograms::save($this->course, pictograms::TYPE_SECTION, (int) $section->id, $this->data($this->create_draft()));

        $this->setAdminUser();
        $this->delete_activity((int) $this->page->cmid);
        $this->assertNull(pictograms::get_record($this->course->id, pictograms::TYPE_CM, (int) $this->page->cmid));

        \core_courseformat\formatactions::section($this->course->id)->delete($section, true);
        $this->assertNull(pictograms::get_record($this->course->id, pictograms::TYPE_SECTION, (int) $section->id));

        $page = $this->getDataGenerator()->create_module('page', ['course' => $this->course->id]);
        pictograms::save($this->course, pictograms::TYPE_CM, (int) $page->cmid, $this->data($this->create_draft()));
        delete_course($this->course, false);
        $this->assertFalse($DB->record_exists(pictograms::TABLE, ['courseid' => $this->course->id]));
    }

    /**
     * The course page carries the data for the script only when there are pictograms.
     */
    public function test_render_page_data(): void {
        global $PAGE;
        // Enrolment may send the course welcome e-mail, which sets up the global page's theme.
        $PAGE = new \moodle_page();
        $PAGE->set_course($this->course);
        $output = $PAGE->get_renderer('core');
        $this->setUser($this->student);
        $this->assertSame('', pictogram_display::render_page_data($PAGE));

        $this->setUser($this->teacher);
        pictograms::save($this->course, pictograms::TYPE_CM, (int) $this->page->cmid, $this->data(
            $this->create_draft(),
            'Book "quoted" <b>'
        ));
        $html = pictogram_display::render_page_data($PAGE);
        $this->assertStringContainsString('data-region="theme_chinijo-pictograms"', $html);
        // The JSON is escaped as an attribute value.
        $this->assertStringNotContainsString('<b>', $html);
        $this->assertStringContainsString('hidden', $html);

        $this->assertStringContainsString(
            get_string('pictogramcredits', 'theme_chinijo'),
            pictogram_display::render_credits($output, $PAGE)
        );
    }
}
