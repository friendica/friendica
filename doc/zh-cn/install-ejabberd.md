安装一个带有同步凭证的ejabberd
=================================================

* [主页](help)

[Ejabberd](https://www.ejabberd.im/)是一种使用XMPP作为通信协议的聊天服务器，您可以与大量客户端一起使用。结合"xmpp"插件，它可以用于为您的用户提供基于Web的聊天解决方案。

安装
------------

- 将其所有者更改为运行服务器的任何用户，例如ejabberd

        $ chown ejabberd:ejabberd /path/to/friendica/bin/auth_ejabberd.php

- 更改访问模式，以便仅对ejabberd用户可读并具有执行权限

        $ chmod 700 /path/to/friendica/bin/auth_ejabberd.php

- 编辑您的ejabberd.cfg文件，注释掉您的auth_method并添加：

        {auth_method, external}.
        {extauth_program, "/path/to/friendica/bin/auth_ejabberd.php"}.

- 禁用模块“mod_register”并禁用注册：

        {access, register, [{deny, all}]}.

- 启用BOSH：
  - 启用模块“mod_http_bind”
  - 编辑此行：

        {5280, ejabberd_http,    [captcha, http_poll, http_bind]}

  - 在您的站点的apache配置中添加此行：

        ProxyPass /http-bind http://127.0.0.1:5280/http-bind retry=0

- 重新启动ejabberd服务，您应该能够使用friendica凭证登录

其他提示
-----------
- 如果用户在昵称中有空格或@符号，用户必须替换这些字符：
  - " "(空格)将其替换为"%20"
  - "@"将其替换为"(a)"