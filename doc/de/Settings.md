# Settings

* [Zur Startseite der Hilfe](help)

Wenn du der Administrator einer Friendica Instanz bist, hast du Zugriff auf das so genannte **Admin Panel** in dem du die Friendica Instanz konfigurieren kannst,

Auf der Startseite des Admin Panels werden die Informationen zu der Instanz zusammengefasst.
Die erste Zahl gibt die Anzahl von Nachrichten an, die nicht zugestellt werden konnten.
Die Zustellung wird zu einem späteren Zeitpunkt noch einmal versucht.
Unter dem Punkt "Warteschlange Inspizieren" kannst du einen schnellen Blick auf die zweite Warteschlange werfen.
Die zweite Zahl steht für die Anzahl der Aufgaben, die die Worker noch vor sich haben. 
Die Worker arbeiten Hintergrundprozesse ab. 
Die Aufgaben der Worker sind priorisiert und werden anhand dieser Prioritäten abgearbeitet.

Desweiteren findest du eine Übersicht über die Accounts auf dem Friendica Knoten, die unter dem Punkt "Nutzer" moderiert werden können.
Sowie eine Liste der derzeit aktivierten Addons.
Diese Liste ist verlinkt, so dass du schnellen Zugriff auf die Informationsseiten der einzelnen Addons hast.
Abschließend findest du auf der Startseite des Admin Panels die installierte Version von Friendica.
Wenn du in Kontakt mit den Entwicklern trittst und Probleme oder Fehler zu schildern, gib diese Version bitte immer mit an.

Die Unterabschnitte des Admin Panels kannst du in der Seitenleiste auswählen.

## Seite

In diesem Bereich des Admin Panels findest du die Hauptkonfiguration deiner Friendica Instanz.
Er ist in mehrere Unterabschnitte aufgeteilt, wobei die Grundeinstellungen oben auf der Seite zu finden sind.

Da die meisten Konfigurationsoptionen einen Hilfstext im Admin Panel haben, kann und will dieser Artikel nicht alle Einstellungen abdecken.

### Grundeinstellungen

#### Banner/Logo

Hiermit legst du das Banner der Seite fest. Standardmäßig ist das Friendica-Logo und der Name festgelegt. 
Du kannst hierfür HTML/CSS nutzen, um den Inhalt zu gestalten und/oder die Position zu ändern, wenn es nicht bereits voreingestellt ist.

#### Systensprache

Diese Einstellung legt die Standardsprache der Instanz fest.
Sie wird verwendet, wenn es Friendica nicht gelingt die Spracheinstellungen des Besuchers zu erkennen oder diese nicht unterstützt wird.
Nutzer können diese Auswahl in den Einstellungen des Benutzerkontos überschreiben.

Die Friendica Gemeinschaft bietet einige Übersetzungen an, von denen einige mehr andere weniger komplett sind.
Mehr Informationen zum Übersetzungsprozess von Friendica findest du [auf dieser Seite](/help/translations) der Dokumentation.

#### Systemweites Theme

Hier kann das Theme bestimmt werden, welches standardmäßig zum Anzeigen der Seite verwendet werden soll.
Nutzer können in ihren Einstellungen andere Themes wählen.
Derzeit ist das "duepunto zero" Theme das vorausgewählte Theme.

Für mobile Geräte kannst du ein spezielles Theme wählen, wenn das Standardtheme ungeeignet für mobile Geräte sein sollte.
Das `vier` Theme z.B. unterstützt kleine Anzeigen und benötigt kein zusätzliches mobiles Theme.

### Registrierung

#### Namen auf Vollständigkeit überprüfen

Es kann vorkommen, dass viele Spammer versuchen, sich auf deiner Seite zu registrieren. 
In Testphasen haben wir festgestellt, dass diese automatischen Registrierungen das Feld "Vollständiger Name" oft nur mit Namen ausfüllen, die kein Leerzeichen beinhalten. 
Wenn du Leuten erlauben willst, sich nur mit einem Namen anzumelden, dann setze die Einstellung auf "true". 
Die Standardeinstellung ist auf "false" gesetzt.
 
#### OpenID Unterstützung

Standardmäßig wird OpenID für die Registrierung und für Logins genutzt. 
Wenn du nicht willst, dass OpenID-Strukturen für dein System übernommen werden, dann setze "no_openid" auf "true".
Standardmäßig ist hier "false" gesetzt.

#### Unterbinde Mehrfachregistrierung

