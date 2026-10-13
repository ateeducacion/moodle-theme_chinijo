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
 * Data generator for theme_chinijo.
 *
 * @package    theme_chinijo
 * @category   test
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_chinijo_generator extends component_generator_base {
    /**
     * Create a pictogram through the theme API, as an administrator.
     *
     * @param array $record courseid, itemtype (section or cm), itemid, alttext, and optionally author,
     *                      license and filepath (relative to the theme's tests/fixtures directory).
     * @return stdClass The pictogram record.
     */
    public function create_pictogram(array $record): stdClass {
        global $USER;

        $course = get_course($record['courseid']);
        $filename = $record['filepath'] ?? 'pictogram-book.png';
        $content = file_get_contents(__DIR__ . '/../fixtures/' . basename($filename));

        $previoususer = $USER;
        \core\session\manager::set_user(get_admin());
        try {
            $draftitemid = file_get_unused_draft_itemid();
            get_file_storage()->create_file_from_string([
                'contextid' => \context_user::instance($USER->id)->id,
                'component' => 'user',
                'filearea' => 'draft',
                'itemid' => $draftitemid,
                'filepath' => '/',
                'filename' => basename($filename),
            ], $content);
            return \theme_chinijo\local\pictograms::save($course, $record['itemtype'], (int) $record['itemid'], (object) [
                'pictogram' => $draftitemid,
                'alttext' => $record['alttext'],
                'author' => $record['author'] ?? '',
                'license' => $record['license'] ?? '',
            ]);
        } finally {
            \core\session\manager::set_user($previoususer);
        }
    }
}
