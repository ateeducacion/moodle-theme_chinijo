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

use theme_chinijo\local\feder;

/**
 * Tests for the EU funding (FEDER) notice.
 *
 * @package    theme_chinijo
 * @category   test
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_chinijo\local\feder
 */
#[\PHPUnit\Framework\Attributes\CoversClass(feder::class)]
final class feder_test extends \advanced_testcase {
    /**
     * Store an emblem file in the theme setting, as the admin setting does.
     */
    protected function store_emblem(): void {
        $file = get_file_storage()->create_file_from_string([
            'contextid' => \context_system::instance()->id,
            'component' => 'theme_chinijo',
            'filearea' => 'federlogo',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'emblem.png',
        ], base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));
        set_config('federlogo', $file->get_filepath() . $file->get_filename(), 'theme_chinijo');
    }

    /**
     * Nothing is shown while the notice is disabled or empty: the feature degrades safely.
     */
    public function test_disabled_or_empty(): void {
        $this->resetAfterTest();
        $this->assertNull(feder::get_content());

        set_config('federenabled', 1, 'theme_chinijo');
        $this->assertNull(feder::get_content());

        // An emblem without a text alternative is not shown.
        $this->store_emblem();
        $this->assertNull(feder::get_content());
    }

    /**
     * The emblem is shown with its text alternative, and the text is formatted and cleaned.
     */
    public function test_content(): void {
        $this->resetAfterTest();
        set_config('federenabled', 1, 'theme_chinijo');
        $this->store_emblem();
        set_config('federlogoalt', 'Co-funded by the European Union', 'theme_chinijo');
        set_config('federtext', '<p>Acknowledgement.</p><script>alert(1)</script>', 'theme_chinijo');

        $content = feder::get_content();
        $this->assertStringContainsString('/theme_chinijo/federlogo/', $content['logourl']);
        $this->assertSame('Co-funded by the European Union', $content['logoalt']);
        $this->assertStringContainsString('Acknowledgement.', $content['text']);
        $this->assertStringNotContainsString('<script', $content['text']);
    }

    /**
     * A text on its own is enough.
     */
    public function test_text_only(): void {
        $this->resetAfterTest();
        set_config('federenabled', 1, 'theme_chinijo');
        set_config('federtext', 'Project co-funded by FEDER.', 'theme_chinijo');

        $content = feder::get_content();
        $this->assertNull($content['logourl']);
        $this->assertStringContainsString('FEDER', $content['text']);
    }

    /**
     * The placement setting decides the pages.
     */
    public function test_is_shown_on(): void {
        $this->resetAfterTest();
        $page = new \moodle_page();
        $page->set_pagelayout('incourse');
        $page->set_pagetype('mod-page-view');
        $this->assertFalse(feder::is_shown_on($page));

        foreach (['site-index' => 'frontpage', 'my-index' => 'mydashboard', 'login-index' => 'login'] as $type => $layout) {
            $landing = new \moodle_page();
            $landing->set_pagelayout($layout);
            $landing->set_pagetype($type);
            $this->assertTrue(feder::is_shown_on($landing), $type);
        }

        set_config('federplacement', feder::PLACEMENT_ALL, 'theme_chinijo');
        $this->assertTrue(feder::is_shown_on($page));
    }

    /**
     * The rendered notice is a labelled landmark outside the main region, and a plain block inside it.
     */
    public function test_render(): void {
        global $PAGE;
        $this->resetAfterTest();
        set_config('federenabled', 1, 'theme_chinijo');
        set_config('federtext', 'Project co-funded by FEDER.', 'theme_chinijo');
        set_config('federplacement', feder::PLACEMENT_ALL, 'theme_chinijo');

        $output = $PAGE->get_renderer('core');
        $html = feder::render($output, $PAGE, true);
        $this->assertStringContainsString('<aside class="theme-chinijo-feder" aria-label="' .
            get_string('feder_region', 'theme_chinijo') . '"', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('<div class="theme-chinijo-feder"', feder::render($output, $PAGE, false));

        set_config('federenabled', 0, 'theme_chinijo');
        $this->assertSame('', feder::render($output, $PAGE, true));
    }
}
