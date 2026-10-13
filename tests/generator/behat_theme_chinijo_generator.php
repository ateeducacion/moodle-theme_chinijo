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
 * Behat data generator for theme_chinijo.
 *
 * Example:
 *   Given the following "theme_chinijo > pictograms" exist:
 *     | course | activity | section | alttext | author  | license      |
 *     | C1     | Story    |         | Book    | ARASAAC | cc-nc-sa-4.0 |
 *     | C1     |          | 1       | Sun     |         |              |
 *
 * The activity column takes the name of an activity of the course and the
 * section column a section number.
 *
 * @package    theme_chinijo
 * @category   test
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_theme_chinijo_generator extends behat_generator_base {
    /**
     * Entities that can be created.
     *
     * @return array
     */
    protected function get_creatable_entities(): array {
        return [
            'pictograms' => [
                'singular' => 'pictogram',
                'datagenerator' => 'pictogram',
                'required' => ['course', 'alttext'],
                'switchids' => ['course' => 'courseid'],
            ],
        ];
    }

    /**
     * Turn the activity name or the section number into an item type and id.
     *
     * @param array $data Row data.
     * @return array
     */
    protected function preprocess_pictogram(array $data): array {
        $modinfo = get_fast_modinfo($data['courseid']);
        if (!empty($data['activity'])) {
            foreach ($modinfo->get_cms() as $cm) {
                if ($cm->name === $data['activity']) {
                    $data['itemtype'] = 'cm';
                    $data['itemid'] = $cm->id;
                }
            }
            if (empty($data['itemid'])) {
                throw new Exception('Activity "' . $data['activity'] . '" not found in the course.');
            }
        } else {
            $data['itemtype'] = 'section';
            $data['itemid'] = $modinfo->get_section_info((int) ($data['section'] ?? 0), MUST_EXIST)->id;
        }
        unset($data['activity'], $data['section']);
        return $data;
    }
}
