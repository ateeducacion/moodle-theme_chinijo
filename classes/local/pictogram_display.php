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

use context_course;
use moodle_page;
use renderer_base;
use stdClass;

/**
 * What learners see of the pictograms: visibility, page data, credits and file serving.
 *
 * Learners only receive the pictograms of the sections and activities they can
 * see on the course page. Names are never replaced: the page script only adds
 * images next to them, so courses look exactly as usual without pictograms or
 * without JavaScript.
 *
 * @package    theme_chinijo
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pictogram_display {
    /**
     * Whether the current user may see a pictogram: only when they may see its item on the course page.
     *
     * @param stdClass $record Pictogram record.
     * @param stdClass $course The course.
     * @return bool
     */
    public static function is_visible_to_user(stdClass $record, stdClass $course): bool {
        if (has_capability(pictograms::CAPABILITY, context_course::instance($course->id))) {
            return true;
        }
        $modinfo = get_fast_modinfo($course);
        if ($record->itemtype === pictograms::TYPE_CM) {
            $cms = $modinfo->get_cms();
            return isset($cms[$record->itemid]) && $cms[$record->itemid]->is_visible_on_course_page();
        }
        $section = $modinfo->get_section_info_by_id((int) $record->itemid);
        return $section !== null && ($section->uservisible || ($section->visible && !empty($section->availableinfo)));
    }

    /**
     * Pictograms that the current user may see in a course, ready for the page script.
     *
     * @param stdClass $course The course.
     * @return array[] Each with type, id, url and alt.
     */
    public static function get_render_data(stdClass $course): array {
        $items = [];
        foreach (pictograms::get_records($course->id) as $record) {
            $file = self::is_visible_to_user($record, $course) ? pictograms::get_file($record) : null;
            if ($file === null) {
                continue;
            }
            $items[] = [
                'type' => $record->itemtype,
                'id' => (int) $record->itemid,
                'url' => pictograms::get_file_url($file)->out(false),
                'alt' => $record->alttext,
            ];
        }
        return $items;
    }

    /**
     * Attribution lines for the pictograms that the current user may see in a course.
     *
     * @param stdClass $course The course.
     * @return array[] Each with alt, author, license (full name) and licenseurl, de-duplicated.
     */
    public static function get_credits(stdClass $course): array {
        global $CFG;
        require_once($CFG->libdir . '/licenselib.php');

        $credits = [];
        foreach (pictograms::get_records($course->id) as $record) {
            if ((empty($record->author) && empty($record->license)) || !self::is_visible_to_user($record, $course)) {
                continue;
            }
            $license = !empty($record->license) ? \license_manager::get_license_by_shortname($record->license) : null;
            $credit = [
                'alt' => $record->alttext,
                'author' => (string) $record->author,
                'license' => $license ? $license->fullname : '',
                'licenseurl' => ($license && !empty($license->source)) ? $license->source : '',
            ];
            // Keyed by the attribution itself, so that each distinct credit is listed once.
            $credits[json_encode($credit)] = $credit;
        }
        return array_values($credits);
    }

    /**
     * The course of a page when it is a real course (not the site home), or null.
     *
     * @param moodle_page $page The page.
     * @return stdClass|null
     */
    protected static function get_page_course(moodle_page $page): ?stdClass {
        $course = $page->course;
        return ($course && (int) $course->id !== (int) SITEID) ? $course : null;
    }

    /**
     * Add the pictogram data and script to a course page. Nothing is added when there are none.
     *
     * @param moodle_page $page The page.
     * @return string HTML holding the data for theme_chinijo/pictograms.
     */
    public static function render_page_data(moodle_page $page): string {
        $course = self::get_page_course($page);
        $items = $course ? self::get_render_data($course) : [];
        if (!$items) {
            return '';
        }
        $page->requires->js_call_amd('theme_chinijo/pictograms', 'init');
        return \html_writer::div('', 'theme-chinijo-pictogram-data', [
            'hidden' => 'hidden',
            'data-region' => 'theme_chinijo-pictograms',
            'data-pictograms' => json_encode($items),
        ]);
    }

    /**
     * Render the attribution of the pictograms of a course page.
     *
     * @param renderer_base $output Renderer.
     * @param moodle_page $page The page.
     * @return string HTML, or an empty string when there is nothing to credit.
     */
    public static function render_credits(renderer_base $output, moodle_page $page): string {
        $course = self::get_page_course($page);
        $credits = $course ? self::get_credits($course) : [];
        if (!$credits) {
            return '';
        }
        return $output->render_from_template('theme_chinijo/pictogram_credits', ['credits' => $credits]);
    }

    /**
     * Send a pictogram image, after checking that the current user may see it.
     *
     * @param stdClass $course The course.
     * @param context_course $context The course context.
     * @param array $args Path arguments: record id, then the file path and name.
     * @param bool $forcedownload Whether to force a download.
     * @param array $options Additional send_file options.
     */
    public static function serve_file(
        stdClass $course,
        context_course $context,
        array $args,
        bool $forcedownload,
        array $options
    ): void {
        global $DB;

        require_course_login($course, true, null, false, true);

        $recordid = (int) array_shift($args);
        $record = $DB->get_record(pictograms::TABLE, ['id' => $recordid, 'courseid' => $course->id]);
        if (!$record || !self::is_visible_to_user($record, $course)) {
            send_file_not_found();
        }
        $filename = array_pop($args);
        $filepath = $args ? '/' . implode('/', $args) . '/' : '/';
        $file = get_file_storage()->get_file(
            $context->id,
            pictograms::COMPONENT,
            pictograms::FILEAREA,
            $record->id,
            $filepath,
            $filename
        );
        if (!$file || $file->is_directory()) {
            send_file_not_found();
        }
        send_stored_file($file, DAYSECS, 0, $forcedownload, $options);
    }
}
