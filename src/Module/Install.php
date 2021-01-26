<?php
/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Module;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core;
use Friendica\Core\Config\Cache;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Network\HTTPException;
use Friendica\Util\BasePath;
use Friendica\Util\Strings;
use Friendica\Util\Temporal;

class Install extends BaseModule
{
	/**
	 * Step one - System check
	 */
	const SYSTEM_CHECK = 1;
	/**
	 * Step two - Base information
	 */
	const BASE_CONFIG = 2;
	/**
	 * Step three - Database configuration
	 */
	const DATABASE_CONFIG = 3;
	/**
	 * Step four - Adapt site settings
	 */
	const SITE_SETTINGS = 4;
	/**
	 * Step five - All steps finished
	 */
	const FINISHED = 5;

	/**
	 * @var int The current step of the wizard
	 */
	private static $currentWizardStep;

	/**
	 * @var Core\Installer The installer
	 */
	private static $installer;

	public static function init(array $parameters = [])
	{
		$a = DI::app();

		if (!DI::mode()->isInstall()) {
			throw new HTTPException\ForbiddenException();
		}

		// route: install/testrwrite
		// $baseurl/install/testrwrite to test if rewrite in .htaccess is working
		if (DI::args()->get(1, '') == 'testrewrite') {
			// Status Code 204 means that it worked without content
			throw new HTTPException\NoContentException();
		}

		self::$installer = new Core\Installer();

		// get basic installation information and save them to the config cache
		$configCache = $a->getConfigCache();
		$basePath = new BasePath($a->getBasePath());
		self::$installer->setUpCache($configCache, $basePath->getPath());

		// We overwrite current theme css, because during install we may not have a working mod_rewrite
		// so we may not have a css at all. Here we set a static css file for the install procedure pages
		Renderer::$theme['stylesheet'] = DI::baseUrl()->get() . '/view/install/style.css';

		self::$currentWizardStep = ($_POST['pass'] ?? '') ?: self::SYSTEM_CHECK;
	}

	public static function post(array $parameters = [])
	{
		$a           = DI::app();
		$configCache = $a->getConfigCache();

		switch (self::$currentWizardStep) {
			case self::SYSTEM_CHECK:
			case self::BASE_CONFIG:
				self::checkSetting($configCache, $_POST, 'config', 'php_path');
				break;

			case self::DATABASE_CONFIG:
				self::checkSetting($configCache, $_POST, 'config', 'php_path');

				self::checkSetting($configCache, $_POST, 'config', 'hostname');
				self::checkSetting($configCache, $_POST, 'system', 'ssl_policy');
				self::checkSetting($configCache, $_POST, 'system', 'basepath');
				self::checkSetting($configCache, $_POST, 'system', 'urlpath');
				break;

			case self::SITE_SETTINGS:
				self::checkSetting($configCache, $_POST, 'config', 'php_path');

				self::checkSetting($configCache, $_POST, 'config', 'hostname');
				self::checkSetting($configCache, $_POST, 'system', 'ssl_policy');
				self::checkSetting($configCache, $_POST, 'system', 'basepath');
				self::checkSetting($configCache, $_POST, 'system', 'urlpath');

				self::checkSetting($configCache, $_POST, 'database', 'hostname', Core\Installer::DEFAULT_HOST);
				self::checkSetting($configCache, $_POST, 'database', 'username', '');
				self::checkSetting($configCache, $_POST, 'database', 'password', '');
				self::checkSetting($configCache, $_POST, 'database', 'database', '');

				self::checkSetting($configCache, $_POST, 'api', 'enable');
				self::checkSetting($configCache, $_POST, 'api', 'paseto_synchronous_key');

				// If we cannot connect to the database, return to the previous step
				if (!self::$installer->checkDB(DI::dba())) {
					self::$currentWizardStep = self::DATABASE_CONFIG;
				}

				break;

			case self::FINISHED:
				self::checkSetting($configCache, $_POST, 'config', 'php_path');

				self::checkSetting($configCache, $_POST, 'config', 'hostname');
				self::checkSetting($configCache, $_POST, 'system', 'ssl_policy');
				self::checkSetting($configCache, $_POST, 'system', 'basepath');
				self::checkSetting($configCache, $_POST, 'system', 'urlpath');

				self::checkSetting($configCache, $_POST, 'database', 'hostname', Core\Installer::DEFAULT_HOST);
				self::checkSetting($configCache, $_POST, 'database', 'username', '');
				self::checkSetting($configCache, $_POST, 'database', 'password', '');
				self::checkSetting($configCache, $_POST, 'database', 'database', '');

				self::checkSetting($configCache, $_POST, 'system', 'default_timezone', Core\Installer::DEFAULT_TZ);
				self::checkSetting($configCache, $_POST, 'system', 'language', Core\Installer::DEFAULT_LANG);
				self::checkSetting($configCache, $_POST, 'config', 'admin_email', '');

				// If we cannot connect to the database, return to the Database config wizard
				if (!self::$installer->checkDB(DI::dba())) {
					self::$currentWizardStep = self::DATABASE_CONFIG;
					return;
				}

				if (!self::$installer->createConfig($configCache)) {
					return;
				}

				self::$installer->installDatabase($configCache->get('system', 'basepath'));

				break;
		}
	}

