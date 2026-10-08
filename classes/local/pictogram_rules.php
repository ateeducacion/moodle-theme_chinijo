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

namespace theme_chinijo\local;

use core_text;
use invalid_parameter_exception;
use stdClass;
use stored_file;

/**
 * Validation rules for pictograms: which items, texts, images and licences are accepted.
 *
 * @package    theme_chinijo
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pictogram_rules {
    /** @var string[] Accepted raster formats. SVG is deliberately excluded because it can carry scripts. */
    public const ACCEPTED_TYPES = ['.png', '.jpg', '.jpeg', '.webp'];

    /** @var string[] MIME types matching ACCEPTED_TYPES, checked again on the content of the stored file. */
    public const ACCEPTED_MIMETYPES = ['image/png', 'image/jpeg', 'image/webp'];

    /** @var int Maximum file size in bytes. */
    public const MAX_BYTES = 1048576;

    /** @var int Maximum length of the text alternative. */
    public const ALT_MAXLENGTH = 150;

    /**
     * Whether an item of the given type and id belongs to the course.
     *
     * This prevents attaching a pictogram to an activity or section of another course.
     *
     * @param stdClass $course The course.
     * @param string $type Item type.
     * @param int $itemid Section id or course module id.
     * @return bool
     */
    public static function item_belongs_to_course(stdClass $course, string $type, int $itemid): bool {
        $modinfo = get_fast_modinfo($course);
        if ($type === pictograms::TYPE_CM) {
            return array_key_exists($itemid, $modinfo->get_cms());
        }
        if ($type === pictograms::TYPE_SECTION) {
            return $modinfo->get_section_info_by_id($itemid) !== null;
        }
        return false;
    }

    /**
     * Display name of an item.
     *
     * @param stdClass $course The course.
     * @param string $type Item type.
     * @param int $itemid Section id or course module id.
     * @return string Plain text name.
     */
    public static function get_item_name(stdClass $course, string $type, int $itemid): string {
        $modinfo = get_fast_modinfo($course);
        if ($type === pictograms::TYPE_CM) {
            return $modinfo->get_cm($itemid)->get_formatted_name();
        }
        return get_section_name($course, $modinfo->get_section_info_by_id($itemid, MUST_EXIST));
    }

    /**
     * Clean and validate the text alternative.
     *
     * @param string $alttext Raw text.
     * @return string Cleaned text.
     * @throws invalid_parameter_exception When it is empty or too long.
     */
    public static function clean_alttext(string $alttext): string {
        $alttext = trim(clean_param($alttext, PARAM_TEXT));
        if ($alttext === '' || core_text::strlen($alttext) > self::ALT_MAXLENGTH) {
            throw new invalid_parameter_exception('Invalid pictogram text alternative');
        }
        return $alttext;
    }

    /**
     * Clean the author and source attribution.
     *
     * @param string $author Raw text.
     * @return string Cleaned text, at most 255 characters; empty when there is none.
     */
    public static function clean_author(string $author): string {
        return core_text::substr(trim(clean_param($author, PARAM_TEXT)), 0, 255);
    }

    /**
     * Licence short name if it is a licence known to this site, or an empty string.
     *
     * @param string $license Licence short name.
     * @return string
     */
    public static function clean_license(string $license): string {
        global $CFG;
        require_once($CFG->libdir . '/licenselib.php');

        // Licence short names contain dots (for example cc-nc-sa-4.0), which PARAM_ALPHANUMEXT would remove.
        $license = preg_replace('/[^a-z0-9._-]/i', '', $license);
        return ($license !== '' && \license_manager::get_license_by_shortname($license)) ? $license : '';
    }

    /**
     * Whether a stored file is a PNG, JPEG or WebP image, judging by its content and not only by its name.
     *
     * @param stored_file $file The file.
     * @return bool
     */
    public static function is_accepted_image(stored_file $file): bool {
        if (!in_array($file->get_mimetype(), self::ACCEPTED_MIMETYPES, true) || $file->get_filesize() > self::MAX_BYTES) {
            return false;
        }
        $info = $file->get_imageinfo();
        return is_array($info) && in_array($info['mimetype'] ?? '', self::ACCEPTED_MIMETYPES, true);
    }

    /**
     * File manager options for the upload form and for saving the draft area.
     *
     * @return array
     */
    public static function get_filemanager_options(): array {
        return [
            'subdirs' => 0,
            'maxfiles' => 1,
            'maxbytes' => self::MAX_BYTES,
            'accepted_types' => self::ACCEPTED_TYPES,
            'return_types' => FILE_INTERNAL,
        ];
    }
}
