# Implementation prompt — Chinijo, an accessible Moodle theme

> Paste this entire document into an AI coding agent with access to the local checkout of `ateeducacion/moodle-theme_chinijo`. It is an implementation brief, not an instruction to merely describe a possible solution.

## Role and mission

You are the lead Moodle LMS theme engineer, accessibility engineer, test architect, and CI/CD maintainer for **Chinijo**.

**Repository:** https://github.com/ateeducacion/moodle-theme_chinijo  
**Moodle component:** `theme_chinijo`  
**Theme directory:** `chinijo` (`theme/chinijo` inside a Moodle installation)  
**Display name:** Chinijo  
**Parent:** Moodle's core **Boost** theme  
**License:** **GPL-3.0-or-later**  
**Languages:** English (`en`, source language) and Spanish (`es`), including all user-facing strings  
**Owner / intended deployment:** Área de Tecnología Educativa (ATE), Consejería de Educación del Gobierno de Canarias; EVAGD (Entorno Virtual de Aprendizaje de Gestión Distribuida).

**Objective:** implement a maintainable, secure, bilingual, responsive, low-cognitive-load Boost child theme aimed particularly at early Primary Education learners and learners with specific educational support needs (NEAE). Deliver the working theme, development environment, continuous integration, test suite, coverage, reproducible Moodle Playground blueprint, documentation, and release packaging. This is a real Moodle plugin, not a mockup or SPA.

**Compatibility:** Moodle **4.5 LTS, 5.0, 5.1, 5.2, and 5.3 LTS** (4.5 and all subsequent released versions through 5.3). Do not claim compatibility without testing it. The plugin's Moodle minimum version must be set to the **verified** Moodle 4.5 core build number; do not guess it. Avoid depending on newer APIs without a documented, tested compatibility strategy.

**Repository state:** inspect it first; it may be empty. Preserve any existing source, configuration, license, decisions, and workflows. Implement the necessary files rather than returning only architectural prose. Do not modify Moodle core, other repositories, EVAGD production, or any GitHub PR/issue discussion. Never force-push. Work incrementally in a `feature/...` branch where Git operations are authorized; create meaningful commits if authorized, but do not push or open a PR unless explicitly authorized by the operator. All code, comments, docblocks, PR titles/descriptions, and commit messages must be in English; user-facing translations must cover English and Spanish. Use spaces, not tabs, for PHP and JS source; Makefile recipe tabs are required by Make itself.

## 1. Source-of-truth policy and required research

**The definitive requirements are in `PPT-EDICI-Lote1_Tema_Moodle_EVAGD.md`.** If the file is available, read it completely before implementing; link each requirement to a design, test and documented evidence. A prior PDF (`PPT-EDICI-Lote1_Tema_Moodle_EVAGD (2)(1).pdf`) may be consulted only for non-conflicting background. If sources disagree, **the Markdown wins**. Do not reintroduce superseded conditions from the PDF (for example, the old 24-month warranty or teacher-controlled per-student visual preferences). This prompt is self-contained if the files are not available to the coding agent. Do not publish an internal procurement document to a public repository without authorization.

Research and validate *current upstream behavior* before choosing an API or template override:

1. Consult **official Moodle developer documentation** at https://moodledev.io/ (particularly 4.5 and 5.3 theme plugins, Boost inheritance, Mustache, SCSS, AMD/ES6 modules, privacy, security, PHPUnit, Behat, and accessibility testing).
2. Use **Context7**: resolve the relevant Moodle library and query focused topics; start with `/moodle/devdocs` or `/websites/moodledev_io`, plus `/moodle/moodle` when appropriate. Verify version-sensitive answers against Moodle's actual source. If Context7 is unavailable, state this in the research notes and use official sources rather than fabricating a lookup.
3. Inspect the **exact corresponding branches** of https://github.com/moodle/moodle (`MOODLE_405_STABLE`, `MOODLE_500_STABLE`, `MOODLE_501_STABLE`, `MOODLE_502_STABLE`, `MOODLE_503_STABLE`), especially Boost `config.php`, `lib.php`, layouts, Mustache templates, `UPGRADING.md`, core output APIs, and relevant PHP version metadata. From Moodle 5.1 onward the public web-root layout differs; locate code rather than assuming a fixed directory.
4. Examine real third-party theme implementations and CI: https://github.com/gjbarnard/moodle-theme_adaptable and https://github.com/willianmano/moodle-theme_moove; adopt sound patterns, not their unreviewed workarounds or outdated CI pinning.
5. Base CI commands on **Moodle HQ's** maintained https://github.com/moodlehq/moodle-plugin-ci and its current `gha.dist.yml` and docs: https://moodlehq.github.io/moodle-plugin-ci/ . Check actual command names/options for the installed version. Pay particular attention to compatibility fixes for Moodle 5.3.
6. Read **ATE's own Moodle Playground**: https://github.com/ateeducacion/moodle-playground and https://ateeducacion.github.io/moodle-playground/docs/blueprints/reference/ , including its JSON schema, examples, runtime/versions and PR-preview Action.
7. Read the documentation and current release tags of **`erseco/alpine-moodle`**: https://github.com/erseco/alpine-moodle and https://erseco.github.io/alpine-moodle/ , particularly theme mounts, automatic core synchronization, PHP-version-specific image tags, environment variables and experimental blueprint support.
8. Compare other public `ateeducacion` repositories for sensible `Makefile`, `AGENTS.md`, test and workflow conventions, but do not copy tools inappropriate for a Moodle plugin.
9. Maintain `docs/research-and-decisions.md`: date, exact versions/branches checked, relevant upstream URLs, choices and alternatives, known compatibility breaks, and any material uncertainty. Do not invent citations or claim tests were executed when they were not.

