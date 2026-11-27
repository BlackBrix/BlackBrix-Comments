# BlackBrix Comments v1.0.2 — Maintenance Release

**Release date:** 2026-11-27 · **License:** GPL-3.0-or-later

## Downloads

- `pkg_bbcomments-1.0.2.zip` — standard Joomla installation/upgrade package (component + six plugins).
- `com_bbengageexport-1.0.0.zip` — separate one-time legacy JSON exporter; unchanged since v1.0.0.

## Changes from v1.0.1

small changes in the forms XML templates: `comment.xml` and `comment_new.xml`

- introduced the `height` Parameter within the forms XML templates (controlling the vertical height of the textarea / TinyMCE editor).
- switched the labels for the fields `body` and `captcha` to visible by using `hiddenLabel="false"` within the forms XML templates.
- Updated the version in the package, component and all six plugin manifests to `1.0.2`, as well as the Web Asset Manager version in `joomla.asset.json`.
- Updated the README, migration documentation and release notes to refer to `1.0.2`; the separate legacy JSON exporter remains version `1.0.0`.
- **No intentional change** to the database schema, JSON migration, security checks, comment handling, moderation or notification logic.

## Upgrade

Make a full backup, then install `pkg_bbcomments-1.0.2.zip` over v1.0.0 or v1.0.1 without uninstalling. Verify the button texts, comments, and language overrides on a Joomla test clone before deploying to production.

## Source integrity

The source files in `extensions/` and `package/` (including version metadata and `.htaccess`) are aligned byte-for-byte with the published `pkg_bbcomments-1.0.2.zip` install archive. The separate exporter sources in `tools/legacy-exporter/` are unchanged and match `com_bbengageexport-1.0.0.zip`.

See [Changelog](https://github.com/BlackBrix/BlackBrix-Comments/blob/v1.0.2/CHANGELOG.md), [README](https://github.com/BlackBrix/BlackBrix-Comments/blob/v1.0.2/README.md), and [Migration](https://github.com/BlackBrix/BlackBrix-Comments/blob/v1.0.2/docs/MIGRATION_EN.md).
