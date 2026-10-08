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

use backup;
use backup_controller;
use restore_controller;
use restore_dbops;
use theme_chinijo\local\pictograms;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

#[\PHPUnit\Framework\Attributes\CoversClass(\backup_theme_chinijo_plugin::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\restore_theme_chinijo_plugin::class)]
/**
 * Tests for the backup and restore of pictograms.
 *
 * @package    theme_chinijo
 * @category   test
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \backup_theme_chinijo_plugin
 * @covers     \restore_theme_chinijo_plugin
 */
final class backup_test extends \advanced_testcase {
    /**
     * Duplicating a course copies its pictograms onto the new sections and activities, with their files.
     */
    public function test_duplicate_course_keeps_pictograms(): void {
        global $DB, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['numsections' => 2]);
        $page = $generator->create_module('page', ['course' => $course->id, 'section' => 1, 'name' => 'Story']);
        $section = get_fast_modinfo($course)->get_section_info(2);
        $chinijo = $generator->get_plugin_generator('theme_chinijo');
        $chinijo->create_pictogram(['courseid' => $course->id, 'itemtype' => pictograms::TYPE_CM,
            'itemid' => $page->cmid, 'alttext' => 'Book', 'author' => 'ARASAAC', 'license' => 'cc-nc-sa-4.0']);
        $chinijo->create_pictogram(['courseid' => $course->id, 'itemtype' => pictograms::TYPE_SECTION,
            'itemid' => $section->id, 'alttext' => 'Sun', 'filepath' => 'pictogram-sun.png']);

        $bc = new backup_controller(
            backup::TYPE_1COURSE,
            $course->id,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_IMPORT,
            $USER->id
        );
        $backupid = $bc->get_backupid();
        $bc->execute_plan();
        $bc->destroy();

        $newcourseid = restore_dbops::create_new_course('Copy', 'COPY', $course->category);
        $rc = new restore_controller(
            $backupid,
            $newcourseid,
            backup::INTERACTIVE_NO,
            backup::MODE_IMPORT,
            $USER->id,
            backup::TARGET_NEW_COURSE
        );
        $rc->execute_precheck();
        $rc->execute_plan();
        $rc->destroy();

        $newmodinfo = get_fast_modinfo($newcourseid);
        $newcm = null;
        foreach ($newmodinfo->get_cms() as $cm) {
            if ($cm->name === 'Story') {
                $newcm = $cm;
            }
        }
        $this->assertNotNull($newcm);
        $cmrecord = pictograms::get_record($newcourseid, pictograms::TYPE_CM, (int) $newcm->id);
        $this->assertNotNull($cmrecord);
        $this->assertSame('Book', $cmrecord->alttext);
        $this->assertSame('ARASAAC', $cmrecord->author);
        $this->assertSame('cc-nc-sa-4.0', $cmrecord->license);
        $this->assertSame('pictogram-book.png', pictograms::get_file($cmrecord)->get_filename());

        $newsection = $newmodinfo->get_section_info(2);
        $sectionrecord = pictograms::get_record($newcourseid, pictograms::TYPE_SECTION, (int) $newsection->id);
        $this->assertSame('Sun', $sectionrecord->alttext);
        $this->assertNotNull(pictograms::get_file($sectionrecord));

        // The original course is untouched.
        $this->assertCount(2, pictograms::get_records($course->id));
        $this->assertCount(2, $DB->get_records(pictograms::TABLE, ['courseid' => $newcourseid]));
    }
}
