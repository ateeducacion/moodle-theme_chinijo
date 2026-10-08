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
 * Development and demonstration only: build a synthetic demo site for Chinijo.
 *
 * Creates (once) three fictitious users, a Primary education demo course with
 * completion tracking, a few activities including an assignment, demo
 * pictograms drawn with GD (no third-party images) and some completion data
 * for the first student. Used by dev/seed.php (make seed) and by the browser
 * Moodle Playground scenario in blueprints/. Excluded from release packages.
 *
 * @package    theme_chinijo
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->libdir . '/testing/generator/lib.php');
require_once($CFG->dirroot . '/course/lib.php');

use theme_chinijo\local\pictograms;

// Demo accounts. Disposable, development and demonstration only.
/** Password of the demo accounts: disposable, for development and demonstration sites only. */
const CHINIJO_DEMO_PASSWORD = 'Chinijo-demo-1234';

/** Short name of the demo course. */
const CHINIJO_DEMO_COURSE = 'CHINIJO-DEMO';


/**
 * Get or create a user.
 *
 * @param testing_data_generator $generator Generator.
 * @param array $record User fields.
 * @return stdClass
 */
function chinijo_seed_user(testing_data_generator $generator, array $record): stdClass {
    global $DB;
    if ($user = $DB->get_record('user', ['username' => $record['username'], 'deleted' => 0])) {
        return $user;
    }
    return $generator->create_user($record + ['password' => CHINIJO_DEMO_PASSWORD, 'auth' => 'manual']);
}

/**
 * Draw a simple, self-made demo pictogram with GD and return its PNG bytes.
 *
 * @param string $shape One of star, book, pencil, numbers or sun.
 * @return string|null PNG data, or null when GD is not available.
 */
function chinijo_seed_draw(string $shape): ?string {
    if (!function_exists('imagecreatetruecolor')) {
        return null;
    }
    $size = 256;
    $image = imagecreatetruecolor($size, $size);
    $white = imagecolorallocate($image, 255, 255, 255);
    $ink = imagecolorallocate($image, 29, 33, 37);
    $colours = [
        'star' => imagecolorallocate($image, 255, 196, 0),
        'book' => imagecolorallocate($image, 15, 108, 191),
        'pencil' => imagecolorallocate($image, 240, 120, 30),
        'numbers' => imagecolorallocate($image, 53, 122, 50),
        'sun' => imagecolorallocate($image, 255, 170, 0),
    ];
    $fill = $colours[$shape] ?? $ink;
    imagefilledrectangle($image, 0, 0, $size, $size, $white);
    imagesetthickness($image, 8);

    switch ($shape) {
        case 'star':
            $points = [];
            for ($i = 0; $i < 10; $i++) {
                $radius = $i % 2 ? 50 : 112;
                $angle = deg2rad(-90 + $i * 36);
                $points[] = (int) round(128 + $radius * cos($angle));
                $points[] = (int) round(132 + $radius * sin($angle));
            }
            imagefilledpolygon($image, $points, $fill);
            imagepolygon($image, $points, $ink);
            break;
        case 'book':
            imagefilledrectangle($image, 36, 56, 124, 200, $fill);
            imagefilledrectangle($image, 132, 56, 220, 200, $fill);
            imagerectangle($image, 36, 56, 124, 200, $ink);
            imagerectangle($image, 132, 56, 220, 200, $ink);
            imageline($image, 128, 50, 128, 206, $ink);
            break;
        case 'pencil':
            imagefilledpolygon($image, [60, 196, 76, 140, 180, 36, 220, 76, 116, 180], $fill);
            imagepolygon($image, [60, 196, 76, 140, 180, 36, 220, 76, 116, 180], $ink);
            imagefilledpolygon($image, [40, 216, 60, 196, 76, 140, 116, 180], $ink);
            break;
        case 'numbers':
            $small = imagecreatetruecolor(30, 16);
            imagefilledrectangle($small, 0, 0, 30, 16, $white);
            imagestring($small, 5, 2, 0, '123', $fill);
            imagecopyresized($image, $small, 16, 64, 0, 0, 224, 120, 30, 16);
            imagedestroy($small);
            break;
        default:
            imagefilledellipse($image, 128, 128, 120, 120, $fill);
            for ($i = 0; $i < 8; $i++) {
                $angle = deg2rad($i * 45);
                imageline(
                    $image,
                    (int) (128 + 80 * cos($angle)),
                    (int) (128 + 80 * sin($angle)),
                    (int) (128 + 116 * cos($angle)),
                    (int) (128 + 116 * sin($angle)),
                    $fill
                );
            }
    }

    ob_start();
    imagepng($image);
    imagedestroy($image);
    return ob_get_clean();
}

