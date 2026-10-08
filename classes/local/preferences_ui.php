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
use moodle_url;
use renderer_base;

/**
 * Builds the template data for the display settings control and form.
 *
 * The same form template is used by the stand-alone page (which works without
 * JavaScript) and by the dialogue opened from the navbar control.
 *
 * @package    theme_chinijo
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class preferences_ui {
    /** @var string Path of the stand-alone display settings page. */
    public const PAGE_PATH = '/theme/chinijo/preferences.php';

    /**
     * Render the control that opens the display settings, plus the form used by the dialogue.
     *
     * @param renderer_base $output Renderer used to render the templates.
     * @param moodle_page $page The page being rendered.
     * @param bool $toolbar True to render it as a stand-alone toolbar (layouts without a navbar).
     * @return string HTML.
     */
    public static function render_control(renderer_base $output, moodle_page $page, bool $toolbar): string {
        if (self::is_preferences_page($page)) {
            // The page itself is the form: a second copy in a dialogue would only duplicate it.
            return '';
        }
        $returnurl = self::get_return_url($page);
        $pageurl = new moodle_url(self::PAGE_PATH, ['returnurl' => $returnurl->out_as_local_url(false)]);
        $form = $output->render_from_template('theme_chinijo/preferences_form', self::get_form_context($returnurl, true));

        $page->requires->js_call_amd('theme_chinijo/preferences', 'init', [[
            'canpersist' => preferences::can_persist(),
        ]]);

        return $output->render_from_template('theme_chinijo/preferences_control', [
            'url' => $pageurl->out(false),
            'toolbar' => $toolbar,
            'form' => $form,
        ]);
    }

    /**
     * Template context for the display settings form.
     *
     * @param moodle_url $returnurl Where to go back to after saving without JavaScript.
     * @param bool $indialogue True when the form is shown inside the dialogue.
     * @return array
     */
    public static function get_form_context(moodle_url $returnurl, bool $indialogue): array {
        $values = preferences::get_all();
        $groups = [];
        foreach (preferences::CHOICES as $name => $choices) {
            $options = [];
            foreach ($choices as $choice) {
                $options[] = [
                    'name' => $name,
                    'value' => $choice,
                    'id' => 'theme-chinijo-pref-' . ($indialogue ? 'dlg-' : 'page-') . $name . '-' . $choice,
                    'label' => get_string('pref_' . $name . '_' . $choice, 'theme_chinijo'),
                    'checked' => $values[$name] === $choice,
                    'isdefault' => $choice === preferences::DEFAULT,
                    // Motion and sound show a small drawing instead of the "Aa" sample.
                    'icon' . $name . $choice => true,
                ];
            }
            $groups[] = [
                'name' => $name,
                'legend' => get_string('pref_' . $name, 'theme_chinijo'),
                'hassample' => !in_array($name, ['motion', 'sound'], true),
                'options' => $options,
            ];
        }

        $presets = [];
        foreach (array_keys(preferences::PRESETS) as $preset) {
            $presets[] = [
                'name' => $preset,
                'label' => get_string('prefs_preset_' . $preset, 'theme_chinijo'),
                'valuesjson' => json_encode(preferences::get_preset_values($preset)),
                'iscalm' => $preset === 'calm',
            ];
        }

        return [
            'actionurl' => (new moodle_url(self::PAGE_PATH))->out(false),
            'sesskey' => sesskey(),
            'returnurl' => $returnurl->out_as_local_url(false),
            'cancelurl' => $returnurl->out(false),
            'indialogue' => $indialogue,
            'canpersist' => preferences::can_persist(),
            'presets' => $presets,
            'groups' => $groups,
        ];
    }

    /**
     * Whether the page is the stand-alone display settings page.
     *
     * @param moodle_page $page The page being rendered.
     * @return bool
     */
    public static function is_preferences_page(moodle_page $page): bool {
        return $page->has_set_url() && $page->url->compare(new moodle_url(self::PAGE_PATH), URL_MATCH_BASE);
    }

    /**
     * The local URL to come back to after saving the display settings.
     *
     * @param moodle_page $page The page being rendered.
     * @return moodle_url
     */
    public static function get_return_url(moodle_page $page): moodle_url {
        global $CFG;

        if (!$page->has_set_url() || self::is_preferences_page($page)) {
            return new moodle_url('/');
        }
        $url = $page->url;
        if (strpos($url->out(false), $CFG->wwwroot) !== 0) {
            return new moodle_url('/');
        }
        return $url;
    }
}
