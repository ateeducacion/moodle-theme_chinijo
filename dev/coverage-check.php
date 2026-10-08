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
 * Development only: report line coverage from a Clover file and enforce a minimum.
 *
 * Usage: php dev/coverage-check.php coverage.xml [minimum percentage]. Exits with 1 below the minimum.
 *
 * @package    theme_chinijo
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// A stand-alone command-line tool: it does not load Moodle.
// phpcs:disable moodle.Files.MoodleInternal.MoodleInternalGlobalState

$file = $argv[1] ?? 'coverage.xml';
$minimum = (float) ($argv[2] ?? 80);

$xml = @simplexml_load_file($file);
if ($xml === false) {
    fwrite(STDERR, "Cannot read $file\n");
    exit(1);
}

$total = 0;
$covered = 0;
$rows = [];
foreach ($xml->xpath('//file') as $node) {
    $statements = 0;
    $hit = 0;
    foreach ($node->line as $line) {
        if ((string) $line['type'] === 'stmt') {
            $statements++;
            if ((int) $line['count'] > 0) {
                $hit++;
            }
        }
    }
    $total += $statements;
    $covered += $hit;
    $rows[] = sprintf(
        '  %6.2f%%  %4d/%-4d  %s',
        $statements ? 100 * $hit / $statements : 100,
        $hit,
        $statements,
        (string) $node['name']
    );
}

$percentage = $total ? 100 * $covered / $total : 0;
echo "Line coverage of the theme's PHP logic (classes/ and lib.php):\n" . implode("\n", $rows) . "\n";
printf("Total: %.2f%% (%d of %d executable lines). Minimum: %.2f%%\n", $percentage, $covered, $total, $minimum);
exit($percentage + 1e-9 < $minimum ? 1 : 0);
