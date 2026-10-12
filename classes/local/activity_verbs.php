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

/**
 * What a learner does in each kind of activity, as one everyday verb ("Read", "Hand in").
 *
 * Young readers recognise an action word sooner than a module name such as
 * "Assignment" or "Page". Activity types that are not listed get no verb and
 * keep Moodle's own name.
 *
 * @package    theme_chinijo
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_verbs {
    /** @var array Verb group => module names. */
    public const GROUPS = [
        'read' => ['page', 'book', 'resource', 'url', 'folder', 'imscp', 'glossary', 'wiki'],
        'handin' => ['assign', 'workshop'],
        'answer' => ['quiz', 'lesson', 'feedback', 'survey', 'questionnaire'],
        'talk' => ['forum', 'chat', 'bigbluebuttonbn'],
        'choose' => ['choice'],
        'practise' => ['h5pactivity', 'hvp', 'scorm'],
    ];

    /**
     * The verb group of a kind of activity.
     *
     * @param string $modname Module name, for example "assign".
     * @return string|null A key of GROUPS, or null when the activity has no verb.
     */
    public static function get_group(string $modname): ?string {
        foreach (self::GROUPS as $group => $modnames) {
            if (in_array($modname, $modnames, true)) {
                return $group;
            }
        }
        return null;
    }

    /**
     * The verb of a kind of activity, in the user's language.
     *
     * @param string $modname Module name.
     * @return string|null The verb, or null when the activity has no verb.
     */
    public static function get_label(string $modname): ?string {
        $group = self::get_group($modname);
        return $group === null ? null : get_string('verb_' . $group, 'theme_chinijo');
    }

    /**
     * The verbs of some kinds of activity, for the course page script.
     *
     * @param string[] $modnames Module names, for example the ones used in a course.
     * @return string[] Module name => verb, only for the activities that have one.
     */
    public static function get_labels(array $modnames): array {
        $labels = [];
        foreach ($modnames as $modname) {
            $label = self::get_label($modname);
            if ($label !== null) {
                $labels[$modname] = $label;
            }
        }
        return $labels;
    }
}
