<?php

// Local configuration

// If you're unsure about what any of the config keys below do, please check the static/defaults.config.php for detailed
// documentation of their data type and behavior.

return [
	'database' => [
		'hostname' => '{{$dbhost}}',
		'username' => '{{$dbuser}}',
		'password' => '{{$dbpass}}',
		'database' => '{{$dbdata}}',
		'charset' => 'utf8mb4',
	],

	// ****************************************************************
	// The configuration below will be overruled by the admin panel.
	// Changes made below will only have an effect if the database does
	// not contain any configuration for the friendica system.
	// ****************************************************************

	'config' => [
		'php_path' => '{{$phpath}}',
		'admin_email' => '{{$adminmail}}',
		'sitename' => 'Friendica Social Network',
		'hostname' => '{{$hostname}}',
		'register_policy' => \Friendica\Module\Register::OPEN,
		'max_import_size' => 200000,
	],
	'system' => [
		'urlpath' => '{{$urlpath}}',
		'url' => '{{$baseurl}}',
		'ssl_policy' => {{$sslpolicy}},
		'basepath' => '{{$basepath}}',
		'default_timezone' => '{{$timezone}}',
		'language' => '{{$language}}',
	],
];
