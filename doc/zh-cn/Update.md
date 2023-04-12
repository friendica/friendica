# 更新 Friendica

## 使用 Friendica 压缩包进行更新

如果你在路径 ``path/to/friendica`` 下安装了 Friendica，可以按照以下步骤进行更新：

1. 将新的 Friendica 压缩包解压到 ``path/to/friendica_new`` 目录下。
2. 从 ``path/to/friendica`` 目录复制以下项到 ``path/to/friendica_new`` 目录下：
   * ``config/local.config.php``
   * ``proxy/``
   如果以下项目在 Friendica 路径下，请进行相应复制：
   * 存储文件夹，该设置在 **管理员 -> 站点 -> 文件上传 -> 存储基本路径** 中
   * 物品缓存，该设置在 **管理员 -> 站点 -> 性能 -> 物品缓存路径** 中
   * 临时文件夹，该设置在 **管理员 -> 站点 -> 高级选项 -> 临时路径** 中
3. 把目录 ``path/to/friendica`` 重命名为 ``path/to/friendica_old``。
4. 把目录 ``path/to/friendica_new`` 重命名为 ``path/to/friendica``。
5. 检查你的站点。注意：可能会进入维护模式以更新数据库结构。
6. 如果一切正常，只需删除 ``path/to/friendica_old`` 目录即可。

要更新插件，请简单地删除 ``path/to/friendica/addon`` 目录，然后将提供的归档文件替换掉。

## 使用 Git 进行更新

你可以随时获取最新的更改：

    cd path/to/friendica
    git pull
    bin/composer.phar install --no-dev

插件树必须单独更新，如下所示：

    cd path/to/friendica/addon
    git pull

对于两个存储库:

默认分支是 ``stable`` 分支，它是 Friendica 的稳定版本。它每年固定时间更新约四次。

如果想使用和测试最新的开发代码，请检出 ``develop`` 分支。
在每次发布之前，新功能和修复将从“develop”合并到“stable”的发布候选期之后。

警告: ``develop`` 分支不稳定，平均每月会中断一次，最多持续 24 小时，直到提交和合并补丁。
如果选择 ``develop`` 分支，请确保经常对其进行拉取。
### Friendica 升级前需要考虑的事项

#### MySQL >= 5.7.4

从 MySQL 版本 5.7.4 开始，ALTER TABLE 语句中的 IGNORE 关键字被忽略。
如果在 Friendica 表结构中添加了一个 UNIQUE 索引，这将阻止自动表去重。
如果在创建 UNIQUE 索引时 DB 更新失败，则在再次尝试更新之前，请确保手动进行表去重。

#### 手动去重

有两种主要的方法，一种是手动删除重复项，另一种是重新创建表。
如果重复项不太多，手动删除通常比较快。
要手动删除重复项，您需要知道 `database.sql` 中可用的 UNIQUE 索引列。

```SQL
SELECT GROUP_CONCAT(id), <index columns>, count(*) as count FROM users
GROUP BY <index columns> HAVING count >= 2;

/* 删除或合并上面查询的重复项 */;
```

如果要处理的行数太多，可以创建一个与具有重复项的表具有相同结构的新表，并使用 INSERT IGNORE 插入现有内容。
要重新创建表，您需要知道在 `database.sql` 中可用的表结构。

```SQL
CREATE TABLE <table_name>_new <rest of the CREATE TABLE>;
INSERT IGNORE INTO <table_name>_new SELECT * FROM <table_name>;
DROP TABLE <table_name>;
RENAME TABLE <table_name>_new TO <table_name>;
```

这种方法总体上较慢，但适用于大量的重复项。

### 升级后解决可能出现的数据库问题

#### 外键

一些更新包括外键的使用，这会导致与之前的版本产生问题。以前的版本可能会将错误的数据插入到表中，从而导致以下错误：

```
在数据库更新期间发生错误1452：
无法添加或更新子行：外键约束失败（`friendica` .`#sql-10ea6_5a6d`，CONSTRAINT` #sql-10ea6_5a6d_ibfk_1` FOREIGN KEY（`contact-id`）REFERENCES` contact`（ `id`）))
ALTER TABLE `thread` ADD FOREIGN KEY (`iid`) REFERENCES `item` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE; 
```

目前已知的所有可能出现问题的项的修复方式如下。

```SQL
DELETE FROM `item` WHERE `owner-id` NOT IN (SELECT `id` FROM `contact`);
DELETE FROM `item` WHERE `contact-id` NOT IN (SELECT `id` FROM `contact`);
DELETE FROM `notify` WHERE `uri-id` NOT IN (SELECT `id` FROM `item-uri`);
DELETE FROM `photo` WHERE `contact-id` NOT IN (SELECT `id` FROM `contact`);
DELETE FROM `thread` WHERE `iid` NOT IN (SELECT `id` FROM `item`);
DELETE FROM `item` WHERE `author-id` NOT IN (SELECT `id` FROM `contact`);
DELETE FROM `diaspora-interaction` WHERE `uri-id` NOT IN (SELECT `id` FROM `item-uri`);
```

以上内容截至当前，整理自问题#9746、#9753和# 9878。
