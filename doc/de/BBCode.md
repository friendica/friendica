Referenz der Friendica BBCode Tags
========================

* [Zur Startseite der Hilfe](help)

Inline Tags
-----


<pre>[b]fett[/b]</pre> : <strong>fett</strong>

<pre>[i]kursiv[/i]</pre> : <em>kursiv</em>

<pre>[u]unterstrichen[/u]</pre> : <u>unterstrichen</u>

<pre>[s]durchgestrichen[/s]</pre> : <strike>durchgestrichen</strike>

<pre>[color=red]rot[/color]</pre> : <span style="color:  red;">rot</span>

<pre>[url=http://www.friendica.com]Friendica[/url]</pre> : <a href="http://www.friendica.com" target="external-link">Friendica</a>

<pre>[img]http://friendica.com/sites/default/files/friendika-32.png[/img]</pre> : <img src="http://friendica.com/sites/default/files/friendika-32.png" alt="Immagine/foto">

<pre>[size=xx-small]kleiner Text[/size]</pre> : <span style="font-size: xx-small;">kleiner Text</span>

<pre>[size=xx-large]gro&szlig; Text[/size]</pre> : <span style="font-size: xx-large;">gro&szlig;er Text</span>

<pre>[size=20]exakte Textgr&ouml;&szlig;e[/size] (Textgr&ouml;&szlig;e kann jede Zahl sein, in Pixeln)</pre> :  <span style="font-size: 20px;">exakte Gr&ouml;&szlig;e</span>







Block Tags
-----

<pre>[code]Code[/code]</pre>

<code>Code</code>

<p style="clear:both;">&nbsp;</p>

<pre>[quote]Zitat[/quote]</pre>

<blockquote>Zitat</blockquote>

<p style="clear:both;">&nbsp;</p>

<pre>[quote=Autor]Der Autor? Ich? Nein, nein, nein...[/quote]</pre>

<strong class="author">Autor hat geschrieben:</strong><blockquote>Der Autor?  Ich? Nein, nein, nein...</blockquote>

<p style="clear:both;">&nbsp;</p>

<pre>[center]zentrierter Text[/center]</pre>

<div style="text-align:center;">zentrierter Text</div>

<p style="clear:both;">&nbsp;</p>

**Tabelle**
<pre>[table border=1]
 [tr] 
   [th]Tabellenzeile[/th]
 [/tr]
 [tr]
   [td]haben &Uuml;berschriften[/td]
 [/tr]
[/table]</pre>

<table border="1"><tbody><tr><th>Tabellenzeile</th></tr><tr><td>haben &Uuml;berschriften</td></tr></tbody></table>

<p style="clear:both;">&nbsp;</p>

**Listen**

<pre>[list]
 [*] Erstes Listenelement
 [*] Zweites Listenelement
[/list]</pre>
<ul class="listbullet" style="list-style-type: circle;">
<li> Erstes Listenelement<br>
</li>
<li> Zweites Listenelement</li>
</ul>

[list] ist Equivalent zu [ul] (unsortierte Liste). 

[ol] kann anstelle von [list] verwendet werden um eine sortierte Liste zu erzeugen:

<pre>[ol]
 [*] Erstes Listenelement
 [*] Zweites Listenelement
[/ol]</pre>
<ul class="listdecimal" style="list-style-type: decimal;"><li>Erstes Listenelement<br></li><li> Zweites Listenelement</li></ul>

F&uuml;r weitere Optionen von sortierten Listen kann man den Stiel der Numerierung der Liste definieren:
<pre>[list=1]</pre> : dezimal

<pre>[list=i]</pre> : r&ouml;misch, Kleinbuchstaben

<pre>[list=I]</pre> : r&ouml;misch, Gro&szlig;buchstaben

<pre>[list=a]</pre> : alphabetisch, Kleinbuchstaben

<pre>[list=A] </pre> : alphabethisch, Gro&szlig;buchstaben




Einbettung von Inhalten
------

Man kann viele Dinge, z.B. Video und Audio Dateine, in Nachrichten einbetten.

<pre>[video]url[/video]</pre>
<pre>[audio]url[/audio]</pre>

Wobei die *url* von youtube, vimeo, soundcloud oder einer anderen Seite stammen kann die die oembed oder opengraph Spezifikationen unterst&uuml;tzt.  Au&szlig;erdem kann *url* die genaue url zu einer ogg Datei sein, die dann per HTML5 eingebunden wird.

<pre>[url]*url*[/url]</pre>

Wenn *url* entweder oembed oder opengraph unterstützt wird das eingebettete
Objekt (z.B. ein Dokument von scribd) eingebunden.
Der Titel der Seite mit einem Link zur *url* wird ebenfalls angezeigt.



Spezielle Tags
-------

Wenn du &uuml;ber BBCode Tags in einer Nachricht schreiben m&ouml;chtest, kannst du [noparse], [nobb] oder [pre] verwenden um den BBCode Tags vor der Evaluierung zu sch&uuml;tzen:

<pre>[noparse][b]fett[/b][/noparse]</pre> : [b]fett[/b]


