Friendica mit SSL nutzen
=====================================

* [Zur Startseite der Hilfe](help)

Disclaimer
---
**Dieses Dokument wurde im November 2015 aktualisiert.
SSL-Verschlüsselung ist sicherheitskritisch.
Das bedeutet, dass sich die empfohlenen Einstellungen schnell verändern.
Halte deine Installation auf dem aktuellen Stand und verlasse dich nicht darauf, dass dieses Dokument genau so schnell aktualisiert wird, wie sich Technologien verändern!**

Einleitung
---

Wenn du deine eigene Friendica-Seite betreibst, willst du vielleicht SSL (https) nutzen, um die Kommunikation zwischen den Servern und zwischen dir und deinem Server zu verschlüsseln.

Dafür gibt es grundsätzlich zwei Arten von SSL-Zertifikaten: Selbst-signierte Zertifikate und Zertifikate, die von einer Zertifizierungsstelle (CA) unterschrieben sind.
Technisch gesehen sorgen beide für valide Verschlüsselung.
Mit selbst-signierten Zertifikaten gibt es jedoch ein Problem:
Sie sind weder in Browsern noch auf anderen Servern installiert.
Deshalb führen sie zu Warnungen über "nicht vertrauenswürdige Zertifikate".
Das ist verwirrend und stört sehr.

Aus diesem Grund empfehlen wir, dass du dir ein von einer CA unterschriebenes Zertifikat besorgst.
Normalerweise kosten sie Geld - und sind nur für eine begrenzte Zeit gültig (z.B. ein Jahr oder zwei).

Es gibt aber Möglichkeiten, ein vertrauenswürdiges Zertifikat umsonst zu bekommen.

Wähle deinen Domainnamen
---

Dein SSL-Zertifikat wird für eine bestimmte Domain gültig sein oder sogar nur für eine Subdomain.
Entscheide dich endgültig für einen Domainnamen, *bevor* du ein Zertifikat bestellst.
Wenn du das Zertifikat hast, brauchst du ein neues, wenn du den Domainnamen ändern möchtest.

Gehosteter Webspace
---

Wenn deine Friendica-Instanz auf einem gehosteten Webspace läuft, solltest du dich bei deinem Hosting-Provider informieren.
Dort bekommst du Instruktionen, wie es dort läuft.
Du kannst bei deinem Provider immer ein kostenpflichtiges Zertifikat bestellen.
Sie installieren es für dich oder haben in der Weboberfläche eine einfache Upload-Möglichkeit für Zertifikat und Schlüssel.

Um Geld zu sparen, kann es sich lohnen, dort auch nachzufragen, ob sie ein anderes Zertifikat, das du selbst beschaffst, für dich installieren würden.
Wenn ja, dann lies weiter.

Ein kostenloses StartSSL-Zertifikat besorgen
---

StartSSL ist eine Zertifizierungsstelle, die kostenlose Zertifikate ausstellt.
Sie sind für ein Jahr gültig und genügen für unsere Zwecke.

### Schritt 1: Client-Zertifikat erstellen

Wenn du dich erstmalig bei StartSSL anmeldest, erhältst du ein Zertifikat, das in deinem Browser installiert wird.
Du brauchst es, um dich bei StartSSL einzuloggen, auch wenn du später wiederkommst.
Dieses Client-Zertifikat hat nichts mit dem SSL-Zertifikat für deinen Server zu tun.

### Schritt 2: Email-Adresse und Domain validieren

Um fortzufahren musst du beweisen, dass du die Email-Adresse, die du angegeben hast, und die Domain, für die du das Zertifikat möchtest, besitzt.
Gehe in den "Validation wizard" und fordere einen Bestätigungslink per Mail an.
Dasselbe machst du auch für die Validierung der Domain.

### Schritt 3: Das Zertifikat bestellen

Gehe in den "Certificate wizard".
Wähle das Target Webserver.
Bei der ersten Abfrage der Domain gibst du deine Hauptdomain an.
Im nächsten Schritt kannst du eine Subdomain hinzufügen.
Ein Beispiel: Wenn die Adresse der Friendica-Instanz friendica.beispiel.net lautet, gibst du zuerst beispiel.net an und danach friendica.

Wenn du weißt, wie man einen openssl-Schlüssel und einen Certificate Signing Request (CSR) erstellt, tu das.
Kopiere den CSR in den Browser, um ihn von StartSSL signiert zu bekommen.

Wenn du nicht weißt, wie man Schlüssel und CSR erzeugt, nimm das Angebot von StartSSL an, beides für dich zu generieren.
Das bedeutet: StartSSL hat den Schlüssel zu deiner SSL-Verschlüsselung, aber das ist immer noch besser als gar kein Zertifikat.
Lade dein Zertifikat von der Website herunter.
(Oder im zweiten Fall: Lade Zertifikat und Schlüssel herunter.)

Um dein Zertifikat auf einem Webserver zu installieren, brauchst du noch ein oder zwei andere Dateien: sub.class1.server.ca.pem und ca.pem, auch von StartSSL.
Gehe in die Rubrik "Tool box" und lade "Class 1 Intermediate Server CA" und "StartCom Root CA (PEM encoded)" herunter.

Wenn du dein Zertifikat zu deinem Hosting-Provider schicken möchtest, brauchen Sie mindestens Zertifikat und Schlüssel.
Schick zur Sicherheit alle vier Dateien hin.
**Du solltest sie auf einem verschlüsselten Weg hinschicken!**

Wenn du deinen eigenen Server betreibst, lade die Dateien hoch und besuche das Mozilla-Wiki (Link unten).

Let's encrypt
---

Wenn du einen eigenen Server betreibst und den Nameserver kontrollierst, könnte auch die Initiative "Let's encrypt" interessant für dich werden.
Momentan ist deren Angebot noch nicht fertig.
Auf der [Website](https://letsencrypt.org/) kannst du dich über den Stand informieren.

Webserver-Einstellungen
---

Im [Wiki von Mozilla](https://wiki.mozilla.org/Security/Server_Side_TLS) gibt es Anleitungen für die Konfiguration sicherer Webserver.
Dort findest du Empfehlungen, die auf [verschiedene Webserver](https://wiki.mozilla.org/Security/Server_Side_TLS#Recommended_Server_Configurations) zugeschnitten sind.

Teste deine SSL-Einstellungen
---

Wenn du fertig bist, kannst du auf der Testseite [SSL-Labs](https://www.ssllabs.com/ssltest/) prüfen lassen, ob Du alles richtig gemacht hast.



















