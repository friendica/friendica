配置 config/local.config.php 
==========================================

* [首页](help)

Friendica 的配置有两个地方：PHP 数组配置文件和 `config` 数据库表中。
数据库配置值会覆盖同名的文件配置值。

## 文件配置

文件配置的配置格式是一个从 PHP 文件返回的数组。
这可以防止您的 Web 服务器显示您的私有配置。它解释了配置文件并不显示任何内容。

典型的配置文件如下所示：

```php
<?php

/*
 * 评论块
 */

return [
	'section1' => [
		// 注释行
		'key' => 'value',
	],
	'section2' => [
		'array' => ['value0', 'value1', 'value2'],
	],
];
```

### 配置位置

`config` 目录包含关键配置文件，并且可以有不同的配置文件。
它们全部必须以 `.config.php` 结尾，并且它们的名称中不能包含 `-sample`。

一些常见已知的配置文件示例：
- `local.config.php` 包含基础节点的自定义配置。
- 此目录中的任何其他文件都用于附加配置（例如插件）。

插件可以在 `addon/[addon]/config/[addon].config.php` 中定义其自己的默认配置值，该文件在插件激活时加载。

如果需要，可以使用 `FRIENDICA_CONFIG_DIR` 环境变量使用替代的 `config` 路径（需要完整路径！）。
这在将配置与程序二进制文件分离以加固系统时非常有用。

### 静态配置位置

`static` 目录保存了代码库的默认配置文件。
用户不能更改它们，因为它们可能会在发布之间更改。

当前，包括以下配置：
- `defaults.config.php` 保存了只能在 `local.config.php` 中设置的所有配置键的默认值。
- `settings.config.php` 保存了通过管理设置页面设置的某些配置键的默认值。

#### 从 .htconfig.php 迁移到 config/local.config.php

旧版 `.htconfig.php` 配置文件仍然受支持，但已弃用并将在随后的 Friendica 版本中删除。

迁移非常简单：
如果您的 `.htconfig.php` 中有任何特定于插件的配置，请将 `config/local-sample.config.php` 复制到 `config/addon.config.php` 并移动您的配置值。
之后，将 `config/local-sample.config.php` 复制到 `config/local.config.php`，将剩余的配置值按照下面的转换表移动到其中，然后将您的 `.htconfig.php` 重命名以检查节点是否按预期工作，然后再将其删除。

<style>
table.config {
    margin: 1em 0;
    background-color: #f9f9f9;
    border: 1px solid #aaa;
    border-collapse: collapse;
    color: #000;
    width: 100%;
}

table.config > tr > th,
table.config > tr > td,
table.config > * > tr > th,
table.config > * > tr > td {
    border: 1px solid #aaa;
    padding: 0.2em 0.4em
}

table.config > tr > th,
table.config > * > tr > th {
    background-color: #f2f2f2;
    text-align: center;
    width: 50%
}
</style>

<table class="config">
	<thead>
		<tr>
			<th>.htconfig.php</th>
			<th>config/local.config.php</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><pre>
$db_host = 'localhost';
$db_user = 'mysqlusername';
$db_pass = 'mysqlpassword';
$db_data = 'mysqldatabasename';
$a->config["system"]["db_charset"] = 'utf8mb4';
</pre></td>
			<td><pre>
'database' => [
	'hostname' => 'localhost',
	'username' => 'mysqlusername',
	'password' => 'mysqlpassword',
	'database' => 'database',
	'charset' => 'utf8mb4',
],
</pre></td>
		</tr>
		<tr>
			<td><pre>
$a->config["section"]["key"] = "value";
</pre></td>
			<td><pre>
'section' => [
	'key' => 'value',
],
</pre></td>
		</tr>
		<tr>
			<td><pre>
$a->config["section"]["key"] = array(
	"value1",
	"value2",
	"value3"
);
</pre></td>
			<td><pre>
'section' => [
	'key' => ['value1', 'value2', 'value3'],
],
</pre></td>
		</tr>
		<tr>
			<td><pre>
$a->config["key"] = "value";
</pre></td>
			<td><pre>
'config' => [
	'key' => 'value',
],
</pre></td>
		</tr>
		<tr>
            <td><pre>
$a->config['register_policy'] = REGISTER_CLOSED;
</pre></td>
                    <td><pre>
'config' => [
    'register_policy' => \Friendica\Module\Register::CLOSED,
],
</pre></td>
        </tr>
		<tr>
			<td><pre>
