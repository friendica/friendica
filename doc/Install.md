# Friendica Installation


We've tried very hard to ensure that Friendica will run on commodity hosting platforms - such as those used to host Wordpress blogs and Drupal websites.
We offer a manual and an automatic installation.
But be aware that Friendica is more than a simple web application.

It is a complex communications system which more closely resembles an email server than a web server.
For reliability and performance, messages are delivered in the background and are queued for later delivery when sites are down.
This kind of functionality requires a bit more of the host system than the typical blog.

Not every PHP/MySQL hosting provider will be able to support Friendica.
Many will.

But **please** review the [requirements](#Requirements) and confirm these with your hosting provider prior to installation.

## Support
If you encounter installation issues, please let us know via the [helper](http://forum.friendi.ca/profile/helpers) or the [developer](https://forum.friendi.ca/profile/developers) forum or [file an issue](https://github.com/friendica/friendica/issues).

Please be as clear as you can about your operating environment and provide as much detail as possible about any error messages you may see, so that we can prevent it from happening in the future.
Due to the large variety of operating systems and PHP platforms in existence we may have only limited ability to debug your PHP installation or acquire any missing modules - but we will do our best to solve any general code issues.

## Prerequisites

* Choose a domain name or subdomain name for your server. Put some thought into this. While changing it after installation is supported, things still might break.
* Setup HTTPS on your domain. 

### Requirements

* Apache with mod-rewrite enabled and "Options All" so you can use a local `.htaccess` file
* PHP 7.2+ (PHP8 is not fully supported yet)
  * PHP *command line* access with register_argc_argv set to true in the php.ini file
  * Curl, GD, PDO, mbstrings, MySQLi, hash, xml, zip and OpenSSL extensions
  * The POSIX module of PHP needs to be activated (e.g. [RHEL, CentOS](http://www.bigsoft.co.uk/blog/index.php/2014/12/08/posix-php-commands-not-working-under-centos-7) have disabled it)
  * some form of email server or email gateway such that PHP mail() works
* MySQL 5.6+ or an equivalent alternative for MySQL (MariaDB, Percona Server etc.)
* ability to schedule jobs with cron (Linux/Mac) or Scheduled Tasks (Windows)
* installation into a top-level domain or sub-domain (without a directory/path component in the URL) is RECOMMENDED. Directory paths will not be as convenient to use and have not been thoroughly tested. This is REQUIRED if you wish to communicate with the Diaspora network.

**If your hosting provider doesn't allow Unix shell access, you might have trouble getting everything to work.**

For alternative server configurations (such as Nginx server and MariaDB database engine), refer to the [Friendica wiki](https://github.com/friendica/friendica/wiki).

### Optional 

* PHP ImageMagick extension (php-imagick) for animated GIF support.

## Installation procedure

### Alternative Installation Methods

This guide will walk you through the manual installation process of Friendica.
If this is nothing for you, you might be interested in

* the [Friendica Docker image](https://github.com/friendica/docker) or
* how to [install Friendica with YunoHost](https://github.com/YunoHost-Apps/friendica_ynh).

### Get Friendica

Download the full archive of the stable release of Friendica core and the addons from [the project homepage](https://friendi.ca/resources/download-files/).
Make sure that the version of the Friendica archive and the addons match.
Unpack the Friendica files into the root of your web server document area.

If you copy the directory tree to your webserver, make sure that you also copy `.htaccess-dist` - as "dot" files are often hidden and aren't normally copied.

**OR**

Clone the [friendica/friendica GitHub repository](https://github.com/friendica/friendica) and import dependencies.
This makes the software much easier to update.

The Linux commands to clone the repository into a directory "mywebsite" would be

    git clone https://github.com/friendica/friendica.git -b stable mywebsite
    cd mywebsite
    bin/composer.phar install --no-dev

Make sure the folder *view/smarty3* exists and is writable by the webserver user, in this case *www-data*

    mkdir -p view/smarty3
    chown www-data:www-data view/smarty3
    chmod 775 view/smarty3

Get the addons by going into your website folder.

    cd mywebsite

Clone the addon repository (separately):

    git clone https://github.com/friendica/friendica-addons.git -b stable addon

If you want to use the development version of Friendica you can switch to the develop branch in the repository by running

    git checkout develop
    bin/composer.phar install
    cd addon
    git checkout develop

**Be aware that the develop branch is unstable and may break your Friendica node at any time.**
You should have a recent backup before updating.
If you encounter a bug, please let us know.

### Create a database

Create an empty database and note the access details (hostname, username, password, database name).

Friendica needs the permission to create and delete fields and tables in its own database.

Please check the [troubleshooting](#Troubleshooting) section if running on MySQL 5.7.17 or newer.

### Option A: Run the installer

Before you point your web browser to the new site you need to copy `.htaccess-dist` to `.htaccess` for Apache installs.
Follow the instructions.
Please note any error messages and correct these before continuing.

If you need to specify a port for the connection to the database, you can do so in the host name setting for the database.

*If* the manual installation fails for any reason, check the following:

* Does `config/local.config.php` exist? If not, edit `config/local-sample.config.php` and change the system settings.
* Rename to `config/local.config.php`.
* Is the database populated? If not, import the contents of `database.sql` with phpmyadmin or the mysql command line.

At this point visit your website again, and register your personal account.
Registration errors should all be recoverable automatically.
If you get any *critical* failure at this point, it generally indicates the database was not installed correctly.
You might wish to move/rename `config/local.config.php` to another name and empty (called 'dropping') the database tables, so that you can start fresh.

### Option B: Run the automatic install script

You have the following options to automatically install Friendica:
-	creating a prepared config file (f.e. `prepared.config.php`)
-	using environment variables (f.e. `MYSQL_HOST`)
-	using options (f.e. `--dbhost <host>`)

You can combine environment variables and options, but be aware that options are prioritized over environment variables. 

For more information during the installation, you can use this command line option

    bin/console autoinstall -v

If you wish to include all optional checks, use `-a` like this statement:

    bin/console autoinstall -a
    
*If* the automatic installation fails for any reason, check the following:

*	Does `config/local.config.php` already exist? If yes, the automatic installation won't start
*	Are the options in the `config/local.config.php` correct? If not, edit them directly.
*	Is the empty MySQL-database created? If not, create it.

#### B.1: Config file

You can use a prepared config file like [local-sample.config.php](/config/local-sample.config.php).

Navigate to the main Friendica directory and execute the following command:

    bin/console autoinstall -f <prepared.config.php>
    
#### B.2: Environment variables

There are two types of environment variables.
-	those you can use in normal mode too (Currently just **database credentials**)
-	those you can only use during installation (because Friendica will normally ignore it)

You can use the options during installation too and skip some of the environment variables.

**Database credentials**

if you don't use the option `--savedb` during installation, the DB credentials will **not** be saved in the `config/local.config.php`.

-	`MYSQL_HOST` The host of the mysql/mariadb database
-	`MYSQL_PORT` The port of the mysql/mariadb database
-	`MYSQL_USERNAME` The username of the mysql database login (used for mysql)
-	`MYSQL_USER` The username of the mysql database login (used for mariadb)
-	`MYSQL_PASSWORD` The password of the mysql/mariadb database login
-	`MYSQL_DATABASE` The name of the mysql/mariadb database

**Friendica settings**

This variables wont be used at normal Friendica runtime.
Instead, they get saved into `config/local.config.php`. 

-	`FRIENDICA_URL_PATH` The URL path of Friendica (f.e. '/friendica')
-	`FRIENDICA_PHP_PATH` The path of the PHP binary
-	`FRIENDICA_ADMIN_MAIL` The admin email address of Friendica (this email will be used for admin access)
-	`FRIENDICA_TZ` The timezone of Friendica
-	`FRIENDICA_LANG` The language of Friendica

Navigate to the main Friendica directory and execute the following command:

    bin/console autoinstall [--savedb]

#### B.3: Execution options

All options will be saved in the `config/local.config.php` and are overruling the associated environment variables.

-	`-H|--dbhost <host>` The host of the mysql/mariadb database (env `MYSQL_HOST`)
-	`-p|--dbport <port>` The port of the mysql/mariadb database (env `MYSQL_PORT`)
-	`-U|--dbuser <username>` The username of the mysql/mariadb database login (env `MYSQL_USER` or `MYSQL_USERNAME`)
-	`-P|--dbpass <password>` The password of the mysql/mariadb database login (env `MYSQL_PASSWORD`)
-	`-d|--dbdata <database>` The name of the mysql/mariadb database (env `MYSQL_DATABASE`)
-	`-u|--urlpath <url_path>` The URL path of Friendica - f.e. '/friendica' (env `FRIENDICA_URL_PATH`)
-	`-b|--phppath <php_path>` The path of the PHP binary (env `FRIENDICA_PHP_PATH`)
-	`-A|--admin <mail>` The admin email address of Friendica (env `FRIENDICA_ADMIN_MAIL`)
-	`-T|--tz <timezone>` The timezone of Friendica (env `FRIENDICA_TZ`)
-	`-L|--lang <language>` The language of Friendica (env `FRIENDICA_LANG`)

Navigate to the main Friendica directory and execute the following command:

    bin/console autoinstall [options]

### Prepare .htaccess file

Copy `.htaccess-dist` to `.htaccess` (be careful under Windows) to have working mod-rewrite again. If you have installed Friendica into a sub directory, like */friendica/* set this path in `RewriteBase` accordingly.

Example:

    cp .htacces-dist .htaccess

*Note*: Do **not** rename the `.htaccess-dist` file as it is tracked by GIT and renaming will cause a dirty working directory.

### Verify the "host-meta" page is working

Friendica should respond automatically to important addresses under the */.well-known/* rewrite path.
One critical URL would look like, for example: https://example.com/.well-known/host-meta   
It must be visible to the public and must respond with an XML file that is automatically customized to your site.

If that URL is not working, it is possible that some other software is using the /.well-known/ path.
Other symptoms may include an error message in the Admin settings that says "host-meta is not reachable on your system.
This is a severe configuration issue that prevents server to server communication."
Another common error related to host-meta is the "Invalid profile URL."

Check for a `.well-known` directory that did not come with Friendica.
The preferred configuration is to remove the directory, however this is not always possible.
If there is any /.well-known/.htaccess file, it could interfere with this Friendica core requirement.
You should remove any RewriteRules from that file, or remove that whole file if appropriate.
It may be necessary to chmod the /.well-known/.htaccess file if you were not given write permissions by default.

## Register the admin account

At this point visit your website again, and register your personal account with the same email as in the `config.admin_email` config value.
Registration errors should all be recoverable automatically.

If you get any *critical* failure at this point, it generally indicates the database was not installed correctly. 
You might wish to delete/rename `config/local.config.php` to another name and drop all the database tables so that you can start fresh.

## Post Install Configuration

### (REQUIRED) Background tasks

Set up a cron job or scheduled task to run the worker once every 5-10 minutes in order to perform background processing.
Example:

    cd /base/directory; /path/to/php bin/worker.php

Change "/base/directory", and "/path/to/php" as appropriate for your situation.

#### cron job for worker

If you are using a Linux server, run "crontab -e" and add a line like the
one shown, substituting for your unique paths and settings:

    */10 * * * * cd /home/myname/mywebsite; /usr/bin/php bin/worker.php

You can generally find the location of PHP by executing "which php".
If you run into trouble with this section please contact your hosting provider for assistance.
Friendica will not work correctly if you cannot perform this step.

If it is not possible to set up a cron job then please activate the "frontend worker" in the administration interface.

Once you have installed Friendica and created an admin account as part of the process, you can access the admin panel of your installation and do most of the server wide configuration from there.

#### worker alternative: daemon
Otherwise, you’ll need to use the command line on your remote server and start the Friendica daemon (background task) using the following command:

    cd /path/to/friendica; php bin/daemon.php start

Once started, you can check the daemon status using the following command:

    cd /path/to/friendica; php bin/daemon.php status

After a server restart or any other failure, the daemon needs to be restarted. 
This could be achieved by a cronjob.

### (RECOMMENDED) Logging & Log Rotation

At this point it is recommended that you set up logging and logrotation.
To do so please visit [Settings](help/Settings) and search the 'Logs' section for more information.

### (RECOMMENDED) Set up a backup plan

Bad things will happen.
Let there be a hardware failure, a corrupted database or whatever you can think of.
So once the installation of your Friendica node is done, you should make yourself a backup plan.

The most important file is the `config/local.config.php` file.
As it stores all your data, you should also have a recent dump of your Friendica database at hand, should you have to recover your node.

### (OPTIONAL) Reverse-proxying and HTTPS

Friendica looks for some well-known HTTP headers indicating a reverse-proxy
terminating an HTTPS connection.
While the standard from RFC 7239 specifies the use of the `Forwarded` header.

    Forwarded: for=192.0.2.1; proto=https; by=192.0.2.2

Friendica also supports a number on non-standard headers in common use.

    X-Forwarded-Proto: https

    Front-End-Https: on

    X-Forwarded-Ssl: on

It is however preferable to use the standard approach if configuring a new server.

## Troubleshooting

### "System is currently unavailable. Please try again later"

Check your database settings.
It usually means your database could not be opened or accessed.
If the database resides on the same machine, check that the database server name is "localhost".

### 500 Internal Error

This could be the result of one of our Apache directives not being supported by your version of Apache. Examine your apache server logs.
You might remove the line "Options -Indexes" from the `.htaccess` file if you are using a Windows server as this has been known to cause problems.
Also check your file permissions. Your website and all contents must generally be world-readable.

It is likely that your web server reported the source of the problem in its error log files.
Please review these system error logs to determine what caused the problem.
Often this will need to be resolved with your hosting provider or (if self-hosted) your web server configuration.

### 400 and 4xx "File not found" errors

First check your file permissions.
Your website and all contents must generally be world-readable.

Ensure that mod-rewite is installed and working, and that your `.htaccess` file
is being used. To verify the latter, create a file `test.out` containing the
word "test" in the top directory of Friendica, make it world readable and point
your web browser to

	http://yoursitenamehere.com/test.out

This file should be blocked. You should get a permission denied message.

If you see the word "test" your Apache configuration is not allowing your
`.htaccess` file to be used (there are rules in this file to block access to any
file with .out at the end, as these are typically used for system logs).

Make certain the `.htaccess` file exists and is readable by everybody, then look
for the existence of "AllowOverride None" in the Apache server configuration for your site.
This will need to be changed to "AllowOverride All".

If you do not see the word "test", your `.htaccess` is working, but it is likely
that mod-rewrite is not installed in your web server or is not working.

On most Linux flavors:

	% a2enmod rewrite
	% /etc/init.d/apache2 restart

Consult your hosting provider, experts on your particular Linux distribution or
(if Windows) the provider of your Apache server software if you need to change
either of these and can not figure out how. There is a lot of help available on
the web. Search "mod-rewrite" along with the name of your operating system
distribution or Apache package (if using Windows).

### Unable to write the file config/local.config.php due to permissions issues

Create an empty `config/local.config.php`file and apply world-write permission.

On Linux:

	% touch config/local.config.php
	% chmod 664 config/local.config.php

Retry the installation. As soon as the database has been created,

******* this is important *********

	% chmod 644 config/local.config.php

### Suhosin issues

Some configurations with "suhosin" security are configured without an ability to
run external processes. Friendica requires this ability. Following are some notes
provided by one of our members.

> On my server I use the php protection system Suhosin [http://www.hardened-php.net/suhosin/].
> One of the things it does is to block certain functions like proc_open, as
> configured in `/etc/php5/conf.d/suhosin.ini`:
> 
>     suhosin.executor.func.blacklist = proc_open, ...
>
> For those sites like Friendica that really need these functions they can be
> enabled, e.g. in `/etc/apache2/sites-available/friendica`:
>
> 	<Directory /var/www/friendica/>
> 	  php_admin_value suhosin.executor.func.blacklist none
> 	  php_admin_value suhosin.executor.eval.blacklist none
> 	</Directory>
> 
> This enables every function for Friendica if accessed via browser, but not for
> the cronjob that is called via php command line. I attempted to enable it for
> cron by using something like:
> 
> 	*/10 * * * * cd /var/www/friendica/friendica/ && sudo -u www-data /usr/bin/php \
>       -d suhosin.executor.func.blacklist=none \
>       -d suhosin.executor.eval.blacklist=none -f bin/worker.php
> 
> This worked well for simple test cases, but the friendica-cron still failed
> with a fatal error:
> 
> 	suhosin[22962]: ALERT - function within blacklist called: proc_open()
>     (attacker 'REMOTE_ADDR not set', file '/var/www/friendica/friendica/boot.php',
>     line 1341)
> 
> After a while I noticed, that `bin/worker.php` calls further PHP script via `proc_open`.
> These scripts themselves also use `proc_open` and fail, because they are NOT
> called with `-d suhosin.executor.func.blacklist=none`.
> 
>  So the simple solution is to put the correct parameters into `config/local.config.php`:
> 
> 	'config' => [
> 		//Location of PHP command line processor
> 		'php_path' => '/usr/bin/php -d suhosin.executor.func.blacklist=none \
>               -d suhosin.executor.eval.blacklist=none',
> 	],
> 
> This is obvious as soon as you notice that the friendica-cron uses `proc_open`
> to execute PHP scripts that also use `proc_open`, but it took me quite some time to find that out.
> I hope this saves some time for other people using suhosin with function blocklists.

### Unable to create all mysql tables on MySQL 5.7.17 or newer

If the setup fails to create all the database tables and/or manual creation from
the command line fails, with this error:

	ERROR 1067 (42000) at line XX: Invalid default value for 'created'

You need to adjust your my.cnf and add the following setting under the [mysqld]
section:

	sql_mode = '';

After that, restart mysql and try again.
