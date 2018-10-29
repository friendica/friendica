<?php
/**
 * @file mod/install.php
 */

use Friendica\App;
use Friendica\Content\Text;
use Friendica\Core\Install;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
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

	$a->setConfigValue('system', 'value', '../install');
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
			$dbhost = Text::noTags(trim($_POST['dbhost']));
			$dbuser = Text::noTags(trim($_POST['dbuser']));
			$dbpass = Text::noTags(trim($_POST['dbpass']));
			$dbdata = Text::noTags(trim($_POST['dbdata']));
			$phpath = Text::noTags(trim($_POST['phpath']));

			require_once("include/dba.php");
			if (!DBA::connect($dbhost, $dbuser, $dbpass, $dbdata)) {
				$a->data['db_conn_failed'] = true;
			}

			return;
			break;
		case 4:
			$urlpath = $a->getURLPath();
			$dbhost = Text::noTags(trim($_POST['dbhost']));
			$dbuser = Text::noTags(trim($_POST['dbuser']));
			$dbpass = Text::noTags(trim($_POST['dbpass']));
			$dbdata = Text::noTags(trim($_POST['dbdata']));
			$phpath = Text::noTags(trim($_POST['phpath']));
			$timezone = Text::noTags(trim($_POST['timezone']));
			$language = Text::noTags(trim($_POST['language']));
			$adminmail = Text::noTags(trim($_POST['adminmail']));

			// connect to db
			DBA::connect($dbhost, $dbuser, $dbpass, $dbdata);

			$install = new Install();

			$errors = $install->createConfig($phpath, $urlpath, $dbhost, $dbuser, $dbpass, $dbdata, $timezone, $language, $adminmail, $a->getBasePath());

			if ($errors !== true) {
				$a->data['data'] = $errors;
				return;
			}

			$errors = DBStructure::update(false, true, true);

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

	if (DBA::$connected) {
		$r = q("SELECT COUNT(*) as `total` FROM `user`");
		if (DBA::isResult($r) && $r[0]['total']) {
			$install_wizard_pass = 2;
			$wizard_status = L10n::t('Database already in use.');
		}
	}

	if (x($a->data, 'txt') && strlen($a->data['txt'])) {
		$db_return_text .= manual_config($a);
	}

	if ($db_return_text != "") {
		$tpl = Text::getMarkupTemplate('install.tpl');
		return App::replaceMacros($tpl, [
			'$title' => $install_title,
			'$pass' => "",
			'$text' => $db_return_text . what_next(),
		]);
	}

	switch ($install_wizard_pass) {
		case 1: { // System check

			$phpath = defaults($_POST, 'phpath', 'php');

			$install = new Install($phpath);

			$status = $install->checkAll($a->getBasePath(), $a->getBaseURL());

			$tpl = Text::getMarkupTemplate('install_checks.tpl');
			$o .= App::replaceMacros($tpl, [
				'$title' => $install_title,
				'$pass' => L10n::t('System check'),
				'$checks' => $install->getChecks(),
				'$passed' => $status,
				'$see_install' => L10n::t('Please see the file "INSTALL.txt".'),
				'$next' => L10n::t('Next'),
				'$reload' => L10n::t('Check again'),
				'$phpath' => $phpath,
				'$baseurl' => $a->getBaseURL(),
			]);
			return $o;
		}; break;

		case 2: { // Database config

			$dbhost    = Text::noTags(trim(defaults($_POST, 'dbhost'   , 'localhost')));
			$dbuser    = Text::noTags(trim(defaults($_POST, 'dbuser'   , ''         )));
			$dbpass    = Text::noTags(trim(defaults($_POST, 'dbpass'   , ''         )));
			$dbdata    = Text::noTags(trim(defaults($_POST, 'dbdata'   , ''         )));
			$phpath    = Text::noTags(trim(defaults($_POST, 'phpath'   , ''         )));
			$adminmail = Text::noTags(trim(defaults($_POST, 'adminmail', ''         )));

			$tpl = Text::getMarkupTemplate('install_db.tpl');
			$o .= App::replaceMacros($tpl, [
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

				'$baseurl' => $a->getBaseURL(),

				'$phpath' => $phpath,

				'$submit' => L10n::t('Submit'),
			]);
			return $o;
		}; break;
		case 3: { // Site settings
			$dbhost = ((x($_POST, 'dbhost')) ? Text::noTags(trim($_POST['dbhost'])) : 'localhost');
			$dbuser = Text::noTags(trim($_POST['dbuser']));
			$dbpass = Text::noTags(trim($_POST['dbpass']));
			$dbdata = Text::noTags(trim($_POST['dbdata']));
			$phpath = Text::noTags(trim($_POST['phpath']));

			$adminmail = Text::noTags(trim($_POST['adminmail']));
			$timezone = ((x($_POST, 'timezone')) ? ($_POST['timezone']) : 'America/Los_Angeles');
			/* Installed langs */
			$lang_choices = L10n::getAvailableLanguages();

			$tpl = Text::getMarkupTemplate('install_settings.tpl');
			$o .= App::replaceMacros($tpl, [
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
				'$baseurl' => $a->getBaseURL(),

				'$submit' => L10n::t('Submit'),

			]);
			return $o;
		}; break;

	}
}

function manual_config(App $a) {
	$data = htmlentities($a->data['txt'],ENT_COMPAT, 'UTF-8');
	$o = L10n::t('The database configuration file "config/local.ini.php" could not be written. Please use the enclosed text to create a configuration file in your web server root.');
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
