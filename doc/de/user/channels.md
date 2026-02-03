# Kanäle (Channels)

Kanäle sind eine Möglichkeit neue Inhalte zu finden, oder Inhalte anzuzeigen, die du sonst möglicherweise verpasst hättest.
Es gibt mehrere vordefinierte Kanäle und zusätzlich kannst du deine eigenen, basierend auf ein paar Regeln, erstellen.
Kanäle zeigen nur Beiträge aus den letzten 24 Stunden an. (Dieser Wert kann vom Administrator geändert werden.)

In den Anzeige-Einstellungen, im Bereich "Timelines", kannst du definieren, welche Kanäle und andere Timelines du im "Kanäle"-Widget auf der "Network"-Seite sehen möchtest und welche Kanäle in der Menüleiste oben auf der Seite erscheinen sollen.

Ebenfalls in den Anzeige-Einstellungen, im Bereich "Kanäle", kannst du alle die Sprachen einstellen, die du in deinen Kanälen sehen möchtest. Hier kannst du mehr als eine Sprache auswählen.

Auf der Profilseite kannst du die Kanal-Frequenz für jeden Kontakt definieren. Die Optionen sind:

* Standardhäufigkeit: Beiträge dieses Kontakts werden im "Für Dich"-Kanal angezeigt, wenn du häufig mit diesem Kontakt interagiert hast oder wenn ein Beitrag ein gewisses Maß an Interaktion erreicht hat.
* Alle Beiträge dieses Kontakts anzeigen: Alle Beiträge dieses Kontakts werden auf dem Kanal "Für Dich" erscheinen
* Zeige nur einige Beiträge an: Wenn ein Kontakt viele Beiträge in einem kurzen Zeitraum erstellt, reduziert diese Einstellung die Anzahl der angezeigten Beiträge in jedem Kanal.
* Zeige keine Beiträge an: Beiträge von diesem Kontakt werden in keinem Kanal angezeigt.

## Voreingestellte Kanäle

* Für Dich: Beiträge von Kontakten mit denen du interagierst und die mit dir interagieren. Im Detail bestehend aus:
    * Beiträge von Leuten, mit denen du überdurchschnittlich viel interagierst.
    * Beiträge von Kontakten, denen du folgst und mit denen du überdurchschnittlich viel interagierst.
    * Beiträge von Kontakten, bei denen du "Benachrichtigung bei neuen Beiträgen" aktiviert hast oder wo du die Kanalfrequenz entsprechend eingestellt hast.
* Entdecken: Beiträge von Kontakten denen du nicht folgst, aber denen zu folgen für dich interessant sein könnte. Im Detail bestehend aus:
    * Beiträge von Leuten denen du nicht folgst, aber mit denen du überdurchschnittlich viel interagierst.
    * Beiträge von Leuten denen du nicht folgst, aber die mit dir überdurchschnittlich viel interagieren.
    * Beliebte Beiträge von Leuten denen du nicht folgst, aber mit denen du interagiert hast oder die mit dir interagiert haben.
* Angesagt: Beiträge mit überdurchschnittlich hoher Anzahl von Interaktionen.
* Sprache: Beiträge in deiner Sprache.
* Folgende: Beiträge von Leuten die dir folgen, aber denen du nicht folgst.
* Geteilt von teilenden: Beiträge von Kontakten denen die Leute folgen, denen du folgst.
* Ruhige teilende: Beiträge von Konten denen du folgst, aber die nicht sehr oft posten.
* Bilder: Beiträge mit Bildern.
* Audio: Beiträge mit Audio.
* Videos: Beiträge mit Videos.

## Vom Benutzer eingestellte Kanäle

In den Einstellungen, unter "Kanäle", kannst du deine eigenen Kanäle erstellen.

Jeder Kanal wird durch diese Werte definiert:

* Bezeichnung: Dieses Feld ist notwendig und wird für die Kanalbezeichnung verwendet.
* Beschreibung: Eine kurze Beschreibung des Inhalts. Dies kann helfen den Überblick zu behalten, wenn du viele Kanäle hast.
* Zugriffsschlüssel: Wenn du auf diesen Kanal über einen Zugriffsschlüssel zugreifen willst, kannst du ihn hier festlegen. Achte darauf, dass du nicht einen bereits verwendeten Schlüssel benutzt.
* Circle/Kanal: Dies definiert die Datenquelle für diesen Kanal. Voreingestellt ist die Globale Gemeinschaft. Es gibt ein paar vorgegebene Werte, wie die Konten denen du folgst, oder die Kontakte, die dir folgen. Außerdem können alle deine Circles ausgewählt werden.
* Tags einschließen: Durch Kommata getrennte Liste von Tags. Ein Beitrag wird verwendet, wenn er eines der aufgeführten Tags enthält.
* Tags ausschließen: Durch Kommata getrennte Liste von Tags. Wenn ein Beitrag eines dieser Tags enthält, wird er nicht Teil dieses Kanals sein.
* Volltextsuche: Dies kann genutzt werden um Inhalte, basierend auf dem Inhalt und ein paar zusätzlichen Schlüsselwörtern, ein- oder auszuschließen. Es nutzt die "boolean mode"-Operatoren von MariaDB: https://mariadb.com/kb/en/full-text-index-overview/#in-boolean-mode
* Bilder, Videos, Audio: Wenn ausgewählt, wirst du Inhalte mit dem gewählten Medientyp sehen. Diese Optionen können kombiniert werden. Wenn keines dieser Felder ausgewählt wurde, wirst du alle Inhalte, mit oder ohne angefügten Medien, sehen.

