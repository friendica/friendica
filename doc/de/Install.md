Friendica Installation
==========

* [Zur Startseite der Hilfe](help)

Wir haben hart daran gearbeitet, um Friendica auf vorgefertigten Hosting-Plattformen zum Laufen zu bringen - solche, auf denen auch Wordpress Blogs und Drupal-Installationen laufen.
Wir bieten eine manuelle und eine automatische Installation an.
Aber bedenke, dass Friendica mehr als eine einfache Webanwendung ist.
Es handelt sich um ein komplexes Kommunikationssystem, das eher an einen Email-Server erinnert als an einen Webserver.
Um die Verfügbarkeit und Performance zu gewährleisten, werden Nachrichten im Hintergrund verschickt und gespeichert, um sie später zu verschicken, wenn eine Webseite gerade nicht erreichbar ist.
Diese Funktionalität benötigt ein wenig mehr als die normalen Blogs.
Nicht jeder PHP/MySQL-Hosting-Anbieter kann Friendica unterstützen.
Viele hingegen können es. Aber **bitte** prüfe die Voraussetzungen deines Servers vor der Installation.

Wenn dir Fehler während der Installation auffallen, sag uns bitte über [Helper](http://forum.friendi.ca/profile/helpers) oder das [Entwickler Forum](https://forum.friendi.ca/profile/developers) Bescheid oder [erstelle ein Issue](https://github.com/friendica/friendica/issues).
Gib uns bitte so viele Infos zu deinem System, wie du kannst, und beschreibe den Fehler mit allen Details und Fehlermeldungen, so dass wir den Fehler zukünftig verhindern können.
Aufgrund der großen Anzahl an verschiedenen Betriebssystemen und PHP-Plattformen haben wir nur geringe Kapazitäten, um deine PHP-Installation zu debuggen oder fehlende Module zu ersetzen, aber wir tun unser Bestes, um allgemeine Code-Fehler zu beheben.
Falls du noch keinen Friendica-Account hast, kannst du dir einen temporären Account hier erstellen: [tryfriendica.de](https://tryfriendica.de).
Darüber kannst du den genannten Forum beitreten.
Der Account wird nach 7 Tagen ablaufen, aber du kannst einen Server-Admin fragen, diesen Account länger zu erhalten, sollte das  Problem nicht innerhalb dieser Zeit gelöst sein.

Bevor du anfängst: suche dir einen Domain- oder Subdomainnamen für deinen Server.
Dinge verändern sich und einige deiner Freunde haben möglicherweise Probleme, mit dir zu kommunizieren.
Wir planen, diese Einschränkung in einer zukünftigen Version zu beheben.


Requirements
---

* Apache mit einer aktiverten mod-rewrite-Funktion und dem Eintrag "Options All", so dass du die lokale .htaccess-Datei nutzen kannst
* PHP  5.6+ (PHP 7 ist aufgrund der Performance empfohlen)
  * PHP *Kommandozeilen*-Zugang mit register_argc_argv auf "true" gesetzt in der php.ini-Datei
  * Curl, GD, PDO, MySQLi, xml, zip und OpenSSL-Erweiterung
  * Das POSIX Modul muss aktiviert sein ([CentOS, RHEL](http://www.bigsoft.co.uk/blog/index.php/2014/12/08/posix-php-commands-not-working-under-centos-7http://www.bigsoft.co.uk/blog/index.php/2014/12/08/posix-php-commands-not-working-under-centos-7) haben dies z.B. deaktiviert)
  * etwas in der Art eines Email-Servers oder eines Gateways wie PHP mail()
* Mysql 5.5.3+ (oder eine äquivalente Alternative: MariaDB, Percona Server etc.)
* die Möglichkeit, wiederkehrende Aufgaben mit cron (Linux/Mac) oder "Scheduled Tasks" einzustellen (Windows) [Beachte: andere Optionen sind in Abschnitt 7 dieser Dokumentation zu finden]
* Installation in einer Top-Level-Domain oder Subdomain (ohne eine Verzeichnis/Pfad-Komponente in der URL) wird bevorzugt. Verzeichnispfade sind für diesen Zweck nicht so günstig und wurden auch nicht ausführlich getestet.

Installation
---

### Friendica

Entpacke die Friendica-Daten in das Quellverzeichnis (root) des Dokumentenbereichs deines Webservers.
Wenn du die Möglichkeit hierzu hast, empfehlen wir dir "git" zu nutzen, um die Daten direkt von der Quelle zu klonen, statt die gepackte .tar- oder .zip-Datei zu nutzen.
Das macht die Aktualisierung wesentlich einfacher.
Der Linux-Code, mit dem man die Dateien direkt in ein Verzeichnis wie "meinewebseite" kopiert, ist

    git clone https://github.com/friendica/friendica.git mywebsite
    cd mywebsite
    bin/composer.phar install

Stelle sicher, dass der Ordner *view/smarty3* existiert and von dem Webserver-Benutzer beschreibbar ist

    mkdir view/smarty3
    chmod 777 view/smarty3

Falls Addons installiert werden sollen: Gehe in den Friendica-Ordner 

    cd mywebsite

Und die Addon Repository klonst:

    git clone https://github.com/friendica/friendica-addons.git addon

Um das Addon-Verzeichnis aktuell zu halten, solltest du in diesem Pfad ein "git pull"-Befehl eintragen

    cd meinewebseite/addon
    git pull

Wenn du den Verzeichnispfad auf deinen Webserver kopierst, dann stelle sicher, dass du auch die .htaccess kopierst, da "Punkt"-Dateien oft versteckt sind und normalerweise nicht direkt kopiert werden.

### Erselle eine Datenbank

Erstelle eine leere Datenbank und notiere alle Zugangsdaten (Adresse der Datenbank, Nutzername, Passwort, Datenbankname).

Friendica benötigt die Berechtigungen um neue Felder in dieser Datenbank zu ertellen (create) und zu löschen (delete).

Mit neueren Versionen von MySQL (5.7.17+) musst du den `sql_mode` zu `''` (blank) setzen.
Benutze diese Einstellung, wenn der Installer nicht in der Lage ist, die Tabellen aufgrund eines Timestamp-Format Problems zu erstellen.
Falls dem so ist, finde den `[mysqld]` Bereich in deiner `my.conf` Datei und füge diese Zeile hinzu:

    sql_mode = ''

Starte MySQL dann neu und es sollte klappen.

### Option A: Der manuelle Installer

Besuche deine Webseite mit deinem Browser und befolge die Anleitung. 
Bitte beachte jeden Fehler und korrigiere diese, bevor du fortfährst.

Falls du einen Port für die Datenbankverbindung angeben musst, kannst du diesen in der Host-Eingabe Zeile angeben.

*Wenn* die manuelle Installation aus irgendeinem Grund fehlschlägt, dann prüfe das Folgende:
* ".htconfig.php" existiert ... wenn nicht, bearbeite die „htconfig.php“ und ändere die Systemeinstellungen. Benenne sie um in „.htconfig.php".
* die Datenbank beinhaltet Daten. ... wenn nicht, importiere den Inhalt der Datei "database.sql" mit phpmyadmin oder per mysql-Kommandozeile.

Besuche deine Seite an diesem Punkt wieder und registriere deinen persönlichen Account.
Alle Registrierungsprobleme sollten automatisch behebbar sein.
Wenn du irgendwelche **kritischen** Fehler zu diesen Zeitpunkt erhalten solltest, deutet das darauf hin, dass die Datenbank nicht korrekt installiert wurde.
Du kannst bei Bedarf die Datei .htconfig.php verschieben/umbenennen und die Datenbank leeren (als „Dropping“ bezeichnet), so dass du mit einem sauberen System neu starten kannst.

### Option B: Starte das manuelle Installationsscript

Öffne die Datei htconfig.php im Friendica-Hauptordner mit einem Text-Editor.
Entferne die `die('...');` Zeile und bearbeite die Einstellungen so, das sie zu deinem System passen (MySQL, Sprache, Theme etc.).
Dann speichere die Datei (jedoch nicht umbenennen).

Gehe in den Friendica-Hauptordner und führe den Kommandozeilen Befehl aus:

    bin/console autoinstall

Oder falls du alle optionalen Checks ausfürehn lassen möchtest, benutze diese Option:

    bin/console autoinstall -a

*Wenn* die automatisierte Installation aus irgendeinem Grund fehlschlägt, dann prüfe das Folgende:
* Existiert die `.htconfig.php`? Falls ja, wird die automatisierte Installation nicht gestartet.
* Sind Einstellungen in der `.htconfig.php` korrekt? Falls nicht, bitte bearbeite diese Datei erneut.
* Ist die leere MySQL-Datenbank erstellt? Falls nicht, erstelle diese.

Für mehr Informationen kannst du diese Option verwenden:

    bin/console autoinstall -v

### Einen Worker einrichten

Erstelle einen Cron job oder einen regelmäßigen Task, um den Poller alle 5-10 Minuten im Hintergrund ablaufen zu lassen. 
Beispiel:

    cd /base/directory; /path/to/php bin/worker.php

Ändere "/base/directory" und "/path/to/php" auf deine Systemvorgaben.

Wenn du einen Linux-Server nutzt, benutze den Befehl "crontab -e" und ergänze eine Zeile wie die Folgende; angepasst an dein System

`*/10 * * * * cd /home/myname/mywebsite; /usr/bin/php bin/worker.php`

Du kannst den PHP-Pfad finden, indem du den Befehl „which php“ ausführst.
Wenn du Schwierigkeiten mit diesem Schritt hast, kannst du deinen Hosting-Anbieter kontaktieren.
Friendica wird nicht korrekt laufen, wenn dieser Schritt nicht erfolgreich abgeschlossen werden kann.

Falls das Einrichten des cron nicht möglich ist, kannst Du alternativ den "frontend worker" vom Administrationsinterface aus aktivieren.

### Erstelle einen Backup Plan

Es werden schlimme Dinge geschehen.
Sei es nun ein Hardwareversagen oder eine kaputte Datenbank.
Deshalb solltest du dir, nachdem die Installation deines Friendica Knotens abgeschlossen ist, einen Backup Plan erstellen.

Die wichtigste Datei ist die `.htconfig.php` im Stammverzeichnis deiner Friendica Installation.
Und da alle Daten in der Datenbank gespeichert werden, solltest du einen nicht all zu alten Dump der Friendica Datenbank zur Hand haben, solltest du deinen Knoten wieder herstellen müssen.
