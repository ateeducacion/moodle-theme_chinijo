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
use theme_chinijo\local\feder;
use theme_chinijo\local\pictogram_display;
use theme_chinijo\local\preferences;
use theme_chinijo\local\preferences_ui;
use theme_chinijo\local\theme;

/**
 * Output hook callbacks registered in db/hooks.php.
 *
 * Hooks are dispatched whatever the current theme is, so every callback first
 * checks that Chinijo renders the page.
 *
 * @package    theme_chinijo
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Write the display preferences to the html element, so they apply before the first paint.
     *
     * Registered with a low priority so that it runs after Boost's own listener.
     * On Moodle 5.3, when a site turns on Boost's experimental colour modes, the
     * high contrast mode pins the light colour scheme so that data-bs-theme
     * never contradicts it.
     *
     * @param before_html_attributes $hook The hook.
     */
    public static function before_html_attributes(before_html_attributes $hook): void {
        if (!theme::is_active($hook->renderer->get_page())) {
            return;
        }
        $values = preferences::get_all();
        foreach (preferences::get_html_attributes($values) as $name => $value) {
            $hook->add_attribute($name, $value);
        }
        if ($values['contrast'] === 'high' && array_key_exists('data-bs-theme', $hook->get_attributes())) {
            $hook->add_attribute('data-bs-theme', 'light');
            $hook->add_attribute('data-colourmode', 'light');
        }
    }

    /**
     * Add the display settings toolbar to layouts that have no navbar, such as the login page.
     *
     * @param before_standard_top_of_body_html_generation $hook The hook.
     */
    public static function before_standard_top_of_body_html_generation(
        before_standard_top_of_body_html_generation $hook
    ): void {
        $page = $hook->renderer->get_page();
        if (!theme::can_decorate($page) || !in_array($page->pagelayout, theme::LAYOUTS_WITHOUT_NAVBAR, true)) {
            return;
        }
        $hook->add_html(preferences_ui::render_control($hook->renderer, $page, true));
    }

    /**
     * Add the FEDER notice and the pictogram credits after the main region.
     *
     * @param after_standard_main_region_html_generation $hook The hook.
     */
    public static function after_standard_main_region_html_generation(
        after_standard_main_region_html_generation $hook
    ): void {
        $page = $hook->renderer->get_page();
        if (!theme::can_decorate($page)) {
            return;
        }
        $hook->add_html(pictogram_display::render_credits($hook->renderer, $page));
        $hook->add_html(feder::render($hook->renderer, $page, true));
    }

    /**
     * Add the FEDER notice to the login page, whose layout has no region after the main one.
     *
     * @param before_footer_html_generation $hook The hook.
     */
    public static function before_footer_html_generation(before_footer_html_generation $hook): void {
        $page = $hook->renderer->get_page();
        if (!theme::can_decorate($page) || $page->pagelayout !== 'login') {
            return;
        }
        $hook->add_html(feder::render($hook->renderer, $page, false));
    }
}
