Konfigurationen
==============

* [Zur Startseite der Hilfe](help)

Hier findest du einige eingebaute Features, welche kein graphisches Interface haben oder nicht dokumentiert sind. 
Konfigurationseinstellungen sind in der Datei ".htconfig.php" gespeichert. 
Bearbeite diese Datei, indem du sie z.B. mit einem Texteditor öffnest. 
Verschiedene Systemeinstellungen sind bereits in dieser Datei dokumentiert und werden hier nicht weiter erklärt. 

**Tastaturbefehle**

Friendica erfasst die folgenden Tastaturbefehle:

* [Pause] - Pausiert die Update-Aktivität via "Ajax". Das ist ein Prozess, der Updates durchführt, ohne die Seite neu zu laden. Du kannst diesen Prozess pausieren, um deine Netzwerkauslastung zu reduzieren und/oder um es in der Javascript-Programmierung zum Debuggen zu nutzen. Ein Pausenzeichen erscheint unten links im Fenster. Klicke die [Pause]-Taste ein weiteres Mal, um die Pause zu beenden.

* [F8] - Zeigt eine Sprachauswahl an


**Geburtstagsbenachrichtigung**

Geburtstage erscheinen auf deiner Startseite für alle Freunde, die in den nächsten 6 Tagen Geburtstag haben. 
Um deinen Geburtstag für alle sichtbar zu machen, musst du deinen Geburtstag (zumindest Tag und Monat) in dein Standardprofil eintragen. 
Es ist nicht notwendig, das Jahr einzutragen.

**Konfigurationseinstellungen**


**Sprache**

Systemeinstellung

Bitte schau dir die Datei util/README an, um Informationen zur Erstellung einer Übersetzung zu erhalten.

Konfiguriere:
```
$a->config['system']['language'] = 'name';
```


**System-Thema (Design)**

Systemeinstellung

Wähle ein Thema als Standardsystemdesign (welches vom Nutzer überschrieben werden kann). Das Standarddesign ist "default".

Konfiguriere:
```
$a->config['system']['theme'] = 'theme-name';
```


**Verifiziere SSL-Zertifikate**

Sicherheitseinstellungen

