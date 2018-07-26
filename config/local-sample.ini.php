<?php return <<<INI

; If automatic system installation fails:

; Copy this file to local.ini.php

; Why local.ini.php? Because it contains sensitive information which could
; give somebody complete control of your database. Apache's default
; configuration will interpret any .php file as a script and won't show the values

; Then set the following for your MySQL installation

[database]
hostname = localhost
username = mysqlusername
password = mysqlpassword
database = mysqldatabasename
charset = utf8mb4


; ****************************************************************
; The configuration below will be overruled by the admin panel.
; Changes made below will only have an effect if the database does
; not contain any configuration for the friendica system.
; ****************************************************************

[config]
admin_email =

sitename = Friendica Social Network

register_policy = REGISTER_OPEN
register_text =

[system]
default_timezone = UTC

language = en

INI;
// Keep this line
