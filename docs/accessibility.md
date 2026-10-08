# Accessibility

Target: **WCAG 2.2 level AA** for everything Chinijo renders or changes, as
required by **EN 301 549 v3.2.1** (clause 9 for web content) and Spain's **Royal
Decree 1112/2018**, with **WAI-ARIA 1.2** used only where native HTML is not
enough. The theme must never make Moodle core less accessible than Boost.

Automated tools do not prove conformance. Results of each audit, automated and
manual, are recorded in `docs/accessibility-audit.md`; manual checks stay
**pending** until a person has done them.

## Method

Based on W3C **WCAG-EM 1.0**:

1. **Scope**: pages rendered with Chinijo on EVAGD (pre-production as the
   reference), for learners, teachers and visitors who are not logged in; theme
   components (display settings control, dialogue and page, toolbar, progress,
   pictograms and credits, completion feedback, FEDER notice, pictogram management
   pages). Third-party content and other plugins are out of scope but reported
   when they affect a sampled page.
2. **Representative sample** (structured sample, WCAG-EM step 3): login page (not
   logged in), dashboard, course list, course page with progress and pictograms,
   section view, an activity (page), an assignment submission (form and success),
   a form error state, display settings dialogue in each mode, stand-alone display
   settings page, pictogram management and edit form, FEDER notice, mobile/tablet
   viewport, Spanish interface. Random sample: two further activity pages chosen in
   pre-production.
3. **Audit**: automated (axe-core through Moodle's Behat step, WCAG 2.0/2.1/2.2 A
   and AA plus best practices) on every sampled page and state, then manual review
   with the checklist below, screen readers and devices.
4. **Report**: per criterion, pass / fail / not applicable, with evidence, the
   affected page, whether the issue is attributable to the theme, core or a
   third party, and its remediation.

## Automated checks

- `tests/behat/accessibility.feature` (`@accessibility`, run by `make test-a11y`
  and by CI on 4.5 and 5.3) uses Moodle's step
  `the page should meet accessibility standards with "best-practice" extra tests`
  (axe-core 4.10 on 4.5, 4.13 on 5.3) on the sampled pages, and on the dialogue in
  default, high contrast + huge text + legible font, and high contrast + large text.
- Keyboard: `keyboard_submission.feature` reaches and submits an assignment with
  Tab and Enter only, and fails if any control cannot be reached (focus trap);
  `preferences.feature` opens the dialogue with Space and checks that focus
  returns to the control after Save and after Escape.
- Contrast of the theme's own colours (computed, see below).

## Theme colours and contrast (WCAG 1.4.3, 1.4.11)

Ratios computed with the WCAG 2.x relative luminance formula (2026-10-08).

