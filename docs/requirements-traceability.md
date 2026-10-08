# Requirements traceability

Each mandatory requirement of the definitive specification
(`PPT-EDICI-Lote1_Tema_Moodle_EVAGD.md`, lot 1, deliverable L1E01; not stored in
this repository) mapped to its implementation, its tests and its status.

Status values: **Done** (implemented and verified by the listed evidence),
**Partial** (implemented, some verification pending), **Pending** (needs EVAGD
pre-production, institutional assets, people or decisions that are not
available), **Process** (contractual or organisational, documented only).
"Local runs" are the runs recorded in the README's verification table.

## General requirements (§2.2–2.10)

| Ref | Requirement | Implementation | Tests / evidence | Status |
|---|---|---|---|---|
| 2.2 / A | Boost child theme | `config.php` (`parents = ['boost']`), `classes/output/core_renderer.php` | `lib_test::test_installed_metadata`, local installs on 4.5 and 5.3 | Done |
| 2.3 / 2.7 / 2.10.A | GPL v3 or later; LICENSE; README with installation and update instructions | `LICENSE`, file headers, `README.md`, `docs/deployment-evagd.md` | Package check (`dev/package.sh`) | Done |
| 2.3 / 2.7 | Code in ATE's GitHub organisation with continuous, incremental history | Incremental commits on `feature/chinijo-theme`, reviewed in pull request #1 | Pull request #1 and its commits | Done |
| 2.3 / 2.10.B.1 | Develop inside ATE's project template | Repository layout follows Moodle plugin conventions | — | Pending: the template is to be supplied with the start-up record |
| 2.3 / 2.10.B.2–4 | Only Moodle/Boost technologies (PHP, Mustache, AMD/ES); no SPA frameworks; GPL-compatible libraries without telemetry; SASS allowed | PHP, Mustache, SCSS, ES modules built with Moodle's Grunt; only third-party asset: Atkinson Hyperlegible (OFL) | `thirdpartylibs.xml`, `docs/third-party-licenses.md`, `make package` contents | Done |
| 2.3 / 2.9 | No external services or extra runtimes without written authorisation | No runtime external requests, no CDN, self-hosted font | Code review (`docs/security-report.md`) | Done |
| 2.5 / G.4 | Service levels and incident log | `docs/incident-and-patch-log.md` | — | Process |
| 2.6 | Continuous integration, periodic pushes | `.github/workflows/ci.yml`, `security.yml` | GitHub Actions on pull request #1 (21 checks pass); actionlint | Done |
| 2.7 | Technology transfer plan | `docs/transfer-plan.md` | — | Partial: sessions to be scheduled |
| 2.9 | moodle-plugin-ci checks: PHP syntax, PHPCS, PHPMD, Mustache, ESLint, Stylelint | CI and `make lint` | Local runs (4.5, 5.0, 5.1, 5.2, 5.3) | Done (PHPMD reports only Moodle-imposed callback signatures and test-class size, see security report) |
| 2.9 | Static security analysis report (tool, rule sets, no critical/high) | `dev/security-scan.sh`, `docs/security-report.md` | Semgrep 1.180.0: 0 findings; gitleaks: no leaks | Done |
| 2.9 / B | GDPR/LOPDGDD: only own display preferences; teachers cannot set them; no health data | `classes/local/preferences.php`, `classes/privacy/provider.php` | `preferences_test` (isolation, permissions, guests), `privacy/provider_test` | Done |
| 2.9 | ENS: do not degrade EVAGD's category | No new services, endpoints only through Moodle APIs | `docs/security-report.md` | Done (EVAGD measures to be communicated) |

## L1E01.B Normative framework

