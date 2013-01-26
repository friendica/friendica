Using SSL with Friendica
=====================================

* [Home](help)

If you are running your own Friendica site, you may want to use SSL (https) to encrypt communication between yourself and your server (communication between servers is encrypted anyway).

To do that on a domain of your own, you have to obtain a certificate from a trusted organization (so-called self-signed certificates that are popular among geeks don’t work very well with Friendica, because they can cause disturbances in other people's browsers).

If you are reading this document before actually installing Friendica, you might want to consider a very simple option: Go for a shared hosting account without your own domain name. That way, your address will be something like yourname.yourprovidersname.com, which isn't very fancy compared to yourname.com. But it will still be your very own site, and you will usually be able to hitch a lift on your provider's SSL certificate. That means that you won't need to configure SSL at all - it will simply work out of the box when people type https instead of http.

If that isn't your idea of doing things, read on...

**Shared hosts**

If you are using a shared host on a domain of your own, your provider may well offer to obtain and install the certificate for you. You will then only need to apply and pay for it – and everything will be set up. If that is the case for you, the rest of this document need not concern you at all. Just make sure the certificate is for the address that Friendica uses: e.g. myownfriendica.com or friendica.myserver.com.

The above ought to be the most common scenario for Friendica sites, making the rest of this article superfluous for most people.

**Obtaining a certificate yourself**

Alternatively, a few shared hosting providers may ask you to obtain and upload the certificate yourself.

The next section describes the process of acquiring a certificate from StartSSL. The good thing about StartSSL is that you can get an entry-level, but perfectly sufficient certificate for free. That’s not the case with most other certificate issuers - so we will be concentrating on StartSSL in this document If you want to use a certificate from a different source, you will have to follow the instructions given by that organization. We can't cover every possibility here.

Installing your certificate - once you have obtained it - depends on your provider’s way of doing things. But for shared hosts, there will usually be an easy web tool for this.

Note: Your certificate is usually restricted to one subdomain. When you apply for the certificate, make sure it’s for the domain and subdomain Friendica uses: e.g. myownfriendica.com or friendica.myserver.com.

**Getting a free StartSSL certificate**

StartSSL’s website attempts to guide you through the process of obtaining a free certificate, but some people end up frustrated. We really recommend working your way through the steps on the site very slowly and carefully. Don't take things for granted - read every word before proceeding and don't close the browser window until everything is working. That said, there are three main stumbling blocks that can confuse users:

When you initially sign up with StartSSL, the first certificate you receive is simply installed in your browser (though you should also store it somewhere safe, so that you can reinstall it in any other browser at a later date, for instance when you need to renew something). This authentication certificate is only used for logging on to the StartSSL website – it has nothing to do with the certificate you will need for your server. As a first-timer with StartSSL, start here: https://www.startssl.com/?app=12 and choose the Express Lane option to get that browser authentication certificate. Then seamlessly continue to the process of acquiring the desired certificate for your server (the one you actually came for). You can change the website’s language if that makes things easier for you.

When you are first prompted for a domain to certify, you need to enter your top-level domain – not the subdomain Friendica uses. In the next step, you will be able to specify that subdomain. So if you have friendica.yourname.com on your server, you first enter yourname.com – and specify the subdomain friendica later.

Don’t quit too fast when you have received your personal web server certificate at the end of the procedure. Depending on your server software, you will also require one or two generic files for use with this free StartSSL certificate. These are sub.class1.server.ca.pem and ca.pem. If you have already overlooked this step, you can download those files here: http://www.startssl.com/?app=21 But once again, the very best way of doing things is not to quit the StartSSL site until you are completely done and your https certificate is up and working.

**Virtual private and dedicated servers (using StartSSL free)**

The rest of this document is slightly more complicated, but it’s only for people running Friendica on a virtual private or dedicated server. Everyone else can stop reading at this point.

Follow the instructions here ( http://www.startssl.com/?app=20 ) to configure the web server you are using (e.g. Apache) for your certificate.

To illustrate the necessary changes, we will now assume you are running Apache. In essence, you can simply create a second httpd.conf entry for Friendica.

To do this, you copy the existing one and change the end of the first line to read :443> instead of :80>, then add the following lines to that entry, as also shown in StartSSL’s instructions:

	SSLEngine on
	SSLProtocol all -SSLv2
	SSLCipherSuite ALL:!ADH:!EXPORT:!SSLv2:RC4+RSA:+HIGH:+MEDIUM

	SSLCertificateFile /usr/local/apache/conf/ssl.crt
	SSLCertificateKeyFile /usr/local/apache/conf/ssl.key
	SSLCertificateChainFile /usr/local/apache/conf/sub.class1.server.ca.pem
	SSLCACertificateFile /usr/local/apache/conf/ca.pem
	SetEnvIf User-Agent ".*MSIE.*" nokeepalive ssl-unclean-shutdown
	CustomLog /usr/local/apache/logs/ssl_request_log \ 
	"%t %h %{SSL_PROTOCOL}x %{SSL_CIPHER}x \"%r\" %b"

(Note that the directory /usr/local/apache/conf/ may not exist on your machine. For Debian, for instance, the directory might be /etc/apache2/ - in which you can create an ssl subdirectory if it doesn’t already exist. Then you have /etc/apache2/ssl/… instead of /usr/local/apache/conf/…)

You thus end up with two entries for your Friendica site - one for simple http and one for https.

Note to those who want to force SSL: Don't redirect to SSL in your Apache settings. Friendica's own admin panel has a special setting for SSL policy. Please use this facility instead.

**Mixing certificates on Apache – StartSSL and others (self-signed)**

Many people using a virtual private or dedicated server will be running more than Friendica on it. They will probably want to use SSL for other sites they run on the server, too. To achieve this, they may wish to employ more than one certificate with a single IP – for instance, a trusted one for Friendica and a self-signed certificate for personal stuff (possibly a wildcard certificate covering arbitrary subdomains).

For this to work, Apache offers a NameVirtualHost directive. You can see how to use it in httpd.conf in the following pattern. Note that wildcards (*) in httpd.conf break the NameVirtualHost method – you can’t use them in this new configuration. In other words, no more *80> or *443>. And you really must specify the IP, too, even if you only have one. Also note that you will soon be needing two additional NameVirtualHost lines at the top of the file to cater for IPv6.

	NameVirtualHost 12.123.456.1:443
	NameVirtualHost 12.123.456.1:80

	<VirtualHost www.anywhere.net:80>
	DocumentRoot /var/www/anywhere
	Servername www.anywhere.net
	</VirtualHost>

	<VirtualHost www.anywhere.net:443>
	DocumentRoot /var/www/anywhere
	Servername www.anywhere.net 
	SSLEngine On
	<pointers to a an eligible cert>
	<more ssl stuff >
	<other stuff>
	</VirtualHost>

	<VirtualHost www.somewhere-else.net:80>
	DocumentRoot /var/www/somewhere-else
	Servername www.somewhere-else.net
	</VirtualHost>

	<VirtualHost www.somewhere-else:443>
	DocumentRoot /var/www/somewhere-else
	Servername www.somewhere-else.net
	SSLEngine On
	<pointers to another eligible cert>
	<more ssl stuff >
	<other stuff>
	</VirtualHost>

Of course, you may optionally be using other places like the sites-available directory to configure Apache, in which case only some of this information need be in httpd.conf or ports.conf - specifically, the NameVirtualHost lines must be there. But if you're savvy about alternatives like that, you will probably be able to figure out the details yourself.

Just restart Apache when you're done, whichever way you decide to do it.

**StartSSL on Nginx**

First, update to the latest Friendica code. Then follow the above instructions to get your free certificate. But instead of following the Apache installation instructions, do this:

Upload your certificate. It doesn't matter where to, as long as Nginx can find it. Some people use /home/randomlettersandnumbers to keep it in out of paranoia, but you can put it anywhere, so we'll call it /foo/bar.

You can remove the password if you like. This is probably bad practice, but if you don't, you'll have to enter the password every time you restart nginx. To remove it:

	openssl rsa -in ssl.key-pass -out ssl.key

Now, grab the helper certificate:

	wget http://www.startssl.com/certs/sub.class1.server.ca.pem

Now you need to merge the files:

	cat ssl.crt sub.class1.server.ca.pem > ssl.crt

In some configurations there is a bug, and this doesn't quite work properly. You may now need to edit ssl.crt, so:

	nano /foo/bar/ssl.crt

You'll see two certificates in the same file. Halfway down, you may see:

	-----END CERTIFICATE----------BEGIN CERTIFICATE-----

This is bad. You need to see:

	-----END CERTIFICATE-----
	-----BEGIN CERTIFICATE-----

You can enter the carriage return manually if the bug is present on your system. Note there is a single carriage return for -----BEGIN CERTIFICATE----- to start on a new line. There is no empty line.

Now you need to tell Nginx about the certs.

In /etc/nginx/sites-available/foo.com.conf you need something like:

	server {
	
	listen 80;
	
	listen 443 ssl;

	listen [::]:80;

	listen [::]:443 ipv6only=on ssl;

	ssl_certificate /foo/bar/ssl.crt;

	ssl_certificate_key /foo/bar/ssl.key;

	...

Now, restart nginx:

	/etc/init.d/nginx restart

And that's it.

For multiple domains, we have it easier than Apache users: Just repeat the above for each certificate, and keep it in it's own {server...} section.