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

use theme_chinijo\local\activity_verbs;

#[\PHPUnit\Framework\Attributes\CoversClass(activity_verbs::class)]
/**
 * Tests for the verbs shown on activity cards.
 *
 * @package    theme_chinijo
 * @category   test
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_chinijo\local\activity_verbs
 */
final class activity_verbs_test extends \advanced_testcase {
    /**
     * Common activities get the verb of what learners do in them; labels and unknown modules get none.
     */
    public function test_groups(): void {
        $this->assertSame('read', activity_verbs::get_group('page'));
        $this->assertSame('read', activity_verbs::get_group('book'));
        $this->assertSame('handin', activity_verbs::get_group('assign'));
        $this->assertSame('answer', activity_verbs::get_group('quiz'));
        $this->assertSame('talk', activity_verbs::get_group('forum'));
        $this->assertSame('choose', activity_verbs::get_group('choice'));
        $this->assertSame('practise', activity_verbs::get_group('h5pactivity'));
        $this->assertNull(activity_verbs::get_group('label'));
        $this->assertNull(activity_verbs::get_group('unknownmodule'));
    }

    /**
     * Every group has a string in both languages, and no module is in two groups.
     */
    public function test_strings_and_unique_modules(): void {
        $manager = get_string_manager();
        $seen = [];
        foreach (activity_verbs::GROUPS as $group => $modnames) {
            $this->assertTrue($manager->string_exists('verb_' . $group, 'theme_chinijo'), $group);
            $this->assertNotEmpty($manager->get_string('verb_' . $group, 'theme_chinijo', null, 'es'));
            foreach ($modnames as $modname) {
                $this->assertArrayNotHasKey($modname, $seen, $modname);
                $seen[$modname] = true;
            }
        }
    }

    /**
     * Labels are given for the activities that have a verb only.
     */
    public function test_labels(): void {
        $this->assertSame(get_string('verb_handin', 'theme_chinijo'), activity_verbs::get_label('assign'));
        $this->assertNull(activity_verbs::get_label('label'));
        $this->assertSame([
            'page' => get_string('verb_read', 'theme_chinijo'),
            'forum' => get_string('verb_talk', 'theme_chinijo'),
        ], activity_verbs::get_labels(['page', 'label', 'forum']));
        $this->assertSame([], activity_verbs::get_labels([]));
    }
}