/**
 * Attach a demo pictogram to an item through the theme API, as a teacher would.
 *
 * @param stdClass $course Course.
 * @param string $type Item type.
 * @param int $itemid Item id.
 * @param string $shape Shape to draw.
 * @param string $alttext Text alternative.
 */
function chinijo_seed_pictogram(stdClass $course, string $type, int $itemid, string $shape, string $alttext): void {
    global $USER;
    if (pictograms::get_record($course->id, $type, $itemid)) {
        return;
    }
    $png = chinijo_seed_draw($shape);
    if ($png === null) {
        mtrace('GD is not available: demo pictograms skipped.');
        return;
    }
    $draftitemid = file_get_unused_draft_itemid();
    get_file_storage()->create_file_from_string([
        'contextid' => context_user::instance($USER->id)->id,
        'component' => 'user',
        'filearea' => 'draft',
        'itemid' => $draftitemid,
        'filepath' => '/',
        'filename' => $shape . '.png',
    ], $png);
    pictograms::save($course, $type, $itemid, (object) [
        'pictogram' => $draftitemid,
        'alttext' => $alttext,
        'author' => 'Chinijo demo image, generated by dev/seed.php',
        'license' => 'public',
    ]);
}

/**
 * Install the Spanish language pack, so that the Spanish interface can be tried (needs network access).
 */
function chinijo_seed_spanish(): void {
    global $CFG;
    if (!file_exists($CFG->dataroot . '/lang/es/langconfig.php')) {
        try {
            (new \tool_langimport\controller())->install_languagepacks('es');
            mtrace('Spanish language pack installed.');
        } catch (Throwable $e) {
            mtrace('Spanish language pack not installed (' . $e->getMessage() . '). Continuing.');
        }
    }
    set_config('langmenu', 1);
}

/**
 * Create the three demo accounts.
 *
 * @param testing_data_generator $generator Generator.
 * @return stdClass[] Keyed by role: teacher, student1, student2.
 */
function chinijo_seed_users(testing_data_generator $generator): array {
    return [
        'teacher' => chinijo_seed_user($generator, [
            'username' => 'teacher1', 'firstname' => 'Ana', 'lastname' => 'Docente (demo)',
            'email' => 'teacher1@example.com', 'lang' => 'en',
        ]),
        'student1' => chinijo_seed_user($generator, [
            'username' => 'student1', 'firstname' => 'Leo', 'lastname' => 'Alumno (demo)',
            'email' => 'student1@example.com', 'lang' => 'en',
        ]),
        'student2' => chinijo_seed_user($generator, [
            'username' => 'student2', 'firstname' => 'Sara', 'lastname' => 'Alumna (demo)',
            'email' => 'student2@example.com', 'lang' => get_string_manager()->translation_exists('es') ? 'es' : 'en',
        ]),
    ];
}

/**
 * Get or create the demo course with its sections and activities.
 *
 * @param testing_data_generator $generator Generator.
 * @return stdClass The course.
 */
