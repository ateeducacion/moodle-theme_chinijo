# Moodle Playground

[Moodle Playground](https://moodle-playground.com/) (ATE) runs Moodle in the
browser from a JSON blueprint. Chinijo ships two blueprints.

| File | Runs on | Content |
|---|---|---|
| `blueprint.json` | Browser Playground and erseco/alpine-moodle's experimental runner | Moodle 5.3 (PHP 8.4) preferred; installs Chinijo from the `main` branch archive (`installMoodlePlugin`, critical); activates it (`setTheme`, critical); site completion on; a demo category and course; three disposable accounts; enrolments; logs in as `student1`. |
| `blueprints/chinijo-full-demo.blueprint.json` | Browser Playground only | Same theme installation, plus the Spanish language pack, the demo users, course, enrolments and eight activities created with Playground's own steps, and `runPhpCode` running `chinijo_seed_playground()` from `dev/seedlib.php` (completion settings, section names, demo pictograms and the first student's progress). Logs in as `student1`. |

Demo accounts (**disposable, demonstration only**, never use these on a real
site): `admin`, `teacher1`, `student1`, `student2`, password `Chinijo-demo-1234`.

## One-click launch

- Portable demo:
  `https://moodle-playground.com/?blueprint-url=https%3A%2F%2Fraw.githubusercontent.com%2Fateeducacion%2Fmoodle-theme_chinijo%2Fmain%2Fblueprint.json`
- Full demo:
  `https://moodle-playground.com/?blueprint-url=https%3A%2F%2Fraw.githubusercontent.com%2Fateeducacion%2Fmoodle-theme_chinijo%2Fmain%2Fblueprints%2Fchinijo-full-demo.blueprint.json`

Both links need this repository's `main` branch to be published on GitHub
(the blueprint downloads `archive/refs/heads/main.zip`).

## Pull request previews

`.github/workflows/playground-preview.yml` uses
`ateeducacion/action-moodle-playground-pr-preview@v1` (pinned to its commit).
On every pull request from a branch of this repository it reads `blueprint.json`,
replaces the URL of the `installMoodlePlugin` step that points to this repository
with the archive of the pull request's branch, and appends a "Preview in Moodle
Playground" button to the pull request description (no comments). The job has
`pull-requests: write`; the rest of the workflow is read-only.

Pull requests from forks get a read-only token, so the job is skipped. To preview
a fork's branch by hand, download `blueprint.json`, change the URL to
`https://github.com/<fork>/moodle-theme_chinijo/archive/refs/heads/<branch>.zip`
and open it with an inline `?blueprint=` URL (raw JSON, base64 or gzip+base64url,
as documented in the Playground's URL parameters).

## Validation

`make validate-blueprint` (and the CI job `repository`) downloads the official
schema from
`https://ateeducacion.github.io/moodle-playground/assets/blueprints/blueprint-schema.json`,
validates every blueprint with ajv-cli, checks the project rules (theme installed
from this repository and activated, both critical, Moodle 5.3 preferred) and runs
`moodle-blueprint validate` from `erseco/alpine-moodle:v5.3.0` on the portable
blueprint.

## Writing PHP steps for Playground

- `runPhpCode` runs the code as it is, without loading Moodle, so `$CFG` and
  `$DB` do not exist until the code loads Moodle itself:
  `define('CLI_SCRIPT', true); require('/www/moodle/config.php');` (the same
  path Playground's own steps use). An error in this step is only a warning in
  the Logs tab of the Playground panel, so the step that follows it (`login`) is
  critical: a broken seed then stops the blueprint visibly.
- Playground ships Moodle without its `tests/` directories, so Moodle's test data
  generators (`testing_data_generator::create_module()` and the like) fail there.
  `chinijo_seed_run()` (used by `make seed`) relies on them;
  `chinijo_seed_playground()` does not.
- Playground's `addModule` writes the activity records directly, without
  `add_moduleinfo()` (its nested transactions fail on SQLite in WebAssembly), and
  does not set completion; `chinijo_seed_playground()` sets it afterwards.
- To read the log of a run, open the panel (right of the toolbar) and its Logs
  tab: every step is listed with its result.

## Playground versus the local Docker stack

- `preferredVersions` chooses the browser runtime; in Docker the Moodle version is
  the image's (`make up MOODLE_VERSION=…`).
- `installMoodle` and `login` are markers that the Docker runner ignores.
- The Docker runner cannot run `installLanguagePack`, `addModule` or `runPhpCode`;
  that is why they are only in `blueprints/`.
- erseco/alpine-moodle's blueprint runner fails on Moodle 5.3 (it looks for
  `admin/cli` under `public/`). The local stack therefore does not use blueprints:
  `make up` installs the bind-mounted repository with Moodle's CLI and `make seed`
  creates the demo with `dev/seed.php`.
