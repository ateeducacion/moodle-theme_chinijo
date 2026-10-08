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

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ExpectationException;

/**
 * Step definitions for theme_chinijo.
 *
 * @package    theme_chinijo
 * @category   test
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_theme_chinijo extends behat_base {
    /**
     * Pages of the theme that scenarios can visit with "I am on the ... page".
     *
     * Recognised page names are:
     * | Page type    | Identifier       | Description                          |
     * | Pictograms   | Course shortname | Pictogram management page of a course |
     *
     * @param string $type Page type.
     * @param string $identifier Identifier.
     * @return moodle_url
     */
    protected function resolve_page_instance_url(string $type, string $identifier): moodle_url {
        switch (strtolower($type)) {
            case 'pictograms':
                return new moodle_url('/theme/chinijo/pictograms.php', ['id' => $this->get_course_id($identifier)]);
            default:
                throw new Exception('Unrecognised theme_chinijo page type "' . $type . '."');
        }
    }

    /**
     * Pages of the theme without an identifier.
     *
     * | Page name        | Description                  |
     * | Display settings | Stand-alone display settings |
     *
     * @param string $page Page name.
     * @return moodle_url
     */
    protected function resolve_page_url(string $page): moodle_url {
        switch (strtolower($page)) {
            case 'display settings':
                return new moodle_url('/theme/chinijo/preferences.php');
            default:
                throw new Exception('Unrecognised theme_chinijo page "' . $page . '."');
        }
    }

    /**
     * Make Spanish available on the test site with a minimal language pack, so that the theme's own Spanish strings
     * are used for users whose language is Spanish (no network access is needed).
     *
     * @Given the Spanish language is available for theme_chinijo tests
     */
    public function the_spanish_language_is_available(): void {
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
     * Press the tab key until an element has the focus, failing if it is never reached.
     *
     * This checks that the element can be reached with the keyboard alone and that no focus trap
     * stops the way to it.
     *
     * @When I press tab until :element :selectortype is focused
     * @param string $element Element locator.
     * @param string $selectortype Selector type.
     */
    public function i_press_tab_until_focused(string $element, string $selectortype): void {
        $this->press_tab_until($this->find($selectortype, $element), "\"$element\" \"$selectortype\"");
    }

    /**
     * Press the tab key until an element inside a container has the focus, failing if it is never reached.
     *
     * @When I press tab until :element :selectortype in the :container :containertype is focused
     * @param string $element Element locator.
     * @param string $selectortype Selector type.
     * @param string $container Container locator.
     * @param string $containertype Container selector type.
     */
    public function i_press_tab_until_focused_in(
        string $element,
        string $selectortype,
        string $container,
        string $containertype
    ): void {
        $node = $this->get_node_in_container($selectortype, $element, $containertype, $container);
        $this->press_tab_until($node, "\"$element\" \"$selectortype\" in \"$container\"");
    }

    /**
     * Press tab until the node is the active element.
     *
     * @param \Behat\Mink\Element\NodeElement $node Target.
     * @param string $description Description for the error message.
     */
    protected function press_tab_until(\Behat\Mink\Element\NodeElement $node, string $description): void {
        if (!$this->running_javascript()) {
            throw new \Behat\Mink\Exception\DriverException('Keyboard navigation requires JavaScript');
        }
        $xpath = addslashes_js($node->getXpath());
        $script = 'return (function() { return document.activeElement === document.evaluate("' . $xpath . '",
            document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue; })();';
        for ($i = 0; $i < 120; $i++) {
            if ($this->evaluate_script($script)) {
                return;
            }
            behat_base::type_keys($this->getSession(), [behat_keys::TAB]);
        }
        throw new ExpectationException("$description was not reached with the tab key", $this->getSession());
    }
}
