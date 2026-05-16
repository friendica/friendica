# Häufig gestellte Fragen (Admin) - FAQ

## Kann ich mehrere Domains mit den selben Dateien aufsetzen?

Ja, das ist möglich.
Es ist allerdings nicht möglich, eine Datenbank durch zwei Domains zu nutzen.
Solange Du Deine config/local.config.php allerdings so einrichtest, dass das System nicht versucht, eine Installation durchzuführen, kannst Du die richtige Config-Datei in include/$hostname/config/local.config.php hinterlegen.
Alle Cache-Aspekte und der Zugriffsschutz können pro Instanz konfiguriert werden.

## Wo kann ich den Quellcode von Friendica, Addons und Themes finden?

Du kannst den Friendica-Quellcode [hier](https://github.com/friendica/friendica) finden.
Dort findest Du immer die aktuellste stabile Version von Friendica.
Der Quellcode von Friendica Red ist [hier](https://github.com/friendica/red) zu finden.

Addons findest Du auf [dieser Seite](https://github.com/friendica/friendica-addons).

Wenn Du neue Themen suchst, findest Du sie auf [github.com/bkil/friendica-themes](https://github.com/bkil/friendica-themes).

## Ich habe meine E-Mail Adresse geändern und jetzt ist das Admin Panel verschwunden?

Bitte aktualisiere deine E-Mail Adresse in der <tt>config/local.config.php</tt> Datei.

## Kann es mehr als einen Admin auf einer Friendica Instanz geben?

Ja.
Du kannst in der <tt>config/local.config.php</tt> Datei mehrere E-Mail Adressen auflisten.
Die aufgelisteten Adressen werden wie folgt durch Kommas voneinander getrennt:

```php
'admin_email' => 'mail1@example.com,mail2@example.com',
```

## Die Datenbank Struktur schein nicht aktuell zu sein. Was kann ich tun?

Rufe bitte im Admin Panel den Punkt [DB Updates](/admin/dbsync/) auf und folge dem Link *Datenbank Struktur überprüfen*.
Damit wird ein Hintergrundprozess gestartet der die Struktur deiner Datenbank überprüft und gegebenenfalls aktualisiert.

Du kannst das Struktur Updatee auch manuell auf der Kommandoeingabe ausführen.
Starte dazu bitte vom Grundverzeichnis deiner Friendica Instanz folgendes Kommand:

    bin/console dbstructure update

sollten bei der Ausführung Fehler auftreten, kontaktiere bitte die [Friendia Support](https://forum.friendi.ca/profile/helpers)  Gruppe oder die [Friendica Admins](https://forum.friendi.ca/profile/admins) Gruppe.
