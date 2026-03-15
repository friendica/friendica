<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

return [
	'database' => [
		'hostname' => getenv('MYSQL_HOST'),
		'username' => getenv('MYSQL_USER'),
		'password' => getenv('MYSQL_PASSWORD'),
		'database' => getenv('MYSQL_DATABASE'),
		'charset' => 'utf8mb4',
	],

	// ****************************************************************
	// The configuration below will be overruled by the admin panel.
	// Changes made below will only have an effect if the database does
	// not contain any configuration for the friendica system.
	// ****************************************************************

	'config' => [
		'admin_email' => 'admin@example.com',
		'sitename' => 'Friendica Social Network',
		'register_policy' => \Friendica\Module\Register::OPEN,
		'register_text' => '',
		'php' => getenv('FRIENDICA_PHP_PATH'),
	],
	'system' => [
		'default_timezone' => 'UTC',
		'language' => 'en',
		'basepath' => getenv('PROJECT_DIR'),
		'url' => 'http://'.getenv('SERVER_NAME').':'.getenv('SERVER_PORT'),
	],
];
