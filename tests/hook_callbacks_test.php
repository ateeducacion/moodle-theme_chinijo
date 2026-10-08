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

use core\hook\output\after_standard_main_region_html_generation;
use core\hook\output\before_footer_html_generation;
use core\hook\output\before_html_attributes;
use core\hook\output\before_standard_top_of_body_html_generation;
use theme_chinijo\local\preferences;
use theme_chinijo\local\theme;

#[\PHPUnit\Framework\Attributes\CoversClass(hook_callbacks::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(theme::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\theme_chinijo\local\preferences_ui::class)]
/**
 * Tests for the output hook callbacks.
 *
 * @package    theme_chinijo
 * @category   test
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_chinijo\hook_callbacks
 * @covers     \theme_chinijo\local\theme
 * @covers     \theme_chinijo\local\preferences_ui
 */
final class hook_callbacks_test extends \advanced_testcase {
    /**
     * Create a page rendered by the given theme with the given layout.
     *
     * @param string $themename Theme name.
     * @param string $layout Page layout.
     * @param string $pagetype Page type.
     * @return \moodle_page
     */
    protected function create_page(string $themename, string $layout = 'standard', string $pagetype = 'course-view'): \moodle_page {
        $page = new \moodle_page();
        $page->set_context(\context_system::instance());
        $page->set_url(new \moodle_url('/course/view.php', ['id' => SITEID]));
        $page->set_pagelayout($layout);
        $page->set_pagetype($pagetype);
        $page->force_theme($themename);
        return $page;
    }

    /**
     * The display preferences are written to the html element when Chinijo renders the page.
     */
    public function test_before_html_attributes(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        preferences::set_many(['fontsize' => 'xxlarge', 'font' => 'legible']);

        $page = $this->create_page('chinijo');
        $hook = new before_html_attributes($page->get_renderer('core'));
        hook_callbacks::before_html_attributes($hook);
        $attributes = $hook->get_attributes();
        $this->assertSame('xxlarge', $attributes['data-chinijo-fontsize']);
        $this->assertSame('legible', $attributes['data-chinijo-font']);
        $this->assertSame('default', $attributes['data-chinijo-contrast']);
        $this->assertArrayNotHasKey('data-bs-theme', $attributes);

        // The whole html element, as the layouts print it.
        $this->assertStringContainsString('data-chinijo-fontsize="xxlarge"', $page->get_renderer('core')->htmlattributes());
    }

    /**
     * Nothing is added when another theme renders the page.
     */
    public function test_before_html_attributes_other_theme(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());

