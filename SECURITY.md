# Security policy

## Supported versions

| Chinijo | Moodle | Security fixes |
|---|---|---|
| 0.x (development) | 4.5 LTS – 5.3 LTS | Yes, on the `main` branch |

During the warranty period of the accepted release, high and critical
vulnerabilities in the theme's own code are fixed within 5 working days.

## Reporting a vulnerability

Please **do not open a public issue**. Report it privately through GitHub's
"Report a vulnerability" (Security › Advisories) on this repository, or to the
Área de Tecnología Educativa (ATE) of the Consejería de Educación del Gobierno
de Canarias through its official channels.

Include the affected version, the Moodle version, steps to reproduce and the
impact. You will receive an acknowledgement, an assessment and, if confirmed, a
fix and a security advisory. Vulnerabilities in Moodle core or in other plugins
should be reported to their maintainers (for Moodle: https://moodle.org/security).

## What is in scope

The code in this repository: PHP, Mustache templates, SCSS and JavaScript of
`theme_chinijo`. Development tooling (`dev/`, Docker files, workflows) is in
scope for supply-chain issues. See `docs/security-report.md` for the controls
and the latest scan results.
