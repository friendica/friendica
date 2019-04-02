<?php
/**
 * A test file for local configuration
 *
 */
return [
	'database' =>
	/** Test it
	 * with comment
	 **/
		[
			'hostname' => 'testhost',
			'username' => 'testuser',
			'password' => 'testpw',
			'database' => 'testdb',
			'charset' => 'utf8mb4',
		],
	// another try
	/**
	 * What about this
	 */
	'config' =>
	// but with comment here
		[
			'admin_email' =>
			// and here
				'admin@test.it',
			'sitename' => 'Friendica Social Network',
			'register_policy' =>
			// and here too
				\Friendica\Module\Register::OPEN,
			'register_text' => '',
			'max_import_size' => 999,
		],
	'system'
			   =>
		[ 'allowed_themes' => 'quattro,vier,duepuntozero',
		  'default_timezone' => 'UTC',
		  'language' => 'en',
		  'no_regfullname' => true,
		  'theme' => 'frio',
		  'numeric' => 2.5
		],
	'testcat' => [
		'testarr' => ['1','2','3'],
	],
	// closing it
	\Friendica\App::class => [
		\Friendica\App\Mode::class => true
	]
];
