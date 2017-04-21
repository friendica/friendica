Friendica Installation
===============

We've tried very hard to ensure that Friendica will run on commodity hosting platforms - such as those used to host Wordpress blogs and Drupal websites.
But be aware that Friendica is more than a simple web application.
It is a complex communications system which more closely resembles an email server than a web server.
For reliability and performance, messages are delivered in the background and are queued for later delivery when sites are down.
This kind of functionality requires a bit more of the host system than the typical blog.
Not every PHP/MySQL hosting provider will be able to support Friendica.
Many will.
But **please** review the requirements and confirm these with your hosting provider prior to installation.

Also if you encounter installation issues, please let us know via the [helper](http://helpers.pyxis.uberspace.de/profile/helpers) or the [developer](https://helpers.pyxis.uberspace.de/profile/developers) forum or [file an issue](https://github.com/friendica/friendica/issues).
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
* PHP 5.4+.
* PHP *command line* access with register_argc_argv set to true in the php.ini file
* curl, gd, mysql, hash and openssl extensions
* some form of email server or email gateway such that PHP mail() works
* Mysql 5.5.3+ or an equivalant alternative for MySQL (MariaDB, Percona Server etc.)
* the ability to schedule jobs with cron (Linux/Mac) or Scheduled Tasks (Windows) (Note: other options are presented in Section 7 of this document.)
* Installation into a top-level domain or sub-domain (without a directory/path component in the URL) is preferred. Directory paths will not be as convenient to use and have not been thoroughly tested.
* If your hosting provider doesn't allow Unix shell access, you might have trouble getting everything to work.

Installation procedure
---

###Get Friendica

Unpack the Friendica files into the root of your web server document area.
If you are able to do so, we recommend using git to clone the source repository rather than to use a packaged tar or zip file.
This makes the software much easier to update.
The Linux command to clone the repository into a directory "mywebsite" would be

    git clone https://github.com/friendica/friendica.git mywebsite

Make sure the folder *view/smarty3* exists and is writable by the webserver user

    mkdir view/smarty3
    chmod 777 view/smarty3

Get the addons by going into your website folder.

    cd mywebsite

Clone the addon repository (separately):

    git clone https://github.com/friendica/friendica-addons.git addon

If you copy the directory tree to your webserver, make sure that you also copy .htaccess - as "dot" files are often hidden and aren't normally copied.

###Create a database

Create an empty database and note the access details (hostname, username, password, database name).

Friendica needs the permission to create and delete fields and tables in its own database.

With newer releases of MySQL (5.7.17 or newer), you might need to set the sql_mode to '' (blank).
Use this setting when the installer is unable to create all the needed tables due to a timestamp format problem.
In this case find the [mysqld] section in your my.cnf file and add the line :

    sql_mode = ''

Restart mysql and you should be fine.


###Run the installer

Point your web browser to the new site and follow the instructions.
Please note any error messages and correct these before continuing.

*If* the automated installation fails for any reason, check the following:

* Does ".htconfig.php" exist? If not, edit htconfig.php and change the system settings. Rename to .htconfig.php
* Is the database is populated? If not, import the contents of "database.sql" with phpmyadmin or mysql command line.

At this point visit your website again, and register your personal account.
Registration errors should all be recoverable automatically.
If you get any *critical* failure at this point, it generally indicates the database was not installed correctly.
You might wish to move/rename .htconfig.php to another name and empty (called 'dropping') the database tables, so that you can start fresh.

###Set up the poller

Set up a cron job or scheduled task to run the poller once every 5-10 minutes in order to perform background processing.
Example:

    cd /base/directory; /path/to/php include/poller.php

Change "/base/directory", and "/path/to/php" as appropriate for your situation.

If you are using a Linux server, run "crontab -e" and add a line like the
one shown, substituting for your unique paths and settings:

    */10 * * * * cd /home/myname/mywebsite; /usr/bin/php include/poller.php

You can generally find the location of PHP by executing "which php".
If you run into trouble with this section please contact your hosting provider for assistance.
Friendica will not work correctly if you cannot perform this step.

Alternative: You may be able to use the 'poormancron' plugin to perform this step.
To do this, edit the file ".htconfig.php" and look for a line describing your plugins.
On a fresh installation, it will look like this:

    $a->config['system']['addon'] = 'js_upload';

It indicates the "js_upload" addon module is enabled.
You may add additional addons/plugins using this same line in the configuration file.
Change it to read

    $a->config['system']['addon'] = 'js_upload,poormancron';

and save your changes.

Once you have installed Friendica and created an admin account as part of the process, you can access the admin panel of your installation and do most of the server wide configuration from there

Updating your installation with git
---

You can get the latest changes at any time with

    cd mywebsite
    git pull

The default branch to use it the ``master`` branch, which is the stable version of Friendica.
If you want to use and test bleeding edge code please checkout the ``develop`` branch.
The new features and fixes will be merged from ``develop`` into ``master`` when they are stable approx four times a year.

The addon tree has to be updated separately like so:

    cd mywebsite/addon
    git pull
