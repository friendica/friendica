Friendica mit SSL nutzen
=====================================

* [Zur Startseite der Hilfe](help)

Wenn du deine eigene Friendica-Seite betreibst, willst du vielleicht SSL (https) nutzen, um die Kommunikation zwischen dir und deinem Server zu verschlüsseln (die Kommunikation zwischen den Servern ist bereits verschlüsselt).

Wenn du das auf deiner eigenen Domain machen willst, musst du ein Zertifikat von einer anerkannten Organisation beschaffen (sogenannte selbst-signierte Zertifikate, die unter Computerfreaks beliebt sind, arbeiten nicht sehr gut mit Friendica, weil sie Warnungen im Browser hervorrufen können).

Wenn du dieses Dokument liest, bevor du Friendica installierst, kannst du eine sehr einfache Option in Betracht ziehen: suche dir ein geteiltes Hosting-Angebot (shared hosting) ohne eigene Domain. 
Dadurch wirst du eine Adresse in der Form deinName.deinAnbietername.de erhalten, was nicht so schön wie deinName.de ist. 
Aber es wird trotzdem deine ganz persönliche Seite sein und du wirst unter Umständen die Möglichkeit haben, das SSL-Zertifikat deines Anbieters mitzubenutzen. 
Das bedeutet, dass du SSL überhaupt nicht konfigurieren musst - es wird einfach sofort funktionieren, wenn die Besucher deiner Seite https statt http eingeben. 

Wenn dir diese Lösung nicht gefällt, lies weiter...

**Geteilte Hosting-Angebote/Shared hosts**

Wenn du ein geteiltes Hosting-Angebot mit einer eigenen Domain nutzt, dann wird dir dein Anbieter ggf. anbieten, dir das Zertifikat zu besorgen und zu installieren. 
Du musst es nur beantragen und bezahlen und alles wird eingerichtet. 
Wenn das die Lösung für dich ist, musst du das weitere Dokument nicht lesen. 
Gehe nur sicher, dass das Zertifikat auch für die Domain gilt, die du für Friendica nutzt: z.B. meinfriendica.de oder friendica.meinserver.de.

Das Vorangehende wird die häufigste Art sein, eine Friendica-Seite zu betreiben, so dass der Rest des Artikels für die meisten Leute nicht von Bedeutung ist.

**Beschaffe dir das Zertifikat selbst**

Alternativ kannst du dir auch selbst ein Zertifikat besorgen und hochladen, falls dein Anbieter das unterstützt.

Der nächste Abschnitt beschreibt den Ablauf, um ein Zertifikat von StartSSL zu erhalten. 
Das Gute an StartSSL ist, dass du kostenlos ein einfaches, aber perfekt ausreichendes Zertifikat erhältst. 
Das ist bei vielen anderen Anbietern nicht so, weshalb wir uns in diesem Dokument auf StartSSL konzentrieren werden. 
Wenn du ein Zertifikat eines anderen Anbieters nutzen willst, musst du die Vorgaben dieser Organisation befolgen. 
Wir können hier nicht jede Möglichkeit abdecken. 

Die Installation deines erhaltenen Zertifikats hängt von den Vorgaben deines Anbieters ab. 
Aber generell nutzen solche Anbieter ein einfaches Web-Tool, um die Einrichtung zu unterstützen.

Beachte: dein Zertifikat gilt gewöhnlich nur für eine Subdomain. 
Wenn du dein Zertifikat beantragst, sorge dafür, dass es für die Domain und die Subdomain gilt, die du für Friendica nutzt: z.B. meinfriendica.de oder friendica.meinserver.de.

**Erhalte ein kostenloses StartSSL-Zertifikat**

Die Webseite von StartSSL führt dich durch den Erstellungsprozess, aber manche Leute haben hier trotzdem Probleme. 
Wir empfehlen dir ausdrücklich, die Installationsanleitung Schritt für Schritt langsam und sorgfältig zu befolgen. 
Lese dir jedes Wort durch und schließe deinen Browser erst, wenn alles läuft. 
Es heißt, dass es drei Schritte gibt, die den Nutzer verwirren können:

