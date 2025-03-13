#!/bin/bash
#
source $workspaceFolder/.devcontainer/.env

# Setup Friendica
echo ">>> Friendica Setup"

FRIENDICA_PHP_PATH=$(which php)
export FRIENDICA_PHP_PATH

envsubst < $workspaceFolder/.devcontainer/include/autoinstall.config.php > /tmp/autoinstall.config.php
cp $workspaceFolder/.devcontainer/include/00apcu.config.php $workspaceFolder/config/00apcu.config.php
cp $workspaceFolder/.devcontainer/include/01redis.config.php $workspaceFolder/config/01redis.config.php
cp $workspaceFolder/.devcontainer/include/zz-docker.config.php $workspaceFolder/config/zz-docker.config.php


cd $DocumentRoot

# copy the .htaccess-dist file to .htaccess so that rewrite rules work
cp $DocumentRoot/.htaccess-dist $DocumentRoot/.htaccess

bin/composer.phar install

# install friendica
bin/console autoinstall -f /tmp/autoinstall.config.php

# add users
# (disable a bunch of validation because this is a dev install, deh, it needs invalid emails and stupid passwords)
bin/console config system disable_email_validation 1
bin/console config system disable_password_exposed 1
bin/console user add "$ADMIN_NICK" "$ADMIN_NICK" "$ADMIN_NICK@$ServerAlias" en http://friendica.local/profile/$ADMIN_NICK
bin/console user password "$ADMIN_NICK" "$ADMIN_PASSW"
bin/console user add "$USER_NICK" "$USER_NICK" "$USER_NICK@$ServerAlias" en http://friendica.local/profile/$USER_NICK
bin/console user password "$USER_NICK" "$USER_PASSW"

# create log file
#mkdir -p $workspaceFolder/log
#touch $workspaceFolder/log/friendica.log
#chmod 666 $workspaceFolder/log/friendica.log
touch $workspaceFolder/friendica.log
chmod 666 $workspaceFolder/friendica.log

exit 0