Known version pitfalls that need explicit regression tests:

- Moodle **4.5 LTS** works with PHP 8.3; do **not** use PHP 8.4 for its required test job.
- Moodle **5.3 LTS** supports PHP 8.3 and 8.4; local `erseco/alpine-moodle` 5.3 tags use PHP 8.4. Keep tested PHP/Moodle combinations valid.
- Moodle **5.1+** places web-accessible Moodle content under `public/`. A plugin repository still has its own plugin root; its *installation mount path* changes across core layouts.
- Boost's **5.3** upgrade notes describe Bootstrap module import changes, CSS custom properties, optional color modes/dark mode, and theme-template changes across 5.0–5.3. Avoid importing private Bootstrap internals or copying obsolete Boost layouts. Prefer public core abstractions and a minimal override surface.
- Do not infer that PHPUnit/Behat/coverage can run merely because the Moodle website starts. Test initialization and browser drivers are separate concerns.

## 2. Architecture and repository layout

Implement a **native Boost child theme**:

- `$plugin->component = 'theme_chinijo';` in `version.php`; verified `requires`, release and maturity metadata.
- `$THEME->name = 'chinijo';` and `$THEME->parents = ['boost'];` in `config.php`, plus only the other properties actually needed and supported on the target branches.
- Native Moodle PHP, Mustache templates, SCSS/Sass, Moodle AMD/ES6 modules; compiled JS assets compatible with Moodle's asset pipeline. Node/Composer may be used as **development/build tooling**, but introduce no new runtime service or SPA framework (no Angular, React, Vue, Svelte, Next.js, etc.). Avoid external CDNs, fonts, analytics, telemetry, tracking, runtime network requests and unapproved third-party services.
- Inherit Boost layouts/renderers where possible. Override the smallest necessary Mustache template or renderer, document every override and compare it against every target Moodle branch. Use Moodle's supported theme callbacks/hook points. No global patching of core PHP, database tables or vendor source.
- Use Moodle coding standards and PHP_CodeSniffer rules intended for **Moodle**, not WordPress coding rules. Follow Moodle namespace, class, privacy, validation, capability, language-string and plugin-file conventions. Write descriptive PHPDoc. Keep comments useful and in English.
- Keep UI logic separate from templates, data access separate from rendering, and JS modules narrowly scoped. No inline business logic in Mustache and no unnecessary large template copies.
- Put static assets in Moodle-supported directories; use `pix/` as appropriate, self-host fonts only with a redistributable compatible license, document all asset attribution.
- If course-specific pictogram metadata genuinely needs persistence, investigate **documented Moodle plugin extension points** and the theme's permissible `db/install.xml`, `db/upgrade.php`, File API and capability structure. Do not assume that core offers section/activity custom fields or that a theme can transparently extend every activity form. Do not modify core tables or pretend that an unsupported hook exists.
- When a requirement cannot be implemented correctly inside a child theme without adding another plugin, document the architectural blocker and the minimum proposed companion plugin. **Do not silently add a companion plugin** or substitute a fragile core patch. Implement all feasible features inside the authorized theme.

Start with an appropriate plugin-root structure, adapting it to verified Moodle conventions:

```text
.
├── .github/
│   ├── workflows/                 # plugin CI, accessibility, preview, release
│   └── ...                        # security / dependency automation if useful
├── AGENTS.md
├── LICENSE
├── README.md
├── README.es.md                 # optional but strongly preferred
├── CHANGELOG.md
├── CONTRIBUTING.md
├── SECURITY.md
├── Makefile
├── docker-compose.yml
├── .env.example
├── .gitignore
├── .editorconfig
├── blueprint.json              # portable Moodle Playground scenario
├── version.php
├── config.php
├── settings.php                 # when settings exist
├── lib.php                      # only for supported callbacks
├── classes/
├── lang/
│   ├── en/theme_chinijo.php
│   └── es/theme_chinijo.php
├── amd/
│   ├── src/
│   └── build/                   # include generated runtime artifacts as required
├── scss/
├── templates/
├── pix/
├── tests/
│   └── behat/
├── docs/
│   ├── screenshots/
│   ├── architecture.md
│   ├── compatibility.md
│   ├── accessibility.md
│   ├── teacher-guide.es.md
│   ├── deployment-evagd.md
│   ├── transfer-plan.md
│   ├── security-report.md
│   ├── accessibility-audit.md
│   ├── accessibility-statement-draft.es.md
│   ├── release-and-reversibility.md
│   ├── incident-and-patch-log.md
│   ├── requirements-traceability.md
│   └── research-and-decisions.md
└── ...                         # supporting dev-only tool configuration
```

This is a **candidate**, not an instruction to create empty files or invent nonexistent features. Include `db/`, extra scripts, tests or config only when they have a purpose. Exclude development-only tooling, secrets, Docker state, `node_modules`, reports and temporary files from production ZIP releases. Include built AMD assets required at runtime. Keep the plugin itself installable as `theme/chinijo` and package its contents with the correct archive directory structure.

