Settings
===
Here are some of the built-in features which don't have an exposed interface or are otherwise undocumented.
Configuration settings are stored in the file ".htconfig.php".
Edit this file with a text editor to make the desired changes.
Several system settings are already documented in that file and will not be covered here. 

Hot Keys
---

Friendica traps the following keyboard events:

* [Pause] - Pauses "Ajax" update activity. This is the process that provides updates without reloading the page. You may wish to pause it to reduce network usage and/or as a debugging aid for javascript developers. A pause indicator will appear at the lower right hand corner of the page. Hit the [pause] key once again to resume.
* [F8] - Displays a language selector


Birthday Notifications
---

Birthday events are published on your Home page for any friends having a birthday in the coming 6 days.
In order for your birthday to be discoverable by all of your friends, you must set your birthday (at least the month and day) in your default profile.
You are not required to provide the year.

System settings
---

###Language

Please see util/README for information on creating language translations.

Config:

	$a->config['system']['language'] = 'name';

###System Theme

Choose a theme to be the default system theme. This can be over-ridden by user profiles.
Default theme is "default".

Config:

	$a->config['system']['theme'] = 'theme-name';

###Proxy Configuration Settings

If your site uses a proxy to connect to the internet, you may use these settings to communicate with the outside world.
The outside world still needs to be able to see your website, or this will not be very useful.

Config:

	$a->config['system']['proxy'] = "http://proxyserver.domain:port";
	$a->config['system']['proxyuser'] = "username:password";

###Network Timeout

How long to wait on a network communication before timing out.
Value is in seconds.
Default is 60 seconds.
Set to 0 for unlimited (not recommended).

Config:

	$a->config['system']['curl_timeout'] = 60;

###Banner/Logo

Set the content for the site banner.
The default logo is the Friendica logo and name.
You may wish to provide HTML/CSS to style and/or position this content, as it may not be themed by default. 

Config:

	$a->config['system']['banner'] = '<span id="logo-text">My Great Website</span>';

###Maximum Image Size

Maximum size in bytes of uploaded images.
The default is set to 0, which means no limits.

Config:

	$a->config['system']['maximagesize'] = 1000000;

###UTF-8 Regular Expressions

During registrations, full names are checked using UTF-8 regular expressions.
This requires PHP to have been compiled with a special setting to allow UTF-8 expressions.
If you are completely unable to register accounts, set no_utf to true.
The default is set to false (meaning UTF8 regular expressions are supported and working).
 
Config:

	$a->config['system']['no_utf'] = true;

###Check Full Names

You may find a lot of spammers trying to register on your site.
During testing we discovered that since these registrations were automatic, the "Full Name" field was often set to just an account name with no space between first and last name.
If you would like to support people with only one name as their full name, you may change this setting to true.
Default is false.
 
Config:

	$a->config['system']['no_regfullname'] = true;

###OpenID

By default, OpenID may be used for both registration and logins.
If you do not wish to make OpenID facilities available on your system (at all), set 'no_openid' to true.
Default is false.

Config:

	$a->config['system']['no_openid'] = true;

###Multiple Registrations

The ability to create "Pages" requires a person to register more than once.
Your site configuration can block registration (or require approval to register).
By default, logged in users can register additional accounts for use as pages.
These will still require approval if REGISTER_APPROVE is selected.
You may prohibit logged in users from creating additional accounts by setting 'block_extended_register' to true.
Default is false.
 
Config:

	$a->config['system']['block_extended_register'] = true;

Security settings
---

###Verify SSL Certitificates

By default Friendica allows SSL communication between websites that have "self-signed" SSL certificates.
For the widest compatibility with browsers and other networks we do not recommend using self-signed certificates, but we will not prevent you from using them.
SSL encrypts all the data transmitted between sites (and to your browser). This allows you to have completely encrypted communications, and also protect your login session from hijacking.
Self-signed certificates can be generated for free, without paying top-dollar for a website SSL certificate. 
However these aren't looked upon favourably in the security community because they can be subject to so-called "man-in-the-middle" attacks.
If you wish, you can turn on strict certificate checking.
This will mean you cannot connect (at all) to self-signed SSL sites.

Config:

	$a->config['system']['verifyssl'] = true;

Corporate/Edu enhancements
---

###Allowed Friend Domains

Comma separated list of domains which are allowed to establish friendships with this site.
Wildcards are accepted.
(Wildcard support on Windows platforms requires PHP5.3).
By default, any (valid) domain may establish friendships with this site.

Config:

	$a->config['system']['allowed_sites'] = "sitea.com, *siteb.com";

###Allowed Email Domains

Comma separated list of domains which are allowed in email addresses for registrations to this site.
This can lockout those who are not part of this organisation from registering here.
Wildcards are accepted.
(Wildcard support on Windows platforms requires PHP5.3).
By default, any (valid) email address is allowed in registrations.  

Config:

	$a->config['system']['allowed_email'] = "sitea.com, *siteb.com";

###Block Public

Set to true to block public access to all otherwise public personal pages on this site unless you are currently logged in.
This blocks the viewing of profiles, friends, photos, the site directory and search pages to unauthorised persons.
A side effect is that entries from this site will not appear in the global directory.
We recommend specifically disabling that also (setting is described elsewhere on this page).
Note: this is specifically for sites that desire to be "standalone" and do not wish to be connected to any other Friendica sites.
Unauthorised persons will also not be able to request friendship with site members.
Default is false.
Available in version 2.2 or greater.
 
Config:

	$a->config['system']['block_public'] = true;

###Force Publish

By default, each user can choose on their Settings page whether or not to have their profile published in the site directory.
This setting forces all profiles on this site to be listed in the site directory and there is no option provided to the user to change it.
Default is false.
 
Config:

	$a->config['system']['publish_all'] = true;

###Global Directory

This configures the URL to update the global directory, and is supplied in the default configuration.
The undocumented part is that if this is not set, the global directory is completely unavailable to the application.
This allows a private community to be completely isolated from the global network. 

	$a->config['system']['directory'] = 'http://dir.friendi.ca';

Developer Settings
---

### Debugging
Most useful when debugging protocol exchanges and tracking down other communications issues. 

Config:

	$a->config['system']['debugging'] = true;
	$a->config['system']['logfile'] = 'logfile.out';
	$a->config['system']['loglevel'] = LOGGER_DEBUG;

Turns on detailed debugging logs which will be stored in 'logfile.out' (which must be writeable by the webserver).
LOGGER_DEBUG will show a good deal of information about system activity but will not include detailed data.
You may also select LOGGER_ALL but due to the volume of information we recommend only enabling this when you are tracking down a specific problem.
Other log levels are possible but are not being used at the present time. 


###PHP error logging

Use the following settings to redirect PHP errors to a file. 

Config:

	error_reporting(E_ERROR | E_WARNING | E_PARSE );
	ini_set('error_log','php.out');
	ini_set('log_errors','1');
	ini_set('display_errors', '0');

This will put all PHP errors in the file php.out (which must be writeable by the webserver).
Undeclared variables are occasionally referenced in the program and therefore we do not recommend using E_NOTICE or E_ALL.
The vast majority of issues reported at these levels are completely harmless.
Please report to the developers any errors you encounter in the logs using the recommended settings above.
They generally indicate issues which need to be resolved. 

If you encounter a blank (white) page when using the application, view the PHP logs - as this almost always indicates an error has occurred.  
