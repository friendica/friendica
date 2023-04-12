安装连接器 (Twitter/GNU Social)
==================================================

* [首页](help)

Friendica使用插件来提供到一些网络(如Twitter)的连接功能。

还有一个插件可以直接发布到已存在的GNU Social服务账户。你只需要这个插件来发布到已经存在的GNU Social账户，但不需要与一般的GNU Social成员通信。

这三个插件都需要在目标网络上创建账户。此外，您（或者通常是服务器管理员）需要获得API密钥以提供对Friendica服务器的身份验证访问。

站点配置
---

在使用之前，管理员必须安装插件。这是通过站点管理面板实现的。

每个连接器还需要来自您希望连接的服务的“API密钥”。一些插件允许您在站点管理员页面中输入此信息，而另一些插件可能需要您编辑配置文件(config/local.config.php)。获取这些密钥的方法因服务而异，但它们都需要在目标服务上拥有现有帐户。安装后，这些API密钥通常可以由所有站点会员共享。

下面是配置每个服务的详细信息(其中大部分信息直接来自插件源文件)：

Friendica的Twitter插件
---

* 作者：Tobias Diekershoff
* 电子邮件：tobias.diekershoff@gmx.net
* 许可证：3条款BSD许可证

### 配置

要使用此插件，您需要OAuth消费者密钥对(密钥和密钥)。您可以从[Twitter](https://twitter.com/apps)获取。

将你的Friendica站点注册为“客户端”应用程序，并获得“读写”访问权限。我们不需要“Twitter作为登录”。当您注册应用程序时，您将获得一对密钥和一个OAuth消费者密钥，以及一个用于您的应用程序/站点的秘密密钥。将此密钥对添加到config/local.config.php中：

	[twitter]
	consumerkey=在这里输入您的consumer_key
	consumersecret=在此处输入您的consumer_secret

之后，用户可以从“设置→连接器设置”中配置其Twitter帐户设置。

### 更多文档

查找作者的文档：[http://diekershoff.homeunix.net/redmine/wiki/friendikaplugin/Twitter_Plugin](http://diekershoff.homeunix.net/redmine/wiki/friendikaplugin/Twitter_Plugin)

Friendica的GNU Social插件
---

* 作者：Tobias Diekershoff
* 电子邮件：tobias.diekershoff@gmx.net
* 许可证：3条款BSD许可证

### 配置

当插件被激活时，用户必须获取以下内容才能连接到所选择的 GNU Social 账户。

* GNU Social API 的基本 URL，对于 quitter.se 来说，这是 https://quitter.se/api/
* OAuth 消费者密钥和密钥证书

要获取 OAuth 消费者密钥对，用户必须

1.询问她的 Friendica 管理员是否已经存在一对密钥对，或者
2.在 GNU Social 服务器上注册 Friendica 服务器作为客户端应用程序。

这可以在 GNU Social 服务器上的帐户设置下完成，在“设置->连接->注册 OAuth 客户端应用程序->注册新应用程序”下。

在注册 OAuth 客户端时，请记住以下内容：

* 应用程序名称必须在 GNU Social 网站上唯一，因此我们建议将名称设为“friendica-nnnn”，将“nnnn”替换为随机数字或您的网站名称。
* 没有回调 URL。
* 注册桌面客户端。
* 具有读写访问权限。
* 源 URL 应为您的 Friendica 服务器的 URL。

在应用程序所需的凭据存储在配置中之后，您必须实际连接您的 Friendica 帐户与 GNU Social。
这可以在“设置->连接器设置”页面完成。
按照“使用 GNU Social 登录”按钮的指示，允许访问，然后将安全代码复制到提供的框中。
Friendica 然后尝试从 API 中获取最终的 OAuth 凭据。

如果成功，插件设置将允许您选择将您的公共消息发布到您的 GNU Social 帐户（在您的主页或网络页面下“编辑器”状态下的小锁符号后面查看）。
