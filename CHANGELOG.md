# Changelog

All notable changes to theme_chinijo. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/); releases use
semantic versioning (`$plugin->release`).

## [Unreleased]

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
