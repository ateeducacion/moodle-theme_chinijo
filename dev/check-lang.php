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
 * Development only: check that the English and Spanish language packs have the same keys and placeholders.
 *
 * Usage: php dev/check-lang.php [plugin directory]. Exits with 1 when they differ.
 *
 * @package    theme_chinijo
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// A stand-alone command-line tool: it does not load Moodle.
// phpcs:disable moodle.Files.MoodleInternal.MoodleInternalGlobalState

$root = $argv[1] ?? dirname(__DIR__);

/**
 * Load a language file without Moodle.
 *
 * @param string $file Path.
 * @return array
 */
function chinijo_load_strings(string $file): array {
    $string = [];
    include($file);
    return $string;
}

/**
 * Placeholders of a string.
 *
 * @param string $text String.
 * @return array
 */
function chinijo_placeholders(string $text): array {
    preg_match_all('/\{\$a(->[a-z0-9_]+)?\}/i', $text, $matches);
    $found = array_unique($matches[0]);
    sort($found);
    return $found;
}

$en = chinijo_load_strings("$root/lang/en/theme_chinijo.php");
$es = chinijo_load_strings("$root/lang/es/theme_chinijo.php");

$errors = [];
foreach (array_diff_key($en, $es) as $key => $unused) {
    $errors[] = "Missing in es: $key";
}
foreach (array_diff_key($es, $en) as $key => $unused) {
    $errors[] = "Missing in en: $key";
}
foreach (array_intersect_key($en, $es) as $key => $value) {
    if (chinijo_placeholders($value) !== chinijo_placeholders($es[$key])) {
        $errors[] = "Different placeholders: $key";
    }
    if (trim($es[$key]) === '' || trim($value) === '') {
        $errors[] = "Empty string: $key";
    }
}

if ($errors) {
    fwrite(STDERR, implode("\n", $errors) . "\n");
    exit(1);
}
echo count($en) . " strings, same keys and placeholders in en and es.\n";
