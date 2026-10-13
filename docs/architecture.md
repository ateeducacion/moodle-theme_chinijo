# Architecture

Chinijo is a native Moodle **Boost child theme** (`theme_chinijo`). It adds a
small amount of PHP, Mustache, SCSS and ES-module code on top of Boost and keeps
the override surface as small as possible, so that Moodle upgrades stay cheap.

## Inheritance and override surface

| Mechanism | What Chinijo uses it for | Why this mechanism |
|---|---|---|
| `config.php` (`$THEME->parents = ['boost']`) | Inherit every Boost layout, template and renderer. Only the SCSS callbacks and the renderer factory are Chinijo's own. | Boost's `config.php` is identical on MOODLE_405_STABLE and MOODLE_503_STABLE, so the child declares the same properties. |
| SCSS: `theme_chinijo_get_main_scss_content()` | `scss/pre.scss` (variables) + Boost's `preset/default.scss` + `scss/post.scss` (rules). | Standard child-theme pattern; the Boost preset keeps the 5.3 colour-mode import order (`moodle/dark` last). |
| `classes/output/core_renderer.php` | Overrides **only** `course_header()` (greeting on the course page, pictogram data, completion feedback script, verbs of the activity cards) and `course_content_header()` ("Up next" and "My path" on the course page; on activity pages the activity bar with "Back to the course", the progress and "Listen", the steps of an assignment for learners who can hand it in, and the "More options" script on forum pages for learners). Both call the parent first and add to its output. | Boost prints `course_header()` from `full_header()` above the page heading, and `course_content_header()` at the top of the main region (`drawers.mustache`), on every branch from 4.5 to 5.3. The parent's "already printed" guard is respected. No template is copied. |
| Hooks (`db/hooks.php`, `classes/hook_callbacks.php`) | `before_html_attributes` (display preferences, priority 0 so it runs after Boost's colour mode listener), `before_standard_top_of_body_html_generation` (toolbar on the login page), `after_standard_main_region_html_generation` (pictogram credits). | Hooks API, available on all supported branches (4.4+). Every callback checks that Chinijo renders the page. |
| `lib.php` callbacks | `render_navbar_output` (display settings control), `user_preferences` (validation of the preferences by core_user), `pluginfile` (pictograms), `extend_navigation_course` (Pictograms page), `extend_navigation_user_settings` (link in Preferences). | Documented plugin callbacks still used by core on 4.5–5.3; no hook replaces them. |
| `db/events.php` | Remove pictograms when their course, section or activity is deleted. | Events API. |
| `backup/moodle2/` | Include pictograms in course backups, imports and duplicates. | Moodle's `backup_theme_plugin`/`restore_theme_plugin`. |

No Moodle core file, database table or vendor library is modified. No Boost
Mustache template is copied.

## Components

```
classes/
├── local/preferences.php      Choices, validation, storage (user preferences) and html attributes
├── local/preferences_ui.php   Template data for the control and the form
├── local/pictograms.php       Pictogram records, files, permissions, visibility, credits, file serving
├── local/course_progress.php  Progress from Moodle's completion API (per-branch compatible)
├── local/learning_path.php    The learner's path: counted activities in course order, their state and the next one
├── local/activity_verbs.php   The verb of each kind of activity ("Read", "Hand in") for cards and "Up next"
├── local/assign_steps.php     The steps of an assignment (read, do, hand in) from the learner's submission status
├── local/theme.php            "Is Chinijo rendering this page?" checks
├── output/core_renderer.php   course_header() and course_content_header()
├── hook_callbacks.php         Output hooks
├── observer.php               Deletion events
├── form/pictogram_form.php    Moodle form for one pictogram
└── privacy/provider.php       Privacy API (user preferences only)
```

Pages: `preferences.php` (display settings without JavaScript, and session-only
settings for guests and visitors) and `pictograms.php` (teacher management).

JavaScript (`amd/src`, built with Moodle's own Grunt into `amd/build`):

- `theme_chinijo/preferences` turns the navbar link into a button that opens a
  `core/modal` dialogue with the same server-rendered form, previews each choice,
  and saves with `core_user/repository::setUserPreferences()`.
- `theme_chinijo/pictograms` places the pictograms next to section and activity
  names on the course page and in the course index, and re-applies them when the
  course format re-renders.
- `theme_chinijo/completion_feedback` listens to core_course's
  `manualcompletiontoggled` event (dispatched after the server confirmed the
  change), updates the progress, "My path" and "Next" by the same step, and shows
  a message of encouragement in a `role="status"` region inside the main
  landmark. It never takes the focus; while it is shown the page keeps focused
  elements clear of it (scroll padding, WCAG 2.4.11). With the "Sounds" setting
  on, it plays a short chime made with the Web Audio API (no sound file).
- `theme_chinijo/activity_cards` adds the verb of each activity ("Read", "Hand
  in") above its name on the course page, coloured like its icon, from the
  verbs that the renderer sends for the kinds of activity used in the course. It
  is hidden from screen readers, which already announce the type of activity
  with the name, and is skipped in edit mode.
- `theme_chinijo/more_options` puts the less used controls of the forum toolbar
  (search, subscription) in a panel behind a "More options" button on the same
  page (an `aria-expanded` disclosure). The controls are moved whole into a panel
  in the same row, so Moodle's own scripts keep working on them. It is loaded for
  learners only (users without `moodle/course:manageactivities`).
- `theme_chinijo/read_aloud` shows the "Listen" button on activity pages only
  when the browser has a voice installed on the device (`localService`) for the
  page language, and reads the main region without controls or hidden text.
  Network voices are never used, so no text leaves the device.

## Display preferences

| Preference (`theme_chinijo_*`) | Values (first is the default) | Effect |
|---|---|---|
| `contrast` | default, high | Black on white, underlined links, solid black boundaries, inverted primary actions. |
| `fontsize` | default, large (112.5 %), xlarge (125 %), xxlarge (150 %) | Scales the root font size; Boost sizes in rem. The fixed navbar is capped at 18 px. |
| `font` | default, legible | Atkinson Hyperlegible (SIL OFL 1.1, self-hosted in `fonts/`). |
| `letterspacing` | default, wide (0.06em), wider (0.12em) | WCAG 1.4.12 letter spacing at the widest value. |
| `wordspacing` | default, wide (0.08em), wider (0.16em) | WCAG 1.4.12 word spacing at the widest value. |
| `lineheight` | default, wide (1.8, paragraphs 1.5em), wider (2, paragraphs 2em) | WCAG 1.4.12 line height and paragraph spacing. |
| `motion` | default (follow `prefers-reduced-motion`), reduce | Reduced motion is always honoured from the system setting; `reduce` forces it. |

Storage and security:

- Values are written with `set_user_preference()` for the current user only.
  Logged-in users save through core's REST route
  `/api/rest/v2/user/current/preferences`, which validates the name, the value
  (`theme_chinijo_user_preferences()` → `preferences::get_user_preference_definitions()`)
  and the permission callback `preferences::can_edit()` (only the user themself).
  Teachers, managers and administrators cannot change another user's preferences.
- Guests and visitors who are not logged in use `preferences.php` (POST + `sesskey`);
  Moodle keeps their values in the session only.
- The server writes the choices to the `<html>` element as `data-chinijo-*`
  attributes before the first paint; invalid stored values fall back to the default.
- Only display choices are stored. Nothing describes or infers a person's needs.

Boost colour modes (Moodle 5.3, experimental, off by default): when high contrast
is on, Chinijo pins `data-bs-theme="light"` and `data-colourmode="light"` after
Boost's listener, so the two never contradict each other. The JavaScript preview
does the same and restores the previous mode when high contrast is turned off.

## Pictograms

- Table `theme_chinijo_pictogram` (courseid, itemtype `section|cm`, itemid,
  alttext, author, license, timecreated, timemodified; unique course+item). It
  stores no user data.
- Files: File API, course context, component `theme_chinijo`, file area
  `pictogram`, item id = record id. PNG, JPEG or WebP up to 1 MB, checked by the
  file content (`stored_file::get_imageinfo()`), never SVG.
- Capability `theme/chinijo:managepictograms` (course context; editing teachers
  and managers by default). `pictograms::save()` and `delete()` check it on the
  server and refuse items of other courses.
- Learners only receive pictograms of items they can see on the course page
  (`cm_info::is_visible_on_course_page()`, section visibility); the file
  callback checks the same before sending the image.
- Rendering is progressive: the data travels as JSON in a hidden element and the
  script adds `<img>` elements; names are never replaced, so without JavaScript or
  without pictograms the course looks exactly as in Boost.
- Credits (author, licence) are listed in a "Pictogram credits" section of the page.
- Cleanup: observers for course, section and module deletion.
- Backup and restore: `backup/moodle2/backup_theme_chinijo_plugin.class.php` adds
  the records and files to course backups (whatever the course theme is);
  `restore_theme_chinijo_plugin` keeps them until the whole course is restored
  (`after_restore_course()`), maps them to the new section and course module ids
  and restores the files. Covered by `backup_test` (course duplication).

## Course progress and feedback

`local\course_progress::get()` reproduces
`\core_completion\progress::get_course_progress_percentage()` on each branch:
Moodle 4.5 counts every activity with completion; 5.0+ counts only the activities
visible to the user (`get_user_activities_with_completion()`), chosen with
`method_exists()`. Teachers (not tracked) and courses without completion show
nothing. The figure is updated in the page only after core confirms a manual
completion change.

## Settings

General (unneeded blocks, brand colour), Advanced (raw initial
SCSS, raw SCSS). The brand colour only reaches the SCSS when it is a valid
hexadecimal colour.

## The classroom and Moodle's activities (0.3.0)

Everything here is SCSS on Moodle's own markup (`scss/chinijo/_classroom.scss`,
`scss/chinijo/_activities.scss`), plus the two scripts and the assignment steps
above. Selectors use markup that is the same on 4.5 and 5.x, or list both (for
example Moodle 5.x's course footer `.course-linear-navigation` and Moodle 4.5's
`.activity-navigation` links). Nothing of Moodle's interface is hidden: controls
are restyled, made larger or, on forums, put one press away.

- Course page (learners and teachers who are not editing): "Up next" is a
  chalkboard with a yellow chalk "Start" button, sections have a coloured band,
  activities are cards with a large icon tile tinted by the activity purpose
  (`$chinijo-purposes`) and the verb, labels are teacher's notes.
- Two colours of main action, one rule: the yellow button exists only on the dark
  chalkboard; every other main action is Moodle's blue primary button. In high
  contrast both are black with white text.
- Headings (page, sections, activities, questions, chapters, posts) use the
  `--chinijo-heading-font` custom property: Andika by default, Atkinson
  Hyperlegible with the easy-to-read typeface.
- Activity pages: the main action (`.btn-primary`) is at least 60 px high;
  assignments show their steps; quiz answers and choice options are cards whose
  whole surface selects the answer (the label is stretched over the card and the
  radio button stays above it, so it can still be pressed directly); books and
  pages limit the line length; the book's chapter buttons show the label Moodle
  gives to screen readers ("Next: The cat") as visible text.

## Styling principles

- 16 px base text, comfortable 44 px targets for buttons and form controls in
  content and dialogues (WCAG 2.5.8 needs 24 px), visible focus ring (light gap +
  dark outline) everywhere, scroll padding so focus is not hidden behind the fixed
  navbar or sticky footers, underlined links in text, bold primary buttons, error
  messages with a bar and bold text, course-index names that wrap instead of being
  cut.
- Bootstrap 4.6 (Moodle 4.5) and 5.3 (Moodle 5.x) are both supported: Chinijo's
  own markup uses its own classes and never relies on version-specific utilities.
