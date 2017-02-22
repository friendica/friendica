Friendica BBCode tags reference
========================

* [Creating posts](help/Text_editor)

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
  <th>Result</th>
</tr>
<tr>
  <td>[b]bold[/b]</td>
  <td><strong>bold</strong></td>
</tr>
<tr>
  <td>[i]italic[/i]</td>
  <td><em>italic</em></td>
</tr>
<tr>
  <td>[u]underlined[/u]</td>
  <td><u>underlined</u></td>
</tr>
<tr>
  <td>[s]strike[/s]</td>
  <td><strike>strike</strike></td>
</tr>
<tr>
  <td>[o]overline[/o]</td>
  <td><span class="overline">overline</span></td>
</tr>
<tr>
  <td>[color=red]red[/color]</td>
  <td><span style="color:  red;">red</span></td>
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
  <td>[size=xx-small]small text[/size]</td>
  <td><span style="font-size: xx-small;">small text</span></td>
</tr>
<tr>
  <td>[size=xx-large]big text[/size]</td>
  <td><span style="font-size: xx-large;">big text</span></td>
</tr>
<tr>
  <td>[size=20]exact size[/size] (size can be any number, in pixel)</td>
  <td><span style="font-size: 20px;">exact size</span></td>
</tr>
<tr>
  <td>[font=serif]Serif font[/font]</td>
  <td><span style="font-family: serif;">Serif font</span></td>
</tr>
</table>

### Links

