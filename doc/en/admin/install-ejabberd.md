# ejabberd integration with Friendica credentials

[ejabberd](https://www.ejabberd.im/) is an XMPP chat server. Combined with the *xmpp* addon
it provides web-based chat for Friendica users. Credentials are checked via ejabberd's external
auth protocol: ejabberd spawns the `auth_ejabberd` daemon and communicates over stdin/stdout
using a small binary framing protocol.

## Deployment models

There are two deployment models depending on whether ejabberd and Friendica run on the same
host.

### Same host

ejabberd spawns the daemon directly. The daemon runs as the ejabberd user; either configure
ejabberd to run as the web server user (`www-data`), or give the ejabberd user read access to
Friendica's files and configure Friendica to log to syslog
(`system.logger_config = syslog` in your local config).

**ejabberd.yml:**
```yaml
auth_method: [external]
# Full path required — ejabberd spawns this via Erlang open_port without a shell.
extauth_program: "/usr/bin/php /path/to/friendica/bin/console.php auth_ejabberd"
extauth_pool_size: 5
# Friendica supports per-account XMPP application passwords; ejabberd's own auth
# cache would silently break them, so it must stay disabled.
auth_use_cache: false
```

No bridge, no extra tools needed. ejabberd manages the daemon lifecycle (restarts on crash).

### Separate host / containerized (cross-host)

When ejabberd and Friendica run on different hosts or in separate containers, the binary
extauth protocol must be bridged over TCP. The recommended production approach is
**systemd socket activation** on the Friendica host.

#### Friendica host — systemd socket unit

```ini
# /etc/systemd/system/friendica-extauth.socket
[Unit]
Description=Friendica ejabberd external auth socket

[Socket]
ListenStream=9000
# One PHP daemon per ejabberd pool worker (inetd style: stdin/stdout = socket).
Accept=yes

[Install]
WantedBy=sockets.target
```

```ini
# /etc/systemd/system/friendica-extauth@.service
[Unit]
Description=Friendica ejabberd external auth daemon (instance %i)

[Service]
# Run as the web server user so the daemon can write to Friendica's log file.
User=www-data
WorkingDirectory=/var/www/friendica
# StandardInput=socket wires the accepted connection to stdin/stdout — this is
# exactly what ejabberd's extauth protocol expects.
StandardInput=socket
StandardOutput=socket
ExecStart=/usr/bin/php /var/www/friendica/bin/console.php auth_ejabberd
# Pass Friendica's runtime config as environment (overrides local.config.php values).
Environment=FRIENDICA_URL=https://your.friendica.domain
Environment=MYSQL_HOST=localhost
Environment=MYSQL_DATABASE=friendica
Environment=MYSQL_USER=friendica
Environment=MYSQL_PASSWORD=secret
```

Enable and start:
```sh
systemctl enable --now friendica-extauth.socket
```

#### ejabberd host — ejabberd.yml

```yaml
auth_method: [external]
# socat bridges ejabberd's extauth stdin/stdout over TCP to the Friendica socket unit.
# Full path required — ejabberd spawns this without a shell.
extauth_program: "/usr/bin/socat STDIO TCP:friendica.host:9000"
extauth_pool_size: 5
auth_use_cache: false
```

`socat` is pre-installed in the official ejabberd Docker image. On a bare-metal ejabberd,
install it via your package manager (`apt install socat` / `apk add socat`).

## Disable XMPP registration

Users are managed exclusively by Friendica. Disable ejabberd's own registration to prevent
accounts being created outside Friendica:

```yaml
access_rules:
  register:
    deny: all
```

## Nickname escaping

ejabberd (and XMPP clients) encode special characters in JID local-parts. The daemon
translates these back before the database lookup:

| Character | XMPP encoding |
|-----------|---------------|
| space     | `%20`         |
| `@`       | `(a)`         |

## Application-specific XMPP passwords

Users can set a dedicated XMPP password.
This password is separate from their Friendica login and is checked automatically when the
main password fails.

Because these per-user passwords bypass ejabberd's auth cache, `auth_use_cache: false` is
mandatory. With caching enabled, a cached "wrong password" result can silently block a valid
XMPP application password for the cache lifetime.

## Deprecated: bin/auth_ejabberd.php

`bin/auth_ejabberd.php` is deprecated since 2026.08 and will be removed. Replace it with
`bin/console.php auth_ejabberd` in your `extauth_program` setting.
