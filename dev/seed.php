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
 * Development only: create the synthetic demo site for Chinijo (make seed).
 *
 * Usage, inside the Moodle container: php theme/chinijo/dev/seed.php
 * (public/theme/chinijo/dev/seed.php from Moodle 5.1).
 *
 * @package    theme_chinijo
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

// The theme lives in theme/chinijo, under the web root (public/ from Moodle 5.1).
require(dirname(__DIR__, 3) . '/config.php');
require_once(__DIR__ . '/seedlib.php');

chinijo_seed_spanish();
chinijo_seed_run();
