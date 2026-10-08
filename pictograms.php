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
 * Manage the pictograms of the sections and activities of a course.
 *
 * @package    theme_chinijo
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use theme_chinijo\form\pictogram_form;
use theme_chinijo\local\pictogram_rules;
use theme_chinijo\local\pictograms;

$courseid = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

$course = get_course($courseid);
require_login($course);
$context = context_course::instance($course->id);
require_capability(pictograms::CAPABILITY, $context);

$baseurl = new moodle_url('/theme/chinijo/pictograms.php', ['id' => $course->id]);
$PAGE->set_url($baseurl);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('pictograms_title', 'theme_chinijo', format_string($course->shortname, true, ['context' => $context])));
$PAGE->set_heading(format_string($course->fullname, true, ['context' => $context]));
$PAGE->navbar->add(get_string('pictograms', 'theme_chinijo'), $baseurl);

if ($action === 'edit' || $action === 'delete') {
    $itemtype = required_param('itemtype', PARAM_ALPHA);
    $itemid = required_param('itemid', PARAM_INT);
    if (!pictogram_rules::item_belongs_to_course($course, $itemtype, $itemid)) {
        throw new moodle_exception('pictogram_error_item', 'theme_chinijo', $baseurl);
    }
    $itemname = pictogram_rules::get_item_name($course, $itemtype, $itemid);
    $record = pictograms::get_record($course->id, $itemtype, $itemid);
    $itemurl = new moodle_url($baseurl, ['action' => $action, 'itemtype' => $itemtype, 'itemid' => $itemid]);
    $PAGE->set_url($itemurl);
}

if ($action === 'delete') {
    if ($record === null) {
        redirect($baseurl);
    }
    if (optional_param('confirm', false, PARAM_BOOL) && confirm_sesskey()) {
        pictograms::delete($course, $itemtype, $itemid);
        redirect(
            $baseurl,
            get_string('pictogram_deleted', 'theme_chinijo', $itemname),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('pictogram_delete', 'theme_chinijo'));
    echo $OUTPUT->confirm(
        get_string('pictogram_deleteconfirm', 'theme_chinijo', s($itemname)),
        new moodle_url($itemurl, ['confirm' => 1, 'sesskey' => sesskey()]),
        $baseurl
    );
    echo $OUTPUT->footer();
    die();
}

if ($action === 'edit') {
    $form = new pictogram_form($itemurl, ['itemname' => $itemname]);

    $draftitemid = file_get_submitted_draft_itemid('pictogram');
    file_prepare_draft_area(
        $draftitemid,
        $context->id,
        pictograms::COMPONENT,
        pictograms::FILEAREA,
        $record->id ?? null,
        pictogram_rules::get_filemanager_options()
    );
    $form->set_data([
        'id' => $course->id,
        'action' => 'edit',
        'itemtype' => $itemtype,
        'itemid' => $itemid,
        'pictogram' => $draftitemid,
        'alttext' => $record->alttext ?? $itemname,
        'author' => $record->author ?? '',
        'license' => $record->license ?? '',
    ]);

    if ($form->is_cancelled()) {
        redirect($baseurl);
    }
    if ($data = $form->get_data()) {
        pictograms::save($course, $itemtype, $itemid, $data);
        redirect(
            $baseurl,
            get_string('pictogram_saved', 'theme_chinijo', $itemname),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string($record ? 'pictogram_change' : 'pictogram_choose', 'theme_chinijo'));
    echo html_writer::div(get_string('pictogram_licensenotice', 'theme_chinijo'), 'alert alert-info');
    $form->display();
    echo $OUTPUT->footer();
    die();
}

// List every section and activity with its pictogram.
$records = pictograms::get_records($course->id);
$modinfo = get_fast_modinfo($course);
$items = [];
$additem = function (string $type, int $itemid, string $name) use (&$items, $records, $baseurl) {
    $record = $records[$type . ':' . $itemid] ?? null;
    $file = $record ? pictograms::get_file($record) : null;
    $params = ['action' => 'edit', 'itemtype' => $type, 'itemid' => $itemid];
    $items[] = [
        'issection' => $type === pictograms::TYPE_SECTION,
        'name' => $name,
        'url' => $file ? pictograms::get_file_url($file)->out(false) : null,
        'alt' => $record->alttext ?? '',
        'editurl' => (new moodle_url($baseurl, $params))->out(false),
        'deleteurl' => $record ? (new moodle_url($baseurl, ['action' => 'delete'] + $params))->out(false) : null,
    ];
};
foreach ($modinfo->get_section_info_all() as $section) {
    $additem(pictograms::TYPE_SECTION, (int) $section->id, get_section_name($course, $section));
    foreach ($modinfo->sections[$section->section] ?? [] as $cmid) {
        $cm = $modinfo->get_cm($cmid);
        if ($cm->deletioninprogress) {
            continue;
        }
        $additem(pictograms::TYPE_CM, (int) $cm->id, $cm->get_formatted_name());
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pictograms', 'theme_chinijo'));
echo $OUTPUT->render_from_template('theme_chinijo/pictogram_manage', ['items' => $items]);
echo $OUTPUT->footer();
