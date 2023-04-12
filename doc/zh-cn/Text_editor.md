<style>
figure { border: 4px #eeeeee solid; }
figure img { padding: 2px; }
figure figcaption { background: #eeeeee; color: #444444; padding: 2px; font-style: italic;}
</style>

# 创建帖子
===========
* [主页](help)

在这里，您可以了解创建和编辑帖子的不同方法。

在您的主页或网络页面的右上角单击“铅笔和纸张”图标或“分享”文本框，即可打开帖子编辑器。以下是 Friendica 的三种常见主题中的帖子编辑器示例:

<figure>
<img src="doc/img/editor_frio.png" alt="frio editor">
<figcaption>文章编辑器，使用<b>Frio</b>主题（流行的默认主题）。</figcaption>
</figure>
<p style="clear:both;"></p>
<figure>
<img src="doc/img/editor_vier.png" alt="vier editor" width="675">
<figcaption>文章编辑器，使用<b>Vier</b>主题。</figcaption>
</figure>
<p style="clear:both;"></p>

帖子标题是可选的，您可以通过单击“设置标题”来设置它。

帖子可以选择放在一个或多个类别中。写入由逗号分隔的类别名称以归档您的新帖子。

大的空白文本区域是您撰写新帖子的地方。
您可以在那里简单地输入文字，然后单击“分享”按钮，您的新帖子将公开在您的个人资料页面上，并分享给您的联系人。

如果纯文本对您来说不够有趣，Friendica 了解 BBCode 以为您的帖子增添情趣：粗体、斜体、图片、链接、列表等等。请查看 [BBCode 标记参考](help/BBCode) 页面，了解所有可以实现的功能。

文本区域下面的图标可帮助您快速书写帖子，但具体内容取决于所使用的主题：

在 Frio 主题下，下划线、斜体和粗体按钮应该是不言自明的。

<img src="doc/img/camera.png" width="32" height="32" alt="editor" align="left"> 从电脑上传一张图片。图片将被上传，正确的 bbcode 标签将会被添加到你的帖子中。

在 Frio 主题中，请使用 <b>浏览器</b> 标签代替以上传和/或附加内容到你的帖子中。

<img src="doc/img/paper_clip.png" width="32" height="32" alt="paper_clip" align="left"> 这取决于主题：对于 Frio，这是为了附加远程内容 - 将 URL 放入你的帖子中以嵌入视频或音频内容。 对于其他主题：从你的电脑中添加文件。与图片相同，但用于对帖子的一般附件。

<img src="doc/img/chain.png" width="32" height="32" alt="chain" align="left"> 添加一个网址 (URL)。输入一个 URL，Friendica 将在你的帖子中添加一个指向该 URL 的链接，并尝试提取网站的摘录信息 (如果可能)。

<img src="doc/img/video.png" width="32" height="32" alt="video" align="left"> 添加一个视频。输入视频播放地址 (OGG) 或者 YouTube 或 Vimeo 视频页面的 URL，它将被嵌入你的帖子中并显示预览图。(在 Frio 主题中，这是通过上述提到的纸片图标实现的。) Friendica 使用 [HTML5](http://en.wikipedia.org/wiki/HTML5_video) 来嵌入内容。因此，支持的文件类型取决于你的浏览器和操作系统 (OS)。一些文件类型为 WebM、MP4 和 OGG。

<img src="doc/img/mic.png" width="32" height="32" alt="mic" align="left"> 添加音频。与视频类似，但用于音频内容。根据你的浏览器和操作系统，可能支持 MP3、OGG 和 AAC 等格式。此外，你还可以添加来自音频主机网站如 Soundcloud 的 URL。

<img src="doc/img/globe.png" width="32" height="32" alt="globe" align="left"> <b>或者</b> <img src="doc/img/frio_location.png" width="32" height="32" alt="location" align="none"> 设置你的地理位置。该位置将被添加到 Google 地图搜索中。因此，类似 "纽约" 或 "10004" 的提示已经足够了。

这些图标可能会根据主题而变化。以下是一些示例：

<table>
<tr>
    <td>Vier: </td>
    <td><img src="doc/img/vier_icons.png" alt="vier.png" style="vertical-align:middle;"></td>
    <td>&nbsp;</td>
</tr>
</table>
<i><b>*</b> 如何上传文件</i>

**<img src="doc/img/lock.png" width="32" height="32" alt="lock icon"  style="vertical-align:middle;"> 锁定/权限**

在Frio主题中，权限选项卡（在其他主题中为锁按钮）是Friendica中最重要的功能。如果锁是打开的，则您的帖子将是公共的，并且当陌生人访问您的个人资料页面时会显示在上面。

单击它，*权限设置*窗口（也称为"*访问控制选择器*"或"*ACL选择器*"）就会弹出。在那里，您可以选择谁可以看到帖子。

<figure>
<img src="doc/img/acl_win.png" alt="Permission settings window">
<figcaption>权限设置窗口，选中了一些联系人</figcaption>
</figure>

单击联系人名称下的“显示”，即可将帖子隐藏到除选定联系人之外的所有人。

单击“对所有人可见”以使帖子再次公开。

如果您定义了一些群组，您也可以为群组勾选“显示”。该群组中的所有联系人都将看到该帖子。如果您想将选定为“显示”的群组的某个联系人的帖子隐藏起来，请单击该联系人名称下的“不显示”。

再次单击“显示”或“不显示”以将其关闭。

您可以使用搜索框搜索联系人或群组。

另请参阅[群组和隐私](help/Groups-and-Privacy)。
