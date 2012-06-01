<?php

$install_wizard_pass=1;


function install_init(&$a){
	
	// $baseurl/install/testrwrite to test if rewite in .htaccess is working
	if ($a->argc==2 && $a->argv[1]=="testrewrite") {
		echo "ok";
		killme();
	}
	global $install_wizard_pass;
	if (x($_POST,'pass'))
		$install_wizard_pass = intval($_POST['pass']);

}

function install_post(&$a) {
	global $install_wizard_pass, $db;

	switch($install_wizard_pass) {
		case 1:
		case 2:
			return;
			break; // just in case return don't return :)
		case 3:
			$urlpath = $a->get_path();
			$dbhost = notags(trim($_POST['dbhost']));
			$dbuser = notags(trim($_POST['dbuser']));
			$dbpass = notags(trim($_POST['dbpass']));
			$dbdata = notags(trim($_POST['dbdata']));
			$phpath = notags(trim($_POST['phpath']));

			require_once("dba.php");
			unset($db);
			$db = new dba($dbhost, $dbuser, $dbpass, $dbdata, true);
			/*if(get_db_errno()) {
				unset($db);
				$db = new dba($dbhost, $dbuser, $dbpass, '', true);

				if(! get_db_errno()) {
					$r = q("CREATE DATABASE '%s'",
							dbesc($dbdata)
					);
					if($r) {
						unset($db);
						$db = new dba($dbhost, $dbuser, $dbpass, $dbdata, true);
					} else {
						$a->data['db_create_failed']=true;
					}
				} else {
					$a->data['db_conn_failed']=true;
					return;
				}
			}*/
			if(get_db_errno()) {
				$a->data['db_conn_failed']=true;
			}

			return; 
			break;
		case 4;
			$urlpath = $a->get_path();
			$dbhost = notags(trim($_POST['dbhost']));
			$dbuser = notags(trim($_POST['dbuser']));
			$dbpass = notags(trim($_POST['dbpass']));
			$dbdata = notags(trim($_POST['dbdata']));
			$phpath = notags(trim($_POST['phpath']));
			$timezone = notags(trim($_POST['timezone']));
			$adminmail = notags(trim($_POST['adminmail']));

			// connect to db
			$db = new dba($dbhost, $dbuser, $dbpass, $dbdata, true);

			$tpl = get_intltext_template('htconfig.tpl');
			$txt = replace_macros($tpl,array(
				'$dbhost' => $dbhost,
				'$dbuser' => $dbuser,
				'$dbpass' => $dbpass,
				'$dbdata' => $dbdata,
				'$timezone' => $timezone,
				'$urlpath' => $urlpath,
				'$phpath' => $phpath,
				'$adminmail' => $adminmail
			));

			$result = file_put_contents('.htconfig.php', $txt);
			if(! $result) {
				$a->data['txt'] = $txt;
			}

			$errors = load_database($db);

			if($errors)
				$a->data['db_failed'] = $errors;
			else
				$a->data['db_installed'] = true;

			return;
		break;
	}
}

function get_db_errno() {
	if(class_exists('mysqli'))
		return mysqli_connect_errno();
	else
		return mysql_errno();
}		

