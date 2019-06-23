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
theme = changed
newKey = newValue

[config]
admin_email = admin@overwritten.local
INI;
