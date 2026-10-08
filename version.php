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
 * Version metadata for the Chinijo theme.
 *
 * @package    theme_chinijo
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'theme_chinijo';
$plugin->version   = 2026100801;
$plugin->release   = '0.2.0';
$plugin->maturity  = MATURITY_ALPHA;
// Moodle 4.5.0 (Build: 20241007), verified against tag v4.5.0 of moodle/moodle.
$plugin->requires  = 2024100700;
// Moodle 4.5 LTS up to and including Moodle 5.3 LTS.
$plugin->supported = [405, 503];
$plugin->dependencies = [
    'theme_boost' => 2024100700,
];
