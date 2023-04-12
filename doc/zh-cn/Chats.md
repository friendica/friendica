聊天

=====

* [主页](help)

在您的 Friendica 网站上使用聊天功能有两种可能性：

* IRC 聊天
* Jappix

IRC 聊天插件
---

启用插件后，您可以在 [yoursite.com/irc](../irc) 找到聊天功能。
注意：您无需登录即可在您的网站上使用此聊天功能，因此任何人都可以使用它。

如果您按照链接进入，您将看到 IRC 聊天的登录页面。
现在选择一个昵称和一个聊天室。
您可以为房间选择任何您喜欢的名称，甚至可以是类似于 #superchatwhosenameisonlyknownbyme。
最后，解决验证码并单击连接按钮。

下面的窗口显示连接期间的一些文本。
这些文本不重要，只需等待下一个窗口即可。
第一行显示您的名称和您当前的 IP 地址。
窗口右侧显示所有用户。
窗口底部包含一个输入字段。

Jappix Mini
---

Jappix Mini 插件为 Jabber 和 XMPP 联系人创建了一个聊天框。
在设置插件之前，您应该已经拥有一个 jabber/XMPP 帐户。
您可以在 [jabber.org](http://www.jabber.org/) 上找到更多信息。

您可以使用多个服务器来创建帐户：

* [https://jappix.com](https://jappix.com)
* [http://xmpp.net](http://xmpp.net)

### 1. 基础

首先，您需要获取当前版本。您可以从 [Github](https://github.com) 拉取它，如下所示：

    $> cd /var/www/virtual/YOURSPACE/html/addon; git pull

或者您可以在这里下载一个 tar 存档文件：[jappixmini.tgz](https://github.com/friendica/friendica-addons/blob/stable/jappixmini.tgz)（点击“查看原始文件”）。

只需解压缩该文件并将该目录重命名为“jappixmini”。
接下来，上传此目录和 .tgz 文件到 Friendica 安装的插件目录中。

现在，您可以在管理员页面上全局激活插件。
在插件侧边栏中，您现在会找到 jappix 条目（您还可以找到 twitter、GNU Social 和其他条目）。
以下页面显示了此插件的设置。

启用 BOSH 代理。

### 2. 设置

接下来进入您的用户帐户设置并选择插件页面。
向下滚动，直到找到 Jappix Mini 插件设置。

首先您需要激活插件。

现在添加您的 Jabber/XMPP 名称、域/服务器（不包括“http”；仅为“jappix.com”）。
对于 "Jabber BOSH Host"，您可以使用 "https://bind.jappix.com/"。
请注意，如果您不使用 jappix.com 作为您的 XMPP 帐户，则需要另一个 BOSH 服务器。
您可以在下面这些字段的“配置帮助”部分找到更多信息。
最后，您需要输入密码（还有一些可选选项，您可以进行选择）。
完成这些步骤后，请单击“发送”以保存条目。
现在，您应该在浏览器窗口的右下角找到聊天框。

如果您想手动添加联系人，可以点击“添加联系人”。