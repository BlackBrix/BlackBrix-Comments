# BlackBrix Engage JSON Exporter 1.0.0

A small, standalone Joomla administrator component for a **one-time export from Akeeba Engage 3.4.3**. It uses only Joomla's database APIs and does not require any Akeeba or FOF runtime code. The exporter does not write or delete any data.

**Installation and use:** Install the exporter on a Joomla 5 or Joomla 6 installation running Engage 3.4.3. In the Joomla administrator area, open **BlackBrix – Engage Export** and download the versioned JSON file as a Super User. Store the file securely, as it contains names and email addresses. Only after verifying the export and creating a complete backup should you fully uninstall Engage and its related extensions. Then install **BlackBrix Comments 1.0.3**, open **Import (Engage JSON)**, review the import preview, and confirm the import.

**Mandatory restriction:** The migration must take place within the same Joomla installation, or a faithful clone, with unchanged Joomla article IDs and the same Joomla site secret. The JSON file contains the article ID, title, and alias for verification; it does not contain any search-and-replace rules. The import is blocked if the article mapping does not match.

The export includes comments, replies, publication status, timestamps, author data, and any existing email notification opt-outs from `#__engage_unsubscribe`. IP addresses and browser identifiers are deliberately excluded.

**Before uninstalling Engage:** Create a complete backup of both the files and the database, inspect the exported JSON file, and compare its contents with the original database records. Test the migration on backup clones before using it on a production site.

**Limits:** Up to 20,000 comments / 10 MiB JSON.

**License:** GPL-3.0-or-later.
