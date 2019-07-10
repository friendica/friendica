Friendica Installation
===============

We've tried very hard to ensure that Friendica will run on commodity hosting platforms - such as those used to host Wordpress blogs and Drupal websites.
We offer a manual and an automatic installation.
But be aware that Friendica is more than a simple web application.
It is a complex communications system which more closely resembles an email server than a web server.
For reliability and performance, messages are delivered in the background and are queued for later delivery when sites are down.
This kind of functionality requires a bit more of the host system than the typical blog.
Not every PHP/MySQL hosting provider will be able to support Friendica.
Many will.
But **please** review the requirements and confirm these with your hosting provider prior to installation.

Also if you encounter installation issues, please let us know via the [helper](http://forum.friendi.ca/profile/helpers) or the [developer](https://forum.friendi.ca/profile/developers) forum or [file an issue](https://github.com/friendica/friendica/issues).
Please be as clear as you can about your operating environment and provide as much detail as possible about any error messages you may see, so that we can prevent it from happening in the future.
Due to the large variety of operating systems and PHP platforms in existence we may have only limited ability to debug your PHP installation or acquire any missing modules - but we will do our best to solve any general code issues.
If you do not have a Friendica account yet, you can register a temporary one at [tryfriendica.de](https://tryfriendica.de) and join the forums mentioned above from there.
The account will expire after 7 days, but you can ask the server admin to keep your account longer, should the problem not be resolved after that.

Before you begin: Choose a domain name or subdomain name for your server.
Put some thought into this.
While changing it after installation is supported, things still might break.

Requirements
---

* Apache with mod-rewrite enabled and "Options All" so you can use a local .htaccess file
* PHP 7+ (PHP 7.1+ is recommended for performance and official support)
  * PHP *command line* access with register_argc_argv set to true in the php.ini file
  * Curl, GD, PDO, MySQLi, hash, xml, zip and OpenSSL extensions
  * The POSIX module of PHP needs to be activated (e.g. [RHEL, CentOS](http://www.bigsoft.co.uk/blog/index.php/2014/12/08/posix-php-commands-not-working-under-centos-7) have disabled it)
  * some form of email server or email gateway such that PHP mail() works
* Mysql 5.6+ or an equivalent alternative for MySQL (MariaDB, Percona Server etc.)
* the ability to schedule jobs with cron (Linux/Mac) or Scheduled Tasks (Windows) (Note: other options are presented in Section 7 of this document.)
* Installation into a top-level domain or sub-domain (without a directory/path component in the URL) is preferred. Directory paths will not be as convenient to use and have not been thoroughly tested.
* If your hosting provider doesn't allow Unix shell access, you might have trouble getting everything to work.

Optional
---

* PHP ImageMagick extension (php-imagick) for animated GIF support.

Installation procedure
---

### Alternative Installation Methods

This guide will walk you through the manual installation process of Friendica.
If this is nothing for you, you might be interested in

* the [Friendica Docker image](https://github.com/friendica/docker) or
* how [install Friendica with YunoHost](https://github.com/YunoHost-Apps/friendica_ynh).

### Get Friendica

Unpack the Friendica files into the root of your web server document area.
If you are able to do so, we recommend using git to clone the source repository rather than to use a packaged tar or zip file.
This makes the software much easier to update.
The Linux commands to clone the repository into a directory "mywebsite" would be

    git clone https://github.com/friendica/friendica.git -b master mywebsite
    cd mywebsite
    bin/composer.phar install --no-dev

Make sure the folder *view/smarty3* exists and is writable by the webserver user, in this case `www-data`

    mkdir view/smarty3
    chown www-data:www-data view/smarty3
    chmod 775 view/smarty3

Get the addons by going into your website folder.

    cd mywebsite

Clone the addon repository (separately):

    git clone https://github.com/friendica/friendica-addons.git -b master addon

If you copy the directory tree to your webserver, make sure that you also copy .htaccess - as "dot" files are often hidden and aren't normally copied.

If you want to use the development version of Friendica you can switch to the develop branch in the repository by running

    git checkout develop
    bin/composer.phar install
    cd addon
    git checkout develop

please be aware that the develop branch may break your Friendica node at any time.
If you encounter a bug, please let us know.

### Create a database

Create an empty database and note the access details (hostname, username, password, database name).

Friendica needs the permission to create and delete fields and tables in its own database.

With newer releases of MySQL (5.7.17 or newer), you might need to set the sql_mode to '' (blank).
Use this setting when the installer is unable to create all the needed tables due to a timestamp format problem.
In this case find the [mysqld] section in your my.cnf file and add the line :

    sql_mode = ''

Restart mysql and you should be fine.

### Option A: Run the manual installer

Point your web browser to the new site and follow the instructions.
Please note any error messages and correct these before continuing.

If you need to specify a port for the connection to the database, you can do so in the host name setting for the database.

*If* the manual installation fails for any reason, check the following:

* Does "config/local.config.php" exist? If not, edit config/local-sample.config.php and change the system settings.
* Rename to `config/local.config.php`.
* Is the database is populated? If not, import the contents of `database.sql` with phpmyadmin or the mysql command line.

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

You can use a prepared config file like [local-sample.config.php](config/local-sample.config.php).

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

Copy .htaccess-dist to .htaccess (be careful under Windows) to have working mod-rewrite again. If you have installed Friendica into a sub directory, like /friendica/ set this path in RewriteBase accordingly.

Example:

    cp .htacces-dist .htaccess

*Note*: Do **not** rename the .htaccess-dist file as it is tracked by GIT and renaming will cause a dirty working directory.

### Verify the "host-meta" page is working

Friendica should respond automatically to important addresses under the /.well-known/ rewrite path.
One critical URL would look like, for example, https://example.com/.well-known/host-meta
It must be visible to the public and must respond with an XML file that is automatically customized to your site.

If that URL is not working, it is possible that some other software is using the /.well-known/ path.
Other symptoms may include an error message in the Admin settings that says "host-meta is not reachable on your system.
This is a severe configuration issue that prevents server to server communication."
Another common error related to host-meta is the "Invalid profile URL."

Check for a .well-known directory that did not come with Friendica.
The preferred configuration is to remove the directory, however this is not always possible.
If there is any /.well-known/.htaccess file, it could interfere with this Friendica core requirement.
You should remove any RewriteRules from that file, or remove that whole file if appropriate.
It may be necessary to chmod the /.well-known/.htaccess file if you were not given write permissions by default.

### Set up the worker

#### cron job for Worker
Set up a cron job or scheduled task to run the worker once every 5-10 minutes in order to perform background processing.
Example:

    cd /base/directory; /path/to/php bin/worker.php

Change "/base/directory", and "/path/to/php" as appropriate for your situation.

If you are using a Linux server, run "crontab -e" and add a line like the
one shown, substituting for your unique paths and settings:

    */10 * * * * cd /home/myname/mywebsite; /usr/bin/php bin/worker.php

You can generally find the location of PHP by executing "which php".
If you run into trouble with this section please contact your hosting provider for assistance.
Friendica will not work correctly if you cannot perform this step.

#### worker alternative: daemon
Otherwise, you’ll need to use the command line on your remote server and start the Friendica daemon (background task) using the following command:

    cd /path/to/friendica; php bin/daemon.php start
Once started, you can check the daemon status using the following command:

    cd /path/to/friendica; php bin/daemon.php status

After a server restart or any other failure, the daemon needs to be restarted. This could be achieved by a cronjob.


Once you have installed Friendica and created an admin account as part of the process, you can access the admin panel of your installation and do most of the server wide configuration from there.

### Logging & Log Rotation
At this point it is recommended that you set up logging and logrotation.
To do so please visit [Settings](help/Settings) and search the 'Logs' section for more information.

### Set up a backup plan

Bad things will happen.
Let there be a hardware failure, a corrupted database or whatever you can think of.
So once the installation of your Friendica node is done, you should make yourself a backup plan.

The most important file is the `config/local.config.php` file.
As it stores all your data, you should also have a recent dump of your Friendica database at hand, should you have to recover your node.
