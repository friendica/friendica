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

There are ways to get a trusted certificate for free.

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

Getting a free StartSSL certificate
---
StartSSL is a certificate authority that issues certificates for free.
They are valid for a year and are sufficient for our purposes.

### Step 1: Create a client certificate

When you initially sign up with StartSSL, you receive a certificate that is installed in your browser.
You need it for the login on startssl.com, also when coming back to the site later.
It has nothing to do with the SSL certificate for your server.

### Step 2: Validate your email address and your domain

To continue you have to prove that you own the email address you specified and the domain that you want a certificate for.
Specify your email address, request a validation link via email from the "validations wizard".
Same procedure for the domain validation.

### Step 3: Request the certificate

Go to the "certificates wizard".
Choose the target web server.
When you are first prompted for a domain to certify, you need to enter your main domain, e.g. example.com.
In the next step, you will be able to specify a subdomain for Friendica, if needed.
Example: If you have friendica.example.com, you first enter example.com, then specify the subdomain friendica later.

If you know how to generate an openssl key and a certificate signing request (csr) yourself, do so.
Paste the csr into your browser to get it signed by StartSSL.

If you do not know how to generate a key and a csr, accept StartSSL's offer to generate it for you.
This means: StartSSL has the key to your encryption but it is better than no certificate at all.
Download your certificate from the website.
(Or in the second case: Download your certificate and your key.)

To install your certificate on a server, you need one or two extra files: sub.class1.server.ca.pem and ca.pem, delivered by startssl.com
Go to the "Tool box" section and download "Class 1 Intermediate Server CA" and "StartCom Root CA (PEM encoded)".

If you want to send your certificate to your hosting provider, they need the certificate, the key and probably at least the intermediate server CA.
To be sure, send those three and the ca.pem file.
**You should send them to your provider via an encrypted channel!**

If you run your own server, upload the files and check out the Mozilla wiki link below.

Let's encrypt
---

If you run your own server and you control your name server, the "Let's encrypt" initiative might become an interesting alternative.
Their offer is not ready, yet.
Check out [their website](https://letsencrypt.org/) for status updates.

Web server settings
---

Visit the [Mozilla's wiki](https://wiki.mozilla.org/Security/Server_Side_TLS) for instructions on how to configure a secure webserver.
They provide recommendations for [different web servers](https://wiki.mozilla.org/Security/Server_Side_TLS#Recommended_Server_Configurations).

Test your SSL settings
---

When you are done, visit the test site [SSL Labs](https://www.ssllabs.com/ssltest/) to have them check if you succeeded.
