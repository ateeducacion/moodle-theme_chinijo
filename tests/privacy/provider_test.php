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
 * Tests for the privacy provider.
 *
 * @package    theme_chinijo
 * @category   test
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_chinijo\privacy\provider
 */
#[\PHPUnit\Framework\Attributes\CoversClass(provider::class)]
final class provider_test extends \core_privacy\tests\provider_testcase {
    /**
     * Every stored preference is described, and nothing else.
     */
    public function test_get_metadata(): void {
        $items = provider::get_metadata(new collection('theme_chinijo'))->get_collection();
        $this->assertCount(count(preferences::CHOICES), $items);
        foreach ($items as $item) {
            $this->assertInstanceOf(\core_privacy\local\metadata\types\user_preference::class, $item);
            $this->assertStringStartsWith('theme_chinijo_', $item->get_name());
        }
    }

    /**
     * Only the user's own valid choices are exported, with readable descriptions.
     */
    public function test_export_user_preferences(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();

        $this->setUser($other);
        preferences::set('contrast', 'high');

        $this->setUser($user);
        preferences::set_many(['fontsize' => 'large', 'motion' => 'reduce']);
        set_user_preference('theme_chinijo_font', 'not-a-choice');

        provider::export_user_preferences((int) $user->id);
        $exported = writer::with_context(\context_system::instance())->get_user_preferences('theme_chinijo');

        $this->assertSame('large', $exported->theme_chinijo_fontsize->value);
        $this->assertStringContainsString(
            get_string('pref_fontsize_large', 'theme_chinijo'),
            $exported->theme_chinijo_fontsize->description
        );
        $this->assertSame('reduce', $exported->theme_chinijo_motion->value);
        $this->assertObjectNotHasProperty('theme_chinijo_font', $exported);
        $this->assertObjectNotHasProperty('theme_chinijo_contrast', $exported);
    }
}
