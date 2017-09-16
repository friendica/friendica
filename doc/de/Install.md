Friendica Installation 
==========

* [Zur Startseite der Hilfe](help)

Wir haben hart daran gearbeitet, um Friendica auf vorgefertigten Hosting-Plattformen zum Laufen zu bringen - solche, auf denen auch Wordpress Blogs und Drupal-Installationen laufen. 
Aber bedenke, dass Friendica mehr als eine einfache Webanwendung ist. 
Es handelt sich um ein komplexes Kommunikationssystem, das eher an einen Email-Server erinnert als an einen Webserver. 
Um die Verfügbarkeit und Performance zu gewährleisten, werden Nachrichten im Hintergrund verschickt und gespeichert, um sie später zu verschicken, wenn eine Webseite gerade nicht erreichbar ist. 
Diese Funktionalität benötigt ein wenig mehr als die normalen Blogs. 
Nicht jeder PHP/MySQL-Hosting-Anbieter kann Friendica unterstützen. 
Viele hingegen können es. Aber **bitte** prüfe die Voraussetzungen deines Servers vor der Installation. 

Wenn dir Fehler während der Installation auffallen, sag uns bitte über http://bugs.friendica.com Bescheid. 
Gib uns bitte so viele Infos zu deinem System, wie du kannst, und beschreibe den Fehler mit allen Details und Fehlermeldungen, so dass wir den Fehler zukünftig verhindern können. 
Aufgrund der großen Anzahl an verschiedenen Betriebssystemen und PHP-Plattformen haben wir nur geringe Kapazitäten, um deine PHP-Installation zu debuggen oder fehlende Module zu ersetzen, aber wir tun unser Bestes, um allgemeine Code-Fehler zu beheben.

Bevor du anfängst: suche dir einen Domain- oder Subdomainnamen für deinen Server. 
Dinge verändern sich und einige deiner Freunde haben möglicherweise Probleme, mit dir zu kommunizieren. 
Wir planen, diese Einschränkung in einer zukünftigen Version zu beheben. 


