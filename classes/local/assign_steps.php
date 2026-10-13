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
use context_module;
use stdClass;

/**
 * The three steps of an assignment for a learner: read it, do the work and hand it in.
 *
 * The state comes only from the learner's submission as Moodle records it, so
 * the steps never claim more than the submission status table below them.
 *
 * @package    theme_chinijo
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_steps {
    /** @var string[] The steps, in order. */
    public const STEPS = ['read', 'do', 'handin'];

    /** @var string Nothing handed in yet (no submission, a new one or a reopened one). */
    public const STATUS_NONE = 'none';

    /** @var string Work saved as a draft, not handed in yet. */
    public const STATUS_DRAFT = 'draft';

    /** @var string Work handed in. */
    public const STATUS_SUBMITTED = 'submitted';

    /**
     * The steps of an assignment for a user who can hand it in.
     *
     * @param stdClass $course The course.
     * @param cm_info $cm The assignment.
     * @param int $userid The user id.
     * @return array|null Null when the user cannot hand anything in. Otherwise cmid, status
     *         (one of the STATUS_ constants) and steps (key, number and state of each one).
     */
    public static function get(stdClass $course, cm_info $cm, int $userid): ?array {
        global $CFG;
        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        if ($cm->modname !== 'assign') {
            return null;
        }
        $context = context_module::instance($cm->id);
        if (!has_capability('mod/assign:submit', $context, $userid)) {
            return null;
        }
        $assign = new \assign($context, $cm, $course);
        if (!$assign->is_any_submission_plugin_enabled()) {
            // Nothing to hand in online, for example a task done in the classroom.
            return null;
        }

        if ($assign->get_instance($userid)->teamsubmission) {
            $submission = $assign->get_group_submission($userid, 0, false);
        } else {
            $submission = $assign->get_user_submission($userid, false);
        }
        $status = self::get_status($submission ? (string) $submission->status : null);

        return [
            'cmid' => (int) $cm->id,
            'status' => $status,
            'steps' => self::get_steps($status),
        ];
    }

    /**
     * The learner's status from the status of their submission.
     *
     * @param string|null $submissionstatus Submission status, or null when there is no submission.
     * @return string One of the STATUS_ constants.
     */
    public static function get_status(?string $submissionstatus): string {
        switch ($submissionstatus) {
            case ASSIGN_SUBMISSION_STATUS_SUBMITTED:
                return self::STATUS_SUBMITTED;
            case ASSIGN_SUBMISSION_STATUS_DRAFT:
                return self::STATUS_DRAFT;
            default:
                return self::STATUS_NONE;
        }
    }

    /**
     * The state of each step for a status.
     *
     * @param string $status One of the STATUS_ constants.
     * @return array[] Each with key, number and state (one of learning_path's done, current and todo).
     */
    public static function get_steps(string $status): array {
        $current = [
            self::STATUS_NONE => 0,
            self::STATUS_DRAFT => 2,
            self::STATUS_SUBMITTED => count(self::STEPS),
        ][$status];

        $steps = [];
        foreach (self::STEPS as $index => $key) {
            if ($index < $current) {
                $state = learning_path::STATE_DONE;
            } else {
                $state = $index === $current ? learning_path::STATE_CURRENT : learning_path::STATE_TODO;
            }
            $steps[] = ['key' => $key, 'number' => $index + 1, 'state' => $state];
        }
        return $steps;
    }
}
