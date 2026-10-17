# Die Logdatei lesen

Diese Seite erklärt, wie man einen Eintrag in der Friendica-Logdatei einordnet, den man gefunden hat, zum Beispiel einen Fehler, eine Warnung, oder etwas, wonach im Support nachgefragt wurde.

## Schritt 1: Loglevel erhöhen

Wenn du versuchst, ein Problem zu reproduzieren, stelle das Loglevel zuerst auf *Debug*.
Das geht im Admin-Panel unter *Protokolle* -> *Einstellungen*.
Auf derselben Seite legst du auch den Pfad der Logdatei fest.
Die vollständige Liste der Level findest du unter [Konfigurationen & Admin-Panel](help/admin/settings#protokolle).

Das Debug-Level erzeugt sehr viele Einträge, stelle es deshalb wieder zurück, sobald du gefunden hast, was du brauchst.

## Schritt 2: Die Art des Prozesses bestimmen

Jede Zeile beginnt mit einem Zeitstempel, gefolgt vom Namen des Prozesses, der sie geschrieben hat.
Es gibt vier Arten von Prozessen:

- `app`: eine Anfrage, die vom Webserver bearbeitet wurde, also jemand, der deine Instanz über Browser oder App besucht oder nutzt
- `daemon`: der Daemon-Prozess, der Worker-Prozesse startet, falls du die Worker so und nicht per Cronjob startest
- `worker`: ein Worker-Prozess, der eine einzelne Aufgabe im Hintergrund abarbeitet
- `jetstream`: der Daemon, der die Verbindung zum AT-Protocol-Firehose (Bluesky) offen hält

```
2026-01-05T03:12:09Z app [DEBUG]: Known frontend found - accept {"isCrawler":false,"agent":"Mozilla/5.0 (X11; Linux x86_64; rv:130.0) Gecko/20100101 Firefox/130.0","method":"GET","uri":"/network?order=commented&p=1","parts":[]} - {"file":"blockbot.php","line":73,"function":"blockbot_init_1","request-id":"3e4f5a6b7c8d9","stack":"blockbot_init_1 (73), Hook::callSingle (207), Hook::callAll (183), HookEventBridge::callHook (563), HookEventBridge::onNamedEvent (275), EventDispatcher::callListeners (206), EventDispatcher::dispatch (56), EventDispatcher::dispatch (35), App::runFrontend (478), App::processRequest (190)","uid":"8a9b0c","process_id":471003}

2026-01-05T03:12:06Z daemon [INFO]: Executed "proc_open" {"command":"'/usr/bin/php' 'bin/console.php' 'worker'"} - {"file":"System.php","line":192,"function":"run","request-id":"4f5a6b7c8d9e0","stack":"System::run (192), Worker::spawnWorker (1222), Daemon::Friendica\\Console\\{closure} (157), Daemon::start (125), Daemon::doExecute (137), Console::execute (86), Console::doExecute (172), Console::execute (86), App::processConsole (233)","uid":"c1d2e3","process_id":483918}

2026-01-05T03:12:07Z worker [DEBUG]: DELETE FROM `process` WHERE (`pid` = 483920 AND `hostname` = 'node1.example.com') {"worker_id":"a1b2c3d","worker_cmd":null} - {"file":"Database.php","line":1265,"function":"delete","request-id":"9f8e7d6c5b4a3","stack":"Database::delete (203), Process::delete (78), Worker::doExecute (90), Console::execute (86), Console::doExecute (172), Console::execute (86), App::processConsole (233)","uid":"1a2b3c","process_id":483920}

2026-01-05T03:12:08Z jetstream [NOTICE]: Pid wasn't found {"worker_id":"7f6e5d4","worker_cmd":null} - {"file":"Daemon.php","line":140,"function":"isRunning","request-id":"1c2d3e4f5a6b7","stack":"Daemon::isRunning (140), JetstreamDaemon::doExecute (135), Console::execute (86), Console::doExecute (172), Console::execute (86), App::processConsole (233)","uid":"4d5e6f","process_id":483925}
2026-01-05T03:12:08Z jetstream [NOTICE]: starting daemon {"pid":null,"pidfile":"/var/www/example.com/tmp/jetstream.pid","worker_id":"7f6e5d4","worker_cmd":null} - {"file":"Daemon.php","line":83,"function":"start","request-id":"2d3e4f5a6b7c8","stack":"Daemon::start (83), JetstreamDaemon::doExecute (143), Console::execute (86), Console::doExecute (172), Console::execute (86), App::processConsole (233)","uid":"4d5e6f","process_id":483925}
```

## Schritt 3: Den Zusammenhang finden

Eine einzelne Zeile reicht selten aus, um zu verstehen, was passiert ist, meistens willst du die anderen Zeilen sehen, die zur selben Anfrage oder zum selben Hintergrund-Job gehören.
Welches Feld diese Zeilen zusammenhält, hängt von der Art des Prozesses ab.

Für `app`- und `daemon`-Einträge nimm `request-id`.
Jede Zeile, die während der Bearbeitung einer HTTP-Anfrage oder einer Daemon-Aktion geschrieben wurde, hat dieselbe `request-id`.

Für `worker`- und `jetstream`-Einträge ist `request-id` nicht hilfreich, da ein Worker- oder Jetstream-Prozess über seine gesamte Laufzeit dieselbe `request-id` behält, während er nacheinander viele voneinander unabhängige Aufgaben abarbeitet.
Nimm stattdessen `worker_id`, dieser Wert ändert sich mit jeder Aufgabe und lässt dich die Zeilen herausfiltern, die zu genau einer davon gehören.

Erzeugt ein Prozess ungewöhnlich viel Prozessor- oder I/O-Last, findest du über `process_id` alles, was genau dieser Betriebssystem-Prozess getan hat, über alle Aufgaben hinweg, mit denen er sich in dieser Zeit beschäftigt hat.

## Schritt 4: Datei, Zeile und Callstack nachschlagen

Drei Felder verweisen direkt auf den Code, der den Eintrag geschrieben hat:

- `file`: die PHP-Datei, die die Meldung geloggt hat
- `line`: die Zeilennummer des Log-Aufrufs in dieser Datei
- `function`: die Funktion oder Methode, die geloggt hat

Das Feld `stack` geht weiter und listet die gesamte Aufrufkette, die dorthin geführt hat, der innerste Aufruf zuerst, jeweils mit eigener Zeilennummer:

```
"stack":"HttpClient::request (185), HttpClient::post (233), ATProtocol::post (227), ATProtocol::refreshUserToken (656), ATProtocol::getUserToken (641), ATProtocol::XRPCGet (131), Actor::syncContacts (58), Jetstream::syncContacts (197), Jetstream::listen (101), JetstreamDaemon::Friendica\\Console\\{closure} (144), Daemon::start (125), JetstreamDaemon::doExecute (143), Console::execute (86), Console::doExecute (172), Console::execute (86)"
```

Das ist oft der schnellste Weg zu verstehen, warum ein Stück Code überhaupt erreicht wurde, ohne es von Hand nachzuvollziehen.

## Die Logdatei mit grep filtern

Die Logdatei wird schnell zu groß, um sie von oben nach unten zu lesen, filtere sie deshalb nach dem Wert, den du in Schritt 3 gefunden hast.
Da die Felder als JSON geschrieben werden, nimm die Anführungszeichen mit in die Suche, sonst matchst du auch auf den bloßen Wert:

```console
$ grep '"request-id":"3e4f5a6b7c8d9"' friendica.log
$ grep '"worker_id":"7f6e5d4"' friendica.log
$ grep '"process_id":483920' friendica.log
```

Bei `process_id` stehen im Log keine Anführungszeichen um den Wert, da es sich um eine Zahl und nicht um eine Zeichenkette handelt.

Kennst du nur einen Teil eines Werts, oder willst du über mehrere rotierte und komprimierte Logdateien hinweg suchen, funktioniert `zgrep` auf `.gz`-Dateien genauso:

```console
$ zgrep '"worker_id":"7f6e5d4"' friendica.log*.gz
```

## Dateirechte

Die Logdatei muss von zwei unterschiedlichen Prozessen beschrieben werden können: vom Benutzerkonto des Webservers, das die `app`-Einträge schreibt, und vom Benutzerkonto, unter dem Worker und Daemons per Kommandozeile oder Cronjob laufen und das alles andere schreibt.
Gehören beide zu einer gemeinsamen Gruppe, kannst du die Datei gruppen-schreibbar machen und beide Benutzerkonten in diese Gruppe aufnehmen, dann muss keiner der beiden Prozesse als der andere laufen.

## Logrotation

Sobald das Logging aktiviert ist, kann die Datei schnell wachsen, besonders im *Debug*-Level.
Richte dafür [Logrotation](help/admin/tools#log+rotation) ein (EN).
Die `create`-Direktive von `logrotate` ist auch ein guter Ort, um Besitzer, Gruppe und Rechte der Datei nach jeder Rotation neu zu setzen, damit du sie nicht jedes Mal von Hand korrigieren musst.
