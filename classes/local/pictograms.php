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

use context_course;
use invalid_parameter_exception;
use moodle_url;
use stdClass;
use stored_file;

/**
 * Storage of the pictograms that teachers associate with course sections and activities.
 *
 * Pictogram images are third-party resources with their own licences (for
 * example ARASAAC, CC BY-NC-SA 4.0). They are uploaded by teachers, stored with
 * the File API in the course context, served by this site only and never
 * bundled with the theme. Metadata (text alternative, author, licence) lives in
 * the theme_chinijo_pictogram table, which holds no personal data. Validation
 * rules are in {@see pictogram_rules} and what learners see in
 * {@see pictogram_display}.
 *
 * @package    theme_chinijo
 * @copyright  2026 Área de Tecnología Educativa (ATE), Gobierno de Canarias
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pictograms {
    /** @var string Database table. */
    public const TABLE = 'theme_chinijo_pictogram';

    /** @var string File area, in the course context. */
    public const FILEAREA = 'pictogram';

    /** @var string Component that owns the files. */
    public const COMPONENT = 'theme_chinijo';

    /** @var string Capability required to manage pictograms. */
    public const CAPABILITY = 'theme/chinijo:managepictograms';

    /** @var string Item type for course sections. */
    public const TYPE_SECTION = 'section';

    /** @var string Item type for course modules (activities and resources). */
    public const TYPE_CM = 'cm';

    /**
     * Pictogram record of an item, if any.
     *
     * @param int $courseid Course id.
     * @param string $type Item type.
     * @param int $itemid Section id or course module id.
     * @return stdClass|null
     */
    public static function get_record(int $courseid, string $type, int $itemid): ?stdClass {
        global $DB;
        $record = $DB->get_record(self::TABLE, ['courseid' => $courseid, 'itemtype' => $type, 'itemid' => $itemid]);
        return $record ?: null;
    }

    /**
     * All pictogram records of a course, keyed by "type:itemid".
     *
     * @param int $courseid Course id.
     * @return stdClass[]
     */
    public static function get_records(int $courseid): array {
        global $DB;
        $records = [];
        foreach ($DB->get_records(self::TABLE, ['courseid' => $courseid], 'id') as $record) {
            $records[$record->itemtype . ':' . $record->itemid] = $record;
        }
        return $records;
    }

    /**
     * Stored image of a pictogram record.
     *
     * @param stdClass $record Pictogram record.
     * @return stored_file|null
     */
    public static function get_file(stdClass $record): ?stored_file {
        $context = context_course::instance($record->courseid, IGNORE_MISSING);
        if (!$context) {
            return null;
        }
        $files = get_file_storage()->get_area_files($context->id, self::COMPONENT, self::FILEAREA, $record->id, 'id', false);
        return $files ? reset($files) : null;
    }

    /**
     * Public URL of a pictogram image.
     *
     * @param stored_file $file The stored image.
     * @return moodle_url
     */
    public static function get_file_url(stored_file $file): moodle_url {
        return moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        );
    }

    /**
     * Create or update the pictogram of an item from a draft file area.
     *
     * The capability, the item, the text and the image are all checked on the
     * server; nothing is kept when any of them is not valid.
     *
     * @param stdClass $course The course.
     * @param string $type Item type.
     * @param int $itemid Section id or course module id.
     * @param stdClass $data Form data with alttext, author, license and the draft item id in 'pictogram'.
     * @return stdClass The saved record.
     * @throws invalid_parameter_exception When the item, the text or the image is not valid.
     */
    public static function save(stdClass $course, string $type, int $itemid, stdClass $data): stdClass {
        global $DB;

        $context = context_course::instance($course->id);
        require_capability(self::CAPABILITY, $context);
        if (!pictogram_rules::item_belongs_to_course($course, $type, $itemid)) {
            throw new invalid_parameter_exception('The item does not belong to this course');
        }
        $record = self::prepare_record($course->id, $type, $itemid, $data);

        $transaction = $DB->start_delegated_transaction();
        try {
            if (empty($record->id)) {
                $record->id = $DB->insert_record(self::TABLE, $record);
            } else {
                $DB->update_record(self::TABLE, $record);
            }
            file_save_draft_area_files(
                (int) $data->pictogram,
                $context->id,
                self::COMPONENT,
                self::FILEAREA,
                $record->id,
                pictogram_rules::get_filemanager_options()
            );
            // The file manager restricts types in the browser; check the stored file on the server as well.
            $file = self::get_file($record);
            if ($file === null || !pictogram_rules::is_accepted_image($file)) {
                throw new invalid_parameter_exception('A PNG, JPEG or WebP image is required');
            }
            $transaction->allow_commit();
        } catch (\Throwable $e) {
            // Rolls back the record and the file records, then rethrows.
            $transaction->rollback($e);
        }

        return $record;
    }

    /**
     * New or existing record with the cleaned values of the form.
     *
     * @param int $courseid Course id.
     * @param string $type Item type.
     * @param int $itemid Item id.
     * @param stdClass $data Form data.
     * @return stdClass
     * @throws invalid_parameter_exception When the text alternative is not valid.
     */
    protected static function prepare_record(int $courseid, string $type, int $itemid, stdClass $data): stdClass {
        $now = time();
        $record = self::get_record($courseid, $type, $itemid) ?? (object) [
            'courseid' => $courseid,
            'itemtype' => $type,
            'itemid' => $itemid,
            'timecreated' => $now,
        ];
        $record->alttext = pictogram_rules::clean_alttext((string) ($data->alttext ?? ''));
        // Optional attribution fields are stored as NULL when empty.
        $record->author = pictogram_rules::clean_author((string) ($data->author ?? '')) ?: null;
        $record->license = pictogram_rules::clean_license((string) ($data->license ?? '')) ?: null;
        $record->timemodified = $now;
        return $record;
    }

    /**
     * Remove the pictogram of an item, if there is one.
     *
     * @param stdClass $course The course.
     * @param string $type Item type.
     * @param int $itemid Section id or course module id.
     */
    public static function delete(stdClass $course, string $type, int $itemid): void {
        require_capability(self::CAPABILITY, context_course::instance($course->id));
        self::delete_for_item((int) $course->id, $type, $itemid);
    }

    /**
     * Remove the pictogram of an item without capability checks (used by event observers).
     *
     * @param int $courseid Course id.
     * @param string $type Item type.
     * @param int $itemid Section id or course module id.
     */
    public static function delete_for_item(int $courseid, string $type, int $itemid): void {
        $record = self::get_record($courseid, $type, $itemid);
        if ($record !== null) {
            self::delete_record($record);
        }
    }

    /**
     * Remove every pictogram of a course (used when the course is deleted; its context may already be gone).
     *
     * @param int $courseid Course id.
     */
    public static function delete_for_course(int $courseid): void {
        global $DB;
        foreach ($DB->get_records(self::TABLE, ['courseid' => $courseid]) as $record) {
            self::delete_record($record);
        }
    }

    /**
     * Remove a pictogram record and its files.
     *
     * @param stdClass $record Pictogram record.
     */
    protected static function delete_record(stdClass $record): void {
        global $DB;
        $context = context_course::instance($record->courseid, IGNORE_MISSING);
        if ($context) {
            get_file_storage()->delete_area_files($context->id, self::COMPONENT, self::FILEAREA, $record->id);
        }
        $DB->delete_records(self::TABLE, ['id' => $record->id]);
    }
}
