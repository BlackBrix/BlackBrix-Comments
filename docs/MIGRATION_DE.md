# Migration von Akeeba Engage 3.4.3 zu BlackBrix Comments 1.0.0

Die Migration ist **ein einmaliger Datentransfer auf derselben Joomla-Installation** oder auf einer vollständigen Kopie derselben Installation. Artikel-IDs, Titel und Aliase müssen identisch bleiben. Der im JSON dokumentierte Fingerprint bindet den Import zusätzlich an den Joomla-`$secret`. Domain, Verzeichnis und Datenbankname dürfen auf einem Testklon unterschiedlich sein.

## Empfohlene Reihenfolge

0. **Vollständiges Backup** der Datenbank und Joomla-Dateien einschließlich der `configuration.php` erstellen und Wiederherstellbarkeit prüfen.
1. **Auf der bestehenden Installation mit Akeeba Engage 3.4.3** die eigenständige Administratorkomponente `com_bbengageexport-1.0.0.zip` installieren, JSON-Datei exportieren und geschützt speichern.
2. **Export prüfen** (Kommentaranzahl, Artikelzuordnung, Antworten, veröffentlichte/nicht veröffentlichte Kommentare, Abmeldungen). Vor dem nächsten Schritt sicherstellen, dass Backup und Export vorliegen.
3. **Alte Akeeba Engage Erweiterung (ggf. einschließlich ihrer Untererweiterungen) deinstallieren** und anschließend den nur temporär benötigten Exporter entfernen. Bereits installierte, von anderen Erweiterungen benötigte Bibliotheken nicht ohne Prüfung löschen. Auf verbliebene alte Datentabellen achten: Der Installer **blockiert**, solange diese vorhanden sind. Keine Tabellen ungeprüft löschen.
4. **BlackBrix Comments 1.0.0 installieren** (`pkg_bbcomments-1.0.0.zip`). Eine vorhandene BlackBrix-alpha-Version **nur aktualisieren, nicht deinstallieren**.
5. Im BlackBrix-Backend **Import (Engage-JSON)** öffnen; Original-JSON hochladen und **Vorschau** prüfen. Erst dann den Import ausdrücklich bestätigen.
6. Anzahl, Autor und Zeitstempel, Freigabestatus, Antwortbeziehungen, Gästebuch und Artikelansichten sowie E-Mail-Abmeldungen kontrollieren. Beim Import werden keine Massenbenachrichtigungen verschickt.

## Schutzprüfungen

- Installer und Importer verweigern den Vorgang bei noch vorhandenen alten Erweiterungseinträgen oder Alt-Datentabellen (auch wenn deaktiviert).
- Fehlende oder abweichende Joomla-Artikel-IDs, Titel und Aliase führen zu einem Abbruch; es gibt **keine** automatische Ersatzzuordnung.
- Abweichender `site_fingerprint` verhindert den Import. Der Fingerprint wird aus dem Joomla-Secret berechnet; das Secret **nicht** in Issues, Logs oder Support-Nachrichten veröffentlichen.
- Bereits importierte Datensätze werden anhand von alten Kommentar-IDs und der Import-Zuordnungstabelle gegen Mehrfachimport geschützt.
- Das Import-HTML wird durch die HTML-Whitelist gefiltert. Historische `<h5>`-Überschriften werden in `<p><strong>…</strong></p>` umgewandelt.
- Der Exporter fügt keine IP- und Browserkennungen in die JSON-Datei ein. Die Exportdatei **kann Namen und E-Mail-Adressen enthalten** und muss vertraulich behandelt werden.
- Der Export ist auf 20.000 Kommentare / 10 MiB JSON begrenzt.

Ein Test mit der unveränderten Originaldatei auf einem vollständig passenden Klon ist ausdrücklich empfohlen. **Die produktive Quellinstallation nicht anhand eines bloßen Teilimports löschen.**