Um mehrfache Seiten zu erstellen, muss sich eine Person mehrfach registrieren können. 
Deine Seiteneinstellung kann Registrierungen komplett blockieren oder an Bedingungen knüpfen. 
Standardmäßig können eingeloggte Nutzer weitere Accounts für die Seitenerstellung registrieren. 
Hier ist weiterhin eine Bestätigung notwendig, wenn "REGISTER_APPROVE" ausgewählt ist. 
Wenn du die Erstellung weiterer Accounts blockieren willst, dann setze die Einstellung "block_extended_register" auf "true". 
Standardmäßig ist hier "false" gesetzt.
 
### Datei hochladen

#### Maximale Bildgröße

Maximale Bild-Dateigröße in Byte. Standardmäßig ist 0 gesetzt, was bedeutet, dass kein Limit gesetzt ist.

### Regeln

#### URL des weltweiten Verzeichnisses

Mit diesem Befehl wird die URL eingestellt, die zum Update des globalen Verzeichnisses genutzt wird. 
Dieser Befehl ist in der Standardkonfiguration enthalten. 
Der nicht dokumentierte Teil dieser Einstellung ist, dass das globale Verzeichnis gar nicht verfügbar ist, wenn diese Einstellung nicht gesetzt wird. 
Dies erlaubt eine private Kommunikation, die komplett vom globalen Verzeichnis isoliert ist.

#### Erzwinge Veröffentlichung

Standardmäßig können Nutzer selbst auswählen, ob ihr Profil im Seitenverzeichnis erscheint. 
Diese Einstellung zwingt alle Nutzer dazu, im Verzeichnis zu erscheinen. 
Diese Einstellung kann vom Nutzer nicht deaktiviert werden. Die Standardeinstellung steht auf "false".

#### Öffentlichen Zugriff blockieren

Aktiviere diese Einstellung um den öffentlichen Zugriff auf alle Seiten zu sperren, solange man nicht eingeloggt ist. 
Das blockiert die Ansicht von Profilen, Freunden, Fotos, vom Verzeichnis und den Suchseiten. 
Ein Nebeneffekt ist, dass Einträge dieser Seite nicht im globalen Verzeichnis erscheinen. 
Wir empfehlen, speziell diese Einstellung auszuschalten (die Einstellung ist an anderer Stelle auf dieser Seite erklärt). 
Beachte: das ist speziell für Seiten, die beabsichtigen, von anderen Friendica-Netzwerken abgeschottet zu sein. 
Unautorisierte Personen haben ebenfalls nicht die Möglichkeit, Freundschaftsanfragen von Seitennutzern zu beantworten. 
Die Standardeinstellung ist deaktiviert. 
Verfügbar in Version 2.2 und höher.

#### Erlaubte Domains für Kontakte

Kommagetrennte Liste von Domains, welche eine Freundschaft mit dieser Seite eingehen dürfen. 
Wildcards werden akzeptiert (Wildcard-Unterstützung unter Windows benötigt PHP5.3) Standardmäßig sind alle gültigen Domains erlaubt.

Mit dieser Option kann man einfach geschlossene Netzwerke, z.B. im schulischen Bereich aufbauen, aus denen nicht mit dem Rest des Netzwerks kommuniziert werden soll.

#### Erlaubte Domains für E-Mails

Kommagetrennte Liste von Domains, welche bei der Registrierung als Part der Email-Adresse erlaubt sind. 
Das grenzt Leute aus, die nicht Teil der Gruppe oder Organisation sind. 
Wildcards werden akzeptiert (Wildcard-Unterstützung unter Windows benötigt PHP5.3) Standardmäßig sind alle gültigen Email-Adressen erlaubt.

#### Nutzern erlauben das remote_self Flag zu setzen

Webb du die Option `Nutzern erlauben das remote_self Flag zu setzen` aktivierst, können alle Nutzer Atom Feeds in den erweiterten Einstellungen des Kontakts als "Entferntes Konto" markieren.
Dadurch werden automatisch alle Beiträge dieser Feeds für diesen Nutzer gespiegelt und an die Kontakte bei Friendica verteilt.

Dieses Feature kann z.B. dafür genutzt werden Blogbeiträge zu spiegeln.
In der Grundeinstellung ist es nicht aktiviert, da es zusätzliche Last auf dem Server verursachen kann.
Außerdem könnte es durch Nutzer als Spam Verteiler missbraucht werden.

Als Administrator der Friendica Instanz kannst du diese Einstellungen ansonsten nur direkt in der Datenbank vornehmen.
Bevor du das tust solltest du sicherstellen, dass du ein Backup der Datenbank hast und genau weißt was die Änderungen an der Datenbank bewirken, die du vornehmen willst.

### Erweitert

#### Proxy Einstellungen

