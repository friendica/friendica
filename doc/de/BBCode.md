Referenz der Friendica BBCode Tags
========================

* [Zur Startseite der Hilfe](help)

## Inline

<style>
table.bbcodes {
    margin: 1em 0;
    background-color: #f9f9f9;
    border: 1px solid #aaa;
    border-collapse: collapse;
    color: #000;
	width: 100%;
}

table.bbcodes > tr > th,
table.bbcodes > tr > td,
table.bbcodes > * > tr > th,
table.bbcodes > * > tr > td {
	border: 1px solid #aaa;
	padding: 0.2em 0.4em
}

table.bbcodes > tr > th,
table.bbcodes > * > tr > th {
	background-color: #f2f2f2;
	text-align: center;
	width: 50%
}
</style>

<table class="bbcodes">
<tr>
  <th>BBCode</th>
  <th>Ergebnis</th>
</tr>
<tr>
  <td>[b]fett[/b]</td>
  <td><strong>fett</strong></td>
</tr>
<tr>
  <td>[i]kursiv[/i]</td>
  <td><em>kursiv</em></td>
</tr>
<tr>
  <td>[u]unterstrichen[/u]</td>
  <td><u>unterstrichen</u></td>
</tr>
<tr>
  <td>[s]durchgestrichen[/s]</td>
  <td><strike>durchgestrichen</strike></td>
</tr>
<tr>
  <td>[o]&uuml;berstrichen[/o]</td>
  <td><span class="overline">&uuml;berstrichen</span></td>
</tr>
<tr>
  <td>[color=red]rot[/color]</td>
  <td><span style="color:  red;">rot</span></td>
