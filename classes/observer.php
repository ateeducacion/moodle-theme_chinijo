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

use theme_chinijo\local\pictograms;

/**
 * Event observers that remove pictograms of deleted courses, sections and activities.
 *
 * @package    theme_chinijo
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * An activity or resource was deleted.
     *
     * @param \core\event\course_module_deleted $event The event.
     */
    public static function course_module_deleted(\core\event\course_module_deleted $event): void {
        pictograms::delete_for_item((int) $event->courseid, pictograms::TYPE_CM, (int) $event->objectid);
    }

    /**
     * A course section was deleted.
     *
     * @param \core\event\course_section_deleted $event The event.
     */
    public static function course_section_deleted(\core\event\course_section_deleted $event): void {
        pictograms::delete_for_item((int) $event->courseid, pictograms::TYPE_SECTION, (int) $event->objectid);
    }

    /**
     * A course was deleted.
     *
     * @param \core\event\course_deleted $event The event.
     */
    public static function course_deleted(\core\event\course_deleted $event): void {
        pictograms::delete_for_course((int) $event->objectid);
    }
}
