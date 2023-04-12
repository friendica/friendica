### 常见问题解答 - FAQ

* [首页](help)

* **[我在哪里可以找到帮助？](help/FAQ#help)**
* **[为什么会收到有关证书的警告？](help/FAQ#ssl)**
* **[如何上传图片、文件、链接、视频和音频文件到帖子中？](help/FAQ#upload)**
* **[是否可以有不同的个人资料头像？](help/FAQ#avatars)**
* **[如何以特定语言查看 Friendica？](help/FAQ#language)**
* **[被屏蔽、忽略、存档和隐藏联系人的行为如何？](help/FAQ#contacts)**
* **[帐户被删除时会发生什么？是否真正删除？](help/FAQ#removed)**
* **[我可以订阅一个话题标签吗？](help/FAQ#hashtag)**
* **[如何创建流的 RSS 订阅？](help/FAQ#rss)**
* **[我可以使用哪些 Friendica 客户端？](help/FAQ#clients)**


<a name="help"></a>

### 我在哪里可以找到帮助？

如果本 FAQ 无法回答您的问题，您可以通过以下方式与社区取得联系：

  * Friendica 支持论坛：[@helpers@forum.friendi.ca](https://forum.friendi.ca/~helpers)
  * 社区聊天室（IRC、Matrix 和 XMPP 聊天室已桥接）这些公共聊天室被记录了
    * XMPP：support(at)forum.friendi.ca
    * IRC：[libera.chat](https://web.libera.chat/?channels=#friendica) 上的 #friendica
    * Matrix：[#friendica-en:matrix.org](https://matrix.to/#/#friendica-en:matrix.org) 或 [#friendi.ca:matrix.org](https://matrix.to/#/#friendi.ca:matrix.org)
  * [邮件列表](http://mailman.friendi.ca/mailman/listinfo/support-friendi.ca)
  <!--- * [XMPP](xmpp:support@forum.friendi.ca?join)
	https://github.com/github/markup/issues/202
	https://github.com/gjtorikian/html-pipeline/pull/307
	https://github.com/github/opensource.guide/pull/807
  --->

<a name="ssl"></a>
### 为什么会收到有关 SSL 证书的警告？


SSL（Secure Socket Layer）是一种加密计算机之间数据传输的技术。有时，您的浏览器会警告您缺少或无效的证书，这些警告可能有以下三个原因：

1. 您连接的服务器不提供SSL加密。
2. 服务器有自签名证书（不建议使用）。
3. 证书已过期。

我们建议与受影响的 Friendica 服务器的管理员进行交谈。（管理员请参阅[管理员手册](help/SSL)中的相应章节。）

<a name="upload"></a>
### 我如何上传图片、文件、链接、视频和音频文件到帖子中？

您可以通过 [编辑器](help/Text_editor) 从计算机上上传图片。所有已上传的图片概览列表位于 *yourpage.com/profile/profilename/photos* 页面。在该页面上，您还可以直接上传图片，并选择是否向联系人发送此上传的消息。

通常，您可以将任何类型的文件附加到帖子中。这可以通过在编辑器中使用“纸夹”符号实现。这些文件将与您的帖子链接，并可由联系人下载。但是，这些项目无法获得预览。因此，此上传方法仅适用于办公室或压缩文件。如果您想分享来自Dropbox，Owncloud或其他[filehoster](http://en.wikipedia.org/wiki/Comparison_of_file_hosting_services)的内容，请使用“链接”按钮（链符号）。

当您使用“链接”按钮添加其他网页的URL时，Friendica会尝试创建一个小预览。如果这不起作用，请尝试通过键入以下内容添加链接：[url=http://example.com]*自选名称*[/url]。

您还可以向帖子中添加视频和音频文件。但是，您必须使用以下一种方法之一，而不是直接上传：

1. 添加一个主机的视频或音频链接（Youtube，Vimeo，Soundcloud和任何支持oembed/opengraph的其他人）。视频将显示带有预览图像的预览，您可以单击该预览图像开始播放。 SoundCloud会直接将播放器插入到您的帖子中。

2. 如果您拥有自己的服务器，可以通过FTP上传多媒体文件并插入URL。

Friendica使用HTML5嵌入内容。因此，支持的文件取决于您的浏览器和操作系统。一些支持的文件类型包括WebM，MP4，MP3和OGG。请参阅维基百科了解更多信息([video](http://en.wikipedia.org/wiki/HTML5_video)，[audio](http://en.wikipedia.org/wiki/HTML5_audio))。

<a name="avatars"></a>

### 如何在每个个人资料上设置不同的头像？

是的。
您可以在 "编辑/管理个人资料" 页面找到 "更改个人资料照片" 链接。
点击链接将会带您到一个页面，您可以上传照片并选择与哪个个人资料关联。
为了避免隐私泄露，在您的帖子中，我们仅显示与默认个人资料关联的照片作为头像。

### 如何以特定语言查看Friendica？

您可以在地址栏中添加 `lang` 参数来完成。
参数中的数据是 [ISO 639-1](https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes) 代码。
在 url 和参数之间需要加上问号。

例如：

    https://social.example.com/profile/example 

在德语中是：

    https://social.example.com/profile/example?lang=de.

当强制使用某种语言时，该语言将一直保留直到会话结束。

### 阻止、忽略、归档和隐藏联系人的行为不同吗？

##### 阻止

将会阻止直接通信。
阻止的联系人不包含在交付范围内，他们发给您的帖子不会被导入。
但是，他们与您的朋友的交流仍然可见于您的动态。
如果您完全删除了一个联系人，他们可以再次向您发送好友请求。
阻止的联系人则不行，他们无法直接与您通信，只能通过朋友间的交流来与您联系。

##### 忽略

忽略的联系人包含在交付范围内，并且会收到您的帖子和私人消息。
但是，我们不会将他们的帖子或私人消息导入到您的动态中。
与阻止相似，您仍然可以看到该人在您朋友的帖子中的评论。

如果您希望完全屏蔽某个人，包括他们与您其他朋友之间的交流，可以安装名为 “blockem” 的插件，使您的动态页面折叠/隐藏来自特定人的所有帖子。

##### 归档

与归档的联系人不能进行通信，不做任何尝试。
然而，与阻止不同的是，该联系人在存档之前发布的帖子仍然可以在您的动态中看到。

##### 隐藏

联系人不会出现在您的公共好友列表中。
但是，隐藏的联系人将正常显示在会话中，这可能会使他们的隐藏状态暴露给任何能够看到该会话的人。
<a name="removed"></a>

### 当一个账户被删除会发生什么？

如果您删除您的账户，它将被安排在七天后永久删除。一旦您启动删除过程，您将无法再登录。只有您节点的管理员可以在永久删除之前停止此过程。

在七天的时间过去后，所有存储在您节点上的帖子、消息、照片和个人信息将被删除。您的节点还将向您的所有联系人发出删除请求；如果您被列入全局目录，则也会将您的个人资料从其中删除。出于安全原因，您的用户名不能重新发行以进行未来的注册。

<a name="hashtag"></a>
### 我可以关注一个话题吗？

可以。只需将标签添加到您的已保存搜索中。这些帖子将出现在您的网络页面上。由于技术原因，对这些帖子的回复不会出现在网络页面上的“个人”选项卡中，整个线程也无法通过 API 访问。

<a name="rss"></a>
### 如何创建流的 RSS 订阅？

如果您想通过 RSS 共享您的公共页面，则可以使用以下链接之一：

#### 您的帖子的 RSS 订阅

	basic-url.com//feed/[nickname]/posts

例如：Friendica Support

	https://forum.friendi.ca/feed/helpers/posts

#### 您站点上的交谈的 RSS 订阅

	basic-url.com/feed/profilename/comments

例如：Friendica Support

	https://forum.friendi.ca/feed/helpers/comments

<a name="clients"></a>
### 我能使用哪些 Friendica 客户端？

Friendica 支持 [Mastodon API](help/API-Mastodon) 和 [Twitter API | gnusocial](help/api)。这意味着您可以使用一些 Mastodon 和 Twitter 客户端来访问 Friendica。可用功能因客户端而异。

#### Android

* [AndStatus](http://andstatus.org) ([F-Droid](https://f-droid.org/repository/browse/?fdid=org.andstatus.app), [Google Play](https://play.google.com/store/apps/details?id=org.andstatus.app))
* [Fedi](https://github.com/Big-Fig/Fediverse.app) ([Google Play](https://play.google.com/store/apps/details?id=com.fediverse.app))
* [Fedilab](https://fedilab.app) ([F-Droid](https://f-droid.org/app/fr.gouv.etalab.mastodon), [Google Play](https://play.google.com/store/apps/details?id=app.fedilab.android))
* [Friendiqa](https://git.friendi.ca/lubuwest/Friendiqa) ([F-Droid](https://git.friendi.ca/lubuwest/Friendiqa#install), [Google Play](https://play.google.com/store/apps/details?id=org.qtproject.friendiqa))
* [Husky](https://git.sr.ht/~captainepoch/husky) ([F-Droid](https://f-droid.org/repository/browse/?fdid=su.xash.husky), [Google Play](https://play.google.com/store/apps/details?id=su.xash.husky))
* [Mastodon](https://github.com/mastodon/mastodon-android) ([F-Droid](https://f-droid.org/en/packages/org.joinmastodon.android/), [Google Play](https://play.google.com/store/apps/details?id=org.joinmastodon.android))
* [Subway Tooter](https://github.com/tateisu/SubwayTooter) ([F-Droid](https://android.izzysoft.de/repo/apk/jp.juggler.subwaytooter))
* [Tooot](https://tooot.app/) ([Google Play](https://play.google.com/store/apps/details?id=com.xmflsct.app.tooot))
* [Tusky](https://tusky.app) ([F-Droid](https://f-droid.org/repository/browse/?fdid=com.keylesspalace.tusky), [Google Play](https://play.google.com/store/apps/details?id=com.keylesspalace.tusky))
* [TwidereX](https://github.com/TwidereProject/TwidereX-Android) ([F-Droid](https://f-droid.org/en/packages/com.twidere.twiderex/), [Google Play](https://play.google.com/store/apps/details?id=com.twidere.twiderex))
* [Yuito](https://github.com/accelforce/Yuito) ([Google Play](https://play.google.com/store/apps/details?id=net.accelf.yuito))

#### iOS

* [Mastodon](https://joinmastodon.org/apps) ([AppStore](https://apps.apple.com/us/app/mastodon-for-iphone/id1571998974))
* [Stella*](https://www.stella-app.net/) ([AppStore](https://apps.apple.com/us/app/stella-for-mastodon-twitter/id921372048))
* [Tooot](https://github.com/tooot-app) ([AppStore](https://apps.apple.com/app/id1549772269)
* [TwidereX](https://github.com/TwidereProject/TwidereX-iOS) ([AppStore](https://apps.apple.com/app/twidere-x/id1530314034))

#### Linux

* [Choqok](https://choqok.kde.org)
* [Whalebird](https://whalebird.social/en/desktop/contents) ([GitHub](https://github.com/h3poteto/whalebird-desktop))
* [TheDesk](https://thedesk.top/en/) ([GitHub](https://github.com/cutls/TheDesk))
* [Toot](https://toot.readthedocs.io/en/latest/)

#### macOS

* [TheDesk](https://thedesk.top/en/) ([GitHub](https://github.com/cutls/TheDesk))
* [Whalebird](https://whalebird.social/en/desktop/contents) ([AppStore](https://apps.apple.com/de/app/whalebird/id1378283354), [GitHub](https://github.com/h3poteto/whalebird-desktop))

#### Windows

* [TheDesk](https://thedesk.top/en/) ([GitHub](https://github.com/cutls/TheDesk))
* [Whalebird](https://whalebird.social/en/desktop/contents) ([Website Download](https://whalebird.social/en/desktop/contents/downloads#windows), [GitHub](https://github.com/h3poteto/whalebird-desktop))

#### Web 前端

* [Halcyon](https://www.halcyon.social/)
* [Pinafore](https://github.com/nolanlawson/pinafore)