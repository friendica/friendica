<?php

/* ********************************************************************
 *  The following configuration has to be within the .htconfig file
 *  and will not be overruled by decisions made in the admin panel.
 * 
 *  See below for variables that may be overruled by the admin panel.
 * ********************************************************************/

// Set the following for your MySQL installation
// Copy or rename this file to .htconfig.php

$db_host = '{{$dbhost}}';
$db_user = '{{$dbuser}}';
$db_pass = '{{$dbpass}}';
$db_data = '{{$dbdata}}';

// Set the database connection charset to UTF8.
// Changing this value will likely corrupt the special characters.
// You have been warned.
$a->config['system']['db_charset'] = "utf8mb4";

// email adress for the system admin

$a->config['admin_email'] = '{{$adminmail}}';

// Location of PHP command line processor

$a->config['php_path'] = '{{$phpath}}';

// If you are using a subdirectory of your domain you will need to put the
// relative path (from the root of your domain) here.
// For instance if your URL is 'http://example.com/directory/subdirectory',
// set path to 'directory/subdirectory'.

$a->path = '{{$urlpath}}';

// Allowed protocols in link URLs; HTTP protocols always are accepted
$a->config['system']['allowed_link_protocols'] = array('ftp', 'ftps', 'mailto', 'cid', 'gopher');

/* *********************************************************************
 *  The configuration below will be overruled by the admin panel.
 *  Changes made below will only have an effect if the database does
 *  not contain any configuration for the friendica system.
 * *********************************************************************/
 
// Choose a legal default timezone. If you are unsure, use "America/Los_Angeles".
// It can be changed later and only applies to timestamps for anonymous viewers.

$default_timezone = '{{$timezone}}';

// Default system language

$a->config['system']['language'] = '{{$language}}';

// What is your site name?

$a->config['sitename'] = "My Friend Network";

// Your choices are REGISTER_OPEN, REGISTER_APPROVE, or REGISTER_CLOSED.
// Be certain to create your own personal account before setting
// REGISTER_CLOSED. 'register_text' (if set) will be displayed prominently on
// the registration page. REGISTER_APPROVE requires you set 'admin_email'
// to the email address of an already registered person who can authorise
// and/or approve/deny the request.

$a->config['register_policy'] = REGISTER_OPEN;
$a->config['register_text'] = '';

// Maximum size of an imported message, 0 is unlimited

$a->config['max_import_size'] = 200000;

// maximum size of uploaded photos

$a->config['system']['maximagesize'] = 800000;

// PuSH - aka pubsubhubbub URL. This makes delivery of public posts as fast as private posts

$a->config['system']['huburl'] = '[internal]';

// Server-to-server private message encryption (RINO) is allowed by default.
// Encryption will only be provided if this setting is true and the
// PHP mcrypt extension is installed on both systems

$a->config['system']['rino_encrypt'] = {{$rino}};

// default system theme

$a->config['system']['theme'] = 'vier';

// By default allow pseudonyms

$a->config['system']['no_regfullname'] = true;

//Deny public access to the local directory
//$a->config['system']['block_local_dir'] = false;

// Location of the global directory
$a->config['system']['directory'] = 'http://dir.friendi.ca';
