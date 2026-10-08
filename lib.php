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

/**
 * Moodle callbacks for the Chinijo theme.
 *
 * Only documented plugin callbacks live here. Logic is delegated to autoloaded
 * classes under classes/ so that it can be unit tested.
 *
 * @package    theme_chinijo
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use theme_chinijo\local\pictogram_display;
use theme_chinijo\local\pictograms;
use theme_chinijo\local\preferences;
use theme_chinijo\local\theme;

/**
 * Main SCSS: Chinijo variables, then Boost's default preset, then Chinijo rules.
 *
 * @param theme_config $theme The theme configuration object.
 * @return string SCSS source.
 */
function theme_chinijo_get_main_scss_content($theme): string {
    global $CFG;

    $pre = file_get_contents(__DIR__ . '/scss/pre.scss');
    $boost = file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');
    $post = file_get_contents(__DIR__ . '/scss/post.scss');
    return $pre . "\n" . $boost . "\n" . $post;
}

/**
 * SCSS prepended before everything else: brand colour and raw pre-SCSS settings.
 *
 * @param theme_config $theme The theme configuration object.
 * @return string SCSS source.
 */
function theme_chinijo_get_pre_scss($theme): string {
    $scss = '';
    $brandcolor = $theme->settings->brandcolor ?? '';
    if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $brandcolor)) {
        $scss .= '$primary: ' . $brandcolor . ";\n";
    }
    if (defined('BEHAT_SITE_RUNNING')) {
        $scss .= "\$behatsite: true;\n";
    }
    if (!empty($theme->settings->scsspre)) {
        $scss .= $theme->settings->scsspre . "\n";
    }
    return $scss;
}

/**
 * SCSS appended after everything else: raw SCSS from the advanced settings.
 *
 * @param theme_config $theme The theme configuration object.
 * @return string SCSS source.
 */
function theme_chinijo_get_extra_scss($theme): string {
    return !empty($theme->settings->scss) ? $theme->settings->scss : '';
}

/**
 * Display preference definitions, so core_user validates writes made through its APIs.
 *
 * @return array
 */
function theme_chinijo_user_preferences(): array {
    return preferences::get_user_preference_definitions();
}

/**
 * Serve the theme's files: the FEDER emblem setting and course pictograms.
 *
 * @param stdClass $course Course object.
 * @param stdClass|null $cm Course module object, unused.
 * @param context $context Context of the file.
 * @param string $filearea File area.
 * @param array $args Remaining path arguments.
 * @param bool $forcedownload Whether to force a download.
 * @param array $options Additional send_file options.
 * @return bool False when the file is not found; otherwise the file is sent and the script ends.
 */
function theme_chinijo_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel == CONTEXT_SYSTEM && $filearea === 'federlogo') {
        $theme = theme_config::load(theme::NAME);
        if (!array_key_exists('cacheability', $options)) {
            $options['cacheability'] = 'public';
        }
        return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
    }
    if ($context->contextlevel == CONTEXT_COURSE && $filearea === pictograms::FILEAREA) {
        pictogram_display::serve_file($course, $context, $args, $forcedownload, $options);
    }
    send_file_not_found();
}

/**
 * Add the display settings control to Boost's navbar when Chinijo renders the page.
 *
 * @param renderer_base $renderer The page renderer.
 * @return string HTML.
 */
function theme_chinijo_render_navbar_output(renderer_base $renderer): string {
    $page = $renderer->get_page();
    if (!theme::can_decorate($page)) {
        return '';
    }
    return \theme_chinijo\local\preferences_ui::render_control($renderer, $page, false);
}

/**
 * Add the pictogram management page to the course administration menu.
 *
 * @param navigation_node $navigation The course administration node.
 * @param stdClass $course The course.
 * @param context_course $context The course context.
 */
function theme_chinijo_extend_navigation_course(navigation_node $navigation, stdClass $course, context_course $context): void {
    if (!theme::is_active() || !has_capability('theme/chinijo:managepictograms', $context)) {
        return;
    }
    $navigation->add(
        get_string('pictograms', 'theme_chinijo'),
        new moodle_url('/theme/chinijo/pictograms.php', ['id' => $course->id]),
        navigation_node::TYPE_SETTING,
        null,
        'theme_chinijo_pictograms',
        new pix_icon('i/settings', '')
    );
}

/**
 * Add a link to the display settings page in the user's own preferences.
 *
 * @param navigation_node $navigation The user settings node.
 * @param stdClass $user The user whose settings are being shown.
 * @param context_user $usercontext The user context.
 * @param stdClass $course The current course.
 * @param context_course $coursecontext The current course context.
 */
function theme_chinijo_extend_navigation_user_settings(
    navigation_node $navigation,
    stdClass $user,
    context_user $usercontext,
    stdClass $course,
    context_course $coursecontext
): void {
    global $USER;

    if ((int) $user->id !== (int) $USER->id || !theme::is_active()) {
        return;
    }
    $navigation->add(
        get_string('displaysettings', 'theme_chinijo'),
        new moodle_url('/theme/chinijo/preferences.php'),
        navigation_node::TYPE_SETTING,
        null,
        'theme_chinijo_preferences'
    );
}
