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

use context_system;
use moodle_page;
use renderer_base;

/**
 * European Union emblem and FEDER (ERDF) acknowledgement configured by administrators.
 *
 * The theme ships no emblem and no wording: the approved institutional emblem
 * file, its text alternative and the acknowledgement must be supplied by the
 * institution (Regulation (EU) 2021/1060, Article 47 and Annex IX). When
 * neither an emblem nor a text has been configured nothing is rendered, so
 * the feature degrades safely.
 *
 * @package    theme_chinijo
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class feder {
    /** @var string Show the notice on the login page, the site home and the dashboard. */
    public const PLACEMENT_LANDING = 'landing';

    /** @var string Show the notice on every page rendered by the theme. */
    public const PLACEMENT_ALL = 'all';

    /** @var string[] Page types counted as landing pages (besides the login layout). */
    public const LANDING_PAGETYPES = ['site-index', 'my-index'];

    /**
     * Template data for the notice, or null when it is disabled or has nothing to show.
     *
     * @return array|null
     */
    public static function get_content(): ?array {
        $config = get_config('theme_chinijo');
        if (empty($config->federenabled)) {
            return null;
        }
        $context = context_system::instance();
        $logoalt = trim((string) ($config->federlogoalt ?? ''));
        $text = trim((string) ($config->federtext ?? ''));
        // An emblem without a text alternative would be inaccessible, so it is not shown.
        $logourl = $logoalt !== '' ? self::get_logo_url($config) : null;
        if ($logourl === null && $text === '') {
            return null;
        }
        return [
            'logourl' => $logourl,
            'logoalt' => $logourl !== null ? format_string($logoalt, true, ['context' => $context]) : '',
            'text' => $text !== '' ? format_text($text, FORMAT_HTML, ['context' => $context]) : '',
        ];
    }

    /**
     * URL of the uploaded emblem, or null when there is none.
     *
     * @param \stdClass $config Theme configuration.
     * @return string|null A protocol-relative URL, as Boost uses for its own setting files.
     */
    protected static function get_logo_url(\stdClass $config): ?string {
        if (empty($config->federlogo)) {
            return null;
        }
        return \theme_config::load(theme::NAME)->setting_file_url('federlogo', 'federlogo') ?: null;
    }

    /**
     * Whether the notice belongs on the given page according to the placement setting.
     *
     * @param moodle_page $page The page.
     * @return bool
     */
    public static function is_shown_on(moodle_page $page): bool {
        $placement = get_config('theme_chinijo', 'federplacement') ?: self::PLACEMENT_LANDING;
        if ($placement === self::PLACEMENT_ALL) {
            return true;
        }
        return $page->pagelayout === 'login' || in_array($page->pagetype, self::LANDING_PAGETYPES, true);
    }

    /**
     * Render the notice for the page, or an empty string.
     *
     * @param renderer_base $output Renderer.
     * @param moodle_page $page The page.
     * @param bool $landmark True to render it as a complementary landmark (outside the main region).
     * @return string HTML.
     */
    public static function render(renderer_base $output, moodle_page $page, bool $landmark): string {
        if (!self::is_shown_on($page)) {
            return '';
        }
        $content = self::get_content();
        if ($content === null) {
            return '';
        }
        $content['landmark'] = $landmark;
        return $output->render_from_template('theme_chinijo/feder', $content);
    }
}
