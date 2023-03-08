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
 *
 * If you're unsure about what any of the config keys below do, please check the static/defaults.config.php file for
 * detailed documentation of their data type and behavior.
 */

return [
	'database' => [
		'hostname' => 'localhost',
		'username' => 'mysqlusername',
		'password' => 'mysqlpassword',
		'database' => 'mysqldatabasename',
		'charset' => 'utf8mb4',
	],

	// ****************************************************************
	// The configuration below will be overruled by the admin panel.
	// Changes made below will only have an effect if the database does
	// not contain any configuration for the friendica system.
	// ****************************************************************

	'config' => [
		'admin_email' => '',
		'sitename' => 'Friendica Social Network',
		'register_policy' => \Friendica\Module\Register::OPEN,
		'register_text' => '',
	],
	'system' => [
		'default_timezone' => 'UTC',
		'language' => 'en',
		'url' => 'https://friendica.local',
	],
];
