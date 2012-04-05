Installing Connectors (Facebook/Twitter/StatusNet)
==================================================

* [Home](help)


Friendica uses plugins to provide connectivity to some networks, such as Facebook and Twitter. 

There is also a plugin to post through to an existing account on a Status.Net service. You do not require this to communicate with Status.Net members from Friendica - only if you wish to post to an existing account.

All three of these plugins require an account on the target network. In addition you (or typically the server administrator) will need to obtain an API key to provide authenticated access to your Friendica server.

**Site Configuration**

Plugins must be installed by the site administrator before they can be used. This is accomplished through the site administration panel.


Each of the connectors also requires an "API key" from the service you wish to connect with. Some plugins allow you to enter this information in the site administration pages, while others may require you to edit your configuration file (.htconfig.php). The method for obtaining these keys varies greatly - but almost always requires an existing account on the target service. Once installed, these API keys can usually be shared by all site members.


The details of configuring each service follows (much of this information comes directly from the plugin source files):

**Twitter Plugin for Friendica**

* Author: Tobias Diekershoff
* tobias.diekershoff@gmx.net

* License:3-clause BSD license

Configuration:
To use this plugin you need a OAuth Consumer key pair (key & secret)
you can get it from Twitter at https://twitter.com/apps

Register your Friendica site as "Client" application with "Read & Write" access.
We do not need "Twitter as login". When you've registered the app you get the
OAuth Consumer key and secret pair for your application/site.

Add this key pair to your global .htconfig.php

```
$a->config['twitter']['consumerkey'] = 'your consumer_key here';
$a->config['twitter']['consumersecret'] = 'your consumer_secret here';
```

After this, your user can configure their Twitter account settings
from "Settings -> Connector Settings".

Documentation: http://diekershoff.homeunix.net/redmine/wiki/friendikaplugin/Twitter_Plugin


**StatusNet Plugin for Friendica**

* Author: Tobias Diekershoff
* tobias.diekershoff@gmx.net

* License:3-clause BSD license

Configuration

When the addon is activated the user has to aquire the following in order to connect to the StatusNet account of choice.

* The base URL for the StatusNet API, for identi.ca this is https://identi.ca/api/
* OAuth Consumer key & secret

To get the OAuth Consumer key pair the user has to 

(a) ask her Friendica admin if a pair already exists or 
(b) has to register the Friendica server as a client application on the StatusNet server. 

This can be done from the account settings under "Settings -> Connections -> Register an OAuth client application -> Register a new application" on the StatusNet server.

During the registration of the OAuth client remember the following:

* Application names must be unique on the StatusNet site, so we recommend a Name of 'friendica-nnnn', replace 'nnnn' with a random number or your website name.
* there is no callback url
* register a desktop client
* with read & write access
* the Source URL should be the URL of your Friendica server

After the required credentials for the application are stored in the configuration you have to actually connect your Friendica account with StatusNet. This is done from the Settings -> Connector Settings page. Follow the Sign in with StatusNet button, allow access and then copy the security code into the box provided. Friendica will then try to acquire the final OAuth credentials from the API. 

If successful the addon settings will allow you to select to post your public messages to your StatusNet account (have a look behind the little lock symbol beneath the status "editor" on your Home or Network pages).

Documentation: http://diekershoff.homeunix.net/redmine/wiki/friendikaplugin/StatusNet_Plugin



**Installing the Friendica/Facebook connector**

* register an API key for your site from developer.facebook.com

This requires a Facebook account, and may require additional authentication in the form of credit card or mobile phone verification. 

a. We'd be very happy if you include "Friendica" in the application name
to increase name recognition. The Friendica icons are also present
in the images directory and may be uploaded as a Facebook app icon.
Use images/friendica-16.jpg for the Icon and images/friendica-128.jpg for the Logo.

b. The url should be your site URL with a trailing slash.

You **may** be required to provide a privacy and/or terms of service URL.

c. Set the following values in your .htconfig.php file

```
$a->config['facebook']['appid'] = 'xxxxxxxxxxx';
$a->config['facebook']['appsecret'] = 'xxxxxxxxxxxxxxx';
```

Replace with the settings Facebook gives you.

d. Navigate to Set Web->Site URL & Domain -> Website Settings.  Set Site URL
to yoursubdomain.yourdomain.com.  Set Site Domain to your yourdomain.com.


On Friendica, visit the Facebook Settings section of the "Settings->Connector Settings" page. And click 'Install Facebook Connector'.

This will ask you to login to Facebook and grant permission to the
plugin to do its stuff. Allow it to do so.

You're done. To turn it off visit the Connector Settings page again and
'Remove Facebook posting'.

Videos and embeds will not be posted if there is no other content. Links
and images will be converted to a format suitable for the Facebook API and
long posts truncated - with a link to view the full post.

Facebook contacts will also not be able to view "private" photos, as they are not able to authenticate to your site to establish identity. We will address this in a future release.




 


