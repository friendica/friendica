<?php

// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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

[config]
admin_email = admin@test.it
INI;