| Pair | Ratio | Use |
|---|---|---|
| `#1d2125` on `#ffffff` | 16.2:1 | Text, focus outline |
| `#0f6cbf` (Boost primary) on `#ffffff` | 5.36:1 | Links, primary buttons (white text on it: 5.36:1) |
| `#357a32` progress fill vs `#e9ecef` track | 4.45:1 | Progress bar (non-text, needs 3:1); fill on white 5.27:1 |
| `#6a737b` boundaries on `#ffffff` | 4.83:1 (4.58:1 on Boost's `#f8f9fa`) | Borders of cards, radio options, progress |
| High contrast: `#000` on `#fff` | 21:1 | Text |
| High contrast: `#00009c` links on `#fff` | 14.19:1 | Links (also underlined) |
| High contrast: `#4b0082` visited on `#fff` | 12.95:1 | Visited links |

## Checklist for theme-controlled UI (WCAG 2.2 A/AA)

| Criterion | How Chinijo addresses it | Evidence |
|---|---|---|
| 1.1.1 Non-text content | Pictograms need a text alternative (form validation); decorative copies in the course index use `alt=""`; the "Aa" glyph is `aria-hidden`; the EU emblem is only shown with its alternative. | PHPUnit, Behat |
| 1.3.1 Info and relationships | Radio groups in `<fieldset>` with `<legend>`; tables with caption and `scope`; landmarks (`aside` with labels); native `<progress>` labelled by its text. | Behat axe |
| 1.3.2 Meaningful sequence / 2.4.3 Focus order | Nothing is reordered with CSS positioning; pictograms are inserted before names in DOM order. | Keyboard scenario |
| 1.4.1 Use of colour | Links in text underlined; selected options bold with thicker border; errors with a bar and bold text; high contrast inverts primary actions. | Manual |
| 1.4.3 / 1.4.6 / 1.4.11 Contrast | See table above; checked in all modes by axe. | Behat axe |
| 1.4.4 Resize text / 1.4.10 Reflow | rem-based scaling up to 150 %; course index names wrap; tested at 200 %/400 % zoom (manual, pending in pre-production). | Manual |
| 1.4.12 Text spacing | The "extra wide" options apply exactly the WCAG values; layout checked without loss of content. | Manual + screenshots |
| 1.4.13 Content on hover or focus | No new hover content. | n/a |
| 2.1.1 Keyboard / 2.1.2 No keyboard trap | Native controls; the dialogue (core/modal) contains focus while open and is closed with Escape. | Behat keyboard scenarios |
| 2.2.2 Pause, stop, hide | No moving content added; toasts are core's, short and polite. | Manual |
| 2.3.1 Three flashes | No flashing content. | n/a |
| 2.3.3 Animation from interactions (AAA, supported) | `prefers-reduced-motion` honoured; "Reduce animations" forces it. | Manual |
| 2.4.7 Focus visible / 2.4.13 Focus appearance (AAA, supported) | 3 px dark outline with a light gap on every focusable element; 4 px in high contrast; `forced-colors` support. | Manual |
| 2.4.11 Focus not obscured | `scroll-padding` for the fixed navbar and sticky footers. | Manual |
| 2.5.3 Label in name | Control's accessible name equals its visible label ("Display settings"). | Behat |
| 2.5.8 Target size (minimum) | Buttons and controls in content and dialogues ≥ 44 px; radio options ≥ 44 px. | Manual |
| 3.1.1 / 3.1.2 Language | Moodle sets `lang`; all theme strings are translated (en, es). | PHPUnit, Behat |
| 3.2.1 / 3.2.2 On focus / on input | Choosing an option previews it but nothing is saved or navigated until Save. | Behat |
| 3.3.1 / 3.3.3 Error identification and suggestion | Moodle form errors; server-side messages explain what is wrong ("The image must be a PNG, JPEG or WebP file"). | Behat |
| 4.1.2 Name, role, value | Native elements; the navbar link gets `role="button"` and `aria-haspopup="dialog"` when it opens the dialogue. | Behat axe |
| 4.1.3 Status messages | Save, reset and completion feedback use core's toast (`role="status"`, polite) or a `role="status"` region. | Behat |

## Manual testing protocol (to be run in EVAGD pre-production)

For each sampled page, with the theme defaults and with each display setting:

1. **Keyboard only** (Tab, Shift+Tab, Enter, Space, Escape, arrows): every control
   reachable, visible focus, logical order, no trap; complete the path "log in →
   course → assignment → add submission → save" without a mouse.
2. **Screen readers**: NVDA (latest) with Firefox and Chrome on Windows; VoiceOver
   with Safari on macOS and iPadOS; TalkBack with Chrome on Android. Repeat the
   keyboard path; check the dialogue's name, groups and announcements, the
   progress text, pictogram alternatives and the FEDER notice.
3. **Zoom and reflow**: 200 % and 400 % (320 CSS px width); text-spacing
   bookmarklet; no loss of content or function.
4. **Devices**: 10" and 12" tablets, Android and iPadOS, portrait and landscape;
   two latest stable Chrome, Firefox, Safari and Edge.
5. **High contrast and forced colours**: Chinijo's high contrast and Windows
   contrast themes.

Record each run (date, tester, tool versions, result, issues) in
`docs/accessibility-audit.md`.

## Known limits

- Moodle core and third-party plugins may have their own issues; they are reported
  but not fixed in the theme unless the fix is safe and local to the theme.
- Teacher content (texts, images, embedded media) must be made accessible by its
  authors: see `docs/teacher-guide.es.md`.
- Pictograms are images chosen by teachers; their clarity and licensing are the
  teacher's responsibility.
