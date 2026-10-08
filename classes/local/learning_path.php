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

use cm_info;
use completion_info;
use course_modinfo;
use moodle_url;
use stdClass;

/**
 * A learner's path through a course: the activities that count towards their
 * progress, in the order of the course page, with what is done and what comes next.
 *
 * The figures are the ones of course_progress, so they always match Moodle's
 * own progress. Activities the learner cannot see on the course page are left
 * out of the path; they are never named.
 *
 * The path drawn on the page is the one of the current section, the section of
 * the next activity: in Canarian Primary courses each section is usually a
 * learning situation (situación de aprendizaje), and an area course holds
 * several of them over the year (see docs/curriculum-canarias.md).
 *
 * @package    theme_chinijo
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class learning_path {
    /** @var int Largest number of activities drawn on the path at once. */
    public const WINDOW = 7;

    /** @var string State of an activity the learner has completed. */
    public const STATE_DONE = 'done';

    /** @var string State of the next activity to do. */
    public const STATE_CURRENT = 'current';

    /** @var string State of an activity still to do. */
    public const STATE_TODO = 'todo';

    /** @var string State of an activity shown on the course page that the learner cannot open yet. */
    public const STATE_LOCKED = 'locked';

    /**
     * The learner's path through a course.
     *
     * @param stdClass $course The course.
     * @param int $userid The user id.
     * @return array|null Null when course_progress has nothing to show. Otherwise the course_progress
     *         figures plus items (every activity on the path), next (the next activity to do, or null),
     *         section (the current section: number, name, url, pictogramurl, completed and total, or null)
     *         and window, before and after (the part of the current section to draw and how many
     *         of its activities it leaves out).
     */
    public static function get(stdClass $course, int $userid): ?array {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');

        $progress = course_progress::get($course, $userid);
        if ($progress === null) {
            return null;
        }

        $completion = new completion_info($course);
        $counted = array_flip($progress['cmids']);
        $pictograms = self::get_pictograms($course);
        $modinfo = get_fast_modinfo($course, $userid);
        $items = [];
        foreach (self::get_ordered_cms($modinfo) as [$cm, $sectionnum]) {
            if (isset($counted[$cm->id]) && $cm->is_visible_on_course_page()) {
                $items[] = self::build_item($cm, $completion, $userid, $pictograms[$cm->id] ?? null) + [
                    'section' => $sectionnum,
                ];
            }
        }

        $nextindex = null;
        foreach ($items as $index => $item) {
            if ($item['state'] === self::STATE_TODO) {
                $items[$index]['state'] = self::STATE_CURRENT;
                $nextindex = $index;
                break;
            }
        }

        // The current section is the one of the next activity, or the last one when everything is done.
        $current = $items[$nextindex ?? array_key_last($items) ?? 0] ?? null;
        $sectionitems = [];
        $sectionnext = null;
        foreach ($items as $index => $item) {
            if ($current !== null && $item['section'] === $current['section']) {
                if ($index === $nextindex) {
                    $sectionnext = count($sectionitems);
                }
                $sectionitems[] = $item;
            }
        }

        return $progress + [
            'items' => $items,
            'next' => $nextindex === null ? null : $items[$nextindex],
            'section' => $current === null ? null : self::get_section(
                $course,
                $modinfo,
                $current['section'],
                $sectionitems,
                $pictograms
            ),
        ] + self::get_window($sectionitems, $sectionnext);
    }

    /**
     * Template data of the current section.
     *
     * @param stdClass $course The course.
     * @param course_modinfo $modinfo Course information for the user.
     * @param int $sectionnum Section number.
     * @param array[] $items Activities of the path in this section.
     * @param array[] $pictograms Pictograms of the course, as returned by get_pictograms().
     * @return array number, name, url, pictogramurl, completed and total.
     */
    protected static function get_section(
        stdClass $course,
        course_modinfo $modinfo,
        int $sectionnum,
        array $items,
        array $pictograms
    ): array {
        global $CFG;
        require_once($CFG->dirroot . '/course/lib.php');

        $section = $modinfo->get_section_info($sectionnum);
        return [
            'number' => $sectionnum,
            'name' => course_get_format($course)->get_section_name($section),
            'url' => (new moodle_url('/course/view.php', ['id' => $course->id], 'section-' . $sectionnum))->out(false),
            'pictogramurl' => $pictograms['section:' . $section->id]['url'] ?? '',
            'completed' => count(array_filter($items, fn(array $item): bool => $item['state'] === self::STATE_DONE)),
            'total' => count($items),
        ];
    }

    /**
     * Course modules in the order of the course page, each with the number of its top-level section.
     * The activities of a subsection come after the subsection itself and belong to its parent section.
     *
     * @param course_modinfo $modinfo Course information for the user.
     * @return array[] Each a course module (cm_info) and a section number.
     */
    protected static function get_ordered_cms(course_modinfo $modinfo): array {
        $ordered = [];
        $sections = $modinfo->get_sections();
        $add = function (int $sectionnum, int $topsection) use (&$add, &$ordered, $modinfo, $sections): void {
            foreach ($sections[$sectionnum] ?? [] as $cmid) {
                $cm = $modinfo->get_cm($cmid);
                $ordered[] = [$cm, $topsection];
                // Subsections (Moodle 4.5 and later) keep their activities in a delegated section.
                $delegated = method_exists($cm, 'get_delegated_section_info') ? $cm->get_delegated_section_info() : null;
                if ($delegated) {
                    $add((int) $delegated->section, $topsection);
                }
            }
        };
        foreach ($modinfo->get_section_info_all() as $section) {
            if (!method_exists($section, 'is_delegated') || !$section->is_delegated()) {
                $add((int) $section->section, (int) $section->section);
            }
        }
        return $ordered;
    }

    /**
     * Template data of one activity on the path.
     *
     * @param cm_info $cm The course module.
     * @param completion_info $completion Completion information of the course.
     * @param int $userid The user id.
     * @param array|null $pictogram The activity's pictogram (url and alt), if any.
     * @return array
     */
    protected static function build_item(cm_info $cm, completion_info $completion, int $userid, ?array $pictogram): array {
        $data = $completion->get_data($cm, false, $userid);
        $done = in_array((int) $data->completionstate, [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS], true);
        $canopen = $cm->uservisible && $cm->url;
        if ($done) {
            $state = self::STATE_DONE;
        } else {
            $state = $canopen ? self::STATE_TODO : self::STATE_LOCKED;
        }

        return [
            'cmid' => (int) $cm->id,
            'name' => $cm->get_formatted_name(),
            'modname' => $cm->modfullname,
            'url' => $canopen ? $cm->url->out(false) : '',
            'iconurl' => $cm->get_icon_url()->out(false),
            'pictogramurl' => $pictogram['url'] ?? '',
            'pictogramalt' => $pictogram['alt'] ?? '',
            'state' => $state,
        ];
    }

    /**
     * Pictograms of the course that the current user may see: activities by course module id,
     * sections by "section:" and the section id.
     *
     * @param stdClass $course The course.
     * @return array[] Key => url and alt.
     */
    protected static function get_pictograms(stdClass $course): array {
        $pictograms = [];
        foreach (pictogram_display::get_render_data($course) as $pictogram) {
            $key = $pictogram['type'] === pictograms::TYPE_CM ? $pictogram['id'] : 'section:' . $pictogram['id'];
            $pictograms[$key] = $pictogram;
        }
        return $pictograms;
    }

    /**
     * The part of the path drawn on the page: up to WINDOW activities, starting two before the next one.
     *
     * @param array[] $items Every activity on the path.
     * @param int|null $nextindex Position of the next activity, or null when everything is done.
     * @return array window (the items to draw), before and after (how many are left out on each side).
     */
    public static function get_window(array $items, ?int $nextindex): array {
        $count = count($items);
        $focus = $nextindex ?? $count - 1;
        $start = max(0, min($focus - 2, $count - self::WINDOW));
        $window = array_slice($items, $start, self::WINDOW);

        return [
            'window' => $window,
            'before' => $start,
            'after' => $count - $start - count($window),
        ];
    }
}
