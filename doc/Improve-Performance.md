How to: improve performance
==============

* [Home](help)

A little guide to increase the performance of a Friendica site

**At first**

Feel free to ask at Friendica support at https://helpers.pyxis.uberspace.de/profile/helpers if you need some clarification about the following instructions or if you need help in any other way.

System configuration
--------

Please go to /admin/site/ on your system and change the following values:

    Set "JPEG image quality" to 50.

This value reduces the data that is send from the server to the client. 50 is a value that doesn't influences image quality too much.

    Set "OStatus conversation completion interval" to "never".

If you have many OStatus contacts then completing of conversations can be very time wasting. The downside: You won't see every comment in OStatus threads.

    Set "Path for lock file" to an empty folder outside your web root.

Lock files help avoid the possibility of several background processes running at the same time.

For example: It can happen that the poller.php takes longer than expected. When there is no lock file, it is possible for several instances of poller.php to run at the same time - which would slow down the system and affect the maximum numbers of processes and database connections.

Please define a full file path that is writeable by the web server process. If your site is located at "/var/www/sitename/htdocs/" you could maybe create a folder "/var/www/sitename/temp/".

    Enable "Use MySQL full text engine"

When using MyISAM (default) this speeds up search.

    Set "Path to item cache" to an empty value outside your web root.

Parsed BBCode and some external images will be put there. Parsing BBCode is a time wasting process that also makes heave use of the CPU.

You can use the same folder you used for the lock file.

**Warning!**

The folder for item cache is cleaned up regularly. Every file that exceeds the cache duration is deleted. **If you accidentally point the cache path to your web root then you will delete your web root!**

So double check that the folder only contains temporary content that can be deleted at any time.

You have been warned.

P.S. It happened to me :)

Plugins
--------

Active the following plugins:

    Alternate Pagination
    Privacy Image Cache
    rendertime

###Alternate Pagination


**Description**

This plugin reduces the database load massively. Downside: You can't see the total number of pages available at each module, and have this replaced with "older" and "newer" links.

**Administration**

Go to the admin settings of "altpager" and set it to "global".

###Privacy Image Cache

**Description**

This plugin pre-fetches external content and stores it in the cache. Besides speeding up the page rendering it is also good for the privacy of your users, since embedded pictures are loaded from your site and not from a foreign site (that could spy on the IP addresses).

Additionally it helps with content from external sites that have slow performance or aren not online all the time.

**Administration**

Please create a folder named "privacy_image_cache" and "photo" in your web root. If these folders exists then the cached files will be stored there. This has the great advantage that your web server will fetch the files directly from there.


###rendertime

This plugin doesn't speed up your system. It helps analyzing your bottlenecks.

When enabled you see some values like the following at the bottom of every page:

    Performance: Database: 0.244, Network: 0.002, Rendering: 0.044, Parser: 0.001, I/O: 0.021, Other: 0.237, Total: 0.548

    Database: This is the time for all database queries
    Network: Time that is needed to fetch content from external sites
    Rendering: Time for theme rendering
    Parser: The time that the BBCode parser needed to create the output
    I/O: Time for local file access
    Others: Everything else :)
    Total: The sum of all above values

These values show your performance problems.

Webserver
--------

If you are using Apache please enable the following modules.

**Cache-Control**

This module tells the client to cache the content of static files so that they aren't fetched with every request.

Enable the module "mod_expires" by typing in "a2enmod expires" as root.

Please add the following lines to your site configuration in the "directory" context.

ExpiresActive on ExpiresDefault "access plus 1 week"

See also: http://httpd.apache.org/docs/2.2/mod/mod_expires.html

**Compress content**

This module compresses the traffic between the web server and the client.

Enable the module "mod_deflate" by typing in "a2enmod deflate" as root.

See also: http://httpd.apache.org/docs/2.2/mod/mod_deflate.html

PHP
--------

**FCGI**

When using apache think about using FCGI. When using a Debian based distribution you will need the packages named "php5-cgi" and "libapache2-mod-fcgid".

Please refer to external documentations for a more detailed explanation how to set up a system based upon FCGI.

**APC**

APC is an opcode cache. It speeds up the processing of PHP code.

When APC is enabled, Friendica uses it to store configuration data between different requests. This helps speeding up the page creation time.

**Database**

There are scripts like [tuning-primer.sh](http://www.day32.com/MySQL/) and [mysqltuner.pl](http://mysqltuner.pl) that analyzes your database server and give hints on values that could be changed.

Please enable the slow query log. This helps being aware of performance problems.
