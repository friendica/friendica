#!/bin/bash

set -e

# set correct charset and collation
mariadb -h localhost -uroot -p"${MYSQL_ROOT_PASSWORD}" -e "ALTER DATABASE ${MYSQL_DATABASE} CHARACTER SET ${MYSQL_CHARSET} COLLATE ${MYSQL_COLLATION};"
