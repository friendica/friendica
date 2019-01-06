<?php

namespace Friendica\Module;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use Friendica\Core;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Util\Strings;
use Friendica\Util\Temporal;

class Install extends BaseModule
{
	/**
	 * Step one - System check
	 */
	const SYSTEM_CHECK = 1;
	/**
	 * Step two - Database configuration
	 */
	const DATABASE_CONFIG = 2;
	/**
	 * Step three - Adapat site settings
	 */
	const SITE_SETTINGS = 3;
	/**
	 * Step four - All steps finished
	 */
	const FINISHED = 4;

	/**
	 * @var int The current step of the wizard
	 */
	private static $currentWizardStep;

	/**
	 * @var Core\Installer The installer
	 */
	private static $installer;

	public static function init()
	{
		$a = self::getApp();

		// route: install/testrwrite
		// $baseurl/install/testrwrite to test if rewrite in .htaccess is working
		if ($a->getArgumentValue(1, '') == 'testrewrite') {
			// Status Code 204 means that it worked without content
			Core\System::httpExit(204);
		}

		// We overwrite current theme css, because during install we may not have a working mod_rewrite
		// so we may not have a css at all. Here we set a static css file for the install procedure pages
		Renderer::$theme['stylesheet'] = $a->getBaseURL() . '/view/install/style.css';

		self::$installer = new Core\Installer();
		self::$currentWizardStep = defaults($_POST, 'pass', self::SYSTEM_CHECK);
	}

	public static function post()
	{
		$a = self::getApp();

		switch (self::$currentWizardStep) {
			case self::SYSTEM_CHECK:
			case self::DATABASE_CONFIG:
				// Nothing to do in these steps
				break;

			case self::SITE_SETTINGS:
				$dbhost  = Strings::escapeTags(trim(defaults($_POST, 'dbhost', Core\Installer::DEFAULT_HOST)));
				$dbuser  = Strings::escapeTags(trim(defaults($_POST, 'dbuser', '')));
				$dbpass  = Strings::escapeTags(trim(defaults($_POST, 'dbpass', '')));
				$dbdata  = Strings::escapeTags(trim(defaults($_POST, 'dbdata', '')));

				// If we cannot connect to the database, return to the previous step
				if (!self::$installer->checkDB($dbhost, $dbuser, $dbpass, $dbdata)) {
					self::$currentWizardStep = self::DATABASE_CONFIG;
				}

				break;

			case self::FINISHED:
				$urlpath   = $a->getURLPath();
				$dbhost    = Strings::escapeTags(trim(defaults($_POST, 'dbhost', Core\Installer::DEFAULT_HOST)));
				$dbuser    = Strings::escapeTags(trim(defaults($_POST, 'dbuser', '')));
				$dbpass    = Strings::escapeTags(trim(defaults($_POST, 'dbpass', '')));
				$dbdata    = Strings::escapeTags(trim(defaults($_POST, 'dbdata', '')));
				$timezone  = Strings::escapeTags(trim(defaults($_POST, 'timezone', Core\Installer::DEFAULT_TZ)));
				$language  = Strings::escapeTags(trim(defaults($_POST, 'language', Core\Installer::DEFAULT_LANG)));
				$adminmail = Strings::escapeTags(trim(defaults($_POST, 'adminmail', '')));

				// If we cannot connect to the database, return to the Database config wizard
				if (!self::$installer->checkDB($dbhost, $dbuser, $dbpass, $dbdata)) {
					self::$currentWizardStep = self::DATABASE_CONFIG;
					return;
				}

				$phpath = self::$installer->getPHPPath();

				if (!self::$installer->createConfig($phpath, $urlpath, $dbhost, $dbuser, $dbpass, $dbdata, $timezone, $language, $adminmail, $a->getBasePath())) {
					return;
				}

				self::$installer->installDatabase();

				break;
		}
	}

