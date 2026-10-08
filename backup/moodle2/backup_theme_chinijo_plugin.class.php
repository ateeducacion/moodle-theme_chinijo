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
 * Course backup of the pictograms of theme_chinijo.
 *
 * @package    theme_chinijo
 * @category   backup
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Adds the course's pictograms (records and image files) to course backups, imports and duplicates.
 *
 * Pictograms are saved whatever the course theme is, because a course usually
 * inherits the site theme.
 *
 * @package    theme_chinijo
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_theme_chinijo_plugin extends backup_theme_plugin {
    /**
     * Define the structure attached to the course element.
     *
     * @return backup_plugin_element
     */
    protected function define_course_plugin_structure() {
        $plugin = $this->get_plugin_element();
        $wrapper = new backup_nested_element($this->get_recommended_name());
        $plugin->add_child($wrapper);

        $pictograms = new backup_nested_element('pictograms');
        $pictogram = new backup_nested_element('pictogram', ['id'], [
            'itemtype', 'itemid', 'alttext', 'author', 'license', 'timecreated', 'timemodified',
        ]);
        $wrapper->add_child($pictograms);
        $pictograms->add_child($pictogram);

        $pictogram->set_source_table('theme_chinijo_pictogram', ['courseid' => backup::VAR_COURSEID]);
        $pictogram->annotate_files('theme_chinijo', 'pictogram', 'id');

        return $plugin;
    }
}
