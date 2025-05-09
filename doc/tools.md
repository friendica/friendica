Admin Tools
===========

* [Home](help)

Friendica Tools
---------------

Friendica has a build in command console you can find in the *bin* directory.
The console provides the following commands:

* cache:                  Manage node cache
* config:                 Edit site config
* createdoxygen:          Generate Doxygen headers
* dbstructure:            Do database updates
* docbloxerrorchecker:    Check the file tree for DocBlox errors
* extract:                Generate translation string file for the Friendica project (deprecated)
* globalcommunityblock:   Block remote profile from interacting with this node
* globalcommunitysilence: Silence remote profile from global community page
* archivecontact:         Archive a contact when you know that it isn't existing anymore
* help:                   Show help about a command, e.g (bin/console help config)
* autoinstall:            Starts automatic installation of friendica based on values from htconfig.php
* maintenance:            Set maintenance mode for this node
* newpassword:            Set a new password for a given user
* php2po:                 Generate a messages.po file from a strings.php file
* po2php:                 Generate a strings.php file from a messages.po file
* typo:                   Checks for parse errors in Friendica files
* postupdate:             Execute pending post update scripts (can last days)
* storage:                Manage storage backend
* relay:                  Manage ActivityPub relay servers

Please consult *bin/console help* on the command line interface of your server for details about the commands.

3rd Party Tools
---------------

In addition to the tools Friendica includes, some 3rd party tools can make your admin days easier.

### Fail2ban

Fail2ban is an intrusion prevention framework ([see Wikipedia](https://en.wikipedia.org/wiki/Fail2ban)) that you can use to forbid access to a server under certain conditions, e.g. 3 failed attempts to log in, for a certain amount of time.

The following configuration was [provided](https://forum.friendi.ca/display/174591b4135ae40c1ad7e93897572454) by Steffen K9 using Debian.
You need to adjust the *logpath* in the *jail.local* file and the *bantime* (value is in seconds).

In */etc/fail2ban/jail.local* create a section for Friendica:

	[friendica]
	enabled = true
	findtime = 300
	bantime  = 900
	filter = friendica
	port = http,https
	logpath = /var/log/friendica.log
	logencoding = utf-8

And create a filter definition in */etc/fail2ban/filter.d/friendica.conf*:

	[Definition]
	failregex = ^.*authenticate\: failed login attempt.*\"ip\"\:\"<HOST>\".*$
	ignoreregex =

Additionally you have to define the number of failed logins before the ban should be activated.
This is done either in the global configuration or for each jail separately.
You should inform your users about the number of failed login attempts you grant them.
Otherwise you'll get many reports about the server not functioning if the number is too low.

### Log rotation

If you have activated the logs in Friendica, be aware that they can grow to a significant size.
To keep them in control you should add them to the automatic [log rotation](https://en.wikipedia.org/wiki/Log_rotation), e.g. using the *logrotate* command.

In */etc/logrotate.d/* add a file called *friendica* that contains the configuration.
The following will compress */var/log/friendica* (assuming this is the location of the log file) on a daily basis and keep 2 days of back-log.

	/var/log/friendica.log {
		compress
		daily
		rotate 2
	}
