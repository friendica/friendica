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
* PHP 5.6+ (PHP 7 is recommended for performance)
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

    git clone https://github.com/friendica/friendica.git mywebsite
    cd mywebsite
    bin/composer.phar install

Make sure the folder *view/smarty3* exists and is writable by the webserver user

    mkdir view/smarty3
    chmod 777 view/smarty3

Get the addons by going into your website folder.

    cd mywebsite

Clone the addon repository (separately):

    git clone https://github.com/friendica/friendica-addons.git addon

If you copy the directory tree to your webserver, make sure that you also copy .htaccess - as "dot" files are often hidden and aren't normally copied.

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

* Does ".htconfig.php" exist? If not, edit htconfig.php and change the system settings. Rename to .htconfig.php
* Is the database is populated? If not, import the contents of "database.sql" with phpmyadmin or the mysql command line.

At this point visit your website again, and register your personal account.
Registration errors should all be recoverable automatically.
If you get any *critical* failure at this point, it generally indicates the database was not installed correctly.
You might wish to move/rename .htconfig.php to another name and empty (called 'dropping') the database tables, so that you can start fresh.

### Option B: Run the automatic install script

Open the file htconfig.php in the main Friendica directory with a text editor.
Remove the `die('...');` line and edit the lines to suit your installation (MySQL, language, theme etc.).
Then save the file (do not rename it). 

Navigate to the main Friendica directory and execute the following command:

    bin/console autoinstall

Or if you wish to include all optional checks, execute this statement instead:

    bin/console autoinstall -a

At this point visit your website again, and register your personal account.

*If* the automatic installation fails for any reason, check the following:

* Does ".htconfig.php" already exist? If yes, the automatic installation won't start
* Are the settings inside "htconfig.php" correct? If not, edit the file again.
* Is the empty MySQL-database created? If not, create it.

For more information during the installation, you can use this command line option

    bin/console autoinstall -v

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

The most important file is the `.htconfig.php` file in the base directory.
As it stores all your data, you should also have a recent dump of your Friendica database at hand, should you have to recover your node.
