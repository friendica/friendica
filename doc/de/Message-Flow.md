Friendica Nachrichtenfluss
==============

* [Zur Startseite der Hilfe](help)

Diese Seite soll einige Infos darüber dokumentieren, wie Nachrichten innerhalb von Friendica von einer Person zur anderen übertragen werden. 
Es gibt verschiedene Pfade, die verschiedene Protokolle und Nachrichtenformate nutzen. 

Diejenigen, die den Nachrichtenfluss genauer verstehen wollen, sollten sich mindestens mit dem DFRN-Protokoll (http://dfrn.org/dfrn.pdf) und den Elementen zur Nachrichtenverarbeitung des OStatus Stack informieren (salmon und Pubsubhubbub).

Der Großteil der Nachrichtenverarbeitung nutzt die Datei include/items.php, welche Funktionen für verschiedene Feed-bezogene Import-/Exportaktivitäten liefert.

Wenn eine Nachricht veröffentlicht wird, werden alle Übermittlungen an alle Netzwerke mit include/notifier.php durchgeführt, welche entscheidet, wie und an wen die Nachricht geliefert wird. 
Diese Datei bindet dabei die lokale Bearbeitung aller Übertragungen ein inkl. dfrn-notify.

mod/dfrn_notify.php handhabt die Rückmeldung (remote side) von dfrn-notify.

Lokale Feeds werden durch mod/dfrn_poll.php generiert - was ebenfalls die Rückmeldung (remote side) von dfrn-notify handhabt.

Salmon-Benachrichtigungen kommen via mod/salmon.php an.

PuSh-Feeds (pubsubhubbub) kommen via mod/pubsub.php an.

DFRN-poll Feed-Imports kommen via include/poller.php als geplanter Task an, das implementiert die lokale Bearbeitung (local side) des DFRN-Protokolls. 


Szenario #1. Bob schreibt eine öffentliche Statusnachricht

Dies ist eine öffentliche Nachricht ohne begrenzte Nutzerfreigabe, so dass keine private Übertragung notwendig ist. 
Es gibt zwei Wege, die genutzt werden können - als bbcode an DFRN-Clients oder als durch den Server konvertierten HTML-Code (mit PuSH; pubsubhubbub). 
Wenn ein PuSH-Hub einsatzfähig ist, nutzen DFRN-Poll-Clients vorrangig die Informationen, die durch den PuSH-Kanal kommen. 
Sie fallen zurück auf eine tägliche Abfrage, wenn der Hub Übertragungsschwierigkeiten hat (das kann vorkommen, wenn der standardmäßige Google-Referenzhub genutzt wird). 
Wenn kein spezifizierter Hub oder Hubs ausgewählt sind, werden DFRN-Clients in einer pro Kontakt konfigurierbaren Rate mit bis zu 5-Minuten-Intervallen abfragen. 
Feeds, die via DFRN-Poll abgerufen werden, sind bbcode und können auch private Unterhaltungen enthalten, die vom Poller auf ihre Zugriffsrechte hin geprüft werden.

Szenario #2. Jack antwortet auf Bobs öffentliche Nachricht. Jack ist im Friendica/DFRN-Netzwerk.

Jack nutzt dfrn-notify, um eine direkte Antwort an Bob zu schicken. 
Bob erstellt dann einen Feed der Unterhaltung und sendet diesen an jeden, der an der Unterhaltung beteiligt ist und dfrn-notify nutzt. 
Die PuSH-Hubs werden darüber informiert, dass neuer Inhalt verfügbar ist. Der/die Hub/s erhalten dann die neuesten Feeds und übertragen diese an alle Hub-Teilnehmer (die auch zu verschiedenen Netzwerken gehören können).

Szenario #3. Mary antwortet auf Bobs öffentliche Nachricht. Mary ist im Friendica/DFRN-Netzwerk.

Mary nutzt dfrn-notify, um eine direkte Antwort an Bob zu schicken. 
Bob erstellt dann einen Feed der Unterhaltung und sendet diesen an jeden, der an der Unterhaltung beteiligt ist (mit Ausnahme von Bob selbst; die Unterhaltung wird nun an Jack und Mary geschickt). 
Die Nachrichten werden mit dfrn-notify übertragen. 
PuSH-Hubs werden darüber informiert, dass neuer Inhalt verfügbar ist. 
Der/die Hub/s erhalten dann die neuesten Feeds und übertragen sie an alle Hub-Teilnehmer (die auch zu verschiedenen Netzwerken gehören können).

Szenario #4. William antwortet auf Bobs öffentliche Nachricht. William ist in einem OStatus-Netzwerk.

William nutzt salmon, um Bob über seine Antwort zu benachrichtigen. 
Der Inhalt ist HTML-Code, der in das Salmon Magic Envelope eingebettet ist. 
Bob erstellt dann einen Feed der Unterhaltung und sendet es an alle Friendica-Nutzer, die an der Unterhaltung beteiligt sind und dfrn-notify nutzen (mit Ausnahme von William selbst; die Unterhaltung wird an Jack und Mary weitergeleitet). 
PuSH-Hubs werden darüber informiert, dass neuer Inhalt verfügbar ist. Der/die Hub/s erhalten dann die neuesten Feeds und übertragen sie an alle Hub-Teilnehmer (die auch zu verschiedenen Netzwerken gehören können).

Szenario #5. Bob schreibt eine private Nachricht an Mary und Jack.

Die Nachricht wird sofort an Mary und Jack mit Hilfe von dfrn_notify geschickt. 
Öffentliche Hubs werden nicht benachrichtigt. 
Im Falle eines Timeouts wird eine erneute Verarbeitung angestoßen. 
Antworten folgen dem gleichen Nachrichtenfluss wie öffentliche Antworten, allerdings werden die Hubs nicht darüber informiert, wodurch die Nachrichten niemals in öffentliche Feeds gelangen. 
Die komplette Unterhaltung ist nur für Mary und Jack in ihren durch dfrn-poll personalisierten Feeds verfügbar (und für niemanden sonst).
