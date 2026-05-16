# Export / Import von gefolgten Kontakte

Zusätzlich zum [Umziehen des Accounts](help/user/move-account) kannst du die Liste der von dir gefolgten Kontakte exportieren und importieren.
Die exportierte Liste wird als CSV Datei in einem zu anderen Plattformen, z.B. Mastodon, Misskey oder Pleroma, kompatiblen Format gespeichert.

## Export der gefolgten Kontakte

Um die Liste der Kontakte *denen du folgst* zu exportieren, geht die [Einstellungen persönliche Daten exportieren](settings/userexport) und klicke den [Exportiere Kontakte als CSV](settings/userexport/contact) an.

## Import der gefolgten Kontakte

Um die Kontakt CSV Datei zu importieren, gehe zu [Kontakte Importieren](settings/importcontacts).
Hier kannst du die CSV Datei auswählen und hoch laden.

### Unterstütztes Datei Format

Die CSV Datei *muss* mindestens eine Spalte beinhalten.
In der ersten Spalte der Tabelle *sollte* entweder das Handle oder die URL des gefolgten Kontakts.
(Ein Kontakt pro Zeile.)
Alle anderen Spalten der CSV Datei werden beim Importieren ignoriert.
