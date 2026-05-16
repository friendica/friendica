FROM php:8.2-fpm

# Install docker-php-extension-installer
COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/

# Update and install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    wget \
    unzip \
    mariadb-client \
    gettext \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions with docker-php-extension-installer
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

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Build arguments for UID/GID of the host user
ARG USER_UID=1000
ARG USER_GID=1000

# Assign www-data the same UID/GID as the host user
RUN groupmod -g ${USER_GID} www-data
RUN usermod -u ${USER_UID} -g ${USER_GID} www-data

RUN mkdir -p /var/www/.composer
RUN chown -R www-data:www-data /var/www

# Set the working directory
WORKDIR /var/www/html

# Set the user to www-data
USER www-data

EXPOSE 9000

CMD ["php-fpm"]