1. Voraussetzungen
    - Apache mit einer aktiverten mod-rewrite-Funktion und dem Eintrag "Options All", so dass du die lokale .htaccess-Datei nutzen kannst
    - PHP  5.2+. Je neuer, desto besser. Du benötigst 5.3 für die Authentifizierung untereinander. In einer Windows-Umgebung arbeitet die Version 5.2+ möglicherweise nicht, da die Funktion dns_get_record() erst ab Version 5.3 verfügbar ist.
        - PHP *Kommandozeilen*-Zugang mit register_argc_argv auf "true" gesetzt in der php.ini-Datei
        - Curl, GD, PDO, MySQLi und OpenSSL-Erweiterung
        - etwas in der Art eines Email-Servers oder eines Gateways wie PHP mail()
    - Mysql 5.x
    - die Möglichkeit, wiederkehrende Aufgaben mit cron (Linux/Mac) oder "Scheduled Tasks" einzustellen (Windows) [Beachte: andere Optionen sind in Abschnitt 7 dieser Dokumentation zu finden] 
    - Installation in einer Top-Level-Domain oder Subdomain (ohne eine Verzeichnis/Pfad-Komponente in der URL) wird bevorzugt. Verzeichnispfade sind für diesen Zweck nicht so günstig und wurden auch nicht ausführlich getestet.


    [Dreamhost.com bietet ein ausreichendes Hosting-Paket mit den nötigen Features zu einem annehmbaren Preis. Wenn dein Hosting-Anbieter keinen Unix-Zugriff erlaubt, kannst du Schwierigkeiten mit der Einrichtung der Webseite haben. 
    
    1.1. APT-Pakete
		- Apache: sudo apt-get install apache2
		- PHP5: sudo apt-get install php5
			- PHP5-Zusätzliche Pakete: sudo apt-get install php5-curl php5-gd php5-mysql
		- MySQL: sudo apt-get install mysql-server

2. Entpacke die Friendica-Daten in das Quellverzeichnis (root) des Dokumentenbereichs deines Webservers.

    - Wenn du die Möglichkeit hierzu hast, empfehlen wir dir "git" zu nutzen, um die Daten direkt von der Quelle zu klonen, statt die gepackte .tar- oder .zip-Datei zu nutzen. Das macht die Aktualisierung wesentlich einfacher. Der Linux-Code, mit dem man die Dateien direkt in ein Verzeichnis wie "meinewebseite" kopiert, ist
    
        `git clone https://github.com/friendica/friendica.git meinewebseite`

    - und dann kannst du die letzten Änderungen immer mit dem folgenden Code holen

        `git pull`
    
    - Addons installieren 
        - zunächst solltest du **in** deinem Webseitenordner sein
        
            `cd meinewebseite`
            
        - dann kannst du das Addon-Verzeichnis seperat kopieren 
        
            `git clone https://github.com/friendica/friendica-addons.git addon`
            
        - Um das Addon-Verzeichnis aktuell zu halten, solltest du in diesem Pfad ein "git pull"-Befehl eintragen
        
            `cd meinewebseite/addon`
            
            `git pull`
            
    - Wenn du den Verzeichnispfad auf deinen Webserver kopierst, dann stelle sicher, dass du auch die .htaccess kopierst, da "Punkt"-Dateien oft versteckt sind und normalerweise nicht direkt kopiert werden. 


3. Erstelle eine leere Datenbank und notiere alle Zugangsdaten (Adresse der Datenbank, Nutzername, Passwort, Datenbankname).

Friendica benötigt die Berechtigungen um neue Felder in dieser Datenbank zu ertellen (create) und zu löschen (delete).

4. Besuche deine Webseite mit deinem Browser und befolge die Anleitung. Bitte beachte jeden Fehler und korrigiere diese, bevor du fortfährst.

5. *Wenn* die automatisierte Installation aus irgendeinem Grund fehlschlägt, dann prüfe das Folgende:

    - ".htconfig.php" existiert ... wenn nicht, bearbeite die „htconfig.php“ und ändere die Systemeinstellungen. Benenne sie um in „.htconfig.php"
“
    - die Datenbank beinhaltet Daten. ... wenn nicht, importiere den Inhalt der Datei "database.sql" mit phpmyadmin oder per mysql-Kommandozeile.

6. Besuche deine Seite an diesem Punkt wieder und registriere deinen persönlichen Account. Alle Registrierungsprobleme sollten automatisch behebbar sein. 
Wenn du irgendwelche **kritischen** Fehler zu diesen Zeitpunkt erhalten solltest, deutet das darauf hin, dass die Datenbank nicht korrekt installiert wurde. Du kannst bei Bedarf die Datei .htconfig.php verschieben/umbenennen und die Datenbank leeren (als „Dropping“ bezeichnet), so dass du mit einem sauberen System neu starten kannst.

7. Erstelle einen Cron job oder einen regelmäßigen Task, um den Poller alle 5-10 Minuten im Hintergrund ablaufen zu lassen. Beispiel:

    `cd /base/directory; /path/to/php include/poller.php`

Ändere "/base/directory" und "/path/to/php" auf deine Systemvorgaben.

Wenn du einen Linux-Server nutzt, benutze den Befehl "crontab -e" und ergänze eine Zeile wie die Folgende; angepasst an dein System

`*/10 * * * * cd /home/myname/mywebsite; /usr/bin/php include/poller.php`

Du kannst den PHP-Pfad finden, indem du den Befehl „which php“ ausführst. 
Wenn du Schwierigkeiten mit diesem Schritt hast, kannst du deinen Hosting-Anbieter kontaktieren. 
Friendica wird nicht korrekt laufen, wenn dieser Schritt nicht erfolgreich abgeschlossen werden kann.

Alternativ kannst du das Plugin 'poormancron' nutzen, um diesen Schritt durchzuführen, wenn du eine aktuelle Friendica-Version nutzt. 
Um dies zu machen, musst du die ".htconfig.php" an der Stelle anpassen, die dein Plugin beschreibt. 
In einer frischen Installation sieht es aus wie: 

`$a->config['system']['addon'] = 'js_upload';`

Dies setzt voraus, dass das Addon-Modul "js_upload" aktiviert ist. 
Du kannst auch weitere Addons/Plugins ergänzen. Ändere den Eintrag folgendermaßen ab:

`$a->config['system']['addon'] = 'js_upload,poormancron';`

und speichere deine Änderungen.
