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
 * for the first student. Used by dev/seed.php (make seed) and, through
 * chinijo_seed_playground(), by the browser Moodle Playground scenario in
 * blueprints/. Excluded from release packages.
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

/** Names of the demo course sections, by section number. */
const CHINIJO_DEMO_SECTIONS = [1 => 'Story time', 2 => 'Let\'s draw', 3 => 'Numbers', 4 => 'Animals'];

/** Completion settings of the demo activities (course_modules fields), by activity name. */
const CHINIJO_DEMO_COMPLETION = [
    'Welcome to the class' => ['completion' => COMPLETION_TRACKING_MANUAL],
    'Read the story' => ['completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionview' => 1],
    'Look at the pictures' => ['completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionview' => 1],
    'Draw your family' => ['completion' => COMPLETION_TRACKING_AUTOMATIC],
    'Colour the sun' => ['completion' => COMPLETION_TRACKING_MANUAL],
    'Show your drawing to the class' => ['completion' => COMPLETION_TRACKING_MANUAL],
    'Count to ten' => ['completion' => COMPLETION_TRACKING_MANUAL],
    'Numbers song (example link)' => ['completion' => COMPLETION_TRACKING_MANUAL],
    'Animals at home' => ['completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionview' => 1],
    'Animals quiz' => ['completion' => COMPLETION_TRACKING_MANUAL],
    'Your favourite animal' => ['completion' => COMPLETION_TRACKING_MANUAL],
    'Show and tell' => ['completion' => COMPLETION_TRACKING_MANUAL],
];

/** Chapters of the demo book: content by title. */
const CHINIJO_DEMO_CHAPTERS = [
    'The cat' => '<p>The cat says miau. It likes to sleep in the sun.</p>',
    'The dog' => '<p>The dog says guau. It likes to run in the park.</p>',
];

/** True/false questions of the demo quiz: name, text and 1 when the statement is true. */
const CHINIJO_DEMO_QUESTIONS = [
    ['Cat', '<p>The cat says miau.</p>', 1],
    ['Dog', '<p>Dogs can fly.</p>', 0],
];

