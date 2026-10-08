# Technology transfer plan

Goal (specification §2.7): the Consejería's technical team can maintain, test,
release and deploy Chinijo with its own means, without the supplier.

## 1. What is handed over

| Item | Where |
|---|---|
| Source code with its full Git history | ATE's GitHub organisation, repository `moodle-theme_chinijo` |
| Release packages and checksums | GitHub releases (`theme_chinijo-<release>-<version>.zip` + `.sha256`) |
| Installation, configuration, upgrade and rollback manual | `docs/deployment-evagd.md`, `docs/compatibility.md` |
| Architecture and decisions | `docs/architecture.md`, `docs/research-and-decisions.md` |
| Rules for developers and AI agents | `AGENTS.md`, `CONTRIBUTING.md` |
| Tests and CI | `tests/`, `.github/workflows/`, `dev/ci/` |
| Accessibility method, audit and draft statement | `docs/accessibility.md`, `docs/accessibility-audit.md`, `docs/accessibility-statement-draft.es.md` |
| Security analysis | `docs/security-report.md`, `make security` |
| Teacher guide | `docs/teacher-guide.es.md` |
| Requirements traceability | `docs/requirements-traceability.md` |
| Incident and patch log | `docs/incident-and-patch-log.md` |

No proprietary tool, licence or account is needed. Everything runs with Docker,
Make and a GitHub account; all dependencies are free software.

## 2. Skills the maintaining team needs

- Moodle plugin development: themes (Boost inheritance, SCSS callbacks, renderers,
  output hooks), Mustache templates, AMD/ES modules and Moodle's Grunt build,
  user preferences, File API, capabilities, privacy API.
- PHPUnit and Behat in Moodle; moodle-plugin-ci.
- Accessibility auditing (WCAG 2.2, EN 301 549, axe-core, screen readers).
- Docker and GitHub Actions.

## 3. Transfer sessions (proposal, to be scheduled with the Consejería)

Dates are not fixed here; they depend on the start-up record and the milestone
plan agreed with the contract manager.

| # | Session | Content | Hands-on result |
|---|---|---|---|
| 1 | Overview | Purpose, architecture, repository map, `AGENTS.md`, decisions | Participants navigate the code and docs |
| 2 | Local environment | `make up` on 4.5 and 5.3, `make seed`, live editing, `make install` | Each participant runs a local site |
| 3 | Code changes | A string, an SCSS rule and a JS change; `make fix`; amd/build | A change with tests passing locally |
| 4 | Tests and CI | PHPUnit, Behat, `@accessibility`, coverage, CI matrix, reading failures | A pull request with green CI |
| 5 | Accessibility | Method, axe, manual checks with NVDA/VoiceOver/TalkBack, audit report | An audit row filled in |
| 6 | Release and deployment | Versioning, tag, release workflow, package checks, EVAGD install, upgrade, rollback | A release candidate installed in pre-production |
| 7 | Moodle upgrades | Reading Boost's `UPGRADING.md`, adding a branch to CI, compatibility review | A compatibility checklist for the next EVAGD version |

## 4. Acceptance of the transfer

The transfer is complete when the Consejería's team, without help:

- [ ] builds a release from a tag and installs it in pre-production;
- [ ] makes a small change, passes CI and deploys it;
- [ ] rolls back to the previous release;
- [ ] runs the accessibility checks and updates the audit.

Record the result in the reversibility record (`docs/release-and-reversibility.md`).
