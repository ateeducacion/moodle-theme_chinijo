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

use theme_chinijo\local\preferences;

#[\PHPUnit\Framework\Attributes\CoversNothing]
/**
 * Tests that the English and Spanish language packs stay complete and consistent.
 *
 * @package    theme_chinijo
 * @category   test
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class lang_test extends \advanced_testcase {
    /**
     * Load the strings of one language file.
     *
     * @param string $lang Language code.
     * @return array
     */
    private function load(string $lang): array {
        $string = [];
        include(__DIR__ . '/../lang/' . $lang . '/theme_chinijo.php');
        return $string;
    }

    /**
     * Placeholders used by a string, such as {$a} or {$a->total}.
     *
     * @param string $text The string.
     * @return string[]
     */
    private function placeholders(string $text): array {
        preg_match_all('/\{\$a(->[a-z0-9_]+)?\}/i', $text, $matches);
        $placeholders = array_unique($matches[0]);
        sort($placeholders);
        return $placeholders;
    }

    /**
     * Both languages define exactly the same keys, none of them empty.
     */
    public function test_same_keys(): void {
        $en = $this->load('en');
        $es = $this->load('es');
        $this->assertSame([], array_diff_key($en, $es), 'Keys missing from the Spanish pack');
        $this->assertSame([], array_diff_key($es, $en), 'Keys missing from the English pack');
        foreach ([$en, $es] as $strings) {
            foreach ($strings as $key => $value) {
                $this->assertNotSame('', trim($value), $key);
            }
        }
    }

    /**
     * Translations keep the placeholders of the English strings.
     */
    public function test_same_placeholders(): void {
        $en = $this->load('en');
        $es = $this->load('es');
        foreach ($en as $key => $value) {
            $this->assertSame($this->placeholders($value), $this->placeholders($es[$key]), $key);
        }
    }

    /**
     * Every preference, choice and privacy item has its strings.
     */
    public function test_preference_strings(): void {
        $en = $this->load('en');
        foreach (preferences::CHOICES as $name => $choices) {
            $this->assertArrayHasKey('pref_' . $name, $en);
            $this->assertArrayHasKey('privacy:metadata:preference:' . $name, $en);
            foreach ($choices as $choice) {
                $this->assertArrayHasKey('pref_' . $name . '_' . $choice, $en);
            }
        }
    }

    /**
     * Keys are sorted, as Moodle's coding style expects.
     */
    public function test_sorted(): void {
        foreach (['en', 'es'] as $lang) {
            $keys = array_keys($this->load($lang));
            $sorted = $keys;
            sort($sorted, SORT_STRING);
            $this->assertSame($sorted, $keys, $lang);
        }
    }

    /**
     * Install a minimal Spanish language pack in the test dataroot, so that Moodle loads the theme's own
     * Spanish strings exactly as it does on a site with the real pack.
     */
    private function install_spanish_stub(): void {
        global $CFG;
        $dir = $CFG->dataroot . '/lang/es';
        if (!file_exists($dir . '/langconfig.php')) {
            check_dir_exists($dir);
            file_put_contents($dir . '/langconfig.php', "<?php\n\$string['thislanguage'] = 'Español';\n" .
                "\$string['parentlanguage'] = '';\n");
        }
        get_string_manager()->reset_caches();
    }

    /**
     * Strings are served through the string manager in each language, with Unicode and placeholders intact.
     */
    public function test_get_string_in_both_languages(): void {
        $this->resetAfterTest();
        $this->install_spanish_stub();
        $manager = get_string_manager();
        $this->assertTrue($manager->translation_exists('es'));
        $a = (object) ['completed' => 3, 'total' => 8];
        $this->assertSame('3 of 8 activities done', $manager->get_string('path_summary', 'theme_chinijo', $a, 'en'));
        $this->assertSame('3 de 8 actividades hechas', $manager->get_string('path_summary', 'theme_chinijo', $a, 'es'));
        $this->assertSame('Tamaño del texto', $manager->get_string('pref_fontsize', 'theme_chinijo', null, 'es'));
        $this->assertSame('¡Muy bien, Leo!', $manager->get_string('completion_title', 'theme_chinijo', 'Leo', 'es'));
        $a->name = 'Leer';
        $this->assertSame(
            'Has terminado «Leer». Ya llevas 3 de 8.',
            $manager->get_string('completion_text', 'theme_chinijo', $a, 'es')
        );
        // Apostrophes are escaped correctly in the source files.
        $description = $manager->get_string('unaddableblocks_desc', 'theme_chinijo', null, 'en');
        $this->assertStringContainsString("'Add a block'", $description);
    }
}
