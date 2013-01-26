Chats
=====

* [Zur Startseite der Hilfe](help)

Du hast derzeit zwei Möglichkeiten, einen Chat auf deiner Friendica-Seite zu betreiben 

* IRC - Internet Relay Chat
* Jappix

##IRC Plugin

Sobald das Plugin aktiviert ist, kannst du den Chat unter [deineSeite.de/irc](../irc) finden. Beachte aber, dass dieser Chat auch ohne Anmeldung auf deiner Seite zugänglich ist und somit auch Fremde diesen Chat mitnutzen können. 

Wenn du dem Link folgst, dann kommst du zum Anmeldefenster des IR-Chats. Wähle nun einen Spitznamen (Nickname) aus und wähle einen Raum aus, in dem du chatten willst. Hier kannst du jeden Namen eingeben. Es ist also auch #tollerChatdessenNamenurichkenne sein. Gib als nächstes nur noch die Captchas ein, um zu zeigen, dass es sich bei dir um einen Menschen handelt. Gehe nun auf "Connect".

Im nächsten Fenster siehst du zunächst viel Text beim Verbindungsaufbau, der allerdings für dich nicht weiter von Bedeutung ist. Anschließend öffnet sich das Chat-Fenster. In den ersten Zeilen wird dir dein Name und deine aktuelle IP-Adresse angezeigt. Rechts im Fenster siehst du alle Teilnehmer des Chats. Unten hast du ein Eingabefeld, um Beiträge zu schreiben.

##Jappix Mini

Das Jappix Mini Plugin erlaubt das Erstellen einer Chatbox für Jabber/XMPP-Kontakte. Ein Jabber/XMPP Account sollte vor der Installation bereits vorhanden sein.
Eine ausführliche Anleitung dazu und eine Kontrolle, ob man nicht sogar schon über seinen E-Mail Anbieter einen Jabber-Account hat, findet man unter http://einfachjabber.de.
Einige Server zum Anmelden eines neuen Accounts:

* [https://jappix.com](https://jappix.com)
* [https://www.jabme.de](https://www.jabme.de)
* [http://www.jabber.de](http://www.jabber.de)
* oder die Auswahl von [http://xmpp.net](http://xmpp.net) nutzen.

**1. Grundsätzliches**

Als erstes muss die aktuellste Version heruntergeladen werden, per Git:

cd /var/www/virtual/DEINUBERSPACE/html/addon; git pull

oder als normaler Download von hier: https://github.com/friendica/friendica-addons/blob/master/jappixmini.tgz (auf „view raw“ klicken)

Diese Datei wird entpackt, ggf. den entpackten Ordner in „jappixmini“ umbenennen und sowohl den kompletten entpackten Ordner als auch die .tgz Datei in den Addon Ordner deiner Friendica Installation hochladen.

Nach dem Upload gehts in den Friendica Adminbereich, dort zu den Plugins. Das Jappixmini Addon aktivieren und anschließend über die Plugins Seitenleiste (dort wo auch die Twitter-, Impressums-, StatusNet-, usw Einstellungen gemacht werden) zu den Jappix Grundeinstellungen gehen.

Hier den Haken zur Aktivierung des BOSH Proxys setzen.
Weiter gehts in den Einstellungen deines Friendica Account.

**2. Einstellungen**

In deinen Einstellungen (Account Settings), gehe bitte zu den Plugin-Einstellungen. Etwas scrollen bis zu Jappix Mini addon settings

Hier zuerst das Addon aktvieren.

Trage nun deinen Jabber/XMPP Namen ein, ebenfalls die entsprechende Domain bzw. den Server (ohne http, also zb einfach so: jappix.com). Bei „Jabber BOSH Host“ kannst du erstmal “https://bind.jappix.com/ “ eintragen. Siehe dazu auch die „Configuration Help“ weiter unten. Danach noch dein Passwort und damit ist eigentlich schon fast alles geschafft. Die weiteren Einstellmöglichkeiten bleiben dir überlassen, sind also optional. Jetzt noch auf „senden“ klicken und fertig.

Falls du manuell Kontakte hinzufügen möchtest, einfach den „Add Contact“ Knopf nutzen. Deine Chatbox sollte jetzt irgendwo unten rechts im Browserfenster „kleben“. Viel Spass beim Chatten! 
