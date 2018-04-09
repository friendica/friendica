<?php
/**
 * @file mod/install.php
 */

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Database\DBM;
use Friendica\Database\DBStructure;
use Friendica\Object\Image;
use Friendica\Util\Network;
use Friendica\Util\Temporal;

$install_wizard_pass = 1;

function install_init(App $a) {

	// $baseurl/install/testrwrite to test if rewite in .htaccess is working
	if ($a->argc == 2 && $a->argv[1] == "testrewrite") {
		echo "ok";
		killme();
	}

	// We overwrite current theme css, because during install we could not have a working mod_rewrite
	// so we could not have a css at all. Here we set a static css file for the install procedure pages
	$a->config['system']['theme'] = "../install";
	$a->theme['stylesheet'] = System::baseUrl()."/view/install/style.css";

	global $install_wizard_pass;
	if (x($_POST, 'pass')) {
		$install_wizard_pass = intval($_POST['pass']);
	}

}

function install_post(App $a) {
	global $install_wizard_pass;

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

			require_once("include/dba.php");
			if (!dba::connect($dbhost, $dbuser, $dbpass, $dbdata, true)) {
				$a->data['db_conn_failed'] = true;
			}

			return;
			break;
		case 4:
			$urlpath = $a->get_path();
			$dbhost = notags(trim($_POST['dbhost']));
			$dbuser = notags(trim($_POST['dbuser']));
			$dbpass = notags(trim($_POST['dbpass']));
			$dbdata = notags(trim($_POST['dbdata']));
			$phpath = notags(trim($_POST['phpath']));
			$timezone = notags(trim($_POST['timezone']));
			$language = notags(trim($_POST['language']));
			$adminmail = notags(trim($_POST['adminmail']));
			$rino = 1;

			// connect to db
			dba::connect($dbhost, $dbuser, $dbpass, $dbdata, true);

			$tpl = get_markup_template('htconfig.tpl');
			$txt = replace_macros($tpl,[
				'$dbhost' => $dbhost,
				'$dbuser' => $dbuser,
				'$dbpass' => $dbpass,
				'$dbdata' => $dbdata,
				'$timezone' => $timezone,
				'$language' => $language,
				'$urlpath' => $urlpath,
				'$phpath' => $phpath,
				'$adminmail' => $adminmail,
				'$rino' => $rino
			]);


			$result = file_put_contents('.htconfig.php', $txt);
			if (! $result) {
				$a->data['txt'] = $txt;
			}

			$errors = load_database();


			if ($errors) {
				$a->data['db_failed'] = $errors;
			} else {
				$a->data['db_installed'] = true;
			}

			return;
		break;
	}
}

