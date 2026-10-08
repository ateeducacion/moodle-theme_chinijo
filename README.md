# Chinijo — accessible Moodle theme

[![CI](https://github.com/ateeducacion/moodle-theme_chinijo/actions/workflows/ci.yml/badge.svg)](https://github.com/ateeducacion/moodle-theme_chinijo/actions/workflows/ci.yml)
[![Security](https://github.com/ateeducacion/moodle-theme_chinijo/actions/workflows/security.yml/badge.svg)](https://github.com/ateeducacion/moodle-theme_chinijo/actions/workflows/security.yml)
[![Licence: GPL v3 or later](https://img.shields.io/badge/licence-GPL--3.0--or--later-blue.svg)](LICENSE)

*Versión en español: [README.es.md](README.es.md).*

**Chinijo** (`theme_chinijo`) is an accessible [Moodle](https://moodle.org) theme
based on Boost, built for EVAGD, the virtual learning environment of the
Consejería de Educación del Gobierno de Canarias. It is designed for the first
years of Primary Education and for learners with specific educational support
needs (NEAE): calm pages, larger text and targets, personal display settings,
course progress and pictogram support, while keeping every Moodle feature.

![Chinijo on Moodle 5.3: a demo course with the learner's progress, pictograms next to sections and activities, and the "Display settings" control in the navbar](docs/screenshots/chinijo-course.png)

*Real screenshot of the theme on Moodle 5.3 with synthetic demo data
(`make seed`, `make screenshot`). More:
[display settings dialogue](docs/screenshots/chinijo-display-settings.png),
[high contrast with large text and the easy-to-read typeface](docs/screenshots/chinijo-course-high-contrast.png).*

> **Status:** development version 0.1.0 (alpha). Automated checks pass locally
> and in GitHub Actions on Moodle 4.5 to 5.3 (see [Verification](#verification)). Manual
> accessibility checks with screen readers and devices, tests on EVAGD
> pre-production and institutional acceptance are **pending**.

## Features

| Feature | Status |
|---|---|
| **Display settings** for each person: high contrast; text size (normal, large, extra large, huge); easy-to-read typeface (Atkinson Hyperlegible); space between letters, words and lines up to the WCAG 2.2 text-spacing values; reduce animations (the system `prefers-reduced-motion` is always honoured). Stored as the user's own Moodle preferences, session-only for guests; nobody can set them for someone else. Keyboard-accessible dialogue from the navbar (and the login page), and a page that works without JavaScript. | Implemented |
| **Course progress** ("3 of 8 activities completed") from Moodle's completion data, the same figures as the dashboard; updated only after Moodle confirms a change. | Implemented |
| **Gentle feedback**: a polite message after marking an activity as done. | Implemented |
| **Pictograms** that teachers add to sections and activities (PNG, JPEG or WebP; text alternative; author and licence shown as credits; included in course backups). Names are always kept. No pictogram is bundled. | Implemented (sections and activities; not for buttons) |
| **EU (FEDER) funding notice**: approved emblem, text alternative and acknowledgement, on landing pages or every page. | Implemented; approved assets pending |
| Accessible defaults: 16 px text, 44 px targets, visible focus ring, underlined links in text, emphasised primary buttons, clear error messages, course index names that wrap, forced-colours support. | Implemented |
| English and Spanish interface. | Implemented |

What the theme does **not** do: it does not change Moodle's permissions,
forms, gradebook, messaging or editing; it does not add services, external
requests, tracking or audio; it does not store anything about a person's needs.

## Compatibility

| Moodle | PHP | Install path | Tested |
|---|---|---|---|
| 4.5 LTS (`MOODLE_405_STABLE`) | 8.3 (8.1–8.3 supported; not 8.4) | `theme/chinijo` | Locally: lint, PHPUnit, Behat, coverage. CI job defined. |
| 5.0 (`MOODLE_500_STABLE`) | 8.3 | `theme/chinijo` | CI job defined (install, static checks, PHPUnit) |
| 5.1 (`MOODLE_501_STABLE`) | 8.3 | `public/theme/chinijo` | CI job defined |
| 5.2 (`MOODLE_502_STABLE`) | 8.3 | `public/theme/chinijo` | CI job defined |
| 5.3 LTS (`MOODLE_503_STABLE`) | 8.4 (also 8.3) | `public/theme/chinijo` | Locally: lint, PHPUnit, Behat, coverage. CI job defined. |

Databases: PostgreSQL 17 (all) and MariaDB 11.8 (both LTS) in CI. Details:
[docs/compatibility.md](docs/compatibility.md).

## Installation

From Moodle 5.1 the web root is `public/`, so the theme goes in
`<moodle>/public/theme/chinijo`; on 4.5 and 5.0 it goes in `<moodle>/theme/chinijo`.

**From a release ZIP** (contains a single `chinijo/` directory):

```sh
shasum -a 256 -c theme_chinijo-<release>-<version>.zip.sha256
unzip theme_chinijo-<release>-<version>.zip -d <moodle>/public/theme/   # <moodle>/theme/ on 4.5 and 5.0
php admin/cli/upgrade.php --non-interactive    # add --allow-unstable for alpha/beta releases
php admin/cli/purge_caches.php
```

Or upload the ZIP in *Site administration › Plugins › Install plugins*.

**From Git**:

```sh
git clone https://github.com/ateeducacion/moodle-theme_chinijo.git <moodle>/public/theme/chinijo
```

Then enable it in *Site administration › Appearance › Themes* (or
`php admin/cli/cfg.php --name=theme --set=chinijo`), or for selected courses and
categories with `allowcoursethemes`/`allowcategorythemes`. To disable it, choose
another theme; to remove it, uninstall it from the plugins overview. EVAGD
procedure, upgrade and rollback: [docs/deployment-evagd.md](docs/deployment-evagd.md).

## Configuration

*Site administration › Appearance › Themes › Chinijo*:

- **General**: unneeded blocks; brand colour (keep 4.5:1 against white).
- **EU funding notice**: enable; pages (login page, site home and dashboard, or
  every page); emblem file; its text alternative; acknowledgement text. Nothing is
  shown until an emblem with its alternative, or a text, is provided. The theme
  ships no emblem or wording: use the ones approved by the institution
  (Regulation (EU) 2021/1060, art. 47 and annex IX).
- **Advanced**: raw SCSS, as in Boost.

**Display settings** need no configuration: every user opens *Display
settings* in the navbar (or *Preferences › Display settings*).

**Pictograms**: teachers with `theme/chinijo:managepictograms` (editing
teachers and managers by default) open *More › Pictograms* in the course. See
the Spanish teacher guide: [docs/teacher-guide.es.md](docs/teacher-guide.es.md).

**Languages**: English and Spanish strings are included; install the Spanish
language pack in Moodle as usual. The theme never forces a language.

## Try it in Moodle Playground

[![Open in Moodle Playground](https://img.shields.io/badge/Moodle%20Playground-Chinijo%20demo-orange)](https://moodle-playground.com/?blueprint-url=https%3A%2F%2Fraw.githubusercontent.com%2Fateeducacion%2Fmoodle-theme_chinijo%2Fmain%2Fblueprint.json)

- [Portable demo](https://moodle-playground.com/?blueprint-url=https%3A%2F%2Fraw.githubusercontent.com%2Fateeducacion%2Fmoodle-theme_chinijo%2Fmain%2Fblueprint.json) (`blueprint.json`)
- [Full demo with activities, completion data and demo pictograms](https://moodle-playground.com/?blueprint-url=https%3A%2F%2Fraw.githubusercontent.com%2Fateeducacion%2Fmoodle-theme_chinijo%2Fmain%2Fblueprints%2Fchinijo-full-demo.blueprint.json)

Accounts (disposable demo only, never on a real site): `student1`, `student2`,
`teacher1` and `admin`, password `Chinijo-demo-1234`. The links install the theme
from the `main` branch on GitHub, so they work once it is published there. Every
pull request gets its own preview button. Details: [docs/playground.md](docs/playground.md).

## Development

Requirements: Docker with Compose v2, GNU Make and a POSIX shell. Everything
else runs in containers.

```sh
cp .env.example .env            # optional: port, development credentials
make up                         # Moodle 5.3 + PostgreSQL 17, theme installed and active
make seed                       # synthetic users, Primary demo course, pictograms, progress
open http://localhost:8080      # student1 / teacher1 / admin, password in .env.example or above
make up MOODLE_VERSION=4.5      # previous LTS (separate data); also 5.0, 5.1, 5.2
```

The repository is bind-mounted **read-only** as the live theme
(`theme/chinijo` before 5.1, `public/theme/chinijo` from 5.1), so edits to PHP,
templates and SCSS show after `make install` (purges caches). JavaScript in
`amd/src` must be rebuilt with `make fix`.

| Target | What it does |
|---|---|
| `make up` / `down` / `logs` / `shell` | Start (waits until ready, installs, activates) / stop (keeps data) / follow logs / shell in Moodle |
| `make install` | Install or upgrade the mounted theme, set it as site theme, purge caches |
| `make seed` | Synthetic demo data (idempotent) |
| `make lint` | moodle-plugin-ci: PHP lint, PHPCS (0 warnings), PHPDoc, PHPMD, validate, savepoints, Mustache, Grunt (ESLint, Stylelint, `amd/build` up to date); en/es string parity; blueprint validation |
| `make fix` | PHPCBF and `amd/build` rebuild, applied to the working tree |
| `make test` / `test-unit` / `test-behat` / `test-a11y` | PHPUnit + Behat / PHPUnit / Behat in Chromium / `@accessibility` (axe-core) scenarios |
| `make coverage` | PHPUnit with PCOV; reports in `build/coverage/` (text, Clover, HTML); fails below 80 % |
| `make test-matrix` | PHPUnit on 4.5, 5.0, 5.1, 5.2 and 5.3, one after the other |
| `make security` | Semgrep (PHP, JS, OWASP, CWE, secrets rule sets) and gitleaks; reports in `build/security/` |
| `make validate-blueprint` | Official Playground schema, project rules and alpine-moodle's validator |
| `make package` | `build/theme_chinijo-<release>-<version>.zip` + SHA-256, contents checked |
| `make screenshot` | Captures the README screenshots from the running site |
| `make reset CONFIRM=yes` | Deletes the stack's volumes for `MOODLE_VERSION` |

Tests run in a development-only container (`dev/ci/`: Moodle HQ's PHP image,
moodle-plugin-ci 4.5.11, PostgreSQL 17, Selenium Chromium). The first run of each
Moodle branch clones and installs Moodle (several minutes); later runs reuse it.

**Troubleshooting**

- *Port in use*: set `CHINIJO_HTTP_PORT` (for example `make up CHINIJO_HTTP_PORT=8085`).
- *Redirect loop on another port*: the stack sets `REVERSEPROXY=true` (development only) because PHP sees the container's port.
- *Changes not visible*: `make install` purges caches; rebuild JavaScript with `make fix`.
- *Never* add a volume for `/var/www/html` or set `SYNC_MOODLE_CODE` to `always`/`auto`: the image's code sync deletes bind-mounted plugin files.
- `make screenshot` uses Docker host networking (Docker Engine on Linux, or Docker Desktop with host networking enabled).

Read [AGENTS.md](AGENTS.md) and [CONTRIBUTING.md](CONTRIBUTING.md) before changing code.

## Verification

Results of the runs made for this version (2026-10-08): local runs and GitHub
Actions on pull request #1.

| Check | Moodle / PHP / DB | Result | Evidence |
|---|---|---|---|
| `make lint` | 5.3 / 8.4 / PostgreSQL 17 | Pass (PHPMD: advisory notes only) | `build/lint-53.log` |
| `make lint` | 5.2.4, 5.1.8, 5.0.11 / 8.3 / PostgreSQL 17 | Pass (PHPMD: advisory notes only) | `build/lint-52.log`, `lint-51.log`, `lint-50.log` |
| `make lint` | 4.5.15 / 8.3 / PostgreSQL 17 | Pass (PHPMD: advisory notes only) | `build/lint-45.log` |
| `make coverage` (PHPUnit 11.5 with PCOV) | 5.3 / 8.4 / PostgreSQL 17 | 72 tests, 621 assertions, pass; 95.57 % of 519 lines (`classes/`, `backup/`, `lib.php`) | `build/coverage-53.log`, `build/coverage/` |
| `make coverage` (PHPUnit 9.6 with PCOV) | 4.5.15 / 8.3 / PostgreSQL 17 | 72 tests, 619 assertions, 1 skipped (Boost colour modes exist only in 5.3), pass; 95.57 % | `build/coverage-45.log` |
| `make test-unit` | 5.2.4, 5.1.8, 5.0.11 / 8.3 / PostgreSQL 17 | 72 tests, 619 assertions, 1 skipped (same test), pass on each | `build/test-unit-5{0,1,2}.log` |
| `make test-behat` (includes the axe-core scenarios) | 5.3 / 8.4 / PostgreSQL 17 / Chromium 152 | 32 scenarios, 432 steps, pass without reruns | `build/behat-53.log` |
| `make test-behat` (includes the axe-core scenarios) | 4.5.15 / 8.3 / PostgreSQL 17 / Chromium 152 | 32 scenarios, 432 steps, pass without reruns | `build/behat-45.log` |
| `make security` | — | Semgrep 1.180.0: 0 findings (171 rules); gitleaks 8.30.1: no leaks | `build/security/` |
| `make validate-blueprint` | — | Both blueprints valid (official schema; alpine-moodle validator) | console |
| `make package` | — | Clean ZIP, single `chinijo/` directory, runtime files only; the ZIP installed on a fresh Moodle 5.3 site | `build/*.zip` |
| `make up`, `make seed`, `make reset` | 5.3 (`erseco/alpine-moodle:v5.3.0`) and 4.5 (`v4.5.15`) | Pass | console |
| GitHub Actions (`ci.yml`, `security.yml`, `playground-preview.yml`) | 4.5 to 5.3; PostgreSQL 17 and MariaDB 11.8; PHP 8.3 and 8.4 | All 21 checks pass. The first run found three CI-only problems, fixed before merging: two PHPUnit tests on MariaDB, JIT with PCOV on PHP 8.4, and the axe step's polling time on the 4.5 runner | pull request #1 |

Behat and the axe-core checks ran on 4.5 and 5.3 only, as in CI; 5.0–5.2 had
static checks and PHPUnit.

Coverage measures PHP logic only. Templates, SCSS, JavaScript behaviour and
accessibility are covered by Behat, axe-core and manual review, not by PHP line
coverage.

## Accessibility

Target: WCAG 2.2 AA, EN 301 549 v3.2.1, RD 1112/2018, WAI-ARIA 1.2. Automated
checks (axe-core) are part of CI; they do not prove conformance. Manual checks
with NVDA, VoiceOver, TalkBack, tablets and EVAGD pre-production are pending.
See [docs/accessibility.md](docs/accessibility.md),
[docs/accessibility-audit.md](docs/accessibility-audit.md) and the draft
statement [docs/accessibility-statement-draft.es.md](docs/accessibility-statement-draft.es.md).

## Documentation

[Architecture](docs/architecture.md) ·
[Compatibility](docs/compatibility.md) ·
[EVAGD deployment](docs/deployment-evagd.md) ·
[Teacher guide (es)](docs/teacher-guide.es.md) ·
[Security report](docs/security-report.md) ·
[Requirements traceability](docs/requirements-traceability.md) ·
[Research and decisions](docs/research-and-decisions.md) ·
[Release and reversibility](docs/release-and-reversibility.md) ·
[Transfer plan](docs/transfer-plan.md) ·
[Incident and patch log](docs/incident-and-patch-log.md) ·
[Third-party licences](docs/third-party-licenses.md) ·
[Changelog](CHANGELOG.md)

## Security

Report vulnerabilities privately: see [SECURITY.md](SECURITY.md).

## Licence and credits

Copyright © 2026 Área de Tecnología Educativa (ATE), Consejería de Educación,
Gobierno de Canarias. Licensed under the [GNU GPL version 3 or later](LICENSE).

- Based on Moodle's Boost theme (GPL v3 or later).
- [Atkinson Hyperlegible](https://www.brailleinstitute.org/freefont/) by the
  Braille Institute of America, SIL Open Font License 1.1 (`fonts/`).
- Pictograms used in courses belong to their authors and keep their own licences
  (for example ARASAAC, CC BY-NC-SA 4.0); they are not part of this theme.

Contributions are welcome: see [CONTRIBUTING.md](CONTRIBUTING.md).
