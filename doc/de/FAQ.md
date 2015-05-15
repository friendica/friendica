Häufig gestellte Fragen - FAQ
==============

* [Zur Startseite der Hilfe](help)

Nutzer

* **[Warum erhalte ich Warnungen über fehlende Zertifikate?](help/FAQ#ssl)**
* **[Wie kann ich Bilder, Dateien, Links, Video und Audio in Beiträge einfügen?](help/FAQ#upload)**
* **[Ist es möglich, bei mehreren Profilen verschiedene Avatare (Nutzerbilder) zu haben?](help/FAQ#avatars)**
* **[Was ist der Unterschied zwischen blockierten|ignorierten|archivierten|versteckten Kontakten?](help/FAQ#contacts)**
* **[Was passiert, wenn ein Account gelöscht ist? Ist dieser richtig gelöscht?](help/FAQ#removed)**
* **[Kann ich einem hashtag folgen?](help/FAQ#hashtag)**
* **[Wie kann ich einen RSS-Feed meiner Netzwerkseite (Stream) erstellen?](help/FAQ#rss)**
* **[Wo finde ich Hilfe?](help/FAQ#help)**

Admins

* **[Kann ich mehrere Domains mit den selben Dateien aufsetzen?](help/FAQ#multiple)**
* **[Wo kann ich den Quellcode von Friendica, Addons und Themes finden?](help/FAQ#sources)**

Nutzer
--------
****
<a name="ssl"></a>

**Warum erhalte ich Warnungen über fehlende Zertifikate?**

Manchmal erhältst du eine Browser-Warnung über fehlende Zertifikate. Diese Warnungen können drei Gründe haben:

1. der Server, mit dem du verbunden bist, nutzt kein SSL; 

2. der Server hat ein selbst-signiertes Zertifikat (nicht empfohlen)

3. das Zertifikat ist nicht mehr gültig.

*(SSL (Secure Socket Layer) ist eine Technologie, die Daten auf ihrem Weg zwischen zwei Computern verschlüsselt.)*

Wenn du noch kein SSL-Zertifikat hast, dann gibt es drei Wege, eines zu erhalten: kauf dir eines, hole dir ein kostenloses (z.B. bei StartSSL) oder kreiere dein eigenes (nicht empfohlen). 
[Weitere Informationen über die Einrichtung von SSL und warum es schlecht ist, selbst-signierte Zertifikate zu nutzen, findest du hier.](help/SSL)

Sei dir bewusst, dass Browser-Warnungen über Sicherheitslücken etwas sind, wodurch neue Nutzer schnell das Vertrauen in das gesamte Friendica-Projekt verlieren können.
Unabhängig von den negativen Aspekten von SSL handelt es sich hierbei um eine notwendige Lösung, solange keine etablierte Alternative existiert. 

Abgesehen davon kann es ohne SSL auch Probleme mit der Verbindung zu Diaspora geben, da einige Diaspora-Pods eine zertifizierte Verbindung benötigen. 

Wenn du Friendica nur für eine bestimmte Gruppe von Leuten auf einem einzelnen Server nutzt, bei dem keine Verbindung zum restlichen Netzwerk besteht, dann benötigst du kein SSL. 
Ebenso benötigst du SSL nicht, wenn du ausschließlich öffentliche Beiträge auf deiner Seite veröffentlichst bzw. empfängst. 

Wenn du zum jetzigen Zeitpunkt noch keinen Server aufgesetzt hast, ist es sinnvoll, die verschiedenen Anbieter in Bezug auf SSL zu vergleichen. 
Einige erlauben die Nutzung von freien Zertifikaten oder lassen dich ihre eigenen Zertifikate mitnutzen. 
Andere erlauben nur kostenpflichtige Zertifikate als eigenes Angebot bzw. von anderen Anbietern. 

<a name="upload"></a>

**Wie kann ich Bilder, Dateien, Links, Video und Audio in Beiträge einfügen?**

Bilder können direkt im [Beitragseditor](help/Text_editor) vom Computer hochgeladen werden. 
Eine Übersicht aller Bilder, die auf deinem Server liegen, findest du unter <i>deineSeite.de/photos/profilname</i>. 
Dort kannst du auch direkt Bilder hochladen und festlegen, ob deine Kontakte eine Nachricht über das neue Bild bekommen.

Alle Arten von Dateien können grundsätzlich als Anhang in Friendica hochgeladen werden. Dafür verwendest du das Büroklammersymbol im Editor. 
Sie sind dann direkt an den Beitrag geknüpft, können von den Betrachtern heruntergeladen werden, aber werden nicht als Vorschau angezeigt. 
Deshalb eignet sich diese Methode vor allem für Office-Dateien oder gepackte Dateien wie ZIPs, aber weniger für Multimediadateien. 
Wer hingegen Dateien über Dropbox, über eine auf dem eigenen Server installierte Owncloud oder über einen anderen [Filehoster](http://en.wikipedia.org/wiki/Comparison_of_file_hosting_services) einfügen will, verwendet den Link-Button.

Wenn du mit dem Link-Button (Ketten-Symbol) URLs zu anderen Seiten einfügst, versucht Friendica eine kurze Zusammenfassung als Vorschau abzurufen. 
Manchmal klappts das nicht ... dann verlinke den Beitrag einfach per [url=http://example.com]<i>freigewählter Name</i>[/url] im Editor.

Video- und Audiodateien können zwar in Beiträge eingebunden werden, allerdings geht das nicht über einen direkten Upload im Editor wie bei Fotos. 
Du hast zwei Möglichkeiten:

1. Du kannst bei dem Video- oder Audiobutton die URL von einem Hoster eingeben (Youtube, Vimeo, Soundcloud und alle anderen mit oembed/opengraph-Unterstützung). Bei Videos zeigt Friendica dann ein Vorschaubild in deinem Beitrag an, nach einem Klick öffnet sich ein eingebetter Player. Bei Soundcloud wird der Player direkt eingebunden.
2. Wenn du Zugang zu einem eigenen Server hast, kannst deine Multimediadatei per FTP dort hochladen und beim Video-/Audiobutton diese URL angeben. Dann wird das Video oder die Audiodatei direkt mit einem Player in deinem Beitrag angezeigt.
Friendica verwendet zur Einbettung HTML5. Das bedeutet, dass je nach Browser und Betriebssystem andere Formate unterstützt werden, darunter WebM, MP4, MP3 und Ogg. Eine Tabelle findest du bei Wikipedia ([Video](http://en.wikipedia.org/wiki/HTML5_video), [Audio](http://en.wikipedia.org/wiki/HTML5_audio)).

Zum Konvertieren von Videos in das lizenfreie Videoformat WebM gibt es unter Windows das kostenlose Programm [Xmedia-Recode](http://www.xmedia-recode.de/).

<a name="avatars"></a>

**Ist es möglich, bei mehreren Profilen verschiedene Avatare (Nutzerbilder) zu haben?**

Ja. 
Auf deiner ["Profile verwalten/editieren"-Seite](../profiles) wählst du zunächst das gewünschte Profil aus. 
Anschließend siehst du eine Seite mit allen Infos zu diesem Profil. 
Klicke nun oben auf den Link "Profilbild ändern" und lade im nächsten Fenster ein Bild von deinem PC hoch. 
Um deine privaten Daten zu schützen, wird in Beiträgen nur das Bild aus deinem öffentlichen Profil angezeigt.

<a name="contacts"></a>

**Was ist der Unterschied zwischen blockierten|ignorierten|archivierten|versteckten Kontakten?**

Wir verhindern direkte Kommunikation mit blockierten Kontakten. 
Sie gehören nicht zu den Empfängern beim Versand von Beiträgen und deren Beiträge werden auch nicht importiert. 
Trotzdem werden deren Unterhaltungen mit deinen Freunden trotzdem in deinem Stream sichtbar sein. 
Wenn du einen Kontakt komplett löschst, können sie dir eine neue Freundschaftsanfrage schicken. 
Blockierte Kontakte können das nicht machen. 
Sie können nicht mit dir direkt kommunizieren, nur über Freunde. 

Ignorierte Kontakte können weiterhin Beiträge von dir erhalten. 
Deren Beiträge werden allerdings nicht importiert. 
Wie bei blockierten Beiträgen siehst du auch hier weiterhin die Kommentare dieser Person zu anderen Beiträgen deiner Freunde. 

Ein Plugin namens "blockem" kann installiert werden, um alle Beiträge einer bestimmten Person in deinem Stream zu verstecken bzw. zu verkürzen. Dabei werden auch Kommentare dieser Person in Beiträgen deiner Freunde blockiert.

Ein archivierter Kontakt bedeutet, dass Kommunikation nicht möglich ist und auch nicht versucht wird (das ist z.B. sinnvoll, wenn eine Person zu einer neuen Seite gewechselt ist und das alte Profil gelöscht hat). 
Anders als beim Blockieren werden existierende Beiträge, die vor der Archivierung erstellt wurden, weiterhin angezeigt.

Ein versteckter Kontakt wird in keiner "Freundeliste" erscheinen (außer für dich). 
Trotzdem wird ein versteckter Kontakt trotzdem normal in Unterhaltungen angezeigt, was jedoch für andere Kontakte ein Hinweis sein kann, dass diese Person als versteckter Kontakt in deiner Liste ist. 

<a name="removed"></a>

**Was passiert, wenn ein Account gelöscht ist? Ist dieser richtig gelöscht?**

Wenn du deinen Account löschst, wird sofort der gesamte Inhalt auf deinem Server gelöscht und ein Löschbefehl an alle deine Kontakte verschickt. 
Dadurch wirst du ebenfalls aus dem globalen Verzeichnis gelöscht. 
Dieses Vorgehen setzt voraus, dass dein Profil für 24 Stunden weiterhin "teilweise" verfügbar sein wird, um eine Verbindung zu allen deinen Kontakten ermöglicht. 
Wir können also dein Profil blockieren und es so erscheinen lassen, als wären alle Daten sofort gelöscht, allerdings warten wir 24 Stunden (bzw. bis alle deine Kontakte informiert wurden), bevor wir die Daten auch physikalisch löschen.

<a name="hashtag"></a>

**Kann ich einem hashtag folgen?**

Nein. 
Die Möglichkeit, einem hashtag zu folgen, ist eine interessante Technik, führt aber zu einigen Schwierigkeiten. 

1.) Alle Beiträge, die diesen tag nutzen, müssten zu allen Seiten im Netzwerk kopiert werden. Das erhöht den Speicherbedarf und beeinträchtigt kleine Seiten. Die Nutzung von geteilten Hosting-Angeboten (Shared Hosting) wäre praktisch unmöglich. 

2.) Die Verbreitung von Spam wäre vereinfacht (tag-Spam ist z.B. bei identi.ca ein schwerwiegendes Problem)

3.) Der wichtigste Grund der gegen diese Technik spricht ist, dass sie eine natürliche Ausrichtung auf größere Seiten mit mehr getaggten Inhalten zur Folge hat. Dies kann z.B. aufkommen, wenn dein Netzwerk tags anstelle von anderen Kommunikationsmitteln wie Gruppen oder Foren nutzt. 

Stattdessen bieten wir andere Mechanismen, um globale Unterhaltungen zu erreichen, dabei aber eine angemesse Basis für kleine und große Seiten zu bieten. 
Hierzu gehören Foren, Gruppen und geteilte tags. 

<a name="rss"></a>

**Wie kann ich einen RSS-Feed meiner Netzwerkseite (Stream) erstellen?**

Wenn du die Beiträge deines Accounts mit RSS teilen willst, dann kannst du einen der folgenden Links nutzen:

RSS-Feed deiner Beiträge

	deineSeite.de/**dfrn_poll/profilname  

	Beispiel: Friendica Support 
	
	https://helpers.pyxis.uberspace.de/dfrn_poll/helpers

RSS-Feed aller Unterhaltungen auf deiner Seite

	deineSeite.de/dfrn_poll/profilname/converse
	
	Beispiel: Friendica Support 
	
	https://helpers.pyxis.uberspace.de/dfrn_poll/helpers/converse

<a name="help"></a>

**Wo finde ich Hilfe?**

Wenn du Probleme mit deiner Friendica-Seite hast, dann kannst du die Community in der [Friendica-Support-Gruppe](https://helpers.pyxis.uberspace.de/profile/helpers) oder im [deutschen Friendica-Support-Forum](http://toktan.org/profile/wiki) fragen oder dir das [deutsche Wiki](http://wiki.toktan.org/doku.php) anschauen. 
Wenn du deinen Account nicht nutzen kannst, kannst du entweder einen [Testaccount](http://friendica.com/node/31) bzw. einen Account auf einer öffentlichen Seite ([Liste](http://dir.friendica.com/siteinfo)) nutzen, oder du wählst die Librelist-mailing-Liste. 
Wenn du die Mailing-Liste nutzen willst, schicke eine Mail an friendica AT librelist PUNKT com.

Wenn du ein Theme-Entwickler bist, wirst du in diesem Forum Hilfe finden: [Friendica Theme Developers](https://friendica.eu/profile/ftdevs).
Desweiteren gibt es ein Forum, das sich UI/UX Problemen widmet [UX Watchdogs](https://fc.oscp.info/profile/ux-watchdogs).

Admin
--------
*****
<a name="multiple"></a>

**Kann ich mehrere Domains mit den selben Dateien aufsetzen?**

Ja, das ist möglich. 
Es ist allerdings nicht möglich, eine Datenbank durch zwei Domains zu nutzen. 
Solange du deine .htconfig.php allerdings so einrichtest, dass das System nicht versucht, eine Installation durchzuführen, kannst du die richtige Config-Datei in include/$hostname/.htconfig.php hinterlegen. 
Alle Cache-Aspekte und der Zugriffsschutz können pro Instanz konfiguriert werden.

<a name="sources"></a>

**Wo kann ich den Quellcode von Friendica, Addons und Themes finden?**

Du kannst den Friendica-Quellcode [hier](https://github.com/friendica/friendica) finden. 
Dort findest du immer die aktuellste stabile Version von Friendica. 

Addons findest du auf [dieser Seite](https://github.com/friendica/friendica-addons).

Wenn du neue Themen suchst, findest du sie auf [Friendica-Themes.com](http://friendica-themes.com/) 
