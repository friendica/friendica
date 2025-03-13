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

Wenn dir Fehler während der Installation auffallen, sag uns bitte über [Helper](http://forum.friendi.ca/profile/helpers) oder die [Entwickler Gruppe](https://forum.friendi.ca/profile/developers) Bescheid oder [erstelle ein Issue](https://github.com/friendica/friendica/issues).
Gib uns bitte so viele Infos zu deinem System, wie du kannst, und beschreibe den Fehler mit allen Details und Fehlermeldungen, so dass wir den Fehler zukünftig verhindern können.
Aufgrund der großen Anzahl an verschiedenen Betriebssystemen und PHP-Plattformen haben wir nur geringe Kapazitäten, um deine PHP-Installation zu debuggen oder fehlende Module zu ersetzen, aber wir tun unser Bestes, um allgemeine Code-Fehler zu beheben.

Bevor du anfängst: suche dir einen Domain- oder Subdomainnamen für deinen Server.
Dinge verändern sich und einige deiner Freunde haben möglicherweise Probleme, mit dir zu kommunizieren.
Wir planen, diese Einschränkung in einer zukünftigen Version zu beheben.


Requirements
---

* Apache mit einer aktiverten mod-rewrite-Funktion und dem Eintrag "Options All", so dass du die lokale .htaccess-Datei nutzen kannst
* PHP  7.4+
  * PHP *Kommandozeilen*-Zugang mit register_argc_argv auf "true" gesetzt in der php.ini-Datei
  * Curl, GD, GMP, PDO, mbstrings, MySQLi, hash, xml, zip, IntlChar, IDN und OpenSSL-Erweiterung
  * Das POSIX Modul muss aktiviert sein ([CentOS, RHEL](http://www.bigsoft.co.uk/blog/index.php/2014/12/08/posix-php-commands-not-working-under-centos-7http://www.bigsoft.co.uk/blog/index.php/2014/12/08/posix-php-commands-not-working-under-centos-7) haben dies z.B. deaktiviert)
  * Einen E-Mail Server, so dass PHP `mail()` funktioniert.
    Wenn kein eigener E-Mail Server zur Verfügung steht, kann alternativ das [phpmailer](https://github.com/friendica/friendica-addons/tree/develop/phpmailer) Addon mit einem externen SMTP Account verwendet werden.
* Mysql Server mit Unterstützung vom InnoDB und Barracuda (wir empfehlen MariaDB da die Entwicklung mit solchen Server erfolgt, aber Alternativen wie MySQL, Percona Server etc. sollten auch funktionieren)
* die Möglichkeit, wiederkehrende Aufgaben mit cron (Linux/Mac) oder "Scheduled Tasks" einzustellen (Windows) [Beachte: andere Optionen sind in Abschnitt 7 dieser Dokumentation zu finden]
* Installation in einer Top-Level-Domain oder Subdomain (ohne eine Verzeichnis/Pfad-Komponente in der URL) wird bevorzugt. Verzeichnispfade sind für diesen Zweck nicht so günstig und wurden auch nicht ausführlich getestet.

Installation
---

### Alternative Wege um Friendica zu Installieren

Diese Anleitung wird dir Schritt-für-Schritt zeigen wie du Friendica auf deinem Server installieren kannst.
Falls du an automatischen Möglichkeiten interesse hast, wirf doch einen Blick auf

* das [Docker image für Friendica](https://github.com/friendica/docker) oder
* die [Installation von Friendica auf YunoHost](https://github.com/YunoHost-Apps/friendica_ynh).
* [Tutorial: Creating a Friendica Server with Ubuntu 22.04](https://nequalsonelifestyle.com/2022/07/30/creating-friendica-server-ubuntu/)
  * [Setting Up Friendica Daemon as a Systemd Service Tutorial](https://nequalsonelifestyle.com/2022/08/04/setting-up-friendica-daemon-systemd-service/)
* [Setting up Friendica on Unraid](https://www.jenovarain.com/2023/03/setting-up-friendica-on-unraid/) (NAS)
* [Installing Friendica with Elastio](https://elest.io/open-source/friendica)

### Friendica

Entpacke die Friendica-Daten in das Quellverzeichnis (root) des Dokumentenbereichs deines Webservers.
Wenn du die Möglichkeit hierzu hast, empfehlen wir dir "git" zu nutzen, um die Daten direkt von der Quelle zu klonen, statt die gepackte .tar- oder .zip-Datei zu nutzen.
Das macht die Aktualisierung wesentlich einfacher.
Der Linux-Code, mit dem man die Dateien direkt in ein Verzeichnis wie "meinewebseite" kopiert, ist

    git clone https://github.com/friendica/friendica.git -b stable mywebsite
    cd mywebsite
    bin/composer.phar run install:prod

Stelle sicher, dass der Ordner *view/smarty3* existiert and von dem Webserver-Benutzer beschreibbar ist

    mkdir view/smarty3
    chmod 775 view/smarty3

Falls Addons installiert werden sollen: Gehe in den Friendica-Ordner

    cd mywebsite

Und die Addon Repository klonst:

    git clone https://github.com/friendica/friendica-addons.git -b stable addon

Um das Addon-Verzeichnis aktuell zu halten, solltest du in diesem Pfad ein "git pull"-Befehl eintragen

    cd meinewebseite/addon
    git pull

Wenn du den Verzeichnispfad auf deinen Webserver kopierst, dann stelle sicher, dass du auch die .htaccess kopierst, da "Punkt"-Dateien oft versteckt sind und normalerweise nicht direkt kopiert werden.

Wenn du die Entwickler Version von Friendica verwenden möchtest kannst du auf den develop Branch im git Repository wechseln.
Dies tust du mit den folgenden Befehlen

    git checkout develop
    bin/composer.phar run install:prod
    cd addon
    git checkout develop

Die Entwickler Version kann nach einem fehlerhaften Commit vorübergehend Probleme haben oder gar nicht mehr funktionieren.
Sollte dir so etwas passieren, lass es uns bitte wissen, damit der Fehler behoben werden kann.

### Erstelle eine Datenbank

Erstelle eine leere Datenbank und notiere alle Zugangsdaten (Adresse der Datenbank, Nutzername, Passwort, Datenbankname).

Friendica benötigt die Berechtigungen um neue Felder in dieser Datenbank zu ertellen (create) und zu löschen (delete).

Mit neueren Versionen von MySQL (5.7.17+) musst du den `sql_mode` zu `''` (blank) setzen.
Benutze diese Einstellung, wenn der Installer nicht in der Lage ist, die Tabellen aufgrund eines Timestamp-Format Problems zu erstellen.
Falls dem so ist, finde den `[mysqld]` Bereich in deiner `my.conf` Datei und füge diese Zeile hinzu:

    sql_mode = ''

Starte MySQL dann neu und es sollte klappen.

### Option A: Der manuelle Installer

Besuche deine Webseite mit deinem Browser und befolge die Anleitung.
Bevor du dies tust, kopiere die Datei `.htaccess-dist` nach `.htaccess`, wenn du den Apache Webserver verwendest.
Bitte beachte jeden Fehler und korrigiere diese, bevor du fortfährst.

Falls du einen Port für die Datenbankverbindung angeben musst, kannst du diesen in der Host-Eingabe Zeile angeben.

*Wenn* die manuelle Installation aus irgendeinem Grund fehlschlägt, dann prüfe das Folgende:
* "config/local.config.php" existiert ... wenn nicht, bearbeite die „config/local-sample.config.php“ und ändere die Systemeinstellungen. Benenne sie um in „config/local.config.php".
* die Datenbank beinhaltet Daten. ... wenn nicht, importiere den Inhalt der Datei "database.sql" mit phpmyadmin oder per mysql-Kommandozeile.

Besuche deine Seite an diesem Punkt wieder und registriere deinen persönlichen Account.
Alle Registrierungsprobleme sollten automatisch behebbar sein.
Wenn du irgendwelche **kritischen** Fehler zu diesen Zeitpunkt erhalten solltest, deutet das darauf hin, dass die Datenbank nicht korrekt installiert wurde.
Du kannst bei Bedarf die Datei config/local.config.php verschieben/umbenennen und die Datenbank leeren (als „Dropping“ bezeichnet), so dass du mit einem sauberen System neu starten kannst.

### Option B: Starte das automatische Installationsscript

Es existieren folgende Varianten zur automatischen Installation von Friendica:
-	Eine vorgefertigte Konfigurationsdatei erstellen (z.B. `prepared.config.php`)
-	Verwendung von Umgebungsvariablen (z.B. `MYSQL_HOST`)
-	Verwendung von Optionen (z.B. `--dbhost <host>`)

Umgebungsvariablen und Optionen können auch kombiniert werden.
Dabei ist jedoch darauf zu achten, dass etwaige Optionen immer die zugehörigen Umgebungsvariablen überschreiben.

Für mehr Informationen kannst du diese Option verwenden:

    bin/console autoinstall -v

Falls du alle optionalen Checks ausfürehn lassen möchtest, benutze diese Option:

    bin/console autoinstall -a

*Wenn* die automatisierte Installation aus irgendeinem Grund fehlschlägt, dann prüfe das Folgende:
*	Existiert die `config/local.config.php`? Falls ja, wird die automatisierte Installation nicht gestartet.
*	Sind Einstellungen in der `config/local.config.php` korrekt? Falls nicht, bitte bearbeite diese Datei erneut.
*	Ist die leere MySQL-Datenbank erstellt? Falls nicht, erstelle diese.

#### B.1: Konfigurationsdatei

Für diese Variante muss ein Konfigurationsdatei bereits vor der Installation fertig definiert sein (z.B. [local-sample.config.php](config/local-sample.config.php).

Gehe im Anschluss in den Friendica-Hauptordner und führe den Kommandozeilen Befehl aus:

    bin/console autoinstall -f <prepared.config.php>

#### B.2: Umgebungsvariablen

Es existieren Zwei Arten von Umgebungsvariablen in Friendica:
-	Jene, die auch im normalen Betrieb verwendet werden können (derzeit ausschließlich **Datenbank Einstellungen**)
-	Jene, die nur während der Installation verwedent werden können (im normalen Betrieb werden sie ignoriert)

Umgebungsvariablen können auch durch adäquate Optionen (z.B. `--dbhost <hostname>`)übersteuert werden.

**Datenbank Einstellungen**

Nur wenn die Option `--savedb` gesetzt ist, werden diese Umgebungsvariablen auch in `config/local.config.php` gespeichert!

-	`MYSQL_HOST` Der Host der MySQL/MariaDB Datenbank
-	`MYSQL_PORT` Der Port der MySQL/MariaDB Datenbank
-	`MYSQL_USERNAME` Der Benutzername des MySQL Datenbanklogins (MySql - Variante)
-	`MYSQL_USER` Der Benutzername des MariaDB Datenbanklogins (MariaDB-Variante)
-	`MYSQL_PASSWORD` Das Passwort der MySQL/MariaDB Datenbanklogins
-	`MYSQL_DATABASE` Der Name der MySQL/MariaDB Datenbank

**Friendica Einstellungen**

Diese Umgebungsvariablen können nicht während des normalen Friendica Betriebs verwendet werden.
Sie werden stattdessen direkt in `config/local.config.php` gespeichert.

-	`FRIENDICA_PHP_PATH` Der Pfad zur PHP-Datei
-	`FRIENDICA_ADMIN_MAIL` Die Admin E-Mail Adresse dieses Friendica Knotens (wird auch für den Admin-Zugang benötigt)
-	`FRIENDICA_TZ` Die Zeitzone von Friendica
-	`FRIENDICA_LANG` Die Sprache von Friendica

Gehe im Anschluss in den Friendica-Hauptordner und führe den Kommandozeilen Befehl aus:

    bin/console autoinstall [--savedb]

#### B.3: Optionen

Alle Optionen werden in `config/local.config.php` gespeichert und überschreiben etwaige, zugehörige Umgebungsvariablen.

-	`-H|--dbhost <host>` Der Host der MySQL/MariaDB Datenbank (env `MYSQL_HOST`)
-	`-p|--dbport <port>` Der Port der MySQL/MariaDB Datenbank (env `MYSQL_PORT`)
-	`-U|--dbuser <username>` Der Benutzername des MySQL/MariaDB Datenbanklogins (env `MYSQL_USER` or `MYSQL_USERNAME`)
-	`-P|--dbpass <password>` Das Passwort der MySQL/MariaDB Datenbanklogins (env `MYSQL_PASSWORD`)
-	`-d|--dbdata <database>` Der Name der MySQL/MariaDB Datenbank (env `MYSQL_DATABASE`)
-	`-b|--phppath <path>` Der Pfad zur PHP-Datei (env `FRIENDICA_PHP_PATH`)
-	`-A|--admin <mail>` Die Admin E-Mail Adresse dieses Friendica Knotens (env `FRIENDICA_ADMIN_MAIL`)
-	`-T|--tz <timezone>` Die Zeitzone von Friendica (env `FRIENDICA_TZ`)
-	`-L|--lang <language>` Die Sprache von Friendica (env `FRIENDICA_LANG`)

Gehe in den Friendica-Hauptordner und führe den Kommandozeilen Befehl aus:

    bin/console autoinstall [options]

### Einen Worker einrichten

Erstelle einen Cron job oder einen regelmäßigen Task, um den Poller alle 5-10 Minuten im Hintergrund ablaufen zu lassen.
Beispiel:

    cd /base/directory; /path/to/php bin/console.php worker

Ändere "/base/directory" und "/path/to/php" auf deine Systemvorgaben.

Wenn du einen Linux-Server nutzt, benutze den Befehl "crontab -e" und ergänze eine Zeile wie die Folgende; angepasst an dein System

`*/10 * * * * cd /home/myname/mywebsite; /usr/bin/php bin/console.php worker`

Du kannst den PHP-Pfad finden, indem du den Befehl „which php“ ausführst.
Wenn du Schwierigkeiten mit diesem Schritt hast, kannst du deinen Hosting-Anbieter kontaktieren.
Friendica wird nicht korrekt laufen, wenn dieser Schritt nicht erfolgreich abgeschlossen werden kann.

Falls das Einrichten des cron nicht möglich ist, kannst Du alternativ den "frontend worker" vom Administrationsinterface aus aktivieren.

### Erstelle einen Backup Plan

Es werden schlimme Dinge geschehen.
Sei es nun ein Hardwareversagen oder eine kaputte Datenbank.
Deshalb solltest du dir, nachdem die Installation deines Friendica Knotens abgeschlossen ist, einen Backup Plan erstellen.

Die wichtigste Datei ist die `config/local.config.php` im Stammverzeichnis deiner Friendica Installation.
Und da alle Daten in der Datenbank gespeichert werden, solltest du einen nicht all zu alten Dump der Friendica Datenbank zur Hand haben, solltest du deinen Knoten wieder herstellen müssen.
