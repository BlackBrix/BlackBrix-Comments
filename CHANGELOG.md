# Changelog

## 1.0.2 — 2026-11-27

small changes in the forms XML templates: `comment.xml` and `comment_new.xml`

- introduced the `height` Parameter within the forms XML templates (controlling the vertical height of the textarea / TinyMCE editor).
- switched the labels for the fields `body` and `captcha` to visible by using `hiddenLabel="false"` within the forms XML templates.
- Updated the version in the package, component and all six plugin manifests to `1.0.2`, as well as the Web Asset Manager version in `joomla.asset.json`.
- Updated the README, migration documentation and release notes to refer to `1.0.2`; the separate legacy JSON exporter remains version `1.0.0`.
- **No intentional change** to the database schema, JSON migration, security checks, comment handling, moderation or notification logic.



## 1.0.1 — 2026-11-25

Maintenance release based on the fully tested 1.0.0/alpha9.3 functionality.

- The **new-comment form** now uses its dedicated language key `COM_BBCOMMENTS_COMMENTS_FORM_BTN_SUBMIT` instead of the edit-form key.
- Updated frontend translations: **Senden** (de-DE) and **Submit** (en-GB) for new comments. The edit form still uses **Speichern** / **Save**.
- Updated the version in the package, component and all six plugin manifests to `1.0.1`, as well as the Web Asset Manager version in `joomla.asset.json`.
- Included the eight existing `.htaccess` protection files in the repository sources, so the tracked source matches the published install package.
- Updated the README, migration documentation and release notes to refer to `1.0.1`; the separate legacy JSON exporter remains version `1.0.0`.
- **No intentional change** to the database schema, JSON migration, security checks, comment handling, moderation or notification logic.


## 1.0.0 — 2026-11-23

First public release based on the extensively Joomla-tested `0.1.0-alpha9.3` feature baseline.

- Set the package, component and six plugin versions to **1.0.0**.
- Removed misleading corporate suffixes from BlackBrix language strings and comments; the product name is simply **BlackBrix**.
- Included provenance and modification notices; preserved original copyright and GPL notices.
- Prepared release README, migration instructions and separately installable one-time JSON exporter.
- **No intentional functional change** to the JSON import, SQL schema, notifications, HTML filtering, threaded replies, or legacy-extension safety guards.

Refer to `CHANGELOG-alpha9.3.md` for the latest functional changes.
