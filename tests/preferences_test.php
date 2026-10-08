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

use core_user;
use invalid_parameter_exception;
use theme_chinijo\local\preferences;

/**
 * Tests for the personal display preferences.
 *
 * @package    theme_chinijo
 * @category   test
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_chinijo\local\preferences
 */
#[\PHPUnit\Framework\Attributes\CoversClass(preferences::class)]
final class preferences_test extends \advanced_testcase {
    /**
     * Every preference offers a default as its first choice, and the text size offers at least three sizes.
     */
    public function test_choices(): void {
        foreach (preferences::CHOICES as $name => $choices) {
            $this->assertSame(preferences::DEFAULT, $choices[0], $name);
            $this->assertSame(array_values(array_unique($choices)), $choices, $name);
        }
        $this->assertGreaterThanOrEqual(3, count(preferences::get_choices('fontsize')));
        $this->assertContains('high', preferences::get_choices('contrast'));
        $this->assertContains('reduce', preferences::get_choices('motion'));
        $this->assertSame('theme_chinijo_fontsize', preferences::get_preference_name('fontsize'));
    }

    /**
     * Unknown preferences have no choices.
     */
    public function test_get_choices_unknown(): void {
        $this->expectException(invalid_parameter_exception::class);
        preferences::get_choices('colour');
    }

    /**
     * Validation accepts only the listed values, with exact types.
     */
    public function test_is_valid(): void {
        $this->assertTrue(preferences::is_valid('fontsize', 'large'));
        $this->assertFalse(preferences::is_valid('fontsize', 'LARGE'));
        $this->assertFalse(preferences::is_valid('fontsize', 'giant'));
        $this->assertFalse(preferences::is_valid('fontsize', ''));
        $this->assertFalse(preferences::is_valid('fontsize', null));
        $this->assertFalse(preferences::is_valid('fontsize', 1));
        $this->assertFalse(preferences::is_valid('unknown', 'default'));
        $this->assertFalse(preferences::is_valid('contrast', '"><script>alert(1)</script>'));
    }

    /**
     * A new user gets every default.
     */
    public function test_get_all_defaults(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());

