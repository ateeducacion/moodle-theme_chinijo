# Research and decisions

Log of what was checked, where, and why each choice was made. Dates are those of
the check. Add new entries at the end of each section; do not rewrite history.

## Sources checked (2026-10-08)

| Source | Exact version / reference | What was checked |
|---|---|---|
| Definitive specification | `PPT-EDICI-Lote1_Tema_Moodle_EVAGD.md` (local copy, not committed) | Every requirement of L1E01, §2.3–2.10; mapped in `requirements-traceability.md`. The older PDF was not used. |
| Moodle core | `MOODLE_405_STABLE` @ `215f443` (4.5.15, build 2024100715) and `MOODLE_503_STABLE` @ `4262229` (5.3, build 2026100500), shallow clones | Boost `config.php`, `lib.php`, layouts, templates, `UPGRADING.md`; `core_renderer`; output hooks; `core_user` preference definitions and REST route; `completion_info`; `core/modal`, `core/toast`, `core_course/events`; file serving; navigation callbacks; backup theme plugin classes; Behat accessibility steps. |
| Moodle core (API checks) | `MOODLE_500_STABLE`, `MOODLE_501_STABLE`, `MOODLE_502_STABLE` (GitHub API) | `completion_info::get_user_activities_with_completion()` exists from 5.0; `cmactions::delete()` and `sectionactions::set_visibility()` from 5.2. |
| Moodle release builds | Tags `v4.5.0` (2024100700), `v5.0.0` (2025041400), `v5.1.0` (2025100600), `v5.2.0` (2026042000), `v5.3.0` (2026100500) | `version.php` of each tag. |
| Context7 | `/moodle/devdocs` | Boost child theme configuration (parents, SCSS callbacks) and the Hooks API (`db/hooks.php`, priorities). Answers were cross-checked in the source above (hook priority: higher runs first, default 100). |
| moodledev.io | Theme, Hooks, Privacy, Testing (PHPUnit/Behat) pages, through Context7 | As above. |
| moodle-plugin-ci | 4.5.11 (2026-08-07), `gha.dist.yml` at that release, `docs/CLI.md`, `CodeCoverage.md`, source of `GruntCommand`, `BehatCommand`, `PluginInstaller` | Commands and options, PostgreSQL 17 / MariaDB 11 recommendation for 5.3, how plugins are copied into Moodle, how Grunt detects stale builds, how Behat finds Selenium. |
| erseco/alpine-moodle | Images `v4.5.15` (PHP 8.3.15) and `v5.3.0` (PHP 8.4.21), run locally | Entrypoint scripts, `SYNC_MOODLE_CODE`, theme mount paths, `public/` layout, CLI location, blueprint runner (`moodle-blueprint validate`). |
| ATE Moodle Playground | Schema `assets/blueprints/blueprint-schema.json` (fetched by `make validate-blueprint`), `docs/blueprints/reference.md`, examples | Step names and shapes, `critical`, `preferredVersions`, `runPhpCode`, `installLanguagePack`, launch URL parameters. |
| PR preview action | `ateeducacion/action-moodle-playground-pr-preview` `v1` (`bfa63e4`, 2026-05-13) | Inputs; only `installMoodlePlugin` URLs of this repository are rewritten to the PR branch archive; runtime `node20`. |
| Third-party themes | gjbarnard/moodle-theme_adaptable (`ci.yml`), willianmano/moodle-theme_moove | CI patterns. Adaptable: matrix and release flow worth reusing; outdated pins avoided. Moove: no current CI. The local `moodle-theme-zagal` (a Moove fork) was only looked at for conventions. |
| Semgrep | 1.180.0 (`semgrep/semgrep:1.180.0`) | Registry rulesets with PHP and JavaScript rules; `--config auto` does not work with `--metrics=off`. |
| gitleaks | 8.30.1 (`ghcr.io/gitleaks/gitleaks:v8.30.1`) | The GitHub Action v3 needs a licence for organisation repositories, the CLI does not. |
| Font | Atkinson Hyperlegible, Fontsource package 5.2.8 (OFL-1.1) | Licence and latin subset (covers Spanish). |

## Decisions

### D1. Boost child theme with a minimal override surface
Inherit everything from Boost; override only `core_renderer::course_header()`; use
output hooks and documented `lib.php` callbacks for the rest. Rejected: copying
Boost layouts or `full_header`/navbar templates (they changed between 4.5 and 5.3:
login form moved to core in 5.3, course index header in 5.3, colour mode menu in
the navbar in 5.3).

### D2. Preferences as core user preferences, validated by core
Store `theme_chinijo_*` user preferences and declare them in
`theme_chinijo_user_preferences()` with a permission callback that only accepts
the user themself. The dialogue saves through `core_user/repository`, the same
path Boost uses for its drawer preferences, so core validates names, values and
ownership. Rejected: a custom AJAX endpoint (more attack surface, duplicated
validation). Guests and visitors who are not logged in use the stand-alone page
(POST + `sesskey`), which Moodle stores in the session only.
Findings during implementation: `setUserPreferences()` needs `userid: 0` for each
item; a `null` value (remove) returns HTTP 500 from the 5.3 route, so defaults are
saved as the value `default`.

