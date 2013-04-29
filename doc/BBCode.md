Friendica BBCode tags reference
========================

* [Home](help)

Inline
-----


<pre>[b]bold[/b]</pre> : <strong>bold</strong>

<pre>[i]italic[/i]</pre> : <em>italic</em>

<pre>[u]underlined[/u]</pre> : <u>underlined</u>

<pre>[s]strike[/s]</pre> : <strike>strike</strike>

<pre>[color=red]red[/color]</pre> : <span style="color:  red;">red</span>

<pre>[url=http://www.friendica.com]Friendica[/url]</pre> : <a href="http://www.friendica.com" target="external-link">Friendica</a>

<pre>[img]http://friendica.com/sites/default/files/friendika-32.png[/img]</pre> : <img src="http://friendica.com/sites/default/files/friendika-32.png" alt="Immagine/foto">

<pre>[size=xx-small]small text[/size]</pre> : <span style="font-size: xx-small;">small text</span>

<pre>[size=xx-large]big text[/size]</pre> : <span style="font-size: xx-large;">big text</span>

<pre>[size=20]exact size[/size] (size can be any number, in pixel)</pre> :  <span style="font-size: 20px;">exact size</span>







Block
-----

<pre>[code]code[/code]</pre>

<code>code</code>

<p style="clear:both;">&nbsp;</p>

<pre>[quote]quote[/quote]</pre>

<blockquote>quote</blockquote>

<p style="clear:both;">&nbsp;</p>

<pre>[quote=Author]Author? Me? No, no, no...[/quote]</pre>

<strong class="author">Author wrote:</strong><blockquote>Author? Me? No, no, no...</blockquote>

<p style="clear:both;">&nbsp;</p>

<pre>[center]centered text[/center]</pre>

<div style="text-align:center;">centered text</div>

<p style="clear:both;">&nbsp;</p>

**Table**
<pre>[table border=1]
 [tr] 
   [th]Tables now[/th]
 [/tr]
 [tr]
   [td]Have headers[/td]
 [/tr]
[/table]</pre>

<table border="1"><tbody><tr><th>Tables now</th></tr><tr><td>Have headers</td></tr></tbody></table>

<p style="clear:both;">&nbsp;</p>

**List**

<pre>[list]
 [*] First list element
 [*] Second list element
[/list]</pre>
<ul class="listbullet" style="list-style-type: circle;">
<li> First list element<br>
</li>
<li> Second list element</li>
</ul>

[list] is equivalent to [ul] (unordered list). 

[ol] can be used instead of [list] to show an ordered list:

<pre>[ol]
 [*] First list element
 [*] Second list element
[/ol]</pre>
<ul class="listdecimal" style="list-style-type: decimal;"><li> First list element<br></li><li> Second list element</li></ul>

For more options on ordered lists, you can define the style of numeration on [list] argument:
<pre>[list=1]</pre> : decimal

<pre>[list=i]</pre> : lover case roman

<pre>[list=I]</pre> : upper case roman

<pre>[list=a]</pre> : lover case alphabetic

<pre>[list=A] </pre> : upper case alphabetic




Embed
------

You can embed video, audio and more in a message.

<pre>[video]url[/video]</pre>
<pre>[audio]url[/audio]</pre>

Where *url* can be an url to youtube, vimeo, soundcloud, or other sites wich supports oembed or opengraph specifications.
*url* can be also full url to an ogg  file. HTML5 tag will be used to show it.

<pre>[url]*url*[/url]</pre>

If *url* supports oembed or opengraph specifications the embedded object will be shown (eg, documents from scribd).
Page title with a link to *url* will be shown.



Special
-------

If you need to put literal bbcode in a message, [noparse], [nobb] or [pre] are used to escape bbcode:

<pre>[noparse][b]bold[/b][/noparse]</pre> : [b]bold[/b]


