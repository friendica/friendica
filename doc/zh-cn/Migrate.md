迁移至新服务器安装步骤
===============

* [主页](help)

## 准备工作

### 新服务器
根据此处的说明 [Install] 配置新服务器，一直到创建了一个数据库为止。

### 提醒用户
提醒用户您的服务将在一段时间内中断。
为确保数据一致性，在迁移过程的某些步骤中需要离线服务器。

您还可以在迁移过程之前使用以下插件与用户进行沟通：
* blackout
* notifyall

### 存储
在根文件夹中运行 ``bin/console storage list`` 检查您的存储后端。
输出应该如下所示：
````
Sel | Name
-----------------------
     | Filesystem
 *   | Database
````
如果您不使用 ``Database`` 运行以下命令：
1.  运行 ``bin/console storage set Database`` 以激活数据库后端。
2.  运行 ``bin/console storage move`` 以开始移动存储的图像文件。

此过程可能需要很长时间，具体取决于您的存储大小和服务器容量。
在启动此过程之前，您可能需要使用以下命令检查存储中的文件数：``tree -if -I index.html /path/to/storage/``。

### 清理
在转移数据库之前，您可能需要进行清理；确保数据库条目的到期时间设置为合理值并通过管理员面板激活。
*Admin* > *Site* > *Performance* > 启用 "Clean up database"
调整这些设置后，将根据您配置的每日 cron 作业启动数据库清理流程。

要查看数据库的大小，请使用 ``mysql -p`` 登录 MySQL 并运行以下查询：
````
SELECT table_schema AS "Database", SUM(data_length + index_length) / 1024 / 1024 / 1024 AS "Size (GB)" FROM information_schema.TABLES GROUP BY table_schema;
````

你应该看到这样的输出：
````
+--------------------+----------------+
| Database           | Size (GB)      |
+--------------------+----------------+
| friendica_db       | 8.054092407227 |
| [..........]       | [...........]  |
+--------------------+----------------+
````

最后，你可能还想使用以下命令来优化你的数据库: ``mysqloptimize -p friendica-db``

### 离线操作
停止后台任务并将服务器置于维护模式。
1. 如果你已经设置了类似于这样的工作者 cron 任务 ``*/10 * * * * cd /var/www/friendica; /usr/bin/php bin/worker.php``，请运行 ``crontab -e`` 并注释掉此行。或者，如果你部署了一个 worker 守护进程，则禁用它。
2. 将服务器置于维护模式：``bin/console maintenance 1 "我们正在升级系统，请稍等片刻。"``

## 导出数据库
导出你的数据库：``mysqldump  -p friendica_db > friendica_db-$(date +%Y%m%d).sql`` 并可能压缩它。

## 转移到新服务器
将你的数据库和配置文件 ``config/local.config.php.copy`` 的副本转移到新的服务器安装中。

## 恢复数据库
在新的服务器上导入你的数据库：``mysql -p friendica_db < your-friendica_db-file.sql``

## 完成迁移

### 配置文件
将旧服务器的配置文件复制到 ``config/local.config.php``。
确保新创建的数据库凭据与配置文件中的设置相同；否则请相应地更新它们。

### 工作者的 cron 任务
设置所需的每日 cron 任务。
运行 ``crontab -e`` 并根据您的系统规格添加以下行
``*/10 * * * * cd /var/www/friendica; /usr/bin/php bin/worker.php`` 

### DNS 设置
通过将其指向新服务器，调整你的 DNS 记录。

## 故障排除
如果你无法登录到新迁移的 Friendica 安装，请检查你的 Web 服务器的错误和访问日志以及 mysql 日志是否有明显的问题。

如果仍然无法解决问题，则可能是安装[问题](Install)。
在这种情况下，你可以尝试在新服务器上完全新的 Friendica 安装，但使用不同的 FQDN 和 DNS 名称。
一旦你已经将其运行起来，将其下线并清除数据库和配置文件，然后尝试迁移到此安装。

