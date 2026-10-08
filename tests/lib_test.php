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

#[\PHPUnit\Framework\Attributes\CoversFunction('theme_chinijo_get_main_scss_content')]
#[\PHPUnit\Framework\Attributes\CoversFunction('theme_chinijo_get_pre_scss')]
#[\PHPUnit\Framework\Attributes\CoversFunction('theme_chinijo_get_extra_scss')]
#[\PHPUnit\Framework\Attributes\CoversFunction('theme_chinijo_user_preferences')]
#[\PHPUnit\Framework\Attributes\CoversFunction('theme_chinijo_extend_navigation_course')]
#[\PHPUnit\Framework\Attributes\CoversFunction('theme_chinijo_extend_navigation_user_settings')]
/**
 * Tests for the plugin metadata, configuration and lib.php callbacks.
 *
 * @package    theme_chinijo
 * @category   test
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     ::theme_chinijo_get_main_scss_content
 * @covers     ::theme_chinijo_get_pre_scss
 * @covers     ::theme_chinijo_get_extra_scss
 * @covers     ::theme_chinijo_user_preferences
 * @covers     ::theme_chinijo_extend_navigation_course
 * @covers     ::theme_chinijo_extend_navigation_user_settings
 */
final class lib_test extends \advanced_testcase {
    /**
     * Load the theme's lib.php.
     */
    public static function setUpBeforeClass(): void {
        global $CFG;
        parent::setUpBeforeClass();
        require_once($CFG->dirroot . '/theme/chinijo/lib.php');
    }

    /**
     * The plugin is installed with the expected component, dependencies and minimum version.
     *
     */
    public function test_installed_metadata(): void {
        $plugin = \core_plugin_manager::instance()->get_plugin_info('theme_chinijo');
        $this->assertNotNull($plugin);
        $this->assertSame('theme_chinijo', $plugin->component);
        $this->assertEquals(2024100700, $plugin->versionrequires);
        $this->assertEquals($plugin->versiondisk, $plugin->versiondb);
        $this->assertArrayHasKey('theme_boost', $plugin->dependencies);

        $theme = \theme_config::load('chinijo');
        $this->assertSame('chinijo', $theme->name);
        $this->assertSame(['boost'], $theme->parents);
    }

    /**
     * The main SCSS wraps Boost's default preset with the theme's own variables and rules.
     *
     */
    public function test_main_scss(): void {
        $scss = theme_chinijo_get_main_scss_content(\theme_config::load('chinijo'));
        $pre = strpos($scss, '$chinijo-target-size');
        $boost = strpos($scss, '@import "bootstrap";');
        $post = strpos($scss, '@import "chinijo/contrast";');
        $this->assertNotFalse($pre);
        $this->assertNotFalse($boost);
        $this->assertNotFalse($post);
        $this->assertTrue($pre < $boost && $boost < $post);
    }

    /**
     * Only a valid hexadecimal brand colour reaches the SCSS, so a setting cannot inject code.
     *
     */
    public function test_pre_and_extra_scss(): void {
        $theme = \theme_config::load('chinijo');
        $theme->settings = (object) ['brandcolor' => '#0f6cbf', 'scsspre' => '$custom: 1;', 'scss' => '.custom { color: red; }'];
        $pre = theme_chinijo_get_pre_scss($theme);
        $this->assertStringContainsString('$primary: #0f6cbf;', $pre);
        $this->assertStringContainsString('$custom: 1;', $pre);
        $this->assertSame('.custom { color: red; }', theme_chinijo_get_extra_scss($theme));

        $theme->settings = (object) ['brandcolor' => 'red; } body { display: none'];
        $this->assertStringNotContainsString('$primary', theme_chinijo_get_pre_scss($theme));
        $this->assertSame('', theme_chinijo_get_extra_scss($theme));
    }

    /**
     * The preference definitions are exposed to core_user.
     *
     */
    public function test_user_preferences_callback(): void {
        $definitions = theme_chinijo_user_preferences();
        $this->assertArrayHasKey('theme_chinijo_contrast', $definitions);
        $this->assertSame(PARAM_ALPHA, $definitions['theme_chinijo_contrast']['type']);
    }

    /**
     * Teachers get the pictogram page in the course menu; students do not; other themes do not.
     *
     */
    public function test_extend_navigation_course(): void {
        global $PAGE;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $context = \context_course::instance($course->id);

        // Enrolment may send the course welcome e-mail, which sets up the global page's theme.
        $PAGE = new \moodle_page();
        $PAGE->force_theme('chinijo');
        $this->setUser($teacher);
        $node = \navigation_node::create('Course');
        theme_chinijo_extend_navigation_course($node, $course, $context);
        $this->assertNotFalse($node->find('theme_chinijo_pictograms', \navigation_node::TYPE_SETTING));

        $this->setUser($student);
        $node = \navigation_node::create('Course');
        theme_chinijo_extend_navigation_course($node, $course, $context);
        $this->assertFalse($node->find('theme_chinijo_pictograms', \navigation_node::TYPE_SETTING));
    }

    /**
     * The display settings page is linked from the user's own preferences only.
     *
     */
    public function test_extend_navigation_user_settings(): void {
        global $PAGE;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $course = get_site();
        $PAGE->force_theme('chinijo');
        $this->setUser($user);

        $node = \navigation_node::create('Preferences');
        theme_chinijo_extend_navigation_user_settings(
            $node,
            $user,
            \context_user::instance($user->id),
            $course,
            \context_course::instance($course->id)
        );
        $this->assertNotFalse($node->find('theme_chinijo_preferences', \navigation_node::TYPE_SETTING));

        $node = \navigation_node::create('Preferences');
        theme_chinijo_extend_navigation_user_settings(
            $node,
            $other,
            \context_user::instance($other->id),
            $course,
            \context_course::instance($course->id)
        );
        $this->assertFalse($node->find('theme_chinijo_preferences', \navigation_node::TYPE_SETTING));
    }
}