Standardmäßig erlaubt Friendica SSL-Kommunikation von Seiten, die "selbstunterzeichnete" SSL-Zertifikate nutzen. 
Um eine weitreichende Kompatibilität mit anderen Netzwerken und Browsern zu gewährleisten, empfehlen wir, selbstunterzeichnete Zertifikate **nicht** zu nutzen. 
Aber wir halten dich nicht davon ab, solche zu nutzen. SSL verschlüsselt alle Daten zwischen den Webseiten (und für deinen Browser), was dir eine komplett verschlüsselte Kommunikation erlaubt. 
Auch schützt es deine Login-Daten vor Datendiebstahl. Selbstunterzeichnete Zertifikate können kostenlos erstellt werden. 
Diese Zertifikate können allerdings Opfer eines sogenannten ["man-in-the-middle"-Angriffs](http://de.wikipedia.org/wiki/Man-in-the-middle-Angriff) werden, und sind daher weniger bevorzugt. 
Wenn du es wünscht, kannst du eine strikte Zertifikatabfrage einstellen. 
Das führt dazu, dass du keinerlei Verbindung zu einer selbstunterzeichneten SSL-Seite erstellen kannst

Konfiguriere:
```
$a->config['system']['verifyssl'] = true;
```


**Erlaubte Freunde-Domains**

Kooperationen/Gemeinschaften/Bildung Erweiterung

Kommagetrennte Liste von Domains, welche eine Freundschaft mit dieser Seite eingehen dürfen. 
Wildcards werden akzeptiert (Wildcard-Unterstützung unter Windows benötigt PHP5.3) Standardmäßig sind alle gültigen Domains erlaubt.

Konfiguriere:
```
$a->config['system']['allowed_sites'] = "sitea.com, *siteb.com";
```


**Erlaubte Email-Domains**

Kooperationen/Gemeinschaften/Bildung Erweiterung

Kommagetrennte Liste von Domains, welche bei der Registrierung als Part der Email-Adresse erlaubt sind. 
Das grenzt Leute aus, die nicht Teil der Gruppe oder Organisation sind. 
Wildcards werden akzeptiert (Wildcard-Unterstützung unter Windows benötigt PHP5.3) Standardmäßig sind alle gültigen Email-Adressen erlaubt.

Konfiguriere: 
```
$a->config['system']['allowed_email'] = "sitea.com, *siteb.com";
```

**Öffentlichkeit blockieren**

Kooperationen/Gemeinschaften/Bildung Erweiterung

Setze diese Einstellung auf "true" und sperre den öffentlichen Zugriff auf alle Seiten, solange man nicht eingeloggt ist. 
Das blockiert die Ansicht von Profilen, Freunden, Fotos, vom Verzeichnis und den Suchseiten. 
Ein Nebeneffekt ist, dass Einträge dieser Seite nicht im globalen Verzeichnis erscheinen. 
Wir empfehlen, speziell diese Einstellung auszuschalten (die Einstellung ist an anderer Stelle auf dieser Seite erklärt). 
Beachte: das ist speziell für Seiten, die beabsichtigen, von anderen Friendica-Netzwerken abgeschottet zu sein. 
Unautorisierte Personen haben ebenfalls nicht die Möglichkeit, Freundschaftsanfragen von Seitennutzern zu beantworten. 
Die Standardeinstellung steht auf "false". 
Verfügbar in Version 2.2 und höher.

Konfiguriere:
```
$a->config['system']['block_public'] = true;
```


**Veröffentlichung erzwingen**

Kooperationen/Gemeinschaften/Bildung Erweiterung

Standardmäßig können Nutzer selbst auswählen, ob ihr Profil im Seitenverzeichnis erscheint. 
Diese Einstellung zwingt alle Nutzer dazu, im Verzeichnis zu erscheinen. 
Diese Einstellung kann vom Nutzer nicht deaktiviert werden. Die Standardeinstellung steht auf "false".

Konfiguriere:
```
$a->config['system']['publish_all'] = true;
```


**Globales Verzeichnis**

Kooperationen/Gemeinschaften/Bildung Erweiterung

Mit diesem Befehl wird die URL eingestellt, die zum Update des globalen Verzeichnisses genutzt wird. 
Dieser Befehl ist in der Standardkonfiguration enthalten. 
Der nichtdokumentierte Teil dieser Einstellung ist, dass das globale Verzeichnis gar nicht verfügbar ist, wenn diese Einstellung nicht gesetzt wird. 
Dies erlaubt eine private Kommunikation, die komplett vom globalen Verzeichnis isoliert ist.

Konfiguriere:
```
$a->config['system']['directory_submit_url'] = 'http://dir.friendica.com/submit';
```


**Proxy Konfigurationseinstellung**

Wenn deine Seite eine Proxy-Einstellung nutzt, musst du diese Einstellungen vornehmen, um mit anderen Seiten im Internet zu kommunizieren.

Konfiguriere:
```
$a->config['system']['proxy'] = "http://proxyserver.domain:port";
$a->config['system']['proxyuser'] = "username:password";
```


**Netzwerk-Timeout**

Legt fest, wie lange das Netzwerk warten soll, bevor ein Timeout eintritt. 
Der Wert wird in Sekunden angegeben. Standardmäßig ist 60 eingestellt; 0 steht für "unbegrenzt" (nicht empfohlen).

Konfiguriere:

```
$a->config['system']['curl_timeout'] = 60;
```


**Banner/Logo**

Hiermit legst du das Banner der Seite fest. Standardmäßig ist das Friendica-Logo und der Name festgelegt. 
Du kannst hierfür HTML/CSS nutzen, um den Inhalt zu gestalten und/oder die Position zu ändern, wenn es nicht bereits voreingestellt ist.

Konfiguriere:

```
$a->config['system']['banner'] = '<span id="logo-text">Meine tolle Webseite</span>';
```


**Maximale Bildgröße**

Maximale Bild-Dateigröße in Byte. Standardmäßig ist 0 gesetzt, was bedeutet, dass kein Limit gesetzt ist.

Konfiguriere:

```
$a->config['system']['maximagesize'] = 1000000;
```


**UTF-8 Reguläre Ausdrücke**

Während der Registrierung werden die Namen daraufhin geprüft, ob sie reguläre UTF-8-Ausdrücke nutzen. 
Hierfür wird PHP benötigt, um mit einer speziellen Einstellung kompiliert zu werden, die UTF-8-Ausdrücke benutzt. 
Wenn du absolut keine Möglichkeit hast, Accounts zu registrieren, setze den Wert von "no_utf" auf "true". 
Standardmäßig ist "false" eingestellt (das bedeutet, dass UTF-8-Ausdrücke unterstützt werden und funktionieren).
 
Konfiguriere:

```
$a->config['system']['no_utf'] = true;
```


**Prüfe vollständigen Namen**

Es kann vorkommen, dass viele Spammer versuchen, sich auf deiner Seite zu registrieren. 
In Testphasen haben wir festgestellt, dass diese automatischen Registrierungen das Feld "Vollständiger Name" oft nur mit Namen ausfüllen, die kein Leerzeichen beinhalten. 
Wenn du Leuten erlauben willst, sich nur mit einem Namen anzumelden, dann setze die Einstellung auf "true". 
Die Standardeinstellung ist auf "false" gesetzt.

Konfiguriere:

```
$a->config['system']['no_regfullname'] = true;
```


**OpenID**

Standardmäßig wird OpenID für die Registrierung und für Logins genutzt. 
Wenn du nicht willst, dass OpenID-Strukturen für dein System übernommen werden, dann setze "no_openid" auf "true".
Standardmäßig ist hier "false" gesetzt.

Konfiguriere:
```
$a->config['system']['no_openid'] = true;
```


**Multiple Registrierungen**

Um mehrfache Seiten zu erstellen, muss sich eine Person mehrfach registrieren können. 
Deine Seiteneinstellung kann Registrierungen komplett blockieren oder an Bedingungen knüpfen. 
Standardmäßig können eingeloggte Nutzer weitere Accounts für die Seitenerstellung registrieren. 
Hier ist weiterhin eine Bestätigung notwendig, wenn "REGISTER_APPROVE" ausgewählt ist. 
Wenn du die Erstellung weiterer Accounts blockieren willst, dann setze die Einstellung "block_extended_register" auf "true". 
Standardmäßig ist hier "false" gesetzt.
 
Konfiguriere:
```
$a->config['system']['block_extended_register'] = true;
```


**Entwicklereinstellungen**

Diese sind am nützlichsten, um Protokollprozesse zu debuggen oder andere Kommunikationsfehler einzugrenzen.

Konfiguriere:
```
$a->config['system']['debugging'] = true;
$a->config['system']['logfile'] = 'logfile.out';
$a->config['system']['loglevel'] = LOGGER_DEBUG;
```
Erstellt detaillierte Debugging-Logfiles, die in der Datei "logfile.out" gespeichert werden (Datei muss auf dem Server mit Schreibrechten versehen sein). "LOGGER_DEBUG" zeigt eine Menge an Systeminformationen, enthält aber keine detaillierten Daten. 
Du kannst ebenfalls "LOGGER_ALL" auswählen, allerdings empfehlen wir dieses nur, wenn ein spezifisches Problem eingegrenzt werden soll. 
Andere Log-Level sind möglich, werden aber derzeit noch nicht genutzt.


**PHP-Fehler-Logging**

Nutze die folgenden Einstellungen, um PHP-Fehler direkt in einer Datei zu erfassen.

Konfiguriere:
```
error_reporting(E_ERROR | E_WARNING | E_PARSE );
ini_set('error_log','php.out');
ini_set('log_errors','1');
ini_set('display_errors', '0');
```

Diese Befehle erfassen alle PHP-Fehler in der Datei "php.out" (Datei muss auf dem Server mit Schreibrechten versehen sein). 
Nicht deklarierte Variablen werden manchmal mit einem Verweis versehen, weshalb wir empfehlen, "E_NOTICE" und "E_ALL" nicht zu nutzen. 
Die Menge an Fehlern, die auf diesem Level gemeldet werden, ist komplett harmlos. 
Bitte informiere die Entwickler über alle Fehler, die du in deinen Log-Dateien mit den oben genannten Einstellungen erhältst. 
Sie weisen generell auf Fehler in, die bearbeitet werden müssen.
Wenn du eine leere (weiße) Seite erhältst, schau in die PHP-Log-Datei - dies deutet fast immer darauf hin, dass ein Fehler aufgetreten ist.