| Requirement | Implementation | Evidence | Status |
|---|---|---|---|
| RD 1112/2018, EN 301 549 v3.2.1, WCAG 2.2 AA; never worse than core | `docs/accessibility.md`, theme styles and components | axe-core in Behat on 4.5 and 5.3 (`accessibility.feature`); `docs/accessibility-audit.md` | Partial: automated checks pass; manual audit and EVAGD sample pending |
| WAI-ARIA 1.2 | Native elements first; `role="button"`/`aria-haspopup` on the control; status regions | axe, Behat | Partial: screen reader validation pending |
| GDPR (see 2.9) | — | — | Done |

## L1E01.C Accessibility

| Requirement | Implementation | Tests / evidence | Status |
|---|---|---|---|
| Full keyboard operation, logical order, no regressions | Native controls; core modal | `keyboard_submission.feature` (assignment submitted with Tab/Enter only), `preferences.feature` (Space, Escape, focus return) | Partial: NVDA/VoiceOver runs pending |
| Visible focus (2.4.7) not obscured (2.4.11) | `scss/chinijo/_focus.scss`, scroll padding in `_base.scss` | Manual check in browser (4.5, 5.3); axe | Partial: manual audit in EVAGD pending |
| Contrast 4.5:1 / 3:1; non-text 3:1 (1.4.3, 1.4.11) | Theme colours, high contrast mode | Computed ratios (`docs/accessibility.md`); axe in default and high contrast | Done for theme UI |
| Semantic markup, ARIA where needed | Templates with fieldset/legend, labelled landmarks, native `<progress>` | axe best-practice on theme regions | Partial: screen readers pending |
| Preferences panel: high contrast; ≥ 3 text sizes; legible typeface; letter, word and line spacing; WCAG 1.4.12 values; available on every page | `preferences_control`, `preferences_form`, `preferences.php`, `_preferences.scss`, `_contrast.scss` | `preferences_test`, `hook_callbacks_test`, `preferences.feature`, `accessibility.feature` (dialogue in 3 modes), screenshots | Done (checks in all required browsers/devices pending) |
| prefers-reduced-motion | `_motion.scss` (system setting and personal override) | Manual check | Partial: no automated test of motion |

## L1E01.D Usability and interface

| Requirement | Implementation | Tests / evidence | Status |
|---|---|---|---|
| Simplified design, minimum cognitive load, core features kept | Calmer defaults (`_base.scss`), nothing hidden | Screenshots; Behat on core flows | Partial: needs UAT with the target users |
| Targets ≥ 24×24 px (2.5.8) | 44 px buttons/controls in content and dialogues, 44 px radio options | `responsive.feature` measures ≥ 44×44 px at phone and tablet sizes with the largest text | Partial: real device testing pending |
| Pictograms for sections, activities or main actions; third-party licences; served from EVAGD; attribution; outside the GPL | `pictograms.php`, `classes/local/pictogram*.php`, `amd/src/pictograms.js`, credits, backup/restore | `pictograms_test`, `pictogram_form_test`, `pluginfile_test`, `backup_test`, `pictograms.feature` | Done for sections and activities. Pictograms for "main actions" (buttons) are not implemented. No pictogram is bundled. |
| Orientation: progress indicators and breadcrumbs | `course_progress`, `learning_path` ("Next" and "My path"), the activity bar ("Back to the course"), Boost breadcrumbs kept | `course_progress_test` (matches core on each branch), `learning_path_test`, `course_progress.feature` | Done |

## L1E01.E Learner autonomy

| Requirement | Implementation | Tests / evidence | Status |
|---|---|---|---|
| Non-intrusive visual reinforcement on completion; understandable errors; optional audio switchable in the panel | `completion_feedback.js` (after server confirmation: message of encouragement that does not take the focus, link to the next activity); "Sounds" setting in the panel (off by default; a short chime made by the browser); "Listen" on activity pages with on-device voices only; error styles; clear server-side messages | `course_progress.feature`, `preferences.feature`, `accessibility.feature` (encouragement), `pictogram_form_test` | Done (the "Listen" reading needs manual checks with real voices) |
| Primary actions distinguished and in predictable positions | Bold, thicker primary buttons; high contrast inverts them; positions are Moodle's own | Screenshots | Partial: UAT pending |
| Compatible with NVDA, VoiceOver, TalkBack and EVAGD's TTS | No interference with page semantics | — | Pending: manual tests and EVAGD's TTS list |

