Chats
=====

* [Zur Startseite der Hilfe](help)

Du hast derzeit zwei Möglichkeiten, einen Chat auf Deiner Friendica-Seite zu betreiben

* IRC - Internet Relay Chat
* Jappix

##IRC Plugin

Sobald das Plugin aktiviert ist, kannst Du den Chat unter [deineSeite.de/irc](../irc) finden.
Beachte aber, dass dieser Chat auch ohne Anmeldung auf Deiner Seite zugänglich ist und somit auch Fremde diesen Chat mitnutzen können.

Wenn Du dem Link folgst, dann kommst Du zum Anmeldefenster des IR-Chats.
Wähle nun einen Spitznamen (Nickname) und wähle einen Raum aus, in dem Du chatten willst.
Hier kannst Du jeden Namen eingeben.
Es kann also auch #tollerChatdessenNamenurichkenne sein.
Gib als nächstes noch die Captchas ein, um zu zeigen, dass es sich bei Dir um einen Menschen handelt und klicke auf "Connect".

Im nächsten Fenster siehst Du zunächst viel Text beim Verbindungsaufbau, der allerdings für Dich nicht weiter von Bedeutung ist.
Anschließend öffnet sich das Chat-Fenster.
In den ersten Zeilen wird Dir Dein Name und Deine aktuelle IP-Adresse angezeigt.
Rechts im Fenster siehst Du alle Teilnehmer des Chats.
Unten hast Du ein Eingabefeld, um Beiträge zu schreiben.

Weiter Informationen zu IRC findest Du zum Beispiel auf <a href="http://wiki.ubuntuusers.de/IRC" target="_blank">ubuntuusers.de</a>, in <a href="https://de.wikipedia.org/wiki/Internet_Relay_Chat" target="_blank">Wikipedia</a> oder bei <a href="http://www.irchelp.org/" target="_blank">icrhelp.org</a> (in Englisch).

##Jappix Mini

Das Jappix Mini Plugin erlaubt das Erstellen einer Chatbox für Jabber/XMPP-Kontakte.
Ein Jabber/XMPP Account sollte vor der Installation bereits vorhanden sein.
Die ausführliche Anleitung dazu und eine Kontrolle, ob Du nicht sogar schon über Deinen E-Mail Anbieter einen Jabber-Account hast, findest Du unter <a href="http://einfachjabber.de" target="_blank">einfachjabber.de</a>.

Einige Server zum Anmelden eines neuen Accounts:

* [https://jappix.com](https://jappix.com)
* [https://www.jabme.de](https://www.jabme.de)
* [http://www.jabber.de](http://www.jabber.de)
* oder die Auswahl von [http://xmpp.net](http://xmpp.net) nutzen.

**1. Grundsätzliches**

Als erstes musst Du die aktuellste Version herunterladen:

Per Git:
<p style="font-family: courier; background-color: #CCCCCC; margin-left:25px; width: 450px;">
cd /var/www/&lt;Pfad zu Deiner friendica-Installation&gt;/addon; git pull
</p>

oder als normaler Download von hier: https://github.com/friendica/friendica-addons/blob/master/jappixmini.tgz (auf „view raw“ klicken)

Entpacke diese Datei (ggf. den entpackten Ordner in „jappixmini“ umbenennen) und lade sowohl den entpackten Ordner komplett als auch die .tgz Datei in den Addon Ordner Deiner Friendica Installation hoch.

Nach dem Upload gehts in den Friendica Adminbereich und dort zu den Plugins.
Aktiviere das Jappixmini Addon und gehe anschließend über die Plugins Seitenleiste (dort wo auch die Twitter-, Impressums-, GNU Social-, usw. Einstellungen gemacht werden) zu den Jappix Grundeinstellungen.

Setze hier den Haken zur Aktivierung des BOSH Proxys.
Weiter gehts in den Einstellungen Deines Friendica Accounts.

2. Einstellungen

Gehe bitte zu den Plugin-Einstellungen in Deinen Konto-Einstellungen (Account Settings).
Scrolle ein Stück hinunter bis zu den Jappix Mini Addon settings.

Aktiviere hier zuerst das Addon.

Trage nun Deinen Jabber/XMPP Namen ein, ebenfalls die entsprechende Domain bzw. den Server (ohne http, also zb einfach so: jappix.com).
Um das JavaScript Applet zum Chatten im Browser verwenden zu können, benötigst du einen BOSH Proxy.
Entweder betreibst du deinen eigenen (s. Dokumentation deines XMPP Servers) oder du verwendest einen öffentlichen BOSH Proxy.
Beachte aber, dass der Betreiber dieses Proxies den kompletten Datenverkehr über den Proxy mitlesen kann.
Siehe dazu auch die „Configuration Help“ unter den Eingabefeldern.
Gebe danach noch Dein Passwort an, und damit ist eigentlich schon fast alles geschafft.
Die weiteren Einstellmöglichkeiten bleiben Dir überlassen, sind also optional.
Jetzt noch auf „senden“ klicken und fertig.

Deine Chatbox sollte jetzt irgendwo unten rechts im Browserfenster „kleben“.
Falls Du manuell Kontakte hinzufügen möchtest, einfach den „Add Contact“-Knopf nutzen.

Viel Spass beim Chatten!