### D3. Data attributes on `<html>` written by a hook
`before_html_attributes` (priority 0, after Boost's colour mode listener) writes
`data-chinijo-*` attributes, so the first paint already uses the user's choices.
Rejected: classes (YUI adds `class` to `<html>`), client-side only application
(flash of default styles).

### D4. High contrast and Boost colour modes
When high contrast is on, `data-bs-theme`/`data-colourmode` are pinned to `light`.
A high contrast dark variant was not added: Boost's dark mode is still
experimental and off by default; this can be revisited.

### D5. Font
Atkinson Hyperlegible (OFL 1.1), self-hosted, latin subset, 4 files of ~17 KB that
browsers only download when the choice is active. No dyslexia-specific font is
offered: the evidence that such fonts improve reading is weak, and the spacing
controls address the same needs. No claim is made that any font "treats" dyslexia.

### D6. Reduced motion
System `prefers-reduced-motion` is always honoured; the preference can force it.
Durations are 0.01 ms rather than 0: core modals and Boost drawers wait for
`transitionend`, which is not fired for zero-length transitions.

### D7. Pictograms inside the theme
A theme can own a table (`db/install.xml`), a capability (`db/access.php`), files
in the course context and a management page, all through documented APIs; no
companion plugin is needed. Images are added by JavaScript next to the names
(progressive enhancement) instead of overriding course format templates, which
differ between formats and versions. Course formats keep the `data-for`
attributes used for this on 4.5–5.3. SVG is not accepted (script risk); images are
checked by content. Course backup and restore use Moodle's theme backup plugin
classes; pictograms are restored in `after_restore_course()`, once section and
course module ids are mapped.

### D8. Progress figures
Reproduce `\core_completion\progress` per branch (4.5 counts all activities with
completion; 5.0+ counts those visible to the user), selected with
`method_exists()`, and verify against core in PHPUnit.

### D9. Local environment
erseco/alpine-moodle with the repository bind-mounted **read-only**,
`SYNC_MOODLE_CODE=never` and no code volume: the image's code sync uses
`rsync --delete`, which deleted bind-mounted plugin files when tested.
`REVERSEPROXY=true` because PHP sees port 8080 inside the container and Moodle's
wwwroot check would redirect forever on any other host port (development only).
The image's installer does not quote `MOODLE_SITENAME`, so it is a single word.
Installation and activation use Moodle's CLI (`dev/install.sh`) because the
image's blueprint runner fails on 5.3 (`admin/cli` is outside `public/`).

### D10. Test runner
A development-only image based on `moodlehq/moodle-php-apache` (PHP 8.3/8.4 with
PCOV) plus Composer, nvm, Java (Mustache HTML validator), the PostgreSQL client
and moodle-plugin-ci 4.5.11, running as a non-root user. Selenium runs as a
separate `selenium/standalone-chromium` service; the runner starts PHP's built-in
server itself (`MOODLE_START_BEHAT_SERVERS=NO`). The alpine production-like image
is not modified.

### D11. CI matrix
Full runs (static, PHPUnit, Behat with axe) on 4.5 LTS (PHP 8.3) and 5.3 LTS
(PHP 8.4); install, static checks and PHPUnit on 5.0, 5.1, 5.2 and on 5.3 with
PHP 8.3; MariaDB 11.8 on both LTS. `amd/build` is produced with Moodle 5.3's Grunt;
Moodle 4.5's Grunt produced identical files in the local run, so the "build is up
to date" check runs on both LTS jobs. Actions are pinned
to commit SHAs. PHPMD runs as a step; moodle-plugin-ci reports its findings but
does not fail on them.

### D12. Blueprint
`installMoodlePlugin` (with `pluginType`/`pluginName`) instead of `installTheme`:
the PR preview action only rewrites `installMoodlePlugin` URLs to the PR branch.
The portable `blueprint.json` uses only steps implemented by both runners;
`blueprints/chinijo-full-demo.blueprint.json` adds browser-only steps
(`installLanguagePack`, `runPhpCode` calling `dev/seedlib.php`).

### D13. Security scanning
Semgrep CE with explicit rulesets (p/php, p/phpcs-security-audit, p/javascript,
p/owasp-top-ten, p/cwe-top-25, p/secrets); the gate fails on ERROR severity.
gitleaks CLI instead of the licensed GitHub Action.

## Known uncertainties

- The PR preview action `v1` declares the `node20` runtime. GitHub has been
  retiring Node 20 on hosted runners; the workflow could not be run from this
  environment (nothing was pushed), so its behaviour on current runners is
  unverified.
- moodle-plugin-ci 4.5.11's own CI does not cover 5.2/5.3; the local runs on 5.3
  (and 4.5) recorded in the README are the evidence available.
- The Moodle Playground run of `blueprint.json` needs the theme published on the
  `main` branch of the GitHub repository; it has not been executed yet.