## 3. Functional and UX requirements (definitive specification)

### 3.1 Users, contexts and visual approach

Serve EVAGD's pupils, teachers and families, especially learners in early Primary Education and learners with NEAE. Build a visually clear, predictable, uncluttered experience with consistent navigation, readable typography, generous spacing and large interactive elements. Default to accessible colors, sober styling and the minimum cognitively necessary content. Do **not** guess institutional brand colors, logos or official pictograms: make relevant assets/colors configurable and use neutral, legally reusable defaults pending approval.

The theme must preserve core functionality, Moodle capability checks, activity completion/assignment submission, course index, navigation, grades, messaging, course editing and the display of existing EVAGD blocks/plugins. A simplified interface must never hide a critical action permanently; if optional details are collapsed, make them discoverable, keyboard operable and accessible to assistive technologies. Avoid unnecessarily changing teachers' administrative/editing workflows.

### 3.2 Accessible preferences panel

Create a small, consistently discoverable preferences control on the pages where the theme is active. The panel itself must be completely keyboard and screen-reader accessible, with appropriate focus management, labels, announcements and an accessible reset-to-default action. Implement at least:

- **High-contrast mode**, consistent across components and not based on color alone.
- **At least three meaningful font-size choices**, including a default, without content truncation or navigation breakage.
- A choice of **high-legibility typeface**. An optional dyslexia-oriented font may be offered only after checking evidence, licensing and load cost; do not claim a font cures dyslexia.
- User-adjustable **letter, word and line spacing**, allowing WCAG 2.2 **1.4.12** text-spacing values to be met without loss of content/functionality: line-height at least 1.5, paragraph spacing 2× font size, letter spacing 0.12×, word spacing 0.16×.
- System **`prefers-reduced-motion`** behavior. Reduce or eliminate nonessential animations; never make meaning depend on motion. If optional audio feedback is added, make it off by default and user-disableable.

**Every individual controls only their own visual preferences**; teachers must not assign accessibility settings to an individual pupil. Use Moodle's native per-user preference APIs where appropriate; guests may use safe session-only/default behavior. Store only display choices, not diagnoses, medical categories or inferred special-needs profiles. Protect preference writes with Moodle authentication/session/security patterns; do not expose one user's preferences to another. Account for Boost 5.3's experimental native color mode without contradictory `data-bs-theme` behavior. Verify settings survive ordinary navigation and honor language switching.

### 3.3 Course navigation and pictograms

- Provide clear breadcrumbs/course location, orientation and progress indicators using **actual Moodle course-completion/activity-completion information**. Never display a made-up completion percentage or a success state before Moodle confirms it.
- Visually distinguish primary actions such as continue, submit and confirm and position them predictably, while retaining semantic markup, text labels and non-color cues.
- Let **authorized teachers** associate pictograms with important course sections, activities or navigation actions via a documented, accessible workflow. Use capability checks, course-context scoping, appropriate validation and Moodle's File API for locally stored files; never pull pictograms from an unauthorised external host at runtime. Supply accessible names/alt text and a text-only fallback. Avoid arbitrary inline SVG/script upload; constrain file types and sanitize any permitted SVG.
- Treat third-party pictograms as **separately licensed** resources. ARASAAC pictograms may be CC BY-NC-SA 4.0 and must **not** simply be re-licensed under the theme GPL. Do not bundle third-party pictograms without validating redistribution rights, attribution, non-commercial restrictions and project authorization. Make licensing/attribution visible where appropriate.
- Support the absence of pictograms gracefully. Existing EVAGD course content must work without migration, custom data, external APIs or special blocks.

### 3.4 Feedback and teaching support

Use subtle visual confirmation only for real, observable completion events. Show comprehensible, non-technical error feedback, preserving core Moodle error semantics and helpful remediation. Avoid flashing, autoplay, forced sound, time-dependent interactions and overstimulation. Do not interfere with NVDA, VoiceOver, TalkBack or TTS systems/plugins available at EVAGD; do not ship a paid/external TTS service. Provide a teacher guide explaining accessible course structure, concise labels, good pictogram use, alternative text, and how to avoid inaccessible content.

### 3.5 FEDER / EU visibility

Provide an administrator-configurable and accessible way to show the **European Union emblem** and the relevant **FEDER** acknowledgement in approved site areas when legally required (Regulation (EU) 2021/1060, Article 47 and Annex IX). Ensure accessible labeling and responsive sizing. Do not invent an official emblem or statement; document exactly which approved institutional assets, placement and wording must be supplied/confirmed by ATE before production. The feature must degrade safely if assets have not been provided.

## 4. Accessibility and public-sector requirements

Implement and test against the following baseline from the definitive specification:

- **WCAG 2.2 Level AA**, as applicable to theme-controlled UI.
- **EN 301 549 v3.2.1**, or the applicable harmonized edition identified for the procurement, and Spain's **Royal Decree 1112/2018**.
- **WAI-ARIA 1.2**, using native semantic HTML first and ARIA only when necessary.
- **GDPR/LOPDGDD**, as scoped to personal visual-display preferences and the hosting EVAGD context; do not add independent processing of sensitive data.
- Keyboard-only operation, logical focus order and **no focus traps**, with visible focus indicators (WCAG **2.4.7**) that are not hidden behind fixed content (WCAG **2.4.11**).
- Text contrast **4.5:1** normal and **3:1** large/bold; meaningful non-text/UI component contrast **3:1** (WCAG **1.4.3** and **1.4.11**), evaluated in all theme modes and interaction states.
- Touch target size meeting WCAG **2.5.8** (**24×24 CSS px** minimum where the criterion applies), with larger practical targets for young children when feasible.
- Legible text at magnification, CSS reflow, tablet landscape/portrait, no broken forms, modal content or page navigation; test 200%/400% zoom and text-spacing overrides.
- Reduced motion and predictable interaction.
- Actual screen-reader validation (NVDA + VoiceOver), TalkBack as relevant, and real-device or sufficiently representative Android/iPadOS tablet testing; desktop Chrome, Firefox, Safari and Edge, especially the two newest stable versions at acceptance time.