function install_content(&$a) {

	global $install_wizard_pass, $db;
	$o = '';
	$wizard_status = "";
	$install_title = t('Friendica Social Communications Server - Setup');
	

	
	if(x($a->data,'db_conn_failed')) {
		$install_wizard_pass = 2;
		$wizard_status =  t('Could not connect to database.');
	}
	if(x($a->data,'db_create_failed')) {
		$install_wizard_pass = 2;
		$wizard_status =  t('Could not create table.');
	}
	
	$db_return_text="";
	if(x($a->data,'db_installed')) {
		$txt = '<p style="font-size: 130%;">';
		$txt .= t('Your Friendica site database has been installed.') . EOL;
		$db_return_text .= $txt;
	}

	if(x($a->data,'db_failed')) {
		$txt = t('You may need to import the file "database.sql" manually using phpmyadmin or mysql.') . EOL;
		$txt .= t('Please see the file "INSTALL.txt".') . EOL ."<hr>" ;
		$txt .= "<pre>".$a->data['db_failed'] . "</pre>". EOL ;
		$db_return_text .= $txt;
	}
	
	if($db && $db->connected) {
		$r = q("SELECT COUNT(*) as `total` FROM `user`");
		if($r && count($r) && $r[0]['total']) {
			$tpl = get_markup_template('install.tpl');
			return replace_macros($tpl, array(
				'$title' => $install_title,
				'$pass' => '',
				'$status' => t('Permission denied.'),
				'$text' => '',
			));
		}
	}

	if(x($a->data,'txt') && strlen($a->data['txt'])) {
		$tpl = get_markup_template('install.tpl');
		$db_return_text .= manual_config($a);
	}
	
	if ($db_return_text!="") {
		return replace_macros($tpl, array(
			'$title' => $install_title,
			'$pass' => "",
			'$text' => $db_return_text . what_next(),
		));
	}
	
	switch ($install_wizard_pass){
		case 1: { // System check


			$checks = array();

			check_funcs($checks);

			check_htconfig($checks);

			check_keys($checks);
			
			if(x($_POST,'phpath'))
				$phpath = notags(trim($_POST['phpath']));

			check_php($phpath, $checks);

            check_htaccess($checks);
            
			function check_passed($v, $c){
				if ($c['required'])
					$v = $v && $c['status'];
				return $v;
			}
			$checkspassed = array_reduce($checks, "check_passed", true);
	        


			$tpl = get_markup_template('install_checks.tpl');
			$o .= replace_macros($tpl, array(
				'$title' => $install_title,
				'$pass' => t('System check'),
				'$checks' => $checks,
				'$passed' => $checkspassed,
				'$see_install' => t('Please see the file "INSTALL.txt".'),
				'$next' => t('Next'),
				'$reload' => t('Check again'),
				'$phpath' => $phpath,
				'$baseurl' => $a->get_baseurl(),
			));
			return $o;
		}; break;
		
		case 2: { // Database config

			$dbhost = ((x($_POST,'dbhost')) ? notags(trim($_POST['dbhost'])) : 'localhost');
			$dbuser = notags(trim($_POST['dbuser']));
			$dbpass = notags(trim($_POST['dbpass']));
			$dbdata = notags(trim($_POST['dbdata']));
			$phpath = notags(trim($_POST['phpath']));
			

			$tpl = get_markup_template('install_db.tpl');
			$o .= replace_macros($tpl, array(
				'$title' => $install_title,
				'$pass' => t('Database connection'),
				'$info_01' => t('In order to install Friendica we need to know how to connect to your database.'),
				'$info_02' => t('Please contact your hosting provider or site administrator if you have questions about these settings.'),
				'$info_03' => t('The database you specify below should already exist. If it does not, please create it before continuing.'),

				'$status' => $wizard_status,
				
				'$dbhost' => array('dbhost', t('Database Server Name'), $dbhost, ''),
				'$dbuser' => array('dbuser', t('Database Login Name'), $dbuser, ''),
				'$dbpass' => array('dbpass', t('Database Login Password'), $dbpass, ''),
				'$dbdata' => array('dbdata', t('Database Name'), $dbdata, ''),
				'$adminmail' => array('adminmail', t('Site administrator email address'), $adminmail, t('Your account email address must match this in order to use the web admin panel.')),

				

				'$lbl_10' => t('Please select a default timezone for your website'),
				
				'$baseurl' => $a->get_baseurl(),
				
				'$phpath' => $phpath,
				
				'$submit' => t('Submit'),
				
			));
			return $o;
		}; break;
		case 3: { // Site settings
			require_once('datetime.php');
			$dbhost = ((x($_POST,'dbhost')) ? notags(trim($_POST['dbhost'])) : 'localhost');
			$dbuser = notags(trim($_POST['dbuser']));
			$dbpass = notags(trim($_POST['dbpass']));
			$dbdata = notags(trim($_POST['dbdata']));
			$phpath = notags(trim($_POST['phpath']));
			
			$adminmail = notags(trim($_POST['adminmail']));
			$timezone = ((x($_POST,'timezone')) ? ($_POST['timezone']) : 'America/Los_Angeles');
			
			$tpl = get_markup_template('install_settings.tpl');
			$o .= replace_macros($tpl, array(
				'$title' => $install_title,
				'$pass' => t('Site settings'),

				'$status' => $wizard_status,
				
				'$dbhost' => $dbhost, 
				'$dbuser' => $dbuser,
				'$dbpass' => $dbpass,
				'$dbdata' => $dbdata,
				'$phpath' => $phpath,
				
				'$adminmail' => array('adminmail', t('Site administrator email address'), $adminmail, t('Your account email address must match this in order to use the web admin panel.')),

				
				'$timezone' => field_timezone('timezone', t('Please select a default timezone for your website'), $timezone, ''),
				
				'$baseurl' => $a->get_baseurl(),
				
				
				
				'$submit' => t('Submit'),
				
			));
			return $o;
		}; break;
			
	}
}

