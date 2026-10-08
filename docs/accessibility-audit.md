# Accessibility audit

Results only: what was actually checked, how, and what was found. Method and
checklist: `docs/accessibility.md`. A row marked **Pending** has not been done.

## Status summary (2026-10-08)

- **Automated (axe-core through Moodle Behat)**: the sampled pages and states
  pass WCAG 2.0/2.1/2.2 A and AA on Moodle 5.3 (axe-core 4.13) and the theme's
  regions pass the best-practice rules. Results for Moodle 4.5 are in the table
  below.
- **One finding, not attributable to the theme**: Moodle 5.3's Boost course
  index drawer heading is outside any landmark (axe `region`, a best-practice
  rule, not a WCAG success criterion). Reproduced with theme_boost (see F-01).
- **Manual review, screen readers, devices and EVAGD pre-production**: pending.
- **Conformance claim**: none. The audit is incomplete until the manual part is
  done on the EVAGD pre-production sample.

## Environment of the automated runs

| Item | Moodle 5.3 run | Moodle 4.5 run |
|---|---|---|
| Moodle | 5.3 (Build 20261005), `MOODLE_503_STABLE` | 4.5.15 (Build 20261005), `MOODLE_405_STABLE` |
| PHP / DB | 8.4.26 / PostgreSQL 17.11 | 8.3 / PostgreSQL 17.11 |
| Browser | Chromium 152 (selenium/standalone-chromium:152.0), headless | same |
| axe-core | 4.13 (bundled with Moodle 5.3) | 4.10 (bundled with Moodle 4.5) |
| Runner | `make test-a11y` / `make test-behat` (dev/ci, moodle-plugin-ci 4.5.11) | same |

## Automated results by page (WCAG-EM structured sample)

`accessibility.feature`, tags WCAG 2.0/2.1/2.2 A and AA; "+BP" adds axe's
best-practice rules on the scope shown.

| Page / state | Scope of +BP | 5.3 | 4.5 |
|---|---|---|---|
| Login page, not logged in, with display settings toolbar and FEDER text | whole page | Pass | see table update below |
| Dashboard (learner) | whole page | Pass | |
| Course page with progress and pictograms (learner) | main region | Pass (whole page: F-01 only under +BP) | |
| Activity page (page resource) | main region | Pass | |
| Assignment, "Add submission" form | main region | Pass | |
| Display settings dialogue: default | dialogue | Pass | |
| Display settings dialogue: high contrast + huge text + legible font | dialogue | Pass | |
| Display settings dialogue: high contrast + large text | dialogue | Pass | |
| Stand-alone display settings page | whole page | Pass | |
| Pictogram management page (teacher) | main region | Pass | |
| Pictogram edit form | main region | Pass | |
| Pictogram edit form with validation errors | main region | Pass | |

Keyboard (Behat, Chromium): an assignment was reached from the course page,
opened, filled in and submitted with Tab and Enter only (`keyboard_submission.feature`);
the display settings dialogue opens with Space, previews, saves, closes with
Escape and returns focus to its control (`preferences.feature`). Spanish
interface after a language switch: `language.feature`.

## Findings

| ID | Page | Criterion / rule | Description | Attributable to | Evidence | Action |
|---|---|---|---|---|---|---|
| F-01 | Pages with the course index (Moodle 5.3) | axe `region` (best practice, not a WCAG success criterion) | `#theme_boost-drawers-courseindex > .drawerheader > .drawerheadertop > .drawerheading` is not inside a landmark. The element was added to Boost in 5.3 (MDL-89050). | Moodle core (Boost) | Same result with `?theme=boost` and with Chinijo on the 5.3 development site (axe-core 4.10.2, 2026-10-08). | Not changed in the theme (it would require overriding Boost's drawer). To be reported to Moodle HQ. Behat checks these pages as a whole against WCAG A/AA and their main region against best practices. |
| F-02 | Course page and course index | axe `presentation-role-conflict` (minor, best practice) | Decorative pictograms in the course index (empty `alt`) were flagged. | Theme | Behat run 2026-10-08 | **Fixed**: they are now inside an `aria-hidden` wrapper; the rule passes. |

## Manual checks (to be done in EVAGD pre-production)

| Check | Tool / device | Tester | Date | Result |
|---|---|---|---|---|
| Keyboard path: login → course → assignment → submission | Keyboard only, Chrome and Firefox | | | Pending |
| Same path with NVDA | NVDA + Firefox/Chrome (Windows) | | | Pending |
| Same path with VoiceOver | VoiceOver + Safari (macOS, iPadOS) | | | Pending |
| TalkBack | TalkBack + Chrome (Android tablet) | | | Pending |
| 200 % and 400 % zoom, reflow at 320 px | Chrome, Firefox, Safari, Edge | | | Pending |
| WCAG 1.4.12 text spacing (bookmarklet and the theme's "extra wide" options) | Browsers | | | Pending (theme options checked visually on 4.5 and 5.3 during development) |
| Display settings in all modes, every browser | Two latest Chrome, Firefox, Safari, Edge | | | Pending |
| Reduced motion (system and personal setting) | macOS/iPadOS/Windows/Android settings | | | Pending |
| High contrast and Windows forced colours | Windows contrast themes | | | Pending |
| Tablets 10" and 12", both orientations | Android and iPadOS devices | | | Pending |
| FEDER notice with the approved emblem | Browsers and tablets | | | Pending (assets not provided) |
| Pictogram management and learner view | Keyboard, screen reader | | | Pending |
| Random sample pages (WCAG-EM step 3.e) | — | | | Pending |
