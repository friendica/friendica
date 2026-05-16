FROM php:8.4-apache

ARG DEBIAN_FRONTEND=noninteractive

# Install MariaDB client
RUN apt-get update \
        && apt-get install -y --no-install-recommends mariadb-client \
;

# Base packages
RUN apt-get install -y \
        vim \
        sudo \
        nano \
        git \
        gnupg2 \
        pinentry-curses \
;

# entrypoint.sh and cron.sh dependencies
RUN apt-get install -y --no-install-recommends \
        rsync \
        bzip2 \
        msmtp \
        tini \
;

RUN apt-get install -y --no-install-recommends \
        bash \
        libpng-dev \
        libjpeg62-turbo-dev \
        libtool \
        libmagick++-dev \
        libmemcached-dev \
        zlib1g-dev \
        libssl-dev \
        libgraphicsmagick1-dev \
        libfreetype6-dev \
        libwebp-dev \
        librsvg2-2 \
        libzip-dev \
        libldap2-dev \
        libgmp-dev \
        # libmagickcore-6.q16-6-extra \
;

RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
;

RUN docker-php-ext-install -j "$(nproc)" \
        pdo_mysql \
        gd \
        exif \
        zip \
        opcache \
        ctype \
        pcntl \
        ldap \
        gmp \
        intl \
 ;

# pecl will claim success even if one install fails, so we need to perform each install separately
RUN pecl install apcu-5.1.27;
RUN pecl install memcached-3.4.0;
RUN pecl install redis-6.2.0;
RUN pecl install imagick-3.8.0;

RUN docker-php-ext-enable \
        apcu \
        memcached \
        redis \
        imagick \
;

RUN apt-get clean -y && rm -rf /var/lib/apt/lists/*

ENV PHP_MEMORY_LIMIT 512M
ENV PHP_UPLOAD_LIMIT 512M

RUN { \
        echo 'opcache.enable=1' ; \
        echo 'opcache.interned_strings_buffer=8'; \
        echo 'opcache.max_accelerated_files=10000'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.save_comments=1'; \
        echo 'opcache.revalidte_freq=1'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini; \
    \
    echo 'apc.enable_cli=1' >> /usr/local/etc/php/conf.d/docker-php-ext-apcu.ini; \
    \
    { \
        echo 'memory_limit=${PHP_MEMORY_LIMIT}'; \
        echo 'upload_max_filesize=${PHP_UPLOAD_LIMIT}'; \
        echo 'post_max_size=${PHP_UPLOAD_LIMIT}'; \
    } > /usr/local/etc/php/conf.d/friendica.ini;

RUN ln -s /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini
RUN mkdir /var/www/data
RUN chmod -R g=u /var/www