<table class="bbcodes">
<tr>
  <th>BBCode</th>
  <th>Result</th>
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
  <td>[bookmark=http://friendica.com]Bookmark[/bookmark]<br><br>
#^[url=http://friendica.com]Bookmark[/url]<br><br>
#[url=http://friendica.com]^[/url][url=http://friendica.com]Bookmark[/url]</td>
  <td><span class="oembed link"><h4>Friendica: <a href="http://friendica.com" rel="oembed"></a><a href="http://friendica.com" target="_blank">Bookmark</a></h4></span></td>
</tr>
<tr>
  <td>[url=/posts/f16d77b0630f0134740c0cc47a0ea02a]Diaspora post with GUID[/url]</td>
  <td><a href="/display/f16d77b0630f0134740c0cc47a0ea02a" target="_blank">Diaspora post with GUID</a></td>
</tr>
<tr>
  <td>#Friendica</td>
  <td>#<a href="/search?tag=Friendica">Friendica</a></td>
</tr>
<tr>
  <td>@Mention</td>
  <td>@<a href="javascript:void(0)">Mention</a></td>
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
  <td>[mail=user@mail.example.com]Send an email to User[/mail]</td>
  <td><a href="mailto:user@mail.example.com">Send an email to User</a></td>
</tr>
</table>

## Blocks

<table class="bbcodes">
<tr>
  <th>BBCode</th>
  <th>Result</th>
</tr>
<tr>
  <td>[p]A paragraph of text[/p]</td>
  <td><p>A paragraph of text</p></td>
</tr>
<tr>
  <td>Inline [code]code[/code] in a paragraph</td>
  <td>Inline <key>code</key> in a paragraph</td>
</tr>
<tr>
  <td>[code]Multi<br>line<br>code[/code]</td>
  <td><code>Multi
line
code</code></td>
</tr>
<tr>
  <td>[code=php]function text_highlight($s,$lang)[/code]</td>
  <td><code><div class="hl-main"><ol class="hl-main"><li><span class="hl-code">&nbsp;</span><span class="hl-reserved">function</span><span class="hl-code"> </span><span class="hl-identifier">text_highlight</span><span class="hl-brackets">(</span><span class="hl-var">$s</span><span class="hl-code">,</span><span class="hl-var">$lang</span><span class="hl-brackets">)</span></li></ol></div></code></td>
</tr>
<tr>
  <td>[quote]quote[/quote]</td>
  <td><blockquote>quote</blockquote></td>
</tr>
<tr>
  <td>[quote=Author]Author? Me? No, no, no...[/quote]</td>
  <td><strong class="author">Author wrote:</strong><blockquote>Author? Me? No, no, no...</blockquote></td>
</tr>
<tr>
  <td>[center]Centered text[/center]</td>
  <td><div style="text-align:center;">Centered text</div></td>
</tr>
<tr>
  <td>You should not read any further if you want to be surprised.[spoiler]There is a happy end.[/spoiler]</td>
  <td>
    <div class="wall-item-container">
      You should not read any further if you want to be surprised.<br>
      <span id="spoiler-wrap-0716e642" class="spoiler-wrap fakelink" onclick="openClose('spoiler-0716e642');">Click to open/close</span>
      <blockquote class="spoiler" id="spoiler-0716e642" style="display: none;">There is a happy end.</blockquote>
      <div class="body-attach"><div class="clear"></div></div>
    </div>
  </td>
</tr>
<tr>
  <td>[spoiler=Author]Spoiler quote[/spoiler]</td>
  <td>
    <div class="wall-item-container">
      <strong class="spoiler">Author wrote:</strong><br>
      <span id="spoiler-wrap-a893765a" class="spoiler-wrap fakelink" onclick="openClose('spoiler-a893765a');">Click to open/close</span>
      <blockquote class="spoiler" id="spoiler-a893765a" style="display: none;">Spoiler quote</blockquote>
      <div class="body-attach"><div class="clear"></div></div>
    </div>
  </td>
</tr>
<tr>
  <td>[hr] (horizontal line)</td>
  <td><hr></td>
</tr>
</table>

### Titles

<table class="bbcodes">
<tr>
  <th>BBCode</th>
  <th>Result</th>
</tr>
<tr>
  <td>[h1]Title 1[/h1]</td>
  <td><h1>Title 1</h1></td>
</tr>
<tr>
  <td>[h2]Title 2[/h2]</td>
  <td><h2>Title 2</h2></td>
</tr>
<tr>
  <td>[h3]Title 3[/h3]</td>
  <td><h3>Title 3</h3></td>
</tr>
<tr>
  <td>[h4]Title 4[/h4]</td>
  <td><h4>Title 4</h4></td>
</tr>
<tr>
  <td>[h5]Title 5[/h5]</td>
  <td><h5>Title 5</h5></td>
</tr>
<tr>
  <td>[h6]Title 6[/h6]</td>
  <td><h6>Title 6</h6></td>
</tr>
</table>

### Tables

<table class="bbcodes">
<tr>
  <th>BBCode</th>
  <th>Result</th>
</tr>
<tr>
  <td>[table]<br>
&nbsp;&nbsp;[tr]<br>
&nbsp;&nbsp;&nbsp;&nbsp;[th]Header 1[/th]<br>
&nbsp;&nbsp;&nbsp;&nbsp;[th]Header 2[/th]<br>
&nbsp;&nbsp;&nbsp;&nbsp;[th]Header 2[/th]<br>
&nbsp;&nbsp;[/tr]<br>
&nbsp;&nbsp;[tr]<br>
&nbsp;&nbsp;&nbsp;&nbsp;[td]Cell 1[/td]<br>
&nbsp;&nbsp;&nbsp;&nbsp;[td]Cell 2[/td]<br>
&nbsp;&nbsp;&nbsp;&nbsp;[td]Cell 3[/td]<br>
&nbsp;&nbsp;[/tr]<br>
&nbsp;&nbsp;[tr]<br>
&nbsp;&nbsp;&nbsp;&nbsp;[td]Cell 4[/td]<br>
&nbsp;&nbsp;&nbsp;&nbsp;[td]Cell 5[/td]<br>
&nbsp;&nbsp;&nbsp;&nbsp;[td]Cell 6[/td]<br>
&nbsp;&nbsp;[/tr]<br>
[/table]</td>
  <td>
	<table>
      <tbody>
        <tr>
          <th>Header 1</th>
          <th>Header 2</th>
          <th>Header 3</th>
        </tr>
        <tr>
          <td>Cell 1</td>
          <td>Cell 2</td>
          <td>Cell 3</td>
        </tr>
        <tr>
          <td>Cell 4</td>
          <td>Cell 5</td>
          <td>Cell 6</td>
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
          <th>Header 1</th>
          <th>Header 2</th>
          <th>Header 3</th>
        </tr>
        <tr>
          <td>Cell 1</td>
          <td>Cell 2</td>
          <td>Cell 3</td>
        </tr>
        <tr>
          <td>Cell 4</td>
          <td>Cell 5</td>
          <td>Cell 6</td>
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
          <th>Header 1</th>
          <th>Header 2</th>
          <th>Header 3</th>
        </tr>
        <tr>
          <td>Cell 1</td>
          <td>Cell 2</td>
          <td>Cell 3</td>
        </tr>
        <tr>
          <td>Cell 4</td>
          <td>Cell 5</td>
          <td>Cell 6</td>
        </tr>
      </tbody>
    </table>
  </td>
</tr>
</table>

### Lists

<table class="bbcodes">
<tr>
  <th>BBCode</th>
  <th>Result</th>
</tr>
<tr>
  <td>[ul]<br>
&nbsp;&nbsp;[li] First list element<br>
&nbsp;&nbsp;[li] Second list element<br>
[/ul]<br>
[list]<br>
&nbsp;&nbsp;[*] First list element<br>
&nbsp;&nbsp;[*] Second list element<br>
[/list]</td>
  <td>
	<ul class="listbullet" style="list-style-type: circle;">
		<li>First list element</li>
		<li>Second list element</li>
	</ul>
  </td>
</tr>
<tr>
  <td>[ol]<br>
&nbsp;&nbsp;[*] First list element<br>
&nbsp;&nbsp;[*] Second list element<br>
[/ol]<br>
[list=1]<br>
&nbsp;&nbsp;[*] First list element<br>
&nbsp;&nbsp;[*] Second list element<br>
[/list]</td>
  <td>
    <ul class="listdecimal" style="list-style-type: decimal;">
      <li> First list element</li>
      <li> Second list element</li>
    </ul>
  </td>
</tr>
<tr>
  <td>[list=]<br>
&nbsp;&nbsp;[*] First list element<br>
&nbsp;&nbsp;[*] Second list element<br>
[/list]</td>
  <td>
    <ul class="listnone" style="list-style-type: none;">
      <li> First list element</li>
      <li> Second list element</li>
    </ul>
  </td>
</tr>
<tr>
  <td>[list=i]<br>
&nbsp;&nbsp;[*] First list element<br>
&nbsp;&nbsp;[*] Second list element<br>
[/list]</td>
  <td>
    <ul class="listlowerroman" style="list-style-type: lower-roman;">
      <li> First list element</li>
      <li> Second list element</li>
    </ul>
  </td>
</tr>
<tr>
  <td>[list=I]<br>
&nbsp;&nbsp;[*] First list element<br>
&nbsp;&nbsp;[*] Second list element<br>
[/list]</td>
  <td>
    <ul class="listupperroman" style="list-style-type: upper-roman;">
      <li> First list element</li>
      <li> Second list element</li>
    </ul>
  </td>
</tr>
<tr>
  <td>[list=a]<br>
&nbsp;&nbsp;[*] First list element<br>
&nbsp;&nbsp;[*] Second list element<br>
[/list]</td>
  <td>
    <ul class="listloweralpha" style="list-style-type: lower-alpha;">
      <li> First list element</li>
      <li> Second list element</li>
    </ul>
  </td>
</tr>
<tr>
  <td>[list=A]<br>
&nbsp;&nbsp;[*] First list element<br>
&nbsp;&nbsp;[*] Second list element<br>
[/list]</td>
  <td>
    <ul class="listupperalpha" style="list-style-type: upper-alpha;">
      <li> First list element</li>
      <li> Second list element</li>
    </ul>
  </td>
</tr>
</table>

## Embed

You can embed video, audio and more in a message.

<table class="bbcodes">
<tr>
  <th>BBCode</th>
  <th>Result</th>
</tr>
<tr>
  <td>[video]url[/video]</td>
  <td>Where *url* can be an url to youtube, vimeo, soundcloud, or other sites wich supports oembed or opengraph specifications.</td>
</tr>
<tr>
  <td>[video]Video file url[/video]
[audio]Audio file url[/audio]</td>
  <td>Full URL to an ogg/ogv/oga/ogm/webm/mp4/mp3 file. An HTML5 player will be used to show it.</td>
</tr>
<tr>
  <td>[youtube]Youtube URL[/youtube]</td>
  <td>Youtube video OEmbed display. May not embed an actual player.</td>
</tr>
<tr>
  <td>[youtube]Youtube video ID[/youtube]</td>
  <td>Youtube player iframe embed.</td>
</tr>
<tr>
  <td>[vimeo]Vimeo URL[/vimeo]</td>
  <td>Vimeo video OEmbed display. May not embed an actual player.</td>
</tr>
<tr>
  <td>[vimeo]Vimeo video ID[/vimeo]</td>
  <td>Vimeo player iframe embed.</td>
</tr>
<tr>
  <td>[embed]URL[/embed]</td>
  <td>Embed OEmbed rich content.</td>
</tr>
<tr>
  <td>[iframe]URL[/iframe]</td>
  <td>General embed, iframe size is limited by the theme size for video players.</td>
</tr>
<tr>
  <td>[url]*url*[/url]</td>
  <td>If *url* supports oembed or opengraph specifications the embedded object will be shown (eg, documents from scribd).
Page title with a link to *url* will be shown.</td>
</tr>
</table>

## Map

This require "openstreetmap" or "Google Maps" addon version 1.3 or newer.
If the addon isn't activated, the raw coordinates are shown instead.

<table class="bbcodes">
<tr>
  <th>BBCode</th>
  <th>Result</th>
</tr>
<tr>
  <td>[map]address[/map]</td>
  <td>Embeds a map centered on this address.</td>
</tr>
<tr>
  <td>[map=lat,long]</td>
  <td>Embeds a map centered on those coordinates.</td>
</tr>
<tr>
  <td>[map]</td>
  <td>Embeds a map centered on the post's location.</td>
</tr>
</table>

## Abstract for longer posts

If you want to spread your post to several third party networks you can have the problem that these networks have a length limitation like on Twitter.

Friendica is using a semi intelligent mechanism to generate a fitting abstract.
But it can be interesting to define a custom abstract that will only be displayed on the external network.
This is done with the [abstract]-element.
<table class="bbcodes">
<tr>
  <th>BBCode</th>
  <th>Result</th>
</tr>
<tr>
  <td>[abstract]Totally interesting! A must-see! Please click the link![/abstract]<br>
I want to tell you a really boring story that you really never wanted to hear.</td>
  <td>Twitter would display the text <blockquote>Totally interesting! A must-see! Please click the link!</blockquote>
On Friendica you would only see the text after <blockquote>I want to tell you a really ...</blockquote></td>
</tr>
</table>

It is even possible to define abstracts for separate networks:

<table class="bbcodes">
<tr>
  <th>BBCode</th>
  <th>Result</th>
</tr>
<tr>
  <td>
[abstract]Hi friends Here are my newest pictures![/abstract]<br>
[abstract=twit]Hi my dear Twitter followers. Do you want to see my new
pictures?[/abstract]<br>
[abstract=apdn]Helly my dear followers on ADN. I made sone new pictures
that I wanted to share with you.[/abstract]<br>
Today I was in the woods and took some real cool pictures ...</td>
  <td>For Twitter and App.net the system will use the defined abstracts.<br>
For other networks (e.g. when you are using the "statusnet" connector that is used to post to your GNU Social account) the general abstract element will be used.</td>
</tr>
</table>

If you use (for example) the "buffer" connector to post to Facebook or Google+ you can use this element to define an abstract for a longer blogpost that you don't want to post completely to these networks.

Networks like Facebook or Google+ aren't length limited.
For this reason the [abstract] element isn't used.
Instead you have to name the explicit network:

<table class="bbcodes">
<tr>
  <th>BBCode</th>
  <th>Result</th>
</tr>
<tr>
  <td>
[abstract]These days I had a strange encounter...[/abstract]<br>
[abstract=goog]Helly my dear Google+ followers. You have to read my newest blog post![/abstract]<br>
[abstract=face]Hello my Facebook friends. These days happened something really cool.[/abstract]<br>
While taking pictures in the woods I had a really strange encounter...</td>
  <td>Google and Facebook will show the respective abstracts while the other networks will show the default one.<br>
<br>Meanwhile, Friendica won't show any of the abstracts.</td>
</tr>
</table>

The [abstract] element isn't working with connectors where we post the HTML like Tumblr, Wordpress or Pump.io.
For the native connections--that is to e.g. Friendica, Hubzilla, Diaspora or GNU Social--the full posting is used and the contacts instance will display the posting as desired.

## Special

<table class="bbcodes">
<tr>
  <th>BBCode</th>
  <th>Result</th>
</tr>
<tr>
  <td>If you need to put literal bbcode in a message, [noparse], [nobb] or [pre] are used to escape bbcode:
    <ul>
      <li>[noparse][b]bold[/b][/noparse]</li>
      <li>[nobb][b]bold[/b][/nobb]</li>
      <li>[pre][b]bold[/b][/pre]</li>
    </ul>
  </td>
  <td>[b]bold[/b]</td>
</tr>
<tr>
  <td>[nosmile] is used to disable smilies on a post by post basis<br>
    <br>
    [nosmile] ;-) :-O
  </td>
  <td>;-) :-O</td>
</tr>
<tr>
  <td>Custom inline styles<br>
<br>
[style=text-shadow: 0 0 4px #CC0000;]You can change all the CSS properties of this block.[/style]</td>
  <td><span style="text-shadow: 0 0 4px #cc0000;;">You can change all the CSS properties of this block.</span></td>
</tr>
<tr>
  <td>Custom class block<br>
<br>
[class=custom]If the class exists, this block will have the custom class style applied.[/class]</td>
  <td><pre>&lt;span class="custom"&gt;If the class exists,<br> this block will have the custom class<br> style applied.&lt;/span&gt;</pre></td>
</tr>
</table>
