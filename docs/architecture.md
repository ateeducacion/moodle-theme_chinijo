# Architecture

Chinijo is a native Moodle **Boost child theme** (`theme_chinijo`). It adds a
small amount of PHP, Mustache, SCSS and ES-module code on top of Boost and keeps
the override surface as small as possible, so that Moodle upgrades stay cheap.

## Inheritance and override surface

| Mechanism | What Chinijo uses it for | Why this mechanism |
|---|---|---|
| `config.php` (`$THEME->parents = ['boost']`) | Inherit every Boost layout, template and renderer. Only the SCSS callbacks and the renderer factory are Chinijo's own. | Boost's `config.php` is identical on MOODLE_405_STABLE and MOODLE_503_STABLE, so the child declares the same properties. |
| SCSS: `theme_chinijo_get_main_scss_content()` | `scss/pre.scss` (variables) + Boost's `preset/default.scss` + `scss/post.scss` (rules). | Standard child-theme pattern; the Boost preset keeps the 5.3 colour-mode import order (`moodle/dark` last). |
| `classes/output/core_renderer.php` | Overrides **only** `course_header()` to add the progress indicator, the pictogram data and the completion feedback script. | `course_header()` is rendered by Boost's `full_header()` on every course page, in the course header region, on all branches. No template is copied. |
| Hooks (`db/hooks.php`, `classes/hook_callbacks.php`) | `before_html_attributes` (display preferences, priority 0 so it runs after Boost's colour mode listener), `before_standard_top_of_body_html_generation` (toolbar on the login page), `after_standard_main_region_html_generation` (FEDER notice, pictogram credits), `before_footer_html_generation` (FEDER notice on the login page). | Hooks API, available on all supported branches (4.4+). Every callback checks that Chinijo renders the page. |
| `lib.php` callbacks | `render_navbar_output` (display settings control), `user_preferences` (validation of the preferences by core_user), `pluginfile` (FEDER emblem, pictograms), `extend_navigation_course` (Pictograms page), `extend_navigation_user_settings` (link in Preferences). | Documented plugin callbacks still used by core on 4.5–5.3; no hook replaces them. |
| `db/events.php` | Remove pictograms when their course, section or activity is deleted. | Events API. |

No Moodle core file, database table or vendor library is modified. No Boost
Mustache template is copied.

## Components

```
classes/
├── local/preferences.php      Choices, validation, storage (user preferences) and html attributes
├── local/preferences_ui.php   Template data for the control and the form
├── local/pictograms.php       Pictogram records, files, permissions, visibility, credits, file serving
├── local/course_progress.php  Progress from Moodle's completion API (per-branch compatible)
├── local/feder.php            EU funding notice from the admin settings
├── local/theme.php            "Is Chinijo rendering this page?" checks
├── output/core_renderer.php   course_header() only
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
  change), shows a polite toast and updates the progress indicator.

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
- Not implemented: course backup and restore of pictograms (Moodle provides
  `backup_theme_plugin`, but restore must remap section and module ids after the
  course structure is restored). Pictograms are therefore not copied by backup,
  import or course duplication. This is a known limitation, tracked in
  `docs/requirements-traceability.md`.

## Course progress and feedback

`local\course_progress::get()` reproduces
`\core_completion\progress::get_course_progress_percentage()` on each branch:
Moodle 4.5 counts every activity with completion; 5.0+ counts only the activities
visible to the user (`get_user_activities_with_completion()`), chosen with
`method_exists()`. Teachers (not tracked) and courses without completion show
nothing. The figure is updated in the page only after core confirms a manual
completion change.

## FEDER (ERDF) notice

Admin settings (`EU funding notice` tab): enable, pages (landing pages or every
page), emblem file, its text alternative and the acknowledgement text. The theme
ships no emblem and no wording. Without an emblem-with-alternative or a text,
nothing is rendered. The notice is a labelled `<aside>` after the main region (a
plain block inside the login layout, which has no region after the main one).

## Settings

General (unneeded blocks, brand colour), EU funding notice, Advanced (raw initial
SCSS, raw SCSS). The brand colour only reaches the SCSS when it is a valid
hexadecimal colour.

## Styling principles

- 16 px base text, comfortable 44 px targets for buttons and form controls in
  content and dialogues (WCAG 2.5.8 needs 24 px), visible focus ring (light gap +
  dark outline) everywhere, scroll padding so focus is not hidden behind the fixed
  navbar or sticky footers, underlined links in text, bold primary buttons, error
  messages with a bar and bold text, course-index names that wrap instead of being
  cut.
- Bootstrap 4.6 (Moodle 4.5) and 5.3 (Moodle 5.x) are both supported: Chinijo's
  own markup uses its own classes and never relies on version-specific utilities.
