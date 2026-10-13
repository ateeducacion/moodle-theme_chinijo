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
 * Helpers for the upgrade steps of the Chinijo theme.
 *
 * @package    theme_chinijo
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Delete the settings and the emblem file of the EU funding (FEDER) notice, removed in version 0.3.1.
 */
function theme_chinijo_remove_feder_settings(): void {
    foreach (['federenabled', 'federplacement', 'federlogo', 'federlogoalt', 'federtext'] as $name) {
        unset_config($name, 'theme_chinijo');
    }
    get_file_storage()->delete_area_files(context_system::instance()->id, 'theme_chinijo', 'federlogo');
}
