FROM php:7.4-fpm

# Installiere docker-php-extension-installer
COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/

# Update und installiere System-Dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    wget \
    unzip \
    mariadb-client \
    && rm -rf /var/lib/apt/lists/*

# Installiere PHP-Erweiterungen mit docker-php-extension-installer
RUN install-php-extensions \
    apcu \
    gd \
    pdo \
    pdo_mysql \
    imagick \
    mysqli \
    gmp \
    zip \
    xml \
    ctype \
    json \
    mbstring \
    posix \
    curl \
    intl \
    opcache

# Installiere Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Build Arguments für UID/GID des Host-Users
ARG USER_UID=1000
ARG USER_GID=1000

# Ordne www-data die gleiche UID/GID wie der Host-User zu
RUN groupmod -g ${USER_GID} www-data
RUN usermod -u ${USER_UID} -g ${USER_GID} www-data

RUN mkdir -p /var/www/.composer
RUN chown -R www-data:www-data /var/www

# Setze das Arbeitsverzeichnis
WORKDIR /var/www/html

# Setze den User auf www-data
USER www-data

EXPOSE 9000

CMD ["php-fpm"]