**Automated scans do not prove WCAG compliance**. Use axe-core and/or WAVE *plus manual review*. Follow the W3C **WCAG-EM** approach to choosing representative pages. For theme-owned UI, the release gate is **no known attributable Level A/AA failures**; failures, warnings, exclusions and core/third-party issues must be evidenced and classified, not silently suppressed. Do not claim legal certification until independent institutional acceptance is completed.

An audit should include at least: login/guest page, dashboard, course listing, course view, section navigation, an activity view, an assignment submission, a form/error state, the preferences panel in all modes, teacher pictogram management, mobile views, and a language switch. Include the full keyboard assignment-submission path and repeat relevant flows with NVDA and VoiceOver.

## 5. Internationalisation

- The theme has fully functional **English and Spanish** Moodle language packs: `lang/en/theme_chinijo.php` and `lang/es/theme_chinijo.php`.
- English is the source language. Put all visible text, accessibility names, errors, validation, preferences, settings descriptions and relevant help in translated strings; use Moodle's `get_string()` and template string conventions. No hard-coded UI messages in PHP/JS/Mustache and no ad-hoc translation JSON framework.
- Provide a test that compares the key sets and meaningful placeholders between languages, checks no accidental missing keys, and exercises the preference UI/course navigation under both languages. Verify correct escaping, apostrophes, grammatical plurality and Unicode/diacritics.
- Respect Moodle's language selection/fallback and EVAGD language configuration; do not force a locale from the theme. Include strings for screen readers and generated notifications.

## 6. Security, maintainability and privacy

- Validate and sanitize *inputs*, escape *output in its context*, use Moodle core forms, CSRF `sesskey` checks, `require_login()`, `require_capability()`, context checks and the Moodle File API whenever applicable. Enforce authorization on the **server**, never just by hiding teacher buttons in CSS/JS.
- Avoid direct SQL unless required and use Moodle DML with placeholders and appropriate context/ownership checks. Prevent reflected/stored XSS, file exposure, path traversal, IDOR, arbitrary uploads, unsafe template interpolation and insecure AJAX endpoints.
- If AJAX/external functions are necessary, use documented APIs, return structured errors, correctly declare capability requirements and cover negative permission tests. Avoid embedding secrets or environment-specific internal hostnames.
- No trackers, external assets or new production server services. CI scanners and local development tools are not production dependencies.
- Respect preservation/attribution of Moodle/Boost GPL headers in copied/modified files. License original code and self-authored assets as GPL-3.0-or-later; track exceptions and third-party content in `docs/third-party-licenses.md` if needed. Do not distribute a font or pictogram with an unverified license.
- Keep documentation of significant design decisions, compatibility changes, migration/upgrade steps and rollback. Make plugin upgrades safe, repeatable and reversible using supported Moodle procedures.
- Provide a dedicated *static security analysis* job/report (e.g., suitable audited Semgrep PHP/JS OWASP/CWE rules; verify chosen rules actually support these languages), plus dependency and secret checks. PHPMD/PHPCS alone do **not** substitute for security analysis. No known high/critical findings in introduced code; document exact tool versions, rule sets, triage and false positives.

## 7. Local development environment — Docker and Make

Deliver a **working**, fast local stack, not unverified example YAML. Use **`erseco/alpine-moodle`** for the Moodle web container and **PostgreSQL 17** as the default persistent development database (add MariaDB only when useful for matrix testing). Pin verified image versions; never rely on a floating `latest` tag for CI/reproducibility. The runtime version must be selectable by a simple documented variable/target:

- Default developer target: **Moodle 5.3 LTS**, matching its supported PHP runtime in the selected `erseco/alpine-moodle` image (PHP 8.4 in current 5.3 images).
- Legacy check: **Moodle 4.5 LTS**, matching the 4.5 image/PHP 8.3.
- Permit opting into the other supported releases and test the version mapping.

Use a **bind mount of this checked-out repository as the live Chinijo theme**, without copying all of Moodle into this repository. Determine whether the target installation directory is `.../theme/chinijo` or `.../public/theme/chinijo` from the selected Moodle version/image. Verify it by inspecting `erseco/alpine-moodle`, not guessing. Handle permissions under the image's non-root user and do not overwrite the local source when Moodle's code sync runs. Persist moodledata and database in named volumes; document the `SYNC_MOODLE_CODE` and `EXTRA_PLUGIN_PATHS` implications. Expose a configurable localhost HTTP port (e.g., 8080). No uncommitted real credentials; supply `.env.example` with safe **development-only** placeholders and ignore `.env`.

