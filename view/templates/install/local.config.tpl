<?php

// Local configuration

// If you're unsure about what any of the config keys below do, please check the static/defaults.config.php for detailed
// documentation of their data type and behavior.

return [
	'database' => [
		'hostname' => '{{$dbhost|escape:'quotes' nofilter}}',
		'username' => '{{$dbuser|escape:'quotes' nofilter}}',
		'password' => '{{$dbpass|escape:'quotes' nofilter}}',
		'database' => '{{$dbdata|escape:'quotes' nofilter}}',
		'charset' => 'utf8mb4',
	],

	// ****************************************************************
	// The configuration below will be overruled by the admin panel.
	// Changes made below will only have an effect if the database does
	// not contain any configuration for the friendica system.
	// ****************************************************************

	'config' => [
		'php_path' => '{{$phpath|escape:'quotes' nofilter}}',
		'admin_email' => '{{$adminmail|escape:'quotes' nofilter}}',
		'sitename' => 'Friendica Social Network',
		'register_policy' => \Friendica\Module\Register::OPEN,
		'max_import_size' => 200000,
	],
	'system' => [
		'url' => '{{$system_url|escape:'quotes' nofilter}}',
		'basepath' => '{{$basepath|escape:'quotes' nofilter}}',
		'default_timezone' => '{{$timezone|escape:'quotes' nofilter}}',
		'language' => '{{$language|escape:'quotes' nofilter}}',
	],
];
