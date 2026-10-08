# Compatibility

## Supported versions

| Moodle | Branch | Version build | PHP tested in CI | Local image (`make up MOODLE_VERSION=…`) | Theme path in the image | Database tested |
|---|---|---|---|---|---|---|
| 4.5 LTS | `MOODLE_405_STABLE` | ≥ 2024100700 (4.5.0) | 8.3 | `erseco/alpine-moodle:v4.5.15` (PHP 8.3) | `/var/www/html/theme/chinijo` | PostgreSQL 17, MariaDB 11.8 |
| 5.0 | `MOODLE_500_STABLE` | ≥ 2025041400 (5.0.0) | 8.3 | `v5.0.11` (PHP 8.3) | `/var/www/html/theme/chinijo` | PostgreSQL 17 |
| 5.1 | `MOODLE_501_STABLE` | ≥ 2025100600 (5.1.0) | 8.3 | `v5.1.8` (PHP 8.3) | `/var/www/html/public/theme/chinijo` | PostgreSQL 17 |
| 5.2 | `MOODLE_502_STABLE` | ≥ 2026042000 (5.2.0) | 8.3 | `v5.2.4` (PHP 8.3) | `/var/www/html/public/theme/chinijo` | PostgreSQL 17 |
| 5.3 LTS | `MOODLE_503_STABLE` | ≥ 2026100500 (5.3.0) | 8.4 and 8.3 | `v5.3.0` (PHP 8.4) | `/var/www/html/public/theme/chinijo` | PostgreSQL 17, MariaDB 11.8 |

- `version.php`: `requires = 2024100700` (the build number of the `v4.5.0` tag, read
  from `version.php` at that tag) and `supported = [405, 503]`.
- PHP: Moodle 4.5 supports PHP 8.1–8.3 (8.4 is **not** supported, so the 4.5 jobs
  use 8.3). Moodle 5.3 requires PHP ≥ 8.3 and supports 8.4.
- Databases (from `admin/environment.xml` of each branch): Moodle 5.3 needs
  PostgreSQL ≥ 17 and MariaDB ≥ 11.4, so CI uses PostgreSQL 17 and MariaDB 11.8
  for every branch.
- The 5.0–5.2 image versions are listed for completeness; the local stack has been
  run on 4.5 and 5.3 (see the verification table in the README).

## Install location (Moodle 5.1 `public/` layout)

From Moodle 5.1 the web root is `public/`. A plugin repository has the same
contents everywhere; only its install path changes:

- Moodle 4.5 and 5.0: `<moodle>/theme/chinijo`
- Moodle 5.1, 5.2 and 5.3: `<moodle>/public/theme/chinijo`

`$CFG->dirroot` points to the web root in both layouts, so the theme always uses
`$CFG->dirroot . '/theme/...'`. CLI scripts stay at `<moodle>/admin/cli/` (outside
`public/`) in 5.1+.

## Verified API differences that the theme handles

| Area | 4.5 | 5.0–5.2 | 5.3 | How Chinijo handles it |
|---|---|---|---|---|
| Bootstrap | 4.6.2 | 5.3 (+ deprecated bs4-compat) | 5.3, bs4-compat still imported from `moodle/deprecated` | Chinijo's markup uses its own classes (e.g. `theme-chinijo-visually-hidden`); no `sr-only`/`visually-hidden`/`ms-*` dependency. |
| Bootstrap JS from AMD | `theme_boost/bootstrap/*` | same | `theme_boost/bootstrap/*` deprecated (MDL-88766) | Chinijo imports no Bootstrap module; it uses `core/modal` and `core/toast`. |
| Colour modes | — | — | Experimental light/dark (`data-bs-theme`, MDL-68037), off by default | High contrast pins `light` after Boost's listener (hook priority 0); covered by `hook_callbacks_test`. |
| Default font | system-ui | system-ui | Noto Sans (self-hosted by Boost, MDL-88412) | The "easy to read" choice overrides `font-family`; icon fonts are untouched. |
| Course index drawer header | dropdown | dropdown | single collapse/expand toggle (MDL-89050) | Not overridden. |
| Login form template | `theme_boost/loginform` | same | moved to core (MDL-89196) | Not overridden; the toolbar is added through a hook. |
| `core/fetch` | AMD module | AMD | ESM build (`lib/js/esm`) | Used only indirectly through `core_user/repository`. |
| User preference REST route | `/api/rest/v2/user/current/preferences` | same | same | `setUserPreferences()` needs `userid: 0`; a `null` value returns HTTP 500 on 5.3, so defaults are sent as the value `default`. |
| Course progress | `count_modules_completed($userid)` over all activities | `get_user_activities_with_completion()` + `count_modules_completed($userid, $cmids)` | same as 5.0 | `method_exists()` picks the branch's own semantics; the test compares with `\core_completion\progress` on every branch. |
| Removed Boost templates | — | — | `flat_navigation`, `nav-drawer` removed in 5.2 | Chinijo never used them. |
| Behat accessibility step | axe-core 4.10 (WCAG 2.0/2.1/2.2 A/AA) | — | axe-core 4.13 | Same step used on both LTS. |
| Favicon | theme's own `pix/favicon.ico` only (no inheritance) | same | same | Chinijo ships Boost's favicon (GPL); sites can set their own under Appearance › Logos. |

## Boost upgrade notes consulted

`theme/boost/UPGRADING.md` (and `public/theme/boost/UPGRADING.md`) on
`MOODLE_503_STABLE`, sections 4.5, 5.0, 5.1, 5.2 and 5.3. Relevant points: SCSS now
refers to colours through CSS custom properties so they follow the colour mode
(5.3); a child theme that renders its own navbar must output
`theme_boost\colour_mode::render_menu()` (Chinijo uses Boost's navbar, so nothing
to do); Bootstrap modules must not be imported directly (5.3).

## EVAGD integration assumptions (to be verified in pre-production)

These cannot be checked on a generic Moodle and are **pending** until EVAGD
pre-production access is provided (see `docs/deployment-evagd.md`):

- Corporate SSO: the theme does not touch authentication; the login page toolbar
  and FEDER notice appear only in the `login` layout, which SSO may bypass.
- EVAGD's own blocks, local plugins and course formats: Chinijo inherits Boost's
  rendering; pictograms rely on the `data-for="cmitem"`, `data-for="section_title"`
  and course index attributes of Moodle's standard course formats.
- Language configuration: the theme ships `en` and `es` strings and never forces a
  language; other installed languages fall back to English for theme strings.
- Other themes: Chinijo's hooks and callbacks do nothing when another theme renders
  a page (course or category themes, `allowcoursethemes`).
