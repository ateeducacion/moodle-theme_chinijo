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

namespace theme_chinijo\form;

use theme_chinijo\local\pictogram_rules;

#[\PHPUnit\Framework\Attributes\CoversClass(pictogram_form::class)]
/**
 * Tests for the pictogram form's server-side validation.
 *
 * @package    theme_chinijo
 * @category   test
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_chinijo\form\pictogram_form
 */
final class pictogram_form_test extends \advanced_testcase {
    /**
     * Put a file in the current user's draft area.
     *
     * @param string $filename File name.
     * @param string $content File content.
     * @return int Draft item id.
     */
    private function draft(string $filename, string $content): int {
        global $USER;
        $draftitemid = file_get_unused_draft_itemid();
        get_file_storage()->create_file_from_string([
            'contextid' => \context_user::instance($USER->id)->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => $filename,
        ], $content);
        return $draftitemid;
    }

    /**
     * Validate data with a new form.
     *
     * @param array $data Submitted data.
     * @return array Errors.
     */
    private function validate(array $data): array {
        $form = new pictogram_form(new \moodle_url('/theme/chinijo/pictograms.php'), ['itemname' => 'Read the story']);
        return $form->validation($data + ['id' => 1, 'itemtype' => 'cm', 'itemid' => 1, 'author' => '', 'license' => ''], []);
    }

    /**
     * A PNG with a text alternative is accepted.
     */
    public function test_valid(): void {
        global $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        $png = file_get_contents($CFG->dirroot . '/theme/chinijo/tests/fixtures/pictogram-book.png');
        $this->assertSame([], $this->validate(['alttext' => 'Book', 'pictogram' => $this->draft('book.png', $png)]));
    }

    /**
     * The text alternative is required and limited in length, even if the browser skipped the client rules.
     */
    public function test_alttext(): void {
        global $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        $png = file_get_contents($CFG->dirroot . '/theme/chinijo/tests/fixtures/pictogram-book.png');

        $errors = $this->validate(['alttext' => '   ', 'pictogram' => $this->draft('book.png', $png)]);
        $this->assertArrayHasKey('alttext', $errors);

        $long = str_repeat('a', pictogram_rules::ALT_MAXLENGTH + 1);
        $errors = $this->validate(['alttext' => $long, 'pictogram' => $this->draft('book.png', $png)]);
        $this->assertArrayHasKey('alttext', $errors);
    }

    /**
     * Exactly one image is needed, and it must really be a PNG, JPEG or WebP image.
     */
    public function test_file(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $errors = $this->validate(['alttext' => 'Book', 'pictogram' => file_get_unused_draft_itemid()]);
        $this->assertSame(get_string('pictogram_error_required', 'theme_chinijo'), $errors['pictogram']);

        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';
        $errors = $this->validate(['alttext' => 'Book', 'pictogram' => $this->draft('evil.svg', $svg)]);
        $this->assertSame(get_string('pictogram_error_type', 'theme_chinijo'), $errors['pictogram']);

        $errors = $this->validate(['alttext' => 'Book', 'pictogram' => $this->draft('fake.png', $svg)]);
        $this->assertSame(get_string('pictogram_error_type', 'theme_chinijo'), $errors['pictogram']);

        $errors = $this->validate(['alttext' => 'Book',
            'pictogram' => $this->draft('huge.png', str_repeat('x', pictogram_rules::MAX_BYTES + 1))]);
        $this->assertStringContainsString(display_size(pictogram_rules::MAX_BYTES), $errors['pictogram']);
    }

    /**
     * The licence menu lists the site's licences, and an empty choice.
     */
    public function test_definition(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $form = new pictogram_form(new \moodle_url('/theme/chinijo/pictograms.php'), ['itemname' => 'Story <b>']);
        ob_start();
        $form->display();
        $html = ob_get_clean();
        $this->assertStringContainsString('value="cc-nc-sa-4.0"', $html);
        $this->assertStringContainsString(get_string('pictogram_license_none', 'theme_chinijo'), $html);
        $this->assertStringContainsString('Story &lt;b&gt;', $html);
        $this->assertStringContainsString('name="alttext"', $html);
    }
}
