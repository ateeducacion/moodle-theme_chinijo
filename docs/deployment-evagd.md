# Deployment in EVAGD

Technical manual for installing, configuring, updating and rolling back Chinijo
on EVAGD. Production deployment is done by the Consejería; this document is
written so that its technical team can do it without the developers.

EVAGD **pre-production** (with the corporate SSO, EVAGD's own blocks and modules
and its language configuration) is the reference for integration and acceptance
tests. A generic Moodle is not. Nothing in this document has been run on EVAGD
yet: every EVAGD check below is **pending** until access is provided.

## 1. Prerequisites

- Moodle 4.5 LTS or later, up to 5.3 LTS (`requires` 2024100700). Use the target
  version communicated in the start-up record; see `docs/compatibility.md`.
- Boost present and up to date (it ships with Moodle).
- Administrator access to the server (CLI) and to Site administration.
- A recent, tested backup of the database and of `moodledata`, and of the code tree.
- The approved EU emblem file, its text alternative and the FEDER wording, if the
  notice must be shown (§4.3).

## 2. Package

Use the release ZIP and its checksum from the release page (or build it with
`make package`). Check it:

```sh
shasum -a 256 -c theme_chinijo-<release>-<version>.zip.sha256
unzip -l theme_chinijo-<release>-<version>.zip | head   # one top-level directory: chinijo/
```

## 3. Installation in pre-production

Moodle 4.5 / 5.0: `<moodle>/theme/chinijo`. Moodle 5.1 and later:
`<moodle>/public/theme/chinijo`.

```sh
# 1. Maintenance mode (optional in pre-production, recommended in production).
php admin/cli/maintenance.php --enable
# 2. Unpack into the theme directory (path depends on the version, see above).
unzip theme_chinijo-<release>-<version>.zip -d <moodle>[/public]/theme/
# 3. Install the plugin.
php admin/cli/upgrade.php --non-interactive
# 4. Purge caches.
php admin/cli/purge_caches.php
php admin/cli/maintenance.php --disable
```

Alpha and beta releases need `--allow-unstable` on `upgrade.php`; stable releases
do not. Alternatively, install from Site administration › Plugins › Install
plugins (ZIP upload), if EVAGD allows it.

Activate the theme where it is wanted (do not change the site default without
the Consejería's decision):

- Site default: Site administration › Appearance › Themes › Chinijo › Use theme
  (or `php admin/cli/cfg.php --name=theme --set=chinijo`).
- Only some courses or categories: enable `allowcoursethemes` /
  `allowcategorythemes` and choose Chinijo in their settings. Chinijo's hooks and
  callbacks do nothing on pages rendered by another theme.

## 4. Configuration

Site administration › Appearance › Themes › Chinijo:

### 4.1 General
- **Unneeded blocks**: same default as Boost.
- **Brand colour**: leave empty for the default (WCAG AA). Any other colour must
  keep 4.5:1 against white.

### 4.2 Advanced
Raw SCSS settings, as in Boost. Changes must not reduce contrast, hide focus
indicators or remove underlines from links in text.

### 4.3 EU funding notice (FEDER)
1. Ask the contract manager for the approved emblem file, its text alternative
   (for example the funding statement shown in the image) and the wording of the
   acknowledgement (Regulation (EU) 2021/1060, Article 47 and Annex IX). The theme
   ships none of them.
2. Upload the emblem (PNG, JPEG or WebP), fill in the text alternative and the
   acknowledgement, choose the pages (landing pages or every page) and enable it.
3. Check it on the login page, the site home and the dashboard, at 200 % zoom and
   on a tablet. Without an emblem-with-alternative or a text, nothing is shown.

### 4.4 Capabilities
`theme/chinijo:managepictograms` is given to editing teachers and managers. Review
it against EVAGD's custom roles.

### 4.5 Languages
The theme ships English and Spanish strings. The Spanish language pack must be
installed in EVAGD as usual; the theme never forces a language.

## 5. Pre-production verification checklist (pending until done)

Record results in `docs/accessibility-audit.md` and `docs/incident-and-patch-log.md`.

- [ ] Login through the corporate SSO; login page toolbar and FEDER notice when the
      Moodle login page is shown.
- [ ] Dashboard, course list, course page, an activity, an assignment submission,
      grades, messaging and course editing work as with Boost.
- [ ] EVAGD's own blocks, local plugins, course formats and filters render and work.
- [ ] Display settings: every option, save, reset, persistence after logout/login,
      language switch, guest access.
- [ ] Pictograms: upload, change, remove, learner view, hidden activities, course
      backup/restore behaviour (pictograms are not part of course backups yet).
- [ ] Course progress matches the dashboard's figures.
- [ ] Spanish interface (EVAGD language configuration).
- [ ] No conflict with other themes or customisations active in EVAGD.
- [ ] Accessibility audit and screen-reader runs (NVDA, VoiceOver, TalkBack) on
      the pre-production pages; tablets (Android and iPadOS, 10" and 12", both
      orientations); the two latest Chrome, Firefox, Safari and Edge.

## 6. Upgrade to a new Chinijo release

1. Read `CHANGELOG.md` and the release notes; check compatibility with the EVAGD
   Moodle version.
2. Back up database, `moodledata` and the current `theme/chinijo` directory.
3. Maintenance mode on; replace the `theme/chinijo` directory with the new one
   (delete the old directory first so removed files do not remain);
   `php admin/cli/upgrade.php --non-interactive`; purge caches; maintenance mode off.
4. Run the pre-production checklist (§5) before production.

## 7. Moodle or EVAGD upgrades

Adapting the theme to Moodle/EVAGD versions released after acceptance is not
covered by the warranty (specification §L1E01.G.2). Before upgrading EVAGD:
check `version.php` (`supported`), run the CI matrix against the new branch
(add it to `.github/workflows/ci.yml`), read Boost's `UPGRADING.md` for the new
version and test in pre-production.

## 8. Rollback

Chinijo does not change core tables. Its data is limited to user preferences
(`theme_chinijo_*`), the `theme_chinijo_pictogram` table, pictogram files and its
settings.

- **Fast rollback (keep data)**: set the previous theme as the site theme
  (`php admin/cli/cfg.php --name=theme --set=boost`), purge caches. Learners'
  preferences and pictograms remain stored but unused.
- **Previous Chinijo release**: Moodle does not downgrade plugins. Restore the
  previous `theme/chinijo` directory **and** the database/moodledata backup taken
  before the upgrade, or uninstall and reinstall the older release (losing its data).
- **Full removal**: switch theme, then Site administration › Plugins › Plugins
  overview › Chinijo › Uninstall. This deletes the table, files, settings and the
  theme's user preferences. Remove the directory afterwards.

Every rollback must be recorded in `docs/incident-and-patch-log.md`.

## 9. Caches and performance

- After any change of settings or files: `php admin/cli/purge_caches.php`.
- SCSS is compiled by Moodle on first use after a purge; the first request can take
  several seconds. `themedesignermode` must stay off in production.
- The theme adds no external requests. The Atkinson Hyperlegible font (≈17 KB per
  style) is downloaded only when a user chooses it.
