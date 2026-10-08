# Security report

Static security analysis, secret scanning and the security design of
theme_chinijo. Required by the specification (§2.9: report naming the tool and
rule sets, no critical or high vulnerabilities).

## Latest results

| Date | Commit | Tool | Scope | Result |
|---|---|---|---|---|
| 2026-10-08 | working tree after `fb2edae` | Semgrep CE 1.180.0, 171 rules | 89 files tracked or trackable by Git (PHP, JS, Mustache, SCSS, shell, YAML, JSON, XML) | **0 findings** (any severity) |
| 2026-10-08 | history (7 commits) | gitleaks 8.30.1, default rules | Full Git history | **No leaks** |
| 2026-10-08 | working tree | gitleaks 8.30.1, default rules | Files tracked or trackable by Git | **No leaks** |

Reproduce with `make security` (reports in `build/security/`). CI runs the same
script (`.github/workflows/security.yml`) on every push and pull request and
weekly, and keeps the reports as an artifact for 30 days.

## Tools and rule sets

- **Semgrep CE** `semgrep/semgrep:1.180.0` (Docker image, `--metrics=off`), registry
  rule sets:
  - `p/php` — PHP security rules;
  - `p/phpcs-security-audit` — PHP rules ported from phpcs-security-audit (all ERROR);
  - `p/javascript` — JavaScript security rules;
  - `p/owasp-top-ten` — OWASP Top 10 (PHP and JavaScript rules);
  - `p/cwe-top-25` — CWE Top 25 (PHP and JavaScript rules);
  - `p/secrets` — hard-coded credentials.
  Gate: any finding with severity ERROR (high/critical) fails the job. The full
  report keeps every severity. Excluded paths: `amd/build` (generated from
  `amd/src`, which is scanned), `fonts`, `build`, local tool folders. Semgrep's
  default `.semgrepignore` also skips `tests/`.
  Limits: no Semgrep rule set covers Mustache templates; they are covered by
  Moodle's Mustache lint and by code review (see below).
- **gitleaks** `ghcr.io/gitleaks/gitleaks:v8.30.1` (CLI; the GitHub Action requires
  a licence for organisation repositories). Configuration `.gitleaks.toml`: default
  rules, allowlisting only generated local output.
- **Not security scanners**, run as quality gates: moodle-plugin-ci PHPCS
  (moodle-cs), PHPDoc, PHPMD, Mustache lint, ESLint and Stylelint.

## Findings and triage history

| Date | Rule | Severity | Location | Decision |
|---|---|---|---|---|
| 2026-10-08 | `php.lang.security.weak-crypto` | ERROR | `sha1()` used as an array key for de-duplicating credits | Not a cryptographic use, but fixed: the key is now the JSON of the credit. |
| 2026-10-08 | `dockerfile.security.missing-user`, `missing-user-entrypoint` | ERROR | `dev/ci/Dockerfile` (development test runner) | Fixed: the runner runs as the unprivileged user `ci`. |
| 2026-10-08 | `dependabot-missing-cooldown` | MEDIUM (×3) | `.github/dependabot.yml` | Fixed: 7-day cooldown on every ecosystem. |

No false positives are suppressed: there are no `nosemgrep` annotations.

## Security design (code review checklist)

| Risk (OWASP / CWE) | Control in Chinijo | Evidence |
|---|---|---|
| Broken access control (A01, CWE-284/639 IDOR) | `pictograms.php`: `require_login($course)` + `require_capability('theme/chinijo:managepictograms')`; `pictograms::save()`/`delete()` check the capability again and refuse items of other courses; file serving checks the record belongs to the course and that the user can see the item. Preferences: only the current user, enforced by core's permission callback (`preferences::can_edit()`) and by `preferences.php`, which has no user parameter at all. | `pictograms_test`, `pluginfile_test`, `preferences_test::test_core_user_permissions` |
| CSRF (CWE-352) | `require_sesskey()` on `preferences.php`; deletion confirmation carries `sesskey`; Moodle forms; core REST route for saved preferences. | Code, Behat |
| XSS (A03, CWE-79) | Mustache escaping (`{{ }}`) for all user data; triple mustache only for strings and HTML produced by Moodle (`format_text`, rendered templates); FEDER text through `format_text()`; toast messages escaped before insertion; pictogram data passed as an escaped attribute and parsed with `JSON.parse`; images built with `createElement`, never `innerHTML`. | `feder_test::test_content`, `pictograms_test::test_render_page_data` |
| Unrestricted upload (CWE-434) | PNG, JPEG or WebP only, ≤ 1 MB, one file, checked by name **and content** (`get_imageinfo()`); SVG refused; files stored with the File API and served by Moodle. | `pictograms_test::test_rejects_svg`, `test_rejects_disguised_file`, `pictogram_form_test` |
| Injection (CWE-89) | Moodle DML with parameters only; no raw SQL. | Code |
| SCSS/CSS injection from settings | The brand colour reaches the SCSS only if it is a hexadecimal colour. Raw SCSS settings are admin-only (as in Boost). | `lib_test::test_pre_and_extra_scss` |
| Path traversal | Files looked up by component, area, item id, path and name in the File API; no file-system paths built from input. | `pluginfile_test` |
| Sensitive data exposure / GDPR | Only display choices are stored; no diagnoses or inferred needs; the pictogram table has no user fields; privacy provider exports preferences. | `privacy/provider_test` |
| External calls / supply chain | No runtime requests to external services, no CDN, self-hosted font; GitHub Actions pinned to commit SHAs; Docker images pinned to versions; Dependabot with cooldown. | Code, workflows |
| Open redirect | `returnurl` is `PARAM_LOCALURL`. | Code |

## Vulnerability handling

See `SECURITY.md` for reporting. High or critical vulnerabilities in the theme's
code are patched within **5 working days** during the warranty (specification
§L1E01.G.4); each patch is recorded in `docs/incident-and-patch-log.md`.
