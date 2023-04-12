常见问题解答（管理员）- FAQ
==============

* [主页](help)

* **[我能用同一个代码实例配置多个域名吗？](help/FAQ-admin#multiple)**
* **[我能在哪里找到friendica, 插件和主题的源代码?](help/FAQ-admin#sources)**
* **[我已经更改了我的电子邮件地址，现在管理员面板消失了？](help/FAQ-admin#adminaccount1)**
* **[节点可以有多个管理员吗？](help/FAQ-admin#adminaccount2)**
* **[数据库结构似乎没有更新。我该怎么办？](help/FAQ-admin#dbupdate)**


<a name="multiple"></a>
### 我能用同一个代码实例配置多个域名吗？

不能了。从Friendica 3.3开始，这个功能不再支持。

<a name="sources"></a>
### 我能在哪里找到friendica，插件和主题的源代码？

你可以在[这里](https://github.com/friendica/friendica)找到主要的仓库。在那里，你总是能够找到当前稳定版本的friendica。

插件列出在[本页](https://github.com/friendica/friendica-addons)上。

如果你正在寻找新的主题，可以在[github.com/bkil/friendica-themes](https://github.com/bkil/friendica-themes)找到它们。

<a name="adminaccount1"></a>
### 我已经更改了我的电子邮件地址，现在管理员面板消失了？

请查看您的<tt>config/local.config.php</tt>文件，并在那里修复您的电子邮件地址。

<a name="adminaccount2"></a>
### 节点可以有多个管理员吗？

可以。你只需要在<tt>config/local.config.php</tt>文件中列出多个电子邮件地址，这些电子邮件需要用逗号隔开。

<a name="dbupdate">
### 数据库结构似乎没有更新。我该怎么办？

请在[DB更新](/admin/dbsync/)下查看管理员面板，并按照 *check database structure* 的链接进行操作。这将启动一个后台进程，检查结构是否符合当前定义。

你可以在Friendica安装的基本目录中通过运行以下命令手动执行结构更新：

    bin/console dbstructure update

如果出现任何错误，请联系[support forum](https://forum.friendi.ca/profile/helpers)。