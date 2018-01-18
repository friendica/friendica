Konnektoren installieren (Twitter/GNU Social) 
==================================================

* [Zur Startseite der Hilfe](help)

Friendica nutzt Erweiterung, um die Verbindung zu anderen Netzwerken wie Twitter oder App.net zu gewährleisten.

Es gibt außerdem ein Erweiterung, um über einen bestehenden GNU Social-Account diesen Service zu nutzen. 
Du brauchst dieses Erweiterung aber nicht, um mit GNU Social-Mitgliedern von Friendica aus zu kommunizieren - es sei denn, du wünschst es, über einen existierenden Account einen Beitrag zu schreiben. 

Alle drei Erweiterung benötigen einen Account im gewünschten Netzwerk. 
Zusätzlich musst du (bzw. der Administrator der Seite) einen API-Schlüssel holen, um einen authentifizierten Zugriff zu deinem Friendica-Server herstellen zu lassen.


**Seitenkonfiguration**

Erweiterung müssen vom Administrator installiert werden, bevor sie genutzt werden können. 
Dieses kann über das Administrationsmenü erstellt werden.

Jeder der Konnektoren benötigt zudem einen API-Schlüssel vom Service, der verbunden werden soll. 
Einige Erweiterung erlaube es, diese Informationen auf den Administrationsseiten einzustellen, wohingegen andere eine direkte Bearbeitung der Konfigurationsdatei ".htconfig.php" erfordern. 
Der Weg, um diese Schlüssel zu erhalten, variiert stark, jedoch brauchen fast alle einen bestehenden Account im gewünschten Service. 
Einmal installiert, können diese Schlüssel von allen Seitennutzern genutzt werden.

Im Folgenden findest du die Einstellungen für die verschiedenen Services (viele dieser Informationen kommen direkt aus den Quelldateien der Erweiterung):


**Twitter Erweiterung für Friendica**

* Author: Tobias Diekershoff
* tobias.diekershoff@gmx.net

* License:3-clause BSD license

Konfiguration:
Um dieses Erweiterung zu nutzen, benötigst du einen OAuth Consumer-Schlüsselpaar (Schlüssel und Geheimnis), das du auf der Seite [https://twitter.com/apps](https://twitter.com/apps) erhalten kannst

Registriere deine Friendica-Seite als "Client"-Anwendung mit "Read&Write"-Zugriff. Wir benötigen "Twitter als Login" nicht. Sobald du deine Anwendung installiert hast, erhältst du das Schlüsselpaar für deine Seite.

Trage dieses Schlüsselpaar in deine globale ".htconfig.php"-Datei ein.

```
$a->config['twitter']['consumerkey'] = 'your consumer_key here';
$a->config['twitter']['consumersecret'] = 'your consumer_secret here';
```

Anschließend kann der Nutzer deiner Seite die Twitter-Einstellungen selbst eintragen: "Einstellungen -> Connector Einstellungen".


**GNU Social Erweiterung für Friendica**

* Author: Tobias Diekershoff
* tobias.diekershoff@gmx.net

* License:3-clause BSD license

Konfiguration

Wenn das Addon aktiv ist, muss der Nutzer die folgenden Einstellungen vornehmen, um sich mit dem GNU Social-Account seiner Wahl zu verbinden.

* Die Basis-URL des GNU Social-API; für quitter.se ist es https://quitter.se/api/
* OAuth Consumer key & Geheimnis

Um das OAuth-Schlüsselpaar zu erhalten, muss der Nutzer

(a) seinen Friendica-Admin fragen, ob bereits ein Schlüsselpaar existiert oder 
(b) einen Friendica-Server als Anwendung auf dem GNU Social-Server anmelden.

Dies kann über Einstellungen --> Connections --> "Register an OAuth client application" -> "Register a new application" auf dem GNU Social-Server durchgeführt werden. 

Während der Registrierung des OAuth-Clients ist Folgendes zu beachten:

* Der Anwendungsname muss auf der GNU Social-Seite einzigartig sein, daher empfehlen wir einen Namen wie "friendica-nnnn", ersetze dabei "nnnn" mit einer frei gewählten Nummer oder deinem Webseitennamen.
* es gibt keine Callback-URL
* Registriere einen Desktop-Client
* stelle Lese- und Schreibrechte ein
* die Quell-URL sollte die URL deines Friendica-Servers sein

Sobald die benötigten Daten gespeichert sind, musst du deinen Friendica-Account mit GNU Social verbinden. 
Das kannst du über Einstellungen --> Connector-Einstellungen durchführen. 
Folge dem "Einloggen mit GNU Social"-Button, erlaube den Zugriff und kopiere den Sicherheitscode in die entsprechende Box. 
Friendica wird dann versuchen, die abschließende OAuth-Einstellungen über die API zu beziehen.

Wenn es geklappt hat, kannst du in den Einstellungen festlegen, ob deine öffentlichen Nachrichten automatisch in deinem GNU Social-Account erscheinen soll (achte hierbei auf das kleine Schloss-Symbol im Status-Editor)