If the alpine-moodle blueprint runner is used locally, read its **documented supported subset** and avoid sending it browser-only steps. Do not let a remote `installTheme` download overwrite the locally bind-mounted theme. It may be more reliable to use a tiny local bootstrap script/verified CLI to install and activate the mounted plugin and a separate portable scenario blueprint. Document the difference.

Provide at least the following **real targets** and a useful `make help`; all must be non-interactive and return meaningful exit codes:

| Make target | Contract |
|---|---|
| `make up` | Start the selected Moodle + DB, install/upgrade if required, activate Chinijo; print local URL only after readiness. |
| `make down` | Stop stack without deleting data. |
| `make logs` | Tail actionable service logs. |
| `make shell` | Open a shell in the Moodle container. |
| `make install` | Idempotently install/upgrade and activate the mounted theme. |
| `make seed` | Populate reproducible *synthetic* users, courses and test scenarios. |
| `make lint` | Run all relevant syntax, Moodle PHPCS, PHPDoc, PHPMD, Mustache, JS and SCSS checks plus blueprint validation; no deceptive green result. |
| `make fix` | Apply safe supported auto-fixers (Moodle PHPCBF, ESLint/stylelint fixes where available); describe manual-only checks; never rewrite vendor/generated code indiscriminately. |
| `make test` | Run the core practical automated developer test suite (PHPUnit and required integration/accessibility smoke tests), failing on actual errors. |
| `make test-unit` | Run just the plugin's fast unit tests. |
| `make test-behat` | Run real Behat scenarios in a configured browser/test database. |
| `make test-a11y` | Run axe/Behat automated accessibility tests, with artifacts/reports. |
| `make coverage` | Enable appropriate coverage driver, run tests and produce human-readable + machine-readable reports with meaningful statistics. |
| `make test-matrix` | Run a documented compatibility test across required Moodle versions (or a containerised/CI wrapper with a truthful explanation). |
| `make validate-blueprint` | Validate `blueprint.json` against the actual Moodle Playground schema and validate supported steps. |
| `make package` | Build and inspect a correctly structured, clean installable theme ZIP. |
| `make screenshot` | Capture a genuine deterministic screenshot from a running Chinijo environment, safely using synthetic data. |
| `make reset` | **Explicitly warn** and require an opt-in before removing development volumes/data. |

On macOS, keep host requirements minimal: Docker Compose, Make and optionally Homebrew tooling; use portable POSIX shell where possible. Docker uses Alpine; use `/bin/sh`/`ash` rather than assuming Bash in that container. If `erseco/alpine-moodle` lacks dev tools/coverage extensions, use an appropriate **development-only** testing service or Moodle HQ's official CI runner instead of installing and shipping them into the production web image. Distinguish "developer smoke test" from complete Moodle test infrastructure.

## 8. Automated testing and coverage

Write tests *alongside* functionality. Do not create empty tests or assert only that a class exists. Use Moodle-compatible PHPUnit conventions, version-appropriate `advanced_testcase`, DB reset patterns, Behat Gherkin and Moodle's own accessibility steps where supported. Where JS logic is sufficiently complex, add executable JS unit tests with a supported tool; use real-browser integration tests for DOM/ARIA and visual behaviors. Avoid tests whose only purpose is to inflate coverage.

At minimum, cover:

1. Installation, version metadata, language key parity, settings defaults and plugin upgrades.
2. Visual-preference validation, permitted values, storing/retrieving per-user values, reset, persistence, isolation between users, unauthorized write rejection and guest behavior.
3. Contrast/modes, font scaling, spacing, reduced motion and interaction with native Boost color modes.
4. Keyboard opening/closing of the panel, focus containment/restoration, visible focus and screen-reader names.
5. Pictogram authorization for teacher/student/guest roles; course scoping; upload validation, File API ownership, rendering, alt text and failure fallback.
6. Course index/breadcrumbs, true activity-completion state, an assignment submission flow and preservation of normal editing/navigation.
7. Spanish/English user-visible output and escaped, translated messages.
8. Responsiveness, key viewport/orientation breakpoints and touch targets.
9. Basic performance/no unexpected remote requests, no content loss at 200%–400% zoom or WCAG text spacing.
10. Tests against each target Moodle branch, especially known 5.1+ layout and 5.3 Bootstrap/Boost regressions.

Use Moodle Behat `@javascript @accessibility` scenarios and the documented step `And the page should meet accessibility standards with "best-practice" extra tests` when it is supported by the selected Moodle branch. Inspect actual available step definitions on each version. Include a real end-to-end keyboard-only workflow for submission. Maintain manual QA instructions and evidence for screen readers and physical tablets; don't pretend CI simulates NVDA/VoiceOver hardware perfectly.

**Coverage is a real measurement, not a vanity badge:**

- Provide PHPUnit code coverage for executable **custom PHP logic** using PCOV/Xdebug supported by the runner; generate terminal summary, Clover XML (`coverage.xml`), and an HTML report locally where technically supported. Report the *correct scoped denominator*. Explain that SCSS/Mustache layout quality and manual accessibility are not measured by PHP line coverage.
- Target **at least 80% line coverage of meaningful, coverable project-owned PHP logic**, with justified exclusions for generated glue/entry points. If the plugin contains almost no executable PHP, disclose that and replace artificial percentage claims with verified Behat/visual/a11y coverage. Measure JS logic separately if substantial. Never fabricate percentages or force a green metric by excluding core logic.
- Implement an enforceable sensible threshold for eligible PHP only after verifying the reporting tool and an actual baseline; document any exceptions. Fail when tests fail, and fail on material coverage regression in meaningful modules. Coverage artifacts must not include secrets or personal data.
- Test negative security cases and edge conditions as carefully as happy paths.

