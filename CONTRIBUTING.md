# Contributing to Chinijo

Thank you for helping make EVAGD more accessible. Read `AGENTS.md` first: it is
the short list of rules for everyone (people and AI agents).

## Workflow

1. Open or pick an issue. Accessibility defects use the "Accessibility problem"
   template.
2. Create a branch `feature/<topic>` (or `fix/<topic>`) from `main`.
3. Make small, focused commits with messages in English.
4. Run the checks locally:

   ```sh
   make up && make seed       # local site on Moodle 5.3 (MOODLE_VERSION=4.5 for the previous LTS)
   make lint                  # must pass with no warnings
   make fix                   # PHPCBF and amd/build rebuild, if lint reports fixable problems
   make test                  # PHPUnit + Behat (use MOODLE_VERSION=4.5 too)
   make test-a11y             # axe-core scenarios
   make coverage              # must stay at or above 80 %
   make security
   ```
5. Open a pull request against `main` and fill in the template. CI must be green
   on every Moodle branch. A preview link to Moodle Playground is added to the
   description automatically for branches of this repository.

## What reviewers check

- Moodle coding style, PHPDoc, no warnings; Moodle APIs only (no core patches).
- Accessibility: keyboard, focus, names, contrast, no colour-only cues, reduced
  motion, both Bootstrap 4 (Moodle 4.5) and 5 (Moodle 5.x) markup.
- Every visible string in `lang/en` **and** `lang/es`, keys sorted.
- Tests for new behaviour (PHPUnit for logic, Behat for UI), including negative
  and permission cases.
- `amd/build` rebuilt with Moodle 5.3's Grunt (`make fix`) when `amd/src` changes.
- Screenshot refreshed (`make screenshot`) when the UI changes visibly.
- Documentation updated (`docs/`), `CHANGELOG.md` entry under "Unreleased".

## Checks that cannot be automated

Screen readers (NVDA, VoiceOver, TalkBack), real tablets, browser versions,
200 %/400 % zoom and EVAGD pre-production. Describe in the pull request what you
checked by hand; record audit results in `docs/accessibility-audit.md`. Never
mark a manual check as done unless somebody actually did it.

## Recommended branch protection (to be configured by the repository owners)

Require pull requests for `main`, require the CI jobs (all `Moodle …` matrix
jobs and `Blueprint, scripts, workflows and package`) and `Security / Semgrep and
gitleaks` to pass, require one review, and forbid force-pushes.

## Licence

By contributing you agree that your contribution is licensed under the GNU GPL
version 3 or later. Do not add third-party material without a compatible
licence and an entry in `docs/third-party-licenses.md`.
