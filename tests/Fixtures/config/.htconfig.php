<?php
/**
 * A test .htconfig file
 */

$db_host = 'testhost';
$db_user = 'testuser';
$db_pass = 'testpw';
$db_data = 'testdb';

$pidfile = '/var/run/friendica.pid';

// Set the database connection charset to UTF8.
// Changing this value will likely corrupt the special characters.
// You have been warned.
$a->config['system']['db_charset'] = "anotherCharset";

// Choose a legal default timezone. If you are unsure, use "America/Los_Angeles".
// It can be changed later and only applies to timestamps for anonymous viewers.
$default_timezone = 'Europe/Berlin';
$lang = 'fr';

// What is your site name?
$a->config['sitename'] = "Friendica My Network";

// Your choices are REGISTER_OPEN, REGISTER_APPROVE, or REGISTER_CLOSED.
// Be certain to create your own personal account before setting
// REGISTER_CLOSED. 'register_text' (if set) will be displayed prominently on
// the registration page. REGISTER_APPROVE requires you set 'admin_email'
// to the email address of an already registered person who can authorise
// and/or approve/deny the request.
// In order to perform system administration via the admin panel, admin_email
// must precisely match the email address of the person logged in.
$a->config['register_policy'] = Friendica\Module\Register::OPEN;
$a->config['register_text'] = 'A register text';
$a->config['admin_email'] = 'admin@test.it';
$a->config['admin_nickname'] = 'Friendly admin';

// Maximum size of an imported message, 0 is unlimited
$a->config['max_import_size'] = 999;

// maximum size of uploaded photos
$a->config['system']['maximagesize'] = 666;

// Location of PHP command line processor
$a->config['php_path'] = '/another/php';

// PuSH - aka pubsubhubbub URL. This makes delivery of public posts as fast as private posts
$a->config['system']['huburl'] = '[internal]';

// allowed themes (change this from admin panel after installation)
$a->config['system']['allowed_themes'] = 'frio,vier';

// default system theme
$a->config['system']['theme'] = 'frio';

// By default allow pseudonyms
$a->config['system']['no_regfullname'] = true;

//Deny public access to the local directory
//$a->config['system']['block_local_dir'] = false;
// Location of the global directory
$a->config['system']['directory'] = 'http://another.url';
