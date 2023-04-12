# Friendica安装

我们已经尽力确保Friendica能够在普通的主机平台上运行，例如用于托管Wordpress博客和Drupal网站的平台。
我们提供手动和自动安装程序。
但是请注意，Friendica不仅仅是一个简单的Web应用程序。

它是一个复杂的通信系统，更类似于电子邮件服务器而不是Web服务器。
为了可靠性和性能，消息会在后台传送，并在站点宕机时排队等待稍后交付。
这种功能需要主机系统比典型的博客要求更高一些。

并非每个PHP/MySQL主机提供商都能支持Friendica。
很多可以。

但是，请在安装之前查阅[要求](#Requirements)并与您的主机提供商确认这些要求。

## 支持
如果您遇到安装问题，请通过[helper](http://forum.friendi.ca/profile/helpers)或[developer](https://forum.friendi.ca/profile/developers)论坛或[提交问题](https://github.com/friendica/friendica/issues)告诉我们。

请尽可能清楚地描述您的操作环境，并尽可能提供有关任何错误消息的详细信息，以便我们能够防止类似的问题在未来发生。
由于存在各种操作系统和PHP平台，我们可能只能有限地调试您的PHP安装或获取缺少的模块 - 但我们将尽力解决任何一般的代码问题。

## 先决条件

* 为您的服务器选择一个域名或子域名。请谨慎考虑此问题。虽然支持安装后更改，但仍可能出现问题。
* 在您的域上设置HTTPS。

### 要求

* Apache启用mod-rewrite并"Options All"，以便您可以使用本地`.htaccess`文件
* PHP 7.3+（PHP8尚未完全支持）
  * register_argc_argv在php.ini文件中设置为true的PHP*命令行*访问
  * Curl，GD，GMP，PDO，mbstrings，MySQLi，hash，xml，zip和OpenSSL扩展
  * 必须激活PHP的POSIX模块（例如[RHEL，CentOS](http://www.bigsoft.co.uk/blog/index.php/2014/12/08/posix-php-commands-not-working-under-centos-7)已将其禁用）
  * 某种形式的电子邮件服务器或电子邮件网关，使得PHP mail（）起作用。
    如果您无法设置自己的电子邮件服务器，则可以使用[phpmailer](https://github.com/friendica/friendica-addons/tree/develop/phpmailer)附加组件并使用远程SMTP服务器。
* 支持InnoDB和Barracuda的MySQL（我们建议使用MariaDB服务器，因为所有开发都是在这些服务器上进行的，但是像MySQL或Percona Server等替代品也可能可以使用）
* 使用cron（Linux/Mac）或Scheduled Tasks（Windows）安排作业的能力。
* 推荐将Friendica安装到顶级域或子域中（URL中没有目录/路径组件）。目录路径不够方便使用，也没有得到充分测试。如果您希望与Diaspora网络通信，则必须这样做。

**如果您的主机提供商不允许Unix shell访问，则可能难以使所有内容正常工作。**

有关替代服务器配置（例如Nginx服务器和MariaDB数据库引擎），请参阅[Friendica wiki](https://github.com/friendica/friendica/wiki)。

### 可选项

* PHP ImageMagick 扩展（php-imagick）用于支持动画 GIF。

## 安装过程

### 其他安装方法

本指南将向您介绍 Friendica 的手动安装过程。
如果这不适合您，您可能会对以下内容感兴趣：

* [Friendica Docker 镜像](https://github.com/friendica/docker) 或
* 如何使用 [YunoHost 安装 Friendica](https://github.com/YunoHost-Apps/friendica_ynh)。

### 获取 Friendica

从[项目主页](https://friendi.ca/resources/download-files/)下载 Friendica 核心和插件的完整存档。
请确保 Friendica 存档和插件的版本匹配。
将 Friendica 文件解压缩到 Web 服务器文档区域的根目录中。

如果将目录树复制到 Web 服务器，请确保您还复制了 `.htaccess-dist` - 因为“点”文件通常是隐藏的，通常不会被复制。

**或者**

克隆 [friendica/friendica GitHub 仓库](https://github.com/friendica/friendica) 并导入依赖项。
这可以使软件更新更加容易。

将仓库克隆到目录“mywebsite”中的 Linux 命令如下：

    git clone https://github.com/friendica/friendica.git -b stable mywebsite
    cd mywebsite
    bin/composer.phar install --no-dev

确保文件夹 *view/smarty3* 存在并且可以被 Web 服务器用户写入，即 *www-data*

    mkdir -p view/smarty3
    chown www-data:www-data view/smarty3
    chmod 775 view/smarty3

通过进入您的网站文件夹获取插件。

    cd mywebsite

克隆插件库（分开）：

    git clone https://github.com/friendica/friendica-addons.git -b stable addon

如果您想使用 Friendica 的开发版，则可以在仓库中切换到 develop 分支运行以下命令

    git checkout develop
    bin/composer.phar install
    cd addon
    git checkout develop

**请注意，develop 分支不稳定，可能会随时破坏您的 Friendica 节点。**
在更新之前，应进行最近的备份。
如果遇到错误，请告诉我们。

### 创建一个数据库

创建一个空的数据库，并注意访问详细信息（主机名、用户名、密码和数据库名称）。
生成一个强密码，然后输入以下命令进入 mysql：

    mysql
    
然后使用下面的脚本，使用你刚生成的密码：

    CREATE DATABASE friendicadb;
    CREATE USER 'friendica'@'localhost' IDENTIFIED BY '<<在此处输入你的 mysql 密码>>';
    GRANT ALL ON friendicadb.* TO 'friendica'@'localhost';
    FLUSH PRIVILEGES;
    EXIT;

Friendica 需要在自己的数据库中创建和删除字段和表的权限。

如果在 MySQL 5.7.17 或更新版本上运行，请检查[故障排除](#Troubleshooting)部分。

### 选项 A：运行安装程序

在将 Web 浏览器指向新站点之前，您需要将 `.htaccess-dist` 复制为 Apache 安装的 `.htaccess`。
按照说明进行操作。
请注意任何错误消息并在继续之前进行更正。

如果需要为与数据库的连接指定端口，可以在数据库的主机名设置中进行设置。

如果手动安装因任何原因失败，请检查以下内容：

* `config/local.config.php` 是否存在？如果不存在，请编辑 `config/local-sample.config.php` 并更改系统设置。
* 将其重命名为 `config/local.config.php`。
* 数据库是否已填充？如果没有，请使用 phpmyadmin 或 mysql 命令行导入 `database.sql` 的内容。

此时再次访问您的网站，并注册您的个人帐户。
注册错误应该都是自动恢复的。
如果此时出现任何 *严重* 故障，则通常表示数据库未正确安装。
您可能希望将 `config/local.config.php` 移动/重命名为其他名称并清空（称为“删除”）数据库表，以便您可以重新开始。

### 选项 B：运行自动安装脚本

您有以下选项以自动安装 Friendica：
- 创建预准备的配置文件（例如 `prepared.config.php`）
- 使用环境变量（例如 `MYSQL_HOST`）
- 使用选项（例如 `--dbhost <host>`）

您可以结合使用环境变量和选项，但请注意，选项优先于环境变量。

安装过程中若需要更多信息，请使用此命令行选项：

    bin/console autoinstall -v

如果您希望包括所有可选的检查，请使用 `-a`，如下所示：

    bin/console autoinstall -a
    
如果自动安装因任何原因失败，请检查以下项：

* `config/local.config.php` 是否已存在？如果是，请勿自动安装。
* `config/local.config.php` 中的选项是否正确？如果不正确，请直接编辑这些选项。
* 是否创建了空的 MySQL 数据库？如果没有，请创建。

### B.1: 配置文件

你可以使用一个准备好的配置文件，例如[local-sample.config.php](/config/local-sample.config.php)。

转到 Friendica 的主目录并执行以下命令：

    bin/console autoinstall -f <prepared.config.php>

### B.2: 环境变量

有两种类型的环境变量。
- 在正常模式下也可以使用的那些（目前只有**数据库凭据**）
- 只能在安装期间使用的那些（因为Friendica通常会忽略它）

您也可以在安装期间使用这些选项并跳过一些环境变量。

**数据库凭据**

如果您在安装期间不使用 `--savedb` 选项，则 **不会** 将 DB 凭据保存在 `config/local.config.php` 中。

-	`MYSQL_HOST` mysql/mariadb 数据库的主机名
-	`MYSQL_PORT` mysql/mariadb 数据库的端口
-	`MYSQL_USERNAME` mysql 数据库登录的用户名（用于 mysql）
-	`MYSQL_USER` mariadb 数据库登录的用户名（用于 mariadb）
-	`MYSQL_PASSWORD`mysql/mariadb 数据库登录的密码
-	`MYSQL_DATABASE`mysql/mariadb 数据库的名称

**Friendica 设置**

这些变量在 Friendica 运行时不会被使用。
它们相反会被保存在 `config/local.config.php` 中。

-	`FRIENDICA_URL_PATH` Friendica 的 URL 路径（例如 '/friendica'）
-	`FRIENDICA_PHP_PATH` PHP 二进制文件的路径
-	`FRIENDICA_ADMIN_MAIL` Friendica 的管理员电子邮件地址（此电子邮件将用于管理员访问）
-	`FRIENDICA_TZ` Friendica 的时区
-	`FRIENDICA_LANG` Friendica 的语言

转到 Friendica 的主目录并执行以下命令：

    bin/console autoinstall [--savedb]

### B.3: 执行选项

所有选项都将保存在 `config/local.config.php` 中，并将覆盖相关的环境变量。

-	`-H|--dbhost <host>` mysql/mariadb 数据库的主机名（环境变量 `MYSQL_HOST`）
-	`-p|--dbport <port>` mysql/mariadb 数据库的端口（环境变量 `MYSQL_PORT`）
-	`-U|--dbuser <username>` mysql/mariadb 数据库登录的用户名（环境变量 `MYSQL_USER` 或 `MYSQL_USERNAME`）
-	`-P|--dbpass <password>` mysql/mariadb 数据库登录的密码（环境变量 `MYSQL_PASSWORD`）
-	`-d|--dbdata <database>` mysql/mariadb 数据库的名称（环境变量 `MYSQL_DATABASE`）
-	`-u|--urlpath <url_path>` Friendica 的 URL 路径，例如 '/friendica' （环境变量 `FRIENDICA_URL_PATH`）
-	`-b|--phppath <php_path>` PHP 二进制文件的路径（环境变量 `FRIENDICA_PHP_PATH`）
-	`-A|--admin <mail>` Friendica 的管理员电子邮件地址（环境变量 `FRIENDICA_ADMIN_MAIL`）
-	`-T|--tz <timezone>` Friendica 的时区（环境变量 `FRIENDICA_TZ`）
-	`-L|--lang <language>` Friendica 的语言（环境变量 `FRIENDICA_LANG`）

转到 Friendica 的主目录并执行以下命令：

    bin/console autoinstall [选项]

### 准备 .htaccess 文件

将 `.htaccess-dist` 复制到 `.htaccess`（在 Windows 下要小心），以便再次使用 mod-rewrite。如果您将 Friendica 安装到子目录中，例如 */friendica/*，请相应地设置 `RewriteBase`。

例如：

    cp .htacces-dist .htaccess

*注意*：不要重命名 `.htaccess-dist` 文件，因为它被 GIT 跟踪，重命名会导致工作目录出错。

### 验证“host-meta”页面是否正常工作

Friendica应该会自动响应 */.well-known/* 重写路径下的重要地址。
一个关键的 URL 是，例如：https://example.com/.well-known/host-meta   
它必须对公众可见，并且必须自动定制为您站点的 XML 文件。

如果该 URL 不起作用，则可能是其他软件正在使用 /.well-known/ 路径。
其他症状可能包括在管理设置中显示“主机元数据在您的系统上无法访问。
这是一个严重的配置问题，会阻止服务器之间的通信。”的错误消息。
与 host-meta 相关的另一个常见错误是“无效的个人资料 URL”。

检查是否存在没有随 Friendica 一起安装的 `.well-known` 目录。
首选的配置是删除该目录，但这并不总是可能的。
如果有任何 /.well-known/.htaccess 文件，则可能会影响此 Friendica 核心要求。
您应该从该文件中删除任何 RewriteRules，或者如果适当的话则删除整个文件。
如果您没有默认情况下获得写权限，则可能需要 chmod /.well-known/.htaccess 文件。

## 注册管理员帐户

此时请再次访问您的网站，并使用与 `config.admin_email` 配置值相同的电子邮件注册个人帐户。
所有注册错误都应该可以自动恢复。

如果此时出现任何 *关键* 错误，则通常表示数据库没有正确安装。
您可能希望将 `config/local.config.php` 更名为其他名称，并删除所有数据库表，以便重新开始。

## 安装后配置

### (必需) 后台任务

设置一个 cron 作业或计划任务，每 5-10 分钟运行一次 worker，以执行后台处理。
例如：

    cd /base/directory; /path/to/php bin/worker.php

根据您的情况更改 “/base/directory” 和 “/path/to/php”。

#### worker 的 cron 作业

如果您使用的是 Linux 服务器，请运行 "crontab -e" 并添加如下所示的一行，替换为您唯一的路径和设置：

    */10 * * * * cd /home/myname/mywebsite; /usr/bin/php bin/worker.php

您通常可以通过执行 "which php" 查找 PHP 的位置。
如果在此步骤中出现问题，请联系您的托管提供商寻求帮助。
如果无法执行此步骤，则 Friendica 将无法正常工作。

如果无法设置 cron 作业，则请在管理界面中激活“前端工作者”。

一旦您安装了 Friendica 并作为该过程的一部分创建了管理员帐户，则可以访问安装的管理面板，并大多数情况下从那里进行整个服务器的配置。

## 定时任务的替代方案：守护进程

否则，您需要在远程服务器上使用命令行，并使用以下命令启动 Friendica 守护进程（后台任务）：

```
cd /path/to/friendica; php bin/daemon.php start
```

启动后，您可以使用以下命令检查守护进程的状态：

```
cd /path/to/friendica; php bin/daemon.php status
```

在服务器重新启动或任何其他故障之后，需要重新启动守护进程。
这可以通过 cronjob 实现。

### （推荐）日志记录和日志轮换

此时建议您设置日志记录和日志轮换。
要做到这一点，请访问[设置](help/Settings)，并搜索“日志”部分以获取更多信息。

### （推荐）设置备份计划

坏事将发生。
无论是硬件故障、损坏的数据库或您能想到的任何其他问题。
因此，一旦 Friendica 节点的安装完成，您应该制定自己的备份计划。

最重要的文件是 `config/local.config.php` 文件。
由于它存储了所有数据，因此您还应该准备一个最近的 Friendica 数据库转储，以防需要恢复节点。

### （可选）反向代理和 HTTPS

Friendica 寻找一些众所周知的 HTTP 标头，指示反向代理终止 HTTPS 连接。
虽然 RFC 7239 标准指定使用 `Forwarded` 标头。

```
Forwarded: for=192.0.2.1; proto=https; by=192.0.2.2
```

Friendica 还支持一些常用的非标准标头。

```
X-Forwarded-Proto: https

Front-End-Https: on

X-Forwarded-Ssl: on
```

但是，如果要配置新服务器，最好使用标准方法。

## 故障排除

### “系统当前不可用。请稍后再试”

检查您的数据库设置。
通常这意味着您的数据库无法打开或访问。
如果数据库驻留在同一台机器上，请检查数据库服务器名称是否为“localhost”。

### 500 内部错误

这可能是由于您使用的 Apache 版本不支持我们的一个 Apache 指令导致的。请检查您的 apache 服务器日志。

如果您使用的是 Windows 服务器，则可以从 `.htaccess` 文件中删除行 "Options -Indexes"，因为已知这会引起问题。同时还要检查您的文件权限。通常情况下，您的网站和所有内容都应该是全局可读的。

很可能您的 Web 服务器在其错误日志文件中报告了问题的来源。请查看这些系统错误日志以确定问题的原因。通常情况下，这需要通过您的托管提供商或（如果是自托管）您的 Web 服务器配置来解决。

### 400 和 4xx "文件未找到" 错误

首先请检查您的文件权限。通常情况下，您的网站和所有内容都应该是全局可读的。

确保已安装并启用mod-rewrite，并且正在使用您的 `.htaccess` 文件。为了验证后者，在 Friendica 的顶级目录中创建一个名为 `test.out` 的包含单词“test”的文件，将其设置为全局可读，然后将您的 Web 浏览器指向 http://yoursitenamehere.com/test.out 。这个文件应该被阻塞，您应该收到一个权限被拒绝的消息。

如果您看到单词 "test"，则说明您的 Apache 配置不允许使用您的 `.htaccess` 文件（此文件中有一些规则，用于阻止访问以 .out 结尾的任何文件，因为这些文件通常用于系统日志）。

确保 `.htaccess` 文件存在并且可被所有人读取，然后查找 Apache 服务器配置中 "AllowOverride None" 是否存在。必须将其更改为 "AllowOverride All"。

如果您没有看到单词 "test"，则说明您的 `.htaccess` 工作正常，但很可能是 mod-rewrite 没有安装在您的 Web 服务器上或者未能正常工作。

在大多数 Linux 发行版中：

	% a2enmod rewrite
	% /etc/init.d/apache2 restart

如果您需要更改这两个设置但不知道如何进行操作，请咨询您的托管提供商、您特定 Linux 发行版的专家或（如果使用 Windows）您的 Apache 服务器软件提供商。网络上有很多帮助。搜索与您的操作系统分发版或 Apache 包（如果使用 Windows）的名称相结合的 "mod-rewrite"。

### 由于权限问题无法写入文件`config/local.config.php`

创建一个空的 `config/local.config.php` 文件，并应用全局写入权限。

在 Linux 上执行：

	% touch config/local.config.php
	% chmod 664 config/local.config.php

然后重试安装。一旦已创建数据库，接下来需要执行以下步骤。

******* 这很重要 *********

	% chmod 644 config/local.config.php

### Suhosin 问题

某些以 "suhosin" 安全为基础的配置被设置为无法运行外部进程。 Friendica 需要此功能。以下是我们的一位成员提供的一些注意事项。

> 在我的服务器上，我使用 php 保护系统 Suhosin [http://www.hardened-php.net/suhosin/]。它的其中一个功能是阻止某些函数，例如在 `/etc/php5/conf.d/suhosin.ini` 中配置的 `proc_open`:
>
>     suhosin.executor.func.blacklist = proc_open, ...
>
> 对于像 Friendica 这样真正需要这些函数的网站，它们可以通过修改 `/etc/apache2/sites-available/friendica` 来启用：
>
> 	<Directory /var/www/friendica/>
> 	  php_admin_value suhosin.executor.func.blacklist none
> 	  php_admin_value suhosin.executor.eval.blacklist none
> 	</Directory>
> 
> 如果通过浏览器访问 Friendica，则会为 Friendica 启用所有函数，但是对于通过 php 命令行调用的 cron 作业，则不会启用。 我尝试通过类似以下命令来启用 cron：
>
> 	*/10 * * * * cd /var/www/friendica/friendica/ && sudo -u www-data /usr/bin/php \
>       -d suhosin.executor.func.blacklist=none \
>       -d suhosin.executor.eval.blacklist=none -f bin/worker.php
>
> 对于简单的测试用例，这可以正常工作，但是 friendica-cron 仍然会因致命错误失败：
>
> 	suhosin[22962]: ALERT - function within blacklist called: proc_open()
>     (attacker 'REMOTE_ADDR not set', file '/var/www/friendica/friendica/boot.php',
>     line 1341)
>
> 过了一段时间我发现，`bin/worker.php`通过 `proc_open` 调用其他 PHP 脚本。这些脚本本身也使用 `proc_open`，并不会生效，因为它们没有使用 `-d suhosin.executor.func.blacklist=none`。
>
> 因此，简单的解决方法是将正确的参数放入 `config/local.config.php`：
>
> 	'config' => [
> 		//Location of PHP command line processor
> 		'php_path' => '/usr/bin/php -d suhosin.executor.func.blacklist=none \
>               -d suhosin.executor.eval.blacklist=none',
> 	],
>
> 当你注意到友好网站 cron 使用 `proc_open` 执行还依赖于使用 `proc_open` 的 PHP 脚本时，这是显而易见的，但我花了很长时间才找到这个问题。我希望这能为使用带有函数黑名单的 Suhosin 的其他人节省一些时间。

### MySQL 5.7.17 或更新版本上无法创建所有 mysql 表

如果安装程序无法创建所有数据库表和/或从命令行手动创建失败，并显示以下错误：

	ERROR 1067 (42000) at line XX: Invalid default value for 'created'

您需要调整 my.cnf 并在 [mysqld] 部分下添加以下设置：

	sql_mode = '';

之后，重新启动 mysql 并重试。

## 你的后台任务很少或从未运行

Friendica 被编码成始终友好。它会检查主机机器是否足够空闲，如果它过载的话，那么它会间歇性地拒绝处理工作队列。

这些检查源于单用户单核心计算机时代，涉及到一些阈值，你应该根据你拥有的独占 CPU 核心数进行调整。更多信息请参见此问题:

* https://github.com/friendica/friendica/issues/10131

如果你想要与他人友好相处，并且正在使用共享网络托管 PaaS 提供商，特别是在免费层中，你需要将 `maxloadavg` 设置为峰值期间 `/proc/loadavg` 最大值的两倍。

如果你拥有整个（虚拟）机器，例如 IaaS VPS，请将其设置为常见值的数量级更高，例如 1000。

你应该根据入口进程的数量在你的 Web 服务器配置中设置限制，以限制你的 PHP 进程的并发内存使用情况。请参阅你的服务器中的 `RLimitMEM`、`RLimitCPU`、`RLimitNPROC`、`StartServers`、`ServerLimit`、`MaxRequestsPerChild`、`pm.max_children`、`pm.start_servers` 和相关选项。

上传小型图像文件出现错误

你尝试上传大小为 100kB 的图像，但失败了。

如果你正在使用文件系统存储后端，则可能没有正确设置所有权或文件模式。

将后端更改为数据库。如果这解决了问题，那就是需要修复的问题。

上传大型文件出现错误

如果文件太大，例如是视频，则在浏览器的网络检查器中可能会发现 `413 Request Entity Too Large` 或 `500 Internal Error`。

首先尝试上传非常小的文件，最多达到 100kB。如果成功，你将需要在多个位置增加限制，包括你正在使用的任何 Web 代理。

在你的 PHP ini 中：

* `upload_max_filesize`：默认为 2MB
* `post_max_size`：默认为 8MB，必须大于 `upload_max_filesize`
* `memory_limit`：默认为 128MB，必须大于 `post_max_size`

你应该通过在“管理”面板概述的末尾检查 Web 界面来验证是否在正确的文件中更改它们。

对于 Apache2：

* `LimitRequestBody`：默认为无限制
* `SSLRenegBufferSize`：默认为 128kB，仅当站点使用 TLS 时，并且也许仅在使用 `SSLVerifyClient` 或 `SSLVerifyDepth` 时

对于 nginx：

* `client_max_body_size`：默认为 1MB

如果你正在使用数据库后端进行存储，请在你的 SQL 配置中增加此选项：

* `max_allowed_packet`：默认为 32MB

如果你使用 ModSecurity WAF：

* `SecRequestBodyLimit`：默认为 12MB
* `SecRequestBodyNoFilesLimit`：默认为 128kB，不应适用于 Friendica