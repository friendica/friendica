<?php
/**
 * A test local ini file
 */

return <<<INI

[database]
hostname = testhost
username = testuser
password = testpw
database = testdb

[system]
theme = frio
no_regfullname = true
numeric = 2.5
allowed_themes = quattro,vier,duepuntozero

[config]
admin_email = admin@test.it
register_policy = 2
max_import_size = 999
INI;
