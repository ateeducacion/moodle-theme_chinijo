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

namespace theme_chinijo;

#[\PHPUnit\Framework\Attributes\CoversFunction('theme_chinijo_remove_feder_settings')]
/**
 * Tests for the upgrade helpers.
 *
 * @package    theme_chinijo
 * @category   test
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     ::theme_chinijo_remove_feder_settings
 */
final class upgradelib_test extends \advanced_testcase {
    /**
     * The settings and the emblem of the removed EU funding notice are deleted; other settings stay.
     */
    public function test_remove_feder_settings(): void {
        global $CFG;
        require_once($CFG->dirroot . '/theme/chinijo/db/upgradelib.php');
        $this->resetAfterTest();

        $names = ['federenabled', 'federplacement', 'federlogo', 'federlogoalt', 'federtext'];
        foreach ($names as $name) {
            set_config($name, 'x', 'theme_chinijo');
        }
        set_config('brandcolor', '#0f6cbf', 'theme_chinijo');
        $system = \context_system::instance();
        get_file_storage()->create_file_from_string([
            'contextid' => $system->id,
            'component' => 'theme_chinijo',
            'filearea' => 'federlogo',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'emblem.png',
        ], 'emblem');

        theme_chinijo_remove_feder_settings();

        foreach ($names as $name) {
            $this->assertFalse(get_config('theme_chinijo', $name), $name);
        }
        $this->assertSame('#0f6cbf', get_config('theme_chinijo', 'brandcolor'));
        $this->assertTrue(get_file_storage()->is_area_empty($system->id, 'theme_chinijo', 'federlogo'));
    }
}
