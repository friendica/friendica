# Settings

* [Home](help)

If you are the admin of a Friendica node, you have access to the so called **Admin Panel** where you can configure your Friendica node.

On the front page of the admin panel you will see a summary of information about your node.
These information include the amount of messages currently being processed in the queues.
The first number is the number of messages which could not been delivered for various reasons.
They will be resend later.
You can have a quick glance into that second queus in the "Inspect Queue" section of the admin panel.
The second number represents the current number of jobs for the background workers.
These worker tasks are prioritised and are done accordingly.

Then you get an overview of the accounts on your node, which can be moderated in the "Users" section of the panel.
As well as an overview of the currently active addons
The list is linked, so you can have quick access to the plugin settings.
And finally you are informed about the version of Friendica you have installed.
If you contact the devs with a bug or problem, please also mention the version of your node.

The admin panel is seperated into subsections accessible from the side bar of the panel.

## Site

This section of the admin panel contains the main configuration of your Friendica node.
It is separated into several sub-section beginning with the basic settings at the top, advancing towards the bottom of the page.

Most configuration options have a help text in the admin panel.
Therefore this document does not yet cover all the options

### Basic Settings

#### Banner/Logo

Set the content for the site banner.
The default logo is the Friendica logo and name.
You may wish to provide HTML/CSS to style and/or position this content, as it may not be themed by default.

#### Language

This option will set the default language for the node.
It is used as fall back setting should Friendica fail to recognize the visitors preferences and can be overwritten by user settings.

The Friendica community offers some translations.
Some more compleate then others.
See [this help page](/help/translations) for more information about the translation process.

#### System Theme

Choose a theme to be the default system theme.
This can be over-ridden by user profiles.
Default theme is "duepunto zero" at the moment.

You may also want to set a special theme for mobile interfaces.
Which may or may not be neccessary depending of the mobile friendlyness of the desktop theme you have chosen.
The `vier` theme for instance is mobile friendly.

### Registration

#### Check Full Names

You may find a lot of spammers trying to register on your site.
During testing we discovered that since these registrations were automatic, the "Full Name" field was often set to just an account name with no space between first and last name.
If you would like to support people with only one name as their full name, you may change this setting to true.
Default is false.

#### OpenID

By default, OpenID may be used for both registration and logins.
If you do not wish to make OpenID facilities available on your system (at all), set 'no_openid' to true.
Default is false.

#### Multiple Registrations

The ability to create "Pages" requires a person to register more than once.
Your site configuration can block registration (or require approval to register).
By default, logged in users can register additional accounts for use as pages.
These will still require approval if the registration policy is set to *require approval*
You may prohibit logged in users from creating additional accounts by setting *block multible registrations* to true.
Default is false.

### File upload

#### Maximum Image Size

Maximum size in bytes of uploaded images.
The default is set to 0, which means no limits.

### Policies

#### Global Directory

This configures the URL to update the global directory, and is supplied in the default configuration.
The undocumented part is that if this is not set, the global directory is completely unavailable to the application.
This allows a private community to be completely isolated from the global network.

#### Force Publish

By default, each user can choose on their Settings page whether or not to have their profile published in the site directory.
This setting forces all profiles on this site to be listed in the site directory and there is no option provided to the user to change it.
Default is false.

#### Block Public

Set to true to block public access to all otherwise public personal pages on this site unless you are currently logged in.
This blocks the viewing of profiles, friends, photos, the site directory and search pages to unauthorised persons.
A side effect is that entries from this site will not appear in the global directory.
We recommend specifically disabling that also (setting is described elsewhere on this page).
Note: this is specifically for sites that desire to be "standalone" and do not wish to be connected to any other Friendica sites.
Unauthorised persons will also not be able to request friendship with site members.
Default is false.
Available in version 2.2 or greater.

#### Allowed Friend Domains

Comma separated list of domains which are allowed to establish friendships with this site.
Wildcards are accepted.
(Wildcard support on Windows platforms requires PHP5.3).
By default, any (valid) domain may establish friendships with this site.

This is useful if you want to setup a closed network for educational groups, cooperations and similar communities that don't want to commuicate with the rest of the network.

