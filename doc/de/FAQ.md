Häufig gestellte Fragen - FAQ
==============

* [Zur Startseite der Hilfe](help)

* **[Wo finde ich Hilfe?](help/FAQ#help)**
* **[Warum erhalte ich Warnungen über fehlende Zertifikate?](help/FAQ#ssl)**
* **[Wie kann ich Bilder, Dateien, Links, Video und Audio in Beiträge einfügen?](help/FAQ#upload)**
* **[Ist es möglich, bei mehreren Profilen verschiedene Avatare (Nutzerbilder) zu haben?](help/FAQ#avatars)**
* **[Wie kann ich Friendica in einer bestimmten Sprache ansehen?](help/FAQ#language)**
* **[Was ist der Unterschied zwischen blockierten|ignorierten|archivierten|versteckten Kontakten?](help/FAQ#contacts)**
* **[Was passiert, wenn ein Account gelöscht ist? Ist dieser richtig gelöscht?](help/FAQ#removed)**
* **[Kann ich einem Hashtag folgen?](help/FAQ#hashtag)**
* **[Wie kann ich einen RSS-Feed meiner Netzwerkseite (Stream) erstellen?](help/FAQ#rss)**
* **[Gibt es Clients für Friendica?](help/FAQ#clients)**


<a name="help"></a>
### Wo finde ich Hilfe?

Wenn Du Probleme mit Deiner Friendica-Seite hast, dann kannst Du die Community in der [Friendica-Support-Gruppe](https://forum.friendi.ca/profile/helpers) fragen.
Wenn Du Deinen Account nicht nutzen kannst, kannst Du einen Account auf einer öffentlichen Seite ([Liste](https://dir.friendica.social/servers)) nutzen.

Wenn du dir keinen weiteren Friendica Account einrichten willst, kannst du auch gerne über einen der folgenden alternativen Kanäle Hilfe suchen:

  * Friendica Support Gruppe: [@helpers@forum.friendi.ca](https://forum.friendi.ca/~helpers)
  * Chats der Friendica Community (die IRC, Matrix und XMPP Räume sind mit einer Brücke verbunden) Logs dieser öffentlichen Chaträume können [hier aus dem IRC](https://gnusociarg.nsupdate.info/2021/%23friendica/) und [hier aus der Matrix](https://view.matrix.org/alias/%23friendi.ca:matrix.org/) gefunden werden.
    * XMPP: support(at)forum.friendi.ca
    * IRC: #friendica auf [libera.chat](https://web.libera.chat/?channels=#friendica)
    * Matrix: [#friendica-en:matrix.org](https://matrix.to/#/#friendica-en:matrix.org) or [#friendi.ca:matrix.org](https://matrix.to/#/#friendi.ca:matrix.org)
  * [Mailing List](http://mailman.friendi.ca/mailman/listinfo/support-friendi.ca)
  <!--- * [XMPP](xmpp:support@forum.friendi.ca?join)
	https://github.com/github/markup/issues/202
	https://github.com/gjtorikian/html-pipeline/pull/307
	https://github.com/github/opensource.guide/pull/807
  --->

<a name="ssl"></a>
### Warum erhalte ich Warnungen über fehlende Zertifikate?

Manchmal erhältst Du eine Browser-Warnung über fehlende Zertifikate.
Diese Warnungen können drei Gründe haben:

1. der Server, mit dem Du verbunden bist, nutzt kein SSL;

2. der Server hat ein selbst-signiertes Zertifikat (nicht empfohlen)

3. das Zertifikat ist nicht mehr gültig.

*(SSL (Secure Socket Layer) ist eine Technologie, die Daten auf ihrem Weg zwischen zwei Computern verschlüsselt.)*

Wenn Du noch kein SSL-Zertifikat hast, dann gibt es drei Wege, eines zu erhalten: kauf Dir eines, hole Dir ein kostenloses (z.B. bei StartSSL, WoSign, hoffentlich bald auch letsencrypt) oder kreiere Dein eigenes (nicht empfohlen).
[Weitere Informationen über die Einrichtung von SSL und warum es schlecht ist, selbst-signierte Zertifikate zu nutzen, findest Du hier.](help/SSL)

Sei Dir bewusst, dass Browser-Warnungen über Sicherheitslücken etwas sind, wodurch neue Nutzer schnell das Vertrauen in das gesamte Friendica-Projekt verlieren können.
Aus diesem Grund wird Friendica Red nur SSL-Zertifikate eines anerkannten Anbieters (CA, certificate authority) akzeptieren und nicht zu Seiten verbinden, die kein SSL nutzen.
Unabhängig von den negativen Aspekten von SSL handelt es sich hierbei um eine notwendige Lösung, solange keine etablierte Alternative existiert.

Abgesehen davon kann es ohne SSL auch Probleme mit der Verbindung zu Diaspora geben, da einige Diaspora-Pods eine zertifizierte Verbindung benötigen.

Wenn Du Friendica nur für eine bestimmte Gruppe von Leuten auf einem einzelnen Server nutzt, bei dem keine Verbindung zum restlichen Netzwerk besteht, dann benötigst Du kein SSL.
Ebenso benötigst Du SSL nicht, wenn Du ausschließlich öffentliche Beiträge auf Deiner Seite veröffentlichst bzw. empfängst.

Wenn Du zum jetzigen Zeitpunkt noch keinen Server aufgesetzt hast, ist es sinnvoll, die verschiedenen Anbieter in Bezug auf SSL zu vergleichen.
Einige erlauben die Nutzung von freien Zertifikaten oder lassen Dich ihre eigenen Zertifikate mitnutzen.
Andere erlauben nur kostenpflichtige Zertifikate als eigenes Angebot bzw. von anderen Anbietern.

<a name="upload"></a>
### Wie kann ich Bilder, Dateien, Links, Video und Audio in Beiträge einfügen?

Bilder können direkt im [Beitragseditor](help/Text_editor) vom Computer hochgeladen werden.
Eine Übersicht aller Bilder, die auf Deinem Server liegen, findest Du unter <i>deineSeite.de/profile/profilname/photos</i>.
Dort kannst Du auch direkt Bilder hochladen und festlegen, ob Deine Kontakte eine Nachricht über das neue Bild bekommen.

Alle Arten von Dateien können grundsätzlich als Anhang in Friendica hochgeladen werden.
Dafür verwendest Du das Büroklammersymbol im Editor.
Sie sind dann direkt an den Beitrag geknüpft, können von den Betrachtern heruntergeladen werden, aber werden nicht als Vorschau angezeigt.
Deshalb eignet sich diese Methode vor allem für Office-Dateien oder gepackte Dateien wie ZIPs, aber weniger für Multimediadateien.
Wer hingegen Dateien über Dropbox, über eine auf dem eigenen Server installierte Owncloud oder über einen anderen [Filehoster](http://en.wikipedia.org/wiki/Comparison_of_file_hosting_services) einfügen will, verwendet den Link-Button.

Wenn Du mit dem Link-Button (Ketten-Symbol) URLs zu anderen Seiten einfügst, versucht Friendica eine kurze Zusammenfassung als Vorschau abzurufen.
Manchmal klappts das nicht ... dann verlinke den Beitrag einfach per [url=http://example.com]<i>freigewählter Name</i>[/url] im Editor.

Video- und Audiodateien können zwar in Beiträge eingebunden werden, allerdings geht das nicht über einen direkten Upload im Editor wie bei Fotos.
Du hast zwei Möglichkeiten:

1. Du kannst bei dem Video- oder Audiobutton die URL von einem Hoster eingeben (Youtube, Vimeo, Soundcloud und alle anderen mit oembed/opengraph-Unterstützung). Bei Videos zeigt Friendica dann ein Vorschaubild in Deinem Beitrag an, nach einem Klick öffnet sich ein eingebetter Player. Bei Soundcloud wird der Player direkt eingebunden.
2. Wenn Du Zugang zu einem eigenen Server hast, kannst Deine Multimediadatei per FTP dort hochladen und beim Video-/Audiobutton diese URL angeben. Dann wird das Video oder die Audiodatei direkt mit einem Player in Deinem Beitrag angezeigt.
Friendica verwendet zur Einbettung HTML5. Das bedeutet, dass je nach Browser und Betriebssystem andere Formate unterstützt werden, darunter WebM, MP4, MP3 und Ogg. Eine Tabelle findest Du bei Wikipedia ([Video](http://en.wikipedia.org/wiki/HTML5_video), [Audio](http://en.wikipedia.org/wiki/HTML5_audio)).

Zum Konvertieren von Videos in das lizenfreie Videoformat WebM gibt es unter Windows das kostenlose Programm [Xmedia-Recode](http://www.xmedia-recode.de/).

<a name="avatars"></a>
### Ist es möglich, bei mehreren Profilen verschiedene Avatare (Nutzerbilder) zu haben?

Ja.
Auf Deiner ["Profile verwalten/editieren"-Seite](../profiles) wählst Du zunächst das gewünschte Profil aus.
Anschließend siehst Du eine Seite mit allen Infos zu diesem Profil.
Klicke nun oben auf den Link "Profilbild ändern" und lade im nächsten Fenster ein Bild von Deinem PC hoch.
Um Deine privaten Daten zu schützen, wird in Beiträgen nur das Bild aus Deinem öffentlichen Profil angezeigt.

<a name="language"></a>
### Wie kann ich Friendica in einer bestimmten Sprache ansehen?

Die Sprache des Friendica Interfaces kann durch den `lang` Parameter un der URL beeinflusst werden.
Das Argument des Parameters ist ein  [ISO 639-1](https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes)  Code.
Zwischen der URL und dem Parameter muss ein Fragezeichen als Trennzeichen verwendet werden.

Ein Beispiel:

     https://social.example.com/profile/example

auf Deutsch:

     https://social.example.com/profile/example?lang=de.

<a name="contacts"></a>
### Was ist der Unterschied zwischen blockierten|ignorierten|archivierten|versteckten Kontakten?

Wir verhindern direkte Kommunikation mit blockierten Kontakten.
Sie gehören nicht zu den Empfängern beim Versand von Beiträgen und deren Beiträge werden auch nicht importiert.
Trotzdem werden deren Unterhaltungen mit Deinen Freunden in Deinem Stream sichtbar sein.
Wenn Du einen Kontakt komplett löschst, können sie Dir eine neue Freundschaftsanfrage schicken.
Blockierte Kontakte können das nicht machen.
Sie können nicht mit Dir direkt kommunizieren, nur über Freunde.

Ignorierte Kontakte können weiterhin Beiträge und private Nachrichten von Dir erhalten.
Deren Beiträge und private Nachrichten werden allerdings nicht importiert.
Wie bei blockierten Beiträgen siehst Du auch hier weiterhin die Kommentare dieser Person zu anderen Beiträgen Deiner Freunde.

[Ein Erweiterung namens "blockem" kann installiert werden, um alle Beiträge einer bestimmten Person in Deinem Stream zu verstecken bzw. zu verkürzen.
Dabei werden auch Kommentare dieser Person in Beiträgen Deiner Freunde blockiert.]

Ein archivierter Kontakt bedeutet, dass Kommunikation nicht möglich ist und auch nicht versucht wird (das ist z.B. sinnvoll, wenn eine Person zu einem neuen Server gewechselt ist und das alte Profil gelöscht hat).
Anders als beim Blockieren werden existierende Beiträge, die vor der Archivierung erstellt wurden, weiterhin angezeigt.

Ein versteckter Kontakt wird in keiner "Freundeliste" erscheinen (außer für dich).
Trotzdem wird ein versteckter Kontakt normal in Unterhaltungen angezeigt - was für andere Kontakte ein Hinweis sein kann, dass diese Person als versteckter Kontakt in Deiner Liste ist.

<a name="removed"></a>
### Was passiert, wenn ein Account gelöscht ist? Ist dieser richtig gelöscht?

Wenn Du Deinen Account löschst, wird sofort der gesamte Inhalt auf Deinem Server gelöscht und ein Löschbefehl an alle Deine Kontakte verschickt.
Dadurch wirst Du ebenfalls aus dem globalen Verzeichnis gelöscht.
Dieses Vorgehen setzt voraus, dass Dein Profil für 24 Stunden weiterhin "teilweise" verfügbar sein wird, um eine Verbindung zu allen Deinen Kontakten ermöglicht.
Wir können also Dein Profil blockieren und es so erscheinen lassen, als wären alle Daten sofort gelöscht, allerdings warten wir 24 Stunden (bzw. bis alle Deine Kontakte informiert wurden), bevor wir die Daten auch physikalisch löschen.

<a name="hashtag"></a>
### Kann ich einem Hashtag folgen?

Ja.
Füge die Tags zu Deinen gespeicherten Suchen hinzu, sie werden automatisch auf der Netzwerk-Seite auftauchen.
Bitte beachte, dass Deine Antworten auf solche Posts aus technischen Gründen nicht unter dem "Persönlich"-Reiter auf der Netzwerk-Seite und der gesamte Thread nicht per API zu sehen sind.

<a name="rss"></a>
### Wie kann ich einen RSS-Feed meiner Netzwerkseite (Stream) erstellen?

Wenn Du die Beiträge Deines Accounts mit RSS teilen willst, dann kannst Du einen der folgenden Links nutzen:

#### RSS-Feed Deiner Beiträge

	deineSeite.de/feed/[profilname]/posts

Beispiel: Friendica Support

	https://forum.friendi.ca/feed/helpers/posts

#### RSS-Feed all deiner Beiträge und Antworten

    deineSeite.de/dfrn_poll/feed/[profilname]/comments

Beispiel: Friendica Support

    https://forum.friendi.ca/feeds/helpers/comments

#### RSS-Feed all deiner Aktivitäten

    deineSeite.de/feed/[profilname]/

<a name="clients">
### Gibt es Clients für Friendica?

Friendica unterstützt [Mastodon API](help/API-Mastodon) und [Twitter API | gnusocial](help/api).
Das bedeutet, du kannst einge der Mastodon und Twitter Clients für Friendica verwenden.
Die verfügbaren Features sind Abhängig vom Client, so dass diese teils unterschiedlich sein können.

#### Android

* [AndStatus](http://andstatus.org) ([F-Droid](https://f-droid.org/repository/browse/?fdid=org.andstatus.app), [Google Play](https://play.google.com/store/apps/details?id=org.andstatus.app))
* [B4X for Pleroma & Mastodon](https://github.com/AnywhereSoftware/B4X-Pleroma)
* DiCa ([Google Play](https://play.google.com/store/apps/details?id=cool.mixi.dica), letztes Update 2019)
* [Fedi](https://play.google.com/store/apps/details?id=com.fediverse.app)
* [Fedilab](https://fedilab.app) ([F-Droid](https://f-droid.org/app/fr.gouv.etalab.mastodon), [Google Play](https://play.google.com/store/apps/details?id=app.fedilab.android))
* [Friendiqa](https://git.friendi.ca/lubuwest/Friendiqa) (Gibt es im Google Playstore oder als [binary Repository](https://freunde.ma-nic.de/display/3e98eba8185a13c5bdbf3d1539646854) für F-Droid)
* [Husky](https://husky.fwgs.ru)
* [Roma](https://play.google.com/store/apps/details?id=tech.bigfig.roma)
* [Subway Tooter](https://github.com/tateisu/SubwayTooter)
* [Tooot](https://tooot.app/)
* [Tusky](https://tusky.app)
* [Twidere](https://dimension.im/) ([F-Droid](https://f-droid.org/repository/browse/?fdid=org.mariotaku.twidere), [Google Play](https://play.google.com/store/apps/details?id=com.twidere.twiderex), [GitHub](https://github.com/TwidereProject/Twidere-Android))
* [TwidereX](https://github.com/TwidereProject/TwidereX-Android)
* [twitlatte](https://github.com/moko256/twitlatte)
* [Yuito](https://github.com/accelforce/Yuito)

#### SailfishOS

* [Friendly](https://openrepos.net/content/fabrixxm/friendly#comment-form)

#### iOS

* [B4X for Pleroma & Mastodon](https://www.b4x.com/) ([AppStore](https://apps.apple.com/app/b4x-pleroma/id1538396871), [GitHub](https://github.com/AnywhereSoftware/B4X-Pleroma))
* [Fedi](https://fediapp.com) ([AppStore](https://apps.apple.com/de/app/fedi-for-pleroma-and-mastodon/id1478806281))
* [Mastodon](https://joinmastodon.org/apps)([AppStore](https://apps.apple.com/us/app/mastodon-for-iphone/id1571998974))
* [Roma](https://www.roma.rocks/)([AppStore](https://apps.apple.com/de/app/roma-for-pleroma-and-mastodon/id1445328699))
* [Stella*](https://www.stella-app.net/) ([AppStore](https://apps.apple.com/us/app/stella-for-mastodon-twitter/id921372048))
* [Tooot](https://tooot.app/) ([AppStore](https://apps.apple.com/app/id1549772269), [GitHub](https://github.com/tooot-app)), Datensammlung (nicht mit Identität verknüpft)
* [Tootle](https://mastodon.cloud/@tootleapp) ([AppStore](https://apps.apple.com/de/app/tootle-for-mastodon/id1236013466)), letztes update: 2020

#### Linux

* [Choqok](https://choqok.kde.org)
* [Whalebird](https://whalebird.social)
* [TheDesk](https://ja.mstdn.wiki/TheDesk)
* [Toot](https://toot.readthedocs.io/en/latest/)
* [Tootle](https://github.com/bleakgrey/tootle)

#### macOS

* [Mastonaut](https://mastonaut.app/) ([AppStore](https://apps.apple.com/us/app/mastonaut/id1450757574)), kostet ~8€
* [Whalebird](https://whalebird.social/en/desktop/contents) ([AppStore](https://apps.apple.com/de/app/whalebird/id1378283354), [GitHub](https://github.com/h3poteto/whalebird-desktop))

#### Web

* [Halcyon](https://www.halcyon.social/)
* [Pinafore](https://github.com/nolanlawson/pinafore)
