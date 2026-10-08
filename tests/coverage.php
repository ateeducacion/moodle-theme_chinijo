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
 * Code coverage settings for theme_chinijo, used by Moodle's PHPUnit configuration.
 *
 * Coverage is measured on the theme's own PHP logic: the autoloaded classes, the
 * backup and restore classes and the lib.php callbacks. Page scripts, settings, language files and templates
 * are exercised by Behat instead and are not part of the PHP line coverage.
 *
 * @package    theme_chinijo
 * @category   test
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Coverage information for theme_chinijo.
 *
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
return new class extends phpunit_coverage_info {
    /** @var array Folders to include. */
    protected $includelistfolders = ['classes', 'backup'];

    /** @var array Files to include. */
    protected $includelistfiles = ['lib.php'];

    /** @var array Folders to exclude. */
    protected $excludelistfolders = [];

    /**
     * @var array Files to exclude. Moodle includes tests/generator by default; these are test-only data
     *            generators (the Behat one never runs under PHPUnit), not part of the theme.
     */
    protected $excludelistfiles = [
        'tests/generator/lib.php',
        'tests/generator/behat_theme_chinijo_generator.php',
    ];
};
