<?php

// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

return [
	'database' => [
		'hostname' => 'testhost',
		'username' => 'testuser',
		'password' => 'testpw',
		'database' => 'testdb',
		'charset' => 'utf8mb4',
	],
	'config' => [
		'admin_email' => 'admin@test.it',
		'sitename' => 'Friendica Social Network',
		'register_policy' => 2,
		'register_text' => '',
	],
	'system' => [
		'default_timezone' => 'UTC',
		'language' => 'en',
		'theme' => 'frio',
	],
];
