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

namespace theme_chinijo\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\writer;
use theme_chinijo\local\preferences;

/**
 * Privacy provider for the Chinijo theme.
 *
 * The theme only stores the display preferences that each user chooses for
 * themself, as ordinary Moodle user preferences. The pictogram table holds no
 * personal data (it does not record who uploaded a pictogram).
 *
 * @package    theme_chinijo
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\user_preference_provider {
    /**
     * Describe the user preferences stored by the theme.
     *
     * @param collection $collection The collection to add metadata to.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        foreach (preferences::get_names() as $name) {
            $collection->add_user_preference(
                preferences::get_preference_name($name),
                'privacy:metadata:preference:' . $name
            );
        }
        return $collection;
    }

    /**
     * Export the display preferences of a user.
     *
     * @param int $userid The user id.
     */
    public static function export_user_preferences(int $userid) {
        foreach (preferences::get_names() as $name) {
            $prefname = preferences::get_preference_name($name);
            $value = get_user_preferences($prefname, null, $userid);
            if ($value === null || !preferences::is_valid($name, $value)) {
                continue;
            }
            writer::export_user_preference(
                'theme_chinijo',
                $prefname,
                $value,
                get_string('privacy:export:preference', 'theme_chinijo', (object) [
                    'name' => get_string('pref_' . $name, 'theme_chinijo'),
                    'value' => get_string('pref_' . $name . '_' . $value, 'theme_chinijo'),
                ])
            );
        }
    }
}
