#!/bin/bash

set -e

# set correct charset and collation
mariadb -h localhost -uroot -p"${MYSQL_ROOT_PASSWORD}" -e "ALTER DATABASE ${MYSQL_DATABASE} CHARACTER SET ${MYSQL_CHARSET} COLLATE ${MYSQL_COLLATION};"

# the PHPUnit suites use their own database, so a test run never touches the dev instance
MYSQL_TEST_DATABASE="${MYSQL_TEST_DATABASE:-test}"
mariadb -h localhost -uroot -p"${MYSQL_ROOT_PASSWORD}" -e "CREATE DATABASE IF NOT EXISTS \`${MYSQL_TEST_DATABASE}\` CHARACTER SET ${MYSQL_CHARSET} COLLATE ${MYSQL_COLLATION}; GRANT ALL PRIVILEGES ON \`${MYSQL_TEST_DATABASE}\`.* TO '${MYSQL_USER}'@'%';"
