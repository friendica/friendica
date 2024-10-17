#!/bin/bash

# 9p write files as 'vagrant:vagrant' always.
# let's run apache as 'vagrant' user to get around write permissions issues
sed -i 's/www-data/vagrant/g' /etc/apache2/envvars
systemctl restart apache2

# I can't find a way to set static ip with libvirt, so configure friendica
# with current ip
MYIP=$(hostname -I | tr -d ' ')
sed -i "s|'url' =>.*|'url' => 'http://$MYIP',|" /var/www/config/local.config.php
echo "########################"
echo
echo "  http://$MYIP/"
echo
echo "########################"
