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

namespace theme_chinijo\local;

use core_user;
use invalid_parameter_exception;
use stdClass;

/**
 * Personal display preferences offered by the Chinijo preferences panel.
 *
 * Every preference is a closed list of display choices stored with Moodle's
 * user preference API. Values are only ever read for, and written to, the
 * current user; guests and visitors who are not logged in get session-only
 * storage, which is how set_user_preference() treats them natively. Nothing
 * here records or infers diagnoses or support needs: these are display choices
 * that anybody can make for themselves.
 *
 * @package    theme_chinijo
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class preferences {
    /** @var string Prefix of every user preference name stored by the theme. */
    public const PREFIX = 'theme_chinijo_';

    /** @var string Value meaning "use the theme default" for every preference. */
    public const DEFAULT = 'default';

    /** @var string Prefix of the data attributes written to the html element. */
    public const ATTRIBUTE_PREFIX = 'data-chinijo-';

    /**
     * Allowed values for each preference. The first value is always the default.
     *
     * @var array
     */
    public const CHOICES = [
        'contrast' => ['default', 'high'],
        'fontsize' => ['default', 'large', 'xlarge', 'xxlarge'],
        'font' => ['default', 'legible'],
        'letterspacing' => ['default', 'wide', 'wider'],
        'wordspacing' => ['default', 'wide', 'wider'],
        'lineheight' => ['default', 'wide', 'wider'],
        'motion' => ['default', 'reduce'],
    ];

    /**
     * Short names of every preference, in the order shown in the panel.
     *
     * @return string[]
     */
    public static function get_names(): array {
        return array_keys(self::CHOICES);
    }

    /**
     * Allowed values for one preference.
     *
     * @param string $name Short preference name, for example 'fontsize'.
     * @return string[]
     * @throws invalid_parameter_exception When the preference does not exist.
     */
    public static function get_choices(string $name): array {
        if (!array_key_exists($name, self::CHOICES)) {
            throw new invalid_parameter_exception('Unknown display preference: ' . $name);
        }
        return self::CHOICES[$name];
    }

    /**
     * Full user preference name for a short preference name.
     *
     * @param string $name Short preference name.
     * @return string
     */
    public static function get_preference_name(string $name): string {
        return self::PREFIX . $name;
    }

    /**
     * Whether a value is allowed for a preference.
     *
     * @param string $name Short preference name.
     * @param mixed $value Candidate value.
     * @return bool
     */
    public static function is_valid(string $name, $value): bool {
        return array_key_exists($name, self::CHOICES) && is_string($value)
            && in_array($value, self::CHOICES[$name], true);
    }

    /**
     * Whether the current visitor can store preferences permanently.
     *
     * Guests and visitors who are not logged in only keep them for the session.
     *
     * @return bool
     */
    public static function can_persist(): bool {
        return isloggedin() && !isguestuser();
    }

    /**
     * Current values of every preference for the current user.
     *
     * Stored values that are no longer valid fall back to the default, so a
     * corrupted or obsolete preference can never reach the page markup.
     *
     * @return array Short name => value.
     */
    public static function get_all(): array {
        $values = [];
        foreach (self::get_names() as $name) {
            $value = get_user_preferences(self::get_preference_name($name), self::DEFAULT);
            $values[$name] = self::is_valid($name, $value) ? $value : self::DEFAULT;
        }
        return $values;
    }

    /**
     * Store one preference for the current user.
     *
     * @param string $name Short preference name.
     * @param string $value New value, which must be one of the allowed choices.
     * @throws invalid_parameter_exception When the name or the value is not allowed.
     */
    public static function set(string $name, string $value): void {
        if (!self::is_valid($name, $value)) {
            throw new invalid_parameter_exception('Invalid value for display preference ' . $name);
        }
        $prefname = self::get_preference_name($name);
        if ($value === self::DEFAULT) {
            // Default values are not stored, so that the table only holds real choices.
            unset_user_preference($prefname);
        } else {
            set_user_preference($prefname, $value);
        }
    }

    /**
     * Store several preferences for the current user.
     *
     * Every value is validated before anything is written, so an invalid
     * submission never leaves the preferences half-updated.
     *
     * @param array $values Short name => value. Unknown names are rejected.
     * @throws invalid_parameter_exception When any name or value is not allowed.
     */
    public static function set_many(array $values): void {
        foreach ($values as $name => $value) {
            if (!self::is_valid((string) $name, $value)) {
                throw new invalid_parameter_exception('Invalid value for display preference ' . $name);
            }
        }
        foreach ($values as $name => $value) {
            self::set((string) $name, $value);
        }
    }

    /**
     * Restore every preference of the current user to its default.
     */
    public static function reset(): void {
        foreach (self::get_names() as $name) {
            unset_user_preference(self::get_preference_name($name));
        }
    }

    /**
     * Data attributes describing the given preference values for the html element.
     *
     * @param array $values Short name => value, as returned by get_all().
     * @return array Attribute name => value.
     */
    public static function get_html_attributes(array $values): array {
        $attributes = [];
        foreach (self::get_names() as $name) {
            $value = $values[$name] ?? self::DEFAULT;
            $attributes[self::ATTRIBUTE_PREFIX . $name] = self::is_valid($name, $value) ? $value : self::DEFAULT;
        }
        return $attributes;
    }

    /**
     * Definitions consumed by core_user through the theme_chinijo_user_preferences() callback.
     *
     * They let core validate writes made through the core_user REST and web
     * service APIs: only the listed values are accepted and a user can only
     * change their own preferences, never anybody else's.
     *
     * @return array
     */
    public static function get_user_preference_definitions(): array {
        $definitions = [];
        foreach (self::CHOICES as $name => $choices) {
            $definitions[self::get_preference_name($name)] = [
                'type' => PARAM_ALPHA,
                'null' => NULL_ALLOWED,
                'default' => self::DEFAULT,
                'choices' => $choices,
                'permissioncallback' => [self::class, 'can_edit'],
            ];
        }
        return $definitions;
    }

    /**
     * Permission callback: only the user themself may change their display preferences.
     *
     * Teachers, managers and administrators cannot set them for anybody else.
     *
     * @param stdClass $user The user whose preference would change.
     * @param string $preferencename The preference name.
     * @return bool
     */
    public static function can_edit(stdClass $user, string $preferencename): bool {
        return core_user::is_current_user($user) && self::can_persist();
    }
}
