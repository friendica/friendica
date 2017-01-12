Using SSL with Friendica
=====================================

* [Home](help)

Disclaimer
---
**This document has been updated in November 2015.
SSL encryption is relevant for security.
This means that recommended settings change fast.
Keep your setup up to date and do not rely on this document being updated as fast as technologies change!**

Intro
---
If you are running your own Friendica site, you may want to use SSL (https) to encrypt communication between servers and between yourself and your server.

There are basically two sorts of SSL certificates: Self-signed certificates and certificates signed by a certificate authority (CA).
Technically, both provide the same valid encryption.
There is a problem with self-signed certificates though:
They are neither installed in browsers nor on other servers.
That is why they provoke warnings about "mistrusted certificates".
This is confusing and disturbing.

For this reason, we recommend to get a certificate signed by a CA.
Normally, you have to pay for them - and they are valid for a limited period of time (e.g. a year or two).

There is a way to get a trusted certificate for free.

Chose your domain name
---

Your SSL certificate will be valid for a domain or even only for a subdomain.
Make your final decision about your domain resp. subdomain *before* ordering the certificate.
Once you have it, changing the domain name means getting a new certificate.

Shared hosts
---

If your Friendica instance is running on a shared hosting platform, you should first check with your hosting provider.
They have instructions for you on how to do it there.
You can always order a paid certificate with your provider.
They will either install it for you or provide an easy way to upload the certificate and the key via a web interface.


It might be worth asking if your provider would install a certificate you provide yourself, to save money.
If so, read on.

Let's encrypt
---

If you run your own server, the "Let's encrypt" initiative might become an interesting alternative.
Their offer is in public beta right now.
Check out [their website](https://letsencrypt.org/) for status updates.

Web server settings
---

Visit the [Mozilla's wiki](https://wiki.mozilla.org/Security/Server_Side_TLS) for instructions on how to configure a secure webserver.
They provide recommendations for [different web servers](https://wiki.mozilla.org/Security/Server_Side_TLS#Recommended_Server_Configurations).

Test your SSL settings
---

When you are done, visit the test site [SSL Labs](https://www.ssllabs.com/ssltest/) to have them check if you succeeded.
