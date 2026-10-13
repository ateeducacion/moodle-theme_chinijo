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

namespace theme_chinijo\form;

use core_text;
use theme_chinijo\local\pictogram_rules;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/licenselib.php');

/**
 * Form to choose the pictogram of a course section or activity.
 *
 * @package    theme_chinijo
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pictogram_form extends \moodleform {
    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;
        $itemname = $this->_customdata['itemname'];

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'action', 'edit');
        $mform->setType('action', PARAM_ALPHA);
        $mform->addElement('hidden', 'itemtype');
        $mform->setType('itemtype', PARAM_ALPHA);
        $mform->addElement('hidden', 'itemid');
        $mform->setType('itemid', PARAM_INT);

        $mform->addElement('static', 'itemname', get_string('pictogram_item', 'theme_chinijo'), s($itemname));

        $mform->addElement(
            'filemanager',
            'pictogram',
            get_string('pictogram_image', 'theme_chinijo'),
            null,
            pictogram_rules::get_filemanager_options()
        );
        $mform->addRule('pictogram', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('pictogram', 'pictogram_image', 'theme_chinijo');

        $mform->addElement(
            'text',
            'alttext',
            get_string('pictogram_alttext', 'theme_chinijo'),
            ['maxlength' => pictogram_rules::ALT_MAXLENGTH, 'size' => 50]
        );
        $mform->setType('alttext', PARAM_TEXT);
        $mform->addRule('alttext', get_string('required'), 'required', null, 'client');
        $mform->addRule(
            'alttext',
            get_string('maximumchars', '', pictogram_rules::ALT_MAXLENGTH),
            'maxlength',
            pictogram_rules::ALT_MAXLENGTH,
            'client'
        );
        $mform->addHelpButton('alttext', 'pictogram_alttext', 'theme_chinijo');

        $mform->addElement('text', 'author', get_string('pictogram_author', 'theme_chinijo'), ['maxlength' => 255, 'size' => 50]);
        $mform->setType('author', PARAM_TEXT);
        $mform->addHelpButton('author', 'pictogram_author', 'theme_chinijo');

        $licenses = ['' => get_string('pictogram_license_none', 'theme_chinijo')];
        foreach (\license_manager::get_active_licenses() as $license) {
            $licenses[$license->shortname] = $license->fullname;
        }
        $mform->addElement('select', 'license', get_string('pictogram_license', 'theme_chinijo'), $licenses);
        // Licence short names contain dots; pictogram_rules::clean_license() checks the value against the site's licences.
        $mform->setType('license', PARAM_NOTAGS);
        $mform->addHelpButton('license', 'pictogram_license', 'theme_chinijo');

        $this->add_action_buttons(true, get_string('savechanges'));
    }

    /**
     * Server-side validation, which does not rely on the client-side rules.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array Errors keyed by element name.
     */
    public function validation($data, $files) {
        global $USER;

        $errors = parent::validation($data, $files);

        $alttext = trim((string) ($data['alttext'] ?? ''));
        if ($alttext === '') {
            $errors['alttext'] = get_string('required');
        } else if (core_text::strlen($alttext) > pictogram_rules::ALT_MAXLENGTH) {
            $errors['alttext'] = get_string('maximumchars', '', pictogram_rules::ALT_MAXLENGTH);
        }

        $usercontext = \context_user::instance($USER->id);
        $draftfiles = get_file_storage()->get_area_files(
            $usercontext->id,
            'user',
            'draft',
            (int) ($data['pictogram'] ?? 0),
            'id',
            false
        );
        if (count($draftfiles) !== 1) {
            $errors['pictogram'] = get_string('pictogram_error_required', 'theme_chinijo');
        } else {
            $file = reset($draftfiles);
            if ($file->get_filesize() > pictogram_rules::MAX_BYTES) {
                $maxsize = display_size(pictogram_rules::MAX_BYTES);
                $errors['pictogram'] = get_string('pictogram_error_size', 'theme_chinijo', $maxsize);
            } else if (!pictogram_rules::is_accepted_image($file)) {
                $errors['pictogram'] = get_string('pictogram_error_type', 'theme_chinijo');
            }
        }

        return $errors;
    }
}
