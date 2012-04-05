Friendica Installation

We've tried very hard to ensure that Friendica will run on commodity hosting platforms - such as those used to host Wordpress blogs and Drupal websites. But be aware that Friendica is more than a simple web application. It is a complex communications system which more closely resembles an email server than a web server. For reliability and performance, messages are delivered in the background and are queued for later delivery when sites are down. This kind of functionality requires a bit more of the host system than the typical blog. Not every PHP/MySQL hosting provider will be able to support Friendica. Many will. But **please** review the requirements and confirm these with your hosting provider prior to installation.

Also if you encounter installation issues, please let us know via the forums at http://groups.google.com/group/friendica or file an issue at http://bugs.friendica.com . Please be as clear as you can about your operating environment and provide as much detail as possible about any error messages you may see, so that we can prevent it from happening in the future. Due to the large variety of operating systems and PHP platforms in existence we may have only limited ability to debug your PHP installation or acquire any missing modules - but we will do our best to solve any general code issues.   

Before you begin: Choose a domain name or subdomain name for your server. Put some thought into this - because changing it after installation is currently not-supported. Things will break, and some of your friends may have difficulty communicating with you. We plan to address this limitation in a future release.


1. Requirements
    - Apache with mod-rewrite enabled and "Options All" so you can use a
local .htaccess file

    - PHP  5.2+. The later the better. You'll need 5.3 for encryption of key exchange conversations. On a Windows environment, 5.2+ might not work as the function dns_get_record() is only available with version 5.3.
        - PHP *command line* access with register_argc_argv set to true in the
php.ini file
        - curl, gd, mysql, and openssl extensions
        - some form of email server or email gateway such that PHP mail() works
        - mcrypt (optional; used for server-to-server message encryption)

    - Mysql 5.x

    - ability to schedule jobs with cron (Linux/Mac) or Scheduled Tasks
(Windows) [Note: other options are presented in Section 7 of this document] 

    - Installation into a top-level domain or sub-domain (without a
directory/path component in the URL) is preferred. Directory paths will
not be as convenient to use and have not been thoroughly tested.


    [Dreamhost.com offers all of the necessary hosting features at a
reasonable price. If your hosting provider doesn't allow Unix shell access,
you might have trouble getting everything to work.]

2. Unpack the Friendica files into the root of your web server document area.

    - If you are able to do so, we recommend using git to clone the source repository rather than to use a packaged tar or zip file. This makes the software much easier to update. The Linux command to clone the repository into a directory "mywebsite" would be 

        `git clone https://github.com/friendica/friendica.git mywebsite`

    - and then you can pick up the latest changes at any time with

        `git pull`
    
    - For installing addons
    
        - First you should be **on** your website folder
        
            `cd mywebsite`
            
        - Then you should clone the addon repository (separtely)
        
            `git clone https://github.com/friendica/friendica-addons.git addon`
            
        - For keeping the addon tree updated, you should be on you addon tree and issue a git pull
        
            `cd mywebsite/addon`
            
            `git pull`
            
    - If you copy the directory tree to your webserver, make sure
    that you also copy .htaccess - as "dot" files are often hidden
    and aren't normally copied.


3. Create an empty database and note the access details (hostname, username, password, database name).

4. Visit your website with a web browser and follow the instructions. Please note any error messages and correct these before continuing.

5. *If* the automated installation fails for any reason, check the following:

    - ".htconfig.php" exists ... If not, edit htconfig.php and change system settings. Rename
to .htconfig.php
    - Database is populated. ... If not, import the contents of "database.sql" with phpmyadmin
or mysql command line

6. At this point visit your website again, and register your personal account.
Registration errors should all be recoverable automatically.
If you get any *critical* failure at this point, it generally indicates the
database was not installed correctly. You might wish to move/rename
.htconfig.php to another name and empty (called 'dropping') the database
tables, so that you can start fresh.

7. Set up a cron job or scheduled task to run the poller once every 5-10
minutes in order to perform background processing. Example:

    `cd /base/directory; /path/to/php include/poller.php`

Change "/base/directory", and "/path/to/php" as appropriate for your situation.

If you are using a Linux server, run "crontab -e" and add a line like the
one shown, substituting for your unique paths and settings:

`*/10 * * * * cd /home/myname/mywebsite; /usr/bin/php include/poller.php`

You can generally find the location of PHP by executing "which php". If you
have troubles with this section please contact your hosting provider for
assistance. Friendica will not work correctly if you cannot perform this step.

Alternative: You may be able to use the 'poormancron' plugin to perform this step 
if you are using a recent Friendica release. To do this, edit the file ".htconfig.php"
and look for a line describing your plugins. On a fresh installation, it will look like

`$a->config['system']['addon'] = 'js_upload';`

This indicates the "js_upload" addon module is enabled. You may add additional 
addons/plugins using this same line in the configuration file. Change it to read

`$a->config['system']['addon'] = 'js_upload,poormancron';`

and save your changes.    
