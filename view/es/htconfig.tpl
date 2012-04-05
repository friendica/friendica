<?php

// Set the following for your MySQL installation
// Copy or rename this file to .htconfig.php

$db_host = '$dbhost';
$db_user = '$dbuser';
$db_pass = '$dbpass';
$db_data = '$dbdata';

// If you are using a subdirectory of your domain you will need to put the
// relative path (from the root of your domain) here.
// For instance if your URL is 'http://example.com/directory/subdirectory',
// set $a->path to 'directory/subdirectory'. 

$a->path = '$urlpath';
 
// Choose a legal default timezone. If you are unsure, use "America/Los_Angeles".
// It can be changed later and only applies to timestamps for anonymous viewers.

$default_timezone = '$timezone';

// What is your site name?

$a->config['sitename'] = "La Mia Rete di Amici";

// Your choices are REGISTER_OPEN, REGISTER_APPROVE, or REGISTER_CLOSED.
// Be certain to create your own personal account before setting 
// REGISTER_CLOSED. 'register_text' (if set) will be displayed prominently on 
// the registration page. REGISTER_APPROVE requires you set 'admin_email'
// to the email address of an already registered person who can authorise
// and/or approve/deny the request.

$a->config['register_policy'] = REGISTER_OPEN;
$a->config['register_text'] = '';
$a->config['admin_email'] = '$adminmail';

// Maximum size of an imported message, 0 is unlimited

$a->config['max_import_size'] = 200000;

// maximum size of uploaded photos

$a->config['system']['maximagesize'] = 800000;

// Location of PHP command line processor

$a->config['php_path'] = '$phpath';

// Location of global directory submission page.

$a->config['system']['directory_submit_url'] = 'http://dir.friendica.com/submit';
$a->config['system']['directory_search_url'] = 'http://dir.friendica.com/directory?search=';

// PuSH - aka pubsubhubbub URL. This makes delivery of public posts as fast as private posts

$a->config['system']['huburl'] = 'http://pubsubhubbub.appspot.com';

// Server-to-server private message encryption (RINO) is allowed by default. 
// Encryption will only be provided if this setting is true and the
// PHP mcrypt extension is installed on both systems 

$a->config['system']['rino_encrypt'] = true;

// default system theme

$a->config['system']['theme'] = 'duepuntozero';

