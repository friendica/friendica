<?php

    $autoconfig_enabled = true;
    $dbhost = notags(trim(getenv('DBHOST')));
    $dbuser = notags(trim(getenv('DBUSER')));
    $dbpass = notags(trim(getenv('DBPASS')));
    $dbdata = notags(trim(getenv('DBDATA')));
    $phpath = notags(trim(getenv('DBPATH')));
    $timezone = notags(trim(getenv('TZ')));
    $language = notags(trim(getenv('LANG')));
    $adminmail = notags(trim(getenv('ADMINMAIL')));
    $rino = 1;