管理工具
===========

* [首页](help)

Friendica 工具
---------------

Friendica 内置了一个命令行控制台，你可以在 *bin* 目录中找到它。
控制台提供以下命令：

* cache:                  管理节点缓存
* config:                 编辑站点配置
* createdoxygen:          生成 Doxygen 头文件
* dbstructure:            进行数据库更新
* docbloxerrorchecker:    检查 DocBlox 错误的文件树
* extract:                为 Friendica 项目生成翻译字符串文件（已弃用）
* globalcommunityblock:   阻止远程个人资料与此节点进行交互
* globalcommunitysilence: 静音远程个人资料以避免出现在全局社区页面上
* archivecontact:         存档联系人，当您知道该联系人不再存在时
* help:                   显示有关命令的帮助信息，例如 (bin/console help config)
* autoinstall:            根据 htconfig.php 中的值启动 Friendica 的自动安装
* maintenance:            设置此节点的维护模式
* newpassword:            为给定用户设置新密码
* php2po:                 从 strings.php 文件生成 messages.po 文件
* po2php:                 从 messages.po 文件生成 strings.php 文件
* typo:                   检查 Friendica 文件中的语法错误
* postupdate:             执行待处理的后续更新脚本（可能需要几天时间）
* storage:                管理存储后端
* relay:                  管理 ActivityPub 中继服务器

请在服务器的命令行界面上咨询 *bin/console help* 以获取有关命令的详细信息。

第三方工具
---------------

除了 Friendica 包含的工具之外，一些第三方工具可以让您的管理员日子更轻松。

### Fail2ban

Fail2ban 是一个入侵预防框架（[参见维基百科](https://en.wikipedia.org/wiki/Fail2ban)），可以用来在特定条件下禁止访问服务器，比如登录失败3次，一段时间内不能再次尝试登录。

以下配置是使用Debian提供的，由Steffen K9提供。您需要在*jail.local*文件中调整*logpath*和*bantime*（值以秒为单位）。

在*/etc/fail2ban/jail.local*中创建Friendica部分：

```
[friendica]
enabled = true
findtime = 300
bantime  = 900
filter = friendica
port = http,https
logpath = /var/log/friendica.log
logencoding = utf-8
```

并在*/etc/fail2ban/filter.d/friendica.conf*中创建一个过滤器定义：

```
[Definition]
failregex = ^.*authenticate\: failed login attempt.*\"ip\"\:\"<HOST>\".*$
ignoreregex =
```

此外，您还需定义在禁止访问之前允许多少次登录失败。这可以在全局配置或每个 jail 中单独完成。您应该告知用户在多少次登录失败后会禁止他们的访问。否则，如果次数太低，则会收到许多有关服务器无法正常运行的报告。

### 日志轮换

如果您已经激活了Friendica的日志记录，请注意它们可能会增长到相当大的大小。为了控制它们，您应该将它们添加到自动 [日志轮换](https://en.wikipedia.org/wiki/Log_rotation)中，例如使用*logrotate*命令。

在*/etc/logrotate.d/*中添加一个名为*friendica*的文件，其中包含配置。以下内容将每天压缩*/var/log/friendica*（假设这是日志文件的位置），并保留2天的后备副本。

	/var/log/friendica.log {
		compress
		daily
		rotate 2
	}