        $page = $this->create_page('boost');
        $hook = new before_html_attributes($page->get_renderer('core'));
        hook_callbacks::before_html_attributes($hook);
        $this->assertSame([], $hook->get_attributes());
        $this->assertFalse(theme::is_active($page));
    }

    /**
     * High contrast pins Boost's colour mode to light, so data-bs-theme never contradicts it.
     */
    public function test_high_contrast_pins_light_colour_mode(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        preferences::set('contrast', 'high');

        $page = $this->create_page('chinijo');
        $hook = new before_html_attributes($page->get_renderer('core'), ['data-bs-theme' => 'dark', 'data-colourmode' => 'dark']);
        hook_callbacks::before_html_attributes($hook);
        $this->assertSame('light', $hook->get_attributes()['data-bs-theme']);
        $this->assertSame('light', $hook->get_attributes()['data-colourmode']);

        // Without high contrast the colour mode is left alone.
        preferences::set('contrast', preferences::DEFAULT);
        $hook = new before_html_attributes($page->get_renderer('core'), ['data-bs-theme' => 'dark']);
        hook_callbacks::before_html_attributes($hook);
        $this->assertSame('dark', $hook->get_attributes()['data-bs-theme']);
    }

    /**
     * On Moodle 5.3 with Boost's colour modes enabled, the real html element agrees with high contrast.
     */
    public function test_high_contrast_with_boost_colour_modes(): void {
        if (!class_exists(\theme_boost\colour_mode::class)) {
            $this->markTestSkipped('Boost colour modes are only available from Moodle 5.3.');
        }
        $this->resetAfterTest();
        set_config('enablecolourmodes', 1, 'theme_boost');
        set_config('defaultcolourmode', 'dark', 'theme_boost');
        $this->setUser($this->getDataGenerator()->create_user());
        preferences::set('contrast', 'high');

        global $PAGE;
        $PAGE = $this->create_page('chinijo');
        $html = $PAGE->get_renderer('core')->htmlattributes();
        $this->assertStringContainsString('data-bs-theme="light"', $html);
        $this->assertStringContainsString('data-chinijo-contrast="high"', $html);
    }

    /**
     * The login page, which has no navbar, gets the display settings toolbar.
     */
    public function test_toolbar_on_login_layout(): void {
        $this->resetAfterTest();
        $this->setUser(null);

        $page = $this->create_page('chinijo', 'login', 'login-index');
        $hook = new before_standard_top_of_body_html_generation($page->get_renderer('core'));
        hook_callbacks::before_standard_top_of_body_html_generation($hook);
        $html = $hook->get_output();
        $this->assertStringContainsString('data-region="theme_chinijo-toolbar"', $html);
        $this->assertStringContainsString('/theme/chinijo/preferences.php', $html);
        $this->assertStringContainsString(get_string('prefs_sessiononly', 'theme_chinijo'), $html);

        // Pages with a navbar get the control from the navbar callback instead.
        $page = $this->create_page('chinijo');
        $hook = new before_standard_top_of_body_html_generation($page->get_renderer('core'));
        hook_callbacks::before_standard_top_of_body_html_generation($hook);
        $this->assertSame('', $hook->get_output());

        // Quiet layouts (pop-ups, embedded frames, secure windows) never get it.
        foreach (theme::QUIET_LAYOUTS as $layout) {
            $this->assertFalse(theme::can_decorate($this->create_page('chinijo', $layout)), $layout);
        }
    }

    /**
     * The navbar callback renders the control and the dialogue form, with labelled radio groups.
     */
    public function test_navbar_control(): void {
        global $CFG;
        require_once($CFG->dirroot . '/theme/chinijo/lib.php');
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        preferences::set('fontsize', 'large');

        $page = $this->create_page('chinijo');
        $html = theme_chinijo_render_navbar_output($page->get_renderer('core'));
        $this->assertStringContainsString('data-action="theme_chinijo-open-preferences"', $html);
        $this->assertStringContainsString(get_string('displaysettings', 'theme_chinijo'), $html);
        $this->assertMatchesRegularExpression('/<legend[^>]*>' . preg_quote(get_string('pref_fontsize', 'theme_chinijo'), '/') .
            '<\/legend>/', $html);
        $this->assertMatchesRegularExpression('/name="fontsize"[^>]*value="large" checked/', $html);
        $this->assertStringContainsString('name="sesskey" value="' . sesskey() . '"', $html);

        $this->assertSame('', theme_chinijo_render_navbar_output($this->create_page('boost')->get_renderer('core')));
    }

    /**
     * The FEDER notice is rendered after the main region, and on the login page before the footer.
     */
    public function test_feder_notice_placement(): void {
        $this->resetAfterTest();
        set_config('federenabled', 1, 'theme_chinijo');
        set_config('federtext', '<p>Funded by the European Union.</p>', 'theme_chinijo');

        $page = $this->create_page('chinijo', 'frontpage', 'site-index');
        $hook = new after_standard_main_region_html_generation($page->get_renderer('core'));
        hook_callbacks::after_standard_main_region_html_generation($hook);
        $this->assertStringContainsString('<aside class="theme-chinijo-feder"', $hook->get_output());

        $page = $this->create_page('chinijo', 'login', 'login-index');
        $hook = new before_footer_html_generation($page->get_renderer('core'));
        hook_callbacks::before_footer_html_generation($hook);
        $this->assertStringContainsString('<div class="theme-chinijo-feder"', $hook->get_output());

        // The footer hook only serves the login page.
        $page = $this->create_page('chinijo', 'frontpage', 'site-index');
        $hook = new before_footer_html_generation($page->get_renderer('core'));
        hook_callbacks::before_footer_html_generation($hook);
        $this->assertSame('', $hook->get_output());

        // Another theme gets nothing.
        $page = $this->create_page('boost', 'frontpage', 'site-index');
        $hook = new after_standard_main_region_html_generation($page->get_renderer('core'));
        hook_callbacks::after_standard_main_region_html_generation($hook);
        $this->assertSame('', $hook->get_output());
    }
}
