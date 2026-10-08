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
 * Course restore of the pictograms of theme_chinijo.
 *
 * @package    theme_chinijo
 * @category   backup
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restores the pictograms of a course backup onto the restored sections and activities.
 *
 * The course element is restored before its sections and activities, so the
 * pictograms are kept until the whole course has been restored and then mapped
 * to the new section and course module ids. Pictograms of items that were not
 * restored, or of items that already have one in the target course, are skipped.
 *
 * @package    theme_chinijo
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_theme_chinijo_plugin extends restore_theme_plugin {
    /** @var stdClass[] Pictograms read from the backup, waiting for the course structure. */
    protected $pictograms = [];

    /**
     * Paths handled by the plugin at course level.
     *
     * @return restore_path_element[]
     */
    protected function define_course_plugin_structure() {
        return [new restore_path_element('theme_chinijo_pictogram', $this->get_pathfor('/pictograms/pictogram'))];
    }

    /**
     * Keep a pictogram until the sections and activities exist.
     *
     * @param array $data Pictogram data from the backup.
     */
    public function process_theme_chinijo_pictogram($data) {
        $this->pictograms[] = (object) $data;
    }

    /**
     * Create the pictograms for the restored sections and activities, then restore their files.
     */
    public function after_restore_course() {
        global $DB;

        $courseid = $this->task->get_courseid();
        foreach ($this->pictograms as $data) {
            $mapping = $data->itemtype === 'cm' ? 'course_module' : 'course_section';
            $newitemid = $this->get_mappingid($mapping, $data->itemid);
            if (!$newitemid || !in_array($data->itemtype, ['cm', 'section'], true)) {
                continue;
            }
            $conditions = ['courseid' => $courseid, 'itemtype' => $data->itemtype, 'itemid' => $newitemid];
            if ($DB->record_exists('theme_chinijo_pictogram', $conditions)) {
                continue;
            }
            $record = (object) ($conditions + [
                'alttext' => $data->alttext,
                'author' => $data->author ?? null,
                'license' => $data->license ?? null,
                'timecreated' => $data->timecreated,
                'timemodified' => $data->timemodified,
            ]);
            $newid = $DB->insert_record('theme_chinijo_pictogram', $record);
            $this->set_mapping('theme_chinijo_pictogram', $data->id, $newid, true);
        }
        $this->add_related_files('theme_chinijo', 'pictogram', 'theme_chinijo_pictogram');
    }
}