	public static function content(array $parameters = [])
	{
		$a           = DI::app();
		$configCache = $a->getConfigCache();

		$output = '';

		$install_title = DI::l10n()->t('Friendica Communications Server - Setup');

		switch (self::$currentWizardStep) {
			case self::SYSTEM_CHECK:
				$php_path = $configCache->get('config', 'php_path');

				$status = self::$installer->checkEnvironment(DI::baseUrl()->get(), $php_path);

				$tpl    = Renderer::getMarkupTemplate('install_checks.tpl');
				$output .= Renderer::replaceMacros($tpl, [
					'$title'       => $install_title,
					'$pass'        => DI::l10n()->t('System check'),
					'$required'    => DI::l10n()->t('Required'),
					'$requirement_not_satisfied' => DI::l10n()->t('Requirement not satisfied'),
					'$optional_requirement_not_satisfied' => DI::l10n()->t('Optional requirement not satisfied'),
					'$ok'          => DI::l10n()->t('OK'),
					'$checks'      => self::$installer->getChecks(),
					'$passed'      => $status,
					'$see_install' => DI::l10n()->t('Please see the file "doc/INSTALL.md".'),
					'$next'        => DI::l10n()->t('Next'),
					'$reload'      => DI::l10n()->t('Check again'),
					'$php_path'    => $php_path,
				]);
				break;

			case self::BASE_CONFIG:
				$ssl_choices = [
					App\BaseURL::SSL_POLICY_NONE     => DI::l10n()->t("No SSL policy, links will track page SSL state"),
					App\BaseURL::SSL_POLICY_FULL     => DI::l10n()->t("Force all links to use SSL"),
					App\BaseURL::SSL_POLICY_SELFSIGN => DI::l10n()->t("Self-signed certificate, use SSL for local links only \x28discouraged\x29")
				];

				$tpl    = Renderer::getMarkupTemplate('install_base.tpl');
				$output .= Renderer::replaceMacros($tpl, [
					'$title'      => $install_title,
					'$pass'       => DI::l10n()->t('Base settings'),
					'$ssl_policy' => ['system-ssl_policy',
						DI::l10n()->t("SSL link policy"),
						$configCache->get('system', 'ssl_policy'),
						DI::l10n()->t("Determines whether generated links should be forced to use SSL"),
						$ssl_choices],
					'$hostname'   => ['config-hostname',
						DI::l10n()->t('Host name'),
						$configCache->get('config', 'hostname'),
						DI::l10n()->t('Overwrite this field in case the determinated hostname isn\'t right, otherweise leave it as is.'),
						DI::l10n()->t('Required')],
					'$basepath'   => ['system-basepath',
						DI::l10n()->t("Base path to installation"),
						$configCache->get('system', 'basepath'),
						DI::l10n()->t("If the system cannot detect the correct path to your installation, enter the correct path here. This setting should only be set if you are using a restricted system and symbolic links to your webroot."),
						DI::l10n()->t('Required')],
					'$urlpath'    => ['system-urlpath',
						DI::l10n()->t('Sub path of the URL'),
						$configCache->get('system', 'urlpath'),
						DI::l10n()->t('Overwrite this field in case the sub path determination isn\'t right, otherwise leave it as is. Leaving this field blank means the installation is at the base URL without sub path.'),
						''],
					'$php_path'   => $configCache->get('config', 'php_path'),
					'$submit'     => DI::l10n()->t('Submit'),
				]);
				break;

			case self::DATABASE_CONFIG:
				$tpl    = Renderer::getMarkupTemplate('install_db.tpl');
				$output .= Renderer::replaceMacros($tpl, [
					'$title'      => $install_title,
					'$pass'       => DI::l10n()->t('Database connection'),
					'$info_01'    => DI::l10n()->t('In order to install Friendica we need to know how to connect to your database.'),
					'$info_02'    => DI::l10n()->t('Please contact your hosting provider or site administrator if you have questions about these settings.'),
					'$info_03'    => DI::l10n()->t('The database you specify below should already exist. If it does not, please create it before continuing.'),
					'$required'   => DI::l10n()->t('Required'),
					'$requirement_not_satisfied' => DI::l10n()->t('Requirement not satisfied'),
					'$checks'     => self::$installer->getChecks(),
					'$hostname'   => $configCache->get('config', 'hostname'),
					'$ssl_policy' => $configCache->get('system', 'ssl_policy'),
					'$basepath'   => $configCache->get('system', 'basepath'),
					'$urlpath'    => $configCache->get('system', 'urlpath'),
					'$dbhost'     => ['database-hostname',
						DI::l10n()->t('Database Server Name'),
						$configCache->get('database', 'hostname'),
						'',
						DI::l10n()->t('Required')],
					'$dbuser'     => ['database-username',
						DI::l10n()->t('Database Login Name'),
						$configCache->get('database', 'username'),
						'',
						DI::l10n()->t('Required'),
						'autofocus'],
					'$dbpass'     => ['database-password',
						DI::l10n()->t('Database Login Password'),
						$configCache->get('database', 'password'),
						DI::l10n()->t("For security reasons the password must not be empty"),
						DI::l10n()->t('Required')],
					'$dbdata'     => ['database-database',
						DI::l10n()->t('Database Name'),
						$configCache->get('database', 'database'),
						'',
						DI::l10n()->t('Required')],
					'$lbl_10'     => DI::l10n()->t('Please select a default timezone for your website'),
					'$php_path'   => $configCache->get('config', 'php_path'),
					'$api_enable' => ['api-enable',
						DI::l10n()->t('Enable experimental OAuth API'),
						$configCache->get('api', 'enable'),
						''],
					'$submit'     => DI::l10n()->t('Submit')
				]);
				break;

			case self::SITE_SETTINGS:
				/* Installed langs */
				$lang_choices = DI::l10n()->getAvailableLanguages();

				$tpl    = Renderer::getMarkupTemplate('install_settings.tpl');
				$output .= Renderer::replaceMacros($tpl, [
					'$title'      => $install_title,
					'$required'   => DI::l10n()->t('Required'),
					'$checks'     => self::$installer->getChecks(),
					'$pass'       => DI::l10n()->t('Site settings'),
					'$hostname'   => $configCache->get('config', 'hostname'),
					'$ssl_policy' => $configCache->get('system', 'ssl_policy'),
					'$basepath'   => $configCache->get('system', 'basepath'),
					'$urlpath'    => $configCache->get('system', 'urlpath'),
					'$dbhost'     => $configCache->get('database', 'hostname'),
					'$dbuser'     => $configCache->get('database', 'username'),
					'$dbpass'     => $configCache->get('database', 'password'),
					'$dbdata'     => $configCache->get('database', 'database'),
					'$adminmail'  => ['config-admin_email',
						DI::l10n()->t('Site administrator email address'),
						$configCache->get('config', 'admin_email'),
						DI::l10n()->t('Your account email address must match this in order to use the web admin panel.'),
						DI::l10n()->t('Required'), 'autofocus', 'email'],
					'$timezone'   => Temporal::getTimezoneField('system-default_timezone',
						DI::l10n()->t('Please select a default timezone for your website'),
						$configCache->get('system', 'default_timezone'),
						''),
					'$language'   => ['system-language',
						DI::l10n()->t('System Language:'),
						$configCache->get('system', 'language'),
						DI::l10n()->t('Set the default language for your Friendica installation interface and to send emails.'),
						$lang_choices],
					'$php_path'   => $configCache->get('config', 'php_path'),
					'$submit'     => DI::l10n()->t('Submit')
				]);
				break;

			case self::FINISHED:
				$db_return_text = "";

				if (count(self::$installer->getChecks()) == 0) {
					$txt            = '<p style="font-size: 130%;">';
					$txt            .= DI::l10n()->t('Your Friendica site database has been installed.') . EOL;
					$db_return_text .= $txt;
				}

				$tpl    = Renderer::getMarkupTemplate('install_finished.tpl');
				$output .= Renderer::replaceMacros($tpl, [
					'$title'    => $install_title,
					'$required' => DI::l10n()->t('Required'),
					'$requirement_not_satisfied' => DI::l10n()->t('Requirement not satisfied'),
					'$checks'   => self::$installer->getChecks(),
					'$pass'     => DI::l10n()->t('Installation finished'),
					'$text'     => $db_return_text . self::whatNext(),
				]);

				break;
		}

		return $output;
	}

