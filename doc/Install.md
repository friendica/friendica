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
Put some thought into this. Changing it after installation is currently not supported.
Things will break, and some of your friends may have difficulty communicating with you.
We plan to address this limitation in a future release.


Requirements
---

* Apache with mod-rewrite enabled and "Options All" so you can use a local .htaccess file
* PHP 5.6.1+ (PHP 7 is recommended for performance)
  * PHP *command line* access with register_argc_argv set to true in the php.ini file
  * Curl, GD, PDO, MySQLi, hash, xml, zip and OpenSSL extensions
  * The POSIX module of PHP needs to be activated (e.g. [RHEL, CentOS](http://www.bigsoft.co.uk/blog/index.php/2014/12/08/posix-php-commands-not-working-under-centos-7) have disabled it)
  * some form of email server or email gateway such that PHP mail() works
* Mysql 5.5.3+ or an equivalant alternative for MySQL (MariaDB, Percona Server etc.)
* the ability to schedule jobs with cron (Linux/Mac) or Scheduled Tasks (Windows) (Note: other options are presented in Section 7 of this document.)
* Installation into a top-level domain or sub-domain (without a directory/path component in the URL) is preferred. Directory paths will not be as convenient to use and have not been thoroughly tested.
* If your hosting provider doesn't allow Unix shell access, you might have trouble getting everything to work.

Installation procedure
---

### Get Friendica

Unpack the Friendica files into the root of your web server document area.
If you are able to do so, we recommend using git to clone the source repository rather than to use a packaged tar or zip file.
This makes the software much easier to update.
The Linux commands to clone the repository into a directory "mywebsite" would be

    git clone https://github.com/friendica/friendica.git -b master mywebsite
    cd mywebsite
    bin/composer.phar install

Make sure the folder *view/smarty3* exists and is writable by the webserver user, in this case `www-data`

    mkdir view/smarty3
    chown www-data:www-data view/smarty3
    chmod 775 view/smarty3

Get the addons by going into your website folder.

    cd mywebsite

Clone the addon repository (separately):

    git clone https://github.com/friendica/friendica-addons.git -b master addon

If you copy the directory tree to your webserver, make sure that you also copy .htaccess - as "dot" files are often hidden and aren't normally copied.

If you want to use the development version of Friendica you can switch to the devel branch in the repository by running

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

* Does "config/local.ini.php" exist? If not, edit config/local-sample.ini.php and change the system settings.
* Rename to `config/local.ini.php`.
* Is the database is populated? If not, import the contents of `database.sql` with phpmyadmin or the mysql command line.

At this point visit your website again, and register your personal account.
Registration errors should all be recoverable automatically.
If you get any *critical* failure at this point, it generally indicates the database was not installed correctly.
You might wish to move/rename `config/local.ini.php` to another name and empty (called 'dropping') the database tables, so that you can start fresh.

### Option B: Run the automatic install script

You have the following options to automatically install Friendica:
-	creating a prepared config file (f.e. `prepared.ini.php`)
-	using environment variables (f.e. `MYSQL_HOST`)
-	using options (f.e. `--dbhost <host>`)

You can combine environment variables and options, but be aware that options are prioritized over environment variables. 

For more information during the installation, you can use this command line option

    bin/console autoinstall -v

If you wish to include all optional checks, use `-a` like this statement:

    bin/console autoinstall -a
    
*If* the automatic installation fails for any reason, check the following:

*	Does `config/local.ini.php` already exist? If yes, the automatic installation won't start
*	Are the options in the `config/local.ini.php` correct? If not, edit them directly.
*	Is the empty MySQL-database created? If not, create it.

#### B.1: Config file

You can use a prepared config file like [local-sample.ini.php](config/local-sample.ini.php).

Navigate to the main Friendica directory and execute the following command:

    bin/console autoinstall -f <prepared.ini.php>
    
#### B.2: Environment variables

There are two types of environment variables.
-	those you can use in normal mode too (Currently just **database credentials**)
-	those you can only use during installation (because Friendica will normally ignore it)

You can use the options during installation too and skip some of the environment variables.

**Database credentials**

if you don't use the option `--savedb` during installation, the DB credentials will **not** be saved in the `config/local.ini.php`.

-	`MYSQL_HOST` The host of the mysql/mariadb database
-	`MYSQL_PORT` The port of the mysql/mariadb database
-	`MYSQL_USERNAME` The username of the mysql database login (used for mysql)
-	`MYSQL_USER` The username of the mysql database login (used for mariadb)
-	`MYSQL_PASSWORD` The password of the mysql/mariadb database login
-	`MYSQL_DATABASE` The name of the mysql/mariadb database

**Friendica settings**

This variables wont be used at normal Friendica runtime.
Instead, they get saved into `config/local.ini.php`. 

-	`FRIENDICA_PHP_PATH` The path of the PHP binary
-	`FRIENDICA_ADMIN_MAIL` The admin email address of Friendica (this email will be used for admin access)
-	`FRIENDICA_TZ` The timezone of Friendica
-	`FRIENDICA_LANG` The langauge of Friendica

Navigate to the main Friendica directory and execute the following command:

    bin/console autoinstall [--savedb]

#### B.3: Execution options

All options will be saved in the `config/local.ini.php` and are overruling the associated environment variables.

-	`-H|--dbhost <host>` The host of the mysql/mariadb database (env `MYSQL_HOST`)
-	`-p|--dbport <port>` The port of the mysql/mariadb database (env `MYSQL_PORT`)
-	`-U|--dbuser <username>` The username of the mysql/mariadb database login (env `MYSQL_USER` or `MYSQL_USERNAME`)
-	`-P|--dbpass <password>` The password of the mysql/mariadb database login (env `MYSQL_PASSWORD`)
-	`-d|--dbdata <database>` The name of the mysql/mariadb database (env `MYSQL_DATABASE`)
-	`-b|--phppath <path>` The path of the PHP binary (env `FRIENDICA_PHP_PATH`)
-	`-A|--admin <mail>` The admin email address of Friendica (env `FRIENDICA_ADMIN_MAIL`)
-	`-T|--tz <timezone>` The timezone of Friendica (env `FRIENDICA_TZ`)
-	`-L|--land <language>` The language of Friendica (env `FRIENDICA_LANG`)

Navigate to the main Friendica directory and execute the following command:

    bin/console autoinstall [options]

### Prepare .htaccess file

Copy .htaccess-dist to .htaccess (be careful under Windows) to have working mod-rewrite again. If you have installed Friendica into a sub directory, like /friendica/ set this path in RewriteBase accordingly.

Example:

    cp .htacces-dist .htaccess

*Note*: Do **not** rename the .htaccess-dist file as it is tracked by GIT and renaming will cause a dirty working directory.

### Set up the worker

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

If it is not possible to set up a cron job then please activate the "frontend worker" in the administration interface.

Once you have installed Friendica and created an admin account as part of the process, you can access the admin panel of your installation and do most of the server wide configuration from there

### Set up a backup plan

Bad things will happen.
Let there be a hardware failure, a corrupted database or whatever you can think of.
So once the installation of your Friendica node is done, you should make yourself a backup plan.

The most important file is the `config/local.ini.php` file.
As it stores all your data, you should also have a recent dump of your Friendica database at hand, should you have to recover your node.
