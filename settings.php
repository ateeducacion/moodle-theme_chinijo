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
 * Administration settings of the Chinijo theme.
 *
 * @package    theme_chinijo
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings = new theme_boost_admin_settingspage_tabs('themesettingchinijo', get_string('configtitle', 'theme_chinijo'));

    // General settings.
    $page = new admin_settingpage('theme_chinijo_general', get_string('generalsettings', 'theme_chinijo'));

    $setting = new admin_setting_configtext(
        'theme_chinijo/unaddableblocks',
        get_string('unaddableblocks', 'theme_chinijo'),
        get_string('unaddableblocks_desc', 'theme_chinijo'),
        'navigation,settings,course_list',
        PARAM_TEXT
    );
    $page->add($setting);

    $setting = new admin_setting_configcolourpicker(
        'theme_chinijo/brandcolor',
        get_string('brandcolor', 'theme_chinijo'),
        get_string('brandcolor_desc', 'theme_chinijo'),
        ''
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);

    // Advanced settings.
    $page = new admin_settingpage('theme_chinijo_advanced', get_string('advancedsettings', 'theme_chinijo'));

    $setting = new admin_setting_scsscode(
        'theme_chinijo/scsspre',
        get_string('rawscsspre', 'theme_chinijo'),
        get_string('rawscsspre_desc', 'theme_chinijo'),
        '',
        PARAM_RAW
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $setting = new admin_setting_scsscode(
        'theme_chinijo/scss',
        get_string('rawscss', 'theme_chinijo'),
        get_string('rawscss_desc', 'theme_chinijo'),
        '',
        PARAM_RAW
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);
}