Wenn deine Seite eine Proxy-Einstellung nutzt, musst du diese Einstellungen vornehmen, um mit anderen Seiten im Internet zu kommunizieren.

#### Netzwerk Wartezeit

Legt fest, wie lange das Netzwerk warten soll, bevor ein Timeout eintritt. 
Der Wert wird in Sekunden angegeben. Standardmäßig ist 60 eingestellt; 0 steht für "unbegrenzt" (nicht empfohlen).

#### UTF-8 Reguläre Ausdrücke

Während der Registrierung werden die Namen daraufhin geprüft, ob sie reguläre UTF-8-Ausdrücke nutzen. 
Hierfür wird PHP benötigt, um mit einer speziellen Einstellung kompiliert zu werden, die UTF-8-Ausdrücke benutzt. 
Wenn du absolut keine Möglichkeit hast, Accounts zu registrieren, setze diesen Wert auf ja.

#### SSL Überprüfen

Standardmäßig erlaubt Friendica SSL-Kommunikation von Seiten, die "selbst unterzeichnete" SSL-Zertifikate nutzen. 
Um eine weitreichende Kompatibilität mit anderen Netzwerken und Browsern zu gewährleisten, empfehlen wir, selbst unterzeichnete Zertifikate **nicht** zu nutzen. 
Aber wir halten dich nicht davon ab, solche zu nutzen. SSL verschlüsselt alle Daten zwischen den Webseiten (und für deinen Browser), was dir eine komplett verschlüsselte Kommunikation erlaubt. 
Auch schützt es deine Login-Daten vor Datendiebstahl. Selbst unterzeichnete Zertifikate können kostenlos erstellt werden. 
Diese Zertifikate können allerdings Opfer eines sogenannten ["man-in-the-middle"-Angriffs](http://de.wikipedia.org/wiki/Man-in-the-middle-Angriff) werden, und sind daher weniger bevorzugt. 
Wenn du es wünscht, kannst du eine strikte Zertifikatabfrage einstellen. 
Das führt dazu, dass du keinerlei Verbindung zu einer selbst unterzeichneten SSL-Seite erstellen kannst

### Automatisch ein Kontaktverzeichnis erstellen

### Performance

### Worker

In diesem Abschnitt kann der Hintergrund-Prozess konfiguriert werden.
Bevor ein neuer *Worker* Prozess gestartet wird, überprüft das System, dass die vorhandenen Resourchen ausrechend sind,
Aus diesem Grund kann es sein, dass die maximale Zahl der Hintergrungprozesse nicht erreicht wird.

Sollte die PHP Funktion `proc_open` auf dem Server nicht verfügbar sein, kann die Verwendung durch Friendica hier unterbunden werden.

Die Aufgaben die im Hintergrund erledigt werden, haben Prioritäten zugeteilt.
Um garantieren zu können, das wichtige Prozesse schnellst möglich abgearbeitet werden können, selbst wenn das System gerade stark belastet ist, sollte die *fastlane* aktiviert sein.

Wenn es auf deinem Server nicht möglich ist, einen cron Job zu starten, kannst du den *frontend* Worker einschalten.
Nachdem dies geschehen ist, kannst du `example.com/worker` (tausche example.com mit dem echten Domainnamen aus) aufrufen werden.
Dadurch werden dann die Aufgaben aktiviert, die der cron Job sonst aktivieren würde.

### Umsiedeln

## Nutzer

In diesem Abschnitt des Admin Panels kannst du die Nutzer deiner Friendica Instanz moderieren.

Solltest du für **Registrierungsmethode** die Einstellung "Bedarf Zustimmung" gewählt haben, werden hier zu Beginn der Seite neue Registrationen aufgelistet.
Als Administrator kannst du hier die Registration akzeptieren oder ablehnen.

Unter dem Abschnitt mit den Registrationen werden die aktuell auf der Instanz registrierten Nutzer aufgelistet.
Die Liste kann nach Namen, E-Mail Adresse, Datum der Registration, der letzten Anmeldung oder dem letzten Beitrag und dem Account Typ sortiert werden.
An dieser Stelle kannst du existierende Accounts vom Zugriff auf die Instanz blockieren, sie wieder frei geben oder Accounts endgültig löschen.

Im letzten Bereich auf der Seite kannst du als Administrator neue Accounts anlegen.
Das Passwort für so eingerichtete Accounts werden per E-Mail an die Nutzer geschickt.

## Plugins

Dieser Bereich des Admin Panels dient der Auswahl und Konfiguration der Erweiterungen von Friendica.
Sie müssen in das `/addon` Verzeichnis kopiert werden.
Auf der Seite wird eine Liste der verfügbaren Erweiterungen angezeigt.
Neben den Namen der Erweiterungen wird ein Indikator angezeigt, der anzeigt ob das Addon gerade aktiviert ist oder nicht.

Wenn du die Erweiterungen aktualisiert die du auf deiner Friendica Instanz nutzt könnte es sein, dass sie neu geladen werden müssen, damit die Änderungen aktiviert werden.
Um diesen Prozess zu vereinfachen gibt es am Anfang der Seite einen Button um alle aktiven Plugins neu zu laden.

## Themen

Der Bereich zur Kontrolle der auf der Friendica Instanz verfügbaren Themen funktioniert analog zum Plugins Bereich.
Jedes Theme hat eine extra Seite auf der der aktuelle Status, ein Bildschirmfoto des Themes, zusätzliche Informationen und eventuelle Einstellungen des Themes zu finden sind.
Genau wie Erweiterungen können Themes in der Übersichtsliste oder der Theme-Seite aktiviert bzw. deaktiviert werden.
Um ein Standardtheme für die Instanz zu wählen, benutze bitte die *Seiten* Bereich des Admin Panels.

## Zusätzliche Features

Es gibt einige optionale Features in Friendica, die Nutzer benutzen können oder halt nicht.
Zum Beispiel den *dislike* Button oder den *Webeditor* beim Erstellen von neuen Beiträgen.
In diesem Bereich des Admin Panels kannst du die Grundeinstellungen für diese Features festlegen und gegebenenfalls die Entscheidung treffen, dass Nutzer deiner Instanz diese auch nicht mehr ändern können.

## DB Updates

Wenn sich die Datenbankstruktur Friendicas ändert werden die Änderungen automatisch angewandt.
Solltest du den Verdacht haben, das eine Aktualisierung fehlgeschlagen ist, kannst du in diesem Bereich des Admin Panels den Status der Aktualisierungen überprüfen.

## Warteschlange Inspizieren

Auf der Eingangsseite des Admin Panels werden zwei Zahlen fpr die Warteschlangen angegeben.
Die zweite Zahl steht für die Beiträge, die initial nicht zugestellt werden konnten und später nochmal zugestellt werden sollen.
Sollte diese Zahl durch die Decke brechen, solltest du nachsehen an welchen Kontakt die Zustellung der Beiträge nicht funktioniert.

Unter dem Menüpunkt "Warteschlange Inspizieren" findest du eine Liste dieser nicht zustellbaren Beiträge.
Diese Liste ist nach dem Empfänger sortiert.
Die Kommunikation zu dem Empfänger kann aus unterschiedlichen Gründen gestört sein.
Der andere Server könnte offline sein, oder gerade einfach nur eine hohe Systemlast aufweisen.

Aber keine Panik!
Friendica wird die Beiträge nicht für alle Zeiten in der Warteschlange behalten.
Nach einiger Zeit werden Knoten als inaktiv identifiziert und Nachrichten an Nutzer dieser Knoten aus der Warteschlange gelöscht.

## Federation Statistik

Deine Instanz ist ein Teil eines Netzwerks von Servern dezentraler sozialer Netzwerke, der sogenannten **Federation**.
In diesem Bereich des Admin Panels findest du ein paar Zahlen zu dem Teil der Federation, die deine Instanz kennt.

## Plugin Features

Einige der Erweiterungen von Friendica benötigen global gültige Einstellungen, die der Administrator vornehmen muss.
Diese Erweiterungen sind hier aufgelistet, damit du die Einstellungen schneller findest.

## Protokolle

Dieser Bereich des Admin Panels ist auf zwei Seiten verteilt.
Die eine Seite dient der Konfiguration, die andere dem Anzeigen der Logs.

Du solltest die Logdatei nicht in einem Verzeichnis anlegen, auf das man vom Internet aus zugreifen kann.
Wenn du das dennoch tun musste und die Standardeinstellungen des Apache Servers verwendest, dann solltest du darauf achten, dass die Logdateien mit der Endung `.log` oder `.out` enden.
Solltest du einen anderen Webserver verwenden, solltest du sicherstellen, dass der Zugrif zu Dateien mit diesen Endungen nicht möglich ist.

Es gibt fünf Level der Ausführlichkeit mit denen Friendica arbeitet: Normal, Trace, Debug, Data und All.
Normalerweise solltest du für den Betrieb deiner Friendica Instanz keine Logs benötigen.
Wenn du versuchst einem Problem auf den Grund zu gehen, solltest du das "DEBUG" Level wählen.
Mit dem "All" Level schreibt Friendica alles in die Logdatei.
Die Datenmenge der geloggten Daten kann relativ schnell anwachsen, deshalb empfehlen wir das Anlegen von Protokollen nur zu aktivieren wenn es unbedingt nötig ist.

**Die Größe der Logdateien kann schnell anwachsen**.
Du solltest deshalb einen Dienst zur [log rotation](https://en.wikipedia.org/wiki/Log_rotation) einrichten.

**Bekannte Probleme**: Der Dateiname `friendica.log` kann bei speziellen Server Konfigurationen zu Problemen führen (siehe [issue 2209](https://github.com/friendica/friendica/issues/2209)).

Normalerweise werden Fehler- und Warnmeldungen von PHP unterdrückt.
Wenn du sie aktivieren willst, musst du folgendes in der `.htconfig.php` Datei eintragen um die Meldungen in die Datei `php.out` zu speichern

	error_reporting(E_ERROR | E_WARNING | E_PARSE );
	ini_set('error_log','php.out');
	ini_set('log_errors','1');
	ini_set('display_errors', '0');

Die Datei `php.out` muss vom Webserver schreibbar sein und sollte ebenfalls außerhalb der Webverzeichnisse liegen.
Es kommt gelegentlich vor, dass nicht deklarierte Variablen referenziert werden, dehalb raten wir davon ab `E_NOTICE` oder `E_ALL` zu verwenden.
Die überwiegende Mehrzahl der auf diesen Stufen dokumentierten Fehler sind absolut harmlos.
Solltest du mit den oben empfohlenen  Einstellungen Fehler finden, teile sie bitte den Entwicklern mit.
Im Allgemeinen sind dies Fehler, die behoben werden sollten.

Solltest du eine leere (weiße) Seite vorfinden, während du Friendica nutzt, werfe bitte einen Blick in die PHP Logs.
Solche *White Screens* sind so gut wie immer ein Zeichen dafür, dass ein Fehler aufgetreten ist.

## Diagnose

In diesem Bereich des Admin Panels findest du zwei Werkzeuge mit der du untersuchen kannst, wie Friendica bestimmte Ressourcen einschätzt.
Diese Werkzeuge sind insbesondere bei der Analyse von Kommunikationsproblemen hilfreich.

"Adresse untersuchen" zeigt Informationen zu einer URL an, wie Friendica sie wahrnimmt.

Mit dem zweiten Werkzeug "Webfinger überprüfen" kannst du Informationen zu einem Ding anfordern, das über einen Webfinger ( jemand@example.com ) identifiziert wird.

# Die Ausnahmen der Regel

Für die oben genannte Regel gibt es vier Ausnahmen, deren Konfiguration nicht über das Admin Panel vorgenommen werden kann.
Dies sind die Datenbank Einstellungen, die Administrator Accounts, der PHP Pfad und die Konfiguration einer eventuellen Installation in ein Unterverzeichnis unterhalb der Hauptdomain.

## Datenbank Einstellungen

Mit den folgenden Einstellungen kannst du die Zugriffsdaten für den Datenbank Server festlegen.

    $db_host = 'your.db.host';
    $db_user = 'db_username';
    $db_pass = 'db_password';
    $db_data = 'database_name';

## Administratoren

Du kannst einen, oder mehrere Accounts, zu Administratoren machen.
Normalerweise trifft dies auf den ersten Account zu, der nach der Installation angelegt wird.
Die Liste der E-Mail Adressen kann aber einfach erweitert werden.
Mit keiner der angegebenen E-Mail Adressen können weitere Accounts registriert werden.

    $a->config['admin_email'] = 'you@example.com, buddy@example.com';

## PHP Pfad

Einige Prozesse von Friendica laufen im Hintergrund.
Für diese Prozesse muss der Pfad zu der PHP Version gesetzt sein, die verwendet werden soll.

    $a->config['php_path'] = '/pfad/zur/php-version';

## Unterverzeichnis Konfiguration

Man kann Friendica in ein Unterverzeichnis des Webservers installieren.
Wir raten allerdings dringen davon ab, da es die Interoperabilität mit anderen Netzwerken (z.B. Diaspora, GNU Social, Hubzilla) verhindert.
Mal angenommen, du hast ein Unterverzeichnis tests und willst Friendica in ein weiteres Unterverzeichnis installieren, dann lautet die Konfiguration hierfür:

    $a->path = 'tests/friendica';

## Weitere Ausnahmen

Es gibt noch einige experimentelle Einstellungen, die nur in der ``.htconfig.php`` Datei konfiguriert werden können.
Im [Konfigurationswerte, die nur in der .htconfig.php gesetzt werden können (EN)](help/htconfig) Artikel kannst du mehr darüber erfahren.