function install_content(App $a) {

	global $install_wizard_pass;
	$o = '';
	$wizard_status = "";
	$install_title = L10n::t('Friendica Communications Server - Setup');



	if (x($a->data, 'db_conn_failed')) {
		$install_wizard_pass = 2;
		$wizard_status = L10n::t('Could not connect to database.');
	}
	if (x($a->data, 'db_create_failed')) {
		$install_wizard_pass = 2;
		$wizard_status = L10n::t('Could not create table.');
	}

	$db_return_text = "";
	if (x($a->data, 'db_installed')) {
		$txt = '<p style="font-size: 130%;">';
		$txt .= L10n::t('Your Friendica site database has been installed.') . EOL;
		$db_return_text .= $txt;
	}

	if (x($a->data, 'db_failed')) {
		$txt = L10n::t('You may need to import the file "database.sql" manually using phpmyadmin or mysql.') . EOL;
		$txt .= L10n::t('Please see the file "INSTALL.txt".') . EOL ."<hr>";
		$txt .= "<pre>".$a->data['db_failed'] . "</pre>". EOL;
		$db_return_text .= $txt;
	}

	if (dba::$connected) {
		$r = q("SELECT COUNT(*) as `total` FROM `user`");
		if (DBM::is_result($r) && $r[0]['total']) {
			$tpl = get_markup_template('install.tpl');
			return replace_macros($tpl, [
				'$title' => $install_title,
				'$pass' => '',
				'$status' => L10n::t('Database already in use.'),
				'$text' => '',
			]);
		}
	}

	if (x($a->data, 'txt') && strlen($a->data['txt'])) {
		$db_return_text .= manual_config($a);
	}

	if ($db_return_text != "") {
		$tpl = get_markup_template('install.tpl');
		return replace_macros($tpl, [
			'$title' => $install_title,
			'$pass' => "",
			'$text' => $db_return_text . what_next(),
		]);
	}

	switch ($install_wizard_pass) {
		case 1: { // System check


			$checks = [];

			check_funcs($checks);

			check_imagik($checks);

			check_htconfig($checks);

			check_smarty3($checks);

			check_keys($checks);

			if (x($_POST, 'phpath')) {
				$phpath = notags(trim($_POST['phpath']));
			}

			check_php($phpath, $checks);

			check_htaccess($checks);

			/// @TODO Maybe move this out?
			function check_passed($v, $c) {
				if ($c['required']) {
					$v = $v && $c['status'];
				}
				return $v;
			}
			$checkspassed = array_reduce($checks, "check_passed", true);



			$tpl = get_markup_template('install_checks.tpl');
			$o .= replace_macros($tpl, [
				'$title' => $install_title,
				'$pass' => L10n::t('System check'),
				'$checks' => $checks,
				'$passed' => $checkspassed,
				'$see_install' => L10n::t('Please see the file "INSTALL.txt".'),
				'$next' => L10n::t('Next'),
				'$reload' => L10n::t('Check again'),
				'$phpath' => $phpath,
				'$baseurl' => System::baseUrl(),
			]);
			return $o;
		}; break;

		case 2: { // Database config

			$dbhost = ((x($_POST, 'dbhost')) ? notags(trim($_POST['dbhost'])) : 'localhost');
			$dbuser = notags(trim($_POST['dbuser']));
			$dbpass = notags(trim($_POST['dbpass']));
			$dbdata = notags(trim($_POST['dbdata']));
			$phpath = notags(trim($_POST['phpath']));

			$adminmail = notags(trim($_POST['adminmail']));

			$tpl = get_markup_template('install_db.tpl');
			$o .= replace_macros($tpl, [
				'$title' => $install_title,
				'$pass' => L10n::t('Database connection'),
				'$info_01' => L10n::t('In order to install Friendica we need to know how to connect to your database.'),
				'$info_02' => L10n::t('Please contact your hosting provider or site administrator if you have questions about these settings.'),
				'$info_03' => L10n::t('The database you specify below should already exist. If it does not, please create it before continuing.'),

				'$status' => $wizard_status,

				'$dbhost' => ['dbhost', L10n::t('Database Server Name'), $dbhost, '', 'required'],
				'$dbuser' => ['dbuser', L10n::t('Database Login Name'), $dbuser, '', 'required', 'autofocus'],
				'$dbpass' => ['dbpass', L10n::t('Database Login Password'), $dbpass, L10n::t("For security reasons the password must not be empty"), 'required'],
				'$dbdata' => ['dbdata', L10n::t('Database Name'), $dbdata, '', 'required'],
				'$adminmail' => ['adminmail', L10n::t('Site administrator email address'), $adminmail, L10n::t('Your account email address must match this in order to use the web admin panel.'), 'required', 'autofocus', 'email'],



				'$lbl_10' => L10n::t('Please select a default timezone for your website'),

				'$baseurl' => System::baseUrl(),

				'$phpath' => $phpath,

				'$submit' => L10n::t('Submit'),

			]);
			return $o;
		}; break;
		case 3: { // Site settings
			$dbhost = ((x($_POST, 'dbhost')) ? notags(trim($_POST['dbhost'])) : 'localhost');
			$dbuser = notags(trim($_POST['dbuser']));
			$dbpass = notags(trim($_POST['dbpass']));
			$dbdata = notags(trim($_POST['dbdata']));
			$phpath = notags(trim($_POST['phpath']));

			$adminmail = notags(trim($_POST['adminmail']));
			$timezone = ((x($_POST, 'timezone')) ? ($_POST['timezone']) : 'America/Los_Angeles');
			/* Installed langs */
			$lang_choices = L10n::getAvailableLanguages();

			$tpl = get_markup_template('install_settings.tpl');
			$o .= replace_macros($tpl, [
				'$title' => $install_title,
				'$pass' => L10n::t('Site settings'),

				'$status' => $wizard_status,

				'$dbhost' => $dbhost,
				'$dbuser' => $dbuser,
				'$dbpass' => $dbpass,
				'$dbdata' => $dbdata,
				'$phpath' => $phpath,

				'$adminmail' => ['adminmail', L10n::t('Site administrator email address'), $adminmail, L10n::t('Your account email address must match this in order to use the web admin panel.'), 'required', 'autofocus', 'email'],


				'$timezone' => Temporal::getTimezoneField('timezone', L10n::t('Please select a default timezone for your website'), $timezone, ''),
				'$language' => ['language', L10n::t('System Language:'), 'en', L10n::t('Set the default language for your Friendica installation interface and to send emails.'), $lang_choices],
				'$baseurl' => System::baseUrl(),



				'$submit' => L10n::t('Submit'),

			]);
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
function check_add(&$checks, $title, $status, $required, $help) {
	$checks[] = [
		'title' => $title,
		'status' => $status,
		'required' => $required,
		'help'	=> $help,
	];
}

function check_php(&$phpath, &$checks) {
	$passed = $passed2 = $passed3 = false;
	if (strlen($phpath)) {
		$passed = file_exists($phpath);
	} else {
		$phpath = trim(shell_exec('which php'));
		$passed = strlen($phpath);
	}
	$help = "";
	if (!$passed) {
		$help .= L10n::t('Could not find a command line version of PHP in the web server PATH.'). EOL;
		$help .= L10n::t("If you don't have a command line version of PHP installed on your server, you will not be able to run the background processing. See <a href='https://github.com/friendica/friendica/blob/master/doc/Install.md#set-up-the-worker'>'Setup the worker'</a>") . EOL;
		$help .= EOL . EOL;
		$tpl = get_markup_template('field_input.tpl');
		$help .= replace_macros($tpl, [
			'$field' => ['phpath', L10n::t('PHP executable path'), $phpath, L10n::t('Enter full path to php executable. You can leave this blank to continue the installation.')],
		]);
		$phpath = "";
	}

	check_add($checks, L10n::t('Command line PHP').($passed?" (<tt>$phpath</tt>)":""), $passed, false, $help);

	if ($passed) {
		$cmd = "$phpath -v";
		$result = trim(shell_exec($cmd));
		$passed2 = ( strpos($result, "(cli)") !== false);
		list($result) = explode("\n", $result);
		$help = "";
		if (!$passed2) {
			$help .= L10n::t("PHP executable is not the php cli binary \x28could be cgi-fgci version\x29"). EOL;
			$help .= L10n::t('Found PHP version: ')."<tt>$result</tt>";
		}
		check_add($checks, L10n::t('PHP cli binary'), $passed2, true, $help);
	}


	if ($passed2) {
		$str = autoname(8);
		$cmd = "$phpath testargs.php $str";
		$result = trim(shell_exec($cmd));
		$passed3 = $result == $str;
		$help = "";
		if (!$passed3) {
			$help .= L10n::t('The command line version of PHP on your system does not have "register_argc_argv" enabled.'). EOL;
			$help .= L10n::t('This is required for message delivery to work.');
		}
		check_add($checks, L10n::t('PHP register_argc_argv'), $passed3, true, $help);
	}


}

function check_keys(&$checks) {

	$help = '';

	$res = false;

	if (function_exists('openssl_pkey_new')) {
		$res = openssl_pkey_new([
			'digest_alg'       => 'sha1',
			'private_key_bits' => 4096,
			'encrypt_key'      => false
		]);
	}

	// Get private key

	if (! $res) {
		$help .= L10n::t('Error: the "openssl_pkey_new" function on this system is not able to generate encryption keys'). EOL;
		$help .= L10n::t('If running under Windows, please see "http://www.php.net/manual/en/openssl.installation.php".');
	}
	check_add($checks, L10n::t('Generate encryption keys'), $res, true, $help);

}


function check_funcs(&$checks) {
	$ck_funcs = [];
	check_add($ck_funcs, L10n::t('libCurl PHP module'), true, true, "");
	check_add($ck_funcs, L10n::t('GD graphics PHP module'), true, true, "");
	check_add($ck_funcs, L10n::t('OpenSSL PHP module'), true, true, "");
	check_add($ck_funcs, L10n::t('PDO or MySQLi PHP module'), true, true, "");
	check_add($ck_funcs, L10n::t('mb_string PHP module'), true, true, "");
	check_add($ck_funcs, L10n::t('XML PHP module'), true, true, "");
	check_add($ck_funcs, L10n::t('iconv PHP module'), true, true, "");
	check_add($ck_funcs, L10n::t('POSIX PHP module'), true, true, "");

	if (function_exists('apache_get_modules')) {
		if (! in_array('mod_rewrite',apache_get_modules())) {
			check_add($ck_funcs, L10n::t('Apache mod_rewrite module'), false, true, L10n::t('Error: Apache webserver mod-rewrite module is required but not installed.'));
		} else {
			check_add($ck_funcs, L10n::t('Apache mod_rewrite module'), true, true, "");
		}
	}

	if (! function_exists('curl_init')) {
		$ck_funcs[0]['status'] = false;
		$ck_funcs[0]['help'] = L10n::t('Error: libCURL PHP module required but not installed.');
	}
	if (! function_exists('imagecreatefromjpeg')) {
		$ck_funcs[1]['status'] = false;
		$ck_funcs[1]['help'] = L10n::t('Error: GD graphics PHP module with JPEG support required but not installed.');
	}
	if (! function_exists('openssl_public_encrypt')) {
		$ck_funcs[2]['status'] = false;
		$ck_funcs[2]['help'] = L10n::t('Error: openssl PHP module required but not installed.');
	}
	if (! function_exists('mysqli_connect') && !class_exists('pdo')) {
		$ck_funcs[3]['status'] = false;
		$ck_funcs[3]['help'] = L10n::t('Error: PDO or MySQLi PHP module required but not installed.');
	}
	if (!function_exists('mysqli_connect') && class_exists('pdo') && !in_array('mysql', PDO::getAvailableDrivers())) {
		$ck_funcs[3]['status'] = false;
		$ck_funcs[3]['help'] = L10n::t('Error: The MySQL driver for PDO is not installed.');
	}
	if (! function_exists('mb_strlen')) {
		$ck_funcs[4]['status'] = false;
		$ck_funcs[4]['help'] = L10n::t('Error: mb_string PHP module required but not installed.');
	}
	if (! function_exists('iconv_strlen')) {
		$ck_funcs[6]['status'] = false;
		$ck_funcs[6]['help'] = L10n::t('Error: iconv PHP module required but not installed.');
	}
	if (! function_exists('posix_kill')) {
		$ck_funcs[7]['status'] = false;
		$ck_funcs[7]['help'] = L10n::t('Error: POSIX PHP module required but not installed.');
	}

	$checks = array_merge($checks, $ck_funcs);

	// check for XML DOM Documents being able to be generated
	try {
		$xml = new DOMDocument();
	} catch (Exception $e) {
		$ck_funcs[5]['status'] = false;
		$ck_funcs[5]['help'] = L10n::t('Error, XML PHP module required but not installed.');
	}
}


function check_htconfig(&$checks) {
	$status = true;
	$help = "";
	if ((file_exists('.htconfig.php') && !is_writable('.htconfig.php')) ||
		(!file_exists('.htconfig.php') && !is_writable('.'))) {

		$status = false;
		$help = L10n::t('The web installer needs to be able to create a file called ".htconfig.php" in the top folder of your web server and it is unable to do so.') .EOL;
		$help .= L10n::t('This is most often a permission setting, as the web server may not be able to write files in your folder - even if you can.').EOL;
		$help .= L10n::t('At the end of this procedure, we will give you a text to save in a file named .htconfig.php in your Friendica top folder.').EOL;
		$help .= L10n::t('You can alternatively skip this procedure and perform a manual installation. Please see the file "INSTALL.txt" for instructions.').EOL;
	}

	check_add($checks, L10n::t('.htconfig.php is writable'), $status, false, $help);

}

function check_smarty3(&$checks) {
	$status = true;
	$help = "";
	if (!is_writable('view/smarty3')) {

		$status = false;
		$help = L10n::t('Friendica uses the Smarty3 template engine to render its web views. Smarty3 compiles templates to PHP to speed up rendering.') .EOL;
		$help .= L10n::t('In order to store these compiled templates, the web server needs to have write access to the directory view/smarty3/ under the Friendica top level folder.').EOL;
		$help .= L10n::t("Please ensure that the user that your web server runs as \x28e.g. www-data\x29 has write access to this folder.").EOL;
		$help .= L10n::t("Note: as a security measure, you should give the web server write access to view/smarty3/ only--not the template files \x28.tpl\x29 that it contains.").EOL;
	}

	check_add($checks, L10n::t('view/smarty3 is writable'), $status, true, $help);

}

function check_htaccess(&$checks) {
	$status = true;
	$help = "";
	if (function_exists('curl_init')) {
		$test = Network::fetchUrl(System::baseUrl()."/install/testrewrite");

		if ($test != "ok") {
			$test = Network::fetchUrl(normalise_link(System::baseUrl()."/install/testrewrite"));
		}

		if ($test != "ok") {
			$status = false;
			$help = L10n::t('Url rewrite in .htaccess is not working. Check your server configuration.');
		}
		check_add($checks, L10n::t('Url rewrite is working'), $status, true, $help);
	} else {
		// cannot check modrewrite if libcurl is not installed
		/// @TODO Maybe issue warning here?
	}
}

function check_imagik(&$checks) {
	$imagick = false;
	$gif = false;

	if (class_exists('Imagick')) {
		$imagick = true;
		$supported = Image::supportedTypes();
		if (array_key_exists('image/gif', $supported)) {
			$gif = true;
		}
	}
	if ($imagick == false) {
		check_add($checks, L10n::t('ImageMagick PHP extension is not installed'), $imagick, false, "");
	} else {
		check_add($checks, L10n::t('ImageMagick PHP extension is installed'), $imagick, false, "");
		if ($imagick) {
			check_add($checks, L10n::t('ImageMagick supports GIF'), $gif, false, "");
		}
	}
}

function manual_config(App $a) {
	$data = htmlentities($a->data['txt'],ENT_COMPAT, 'UTF-8');
	$o = L10n::t('The database configuration file ".htconfig.php" could not be written. Please use the enclosed text to create a configuration file in your web server root.');
	$o .= "<textarea rows=\"24\" cols=\"80\" >$data</textarea>";
	return $o;
}

function load_database_rem($v, $i) {
	$l = trim($i);
	if (strlen($l)>1 && ($l[0] == "-" || ($l[0] == "/" && $l[1] == "*"))) {
		return $v;
	} else  {
		return $v."\n".$i;
	}
}

function load_database() {
	$errors = DBStructure::update(false, true, true);

	return $errors;
}

function what_next() {
	$baseurl = System::baseUrl();
	return
		L10n::t('<h1>What next</h1>')
		."<p>".L10n::t('IMPORTANT: You will need to [manually] setup a scheduled task for the worker.')
		.L10n::t('Please see the file "INSTALL.txt".')
		."</p><p>"
		.L10n::t('Go to your new Friendica node <a href="%s/register">registration page</a> and register as new user. Remember to use the same email you have entered as administrator email. This will allow you to enter the site admin panel.', $baseurl)
		."</p>";
}
