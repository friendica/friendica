How-to: Performance verbessern
==========

* [Zur Startseite der Hilfe](help)

Eine kleine Anleitung, um die Performance einer Friendica-Seite zu verbessern.

**Vorab:**

Wenn du Fragen zu den folgenden Anweisungen oder zu anderen Themen hast, dann kannst du jederzeit beim Friendica-Support unter https://forum.friendi.ca/profile/helpers nachfragen.

Systemeinstellungen
---------------

Geh auf /admin/site in deinem System und ändere die folgenden Werte:

    setze "Qualität des JPEG Bildes" auf 50.

Dieser Wert reduziert die Daten, die vom Server an den Client geschickt werden. 
50 ist ein Wert, der die Bildqualität nicht zu stark beeinflusst.

    setze "Intervall zum Vervollständigen von OStatus Unterhaltungen" auf "niemals"

Wenn du viele OStatus-Kontakte hast, dann kann die Vervollständigung von Unterhaltungen sehr zeitraubend sein. 
Der Nachteil: Du siehst nicht jede Antwort einer OStatus-Unterhaltung. Aus diesem Grund ist die Option "Beim Empfang von Nachrichten" in der Regel ein guter Kompromiss.

    setze "Nutze MySQL full text engine".

Wenn du MyISAM (Standardeinstellung) oder InnoDB mit MariaDB 10 nutzt, dann beschleunigt dies die Suche.

Addons
--------

Aktiviere die folgenden Addons:

    rendertime

### rendertime

**Beschreibung**

Dieses Addon beschleunigt dein System nicht, aber es hilft dabei, die Flaschenhälse zu ermitteln.

Wenn es aktiviert ist, dann siehst du Werte wie die folgenden auf jeder deiner Seiten:

    Performance: Database: 0.244, Network: 0.002, Rendering: 0.044, Parser: 0.001, I/O: 0.021, Other: 0.237, Total: 0.548

    Database: Dies ist die Zeit für alle Datenbankabfragen
    Network: Zeit, die benötigt wird, um Inhalte von externen Seiten vorzuladen
    Rendering: Zeit, die zum rendern des Themas benötigt wird
    Parser: Die Zeit, die der BBCode-Parser benötigt, um die Ausgabe der Seite zu erstellen
    I/O: Zeit, die der lokale Dateizugriff benötigt
    Others: alles andere :)
    Total: Die Summe aller genannten Werte

Diese Werte zeigen deine Performance-Probleme.

Webserver
----------

Wenn du einen Apache-Webserver nutzt, aktiviere bitte die folgenden Module:

### Cache-Control

**Beschreibung**

Dieses Modul weist den Client an, den Inhalt statischer Dateien zu speichern, um diese nicht immer wieder neu laden zu müssen.

Aktiviere das Modul "mod_expires", indem du "a2enmod expires" als root eingibst.

Füge die folgenden Zeilen in die Apache-Konfiguration deiner Seite im "directory"-Bereich ein.

ExpiresActive on ExpiresDefault "access plus 1 week"

Weitere Informationen findest du hier: http://httpd.apache.org/docs/2.2/mod/mod_expires.html.

### Compress content

**Beschreibung**

Dieses Modul komprimiert den Datenverkehr (Traffic) zwischen dem Webserver und dem Client.

Aktiviere das Modul "mod_deflate" durch die Eingabe "a2enmod deflate" als root.

Weitere Informationen findest du hier: http://httpd.apache.org/docs/2.2/mod/mod_deflate.html


### PHP

**FCGI**

Wenn du Apache nutzt, dann denk darüber nach, FCGI zu nutzen. 
Wenn du eine Debian-basierte Distribution nutzt, dann wirst du die Pakete "php5-cgi" und "libapache2-mod-fcgid" benötigen.
Nutze externe Dokumente, um eine detailiertere Erklärung für die Einrichtung eines Systems auf FCGI-Basis zu erhalten.

### Database

Es gibt Skripte wie [tuning-primer.sh](http://www.day32.com/MySQL/) und [mysqltuner.pl](http://mysqltuner.pl), die den Datenbankserver analysieren und Hinweise darauf geben, welche Werte verändert werden könnten.

Aktivere hierfür die "Slow query" Log-Datei, um Performanceprobleme zu erkennen.
