Installing Connectors (Twitter/GNU Social)
==================================================

* [Home](help)


Friendica uses addons to provide connectivity to some networks, such as Twitter or App.net. 

There is also a addon to post through to an existing account on a GNU Social service.
You only need this to post to an already existing GNU Social account, but not to communicate with GNU Social members in general.

All three addons require an account on the target network.
In addition you (or typically the server administrator) will need to obtain an API key to provide authenticated access to your Friendica server.

Site Configuration
---

Addons must be installed by the site administrator before they can be used.
This is accomplished through the site administration panel.

Each of the connectors also requires an "API key" from the service you wish to connect with.
Some addons allow you to enter this information in the site administration pages, while others may require you to edit your configuration file (.htconfig.php).
The ways to obtain these keys vary between the services, but they all require an existing account on the target service.
Once installed, these API keys can usually be shared by all site members.

The details of configuring each service follow (much of this information comes directly from the addon source files):

Twitter Addon for Friendica
---

* Author: Tobias Diekershoff
* tobias.diekershoff@gmx.net
* License: 3-clause BSD license

###Configuration
To use this addon you need a OAuth Consumer key pair (key & secret).
You can get it from [Twitter](https://twitter.com/apps).

Register your Friendica site as "Client" application with "Read & Write" access.
We do not need "Twitter as login".
When you've registered the app you get a key pair with an OAuth Consumer key and a secret key for your application/site.
Add this key pair to your global .htconfig.php:

	$a->config['twitter']['consumerkey'] = 'your consumer_key here';
	$a->config['twitter']['consumersecret'] = 'your consumer_secret here';

After this, your users can configure their Twitter account settings from "Settings -> Connector Settings".

###More documentation

Find the author's documentation here: [http://diekershoff.homeunix.net/redmine/wiki/friendikaplugin/Twitter_Plugin](http://diekershoff.homeunix.net/redmine/wiki/friendikaplugin/Twitter_Plugin)


GNU Social Addon for Friendica
---

* Author: Tobias Diekershoff
* tobias.diekershoff@gmx.net
* License: 3-clause BSD license

###Configuration

When the addon is activated the user has to acquire the following in order to connect to the GNU Social account of choice.

* The base URL for the GNU Social API, for quitter.se this is https://quitter.se/api/
* OAuth Consumer key & secret

To get the OAuth Consumer key pair the user has to

1 ask her Friendica admin if a pair already exists or 
2 has to register the Friendica server as a client application on the GNU Social server. 

This can be done from the account settings under "Settings -> Connections -> Register an OAuth client application -> Register a new application" on the GNU Social server.

During the registration of the OAuth client remember the following:

* Application names must be unique on the GNU Social site, so we recommend a Name of 'friendica-nnnn', replace 'nnnn' with a random number or your website name.
* there is no callback url
* register a desktop client
* with read & write access
* the Source URL should be the URL of your Friendica server

After the required credentials for the application are stored in the configuration you have to actually connect your Friendica account with GNU Social.
This is done from the Settings -> Connector Settings page.
Follow the Sign in with GNU Social button, allow access and then copy the security code into the box provided.
Friendica will then try to acquire the final OAuth credentials from the API. 

If successful, the addon settings will allow you to select to post your public messages to your GNU Social account (have a look behind the little lock symbol beneath the status "editor" on your Home or Network pages).