	/**
	 * Creates the text for the next steps
	 *
	 * @return string The text for the next steps
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function whatNext()
	{
		$baseurl = DI::baseUrl()->get();
		return
			DI::l10n()->t('<h1>What next</h1>')
			. "<p>" . DI::l10n()->t('IMPORTANT: You will need to [manually] setup a scheduled task for the worker.')
			. DI::l10n()->t('Please see the file "INSTALL.txt".')
			. "</p><p>"
			. DI::l10n()->t('Go to your new Friendica node <a href="%s/register">registration page</a> and register as new user. Remember to use the same email you have entered as administrator email. This will allow you to enter the site admin panel.', $baseurl)
			. "</p>";
	}

	/**
	 * Checks the $_POST settings and updates the config Cache for it
	 *
	 * @param Cache       $configCache The current config cache
	 * @param array       $post        The $_POST data
	 * @param string      $cat         The category of the setting
	 * @param string      $key         The key of the setting
	 * @param null|string $default     The default value
	 */
	private static function checkSetting(Cache $configCache, array $post, $cat, $key, $default = null)
	{
		$configCache->set($cat, $key,
			Strings::escapeTags(
				trim(($post[sprintf('%s-%s', $cat, $key)] ?? '') ?:
						($default ?? $configCache->get($cat, $key))
				)
			)
		);
	}
}