## L1E01.F Technical

| Requirement | Implementation | Evidence | Status |
|---|---|---|---|
| Child theme, minimal duplication | No copied Boost templates | `docs/architecture.md` | Done |
| Compatible with the target Moodle/EVAGD version | 4.5 LTS – 5.3 LTS | CI matrix (GitHub Actions on pull request #1); local lint and PHPUnit on all five branches, Behat on 4.5 and 5.3 | Partial: target version to be communicated; EVAGD pre-production pending |
| Responsive: 10" and 12" tablets both orientations, desktop; two latest Chrome, Firefox, Safari, Edge | Responsive Boost layout; wrapping index; navbar label hidden on narrow screens | `responsive.feature` in Chromium (phone, tablet portrait and landscape, no horizontal scrolling) | Partial: real devices and browser matrix pending |
| Code security (2.9) | See above | — | Done |
| FEDER visibility (Reg. (EU) 2021/1060 art. 47, annex IX); placement agreed with the contract manager | `classes/local/feder.php`, settings, template | `feder_test`, `hook_callbacks_test`, `feder.feature` | Partial: approved emblem, wording and placement pending |

## L1E01.G Warranty and EVAGD compatibility

| Requirement | Implementation | Status |
|---|---|---|
| G.1/G.2 12-month warranty on the delivered target version; later upgrades excluded | `docs/incident-and-patch-log.md`, `docs/release-and-reversibility.md` | Process (start date: acceptance record, not yet set) |
| G.3 Tests on EVAGD pre-production: SSO, EVAGD blocks/modules, language configuration, no conflicts | Checklist in `docs/deployment-evagd.md` §5 | Pending |
| G.4 Security patches in 5 working days; A/AA fixes within service levels; change log | `SECURITY.md`, `docs/incident-and-patch-log.md` | Process |
| G.5 Final tag, deployment documentation, reversibility record | `docs/release-and-reversibility.md` (template, unsigned) | Pending |

## L1E01.H Deliverables

| # | Deliverable | Where | Status |
|---|---|---|---|
| 1 | Source code in ATE's GitHub with history | This repository (pull request #1) | Done |
| 2 | Technical installation/configuration/upgrade manual and transfer plan | `docs/deployment-evagd.md`, `docs/compatibility.md`, `docs/transfer-plan.md` | Done (to be validated by the Consejería) |
| 3 | Teacher guide | `docs/teacher-guide.es.md` | Done |
| 4 | Accessibility audit report without A/AA errors | `docs/accessibility-audit.md` | Partial (automated part only) |
| 5 | Draft accessibility statement and data | `docs/accessibility-statement-draft.es.md` | Done as a draft; data pending |
| 6 | Code quality results and static security report | CI, `docs/security-report.md` | Done (local and GitHub Actions) |
| 7 | Warranty incident and patch log | `docs/incident-and-patch-log.md` | Process |
| 8 | Reversibility record | Template in `docs/release-and-reversibility.md` | Pending (signed at closure) |

## L1E01.I Acceptance tests

| Test | Status |
|---|---|
| Accessibility audit (automated + manual, WCAG-EM sample) without theme A/AA errors | Partial: automated part done on a generic Moodle; manual and EVAGD pending |
| Keyboard-only navigation and assignment submission; same with NVDA and VoiceOver | Partial: keyboard automated (Behat); screen readers pending |
| Tablets 10" and 12", Android and iPadOS, both orientations | Pending |
| Preferences panel, reduced motion and FEDER emblem in the required browsers and devices | Partial: Chromium automated; others pending; FEDER assets pending |
| EVAGD pre-production with SSO and active modules | Pending |