	public static function content()
	{
		$a = self::getApp();

		$output = '';

		$install_title = L10n::t('Friendica Communications Server - Setup');

		switch (self::$currentWizardStep) {
			case self::SYSTEM_CHECK:
				$phppath = defaults($_POST, 'phpath', null);

				$status = self::$installer->checkEnvironment($a->getBaseURL(), $phppath);

				$tpl = Renderer::getMarkupTemplate('install_checks.tpl');
				$output .= Renderer::replaceMacros($tpl, [
					'$title'		=> $install_title,
					'$pass'			=> L10n::t('System check'),
					'$checks'		=> self::$installer->getChecks(),
					'$passed'		=> $status,
					'$see_install'	=> L10n::t('Please see the file "INSTALL.txt".'),
					'$next' 		=> L10n::t('Next'),
					'$reload' 		=> L10n::t('Check again'),
					'$phpath' 		=> $phppath,
					'$baseurl' 		=> $a->getBaseURL()
				]);
				break;

			case self::DATABASE_CONFIG:
				$dbhost    = Strings::escapeTags(trim(defaults($_POST, 'dbhost'   , Core\Installer::DEFAULT_HOST)));
				$dbuser    = Strings::escapeTags(trim(defaults($_POST, 'dbuser'   , ''                          )));
				$dbpass    = Strings::escapeTags(trim(defaults($_POST, 'dbpass'   , ''                          )));
				$dbdata    = Strings::escapeTags(trim(defaults($_POST, 'dbdata'   , ''                          )));
				$phpath    = Strings::escapeTags(trim(defaults($_POST, 'phpath'   , ''                          )));
				$adminmail = Strings::escapeTags(trim(defaults($_POST, 'adminmail', ''                          )));

				$tpl = Renderer::getMarkupTemplate('install_db.tpl');
				$output .= Renderer::replaceMacros($tpl, [
					'$title' 	=> $install_title,
					'$pass' 	=> L10n::t('Database connection'),
					'$info_01' 	=> L10n::t('In order to install Friendica we need to know how to connect to your database.'),
					'$info_02' 	=> L10n::t('Please contact your hosting provider or site administrator if you have questions about these settings.'),
					'$info_03' 	=> L10n::t('The database you specify below should already exist. If it does not, please create it before continuing.'),
					'checks' 	=> self::$installer->getChecks(),
					'$dbhost' 	=> ['dbhost',
									L10n::t('Database Server Name'),
									$dbhost,
									'',
									'required'],
					'$dbuser' 	=> ['dbuser',
									L10n::t('Database Login Name'),
									$dbuser,
									'',
									'required',
									'autofocus'],
					'$dbpass' 	=> ['dbpass',
									L10n::t('Database Login Password'),
									$dbpass,
									L10n::t("For security reasons the password must not be empty"),
									'required'],
					'$dbdata' 	=> ['dbdata',
									L10n::t('Database Name'),
									$dbdata,
									'',
									'required'],
					'$adminmail' => ['adminmail',
									L10n::t('Site administrator email address'),
									$adminmail,
									L10n::t('Your account email address must match this in order to use the web admin panel.'),
									'required',
									'autofocus',
									'email'],
					'$lbl_10' 	=> L10n::t('Please select a default timezone for your website'),
					'$baseurl' 	=> $a->getBaseURL(),
					'$phpath' 	=> $phpath,
					'$submit' 	=> L10n::t('Submit')
				]);
				break;

			case self::SITE_SETTINGS:
				$dbhost = Strings::escapeTags(trim(defaults($_POST, 'dbhost', Core\Installer::DEFAULT_HOST)));
				$dbuser = Strings::escapeTags(trim(defaults($_POST, 'dbuser', ''                          )));
				$dbpass = Strings::escapeTags(trim(defaults($_POST, 'dbpass', ''                          )));
				$dbdata = Strings::escapeTags(trim(defaults($_POST, 'dbdata', ''                          )));
				$phpath = Strings::escapeTags(trim(defaults($_POST, 'phpath', ''                          )));

				$adminmail = Strings::escapeTags(trim(defaults($_POST, 'adminmail', '')));

				$timezone = defaults($_POST, 'timezone', Core\Installer::DEFAULT_TZ);
				/* Installed langs */
				$lang_choices = L10n::getAvailableLanguages();

				$tpl = Renderer::getMarkupTemplate('install_settings.tpl');
				$output .= Renderer::replaceMacros($tpl, [
					'$title' 		=> $install_title,
					'$checks' 		=> self::$installer->getChecks(),
					'$pass' 		=> L10n::t('Site settings'),
					'$dbhost' 		=> $dbhost,
					'$dbuser' 		=> $dbuser,
					'$dbpass' 		=> $dbpass,
					'$dbdata' 		=> $dbdata,
					'$phpath' 		=> $phpath,
					'$adminmail'	=> ['adminmail', L10n::t('Site administrator email address'), $adminmail, L10n::t('Your account email address must match this in order to use the web admin panel.'), 'required', 'autofocus', 'email'],
					'$timezone' 	=> Temporal::getTimezoneField('timezone', L10n::t('Please select a default timezone for your website'), $timezone, ''),
					'$language' 	=> ['language',
										L10n::t('System Language:'),
										Core\Installer::DEFAULT_LANG,
										L10n::t('Set the default language for your Friendica installation interface and to send emails.'),
										$lang_choices],
					'$baseurl' 		=> $a->getBaseURL(),
					'$submit' 		=> L10n::t('Submit')
				]);
				break;

			case self::FINISHED:
				$db_return_text = "";

				if (count(self::$installer->getChecks()) == 0) {
					$txt = '<p style="font-size: 130%;">';
					$txt .= L10n::t('Your Friendica site database has been installed.') . EOL;
					$db_return_text .= $txt;
				}

				$tpl = Renderer::getMarkupTemplate('install_finished.tpl');
				$output .= Renderer::replaceMacros($tpl, [
					'$title'  => $install_title,
					'$checks' => self::$installer->getChecks(),
					'$pass'   => L10n::t('Installation finished'),
					'$text'   => $db_return_text . self::whatNext($a),
				]);

				break;
		}

		return $output;
	}

	/**
	 * Creates the text for the next steps
	 *
	 * @param App $a The global App
	 *
	 * @return string The text for the next steps
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function whatNext($a)
	{
		$baseurl = $a->getBaseUrl();
		return
			L10n::t('<h1>What next</h1>')
			. "<p>".L10n::t('IMPORTANT: You will need to [manually] setup a scheduled task for the worker.')
			. L10n::t('Please see the file "INSTALL.txt".')
			. "</p><p>"
			. L10n::t('Go to your new Friendica node <a href="%s/register">registration page</a> and register as new user. Remember to use the same email you have entered as administrator email. This will allow you to enter the site admin panel.', $baseurl)
			. "</p>";
	}
}