#### Allowed Email Domains

Comma separated list of domains which are allowed in email addresses for registrations to this site.
This can lockout those who are not part of this organisation from registering here.
Wildcards are accepted.
(Wildcard support on Windows platforms requires PHP5.3).
By default, any (valid) email address is allowed in registrations.

#### Allow Users to set remote_self

If you enable the `Allow Users to set remote_self` users can select Atom feeds from their contact list being their *remote self* in die advanced contact settings.
Which means that postings by the remote self are automatically reposted by Friendica in their names.

As admin of the node you can also set this flag directly in the database.
Before doing so, you should be sure you know what you do and have a backup of the database.

### Advanced

#### Proxy Configuration Settings

If your site uses a proxy to connect to the internet, you may use these settings to communicate with the outside world.
The outside world still needs to be able to see your website, or this will not be very useful.

#### Network Timeout

How long to wait on a network communication before timing out.
Value is in seconds.
Default is 60 seconds.
Set to 0 for unlimited (not recommended).

#### Verify SSL Certitificates

By default Friendica allows SSL communication between websites that have "self-signed" SSL certificates.
For the widest compatibility with browsers and other networks we do not recommend using self-signed certificates, but we will not prevent you from using them.
SSL encrypts all the data transmitted between sites (and to your browser).
This allows you to have completely encrypted communications, and also protect your login session from hijacking.
Self-signed certificates can be generated for free, without paying top-dollar for a website SSL certificate.
However these aren't looked upon favourably in the security community because they can be subject to so-called "man-in-the-middle" attacks.
If you wish, you can turn on strict certificate checking.
This will mean you cannot connect (at all) to self-signed SSL sites.

### Auto Discovered Contact Directory

### Performance

### Worker

### Relocate

## Users

This section of the panel let the admin control the users registered on the node.

If you have selected "Requires approval" for the *Register policy* in the general nodes configuration, new registrations will be listed at the top of the page.
There the admin can then approve or disapprove the request.

Below the new registration block the current accounts on the Friendica node are listed.
You can sort the user list by name, email, registration date, date of last login, date of last posting and the account type.
Here the admin can also block/unblock users from accessing the node or delete the accounts entirely.

In the last section of the page admins can create new accounts on the node.
The password for the new account will be send by email to the choosen email address.

## Plugins

This page is for selecting and configuration of extensions for Friendica which have to be placed into the `/addon` subdirectory of your Friendica installation.
You are presented with a long list of available addons.
The name of each addon is linked to a separate page for that addon which offers more informations and configuration possibilities.
Also shown is the version of the addon and an indicator if the addon is currently active or not.

When you update your node and the addons they may have to be reloaded.
To simplify this process there is a button at the top of the page to reload all active plugins.

## Themes

The Themes section of the admin panel works similar to the Plugins section but let you control the themes on your Friendica node.
Each theme has a dedicated suppage showing the current status, some information about the theme and a screen-shot of the Friendica interface using the theme.
Should the theme offer special settings, admins can set a global default value here.

You can activate and deactivate themes on their dedicated sub-pages thus making them available for the users of the node.
To select a default theme for the Friendica node, see the *Site* section of the admin panel.

## Additional Features

There are several optional features in Friendica like the *dislike* button.
In this section of the admin panel you can select a default setting for your node and eventually fix it, so users cannot change the setting anymore.

## DB Updates

Should the database structure of Friendica change, it will apply the changes automatically.
In case you are suspecious that the update might not have worked, you can use this section of the admin panel to check the situation.

## Inspect Queue

In the admin panel summary there are two numbers for the message queues.
The second number represents messages which could not be delivered and are queued for later retry.
If this number goes sky-rocking you might ask yourself which receopiant is not receiving.

Behind the inspect queue section of the admin panel you will find a list of the messages that could not be delivered.
The listing is sorted by the receipiant name so identifying potential broken communication lines should be simple.
These lines might be broken for various reasons.
The receiving end might be off-line, there might be a high system load and so on.

