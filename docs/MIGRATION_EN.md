# Migrating Akeeba Engage 3.4.3 to BlackBrix Comments 1.0.0

This is a **one-time migration on the same Joomla installation** (or a complete clone preserving article IDs, titles, aliases and the Joomla `$secret`). The domain, site path and database credentials may differ on a test clone.

## Order of operations

0. Make and verify a **full files-and-database backup**, including `configuration.php`.
1. On the existing Joomla installation **while the Akeeba Engage extension is still installed**, install the *separate* exporter `com_bbengageexport-1.0.0.zip`, export JSON and securely store it.
2. **Verify the JSON** against the Akeeba Engage comment totals and reply relationships. Save the backup and JSON before removing anything.
3. Uninstall the Akeeba Engage extension and its subextensions, then remove the temporary exporter. Check for leftover Akeeba Engage data tables. Do not blindly delete shared libraries required by other extensions or unverified tables.
4. Install `pkg_bbcomments-1.0.0.zip`. If upgrading from BlackBrix alpha, **upgrade in place; do not uninstall**.
5. In BlackBrix Comments → **Import (Engage JSON)**, upload the original JSON, inspect the preview and explicitly confirm the import.
6. Verify article mappings, published/unpublished statuses, parent-child replies, frontend/back-end display and notification opt-outs.

## Important safeguards

- Both the installer and JSON importer refuse to proceed while old Akeeba Engage extensions/records or Akeeba Engage data tables remain, including disabled extensions. Neither tool deletes the old data automatically.
- Article ID, title, alias, and site fingerprint must match the source. There is **no best-effort reassignment** to unrelated content.
- Duplicate import attempts are blocked by stored ID mappings; the import does not send bulk notification emails.
- Akeeba Engage `<h5>` headings are converted into `<p><strong>…</strong></p>` and all imported HTML passes through an allowlist.
- The exported JSON can contain personal data; keep it confidential and **do not publish it on GitHub**. IP addresses and browser identifiers are excluded.
- Export limits: 20,000 comments, 10 MiB JSON.

Test against a complete clone with the full unmodified original JSON file before using this workflow on a production installation.