Wenn du dich erstmals bei StartSSL anmeldest, erhältst du ein erstes Zertifikat, dass sich einfach in deinem Browser installiert. 
Dieses Zertifikat solltest du zur Sicherheit irgendwo speichern, so dass du es für einen neuen Browser neu installieren kannst, wenn du z.B. etwas erneuern musst. 
Dieses Authentifizierungszertifikat wird nur für das Login benötigt und hat nichts mit dem Zertifikat zu tun, dass du später für deinen Server benötigst. 
Als Anfänger mit StartSSL kannst du [hier starten](https://www.startssl.com/?lang=de) und die "Express Lane" nutzen, um dein Browser-Zertifikiat zu erhalten. 
Im nächsten Schritt kannst du die Einrichtung deines Zertifikats fortsetzen.

Wenn du zuerst nach einer Domain für dein Zertifikat gefragt wirst, musst du die Top-Level-Domain angeben, nicht die Subdomain, die Friendica nutzt. 
Im nächsten Schritt kannst du dann die Subdomain spezifizieren. 
Wenn du also friendica.deinName.de auf deinem Server hast, musst du zuerst deinName.de angeben. 

Höre nicht zu früh auf, wenn du am Ende der Einrichtung dein persönliches Server-Zertifikat erhalten hast. 
Abhängig von deiner Server-Software benötigst du ein oder zwei generische Dateien, die du mit deinem kostenlosen StartSSL-Zertifikat nutzen musst. 
Diese Dateien sind sub.class1.server.ca.pem und ca.pem. 
Wenn du diesen Schritt bereits übersprungen hast, kannst du die Dateien hier finden: [http://www.startssl.com/?app=21](http://www.startssl.com/?app=21). 
Aber am besten funktioniert es, wenn du StartSSL nicht beendest, bevor du den Vorgang komplett abgeschlossen hast und dein https-Zertifikat hochgeladen ist und funktioniert. 

**Virtuelle private und dedizierte Server (mit StartSSL free)**

Der Rest dieses Dokuments ist etwas komplizierter, aber es ist auch nur für Personen, die Friendica auf einem virtuellen oder dedizierten Server nutzen. 
Jeder andere kann an dieser Stelle mit dem Lesen aufhören.

Folge den weiteren Anleitungen [hier](http://www.startssl.com/?app=20), um den Webserver, den du benutzt (z.B. Apache), für dein Zertifikat einzurichten.

Um die nötigen Schritte zu verdeutlichen, setzen wir nun voraus, dass Apache aktiv ist. 
Im Wesentlichen kannst du einfach einen zweiten httpd.conf-Eintrag für Friendica erstellen. 

Um das zu machen, kopiere den existierenden Eintrag und ändere das Ende der ersten Zeile auf "lesen" :443> anstelle von :80> und trage dann die folgenden Zeilen ein, wie du es auch in der Anleitung von StartSSL finden kannst:

	SSLEngine on
	SSLProtocol all -SSLv2
	SSLCipherSuite ALL:!ADH:!EXPORT:!SSLv2:RC4+RSA:+HIGH:+MEDIUM

	SSLCertificateFile /usr/local/apache/conf/ssl.crt
	SSLCertificateKeyFile /usr/local/apache/conf/ssl.key
	SSLCertificateChainFile /usr/local/apache/conf/sub.class1.server.ca.pem
	SSLCACertificateFile /usr/local/apache/conf/ca.pem
	SetEnvIf User-Agent ".*MSIE.*" nokeepalive ssl-unclean-shutdown
	CustomLog /usr/local/apache/logs/ssl_request_log \ 
	"%t %h %{SSL_PROTOCOL}x %{SSL_CIPHER}x \"%r\" %b"

(Beachte, dass das Verzeichnis /usr/local/apache/conf/ möglicherweise nicht in deinem System existiert. 
In Debian ist der Pfad bspw. /etc/apache2/, in dem du ein SSL-Unterverzeichnis erstellen kannst, wenn dieses noch nicht vorhanden ist. 
Dann hast du /etc/apache2/ssl/… statt /usr/local/apache/conf/…)

Du solltest nun zwei Einträgen für deine Friendica-Seite haben - einen für einfaches http und eines für https.

Ein Hinweis für diejenigen, die SSL steuern wollen: setze keine Weiterleitung deines SSL in deine Apache-Einstellung. Friendicas Admin-Panel hat eine spezielle Einstellung für die SSL-Methode. 
Bitte nutze diese Einstellungen. 

**Vermische Zertifikate in Apache – StartSSL und andere (selbst-signierte)**

Viele Leute nutzen einen virtuellen privaten oder einen dedizierten Server, um mehr als Friendica darauf laufen zu lassen. 
Sie wollen möglicherweise SSL auch für andere Seiten nutzen, die auf dem Server liegen. 
Um das zu erreichen, wollen sie mehrere Zertifikate für eine IP nutzen, z.B. ein Zertifikat eines anerkannten Anbieters für Friendica und ein selbst-signiertes für eine persönliche Inhalte (möglw. ein Wildcard-Zertifikat für mehrere Subdomains).

Um das zum Laufen zu bringen, bietet Apache eine NameVirtualHost-Direktive. 
Du findest Informationen zur Nutzung in httpd.conf in den folgenden Ausschnitten. 
Beachte, dass Wildcards (*) in httpd.conf dazu führen, dass die NameVirtualHost-Methode nicht funktioniert; du kannst diese in dieser neuen Konfiguration nicht nutzen. 
Das bedeutet, dass *80> oder *443> nicht funktionieren. 
Und du musst unbedingt die IP definieren, selbst wenn du nur eine hast. 
Beachte außerdem, dass du bald zwei Zeilen zu Beginn der Datei hinzufügen musst, um NameVirtualHost für IPv6 vorzubereiten.

	NameVirtualHost 12.123.456.1:443
	NameVirtualHost 12.123.456.1:80

	<VirtualHost www.anywhere.net:80>
	DocumentRoot /var/www/anywhere
	Servername www.anywhere.net
	</VirtualHost>

	<VirtualHost www.anywhere.net:443>
	DocumentRoot /var/www/anywhere
	Servername www.anywhere.net 
	SSLEngine On
	<pointers to a an eligible cert>
	<more ssl stuff >
	<other stuff>
	</VirtualHost>

	<VirtualHost www.somewhere-else.net:80>
	DocumentRoot /var/www/somewhere-else
	Servername www.somewhere-else.net
	</VirtualHost>

	<VirtualHost www.somewhere-else:443>
	DocumentRoot /var/www/somewhere-else
	Servername www.somewhere-else.net
	SSLEngine On
	<pointers to another eligible cert>
	<more ssl stuff >
	<other stuff>
	</VirtualHost>

Natürlich kannst du auch andere Verzeichnisse auf deinem Server nutzen, um Apache zu konfigurieren. 
In diesem Fall müssen nur einige Zeilen in httpd.conf oder ports.conf angepasst werden - vor allem die NameVirtualHost-Zeilen. 
Aber wenn du sicher im Umgang mit solchen Alternativen bist, wirst du sicherlich die nötigen Anpassungen herausfinden.

Starte dein Apache abschließend neu. 

**StartSSL auf Nginx**

Führe zunächst ein Update auf den neuesten Friendica-Code durch. 
Folge dann der Anleitung oben, um dein kostenloses Zertifikat zu erhalten. 
Aber statt der Apache-Installationsanleitung zu folgen, mache das Folgende:

Lade dein Zertifikat hoch. 
Es ist nicht wichtig, wohin du es lädst, solange Nginx es finden kann. 
Einige Leute nutzen /home/verschiedeneNummernundBuchstaben, du kannst aber auch z.B. etwas wie /foo/bar nutzen.

Du kannst das Passwort entfernen, wenn du willst. 
Es ist zwar möglicherweise nicht die beste Wahl, aber wenn du es nicht machst, wirst du das Passwort immer wieder eingeben müssen, wenn du Ngingx neustartest. 
Um es zu entfernen, gebe Folgendes ein: 

	openssl rsa -in ssl.key-pass -out ssl.key

Nun hole dir das Hifs-Zertifikat:

	wget http://www.startssl.com/certs/sub.class1.server.ca.pem

Nun vereinige die Dateien:

	cat ssl.crt sub.class1.server.ca.pem > ssl.crt

In manchen Konfigurationen ist ein Bug enthalten, weshalb diese Schritte nicht ordentlich arbeiten. 
Du musst daher ggf. ssl.crt bearbeiten:

	nano /foo/bar/ssl.crt

Du wirst zwei Zertifikate in der gleichen Date sehen. In der Mitte findest du:

	-----END CERTIFICATE----------BEGIN CERTIFICATE-----

Das ist schlecht. Du brauchst die folgenden Einträge:

	-----END CERTIFICATE-----
	-----BEGIN CERTIFICATE-----


Du kannst den Zeilenumbruch manuell eingeben, falls dein System vom Bug betroffen ist. 
Beachte, dass nach -----BEGIN CERTIFICATE----- nur ein Zeilenumbruch ist. 
Es gibt keine leere Zeile zwischen beiden Einträgen.

Nun musst du Nginx über die Zertifikate informieren.

In /etc/nginx/sites-available/foo.com.conf benötigst du etwas wie:

	server {
	
	listen 80;
	
	listen 443 ssl;

	listen [::]:80;

	listen [::]:443 ipv6only=on ssl;

	ssl_certificate /foo/bar/ssl.crt;

	ssl_certificate_key /foo/bar/ssl.key;

	...

Nun starte Nginx neu:

	/etc/init.d/nginx restart

Und das war es schon. 

Für multiple Domains ist es mit Nginx einfacher als mit Apache. 
Du musst du oben genannten Schritte nur für jedes Zertifikat wiederholen und die spezifischen Informationen im eigenen {server...}-Bereich spezifizieren.
