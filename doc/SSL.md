Using SSL with Friendica
=====================================

* [Home](help)

## Disclaimer

**This document has been updated in November 2016.
SSL encryption is relevant for security.
This means that recommended settings change fast.
Keep your setup up to date and do not rely on this document being updated as fast as technologies change!**

## Intro

If you are running your own Friendica site, you may want to use SSL (https) to encrypt communication between servers and between yourself and your server.

There are basically two sorts of SSL certificates: Self-signed certificates and certificates signed by a certificate authority (CA).
Technically, both provide the same valid encryption.
There is a problem with self-signed certificates though:
They are neither installed in browsers nor on other servers.
That is why they provoke warnings about "mistrusted certificates".
This is confusing and disturbing.

For this reason, we recommend to get a certificate signed by a CA.
Normally, you have to pay for them - and they are valid for a limited period of time (e.g. a year or two).

There are ways to get a trusted certificate for free.

## Choose your domain name

Your SSL certificate will be valid for a domain or even only for a subdomain.
Make your final decision about your domain resp. subdomain *before* ordering the certificate.
Once you have it, changing the domain name means getting a new certificate.

### Shared hosts

If your Friendica instance is running on a shared hosting platform, you should first check with your hosting provider.
They have instructions for you on how to do it there.
You can always order a paid certificate with your provider.
They will either install it for you or provide an easy way to upload the certificate and the key via a web interface.
With some providers, you have to send them your certificate.
They need the certificate, the key and the CA's intermediate certificate.
To be sure, send those three files.
**You should send them to your provider via an encrypted channel!**

### Own server

If you run your own server, we recommend to check out the ["Let's Encrypt" initiative](https://letsencrypt.org/).
Not only do they offer free SSL certificates, but also a way to automate their renewal.
You need to install a client software on your server to use it.
Instructions for the official client are [here](https://certbot.eff.org/).
Depending on your needs, you might want to look at the [list of alternative letsencrypt clients](https://letsencrypt.org/docs/client-options/).

## Web server settings

Visit the [Mozilla's wiki](https://wiki.mozilla.org/Security/Server_Side_TLS) for instructions on how to configure a secure webserver.
They provide recommendations for [different web servers](https://mozilla.github.io/server-side-tls/ssl-config-generator/).

## Test your SSL settings

When you are done, visit the test site [SSL Labs](https://www.ssllabs.com/ssltest/) to have them check if you succeeded.

## Configure Friendica

If you can successfully access your Friendica instance through https, there are a number of steps you can take to ensure your users will use SSL to access your instance.

### Web server redirection

This is the simplest way to enforce site-wide secure access.
Every time a user tries to access any Friendica page by any mean (manual address bar entry or link), the web server issues a Permanent Redirect response with the secure protocol prepended to the requested URL.

With Apache, simply add the following lines to the [code].htaccess[/code] file in the root folder of your Friendica instance (thanks to [url=https://github.com/AlfredSK]AlfredSK[/url]):

[code]
#Force SSL connections

RewriteEngine On
RewriteCond %{SERVER_PORT} 80
RewriteRule ^(.*)$ https://your.friendica.domain/$1 [R=301,L]
[/code]

With nginx, configure your [code]server[/code] directive this way (thanks to [url=https://bjornjohansen.no/redirect-to-https-with-nginx/]Bjørn Johansen[/url]):

[code]
server {
	listen 80;
	listen [::]:80;
	server_name your.friendica.domain;
	return 301 https://$server_name$request_uri;
}
[/code]

### SSL Settings

In the Admin Settings, there are three SSL-related settings:
- **SSL link policy**: this affects how Friendica generates internal links. If your SSL installation was successful, we recommend "Force all links to SSL" just in case your web server configuration can't be altered like described above.
- **Force SSL**: This forces all external links to HTTPS, which may solve Mixed-Content issues, but not all websites support HTTPS yet. Use at your own risk.
- **Verify SSL**: Enabling this will prevent Friendica to interact with self-signed SSL sites. We recommend you leave it on as a self-signed SSL certificate can be a vectorfor a man-in-the-middle attack.