$a->path = "value";
</pre></td>
			<td><pre>
'system' => [
	'urlpath' => 'value',
],
</pre></td>
		</tr>
		<tr>
			<td><pre>
$default_timezone = "value";
</pre></td>
			<td><pre>
'system' => [
	'default_timezone' => 'value',
],
</pre></td>
		</tr>
		<tr>
			<td><pre>
$pidfile = "value";
</pre></td>
			<td><pre>
'system' => [
	'pidfile' => 'value',
],
</pre></td>
		</tr>
		<tr>
			<td><pre>
$lang = "value";
</pre></td>
			<td><pre>
'system' => [
	'language' => 'value',
],
</pre></td>
		</tr>
	</tbody>
</table>

#### 从config/local.ini.php迁移到config/local.config.php

旧的 `config/local.ini.php` 配置文件仍然被支持，但已经被弃用，并将在之后的Friendica版本中删除。

迁移非常简单：
如果您在 `config/addon.ini.php` 中有任何特定于插件的配置，请将 `config/local-sample.config.php` 复制到 `config/addon.config.php` 并移动您的配置值。
之后，将 `config/local-sample.config.php` 复制到 `config/local.config.php`，根据以下转换表将剩余的配置值移动到其中，然后将`config/local.ini.php` 文件重命名以检查节点是否按预期工作，最后再删除它。

<table class="config">
	<thead>
		<tr>
			<th>config/local.ini.php</th>
			<th>config/local.config.php</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><pre>
[database]
hostname = localhost
username = mysqlusername
password = mysqlpassword
database = mysqldatabasename
charset = utf8mb4
</pre></td>
			<td><pre>
'database' => [
	'hostname' => 'localhost',
	'username' => 'mysqlusername',
	'password' => 'mysqlpassword',
	'database' => 'database',
	'charset' => 'utf8mb4',
],
</pre></td>
		</tr>
		<tr>
			<td><pre>
[section]
key = value
</pre></td>
			<td><pre>
'section' => [
	'key' => 'value',
],
</pre></td>
		</tr>
		<tr>
            <td><pre>
[config]
register_policty = REGISTER_CLOSED
</pre></td>
        			<td><pre>
'config' => [
    'register_policy' => \Friendica\Module\Register::CLOSED,
],
</pre></td>
        </tr>
		<tr>
			<td><pre>
[section]
key[] = value1
key[] = value2
key[] = value3
</pre></td>
			<td><pre>
'section' => [
	'key' => ['value1', 'value2', 'value3'],
],
</pre></td>
		</tr>
	</tbody>
</table>



### 数据库设置

配置变量`database.hostname`（或`database.socket`），`database.username`，`database.password`，`database.database`和可选的`database.charset`持有您的数据库连接凭据。如果您需要指定访问数据库的端口，可以将“：portnumber”附加到`database.hostname`变量末尾。

    'database' => [
        'hostname' => 'your.mysqlhost.com:123456',
    ]

如果设置了以下所有环境变量，则Friendica将使用它们而不是先前配置的变量来连接数据库：

    MYSQL_HOST或MYSQL_SOCKET
    MYSQL_PORT
    MYSQL_USERNAME
    MYSQL_PASSWORD
    MYSQL_DATABASE

只能在config/local.config.php中设置的配置值

有些配置值没有出现在管理页面中。这有几个原因。也许它们是当前正在进行的开发的一部分，这些开发被认为是不稳定的，当它被认为是安全的时，将在管理页面中添加。或者它会触发一些不被认为是公共利益的事情。或者它仅供测试目的。

**注意：**请注意，您不应该在不知道它可能触发的情况下使用其中一个值。尤其是不要使用未记录的值。

这些配置键及其默认值列在“static/defaults.config.php”中，并应在“config/local.config.php”中进行覆盖。

管理员选项

启用帐户的管理员面板，从而使帐户持有人成为节点管理员，是通过设置变量完成的

    'config' => [
        'admin_email' => 'someone@example.com',
    ]

您必须将用于帐户的电子邮件地址与输入到`config/local.config.php`文件中的电子邮件地址匹配。如果超过一个帐户应能够访问管理面板，则用逗号分隔电子邮件地址。

    'config' => [
        'admin_email' => 'someone@example.com,someoneelse@example.com',
    ]

如果您想要为通知电子邮件设置更个性化的关闭行，可以为`admin_name`设置变量。

    'config' => [
        'admin_name' => 'Marvin',
    ]