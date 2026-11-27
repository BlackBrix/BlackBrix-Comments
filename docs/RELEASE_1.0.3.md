# BlackBrix Comments v1.0.3 — Maintenance Release

**Release date:** 2026-11-27 · **License:** GPL-3.0-or-later

## Downloads

- `pkg_bbcomments-1.0.3.zip` — Joomla install/upgrade package containing the component and six plugins.
- `com_bbengageexport-1.0.0.zip` — **separate**, optional one-time JSON exporter for migration from the old extension; its version and code are unchanged.

## Changes from v1.0.2

- The new-comment form now includes a regular translated CAPTCHA field label, using `COM_BBCOMMENTS_COMMENT_FIELD_CAPTCHA`. German and English default text: **Captcha:**
- The form continues to specify `hiddenLabel="false"`. Whether that preference is honored depends on the selected CAPTCHA provider. The separately maintained Captcha QA plugin is **not** part of this package; an unmodified Captcha QA 2.1.1 overrides `hiddenLabel` and hides the label. Use a Captcha QA build with the discussed `setupField()` correction when a visible label is desired.
- Removed duplicate, identical `COM_BBCOMMENTS_TITLE_EMAILTEMPLATES` entries from both administrator language files. The translation values remain unchanged.
- Updated package/component/plugin and Web Asset Manager versions to `1.0.3`, and refreshed current documentation.
- No intended changes to the database schema, import/export logic, PHP/JavaScript logic, moderation, emails or spam filtering.

## Upgrade

Create a full files-and-database backup. Install `pkg_bbcomments-1.0.3.zip` **over** an existing BlackBrix Comments v1.0.0, v1.0.1, v1.0.2 or earlier alpha installation; **do not uninstall** the old version, as uninstalling may remove data. Confirm that comments, language labels and the configured CAPTCHA still work on a Joomla test clone before deploying to production.

The one-time Engage migration exporter remains separate and is needed only when migrating legacy comments; it is not an update dependency.

## Source and integrity

The install package is assembled from the `extensions/` and `package/` source tree for this release. Its child archives contain the corresponding files with no `.git` directories. Static syntax/manifest tests are not a substitute for Joomla runtime testing.

See [Changelog](https://github.com/BlackBrix/BlackBrix-Comments/blob/v1.0.3/CHANGELOG.md), [README](https://github.com/BlackBrix/BlackBrix-Comments/blob/v1.0.3/README.md), [Migration (English)](https://github.com/BlackBrix/BlackBrix-Comments/blob/v1.0.3/docs/MIGRATION_EN.md), and [Migration (Deutsch)](https://github.com/BlackBrix/BlackBrix-Comments/blob/v1.0.3/docs/MIGRATION_DE.md).