function chinijo_seed_course(testing_data_generator $generator): stdClass {
    global $DB;

    $course = $DB->get_record('course', ['shortname' => CHINIJO_DEMO_COURSE]);
    if ($course) {
        return $course;
    }
    $category = $generator->create_category(['name' => 'Primary education (demo)', 'idnumber' => 'CHINIJO-DEMO']);
    $course = $generator->create_course([
        'fullname' => 'Year 1 classroom (Chinijo demo)',
        'shortname' => CHINIJO_DEMO_COURSE,
        'category' => $category->id,
        'format' => 'topics',
        'numsections' => 3,
        'enablecompletion' => 1,
        'showcompletionconditions' => 1,
        'summary' => 'Synthetic demonstration course for the Chinijo theme. No real people or data.',
    ]);
    foreach ([1 => 'Story time', 2 => 'Let\'s draw', 3 => 'Numbers'] as $number => $name) {
        course_update_section(
            $course,
            $DB->get_record('course_sections', ['course' => $course->id, 'section' => $number]),
            ['name' => $name]
        );
    }

    $activities = [
        ['page', 0, 'Welcome to the class', ['completion' => COMPLETION_TRACKING_MANUAL,
            'content' => '<p>Hello! This is our class. Press <strong>Mark as done</strong> when you have read this page.</p>']],
        ['page', 1, 'Read the story', ['completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionview' => 1,
            'content' => '<p>Once upon a time, a little goat lived on a volcano by the sea.</p>']],
        ['assign', 2, 'Draw your family', ['completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionsubmit' => 1,
            'intro' => '<p>Write two sentences about your drawing and press <strong>Submit</strong>.</p>',
            'assignsubmission_onlinetext_enabled' => 1, 'assignsubmission_file_enabled' => 0, 'submissiondrafts' => 0]],
        ['page', 3, 'Count to ten', ['completion' => COMPLETION_TRACKING_MANUAL,
            'content' => '<p>One, two, three, four, five, six, seven, eight, nine, ten!</p>']],
        ['url', 3, 'Numbers song (example link)', ['completion' => COMPLETION_TRACKING_MANUAL,
            'externalurl' => 'https://example.com/']],
    ];
    foreach ($activities as [$module, $section, $name, $fields]) {
        $generator->create_module($module, ['course' => $course->id, 'section' => $section, 'name' => $name] + $fields);
    }
    mtrace('Demo course created.');
    return $course;
}

/**
 * Add the demo pictograms to the sections and to some activities.
 *
 * @param stdClass $course The demo course.
 */
function chinijo_seed_pictograms(stdClass $course): void {
    $modinfo = get_fast_modinfo($course);
    $sections = $modinfo->get_section_info_all();
    $shapes = [0 => ['sun', 'Sun'], 1 => ['book', 'Book'], 2 => ['pencil', 'Pencil'], 3 => ['numbers', 'Numbers']];
    foreach ($shapes as $number => [$shape, $alt]) {
        if (isset($sections[$number])) {
            chinijo_seed_pictogram($course, pictograms::TYPE_SECTION, (int) $sections[$number]->id, $shape, $alt);
        }
    }
    $activityshapes = ['Welcome to the class' => ['star', 'Star'], 'Read the story' => ['book', 'Book'],
        'Draw your family' => ['pencil', 'Pencil'], 'Count to ten' => ['numbers', 'Numbers']];
    foreach ($modinfo->get_cms() as $cm) {
        if (isset($activityshapes[$cm->name])) {
            [$shape, $alt] = $activityshapes[$cm->name];
            chinijo_seed_pictogram($course, pictograms::TYPE_CM, (int) $cm->id, $shape, $alt);
        }
    }
}

/**
 * Record real completion data for a student through Moodle's completion API.
 *
 * @param stdClass $course The demo course.
 * @param stdClass $student The student.
 */
function chinijo_seed_completion(stdClass $course, stdClass $student): void {
    $completion = new completion_info($course);
    foreach (get_fast_modinfo($course, $student->id)->get_cms() as $cm) {
        if ($cm->name === 'Welcome to the class') {
            $completion->update_state($cm, COMPLETION_COMPLETE, $student->id);
        } else if ($cm->name === 'Read the story') {
            $completion->set_module_viewed($cm, $student->id);
        }
    }
}

/**
 * Create the demo site content. Idempotent.
 *
 * @return stdClass The demo course.
 */
function chinijo_seed_run(): stdClass {
    global $CFG;

    \core\session\manager::set_user(get_admin());
    $generator = new testing_data_generator();
    // Enrolment welcome messages are not sent on development sites; do not print their debugging noise.
    $debug = $CFG->debug;
    $CFG->debug = 0;

    $users = chinijo_seed_users($generator);
    $course = chinijo_seed_course($generator);
    $generator->enrol_user($users['teacher']->id, $course->id, 'editingteacher');
    $generator->enrol_user($users['student1']->id, $course->id, 'student');
    $generator->enrol_user($users['student2']->id, $course->id, 'student');
    chinijo_seed_pictograms($course);
    chinijo_seed_completion($course, $users['student1']);

    mtrace('Demo data ready. Course: ' . (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false));
    mtrace('Demo accounts (development only, password ' . CHINIJO_DEMO_PASSWORD . '): teacher1, student1, student2.');
    $CFG->debug = $debug;
    return $course;
}