/**
 * checks   : array passed to template
 * title    : string
 * status   : boolean
 * required : boolean
 * help		: string optional
 */
function check_add(&$checks, $title, $status, $required, $help){
	$checks[] = array(
		'title' => $title,
		'status' => $status,
		'required' => $required,
		'help'	=> $help,
	);
}

function check_php(&$phpath, &$checks) {
	if (strlen($phpath)){
		$passed = file_exists($phpath);
	} else {
		$phpath = trim(shell_exec('which php'));
		$passed = strlen($phpath);
	}
	$help = "";
	if(!$passed) {
		$help .= t('Could not find a command line version of PHP in the web server PATH.'). EOL;
		$help .= t("If you don't have a command line version of PHP installed on server, you will not be able to run background polling via cron. See <a href='http://friendica.com/node/27'>'Activating scheduled tasks'</a>") . EOL ;
		$help .= EOL . EOL ;
		$tpl = get_markup_template('field_input.tpl');
		$help .= replace_macros($tpl, array(
			'$field' => array('phpath', t('PHP executable path'), $phpath, t('Enter full path to php executable. You can leave this blank to continue the installation.')),
		));
		$phpath="";
	}
	
	check_add($checks, t('Command line PHP').($passed?" (<tt>$phpath</tt>)":""), $passed, false, $help);
	
	if($passed) {
		$str = autoname(8);
		$cmd = "$phpath testargs.php $str";
		$result = trim(shell_exec($cmd));
		$passed2 = $result == $str;
		$help = "";
		if(!$passed2) {
			$help .= t('The command line version of PHP on your system does not have "register_argc_argv" enabled.'). EOL;
			$help .= t('This is required for message delivery to work.');
		}
		check_add($checks, t('PHP register_argc_argv'), $passed, true, $help);
	}
	

}

function check_keys(&$checks) {

	$help = '';

	$res = false;

	if(function_exists('openssl_pkey_new')) 
		$res=openssl_pkey_new(array(
		'digest_alg' => 'sha1',
		'private_key_bits' => 4096,
		'encrypt_key' => false ));

	// Get private key

	if(! $res) {
		$help .= t('Error: the "openssl_pkey_new" function on this system is not able to generate encryption keys'). EOL;
		$help .= t('If running under Windows, please see "http://www.php.net/manual/en/openssl.installation.php".');
	}
	check_add($checks, t('Generate encryption keys'), $res, true, $help);

}


