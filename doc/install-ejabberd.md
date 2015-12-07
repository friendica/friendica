Install an ejabberd with synchronized credentials
=================================================

* [Home](help)

[Ejabberd](https://www.ejabberd.im/) is a chat server that uses XMPP as messaging protocol that you can use with a large amount of clients. In conjunction 
with the "xmpp" addon it can be used for a web based chat solution for your users.

Installation
------------

- Change it's owner to whichever user is running the server, ie. ejabberd

        $ chown ejabberd:ejabberd /path/to/friendica/include/auth_ejabberd.php

- Change the access mode so it is readable only to the user ejabberd and has exec

        $ chmod 700 /path/to/friendica/include/auth_ejabberd.php

- Edit your ejabberd.cfg file, comment out your auth_method and add:

        {auth_method, external}.
        {extauth_program, "/path/to/friendica/include/auth_ejabberd.php"}.

- Disable the module "mod_register" and disable the registration:

        {access, register, [{deny, all}]}.

- Enable BOSH:
  - Enable the module "mod_http_bind"
  - Edit this line:

        {5280, ejabberd_http,    [captcha, http_poll, http_bind]}

  - In your apache configuration for your site add this line:

        ProxyPass /http-bind http://127.0.0.1:5280/http-bind retry=0

- Restart your ejabberd service, you should be able to login with your friendica credentials

Other hints
-----------
- if a user has a space or a @ in the nickname, the user has to replace these characters:
  - " " (space) is replaced with "%20"
  - "@" is replaced with "(a)"
