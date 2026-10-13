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

namespace theme_chinijo\local;

use moodle_page;

/**
 * Helpers to decide whether Chinijo is the theme rendering a page.
 *
 * Hook callbacks and lib.php callbacks run whatever the current theme is, so
 * every one of them must check this before adding anything to the page.
 *
 * @package    theme_chinijo
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme {
    /** @var string Name of this theme. */
    public const NAME = 'chinijo';

    /** @var string[] Page layouts without Boost's navbar, where the preferences control goes at the top of the body. */
    public const LAYOUTS_WITHOUT_NAVBAR = ['login'];

    /** @var string[] Page layouts on which nothing is ever added (pop-ups, embedded frames, maintenance and secure). */
    public const QUIET_LAYOUTS = ['embedded', 'maintenance', 'popup', 'frametop', 'redirect', 'secure', 'print'];

    /**
     * Whether Chinijo (or a theme inheriting from it) renders the page.
     *
     * @param moodle_page|null $page The page, or null for the global $PAGE.
     * @return bool
     */
    public static function is_active(?moodle_page $page = null): bool {
        global $PAGE;

        if (during_initial_install()) {
            return false;
        }
        $page = $page ?? $PAGE;
        $theme = $page->theme;
        return $theme->name === self::NAME || in_array(self::NAME, (array) $theme->parents, true);
    }

    /**
     * Whether the theme may add its own interface (preferences control, notices) to the page.
     *
     * @param moodle_page|null $page The page, or null for the global $PAGE.
     * @return bool
     */
    public static function can_decorate(?moodle_page $page = null): bool {
        global $PAGE;

        $page = $page ?? $PAGE;
        return self::is_active($page) && !in_array($page->pagelayout, self::QUIET_LAYOUTS, true);
    }
}
