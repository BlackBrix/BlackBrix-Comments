# BlackBrix Comments v1.0.1 — Maintenance Release

**Release date:** 2026-11-25 · **License:** GPL-3.0-or-later

## Downloads

- `pkg_bbcomments-1.0.1.zip` — standard Joomla installation/upgrade package (component + six plugins).
- `com_bbengageexport-1.0.0.zip` — separate one-time legacy JSON exporter; unchanged since v1.0.0.

## Changes from v1.0.0

- Changed the new-comment submit button to use `COM_BBCOMMENTS_COMMENTS_FORM_BTN_SUBMIT`. Default labels: **Senden** (German) and **Submit** (English).
- Existing-comment edit button keeps `COM_BBCOMMENTS_COMMENTS_FORM_EDIT_BTN_SUBMIT` (defaults **Speichern** / **Save**).
- Package/component/plugin manifests and the web-asset metadata carry version `1.0.1`.
- The source tree now includes the eight `.htaccess` files that were already shipped in the Joomla install package.
- No intended changes to the importer/exporter, SQL schema, moderation, notifications, or guestbook functions.

## Upgrade

Make a full backup, then install `pkg_bbcomments-1.0.1.zip` over v1.0.0 without uninstalling. Verify the button texts, comments, and language overrides on a Joomla test clone before deploying to production.

## Source integrity

The source files in `extensions/` and `package/` (including version metadata and `.htaccess`) are aligned byte-for-byte with the published `pkg_bbcomments-1.0.1.zip` install archive. The separate exporter sources in `tools/legacy-exporter/` are unchanged and match `com_bbengageexport-1.0.0.zip`.

See [Changelog](../CHANGELOG.md), [README](../README.md), and [Migration](MIGRATION_EN.md).
