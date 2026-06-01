# Install an ejabberd with synchronized credentials

[Ejabberd](https://www.ejabberd.im/) is a chat server that uses XMPP as messaging protocol that you can use with a large amount of clients.
In conjunction with the "xmpp" addon it can be used for a web based chat solution for your users.

## Installation

- Change its owner to whichever user is running the server, ie. ejabberd
```sh
chown ejabberd:ejabberd /path/to/friendica/bin/auth_ejabberd.php
```

- Change the access mode, so it is readable only to the user ejabberd and has exec
```sh
chmod 700 /path/to/friendica/bin/auth_ejabberd.php
```

- Edit your `ejabberd.yml` file, comment out your `auth_method` and add:
```yaml
auth_method: [external]
extauth_program: "/path/to/friendica/bin/console.php auth_ejabberd"
# Number of persistent auth daemons ejabberd keeps per virtual host. This pool is the
# upper bound on concurrent auth processes
extauth_pool_size: 5
# Friendica supports per-account application-specific (XMPP) passwords, which is
# incompatible with ejabberd's own auth cache, so it must stay disabled.
auth_use_cache: false
```

> **Note:** `bin/auth_ejabberd.php` is deprecated; use `bin/console.php auth_ejabberd`.
> The daemon bounds every outgoing HTTP request with `jabber.auth_http_timeout` (default 5s,
> see `static/defaults.config.php`) so a slow remote host can never keep a pooled worker busy
> past ejabberd's own extauth call timeout.

- Disable the module "mod_register" and disable the registration:
```
{access, register, [{deny, all}]}.
```

- Enable BOSH:
  - Enable the module "mod_http_bind"
  - Edit this line:
```
{5280, ejabberd_http,    [captcha, http_poll, http_bind]}
```

  - In your apache configuration for your site add this line:
```
ProxyPass /http-bind http://127.0.0.1:5280/http-bind retry=0
```

- Restart your ejabberd service, you should be able to log in with your friendica credentials

## Other hints

- if a user has a space or a @ in the nickname, the user has to replace these characters:
  - " " (space) is replaced with "%20"
  - "@" is replaced with "(a)"
