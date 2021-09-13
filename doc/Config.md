Config values that can only be set in config/local.config.php
==========================================================

* [Home](help)

Friendica's configuration is done in two places: in PHP array configuration files and in the `config` database table.
Database config values overwrite the same file config values.

## File configuration

The configuration format for file configuration is an array returned from a PHP file.
This prevents your webserver from displaying your private configuration. It interprets the configuration files and displays nothing.

A typical configuration file looks like this:

```php
<?php

/*
 * Comment block
 */

return [
	'section1' => [
		// Comment line
		'key' => 'value',
	],
	'section2' => [
		'array' => ['value0', 'value1', 'value2'],
	],
];
```

### Configuration location

The `config` directory holds key configuration files and can have different config files.
All of them have to end with `.config.php` and must not include `-sample` in their name.

Some examples of common known configuration files:
- `local.config.php` holds the current node custom configuration.
- `addon.config.php` is optional and holds the custom configuration for specific addons.

Addons can define their own default configuration values in `addon/[addon]/config/[addon].config.php` which is loaded when the addon is activated.

If needed, an alternative `config` path can be used by using the `FRIENDICA_CONFIG_DIR` environment variable (full path required!).
This is useful in case of hardening the system by separating configuration from program binaries. 

### Static Configuration location

The `static` directory holds the codebase default configurations files.
They must not be changed by users, because they can get changed from release to release.

Currently, the following configurations are included:
- `defaults.config.php` holds the default values for all the configuration keys that can only be set in `local.config.php`.
- `settings.config.php` holds the default values for some configuration keys that are set through the admin settings page.

#### Migrating from .htconfig.php to config/local.config.php

The legacy `.htconfig.php` configuration file is still supported, but is deprecated and will be removed in a subsequent Friendica release.

The migration is pretty straightforward:
If you had any addon-specific configuration in your `.htconfig.php`, just copy `config/addon-sample.config.php` to `config/addon.config.php` and move your configuration values.
Afterwards, copy `config/local-sample.config.php` to `config/local.config.php`, move the remaining configuration values to it according to the following conversion chart, then rename your `.htconfig.php` to check your node is working as expected before deleting it.

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

#### Migrating from config/local.ini.php to config/local.config.php

The legacy `config/local.ini.php` configuration file is still supported, but is deprecated and will be removed in a subsequent Friendica release.

The migration is pretty straightforward:
If you had any addon-specific configuration in your `config/addon.ini.php`, just copy `config/addon-sample.config.php` to `config/addon.config.php` and move your configuration values.
Afterwards, copy `config/local-sample.config.php` to `config/local.config.php`, move the remaining configuration values to it according to the following conversion chart, then rename your `config/local.ini.php` file to check your node is working as expected before deleting it.

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



### Database Settings

The configuration variables database.hostname, database.username, database.password, database.database and database.charset are holding your credentials for the database connection.
If you need to specify a port to access the database, you can do so by appending ":portnumber" to the database.hostname variable.

    'database' => [
        'hostname' => 'your.mysqlhost.com:123456',
    ]

If all of the following environment variables are set, Friendica will use them instead of the previously configured variables for the db:

    MYSQL_HOST
    MYSQL_PORT
    MYSQL_USERNAME
    MYSQL_PASSWORD
    MYSQL_DATABASE

## Config values that can only be set in config/local.config.php

There are some config values that haven't found their way into the administration page.
This has several reasons.
Maybe they are part of a current development that isn't considered stable and will be added later in the administration page when it is considered safe.
Or it triggers something that isn't expected to be of public interest.
Or it is for testing purposes only.

**Attention:** Please be warned that you shouldn't use one of these values without the knowledge what it could trigger.
Especially don't do that with undocumented values.

These configurations keys and their default value are listed in `static/defaults.config.php` and should be overwritten in `config/local.config.php`.

## Administrator Options

Enabling the admin panel for an account, and thus making the account holder admin of the node, is done by setting the variable

    'config' => [
        'admin_email' => 'someone@example.com',
    ]
    

Where you have to match the email address used for the account with the one you enter to the `config/local.config.php` file.
If more then one account should be able to access the admin panel, separate the email addresses with a comma.

    'config' => [
        'admin_email' => 'someone@example.com,someoneelse@example.com',
    ]

If you want to have a more personalized closing line for the notification emails you can set a variable for the `admin_name`.

    'config' => [
        'admin_name' => 'Marvin',
    ]
