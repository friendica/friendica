# Combined ejabberd + Friendica auth daemon image (same-host production model).
#
# In production, ejabberd and Friendica run on the SAME host so ejabberd can spawn the
# Friendica auth daemon directly via its external-auth protocol (stdin/stdout) — no socat,
# no nc, no TCP bridge. This image reproduces that model in a single container: the PHP CLI
# lives next to ejabberd, installed through Alpine's own package manager (the same way you
# would `apt install php` on a bare-metal host), so `extauth_program` can call
# `php bin/console.php auth_ejabberd` directly.
FROM ghcr.io/processone/ejabberd:26.04

# php85 matches Friendica's supported PHP version. The extension set is the subset the auth
# daemon's bootstrap needs: PDO/MySQL for the credential lookup, plus the framework's core
# runtime deps (mbstring/ctype/dom/simplexml/intl/curl/gmp/...).
USER root
RUN apk add --no-cache \
        php85 \
        php85-cli \
        php85-pdo \
        php85-pdo_mysql \
        php85-mysqli \
        php85-mbstring \
        php85-ctype \
        php85-dom \
        php85-simplexml \
        php85-xml \
        php85-xmlreader \
        php85-xmlwriter \
        php85-intl \
        php85-curl \
        php85-gmp \
        php85-openssl \
        php85-posix \
        php85-iconv \
        php85-fileinfo \
        php85-session \
        php85-tokenizer \
        php85-phar \
        php85-gd \
        php85-zip \
        php85-sodium \
    # Stable /usr/bin/php so extauth_program does not depend on the versioned binary name.
    && ln -sf /usr/bin/php85 /usr/bin/php
USER ejabberd