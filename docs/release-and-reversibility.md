# Releases and reversibility

## Versioning

- `version.php` → `$plugin->version`: `YYYYMMDDXX` (date of the change plus a
  two-digit counter), always increasing. Any database, capability, hook, event or
  setting change needs a new version.
- `$plugin->release`: semantic version `MAJOR.MINOR.PATCH` (patches for warranty
  fixes, minor for compatible features, major for breaking changes).
- `$plugin->maturity`: `MATURITY_ALPHA` during development, `MATURITY_BETA` for
  pre-production acceptance, `MATURITY_STABLE` from the accepted delivery.
- Git tag: `v<release>` (for example `v1.0.0`), on `main`.
- Package name: `theme_chinijo-<release>-<version>.zip` with
  `theme_chinijo-<release>-<version>.zip.sha256`.

## Release procedure

1. On a branch: update `version.php` (version, release, maturity), add the
   upgrade steps in `db/upgrade.php` if the schema changed, update `CHANGELOG.md`.
2. Run locally: `make lint`, `make test` on 4.5 and 5.3, `make coverage`,
   `make security`, `make validate-blueprint`, `make package`.
3. Merge the pull request once CI is green on all branches.
4. Tag `main`: `git tag -a v<release> -m "Chinijo <release>"` and push the tag
   (maintainers only).
5. The **Release** workflow reruns CI and security on the tag, checks that the tag
   matches `$plugin->release`, builds the ZIP and its SHA-256 and creates a
   **draft** GitHub release with the changelog. A maintainer reviews and
   publishes it.
6. Hand over the ZIP, checksum and changelog to the Consejería for pre-production;
   record the validation (UAT) in `docs/incident-and-patch-log.md`.

The package never contains Docker files, tests, development scripts, reports,
`.env` files, coverage data, dependencies or procurement documents
(`dev/package.sh` checks this).

## Reversibility

- The theme changes no core table or file. Its data: user preferences
  `theme_chinijo_*`, table `theme_chinijo_pictogram`, pictogram files (component
  `theme_chinijo`), settings (`theme_chinijo/*`) and the FEDER emblem file.
- Switching back to Boost (or any theme) is immediate and keeps the data.
- Uninstalling through Moodle's plugin manager removes all of the above.
- Downgrades are not supported by Moodle: keep the backup taken before each
  upgrade (database, moodledata and code) to return to a previous release.
  See `docs/deployment-evagd.md` §8.

## End of warranty or early termination (specification §L1E01.G.5)

Within 30 calendar days of the formal notice, the supplier provides:

1. A **tag** in ATE's repository with the last delivered version (target version
   plus warranty patches), verified by the Consejería.
2. **Up-to-date deployment documentation** (`docs/deployment-evagd.md`,
   `docs/compatibility.md`, `README.md`, `AGENTS.md`).
3. A **reversibility record** signed by both parties, using the template below.

### Reversibility record (template — not signed)

| Field | Value |
|---|---|
| Final version (tag, release, version build) | |
| SHA-256 of the delivered ZIP | |
| Moodle/EVAGD target version on which it was verified | |
| Verification date and pre-production environment | |
| Tests run (CI run URL, local runs, manual checks) | |
| Documentation delivered (list and versions) | |
| Open issues at handover | |
| Statement: the last version works on the target version and the documentation is sufficient to operate without the supplier | |
| For the Consejería (name, role, date, signature) | |
| For the supplier (name, role, date, signature) | |