## 9. GitHub Actions and continuous integration

Create professional **GitHub Actions** workflows with separate/clear jobs and meaningful required checks. Start from the current Moodle HQ `moodle-plugin-ci` GitHub Actions template rather than blindly copying an old theme workflow. Keep permission scopes minimal (`contents: read` by default), pin supported action versions or immutable SHAs where feasible, scope caches safely, do not expose secrets to untrusted fork PR code, and make parallel matrix failures visible (`fail-fast: false`). At a minimum:

### 9.1 Core CI on push and PR

- Triggers: push to appropriate branches, pull requests into main, and optional manual dispatch.
- **Fast lint/validation:** PHP syntax, Moodle PHPCS, PHPDoc, PHPMD, plugin validation, Mustache lint, Moodle's supported Grunt/ESLint/Stylelint checks (as actually exposed), JSON schema verification, shell/YAML checks, language key parity and packaging consistency.
- **Functional matrix:** run at least the following tested combinations, adding verified variations if useful:

| Moodle branch | PHP (baseline) | Notes |
|---|---|---|
| `MOODLE_405_STABLE` | 8.3 | Previous LTS; do not use PHP 8.4. |
| `MOODLE_500_STABLE` | 8.3 | Transitional Bootstrap behavior. |
| `MOODLE_501_STABLE` | 8.3 | `public/` layout; test installation/rendering. |
| `MOODLE_502_STABLE` | 8.3 | Check removed Boost templates. |
| `MOODLE_503_STABLE` | 8.4 | New LTS and Boost/Bootstrap color/module behavior. |

On **both 4.5 and 5.3 LTS**, include real PHP/unit + theme Behat + automated accessibility checks as blocking jobs. On intermediate versions, at minimum install/smoke, static checks and relevant unit/integration tests; expand coverage when practical. Test **PostgreSQL 17** and **MariaDB 11** on the main LTS versions when supported; use current `moodle-plugin-ci` DB setup recommendations and verify cross-version DB support before declaring the matrix valid. Include a PHP 8.3 job for Moodle 5.3 as an additional useful compatibility check if resources permit.

- An automated **security scan** with declared rule set and report, and dependency/secret scanning, must fail the build for verified introduced high/critical issues. Do not misuse PHPMD as a vulnerability scanner.
- Create `artifacts` for failed Behat screenshots/logs, test reports, accessibility JSON/HTML, selected coverage files and packaged ZIP. Use bounded retention and exclude sensitive configuration.
- Be transparent about jobs that need extra runner support or cannot exercise SSO/preproduction. Do not use `continue-on-error: true` for mandatory gates, broadly silence warnings, skip broken checks, or call a disabled step "passed".
- Have at least one maintainable mechanism for scheduled compatibility checks or dependency updates, without making network-dependent CI flaky for every PR.

### 9.2 Moodle Playground PR preview

Add a separate `.github/workflows/playground-preview.yml` using **`ateeducacion/action-moodle-playground-pr-preview@v1`**, per its published docs: https://ateeducacion.github.io/moodle-playground/docs/github/pr-previews/ . Configure the plugin path as the repo root and use the repo's **`blueprint.json`** with the Action's documented `blueprint-file` input if compatible. Check that branch rewriting points to the **PR version of Chinijo**, not always `main`. Explicitly choose an appropriate supported Moodle version, ideally 5.3 LTS; don't depend on the action's older default version. Grant `pull-requests: write` **only** to this job if the action needs to append its preview link to the PR description. Protect against untrusted forks and permission failures and provide a read-only alternative preview link if necessary. Do not implement a workflow that creates unsolicited comments.

### 9.3 Release workflow

On an intentional version tag (`v*`), only after passing required tests, generate a **clean Moodle plugin ZIP**, confirm component/structure/version metadata, calculate a SHA-256 checksum, attach release artifacts as appropriate, and provide changelog/release instructions. Versioning, tag and package naming must be documented. Never publish with fake successful tests or include Docker volumes, test databases, `.env`, coverage, dev dependencies or confidential procurement materials. Optionally automate a GitHub Release if authorized.

### 9.4 Reporting and repository hygiene

A public `README.md` should show **real CI badges** (not nonexistent checks) and a meaningful coverage badge only after a real reporting endpoint exists. Add `CONTRIBUTING.md`, security disclosure instructions, issue/PR templates if justified, Dependabot/Renovate only for actual development dependencies, and clearly defined branch/PR quality expectations. Do not change branch protection or GitHub settings without permission; document recommended required checks.

## 10. `blueprint.json` for Moodle Playground (mandatory)

Ship a real root-level **`blueprint.json`** that works with https://moodle-playground.com/ and validates against ATE's official schema:

`https://ateeducacion.github.io/moodle-playground/assets/blueprints/blueprint-schema.json`

Use supported, correctly ordered steps. It should:

