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

use completion_info;
use stdClass;

/**
 * Course progress of a user, taken only from Moodle's own completion data.
 *
 * The counts reproduce \core_completion\progress::get_course_progress_percentage()
 * on each supported branch: Moodle 4.5 counts every activity with completion
 * enabled, while later branches count only the activities the user can see
 * (completion_info::get_user_activities_with_completion()). Nothing is shown
 * when completion is disabled or the user is not tracked, and no figure is
 * ever estimated.
 *
 * @package    theme_chinijo
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_progress {
    /**
     * Progress of a user in a course.
     *
     * @param stdClass $course The course.
     * @param int $userid The user id.
     * @return array|null Null when there is no progress to show; otherwise completed, total,
     *         percentage (integer, rounded down), coursecomplete and the counted course module ids.
     */
    public static function get(stdClass $course, int $userid): ?array {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');

        $completion = new completion_info($course);
        if (!$completion->is_enabled() || !$completion->is_tracked_user($userid)) {
            return null;
        }

        if (method_exists($completion, 'get_user_activities_with_completion')) {
            $activities = $completion->get_user_activities_with_completion($userid);
            $total = count($activities);
            $completed = $total ? $completion->count_modules_completed($userid, array_keys($activities)) : 0;
        } else {
            $activities = $completion->get_activities();
            $total = count($activities);
            $completed = $total ? $completion->count_modules_completed($userid) : 0;
        }

        $coursecomplete = $completion->is_course_complete($userid);
        if ($total === 0 && !$coursecomplete) {
            return null;
        }
        $completed = min($completed, $total);
        $percentage = $coursecomplete ? 100 : (int) floor(($completed / $total) * 100);

        return [
            'completed' => $completed,
            'total' => $total,
            'percentage' => $percentage,
            'coursecomplete' => $coursecomplete,
            'cmids' => array_map('intval', array_keys($activities)),
        ];
    }
}
