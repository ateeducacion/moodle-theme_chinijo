# Incident and patch log

Register of incidents and patches during development and the warranty period
(specification §L1E01.G.4). One row per incident; never delete rows.

## Service levels (from the specification, subject to the signed contract)

| Severity | Definition | Response | Resolution |
|---|---|---|---|
| High | The theme prevents using EVAGD, or leaves a page with WCAG level A or AA errors | 4 working hours | 2 working days |
| Medium | A theme feature fails but there is a workaround | 1 working day | 5 working days |
| Low | Visual or documentation defects that do not prevent use | 2 working days | 15 working days |
| Security (high or critical vulnerability in the theme's code) | — | — | Patch within 5 working days |

Working hours are those of the Consejería, Monday to Friday. The warranty covers
defects of the delivered target version for 12 months (or the longer period
offered) from acceptance; adaptation to later Moodle or EVAGD versions and
defects caused by third-party changes are excluded. The warranty start date is
the date of the acceptance record: **not yet set**.

## Log

| ID | Reported (date, by) | Severity | Description | Moodle/EVAGD version | Theme version | Fix (commit, release) | Acceptance test result | Closed |
|---|---|---|---|---|---|---|---|---|
| — | — | — | No incidents registered yet. | — | — | — | — | — |

## Development fixes recorded before delivery

Defects found by the automated checks while building the first version (kept for
traceability; they never reached a delivered release):

| Date | Found by | Defect | Fix |
|---|---|---|---|
| 2026-10-08 | PHPUnit install (XMLDB) | `author`/`license` columns declared `NOT NULL DEFAULT ''`, which XMLDB rejects | Columns made nullable; empty values stored as NULL |
| 2026-10-08 | PHPUnit | Licence short names lost their dot (`PARAM_ALPHANUMEXT`), so licences were never saved | Own cleaning that keeps dots, checked against the site's licences |
| 2026-10-08 | Browser test | Display settings could not be saved: `setUserPreferences()` needs `userid: 0`; a `null` value returns HTTP 500 on 5.3 | Explicit user id; defaults saved as the value `default` |
| 2026-10-08 | Semgrep | `sha1()` used as an array key; development runner image running as root | Plain JSON key; runner runs as an unprivileged user |