1. Select a compatible **Moodle 5.3 / PHP 8.4** runtime in `preferredVersions` (or another verified pairing if the current Playground requires it).
2. Set up an English/Spanish-friendly synthetic demo with an admin, a teacher, one or more students, a clearly named Primary-education test course, sections and representative navigation.
3. Download/install **this theme's GitHub archive ZIP** with the documented `installTheme` step and **activate `chinijo`** with `setTheme`. In the main-branch public scenario, use a real URL such as `https://github.com/ateeducacion/moodle-theme_chinijo/archive/refs/heads/main.zip`; the PR preview action must rewrite it to the candidate branch. Do not put a made-up release URL in the blueprint.
4. Use the documented `login`, `createCategory`, `createCourse`, `createUser(s)`, `enrolUser(s)`, `setConfig`/`setTheme` and other **actually supported** steps with correct shapes. Ensure demo account passwords are publicly documented as *disposable demo only*, never deployed as real credentials.
5. Prefer an ordered **portable core of steps** supported by both browser Moodle Playground and `erseco/alpine-moodle`'s documented experimental blueprint runner. If richer browser-only steps (`addModule`, language-pack installation, etc.) are needed, document them and consider a second browser-specific scenario rather than breaking the required portable `blueprint.json`.
6. Set a useful landing page and add a one-click Playground launch link to the README (encode URL parameters correctly). Show that a fresh launch actually applies the theme and displays test content, not just the stock Boost interface.
7. Include a JSON-schema validity test in `make validate-blueprint` and CI and a browser/functional smoke test. Surface step failures: where supported, mark installation/activation steps `critical: true` so an invalid theme does not yield a misleading green preview.

**Important:** `preferredVersions` selects a browser runtime but is advisory in Docker, which selects the Moodle version with its image. `installMoodle`/`login` are handled differently by Docker, and some browser-only steps do not work in Docker. Do not assume feature parity. Distinguish demo blueprint installation from local live bind-mount development.

## 11. `AGENTS.md` (mandatory)

Create a concise but effective **root `AGENTS.md`** that any subsequent AI coding agent must follow. It must contain:

- Project purpose, source-of-truth order, supported versions and languages.
- Repository map: runtime plugin files vs tools, built JS, tests, docs and release artifacts.
- Required research workflow using **Context7 + official Moodle docs + exact upstream source branches**; never guess Moodle hooks or API signatures.
- Moodle coding standards, parent-theme inheritance and minimal override policy.
- Accessibility, i18n, security, GDPR, no external runtime calls, licensing and third-party asset rules.
- Common local commands (`make up`, `make lint`, `make fix`, `make test`, `make test-a11y`, `make coverage`, `make validate-blueprint`, `make package`).
- Testing and CI gates, minimum accepted evidence, test/coverage policy and manual preproduction limitation.
- Compatibility matrix, 5.1 `public/` path, 5.3 Boost/Bootstrap regressions.
- Rules for updating templates, source JS and `amd/build`, translations and screenshot after UI changes.
- Branch/PR/release conventions and forbidden operations (no core patches, force push, production changes, fabricated test claims, unrequested GitHub comments).
- Pointers to focused docs rather than duplicating this entire prompt.

The name **`AGENTS.md`** is case-sensitive and conventional; do not create only lowercase `agents.md`.

## 12. README, screenshots, accessibility and operational deliverables

Write a professional, useful **`README.md`** in English, with a Spanish companion or sections, not only a logo and badges. It must include:

- Theme name and purpose; a concise list of implemented features with truthful status.
- A **real screenshot** taken from the running theme, in `docs/screenshots/` (for example `docs/screenshots/chinijo-course.png`), embedded directly with standard Markdown `![...](docs/screenshots/...)`. Screenshot must show the **actual developed Chinijo** in a safe synthetic course, not a stock Boost screenshot, Figma mockup, AI-generated image or a broken placeholder. Provide stable screenshot generation instructions, and if screenshot capture is not possible, explicitly report the blocker rather than inventing one.
- Actual CI badge and accurate (not fabricated) coverage link/badge when configured.
- Clear compatibility table covering Moodle 4.5–5.3 and relevant PHP versions.
- Installation instructions for ZIP and Git checkout, including differences in Moodle 4.5 vs 5.1+ webroot layouts.
- `make up`, `make lint`, `make fix`, `make test`, `make coverage`, Docker version selection and troubleshooting.
- Correct one-click **Moodle Playground** launch and PR-preview workflow.
- How to enable/disable the theme, configure visual preferences, pictograms, FEDER notices and language packs.
- Test methodology, manual accessibility boundaries, security policy, attribution, GPL-3.0-or-later license and contributor information.

Create substantive docs, using approved facts rather than empty declarations:

- `docs/architecture.md`: theme inheritance, UI extension points, settings, storage and rationale.
- `docs/compatibility.md`: version matrix, verified API differences, Boost upgrade-note references, EVAGD integration assumptions.
- `docs/deployment-evagd.md`: safe installation, preproduction verification, backup/rollback, upgrade and cache-purge procedures; preserve institutional SSO and current EVAGD modules.
- `docs/teacher-guide.es.md`: concise teacher-facing accessibility/course-structure and pictogram usage guidance in Spanish.
- `docs/accessibility.md` plus `docs/accessibility-audit.md`: WCAG 2.2 AA and EN 301 549 checklist, WCAG-EM sampling, automated vs manual evidence, findings and remediations; record actual results only.
- `docs/accessibility-statement-draft.es.md`: **draft** accessibility statement for review/signature/publication by the Consejería; do not falsely claim official approval.
- `docs/security-report.md`: explicit scanner name/version, rules/CWE/OWASP scope, findings and remediation status; no high/critical introduced issues at acceptance.
- `docs/transfer-plan.md`, `docs/release-and-reversibility.md`, `docs/incident-and-patch-log.md`: practical technical handover, tagged final version, warranty patch tracking, tested release/rollback procedures, and sign-off templates. Do not invent signed acceptance documents.
- `docs/requirements-traceability.md`: map **each mandatory requirement** in the definitive specification to an implementation location, automated/manual test and evidence/status. Explicitly label blocked requirements awaiting EVAGD preproduction access or approved art assets.
- A changelog following meaningful versions/releases.