Don't panic!
Friendica will not queue messages for all time but will sort out *dead* nodes automatically after a while and remove messages from the queue then.

## Federation Statistics

The federation statistics page gives you a short summery of the nodes/servers/pods of the decentralized social network federation your node knows.
These numbers are not compleate and only contain nodes from networks Friendica federates directly with.

## Plugin Features

Some of the addons you can install for your Friendica node have settings which have to be set by the admin.
All those addons will be listed in this area of the admin panels side bar with their names.

## Logs

The log section of the admin panel is seperated into two pages.
On the first, following the "log" link, you can configure how much Friendica shall log.
And on the second you can read the log.

You should not place your logs into any directory that is accessible from the web.
If you have to, and you are using the default configuration from Apache, you should choose a name for the logfile ending in ``.log`` or ``.out``.
Should you use another web server, please make sure that you have the correct accessrules in place so that your log files are not accessible.

There are five different log levels: Normal, Trace, Debug, Data and All.
Specifying different verbosities of information and data written out to the log file.
Normally you should not need to log at all.
The *DEBUG* level will show a good deal of information about system activity but will not include detailed data.
In the *ALL* level Friendica will log everything to the file.
But due to the volume of information we recommend only enabling this when you are tracking down a specific problem.

**The amount of data can grow the filesize of the logfile quickly**.
You should set up some kind of [log rotation](https://en.wikipedia.org/wiki/Log_rotation) to keep the log file from growing too big.

**Known Issues**: The filename ``friendica.log`` can cause problems depending on your server configuration (see [issue 2209](https://github.com/friendica/friendica/issues/2209)).

By default PHP warnings and error messages are supressed.
If you want to enable those, you have to activate them in the ``.htconfig.php`` file.
Use the following settings to redirect PHP errors to a file.

Config:

	error_reporting(E_ERROR | E_WARNING | E_PARSE );
	ini_set('error_log','php.out');
	ini_set('log_errors','1');
	ini_set('display_errors', '0');

This will put all PHP errors in the file php.out (which must be writeable by the webserver).
Undeclared variables are occasionally referenced in the program and therefore we do not recommend using `E_NOTICE` or `E_ALL`.
The vast majority of issues reported at these levels are completely harmless.
Please report to the developers any errors you encounter in the logs using the recommended settings above.
They generally indicate issues which need to be resolved.

If you encounter a blank (white) page when using the application, view the PHP logs - as this almost always indicates an error has occurred.

## Diagnostics

In this section of the admin panel you find two tools to investigate what Friendica sees for certain ressources.
These tools can help to clarify communication problems.

For the *probe address* Friendica will display information for the address provided.

With the second tool *check webfinger* you can request information about the thing identified by a webfinger (`someone@example.com`).

# Exceptions to the rule

There are four exceptions to the rule, that all the config will be read from the data base.
These are the data base settings, the admin account settings, the path of PHP and information about an eventual installation of the node in a sub-directory of the (sub)domain.

## DB Settings

With the following settings, you specify the data base server, the username and passwort for Friendica and the database to use.

    $db_host = 'your.db.host';
    $db_user = 'db_username';
    $db_pass = 'db_password';
    $db_data = 'database_name';

## Admin users

You can set one, or more, accounts to be *Admin*.
By default this will be the one account you create during the installation process.
But you can expand the list of email addresses by any used email address you want.
Registration of new accounts with a listed email address is not possible.

    $a->config['admin_email'] = 'you@example.com, buddy@example.com';

## PHP Path

Some of Friendicas processes are running in the background.
For this you need to specify the path to the PHP binary to be used.

    $a->config['php_path'] = '{{$phpath}}';

## Subdirectory configuration

It is possible to install Friendica into a subdirectory of your webserver.
We strongly discurage you from doing so, as this will break federation to other networks (e.g. Diaspora, GNU Socia, Hubzilla)
Say you have a subdirectory for tests and put Friendica into a further subdirectory, the config would be:

    $a->path = 'tests/friendica';

## Other exceptions

Furthermore there are some experimental settings, you can read-up in the [Config values that can only be set in .htconfig.php](help/htconfig) section of the documentation.

