# Konnektoren installieren

Friendica verwendet Konnektoren, um sich mit einigen Netzwerken zu verbinden, wie Tumblr oder Bluesky.

Alle diese Konnektoren erfordern einen Account im Zielnetzwerk.
Außerdem musst du (oder die Server-Administration) in der Regel einen API-Schlüssel erhalten, um die Verbindung zu ermöglichen.

**Seitenkonfiguration**

Konnektoren müssen von der Server-Administration installiert werden, bevor sie verwendet werden können.
Dies geschieht über die Server-Verwaltung.

Einige der Konnektoren erfordern auch einen „API-Schlüssel“ des Dienstes, mit dem du dich verbinden möchtest.
Für Tumblr findet man diese Informationen auf den Seiten der Server-Verwaltung, während für Twitter (X) jede Person einen eigenen API-Schlüssel erstellen muss.
Andere Konnektoren, wie Bluesky, benötigen überhaupt keinen API-Schlüssel.

Weitere Informationen zu den spezifischen Anforderungen findest du auf der Einstellungsseite des jeweiligen Addons, entweder auf der Verwaltungsseite oder auf der Benutzerseite.

## Bluesky Jetstream

Um die Konnektivität mit Bluesky weiter zu verbessern, kann die „Jetstream“-Konnektivität aktiviert werden.
Jetstream ist ein Dienst, der sich mit dem Bluesky-Firehose verbindet.
Mit Jetstream kommen die Nachrichten in Echtzeit an und müssen nicht erst abgefragt werden.
Es ermöglicht auch die Echtzeitverarbeitung von Blöcken oder Tracking-Aktivitäten, die über die Bluesky-Website oder -Anwendung durchgeführt werden.

Um die Jetstream-Verarbeitung zu aktivieren, führe `bin/console.php daemon' über die Befehlszeile aus.
Du musst vorher die Prozess-ID-Datei in local.config.php im Abschnitt „jetstream“ mit dem Schlüssel „pidfile“ definieren.

Um die verarbeiteten Nachrichten und die Drift (die Zeitdifferenz zwischen dem Datum der Nachricht und dem Datum, an dem das System diese Nachricht verarbeitet hat) zu verfolgen, wurden dem Statistik-Endpunkt einige Felder hinzugefügt.