The definitive specification includes **a 12-month defect warranty from acceptance for the accepted target Moodle/EVAGD version**; upgrades after delivery are not automatically within that warranty. Operational docs may capture this contractual context, including severity-based incident response commitments and critical/high security patch timing, but do not invent a contract start date, pledge unapproved service obligations, or confuse commercial obligations with automatically testable code. Record and document support expectations: high priority response 4 working hours / resolution 2 working days; medium 1/5 working days; low 2/15 working days; high/critical security patches within 5 working days, subject to the definitive contract terms. The original plan relates to work scheduled to start in 2027; do not fabricate milestone dates.

The official **EVAGD preproduction environment**, including corporate SSO, local modules/blocks, active language configuration and institutional customizations, is the final integration/UAT reference, **not** a generic local Moodle. If access is not provided, do real generic tests and provide a detailed pending manual validation checklist. Do not mark institutional UAT as passed. Production deployment belongs to the Consejería.

## 13. Development sequence and definition of done

Proceed in these phases without waiting for routine implementation permission:

1. **Discovery & plan:** inspect repo, definitive Markdown spec (if present), Context7, current Moodle docs/source and counterpart projects. Write a compact requirement matrix, API risk register and component architecture before coding; identify teacher pictogram extension risks and pending EVAGD-only tests.
2. **Small valid plugin:** create legal GPL header/license, core metadata, Boost inheritance, language files, minimal settings and a working install on 4.5 and 5.3.
3. **Fast reproducible environment:** implement Docker Compose and Makefile, verify both LTS selections and the live source mount, establish a synthetic seed.
4. **Accessible features:** preferences panel, minimal SCSS/layout customizations, teacher pictogram workflow (if implementable natively), predictable course navigation/progress, honest notifications, FEDER configurability. Add tests per feature, not afterward.
5. **Automation:** Moodle Plugin CI, full required compatibility matrix, PHPUnit, Behat/axe, separate security scanning and coverage; fix failures rather than bypassing checks.
6. **Playground & preview:** validated `blueprint.json`, actual local/browser demo and GitHub Action for PR previews.
7. **Docs & screenshot:** README with genuine screenshot, bilingual user strings/docs as defined, compliance evidence, licensing, technical transfer/reversibility and release packaging.
8. **Final verification:** run applicable lint/fix/test/coverage/package/blueprint targets, run 4.5 and 5.3 UI smoke tests, review all five Moodle versions' jobs, inspect release ZIP and screenshot, and produce a truthful status report.

**Definition of done:**

- `theme_chinijo` installs and runs on all required Moodle releases without modifying core; Moodle 4.5 and 5.3 LTS receive actual full regression coverage.
- Settings and user-facing features work in **both en and es**; a user cannot modify another user's preferences; no external runtime dependency or telemetry is introduced.
- Functional theme and styling demonstrably improve clarity while retaining core Moodle access and usability; pictogram implementation and known architectural limits are explicit.
- Mandatory Moodle code-style/static/security checks, PHP/JS tests where applicable, Behat and automated accessibility gates pass; coverage is actual and scoped honestly.
- `make up`, `make lint`, `make fix`, `make test`, `make coverage`, `make validate-blueprint`, `make package` are real and documented; 4.5/5.3 local version selection has been verified.
- `blueprint.json` installs and activates Chinijo in Moodle Playground; CI PR previews point to the PR branch; release packaging is installable and contains runtime assets.
- `AGENTS.md`, full README with **real screenshot**, GPL license, release/architecture/a11y/teacher/security/transfer documentation are complete.
- EVAGD-only SSO/plugin compatibility, actual NVDA/VoiceOver sessions, device tests and procurement/UAT approvals are labeled **pending** until they have genuinely occurred.

### Expected final response from the coding agent

Report in **English**, with:

1. **Implemented:** concise grouped summary, important architectural decisions, exact files created/changed, compatibility caveats.
2. **Verification table:** command/job, Moodle/PHP/DB combination, actual pass/fail/skip and evidence/artifact paths; include measured coverage **only if generated**.
3. **Accessibility/security:** concrete test evidence, manual validation still required, known high-risk findings (if any), third-party asset licensing status.
4. **Development/demo instructions:** exact working `make` commands, local URL, how to load the blueprint and PR preview, and screenshot file path.
5. **Outstanding items:** blockers requiring EVAGD preproduction, institution-approved FEDER graphics/text or pictogram licenses; no invented sign-offs.
6. **References:** links to key Moodle documentation/core branches, Context7 research (when available), Moodle Plugin CI, Moodle Playground and alpine-moodle sources actually inspected.

**Execute and verify work where your environment allows. If blocked, make maximum safe progress, explain the precise blocker, and leave real tests/checks with honest status. Never substitute plans, fake screenshots, fake coverage or empty workflow files for deliverables.**

