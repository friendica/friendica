# Friendica Installation

We've tried very hard to ensure that Friendica will run on commodity hosting
platforms - such as those used to host Wordpress blogs and Drupal websites.
But be aware that Friendica is more than a simple web application. It is a
complex communications system which more closely resembles an email server than
a web server. For reliability and performance, messages are delivered in the
background and are queued for later delivery when sites are down. This kind of
functionality requires a bit more of the host system than the typical blog.
Not every PHP/MySQL hosting provider will be able to support Friendica. Many will.
But please review the requirements and confirm these with your hosting provider
prior to installation.

Before you begin: Choose a domain name or subdomain name for your server.
Put some thought into this - because changing it is currently not-supported.
Things will break, and some of your friends may have difficulty communicating with you.
We plan to address this limitation in a future release. Also decide if you wish
to connect with members of the Diaspora network, as this will impact the
installation requirements.

Decide if you will use SSL and obtain an SSL cert. Communications with the
Diaspora network MAY require both SSL AND an SSL cert signed by a CA which is
recognized by major browsers. Friendica will work with self-signed certs but
Diaspora communication may not. For best results, install your cert PRIOR to
installing Friendica and when visiting your site for the initial installation in
step 5, please use the https: link. (Use the http: or non-SSL link if your cert
is self-signed).

## 1. Requirements

- Apache with mod-rewrite enabled and "Options All" so you can use a local .htaccess file
- PHP 7+ (PHP 7.1+ recommended for performance and official support).
	- PHP *command line* with `register_argc_argv = true` in php.ini
	- curl, gd (with at least jpeg support), mysql, mbstring, xml, zip and openssl extensions
	- Some form of email server or email gateway such that PHP mail() works
	- The POSIX module of PHP needs to be activated (e.g. RHEL, CentOS have disabled it)
	- Composer for a Git install

- Mysql 5.6+ or an equivalent alternative for MySQL (MariaDB 10.0.5+, Percona Server etc.)
- Ability to schedule jobs with cron (Linux/Mac) or Scheduled Tasks (Windows)
- Installation into a top-level domain or sub-domain (without a directory/path 
  component in the URL) is preferred. This is REQUIRED if you wish to communicate
  with the Diaspora network.
