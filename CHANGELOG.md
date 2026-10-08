# Changelog

All notable changes to theme_chinijo. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/); releases use
semantic versioning (`$plugin->release`).

## [Unreleased]

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