</tr>
<tr>
  <td>[url=http://www.friendica.com]Friendica[/url]</td>
  <td><a href="http://www.friendica.com" target="external-link">Friendica</a></td>
</tr>
<tr>
  <td>[img]http://friendica.com/sites/default/files/friendika-32.png[/img]</td>
  <td><img src="http://friendica.com/sites/default/files/friendika-32.png" alt="Immagine/foto"></td>
</tr>
<tr>
  <td>[img=64x32]http://friendica.com/sites/default/files/friendika-32.png[/img]<br>
<br>Note: provided height is simply discarded.</td>
  <td><img src="http://friendica.com/sites/default/files/friendika-32.png" style="width: 64px;"></td>
</tr>
<tr>
  <td>[size=xx-small]kleiner Text[/size]</td>
  <td><span style="font-size: xx-small;">kleiner Text</span></td>
</tr>
<tr>
  <td>[size=xx-large]gro&szlig;er Text[/size]</td>
  <td><span style="font-size: xx-large;">gro&szlig;er Text</span></td>
</tr>
<tr>
  <td>[size=20]exakte Gr&ouml;&szlig;e[/size] (die Gr&ouml;&szlig;e kann beliebig  in Pixeln gew&auml;lt werden)</td>
  <td><span style="font-size: 20px;">exakte Gr&ouml;&szlig;e</span></td>
</tr>
<tr>
  <td>[font=serif]Serife Schriftart[/font]</td>
  <td><span style="font-family: serif;">Serife Schriftart</span></td>
</tr>
</table>

### Links

<table class="bbcodes">
<tr>
  <th>BBCode</th>
  <th>Ergebnis</th>
</tr>
<tr>
  <td>[url]http://friendica.com[/url]</td>
  <td><a href="http://friendica.com">http://friendica.com</a></td>
</tr>
<tr>
  <td>[url=http://friendica.com]Friendica[/url]</td>
  <td><a href="http://friendica.com">Friendica</a></td>
</tr>
<tr>
  <td>[bookmark]http://friendica.com[/bookmark]<br><br>
#^[url]http://friendica.com[/url]</td>
  <td><span class="oembed link"><h4>Friendica: <a href="http://friendica.com" rel="oembed"></a><a href="http://friendica.com" target="_blank">http://friendica.com</a></h4></span></td>
</tr>
<tr>
  <td>[bookmark=http://friendica.com]Lesezeichen[/bookmark]<br><br>
#^[url=http://friendica.com]Lesezeichen[/url]<br><br>
#[url=http://friendica.com]^[/url][url=http://friendica.com]Lesezeichen[/url]</td>
  <td><span class="oembed link"><h4>Friendica: <a href="http://friendica.com" rel="oembed"></a><a href="http://friendica.com" target="_blank">Lesezeichen</a></h4></span></td>
</tr>
<tr>
  <td>[url=/posts/f16d77b0630f0134740c0cc47a0ea02a]Diaspora Beitrag mit GUID[/url]</td>
  <td><a href="/display/f16d77b0630f0134740c0cc47a0ea02a" target="_blank">Diaspora Beitrag mit GUID</a></td>
</tr>
<tr>
  <td>#Friendica</td>
  <td>#<a href="/search?tag=Friendica">Friendica</a></td>
</tr>
<tr>
  <td>@Erw&auml;hnung</td>
  <td>@<a href="javascript:void(0)">Erw&auml;hnung</a></td>
</tr>
<tr>
  <td>acct:account@friendica.host.com (WebFinger)</td>
  <td><a href="/acctlink?addr=account@friendica.host.com" target="extlink">acct:account@friendica.host.com</a></td>
</tr>
<tr>
  <td>[mail]user@mail.example.com[/mail]</td>
  <td><a href="mailto:user@mail.example.com">user@mail.example.com</a></td>
</tr>
<tr>
  <td>[mail=user@mail.example.com]Eine E-Mail senden[/mail]</td>
  <td><a href="mailto:user@mail.example.com">Eine E-Mail senden</a></td>
</tr>
</table>

## Blocks

<table class="bbcodes">
<tr>
  <th>BBCode</th>
  <th>Ergebnis</th>
</tr>
<tr>
  <td>[p]Ein Absatz mit Text[/p]</td>
  <td><p>Ein Absatz mit Text</p></td>
</tr>
<tr>
  <td>Eingebetteter [code]Programmcode[/code] im Text</td>
  <td>Eingebetteter <key>Programmcode</key> im Text</td>
</tr>
<tr>
  <td>[code]Programmcode<br>&uuml;ber<br>mehrere<br>Zeilen[/code]</td>
  <td><code>Programmcode
&uuml;ber
mehrere
Zeilen</code></td>
</tr>
<tr>
  <td>[code=php]function text_highlight($s,$lang)[/code]</td>
  <td><code><div class="hl-main"><ol class="hl-main"><li><span class="hl-code">&nbsp;</span><span class="hl-reserved">function</span><span class="hl-code"> </span><span class="hl-identifier">text_highlight</span><span class="hl-brackets">(</span><span class="hl-var">$s</span><span class="hl-code">,</span><span class="hl-var">$lang</span><span class="hl-brackets">)</span></li></ol></div></code></td>
</tr>
<tr>
  <td>[quote]Zitat[/quote]</td>
  <td><blockquote>Zitat</blockquote></td>
</tr>
<tr>
  <td>[quote=Autor]Autor? Ich? Nein, niemals...[/quote]</td>
  <td><strong class="Autor">Autor hat geschrieben:</strong><blockquote>Autor? Ich? Nein, niemals...</blockquote></td>
</tr>
<tr>
  <td>[center]zentrierter Text[/center]</td>
  <td><div style="text-align:center;">zentrierter Text</div></td>
</tr>
<tr>
  <td>Du solltest nicht weiter lesen, wenn du das Ende des Films nicht vorher erfahren willst. [spoiler]Es gibt ein Happy End.[/spoiler]</td>
  <td>
    <div class="wall-item-container">
      Du solltest nicht weiter lesen, wenn du das Ende des Films nicht vorher erfahren willst. <br>
      <span id="spoiler-wrap-0716e642" class="spoiler-wrap fakelink" onclick="openClose('spoiler-0716e642');">Zum &ouml;ffnen/schlie&szlig;en klicken</span>
      <blockquote class="spoiler" id="spoiler-0716e642" style="display: none;">Es gibt ein Happy End.</blockquote>
      <div class="body-attach"><div class="clear"></div></div>
    </div>
  </td>
</tr>
<tr>
  <td>[spoiler=Autor]Spoiler Alarm[/spoiler]</td>
  <td>
    <div class="wall-item-container">
      <strong class="spoiler">Autor hat geschrieben</strong><br>
      <span id="spoiler-wrap-a893765a" class="spoiler-wrap fakelink" onclick="openClose('spoiler-a893765a');">Zum &ouml;ffnen/schlie&szlig;en klicken</span>
      <blockquote class="spoiler" id="spoiler-a893765a" style="display: none;">Spoiler Alarm</blockquote>
      <div class="body-attach"><div class="clear"></div></div>
    </div>
  </td>
</tr>
<tr>
  <td>[hr] (horizontale Linie)</td>
  <td><hr></td>
</tr>
</table>

### &Uuml;berschriften

<table class="bbcodes">
<tr>
  <th>BBCode</th>
  <th>Ergebnis</th>
</tr>
<tr>
  <td>[h1]Titel 1[/h1]</td>
  <td><h1>Titel 1</h1></td>
</tr>
<tr>
  <td>[h2]Titel 2[/h2]</td>
  <td><h2>Titel 2</h2></td>
</tr>
<tr>
  <td>[h3]Titel 3[/h3]</td>
  <td><h3>Titel 3</h3></td>
</tr>
<tr>
  <td>[h4]Titel 4[/h4]</td>
  <td><h4>Titel 4</h4></td>
</tr>
<tr>
  <td>[h5]Titel 5[/h5]</td>
  <td><h5>Titel 5</h5></td>
</tr>
<tr>
  <td>[h6]Titel 6[/h6]</td>
  <td><h6>Titel 6</h6></td>
</tr>
</table>

### Tabellen

<table class="bbcodes">
<tr>
  <th>BBCode</th>
  <th>Ergebnis</th>
</tr>
<tr>
  <td>[table]<br>
&nbsp;&nbsp;[tr]<br>
&nbsp;&nbsp;&nbsp;&nbsp;[th]Kopfzeile 1[/th]<br>
&nbsp;&nbsp;&nbsp;&nbsp;[th]Kopfzeile 2[/th]<br>
&nbsp;&nbsp;&nbsp;&nbsp;[th]Kopfzeile 2[/th]<br>
&nbsp;&nbsp;[/tr]<br>
&nbsp;&nbsp;[tr]<br>
&nbsp;&nbsp;&nbsp;&nbsp;[td]Zelle 1[/td]<br>
&nbsp;&nbsp;&nbsp;&nbsp;[td]Zelle 2[/td]<br>
&nbsp;&nbsp;&nbsp;&nbsp;[td]Zelle 3[/td]<br>
&nbsp;&nbsp;[/tr]<br>
&nbsp;&nbsp;[tr]<br>
&nbsp;&nbsp;&nbsp;&nbsp;[td]Zelle 4[/td]<br>
&nbsp;&nbsp;&nbsp;&nbsp;[td]Zelle 5[/td]<br>
&nbsp;&nbsp;&nbsp;&nbsp;[td]Zelle 6[/td]<br>
&nbsp;&nbsp;[/tr]<br>
[/table]</td>
  <td>
	<table>
      <tbody>
        <tr>
          <th>Kopfzeile 1</th>
          <th>Kopfzeile 2</th>
          <th>Kopfzeile 3</th>
        </tr>
        <tr>
          <td>Zelle 1</td>
          <td>Zelle 2</td>
          <td>Zelle 3</td>
        </tr>
        <tr>
          <td>Zelle 4</td>
          <td>Zelle 5</td>
          <td>Zelle 6</td>
        </tr>
      </tbody>
    </table>
  </td>
</tr>
<tr>
  <td>[table border=0]</td>
  <td>
	<table border="0">
      <tbody>
        <tr>
          <th>Kopfzeile 1</th>
          <th>Kopfzeile 2</th>
          <th>Kopfzeile 3</th>
        </tr>
        <tr>
          <td>Zelle 1</td>
          <td>Zelle 2</td>
          <td>Zelle 3</td>
        </tr>
        <tr>
          <td>Zelle 4</td>
          <td>Zelle 5</td>
          <td>Zelle 6</td>
        </tr>
      </tbody>
    </table>
  </td>
</tr>
<tr>
  <td>[table border=1]</td>
  <td>
	<table border="1">
      <tbody>
        <tr>
          <th>Kopfzeile 1</th>
          <th>Kopfzeile 2</th>
          <th>Kopfzeile 3</th>
        </tr>
        <tr>
          <td>Zelle 1</td>
          <td>Zelle 2</td>
          <td>Zelle 3</td>
        </tr>
        <tr>
          <td>Zelle 4</td>
          <td>Zelle 5</td>
          <td>Zelle 6</td>
        </tr>
      </tbody>
    </table>
  </td>
</tr>
</table>

### Listen

<table class="bbcodes">
<tr>
  <th>BBCode</th>
  <th>Ergebnis</th>
</tr>
<tr>
  <td>[ul]<br>
&nbsp;&nbsp;[li] Erstes Listenelement<br>
&nbsp;&nbsp;[li] Zweites Listenelement<br>
[/ul]<br>
[list]<br>
&nbsp;&nbsp;[*] Erstes Listenelement<br>
&nbsp;&nbsp;[*] Zweites Listenelement<br>
[/list]</td>
  <td>
	<ul class="listbullet" style="list-style-type: circle;">
		<li>Erstes Listenelement</li>
		<li>Zweites Listenelement</li>
	</ul>
  </td>
</tr>
<tr>
  <td>[ol]<br>
&nbsp;&nbsp;[*] Erstes Listenelement<br>
&nbsp;&nbsp;[*] Zweites Listenelement<br>
[/ol]<br>
[list=1]<br>
&nbsp;&nbsp;[*] Erstes Listenelement<br>
&nbsp;&nbsp;[*] Zweites Listenelement<br>
[/list]</td>
  <td>
    <ul class="listdecimal" style="list-style-type: decimal;">
      <li> Erstes Listenelement</li>
      <li> Zweites Listenelement</li>
    </ul>
  </td>
</tr>
<tr>
  <td>[list=]<br>
&nbsp;&nbsp;[*] Erstes Listenelement<br>
&nbsp;&nbsp;[*] Zweites Listenelement<br>
[/list]</td>
  <td>
    <ul class="listnone" style="list-style-type: none;">
      <li> Erstes Listenelement</li>
      <li> Zweites Listenelement</li>
    </ul>
  </td>
</tr>
<tr>
  <td>[list=i]<br>
&nbsp;&nbsp;[*] Erstes Listenelement<br>
&nbsp;&nbsp;[*] Zweites Listenelement<br>
[/list]</td>
  <td>
    <ul class="listlowerroman" style="list-style-type: lower-roman;">
      <li> Erstes Listenelement</li>
      <li> Zweites Listenelement</li>
    </ul>
  </td>
</tr>
<tr>
  <td>[list=I]<br>
&nbsp;&nbsp;[*] Erstes Listenelement<br>
&nbsp;&nbsp;[*] Zweites Listenelement<br>
[/list]</td>
  <td>
    <ul class="listupperroman" style="list-style-type: upper-roman;">
      <li> Erstes Listenelement</li>
      <li> Zweites Listenelement</li>
    </ul>
  </td>
</tr>
<tr>
  <td>[list=a]<br>
&nbsp;&nbsp;[*] Erstes Listenelement<br>
&nbsp;&nbsp;[*] Zweites Listenelement<br>
[/list]</td>
  <td>
    <ul class="listloweralpha" style="list-style-type: lower-alpha;">
      <li> Erstes Listenelement</li>
      <li> Zweites Listenelement</li>
    </ul>
  </td>
</tr>
<tr>
  <td>[list=A]<br>
&nbsp;&nbsp;[*] Erstes Listenelement<br>
&nbsp;&nbsp;[*] Zweites Listenelement<br>
[/list]</td>
  <td>
    <ul class="listupperalpha" style="list-style-type: upper-alpha;">
      <li> Erstes Listenelement</li>
      <li> Zweites Listenelement</li>
    </ul>
  </td>
</tr>
</table>

## Einbetten

Du kannst Videos, Musikdateien und weitere Dinge in Beitr&auml;gen einbinden.

<table class="bbcodes">
<tr>
  <th>BBCode</th>
  <th>Ergebnis</th>
</tr>
<tr>
  <td>[video]url[/video]</td>
  <td>Wobei die *url* eine URL von youtube, vimeo, soundcloud oder einer anderen Plattform sein kann, die die opengraph Spezifikationen unterst&uuml;tzt.</td>
</tr>
<tr>
  <td>[video]URL der Videodatei[/video]
[audio]URL der Musikdatei[/audio]</td>
  <td>Die komplette URL einer ogg/ogv/oga/ogm/webm/mp4/mp3 Datei angeben, diese wird dann mit einem HTML5-Player angezeigt.</td>
</tr>
<tr>
  <td>[youtube]Youtube URL[/youtube]</td>
  <td>Youtube Video mittels OEmbed anzeigen. Kann u.U, den Player nicht einbetten.</td>
</tr>
<tr>
  <td>[youtube]Youtube video ID[/youtube]</td>
  <td>Youtube-Player im iframe einbinden.</td>
</tr>
<tr>
  <td>[vimeo]Vimeo URL[/vimeo]</td>
  <td>Vimeo Video mittels OEmbed anzeigen. Kann u.U, den Player nicht einbetten.</td>
</tr>
<tr>
  <td>[vimeo]Vimeo video ID[/vimeo]</td>
  <td>Vimeo-Player im iframe einbinden.</td>
</tr>
<tr>
  <td>[embed]URL[/embed]</td>
  <td>OEmbed rich content einbetten.</td>
</tr>
<tr>
  <td>[iframe]URL[/iframe]</td>
  <td>General embed, iframe size is limited by the theme size for video players.</td>
</tr>
<tr>
  <td>[url]*url*[/url]</td>
  <td>Wenn *url* die OEmbed- oder Opengraph-Spezifikationen unterst&uuml;tzt, wird das Objekt eingebettet (z.B. Dokumente von scribd).
  Ansonsten wird der Titel der Seite mit der URL verlinkt.</td>
</tr>
</table>

## Karten

Das Einbetten von Karten ben&ouml;tigt das "openstreetmap" oder das "Google Maps" Addon.
Wenn keines der Addons aktiv ist, werden stattde&szlig;en die Kordinaten angezeigt-

<table class="bbcodes">
<tr>
  <th>BBCode</th>
  <th>Ergebnis</th>
</tr>
<tr>
  <td>[map]Adresse[/map]</td>
  <td>Bindet eine Karte ein, auf der die angegebene Adresse zentriert ist.</td>
</tr>
<tr>
  <td>[map=lat,long]</td>
  <td>Bindet eine Karte ein, die auf die angegebenen Koordinaten zentriert ist.</td>
</tr>
<tr>
  <td>[map]</td>
  <td>Bindet eine Karte ein, die auf die Position des Beitrags zentriert ist.</td>
</tr>
</table>

## Zusammenfassungen f&uuml;r lange Beitr&auml;ge

Wenn du deine Beitr&auml;ge auf anderen Netzwerken von Drittanbietern verbreiten m&ouml;chtest, z.B. Twitter, k&ouml;nntest du Probleme mit deren Zeichenbegrenzung haben.

Friendica verwendet einen semi-inelligenten Mechanismus um passende Zusammenfassungen zu erstellen.
Du kannst allerdings auch selbst die Zusammenfassungen erstellen, die auf den unterschiedlichen Netzwerken angezeigt werden.
Um dies zu tun, verwendest du den [abstract]-Tag.

<table class="bbcodes">
<tr>
  <th>BBCode</th>
  <th>Ergebnis</th>
</tr>
<tr>
  <td>[abstract]Unglaublich interessant! Muss man gesehen haben! Unbedingt dem Link folgen![/abstract]<br>
Ich m&ouml;chte euch eine unglaublich langweilige Geschichte erz&auml;hlen, die ihr sicherlich niemals h&ouml;ren wolltet.</td>
  <td>Auf Twitter w&uuml;rde folgender Text verlffentlicht werden <blockquote>Unglaublich interessant! Muss man gesehen haben! Unbedingt dem Link folgen!</blockquote>
Wohingegen auf Friendica folgendes stehen w&uuml;rde <blockquote>Ich m&ouml;chte euch eine unglaublich langweilige Geschichte erz&auml;hlen, die ihr sicherlich niemals h&ouml;ren wolltet.</blockquote></td>
</tr>
</table>

Wenn du magst, kannst du auch unterschiedliche Zusammenfassungen f&uuml;r die unterschiedlichen Netzwerke verwenden.

<table class="bbcodes">
<tr>
  <th>BBCode</th>
  <th>Ergebnis</th>
</tr>
<tr>
  <td>
[abstract]Hey Leute, hier sind meines neuesten Bilder![/abstract]<br>
[abstract=twit]Hallo liebe Twitter Follower. Wollt ihr meine neuesten Bilder sehen?[/abstract]<br>
[abstract=apdn]Moin liebe Follower auf ADN. Ich habe einige neue Bilder gemacht, die ich euch gerne zeigen will.[/abstract]<br>
Heute war ich im Wald unterwegs und habe einige wirklich sch&ouml;ne Bilder gemacht...</td>
  <td>F&uuml;r Twitter und App.net wird Friendica in diesem Fall die speziell definierten Zusammenfassungen Verwenden. F&uuml;r andere Netzwerke (wie z.B. bei der Verwendung des GNU Social Konnektors zum Ver&ouml;ffentlichen auf deinen GNU Social Account) w&uuml;rde die allgemeine Zusammenfassung verwenden.</td>
</tr>
</table>

Wenn du beispielsweise den "buffer"-Konnektor verwendest um Beitr&auml;ge nach Facebook und Google+ zu senden, dort aber nicht den gesamten Blogbeitrag posten willst sondern nur einen Anrei&szlig;er, kannst du dies mit dem [abstract]-Tag realisieren.

Bei Netzwerken wie Facebook oder Google+, die selbst kein Zeichenlimit haben wird das [abstract]-Element allerdings nicht grunds&auml;tzlich verwendet.
Daher m&uuml;ssen diese Netzwerke explizit genannt werden.

<table class="bbcodes">
<tr>
  <th>BBCode</th>
  <th>Ergebnis</th>
</tr>
<tr>
  <td>
[abstract]Dieser Tage hatte ich eine ungew&ouml;hnliche Begegnung...[/abstract]<br>
[abstract=goog]Hey liebe Google+ Follower. Habt ich schon meinen neuesten Blog-Beitrag gelesen?[/abstract]<br>
[abstract=face]Hallo liebe Facebook Freunde. Letztens ist mir etwas wirklich sch&ouml;nes pa&szlig;iert.[/abstract]<br>
Als ich die Bilder im Wald aufgenommen habe, hatte ich eine wirklich ungew&ouml;hnliche Begegnung...</td>
  <td>Auf Google und Facebook w&uuml;rde nun die entsprechende Zusammenfassung verbreitet. F&uuml;r andere Netzwerke w&uuml;rde die allgemeine Zusammenfassung verwendet werden.<br>
<br>Auf Friendica wird weiterhin keine Zusammenfassung angezeigt.</td>
</tr>
</table>

F&uuml;r Verbindungen zu Netzwerken, zu denen Friendica den HTML Code postet, wie Tumblr, Wordpress oder Pump.io wird das [abstract] Element nicht verwendet.
Bei nativen Verbindungen; das hei&szlig;t zu z.B. Friendica, Hubzilla, Diaspora oder GNU Social Kontakten; wird der ungek&uuml;rzte Beitrag &uuml;bertragen.
Die Instanz des Kontakts k&uuml;mmert sich um die Darstellung.

## Special

<table class="bbcodes">
<tr>
  <th>BBCode</th>
  <th>Ergebnis</th>
</tr>
<tr>
  <td>Wenn du verhindern m&ouml;chtest, da&szlig; der BBCode in einer Nachricht interpretiert wird, kannst du die [noparse], [nobb] oder [pre] Tag verwenden:<br>
    <ul>
      <li>[noparse][b]fett[/b][/noparse]</li>
      <li>[nobb][b]fett[/b][/nobb]</li>
      <li>[pre][b]fett[/b][/pre]</li>
    </ul>
  </td>
  <td>[b]fett[/b]</td>
</tr>
<tr>
  <td>[nosmile] kann verwendet werden um f&uuml;r einen Beitrag das umsetzen von Smilies zu verhindern.<br>
    <br>
    [nosmile] ;-) :-O
  </td>
  <td>;-) :-O</td>
</tr>
<tr>
  <td>Benutzerdefinierte Inline-Styles<br>
<br>
[style=text-shadow: 0 0 4px #CC0000;]Du kannst alle CSS-Eigenschaften eines Blocks &auml;ndern-[/style]</td>
  <td><span style="text-shadow: 0 0 4px #cc0000;;">Du kannst alle CSS-Eigenschaften eines Blocks &auml;ndern-</span></td>
</tr>
<tr>
  <td>Benutzerdefinierte CSS Klassen<br>
<br>
[class=custom]Wenn die vergebene Klasse in den CSS Anweisungen existiert, wird sie angewandt.[/class]</td>
  <td><pre>&lt;span class="custom"&gt;Wenn die<br>
vergebene Klasse in den CSS Anweisungen<br>
existiert,wird sie angewandt.&lt;/span&gt;</pre></td>
</tr>
</table>

