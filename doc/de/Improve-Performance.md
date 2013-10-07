How-to: Performance verbessern
==========

* [Zur Startseite der Hilfe](help)

Eine kleine Anleitung, um die Performance einer Friendica-Seite zu verbessern.

**Vorab:**

Wenn du Fragen zu den folgenden Anweisungen oder zu anderen Themen hast, dann kannst du jederzeit beim Friendica-Support unter https://helpers.pyxis.uberspace.de/profile/helpers nachfragen.

Systemeinstellungen
---------------

Geh auf /admin/site in deinem System und ändere die folgenden Werte: 

    setze "Qualität des JPEG Bildes" auf 50.

Dieser Wert reduziert die Daten, die vom Server an den Client geschickt werden. 50 ist ein Wert, der die Bildqualität nicht zu stark beeinflusst.

    setze "Intervall zum Vervollständigen von OStatus Unterhaltungen" auf "niemals"

Wenn du viele OStatus-Kontakte hast, dann kann die Vervollständigung von Unterhaltungen sehr zeitraubend sein. Der Nachteil: Du siehst nicht jede Antwort einer OStatus-Unterhaltung.

    setze "Pfad für die Sperrdatei" auf einen Ordner außerhalb deines Stammverzeichnisses deines Servers.

Sperrdateien sorgen dafür, dass Hintergrundprozesse nicht parallel ablaufen.

Als Beispiel: Es kann passieren, dass die poller.php länger als erwartet läuft. Ohne Sperrdatei kann es passieren, dass mehrere Instanzen der poller.php zur gleichen Zeit laufen. Dies würde das System verlangsamen und Einfluss auf die maximale Anzahl an Prozessen und Datenbankverbindungen nehmen.

Bitte definiere einen kompletten Pfad, auf den der Server einen Schreibzugriff hat. Wenn deine Seite unter "/var/www/namederseite/htdocs/" liegt, dann kannst du z.B. einen Ordner unter "/var/www/sitename/temp/" erstellen.

    setze "Nutze MySQL full text engine".

Wenn du MyISAM (Standardeinstellung) nutzt, dann beschleunigt dies die Suche.

    setze "Pfad zum Eintrag Cache" auf einen leeren Ordner außerhalb deines Stammverzeichnisses.

Verarbeiteter BBCode und einige externe Bilder werden hier gespeichert. BBCode verarbeiten ist ein zeitintensiver Prozess, der zudem eine hohe CPU-Leistung erfordert. 

Du kannst den gleichen Ordner nutzen, den du für die Sperrdatei genutzt hast. 

**Warnung!**

Der Ordner für den Eintrag-Cache wird regelmäßig geleert. Jede Datei, die die Cache-Dauer überschreitet, wird gelöscht. **Wenn du versehentlich den Cache-Pfad auf dein Stammverzeichnis legst, dann würde dir dies das gesamte Stammverzeichnis löschen.** 

Prüfe also doppelt, dass der gewählte Ordner nur temporäre Dateien enthält, die jederzeit gelöscht werden können. 

Plugins
--------

Aktiviere die folgenden Plugins: 

    Alternate Pagination
    Privacy Image Cache
    rendertime

###Alternate Pagination

**Beschreibung**

Dieses Plugin reduziert die Ladezeit der Datenbank massiv. Nachteil: Du kannst nicht mehr die Anzahl aller Seiten sehen. 

**Einrichtung**

Gehe auf admin/plugins/altpager und wähle "global".

###Privacy Image Cache

**Beschreibung**

Dieses Plugin lädt externe Inhalte vor und speichert sie im Cache. Neben der Beschleunigung der Seite dient es so außerdem dazu, die Privatssphäre der Nutzer zu schützen, da eingebettete Inhalte so von deiner Seite aus geladen werden und nicht von externen Quellen (die deine IP-Adresse ermitteln könnten). 

Ebenso hilft es bei Inhalten, die nur langsam laden oder nicht immer online sind. 

**Einrichtung**

Bitte erstelle einen Ordner namens "privacy_image_cache" und "photo" in deinem Stammverzeichnis. Wenn diese Ordner existieren, dann werden die zwischengespeicherten Inhalte dort abgelegt. Dies hat den großen Vorteil, dass der Server die Dateien direkt von dort bezieht. 

###rendertime

**Beschreibung**

Dieses Plugin beschleunigt dein System nicht, aber es hilft dabei, die Flaschenhälse zu ermitteln. 

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

###Cache-Control

**Beschreibung**

Dieses Modul weist den Client an, den Inhalt statischer Dateien zu speichern, um diese nicht immer wieder neu laden zu müssen. 

Aktiviere das Modul "mod_expires", indem du "a2enmod expires" als root eingibst.

Füge die folgenden Zeilen in die Apache-Konfiguration deiner Seite im "directory"-Bereich ein. 

ExpiresActive on ExpiresDefault "access plus 1 week"

Weitere Informationen findest du hier: http://httpd.apache.org/docs/2.2/mod/mod_expires.html.

###Compress content

**Beschreibung**

Dieses Modul komprimiert den Datenverkehr (Traffic) zwischen dem Webserver und dem Client. 

Aktiviere das Modul "mod_deflate" durch die Eingabe "a2enmod deflate" als root.

Weitere Informationen findest du hier: http://httpd.apache.org/docs/2.2/mod/mod_deflate.html


###PHP

**FCGI**

Wenn du Apache nutzt, dann denk darüber nach, FCGI zu nutzen. Wenn du eine Debian-basierte Distribution nutzt, dann wirst du die Pakete "php5-cgi" und "libapache2-mod-fcgid" benötigen. 
Nutze externe Dokumente, um eine detailiertere Erklärung für die Einrichtung eines Systems auf FCGI-Basis zu erhalten.

**APC**

APC ist ein Zwischenspeicher für die Verarbeitung des Befehlscodes. Es beschleunigt die Verarbeitung des PHP-Codes.

Wenn APC aktiviert ist, dann nutzt Friendica dies, um Konfigurationseinstellungen für verschiedene Anfragen zwischenzuspeichern. Dies beschleunigt die Reaktionszeit der Seite.

###Database

Es gibt Skripte wie [tuning-primer.sh](http://www.day32.com/MySQL/) und [mysqltuner.pl](http://mysqltuner.pl), die den Datenbankserver analysieren und Hinweise darauf geben, welche Werte verändert werden könnten. 
 
Aktivere hierfür die "Slow query" Log-Datei, um Performanceprobleme zu erkennen. 