/** Options of the demo choice. */
const CHINIJO_DEMO_CHOICE_OPTIONS = ['Cat', 'Dog', 'Fish', 'Bird'];


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
    // The category may be left over from a demo course deleted by hand.
    $category = $DB->get_record('course_categories', ['idnumber' => 'CHINIJO-DEMO'])
        ?: $generator->create_category(['name' => 'Primary education (demo)', 'idnumber' => 'CHINIJO-DEMO']);
    $course = $generator->create_course([
        'fullname' => 'Year 1 classroom (Chinijo demo)',
        'shortname' => CHINIJO_DEMO_COURSE,
        'category' => $category->id,
        'format' => 'topics',
        'numsections' => 4,
        'enablecompletion' => 1,
        'showcompletionconditions' => 1,
        'summary' => 'Synthetic demonstration course for the Chinijo theme. No real people or data.',
    ]);
    foreach (CHINIJO_DEMO_SECTIONS as $number => $name) {
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
        // A learning situation usually has several activities (see docs/curriculum-canarias.md).
        ['page', 2, 'Look at the pictures', ['completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionview' => 1,
            'content' => '<p>Look at the pictures of families. Who is in each family?</p>']],
        ['assign', 2, 'Draw your family', ['completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionsubmit' => 1,
            'intro' => '<p>Write two sentences about your drawing and press <strong>Submit</strong>.</p>',
            'assignsubmission_onlinetext_enabled' => 1, 'assignsubmission_file_enabled' => 0, 'submissiondrafts' => 0]],
        ['page', 2, 'Colour the sun', ['completion' => COMPLETION_TRACKING_MANUAL,
            'content' => '<p>Colour the sun in your drawing with yellow and orange.</p>']],
        ['page', 2, 'Show your drawing to the class', ['completion' => COMPLETION_TRACKING_MANUAL,
            'content' => '<p>Tell the class who is in your drawing.</p>']],
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
 * Save a true/false question through its question type, as the question editing form does.
 *
 * @param stdClass $category Question category.
 * @param string $name Question name.
 * @param string $text Question text (HTML).
 * @param int $correct 1 when the statement is true, 0 when it is false.
 * @return stdClass The question.
 */
function chinijo_seed_truefalse(stdClass $category, string $name, string $text, int $correct): stdClass {
    global $USER;

    $empty = ['text' => '', 'format' => FORMAT_HTML];
    $form = (object) [
        'category' => $category->id . ',' . $category->contextid,
        'name' => $name,
        'questiontext' => ['text' => $text, 'format' => FORMAT_HTML],
        'generalfeedback' => $empty,
        'defaultmark' => 1,
        'penalty' => 1,
        'correctanswer' => $correct,
        'feedbacktrue' => $empty,
        'feedbackfalse' => $empty,
        'showstandardinstruction' => 0,
        'status' => \core_question\local\bank\question_version_status::QUESTION_STATUS_READY,
    ];
    $question = (object) ['category' => $category->id, 'qtype' => 'truefalse', 'createdby' => $USER->id];
    return question_bank::get_qtype('truefalse')->save_question($question, $form);
}

/**
 * Add the chapters of the demo book, unless it has chapters already.
 *
 * @param stdClass $book The book record.
 */
function chinijo_seed_book_chapters(stdClass $book): void {
    global $DB;

    if ($DB->record_exists('book_chapters', ['bookid' => $book->id])) {
        return;
    }
    $pagenum = 0;
    foreach (CHINIJO_DEMO_CHAPTERS as $title => $content) {
        $DB->insert_record('book_chapters', (object) ['bookid' => $book->id, 'pagenum' => ++$pagenum, 'subchapter' => 0,
            'title' => $title, 'content' => $content, 'contentformat' => FORMAT_HTML, 'hidden' => 0, 'importsrc' => '',
            'timecreated' => time(), 'timemodified' => time()]);
    }
    // The book's revision is part of the URLs of its files, as when a chapter is saved with the form.
    $DB->execute('UPDATE {book} SET revision = revision + 1 WHERE id = ?', [$book->id]);
}

/**
 * Add the true/false questions of the demo quiz in its own question bank, unless it has questions already.
 *
 * @param stdClass $quiz The quiz record, with its course module id in cmid.
 */
function chinijo_seed_quiz_questions(stdClass $quiz): void {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/quiz/locallib.php');
    require_once($CFG->libdir . '/questionlib.php');

    if ($DB->record_exists('quiz_slots', ['quizid' => $quiz->id])) {
        return;
    }
    $context = context_module::instance($quiz->cmid);
    // Moodle 5.0 creates a module's default category on request; 4.5 has question_make_default_categories().
    $category = (int) $CFG->branch >= 500
        ? question_get_default_category($context->id, true)
        : question_make_default_categories([$context]);
    // The question generator needs PHPUnit, so the questions are saved through the question type, as the form does.
    foreach (CHINIJO_DEMO_QUESTIONS as $slot => [$name, $text, $correct]) {
        $question = chinijo_seed_truefalse($category, $name, $text, $correct);
        quiz_add_quiz_question($question->id, $quiz, $slot + 1);
    }
    \mod_quiz\quiz_settings::create($quiz->id)->get_grade_calculator()->recompute_quiz_sumgrades();
}

/**
 * Start the teacher's discussion in the demo forum, unless the forum has discussions already.
 *
 * @param stdClass $forum The forum record.
 * @param stdClass $teacher The teacher who starts the discussion.
 */
function chinijo_seed_forum_discussion(stdClass $forum, stdClass $teacher): void {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/forum/lib.php');

    if ($DB->record_exists('forum_discussions', ['forum' => $forum->id])) {
        return;
    }
    forum_add_discussion((object) [
        'course' => $forum->course,
        'forum' => $forum->id,
        'name' => 'My cat Luna',
        'message' => '<p>This is my cat Luna. What animal do you have?</p>',
        'messageformat' => FORMAT_HTML,
        'messagetrust' => 0,
        'mailnow' => 0,
        'groupid' => -1,
        'timestart' => 0,
        'timeend' => 0,
        'pinned' => FORUM_DISCUSSION_UNPINNED,
        'timelocked' => 0,
    ], null, null, $teacher->id);
}

/**
 * Add a learning situation with Moodle's other common content types: a label, a book, a quiz
 * with two questions, a choice and a forum with a discussion. Idempotent.
 *
 * @param testing_data_generator $generator Generator.
 * @param stdClass $course The demo course.
 * @param stdClass $teacher The teacher who starts the forum discussion.
 */
function chinijo_seed_more_content(testing_data_generator $generator, stdClass $course, stdClass $teacher): void {
    global $DB;

    if ($DB->record_exists('quiz', ['course' => $course->id, 'name' => 'Animals quiz'])) {
        return;
    }
    $common = ['course' => $course->id, 'section' => 4];

    $generator->create_module('label', $common + [
        'intro' => '<p>This week we learn about the animals that live with us.</p>',
    ]);

    chinijo_seed_book_chapters($generator->create_module('book', $common + ['name' => 'Animals at home',
        'intro' => '<p>Read about two animals that live at home.</p>',
        'completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionview' => 1]));

    chinijo_seed_quiz_questions($generator->create_module('quiz', $common + ['name' => 'Animals quiz',
        'intro' => '<p>Answer two questions about animals.</p>', 'questionsperpage' => 1,
        'completion' => COMPLETION_TRACKING_MANUAL]));

    $generator->create_module('choice', $common + ['name' => 'Your favourite animal',
        'intro' => '<p>Which animal do you like best?</p>', 'option' => CHINIJO_DEMO_CHOICE_OPTIONS,
        'completion' => COMPLETION_TRACKING_MANUAL]);

    chinijo_seed_forum_discussion($generator->create_module('forum', $common + ['name' => 'Show and tell',
        'type' => 'general', 'intro' => '<p>Tell us about an animal you know.</p>',
        'completion' => COMPLETION_TRACKING_MANUAL]), $teacher);
    mtrace('Demo content types added.');
}

/**
 * Complete the book, quiz, choice and forum that Playground's addModule step created: it writes
 * only the activity record, so this adds the book's chapters, the quiz's first section, questions
 * and grade item, the choice's options and the forum's discussion. Idempotent.
 *
 * @param stdClass $course The demo course.
 * @param stdClass $teacher The teacher who starts the forum discussion.
 */
function chinijo_seed_playground_content(stdClass $course, stdClass $teacher): void {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/quiz/lib.php');

    // By name: the course also has Moodle's announcements forum.
    $activities = ['Animals at home' => 'book', 'Animals quiz' => 'quiz', 'Your favourite animal' => 'choice',
        'Show and tell' => 'forum'];
    foreach (get_fast_modinfo($course)->get_cms() as $cm) {
        if (($activities[$cm->name] ?? null) !== $cm->modname) {
            continue;
        }
        $instance = $DB->get_record($cm->modname, ['id' => $cm->instance], '*', MUST_EXIST);
        if ($cm->modname === 'book') {
            chinijo_seed_book_chapters($instance);
        } else if ($cm->modname === 'quiz') {
            // As quiz_add_instance() does.
            if (!$DB->record_exists('quiz_sections', ['quizid' => $instance->id])) {
                $DB->insert_record('quiz_sections', (object) ['quizid' => $instance->id, 'firstslot' => 1,
                    'heading' => '', 'shufflequestions' => 0]);
            }
            $instance->cmid = $cm->id;
            chinijo_seed_quiz_questions($instance);
            quiz_grade_item_update($instance);
        } else if ($cm->modname === 'choice') {
            if (!$DB->record_exists('choice_options', ['choiceid' => $instance->id])) {
                foreach (CHINIJO_DEMO_CHOICE_OPTIONS as $text) {
                    $DB->insert_record('choice_options', (object) ['choiceid' => $instance->id, 'text' => $text,
                        'maxanswers' => 0, 'timemodified' => time()]);
                }
            }
        } else {
            chinijo_seed_forum_discussion($instance, $teacher);
        }
    }
}

/**
 * Add the demo pictograms to the sections and to some activities.
 *
 * @param stdClass $course The demo course.
 */
function chinijo_seed_pictograms(stdClass $course): void {
    $modinfo = get_fast_modinfo($course);
    $sections = $modinfo->get_section_info_all();
    $shapes = [0 => ['sun', 'Sun'], 1 => ['book', 'Book'], 2 => ['pencil', 'Pencil'], 3 => ['numbers', 'Numbers'],
        4 => ['star', 'Star']];
    foreach ($shapes as $number => [$shape, $alt]) {
        if (isset($sections[$number])) {
            chinijo_seed_pictogram($course, pictograms::TYPE_SECTION, (int) $sections[$number]->id, $shape, $alt);
        }
    }
    $activityshapes = ['Welcome to the class' => ['star', 'Star'], 'Read the story' => ['book', 'Book'],
        'Draw your family' => ['pencil', 'Pencil'], 'Colour the sun' => ['sun', 'Sun'], 'Count to ten' => ['numbers', 'Numbers']];
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
        } else if ($cm->name === 'Read the story' || $cm->name === 'Look at the pictures') {
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
    chinijo_seed_more_content($generator, $course, $users['teacher']);
    chinijo_seed_pictograms($course);
    chinijo_seed_completion($course, $users['student1']);

    mtrace('Demo data ready. Course: ' . (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false));
    mtrace('Demo accounts (development only, password ' . CHINIJO_DEMO_PASSWORD . '): teacher1, student1, student2.');
    $CFG->debug = $debug;
    return $course;
}

/**
 * Finish the demo course built by the Moodle Playground blueprint.
 *
 * Moodle Playground ships Moodle without its tests/ directories, so the test
 * data generators used by chinijo_seed_run() are not available there. The
 * blueprint creates the users, the course, the enrolments and the activities
 * with Playground's own steps, which do not set completion; this adds what they
 * leave out: completion settings, section names, the assignment's online text
 * submissions, the content of the book, quiz, choice and forum, the demo
 * pictograms and the completion data of student1. Idempotent.
 */
function chinijo_seed_playground(): void {
    global $DB;

    \core\session\manager::set_user(get_admin());
    $course = $DB->get_record('course', ['shortname' => CHINIJO_DEMO_COURSE], '*', MUST_EXIST);
    $DB->update_record('course', (object) ['id' => $course->id, 'enablecompletion' => 1, 'showcompletionconditions' => 1]);
    foreach (CHINIJO_DEMO_SECTIONS as $number => $name) {
        $DB->set_field('course_sections', 'name', $name, ['course' => $course->id, 'section' => $number]);
    }
    foreach (get_fast_modinfo($course)->get_cms() as $cm) {
        if (isset(CHINIJO_DEMO_COMPLETION[$cm->name])) {
            $DB->update_record('course_modules', (object) (['id' => $cm->id] + CHINIJO_DEMO_COMPLETION[$cm->name]));
        }
        if ($cm->modname === 'assign') {
            $config = ['assignment' => $cm->instance, 'plugin' => 'onlinetext', 'subtype' => 'assignsubmission',
                'name' => 'enabled'];
            if (!$DB->record_exists('assign_plugin_config', $config)) {
                $DB->insert_record('assign_plugin_config', (object) ($config + ['value' => 1]));
            }
        }
    }
    rebuild_course_cache($course->id, true);
    $course = get_course($course->id);

    try {
        chinijo_seed_pictograms($course);
    } catch (\Throwable $e) {
        // The demo still works without pictograms.
        mtrace('Demo pictograms skipped: ' . $e->getMessage());
    }
    chinijo_seed_completion($course, $DB->get_record('user', ['username' => 'student1'], '*', MUST_EXIST));
    try {
        chinijo_seed_playground_content($course, $DB->get_record('user', ['username' => 'teacher1'], '*', MUST_EXIST));
    } catch (\Throwable $e) {
        // The rest of the demo works without the chapters, questions, options and discussion.
        mtrace('Demo content skipped: ' . $e->getMessage());
    }
    mtrace('Playground demo course ready.');
}
