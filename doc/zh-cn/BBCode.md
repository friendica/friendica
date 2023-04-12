Friendica BBCode 标签参考
========================

* [创建帖子](help/Text_editor)

## 行内标签 (Inline)

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
  <td>[url=http://friendi.ca]Friendica[/url]</td>
  <td><a href="http://friendi.ca" target="external-link">Friendica</a></td>
</tr>
<tr>
  <td>[img]https://raw.githubusercontent.com/friendica/friendica/stable/images/friendica-32.png[/img]</td>
  <td><img src="https://raw.githubusercontent.com/friendica/friendica/stable/images/friendica-32.png"></td>
</tr>
<tr>
  <td>[img=https://raw.githubusercontent.com/friendica/friendica/stable/images/friendica-32.png]The Friendica Logo[/img]</td>
  <td><img src="https://raw.githubusercontent.com/friendica/friendica/stable/images/friendica-32.png" alt="The Friendica Logo"></td>
</tr>
<tr>
  <td>[img=64x32]https://raw.githubusercontent.com/friendica/friendica/stable/images/friendica.svg[/img]<br>
<br>Note: provided height is simply discarded.</td>
  <td><img src="https://raw.githubusercontent.com/friendica/friendica/stable/images/friendica.svg" style="width: 64px;"></td>
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
  <td>[size=20]exact size[/size] (size can be any number, in pixels)</td>
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
  <td>[url]http://friendi.ca[/url]</td>
  <td><a href="http://friendi.ca">http://friendi.ca</a></td>
</tr>
<tr>
  <td>[url=http://friendi.ca]Friendica[/url]</td>
  <td><a href="http://friendi.ca">Friendica</a></td>
</tr>
<tr>
  <td>[bookmark]http://friendi.ca[/bookmark]<br><br>
#^[url]http://friendi.ca[/url]</td>
  <td><span class="oembed link"><h4>Friendica: <a href="http://friendi.ca" rel="oembed"></a><a href="http://friendi.ca" target="_blank" rel="noopener noreferrer">http://friendi.ca</a></h4></span></td>
</tr>
<tr>
  <td>[bookmark=http://friendi.ca]Bookmark[/bookmark]<br><br>
#^[url=http://friendi.ca]Bookmark[/url]<br><br>
#[url=http://friendi.ca]^[/url][url=http://friendi.ca]Bookmark[/url]</td>
  <td><span class="oembed link"><h4>Friendica: <a href="http://friendi.ca" rel="oembed"></a><a href="http://friendi.ca" target="_blank" rel="noopener noreferrer">Bookmark</a></h4></span></td>
</tr>
<tr>
  <td>[url=/posts/f16d77b0630f0134740c0cc47a0ea02a]Diaspora post with GUID[/url]</td>
  <td><a href="/display/f16d77b0630f0134740c0cc47a0ea02a" target="_blank" rel="noopener noreferrer">Diaspora post with GUID</a></td>
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
  <td>[code=php]function text_highlight($s,$lang)[/code]<sup><a href="#supported-code">1</a></sup></td>
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
      <div class="body-attach"></div>
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
      <div class="body-attach"></div>
    </div>
  </td>
</tr>
<tr>
  <td>[hr] (horizontal line)</td>
  <td><hr></td>
</tr>
</table>

<a name="supported-code">1</a>: Supported language parameter values for code highlighting:
- abap
- avrc
- cpp
- css
- diff
- dtd
- html
- java
- javascript
- js
- mysql
- perl
- php
- python
- ruby
- sh
- sql
- vbscript
- xml

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

您可以在消息中嵌入视频，音频等等。

<table class="bbcodes">
<tr>
  <th>BBCode</th>
  <th>Result</th>
</tr>
<tr>
  <td>[video]url[/video]</td>
  <td>其中 *url* 可以是指向 youtube、vimeo、soundcloud 或其他支持 oembed 和 opengraph 规范的网站的 url。</td>
</tr>
<tr>
  <td>[video]视频文件 url[/video]
[audio]音频文件 url[/audio]</td>
  <td>完整的 ogg/ogv/oga/ogm/webm/mp4/mp3 文件 URL。将使用 HTML5 播放器显示它。</td>
</tr>
<tr>
  <td>[youtube]Youtube URL[/youtube]</td>
  <td>Youtube 视频 OEmbed 显示。可能无法嵌入实际播放器。</td>
</tr>
<tr>
  <td>[youtube]Youtube video ID[/youtube]</td>
  <td>Youtube 播放器 iframe 嵌入。</td>
</tr>
<tr>
  <td>[vimeo]Vimeo URL[/vimeo]</td>
  <td>Vimeo 视频 OEmbed 显示。可能无法嵌入实际播放器。</td>
</tr>
<tr>
  <td>[vimeo]Vimeo video ID[/vimeo]</td>
  <td>Vimeo 播放器 iframe 嵌入。</td>
</tr>
<tr>
  <td>[embed]URL[/embed]</td>
  <td>嵌入 OEmbed 富内容。</td>
</tr>
<tr>
  <td>[url]*url*[/url]</td>
  <td>如果 *url* 支持 oembed 或 opengraph 规范，则会显示嵌入对象（例如来自 scribd 的文档）。
将显示带有指向 *url* 的页面标题的链接。</td>
</tr>
</table>

## 地图

这需要 "openstreetmap" 或 "Google Maps" 插件版本 1.3 或更高版本。
如果未激活插件，则将显示原始坐标。

<table class="bbcodes">
<tr>
  <th>BBCode</th>
  <th>Result</th>
</tr>
<tr>
  <td>[map]address[/map]</td>
  <td>嵌入以此地址为中心的地图。</td>
</tr>
<tr>
  <td>[map=lat,long]</td>
  <td>嵌入以这些坐标为中心的地图。</td>
</tr>
<tr>
  <td>[map]</td>
  <td>嵌入以帖子位置为中心的地图。</td>
</tr>
</table>

较长帖子的摘要

如果您要将帖子传播到多个第三方网络，您可能会遇到这样的问题，这些网络会有类似 Twitter 的长度限制。

Friendica 使用半智能机制生成适当的摘要。
但是定义一个仅在外部网络上显示的自定义摘要可能会很有用。这是使用 [abstract] 元素完成的。

<table class="bbcodes">
<tr>
  <th>BBCode</th>
  <th>Result</th>
</tr>
<tr>
  <td>[abstract]非常有趣！必看！请单击链接！[/abstract]<br>
我想告诉你一个非常无聊的故事，你真的从未想过要听。</td>
  <td>Twitter 将显示文本：<blockquote>非常有趣！必看！请单击链接！</blockquote>
在 Friendica 上，您只会在 <blockquote>我想告诉你一个非常...</blockquote> 后看到文本。</td>
</tr>
</table>

甚至可以为不同的网络定义摘要：

<table class="bbcodes">
<tr>
  <th>BBCode</th>
  <th>Result</th>
</tr>
<tr>
  <td>
[abstract]大家好，这是我的最新照片！[/abstract]<br>
[abstract=twit]亲爱的 Twitter 关注者们，你们想看看我的新照片吗？[/abstract]<br>
[abstract=apdn]亲爱的 ADN 关注者，我拍了一些新照片，想和你们分享。</abstract><br>
今天我在森林里拍了些很酷的照片...</td>
  <td>对于 Twitter 和 App.net 等网络，系统将使用定义的摘要。<br>
对于其他网络（例如，当您使用 "statusnet" 连接器发布到您的 GNU Social 账户时），将使用通用抽象元素。</td>
</tr>
</table>

如果您使用（例如）"缓冲区" 连接器发布到 Facebook 或 Google+，则可以使用此元素为您不希望完全发布到这些网络的较长博客文章定义摘要。

像 Facebook 或 Google+ 这样的网络没有长度限制。
因此，[abstract] 元素未被使用。
相反，必须命名显式网络：

<table class="bbcodes">
<tr>
  <th>BBCode</th>
  <th>Result</th>
</tr>
<tr>
  <td>
[abstract]这些天我遇到了一些奇怪的事情......[/abstract]<br>
[abstract=goog]你好，我的亲爱的 Google+ 关注者。你们必须阅读一下我的最新博客文章！</abstract><br>
[abstract=face]你好，我的脸书朋友们。这些天发生了一些真正酷的事情。</abstract><br>
在森林里拍照时，我遇到了一个非常奇怪的事...</td>
  <td>Google 和 Facebook 将显示各自的摘要，而其他网络将显示默认的摘要。<br>
<br>同时，Friendica 不会显示任何摘要。</td>
</tr>
</table>

[abstract] 元素无法与我们直接发布 HTML 的连接器一起使用，例如 Tumblr、WordPress 或 Pump.io。
对于本机连接--例如 Friendica、Hubzilla、Diaspora 或 GNU Social--将使用完整的帖子，并且联系人实例将按预期显示帖子。

对于通过 ActivityPub 传递的帖子，将在摘要字段中放置来自摘要的文本。
在 Mastodon 上，此字段用于内容警告。

## 特殊功能

<table class="bbcodes">
<tr>
  <th>BBCode</th>
  <th>结果</th>
</tr>
<tr>
  <td>如果你需要在消息中插入纯文本的 BBCode，可以使用 [noparse]、[nobb] 或 [pre] 块来防止 BBCode 转换：
    <ul>
      <li>[noparse][b]加粗[/b][/noparse]</li>
      <li>[nobb][b]加粗[/b][/nobb]</li>
      <li>[pre][b]加粗[/b][/pre]</li>
    </ul>
    注意：[code] 优先于 [noparse]、[nobb] 和 [pre]，这使它们在代码块中显示为 BBCode 标签，而不是被删除。
    [noparse] 中的 [code] 块仍将转换为代码块。
  </td>
  <td>[b]加粗[/b]</td>
</tr>
<tr>
  <td>此外，[noparse] 和 [pre] 块防止提及和标签转换为链接：
    <ul>
      <li>[noparse]@用户@域名.tld #hashtag[/noparse]</li>
      <li>[pre]@用户@域名.tld #hashtag[/pre]</li>
    </ul>
  </td>
  <td>@用户@域名.tld #hashtag</td>
</tr>
<tr>
  <td>此外，[pre] 块保留空格：
    <ul>
      <li>[pre]&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;空格[/pre]</li>
    </ul>
  </td>
  <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;空格</td>
</tr>
<tr>
  <td>[nosmile] 用于按帖子禁用表情符号<br>
    <br>
    [nosmile] ;-) :-O
  </td>
  <td>;-) :-O</td>
</tr>
<tr>
  <td>自定义块级样式<br>
<br>
[style=text-shadow: 0 0 4px #CC0000;]您可以更改此块的所有 CSS 属性。[/style]</td>
  <td><div style="text-shadow: 0 0 4px #cc0000;;">您可以更改此块的所有 CSS 属性。</div></td>
</tr>
<tr>
  <td>自定义内联样式<br>
<br>
[style=text-shadow: 0 0 4px #CC0000;]您可以更改此内联文本的所有 CSS 属性。[/style]</td>
  <td>您可以更改所有<span style="text-shadow: 0 0 4px #cc0000;;">此内联文本的 CSS 属性。</span></td>
</tr>
</table>