## Zusätzliche Schlüsselwörter für die Volltextsuche

Zusätzlich zu der Suche nach Inhalten, gibt es Schlüsselwörter, die in der Volltextsuche genutzt werden können.
Alternativen werden durch "|" dargestellt.

* from - Verwende "from:nickname" oder "from:nickname@domain.tld" um nach Beiträgen von einem bestimmten Autor zu suchen.
* to - Verwende "to:nickname" oder "to:nickname@domain.tld" um nach Beiträgen mit dem gegebenen Empfänger zu suchen.
* group - Verwende "group:nickname" oder "group:nickname@domain.tld" um nach Beiträgen aus der gegebenen Gruppe zu suchen.
* application | relay - Nutze "application:nickname" oder "application:nickname@domain.tld" um Beiträge zu finden, die von der gegebenen relay application geteilt wurden.
* server - Verwende "server:hostname" um Beiträge von einem bestimmten Server zu suchen. Im Falle eine Gruppen-Postings enthält der Suchtext beides, den Hostname des Gruppen-Servers und den Hostname des Autors.
* source - Der ActivityPub-Typ der Beitragsquelle. Nutze dies um beispielsweise Gruppenpostings oder Beiträge von Services (aka Bots) ein- oder auszuschließen.
    * source:person - Der Beitrag wurde von einem regulären Nutzerkonto erstellt.
    * source:organization - Der Beitrag wurde von einer Organisation erstellt.
    * source:group -  Dieser Beitrag wurde über eine Gruppe erstellt oder verteilt.
    * source:service | source:news - Dieser Beitrag stammt aus einem 'service' Account. Dieser Quellen(source)-Typ wird oft genutzt um Bot Accounts zu markieren.
    * source:application | source:relay - Dieser Beitrag wurde von einer Anwendung (application) erstellt. Dies wird im Fediverse höchstwahrscheinlich für die Beitragserstellung nicht genutzt.
* tag - Nutze "tag:tagname" um nach einem bestimmten tag (Schlagwort) zu suchen.
* media - Mit diesem Schlüsselwort kannst du nach angefügten Medien suchen.
    * media:image | media:photo | media:picture - Dieser Beitrag enthält ein Bild
    * media:video - Dieser Beitrag enthält ein Video
    * media:audio - Dieser Beitrag enthält Audio
    * media:card - Dieser Beitrag enthält eine Linkvorschau-'card'
    * media:post - Dieser Beitrag verweist auf einen anderen Beitrag, was bedeutet, es ist ein zitierter Beitrag
* network | net - Verwende dies um Netzwerke in deinen Kanal einzuschließen oder von ihm auszuschließen.
    * network:apub | network:activitypub - ActivityPub (verwendet von den Systemen im Fediverse)
    * network:dfrn | network:friendica - altes Friendica-Protokoll. Heutzutage nutzt Friendica meist ActivityPub.
    * network:dspr | network:diaspora - Das Diaspora-Protokoll wird hauptsächlich von Diaspora selbst genutzt. Ein paar andere Systeme unterstützen dieses Protokoll ebenfalls, wie Hubzilla, Socialhome or Ganggo.
    * network:feed - RSS/Atom feeds
    * network:mail - Mails die via IMAP importiert worden sind.
    * network:dscs | network:discourse - Beiträge, die über den Discourse connector empfangen werden.
    * network:tmbl | network:tumblr - Beiträge, die über den Tumblr connector empfangen werden.
    * network:bsky | network:bluesky - Beiträge, die über den Bluesky connector empfangen werden.
* platform - Benutze dies, um Plattformen in deinen Kanal einzuschließen, oder von ihm auszuschließen, d.h. "+platform:friendica". Im Falle eines Gruppen-Postings enthält der Suchtext beides, die Plattform des Gruppen-Servers und die Plattform des Autors.
* visibility - Du hast die Wahl zwischen verschiedenen Sichtbarkeiten. Du kannst nur die ungelisteten oder privaten Beiträge sehen, zu denen du Zugang hast.
    * visibility:public - (öffentlich)
    * visibility:unlisted - (ungelistet)
    * visibility:private - (privat)
* language | lang - Verwende "language:code" um nach Beiträgen in der gewünschten Sprache (im [ISO 639-1](https://en.wikipedia.org/wiki/ISO_639-1) format) zu suchen.

Denke daran, dass du diese Schlüsselwörter kombinieren kannst.
So kannst du zum Beispiel einen Kanal erstellen, mit allen Beiträgen, die über das Fediverse sprechen, aber nicht im Fediverse veröffentlich wurden, mit diesen Suchbegriffen: "fediverse -network:apub -network:dfrn".