- For alternative server configurations (such as Nginx server and MariaDB database
  engine), refer to the [Friendica wiki](https://github.com/friendica/friendica/wiki).

This guide will walk you through the manual installation process of Friendica.
If this is nothing for you, you might be interested in:
* the Friendica Docker image (https://github.com/friendica/docker) or
* how install Friendica with YunoHost (https://github.com/YunoHost-Apps/friendica_ynh).

## 2. Install Friendica sources

Unpack the Friendica files into the root of your web server document area.

If you copy the directory tree to your webserver, make sure that you also copy 
`.htaccess-dist` - as "dot" files are often hidden and aren't normally copied.

OR

Clone the friendica/friendica GitHub repository and import dependencies

	git clone https://github.com/friendica/friendica -b master [web server folder]
	cd [web server folder]
	bin/composer.phar install --no-dev

Make sure the folder view/smarty3 exists and is writable by the webserver user,
in this case `www-data`

    mkdir view/smarty3
    chown www-data:www-data view/smarty3
    chmod 775 view/smarty3

Get the addons by going into your website folder.

    cd mywebsite

Clone the addon repository (separately):

    git clone https://github.com/friendica/friendica-addons.git -b master addon

If you want to use the development version of Friendica you can switch to the 
develop branch in the repository by running

    git checkout develop
    bin/composer.phar install
    cd addon
    git checkout develop

Please be aware that the develop branch is unstable.
Exercise caution when pulling.
If you encounter a bug, please let us know.

Either way, if you use Apache, copy `.htaccess-dist` to `.htaccess` to enable 
URL rewriting.

## 3. Database

Create an empty database and note the access details (hostname, username, password,
database name).

- Friendica needs the permission to create and delete fields and tables in its 
  own database.
- Please check the additional notes if running on MySQ 5.7.17 or newer

## 4. Config 

If you know in advance that it will be impossible for the web server to write or
create files in the `config/` directory, please create an empty file called 
`local.config.php` in it and make it writable by the web server.

## 5a. Install Wizard

Visit your website with a web browser and follow the instructions.
Please note any error messages and correct these before continuing.

If you are using SSL with a known signature authority (recommended), use the
https: link to your website. If you are using a self-signed cert or no cert,
use the http: link.

If you need to specify a port for the connection to the database, you can do so
in the host name setting for the database.

## 5b. Manual install

*If* the automated installation fails for any reason, please check the following:

- `config/local.config.php` exists
	- If not, copy `config/local-sample.config.php` to `config/local.config.php`
	  and edit it with your settings.
- Database is populated.
	- If not, import the contents of `database.sql` with phpMyAdmin or the mysql
	  command line tool.

## 6. Register the admin account

At this point visit your website again, and register your personal account with
the same email as in the `config.admin_email` config value.
Registration errors should all be recoverable automatically.

If you get any *critical* failure at this point, it generally indicates the
database was not installed correctly. You might wish to delete/rename 
`config/local.config.php` to another name and drop all the database tables so
that you can start fresh.

## 7. Background tasks (IMPORTANT)

Set up a cron job or scheduled task to run the worker once every 5-10 minutes to
pick up the recent "public" postings of your friends. Example:

	cd /base/directory; /path/to/php bin/worker.php

Change "/base/directory", and "/path/to/php" as appropriate for your situation.

If you are using a Linux server, run "crontab -e" and add a line like the one
shown, substituting for your unique paths and settings:

	*/10 * * * *	cd /home/myname/mywebsite; /usr/bin/php bin/worker.php

You can generally find the location of PHP by executing "which php".
If you have troubles with this section please contact your hosting provider for assistance.
Friendica will not work correctly if you cannot perform this step.

You should also be sure that `config.php_path` is set correctly, it should look
like this: (changing it to the correct PHP location)

	'config' => [
    	'php_path' => '/usr/local/php56/bin/php',
    ]

### worker alternative: daemon

If you can not use cronjobs, login in to the command line ("shell") on your remote server and start the Friendica daemon (background task) using the following command:

    cd /path/to/friendica; php bin/daemon.php start
Once started, you can check the daemon status using the following command:

    cd /path/to/friendica; php bin/daemon.php status
After a server restart or any other failure, the daemon needs to be restarted. This could be achieved by a cronjob.

## 8. (Recommended) Set up a backup plan

Bad things will happen.
Let there be a hardware failure, a corrupted database or whatever you can think of.
So once the installation of your Friendica node is done, you should make yourself
a backup plan.

The most important file is the `config/local.config.php` file in the base directory.
As it stores all your data, you should also have a recent dump of your Friendica
database at hand, should you have to recover your node.

## 9. (Optional) Reverse-proxying and HTTPS

Friendica looks for some well-known HTTP headers indicating a reverse-proxy
terminating an HTTPS connection.
While the standard from RFC 7239 specifies the use of the `Forwaded` header.

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
If the database resides on the same machine, check that the database server name
is "localhost".

### 500 Internal Error

This could be the result of one of our Apache directives not being supported by
your version of Apache. Examine your apache server logs.
You might remove the line "Options -Indexes" from the .htaccess file if you are
using a Windows server as this has been known to cause problems.
Also check your file permissions. Your website and all contents must generally
be world-readable.

It is likely that your web server reported the source of the problem in its error log files.
Please review these system error logs to determine what caused the problem.
Often this will need to be resolved with your hosting provider or (if self-hosted)
your web server configuration.

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

Create an empty `config/local.config.php`file with that name and give it
world-write permission.

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
> I hope this saves some time for other people using suhosin with function blacklists.

### Unable to create all mysql tables on MySQL 5.7.17 or newer

If the setup fails to create all the database tables and/or manual creation from
the command line fails, with this error:

	ERROR 1067 (42000) at line XX: Invalid default value for 'created'

You need to adjust your my.cnf and add the following setting under the [mysqld]
section:

	sql_mode = '';

After that, restart mysql and try again.
