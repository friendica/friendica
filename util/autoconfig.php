<?php

    $autoconfig_enabled = true;
    $db_host = getenv('MYSQL_HOST') . ':' . getenv('MYSQL_PORT');
    $db_user = getenv('MYSQL_USERNAME');
    $db_pass = getenv('MYSQL_PASSWORD');
    $db_data = getenv('MYSQL_DATABASE');
    $phpath = getenv('PHP_PATH');
    $timezone = getenv('TZ');
    $language = getenv('FRIENDICA_LANG');
    $adminmail = getenv('FRIENDICA_ADMINMAIL');
    $rino = 1;