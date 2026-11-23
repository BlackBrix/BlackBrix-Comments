# BlackBrix Comments v1.0.0 — Initial Public Release

**Release date:** 2026-11-23 · **License:** GPL-3.0-or-later

## Download assets

- `pkg_bbcomments-1.0.0.zip`: the normal Joomla install/upgrade package (component + 6 plugins).
- `com_bbengageexport-1.0.0.zip`: **separate** administrator component for one-time exporting from the old extension. Do not install it as a BlackBrix subextension.
- SHA256SUMS file for download verification.

## Tested baseline

This release is based on the alpha9.3 build, which passed user runtime testing on Joomla 5.4.10 and 6.2.0 (PHP 8.4 / 8.5), including updates, installer migration guards, complete historical JSON import (21 comments / 10 articles / 4 replies), duplicate protection, DE/EN errors, mail/privacy behavior, comment hierarchy and template-independent reply indentation.

**Compared with alpha9.3:** release version changes, documentation, attribution notice, and text-only branding cleanup. No intentional logic/schema changes. A final smoke-test of the newly versioned 1.0.0 ZIP on a Joomla clone is recommended before production deployment.

See [README.md](../README.md), [NOTICE.md](../NOTICE.md) and [Migration instructions](MIGRATION_EN.md).
