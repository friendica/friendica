如何提高 Friendica 站点的性能
==============

* [主页](help)

如果您需要以下说明的澄清或其他任何帮助，请随意在 [Friendica 支持论坛](https://forum.friendi.ca/profile/helpers) 上发帖求助。

系统配置
--------

请进入您系统上的 `/admin/site/`，并更改以下值：

    将“JPEG 图像质量”设置为 50。

该值减少了从服务器发送到客户端的数据。50 是一个不会太影响图片质量的值。

    将“OStatus 对话完成间隔”设置为“从不”。

如果您有很多 OStatus 联系人，则完成对话可能需要一些时间。由于您将错过在 OStatus 主题中的若干评论，因此您可以考虑选择选项“在发布到达时”。

    启用“使用 MySQL 全文引擎”

在使用 MyISAM（默认）或 MariaDB 10 上的 InnoDB 时，这样可以加快搜索速度。

插件
--------

启用以下插件：

    rendertime

### rendertime

该插件不会加速系统。
它有助于分析您的瓶颈。

启用后，您会在每个页面底部看到一些值。
它们显示了您的性能问题。

    性能：数据库：0.244、网络：0.002、渲染：0.044、解析器：0.001、I/O：0.021、其他：0.237、总计：0.548

    数据库：所有数据库查询所需的时间
    网络：从外部网站获取内容所需的时间
    渲染：主题渲染时间
    解析器：BBCode 解析器创建输出所需的时间
    I/O：本地文件访问时间
    其他：其他所有 :)
    总计：以上所有值的总和

Apache Web 服务器
--------

推荐使用以下 Apache 模块：

### Cache-Control

该模块告诉客户端缓存静态文件的内容，以便它们不会在每个请求中获取。
通过将 `a2enmod expires` 作为 root 进行启用 "mod_expires" 模块。
请将以下行添加到您的站点配置中的 “目录” 上下文中。

	ExpiresActive on ExpiresDefault "access plus 1 week"

另请参见 Apache [2.2](http://httpd.apache.org/docs/2.2/mod/mod_expires.html) / [2.4](https://httpd.apache.org/docs/2.4/mod/mod_expires.html) 文档。

### 压缩内容

该模块压缩 Web 服务器与客户端之间的流量。
通过将 `a2enmod deflate` 作为 root 进行启用 "mod_deflate" 模块。

另请参见 Apache [2.2](http://httpd.apache.org/docs/2.2/mod/mod_deflate.html) / [2.4](https://httpd.apache.org/docs/2.4/mod/mod_deflate.html) 文档。

PHP
--------

### FCGI

在使用 Apache 时，请考虑使用 FCGI。在基于 Debian 的发行版中，您需要安装名为“php5-cgi”和“libapache2-mod-fcgid”的软件包。

有关如何设置基于 FCGI 的系统的更详细解释，请参考外部文档。

### 数据库

有一些脚本像 [tuning-primer.sh](http://www.day32.com/MySQL/) 和 [mysqltuner.pl](http://mysqltuner.pl) 可以分析您的数据库服务器并提供可更改的值的提示。

请启用慢查询日志。这有助于找到性能问题。