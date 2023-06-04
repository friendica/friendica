How to improve the performance of a Friendica site
==============

* [Home](help)

Feel free to ask in the [Friendica support group](https://forum.friendi.ca/profile/helpers) if you need some clarification about the following instructions or if you need help in any other way.

System configuration
--------

Please go to /admin/site/ on your system and change the following values:

    Set "JPEG image quality" to 50.

This value reduces the data that is send from the server to the client. 50 is a value that doesn't influences image quality too much.

    Set "OStatus conversation completion interval" to "never".

If you have many OStatus contacts then completing of conversations can take some time. Since you will miss several comments in OStatus threads, you maybe should consider the option "At post arrival" instead.

    Enable "Use MySQL full text engine"

When using MyISAM (default) or InnoDB on MariaDB 10 this speeds up search.

Addons
--------

Active the following addons:

    rendertime

### rendertime

This addon doesn't speed up your system.
It helps to analyze your bottlenecks.

When enabled you see some values at the bottom of every page.
They show your performance problems.

    Performance: Database: 0.244, Network: 0.002, Rendering: 0.044, Parser: 0.001, I/O: 0.021, Other: 0.237, Total: 0.548

    Database: This is the time for all database queries
    Network: Time that is needed to fetch content from external sites
    Rendering: Time for theme rendering
    Parser: The time that the BBCode parser needed to create the output
    I/O: Time for local file access
    Others: Everything else :)
    Total: The sum of all above values

Apache Webserver
--------

The following Apache modules are recommended:

### Cache-Control

This module tells the client to cache the content of static files so that they aren't fetched with every request.
Enable the module "mod_expires" by typing in "a2enmod expires" as root.
Please add the following lines to your site configuration in the "directory" context.

	ExpiresActive on ExpiresDefault "access plus 1 week"

Also see the Apache [2.2](http://httpd.apache.org/docs/2.2/mod/mod_expires.html) / [2.4](https://httpd.apache.org/docs/2.4/mod/mod_expires.html) documentation.

### Compress content

This module compresses the traffic between the web server and the client.
Enable the module "mod_deflate" by typing in "a2enmod deflate" as root.

Also see the Apache [2.2](http://httpd.apache.org/docs/2.2/mod/mod_deflate.html) / [2.4](https://httpd.apache.org/docs/2.4/mod/mod_deflate.html) documentation.

PHP
--------

### FCGI

When using Apache think about using FCGI.
In a Debian-based distribution you will need to install the packages named "php5-cgi" and "libapache2-mod-fcgid".

Please refer to external documentation for a more detailed explanation how to set up a system based upon FCGI.

### Database

There are scripts like [tuning-primer.sh](https://github.com/BMDan/tuning-primer.sh) and [mysqltuner.pl](https://github.com/major/MySQLTuner-perl/blob/master/mysqltuner.pl) that analyze your database server and give hints on values that could be changed.

Please enable the slow query log. This helps to find performance problems.
