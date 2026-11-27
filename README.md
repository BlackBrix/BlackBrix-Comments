# BlackBrix Comments 1.0.2

**A native comments and guestbook extension for Joomla 5.4.x / Joomla 6.2.x.** Licensed under GPL-3.0-or-later. German and English language packs are included.

BlackBrix Comments includes one Joomla package (`pkg_bbcomments`), a component (`com_bbcomments`) and six plugins. Features include article comments, per-article guestbooks, threaded replies, moderation, notification emails and opt-outs, backend searches/filters, and a validated JSON migration path for legacy comments.

## Installation / updates

1. **Back up your Joomla files and database.**
2. Install `pkg_bbcomments-1.0.2.zip` through the Joomla administrator's extension installer. Do **not** install the seven inner archives individually.
3. Review the component settings and plugins. On a clean installation the three customary plugins are initially enabled.
4. For updates from BlackBrix Comments `1.0.0` or `1.0.1` or an earlier alpha build, install the new package **over the existing version without uninstalling it**. Existing comments, settings, mail templates and opt-outs are retained.

Joomla 6 operation is designed not to require Joomla's backward-compatibility plugin. The functional baseline was tested with Joomla 5.4.10 / 6.2.0 and PHP 8.4 / 8.5. Version 1.0.2 is a small form-label and version-metadata update; perform a smoke test on a clone before production deployment.

## Migration from the legacy extension

The **one-time JSON exporter is a separate Joomla component**, available as `com_bbengageexport-1.0.0.zip`. It must be installed on the **old site before uninstalling the legacy extension**. It is *not* automatically installed by the BlackBrix package. Read [Migration (English)](docs/MIGRATION_EN.md) or [Migration (Deutsch)](docs/MIGRATION_DE.md) for the complete workflow.

The receiving Joomla site must preserve original article IDs, titles and aliases, and its Joomla site secret must match the source. The migration refuses ambiguous article matches or leftover legacy extensions/tables. Exported JSON files contain personal data (e.g. names and email addresses); keep them private and never commit them to a public repository.

## Source and license

This project contains modified GPL-licensed code; **original authors' legally required copyright/license notices are retained**. See [NOTICE.md](NOTICE.md) for provenance, third-party notices and modifications made in 2026. Full license: [LICENSE-GPLv3.txt](LICENSE-GPLv3.txt). No affiliation with or endorsement by the previous rights holders is implied.

- [Changelog](CHANGELOG.md)
- [1.0.2 release notes](docs/RELEASE_1.0.2.md)
- [1.0.1 release notes](docs/RELEASE_1.0.1.md)
- [1.0.0 release notes](docs/RELEASE_1.0.0.md)
- [Legacy exporter source](tools/legacy-exporter)

**No warranty.** Before production use, test the final 1.0.2 package on a backup/clone.