function check_funcs(&$checks) {
	$ck_funcs = array();
	check_add($ck_funcs, t('libCurl PHP module'), true, true, "");
	check_add($ck_funcs, t('GD graphics PHP module'), true, true, "");
	check_add($ck_funcs, t('OpenSSL PHP module'), true, true, "");
	check_add($ck_funcs, t('mysqli PHP module'), true, true, "");
	check_add($ck_funcs, t('mb_string PHP module'), true, true, "");
		
	
	if(function_exists('apache_get_modules')){
		if (! in_array('mod_rewrite',apache_get_modules())) {
			check_add($ck_funcs, t('Apache mod_rewrite module'), false, true, t('Error: Apache webserver mod-rewrite module is required but not installed.'));
		} else {
			check_add($ck_funcs, t('Apache mod_rewrite module'), true, true, "");
		}
	}

	if(! function_exists('curl_init')){
		$ck_funcs[0]['status']= false;
		$ck_funcs[0]['help']= t('Error: libCURL PHP module required but not installed.');
	}
	if(! function_exists('imagecreatefromjpeg')){
		$ck_funcs[1]['status']= false;
		$ck_funcs[1]['help']= t('Error: GD graphics PHP module with JPEG support required but not installed.');
	}
	if(! function_exists('openssl_public_encrypt')) {
		$ck_funcs[2]['status']= false;
		$ck_funcs[2]['help']= t('Error: openssl PHP module required but not installed.');
	}
	if(! function_exists('mysqli_connect')){
		$ck_funcs[3]['status']= false;
		$ck_funcs[3]['help']= t('Error: mysqli PHP module required but not installed.');
	}
	if(! function_exists('mb_strlen')){
		$ck_funcs[4]['status']= false;
		$ck_funcs[4]['help']= t('Error: mb_string PHP module required but not installed.');
	}
	
	$checks = array_merge($checks, $ck_funcs);
	
	/*if((x($_SESSION,'sysmsg')) && is_array($_SESSION['sysmsg']) && count($_SESSION['sysmsg']))
		notice( t('Please see the file "INSTALL.txt".') . EOL);*/
}


function check_htconfig(&$checks) {
	$status = true;
	$help = "";
	if(	(file_exists('.htconfig.php') && !is_writable('.htconfig.php')) ||
		(!file_exists('.htconfig.php') && !is_writable('.')) ) {
	
		$status=false;
		$help = t('The web installer needs to be able to create a file called ".htconfig.php" in the top folder of your web server and it is unable to do so.') .EOL;
		$help .= t('This is most often a permission setting, as the web server may not be able to write files in your folder - even if you can.').EOL;
		$help .= t('At the end of this procedure, we will give you a text to save in a file named .htconfig.php in your Friendica top folder.').EOL;
		$help .= t('You can alternatively skip this procedure and perform a manual installation. Please see the file "INSTALL.txt" for instructions.').EOL; 
	}
    
	check_add($checks, t('.htconfig.php is writable'), $status, false, $help);

}

function check_htaccess(&$checks) {
	$a = get_app();
	$status = true;
	$help = "";
	if (function_exists('curl_init')){
        $test = fetch_url($a->get_baseurl()."/install/testrewrite");
        if ($test!="ok") {
            $status = false;
            $help = t('Url rewrite in .htaccess is not working. Check your server configuration.');
        }
        check_add($checks, t('Url rewrite is working'), $status, true, $help); 
    } else {
        // cannot check modrewrite if libcurl is not installed
    }
	
}

	
function manual_config(&$a) {
	$data = htmlentities($a->data['txt']);
	$o = t('The database configuration file ".htconfig.php" could not be written. Please use the enclosed text to create a configuration file in your web server root.');
	$o .= "<textarea rows=\"24\" cols=\"80\" >$data</textarea>";
	return $o;
}

function load_database_rem($v, $i){
	$l = trim($i);
	if (strlen($l)>1 && ($l[0]=="-" || ($l[0]=="/" && $l[1]=="*"))){
		return $v;
	} else  {
		return $v."\n".$i;
	}
}


function load_database($db) {

	$str = file_get_contents('database.sql');
//	$str = array_reduce(explode("\n", $str),"load_database_rem","");
	$arr = explode(';',$str);
	$errors = false;
	foreach($arr as $a) {
		if(strlen(trim($a))) {	
			$r = @$db->q(trim($a));
			if(! $r) {
				$errors .=  t('Errors encountered creating database tables.') . $a . EOL;
			}
		}
	}
	return $errors;
}

function what_next() {
	$a = get_app();
	$baseurl = $a->get_baseurl();
	return 
		t('<h1>What next</h1>')
		."<p>".t('IMPORTANT: You will need to [manually] setup a scheduled task for the poller.')
		.t('Please see the file "INSTALL.txt".')			
		."</p><p>"
		.t("Go to your new Firendica node <a href='$baseurl/register'>registration page</a> and register as new user. Remember to use the same email you have entered as administrator email. This will allow you to enter the site admin panel.")
		."</p>";
}


