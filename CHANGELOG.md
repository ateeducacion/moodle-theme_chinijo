# Changelog

All notable changes to theme_chinijo. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/); releases use
semantic versioning (`$plugin->release`).

## [Unreleased]

### Added
- Development: the full Moodle Playground demo has the "Animals" section of
  `make seed` (a teacher's note, a book with two chapters, a quiz with two
  questions, a choice and a forum with a discussion), so the activity designs of
  version 0.3.0 can be tried there.

## [0.3.0] - not released yet (development version 2026101200, alpha)

### Added
- The course page as a primary classroom: "Up next" is a chalkboard with a
  yellow chalk "Start" button (the only yellow button; every other main action
  stays blue), each section has a coloured band at its top, activities are cards
  with a large icon tile tinted by the activity purpose and a verb above the name
  ("Read", "Hand in", "Answer", "Talk", "Choose", "Practise"), and labels look
  like the teacher's notes. Only outside edit mode; nothing of the course page is
  hidden.
- Assignments: three steps above the instructions (Read, Do your work, Hand it in)
  and a sentence that says whether the work has been handed in, both from the
  learner's own submission status.
- Quizzes: the question number on a line above the question, large question
  text, each answer as a large card that can be pressed anywhere, and larger quiz
  navigation buttons.
- Choices: each option as a large card that can be pressed anywhere.
- Forums: larger discussion names, posts as cards with large text and "Reply" as
  a button; learners find search and subscription behind a "More options" button
  on the same page (teachers see every control as before).
- Books and pages: large text in lines of a comfortable length; the previous and
  next chapter as large buttons with their name. Links and files: one large
  button to open them. Previous and next activity (Moodle 5.x footer, Moodle 4.5
  links) as large buttons.

### Changed
- Headings use Andika by default (Atkinson Hyperlegible when the easy-to-read
  typeface is chosen).
- The "Next" card is now called "Up next" ("Hoy toca" in Spanish).
- Development: the Docker Compose files mount the PostgreSQL volume at
  `/var/lib/postgresql`, as PostgreSQL 18 images require; the test runner
  reinstalls Moodle when its database is missing and gives Behat the same
  timeout allowance as the GitHub workflow.

## [0.2.0] - not released yet (development version 2026100801, alpha)

### Added
- Course page for learners: a greeting, a "Next" card with a large Start link to
  the next activity to do, and "My path": the activities of the current section
  (usually a learning situation) with their pictograms and state (done, now, to
  do, not yet) given by text and shape as well as colour, a link to the section
  and its count, plus the whole-course count. Outside edit mode, rounder section
  cards, large activity rows and large completion buttons.
- `docs/curriculum-canarias.md`: areas and weekly timetable of 1.º–2.º from
  Decreto 211/2022 and the course sizes the design is based on.
- Activity pages: a bar with a large "Back to the course" link, the progress and
  a "Listen" button that reads the page with a voice installed on the device
  (hidden when there is none; no external service).
- Encouragement after marking an activity as done, with a link to the next
  activity; it does not take the focus or block the page.
- Display settings: ready-made combinations (As usual, Easier to read, Easier to
  see, Calmer), large tiles with a preview of each choice, an Andika "School
  letters" typeface and a "Sounds" setting (a short chime when an activity is
  done, off by default).

### Changed
- The progress summary reads "2 of 5 activities done"; the percentage is no
  longer shown.
- Spanish: the display settings control is called "Cómo lo veo".

## [0.1.0] - not released yet (development version 2026100800, alpha)

### Added
- Boost child theme for Moodle 4.5 LTS to 5.3 LTS.
- Personal display settings: high contrast, four text sizes, Atkinson
  Hyperlegible typeface, letter, word and line spacing (up to the WCAG 1.4.12
  values) and reduced motion; stored as each user's own preferences, session-only
  for guests; keyboard-accessible dialogue and a page that works without JavaScript.
- Course progress from Moodle's completion data and polite feedback after a
  confirmed manual completion.
- Pictograms for course sections and activities managed by teachers
  (`theme/chinijo:managepictograms`), with text alternatives, author and licence
  credits, course backup and restore, and cleanup on deletion.
- Configurable EU (FEDER) funding notice (emblem, text alternative, text, pages).
- Accessible defaults: 16 px text, 44 px targets, visible focus ring, underlined
  links in text, emphasised primary actions, error messages with non-colour cues,
  wrapping course index names, forced-colours support.
- English and Spanish language packs; privacy provider.
- Docker development stack, Makefile, moodle-plugin-ci test runner, PHPUnit and
  Behat tests (including axe-core checks), GitHub Actions (CI matrix, security,
  release, Playground preview), Moodle Playground blueprints and documentation.
