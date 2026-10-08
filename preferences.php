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
 * Stand-alone display settings page.
 *
 * It works without JavaScript and is also where guests and visitors who are
 * not logged in save their settings for the session. It only ever reads and
 * writes the display preferences of the person making the request.
 *
 * @package    theme_chinijo
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Visitors who are not logged in may change their own display settings for the
// session, so the login page stays usable for everybody.
// phpcs:ignore moodle.Files.RequireLogin.Missing
require(__DIR__ . '/../../config.php');

use theme_chinijo\local\preferences;
use theme_chinijo\local\preferences_ui;

$returnurl = optional_param('returnurl', '/', PARAM_LOCALURL);
$returnurl = new moodle_url($returnurl ?: '/');

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url(preferences_ui::PAGE_PATH));
$PAGE->set_pagelayout(isloggedin() && !isguestuser() ? 'standard' : 'login');
$PAGE->set_title(get_string('displaysettings', 'theme_chinijo'));
$PAGE->set_heading(get_string('displaysettings', 'theme_chinijo'));

if (data_submitted()) {
    require_sesskey();

    if (optional_param('reset', false, PARAM_BOOL)) {
        preferences::reset();
        $message = get_string('prefs_resetdone', 'theme_chinijo');
    } else {
        $values = [];
        foreach (preferences::get_names() as $name) {
            $value = optional_param($name, null, PARAM_ALPHA);
            if ($value !== null) {
                $values[$name] = $value;
            }
        }
        // Throws invalid_parameter_exception, and stores nothing, when any value is not allowed.
        preferences::set_many($values);
        $message = get_string('prefs_saved', 'theme_chinijo');
    }
    redirect($returnurl, $message, null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('theme_chinijo/preferences_form', preferences_ui::get_form_context($returnurl, false));
echo $OUTPUT->footer();