        $values = preferences::get_all();
        $this->assertSame(preferences::get_names(), array_keys($values));
        foreach ($values as $value) {
            $this->assertSame(preferences::DEFAULT, $value);
        }
    }

    /**
     * Values are stored as user preferences, survive a reload and defaults are not stored.
     */
    public function test_set_persists(): void {
        global $DB, $USER;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        preferences::set('fontsize', 'xlarge');
        preferences::set('contrast', 'high');
        $this->assertSame('xlarge', $DB->get_field(
            'user_preferences',
            'value',
            ['userid' => $user->id, 'name' => 'theme_chinijo_fontsize']
        ));

        // Simulate a new request: preferences are read from the database again.
        unset($USER->preference);
        $values = preferences::get_all();
        $this->assertSame('xlarge', $values['fontsize']);
        $this->assertSame('high', $values['contrast']);

        preferences::set('fontsize', preferences::DEFAULT);
        $this->assertFalse($DB->record_exists('user_preferences', ['userid' => $user->id, 'name' => 'theme_chinijo_fontsize']));
        $this->assertSame(preferences::DEFAULT, preferences::get_all()['fontsize']);
    }

    /**
     * Invalid values are rejected and nothing is stored.
     */
    public function test_set_rejects_invalid_value(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        try {
            preferences::set('fontsize', 'giant');
            $this->fail('An invalid value was accepted.');
        } catch (invalid_parameter_exception $e) {
            $this->assertFalse($DB->record_exists('user_preferences', ['userid' => $user->id]));
        }
    }

    /**
     * Setting several values is all or nothing.
     */
    public function test_set_many_is_atomic(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        try {
            preferences::set_many(['fontsize' => 'large', 'contrast' => 'neon']);
            $this->fail('An invalid value was accepted.');
        } catch (invalid_parameter_exception $e) {
            $this->assertFalse($DB->record_exists_select(
                'user_preferences',
                'userid = ? AND name LIKE ?',
                [$user->id, 'theme_chinijo_%']
            ));
        }

        $this->expectException(invalid_parameter_exception::class);
        preferences::set_many(['notapreference' => 'default']);
    }

    /**
     * Valid sets of values are stored together.
     */
    public function test_set_many(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());

        preferences::set_many(['fontsize' => 'large', 'letterspacing' => 'wider', 'motion' => 'reduce']);
        $values = preferences::get_all();
        $this->assertSame('large', $values['fontsize']);
        $this->assertSame('wider', $values['letterspacing']);
        $this->assertSame('reduce', $values['motion']);
        $this->assertSame(preferences::DEFAULT, $values['contrast']);
    }

    /**
     * Reset brings every preference back to its default.
     */
    public function test_reset(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        preferences::set_many(['fontsize' => 'large', 'contrast' => 'high', 'font' => 'legible']);
        preferences::reset();
        $this->assertSame(array_fill_keys(preferences::get_names(), preferences::DEFAULT), preferences::get_all());
        $this->assertFalse($DB->record_exists_select(
            'user_preferences',
            'userid = ? AND name LIKE ?',
            [$user->id, 'theme_chinijo_%']
        ));
    }

    /**
     * One user's preferences never affect another user.
     */
    public function test_isolation_between_users(): void {
        $this->resetAfterTest();
        $alice = $this->getDataGenerator()->create_user();
        $bob = $this->getDataGenerator()->create_user();

        $this->setUser($alice);
        preferences::set('contrast', 'high');

        $this->setUser($bob);
        $this->assertSame(preferences::DEFAULT, preferences::get_all()['contrast']);
        preferences::set('contrast', preferences::DEFAULT);

        $this->setUser($alice);
        $this->assertSame('high', preferences::get_all()['contrast']);
    }

    /**
     * A stored value that is no longer valid can never reach the page.
     */
    public function test_invalid_stored_value_falls_back_to_default(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        set_user_preference('theme_chinijo_fontsize', '"><script>alert(1)</script>');
        $this->assertSame(preferences::DEFAULT, preferences::get_all()['fontsize']);
        $this->assertSame(
            preferences::DEFAULT,
            preferences::get_html_attributes(['fontsize' => 'bad'])['data-chinijo-fontsize']
        );
    }

    /**
     * Guests only keep their choices for the session: nothing reaches the database.
     */
    public function test_guest_is_session_only(): void {
        global $DB, $USER;
        $this->resetAfterTest();
        $this->setGuestUser();

        $this->assertFalse(preferences::can_persist());
        preferences::set('fontsize', 'large');
        $this->assertSame('large', preferences::get_all()['fontsize']);
        $this->assertSame('large', $USER->preference['theme_chinijo_fontsize']);
        $this->assertFalse($DB->record_exists('user_preferences', ['userid' => $USER->id, 'name' => 'theme_chinijo_fontsize']));
    }

    /**
     * Visitors who are not logged in also get session-only storage.
     */
    public function test_not_logged_in_is_session_only(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setUser(null);

        $this->assertFalse(preferences::can_persist());
        preferences::set('contrast', 'high');
        $this->assertSame('high', preferences::get_all()['contrast']);
        $this->assertFalse($DB->record_exists('user_preferences', ['name' => 'theme_chinijo_contrast']));
    }

    /**
     * The html attributes describe every preference.
     */
    public function test_get_html_attributes(): void {
        $attributes = preferences::get_html_attributes(['fontsize' => 'large', 'contrast' => 'high']);
        $this->assertCount(count(preferences::CHOICES), $attributes);
        $this->assertSame('large', $attributes['data-chinijo-fontsize']);
        $this->assertSame('high', $attributes['data-chinijo-contrast']);
        $this->assertSame(preferences::DEFAULT, $attributes['data-chinijo-motion']);
    }

    /**
     * Core's preference API validates the theme's preferences with the theme's definitions.
     */
    public function test_core_user_definitions(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $definition = core_user::get_preference_definition('theme_chinijo_fontsize');
        $this->assertSame(preferences::get_choices('fontsize'), $definition['choices']);
        $this->assertSame('large', core_user::clean_preference('large', 'theme_chinijo_fontsize'));
        // Core replaces values outside the list with the default.
        $this->assertSame(preferences::DEFAULT, core_user::clean_preference('giant', 'theme_chinijo_fontsize'));
        $this->assertTrue(core_user::can_edit_preference('theme_chinijo_fontsize', $user));
    }

    /**
     * Nobody can change somebody else's display preferences, not even an administrator or a teacher.
     */
    public function test_core_user_permissions(): void {
        $this->resetAfterTest();
        $student = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');

        $this->setUser($teacher);
        $this->assertFalse(core_user::can_edit_preference('theme_chinijo_contrast', $student));

        $this->setAdminUser();
        $this->assertFalse(core_user::can_edit_preference('theme_chinijo_contrast', $student));

        $this->setGuestUser();
        $this->assertFalse(core_user::can_edit_preference('theme_chinijo_contrast', $student));

        $this->setUser($student);
        $this->assertTrue(core_user::can_edit_preference('theme_chinijo_contrast', $student));
        $this->assertFalse(preferences::can_edit($teacher, 'theme_chinijo_contrast'));
    }
}
