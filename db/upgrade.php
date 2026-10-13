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
 * Upgrade steps for the Chinijo theme.
 *
 * @package    theme_chinijo
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade the theme from an older version.
 *
 * @param int $oldversion The version installed before this upgrade.
 * @return bool
 */
function xmldb_theme_chinijo_upgrade($oldversion): bool {
    require_once(__DIR__ . '/upgradelib.php');

    if ($oldversion < 2026101300) {
        // The EU funding (FEDER) notice was removed: Chinijo is ATE's own development, not FEDER co-funded.
        theme_chinijo_remove_feder_settings();
        upgrade_plugin_savepoint(true, 2026101300, 'theme', 'chinijo');
    }

    return true;
}
