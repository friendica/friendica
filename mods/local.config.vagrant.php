<?php

// Local configuration

/* If automatic system installation fails:
 *
 * Copy this file to local.config.php
 *
 * Why local.config.php? Because it contains sensitive information which could
 * give somebody complete control of your database. Apache's default
 * configuration will interpret any .php file as a script and won't show the values
 *
 * Then set the following for your MySQL installation
 */

return [
	'database' => [
		'hostname' => 'localhost',
		'username' => 'friendica',
		'password' => 'friendica',
		'database' => 'friendica',
		'charset' => 'utf8mb4',
	],

	// ****************************************************************
	// The configuration below will be overruled by the admin panel.
	// Changes made below will only have an effect if the database does
	// not contain any configuration for the friendica system.
	// ****************************************************************

	'config' => [
		'admin_email' => 'admin@friendica.local',
		'sitename' => 'Friendica Social Network',
		'register_policy' => \Friendica\Module\Register::OPEN,
		'register_text' => '',
	],
	'system' => [
		'default_timezone' => 'UTC',
		'language' => 'en',
		'basepath' => '/vagrant',
		'url' => 'https://192.168.56.10',
	],
];
