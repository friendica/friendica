Konnektoren installieren (Facebook/Twitter/StatusNet) 
==================================================

* [Zur Startseite der Hilfe](help)

Friendica nutzt Plugins, um die Verbindung zu anderen Netzwerken wie Facebook und Twitter zu gewährleisten.

Es gibt außerdem ein Plugin, um über einen bestehenden Status.Net-Account diesen Service zu nutzen. 
Du brauchst dieses Plugin aber nicht, um mit Status.Net-Mitgliedern von Friendica aus zu kommunizieren - es sei denn, du wünschst es, über einen existierenden Account einen Beitrag zu schreiben. 

Alle drei Plugins benötigen einen Account im gewünschten Netzwerk. 
Zusätzlich musst du (bzw. der Administrator der Seite) einen API-Schlüssel holen, um einen authentifizierten Zugriff zu deinem Friendica-Server herstellen zu lassen.


**Seitenkonfiguration**

Plugins müssen vom Administrator installiert werden, bevor sie genutzt werden können. 
Dieses kann über das Administrationsmenü erstellt werden.

Jeder der Konnektoren benötigt zudem einen API-Schlüssel vom Service, der verbunden werden soll. 
Einige Plugins erlaube es, diese Informationen auf den Administrationsseiten einzustellen, wohingegen andere eine direkte Bearbeitung der Konfigurationsdatei ".htconfig.php" erfordern. 
Der Weg, um diese Schlüssel zu erhalten, variiert stark, jedoch brauchen fast alle einen bestehenden Account im gewünschten Service. 
Einmal installiert, können diese Schlüssel von allen Seitennutzern genutzt werden.

Im Folgenden findest du die Einstellungen für die verschiedenen Services (viele dieser Informationen kommen direkt aus den Quelldateien der Plugins):


**Twitter Plugin für Friendica**

* Author: Tobias Diekershoff
* tobias.diekershoff@gmx.net

* License:3-clause BSD license

Konfiguration:
Um dieses Plugin zu nutzen, benötigst du einen OAuth Consumer-Schlüsselpaar (Schlüssel und Geheimnis), das du auf der Seite [https://twitter.com/apps](https://twitter.com/apps) erhalten kannst

Registriere deine Friendica-Seite als "Client"-Anwendung mit "Read&Write"-Zugriff. Wir benötigen "Twitter als Login" nicht. Sobald du deine Anwendung installiert hast, erhältst du das Schlüsselpaar für deine Seite.

Trage dieses Schlüsselpaar in deine globale ".htconfig.php"-Datei ein.

```
$a->config['twitter']['consumerkey'] = 'your consumer_key here';
$a->config['twitter']['consumersecret'] = 'your consumer_secret here';
```

Anschließend kann der Nutzer deiner Seite die Twitter-Einstellungen selbst eintragen: "Einstellungen -> Connector Einstellungen".


**StatusNet Plugin für Friendica**

* Author: Tobias Diekershoff
* tobias.diekershoff@gmx.net

* License:3-clause BSD license

Konfiguration

Wenn das Addon aktiv ist, muss der Nutzer die folgenden Einstellungen vornehmen, um sich mit dem StatusNet-Account seiner Wahl zu verbinden.

* Die Basis-URL des StatusNet-API; für identi.ca ist es https://identi.ca/api/
* OAuth Consumer key & Geheimnis

Um das OAuth-Schlüsselpaar zu erhalten, muss der Nutzer

(a) seinen Friendica-Admin fragen, ob bereits ein Schlüsselpaar existiert oder 
(b) einen Friendica-Server als Anwendung auf dem StatusNet-Server anmelden.

Dies kann über Einstellungen --> Connections --> "Register an OAuth client application" -> "Register a new application" auf dem StatusNet-Server durchgeführt werden. 

Während der Registrierung des OAuth-Clients ist Folgendes zu beachten:

* Der Anwendungsname muss auf der StatusNet-Seite einzigartig sein, daher empfehlen wir einen Namen wie "friendica-nnnn", ersetze dabei "nnnn" mit einer frei gewählten Nummer oder deinem Webseitennamen.
* es gibt keine Callback-URL
* Registriere einen Desktop-Client
* stelle Lese- und Schreibrechte ein
* die Quell-URL sollte die URL deines Friendica-Servers sein

Sobald die benötigten Daten gespeichert sind, musst du deinen Friendica-Account mit StatusNet verbinden. 
Das kannst du über Einstellungen --> Connector-Einstellungen durchführen. 
Folge dem "Einloggen mit StatusNet"-Button, erlaube den Zugriff und kopiere den Sicherheitscode in die entsprechende Box. 
Friendica wird dann versuchen, die abschließende OAuth-Einstellungen über die API zu beziehen.

Wenn es geklappt hat, kannst du in den Einstellungen festlegen, ob deine öffentlichen Nachrichten automatisch in deinem StatusNet-Account erscheinen soll (achte hierbei auf das kleine Schloss-Symbol im Status-Editor)


**Installiere den Friendica/Facebook-Konnektor**

* Registriere einen API-Schlüssel für deine Seite auf [developer.facebook.com](Facebook).

Hierfür benötigst du einen Facebook-Account und ggf. weitere Authentifizierungen über eine Kreditkarten- oder Mobilfunknummer.

a. Wir würden uns sehr darüber freuen, wenn du "Friendica" in dem Anwendungsnamen eintragen würdest, um die Bekanntheit des Namens zu erhöhen. Das Friendica-Icon ist im Bildverzeichnis enthalten und kann als Anwendungs-Icon für die Facebook-App genutzt werden. Nutze [images/friendica-16.jpg](images/friendica-16.jpg) für das Icon und [images/friendica-128.jpg](images/friendica-128.jpg) für das Logo.

b. Die URL sollte deine Seite mit dem abschließenden Schrägstrich sein

Es **kann** notwendig sein, dass du eine "Privacy"- oder "Terms of service"-URL angeben musst.

c. Setze nun noch unter "App Domains" die URL auf deineSubdomain.deineDomain.de und bei "Website with Facebook Login" die URL zu deineDomain.de.

d. Installiere nun das Facebook-Plugin auf deiner Friendica-Seite über "admin/plugins". Du solltest links in der Sidebar einen Facebook-Link unter "Plugin Features" finden. Klicke diesen an.

e. Gib nun die App-ID und das App-Secret ein, die Facebook dir gegeben hat. Ändere die anderen Daten, wie es gewünscht ist.

Auf Friendica kann nun jeder Nutzer, der eine Verbindung zu Facebook wünscht, die Seite "Einstellungen -> Connector-Einstellungen" aufrufen und dort "Installiere Facebook-Connector" auswählen. 

Wähle die gewünschten Einstellungen für deine Nutzungs- und Privatsphäreansprüche.

Hier meldest du dich bei Facebook an und gibst dem Plugin die nötigen Zugriffsrechte, um richtig zu funktionieren. 
Erlaube dieses.

Und fertig. Um es abzustellen, gehe wieder auf die Einstellungsseite und auf "Remove Facebook posting".

Videos und eingebetteter Code werden nicht gepostet, wenn sonst kein anderer Inhalt enthalten ist. 
Links und Bilder werden in ein Format übertragen, das von der Facebook-API verstanden wird. 
Lange Texte werden verkürzt und mit einem Link zum Originalbeitrag versehen. 

Facebook-Kontakte können außerdem keine privaten Fotos sehen, da diese nicht richtig authentifiziert werden können, wenn sie deine Seite besuchen. 
Dieser Fehler wird zukünftig bearbeitet.
