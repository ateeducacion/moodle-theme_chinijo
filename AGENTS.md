# AGENTS.md — working on theme_chinijo

Rules for any AI coding agent (and human) changing this repository. Keep this
file short; details live in `docs/`.

## Purpose and sources of truth

Chinijo (`theme_chinijo`, directory `theme/chinijo`) is an accessible Boost child
theme for EVAGD, the Moodle platform of the Consejería de Educación del Gobierno
de Canarias, aimed at early Primary learners and learners with specific
educational support needs (NEAE). Licence: GPL-3.0-or-later.

Order of authority when sources disagree:

1. The definitive procurement specification (Markdown, `PPT-EDICI-Lote1_Tema_Moodle_EVAGD.md`).
   It is **not** in this repository and must not be committed. Its requirements are
   mapped in `docs/requirements-traceability.md`.
2. Decisions recorded in `docs/research-and-decisions.md`.
3. This file and the other docs.

Supported: Moodle 4.5 LTS, 5.0, 5.1, 5.2 and 5.3 LTS (`$plugin->requires = 2024100700`,
`$plugin->supported = [405, 503]`). Languages: English (source) and Spanish.

## Repository map

| Path | What it is | Shipped in the ZIP |
|---|---|---|
| `version.php`, `config.php`, `lib.php`, `settings.php` | Plugin entry points (callbacks only in `lib.php`) | yes |
| `classes/` | All logic: `local/` (preferences, pictograms, progress, FEDER, theme checks), `output/core_renderer.php`, `hook_callbacks.php`, `observer.php`, `form/`, `privacy/` | yes |
| `db/` | `install.xml`, `access.php`, `hooks.php`, `events.php` | yes |
| `backup/moodle2/` | Course backup and restore of pictograms | yes |
| `templates/`, `scss/`, `fonts/`, `pix/`, `lang/en`, `lang/es` | UI | yes |
| `amd/src/` → `amd/build/` | ES modules and their Grunt build (commit both) | yes |
| `preferences.php`, `pictograms.php` | Pages | yes |
| `tests/` | PHPUnit, Behat features, generators, fixtures | no |
| `dev/` | Seed, install, packaging, validation, security and CI-runner scripts | no |
| `blueprint.json`, `blueprints/` | Moodle Playground scenarios | no |
| `.github/` | CI, security, release and preview workflows | no |
| `docs/` | Project documentation | no |

## Research before coding

Never guess a Moodle API, hook, callback, template name or SCSS variable.

1. Query Context7 (`/moodle/devdocs`, `/websites/moodledev_io`, `/moodle/moodle`).
2. Check the official docs at https://moodledev.io/.
3. Read the code of **every** supported branch (`MOODLE_405_STABLE` … `MOODLE_503_STABLE`);
   from 5.1 the web root is `public/`. Record version differences in `docs/compatibility.md`
   and decisions in `docs/research-and-decisions.md`.

## Coding rules

- Boost child theme: inherit layouts, templates and renderers. The only overrides are
  `core_renderer::course_header()`, `core_renderer::course_content_header()` and the hooks in
  `db/hooks.php`. Every new override must be justified in `docs/architecture.md` and checked
  on all branches.
- Hook and `lib.php` callbacks run for every theme: always check `local\theme::is_active()`
  or `can_decorate()` first.
- Moodle coding style (moodle-cs), PHPDoc on everything, 4 spaces, no tabs (except Makefile),
  English code and comments, `get_string()` for every visible text (en and es, same keys,
  sorted). Templates and JS must work with Bootstrap 4.6 (4.5) and 5.3 (5.x): use the
  theme's own classes (e.g. `theme-chinijo-visually-hidden`), never `sr-only`/`visually-hidden`,
  and never import `theme_boost/bootstrap/*`.
- No SPA frameworks, CDNs, external fonts, analytics or runtime calls to external services.
- Security: `require_login`/`require_capability`/context checks on the server, `sesskey`
  on writes, Moodle forms and the File API, DML with placeholders, escape output. Pictograms:
  PNG, JPEG and WebP only, checked by content; no SVG.
- Privacy: only the user's own display choices are stored, as user preferences. Never store
  or infer diagnoses or support needs. Teachers cannot set preferences for learners.
- Third-party assets (fonts, pictograms) need a verified licence and an entry in
  `thirdpartylibs.xml` / `docs/third-party-licenses.md`. Never bundle ARASAAC pictograms.

## Commands

```sh
make up [MOODLE_VERSION=4.5|5.0|5.1|5.2|5.3]   # local site (default 5.3), then: make seed
make lint          # moodle-plugin-ci static checks + language parity + blueprint validation
make fix           # PHPCBF and amd/build rebuild, written to build/fix.patch
make test          # PHPUnit + Behat;  make test-unit / test-behat / test-a11y
make coverage      # PCOV coverage of classes/ and lib.php, minimum 80 %
make security      # Semgrep + gitleaks
make validate-blueprint
make package       # build/theme_chinijo-<release>-<version>.zip + SHA-256
make screenshot    # docs/screenshots/chinijo-course.png from the running site
```

## After changing…

- **JS** (`amd/src`): rebuild with `make fix` (Moodle 5.3 Grunt) and commit `amd/build`.
- **Strings**: update `lang/en` and `lang/es` together; `make lint` checks parity.
- **DB schema**: bump `version.php`, add `db/upgrade.php` steps with savepoints and a test;
  update the backup and restore classes if the pictogram table changes.
- **UI**: run `make test-a11y`, refresh the screenshot (`make screenshot`) and update
  `docs/accessibility-audit.md` with what was actually checked.

## Gates and evidence

CI (`.github/workflows/ci.yml`) must pass on all five branches; 4.5 and 5.3 run PHPUnit,
Behat and the axe-core `@accessibility` scenarios. Security (`security.yml`) must show no
ERROR-severity Semgrep findings and no secrets. Coverage of `classes/` + `lib.php` must stay
at or above 80 %. Automated checks do not prove WCAG conformance: manual checks (screen
readers, devices, EVAGD pre-production) are recorded in `docs/accessibility-audit.md` and
stay **pending** until a person has really done them.

## Branches, releases and forbidden actions

- Work on `feature/<topic>` branches; open PRs against `main`; commit messages in English.
- Release: bump `version.php`, update `CHANGELOG.md`, tag `v<release>` (see
  `docs/release-and-reversibility.md`).
- Never: patch Moodle core, force-push, push or open PRs without the maintainers' approval,
  touch EVAGD production, post unrequested GitHub comments, commit secrets or the procurement
  documents, or claim tests, audits, screenshots or sign-offs that did not happen.
