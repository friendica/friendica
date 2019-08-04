<?php
/**
 * @file src/Core/Install.php
 */
namespace Friendica\Core;

use Exception;
use Friendica\Core\Config\Cache\ConfigCache;
use Friendica\Database\DBStructure;

/**
 * Contains methods for installation purpose of Friendica
 */
class Installer
{
	// Default values for the install page
	const DEFAULT_LANG = 'en';
	const DEFAULT_TZ   = 'America/Los_Angeles';
	const DEFAULT_HOST = 'localhost';



	/**
	 * @var string The path to the PHP binary
	 */
	private $phppath = null;

	/**
	 * Returns the PHP path
	 *
	 * @return string the PHP Path
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function getPHPPath()
	{
		// if not set, determine the PHP path
		if (!isset($this->phppath)) {
			$this->checkPHP();
			$this->resetChecks();
		}

		return $this->phppath;
	}

	/**
	 * Executes the installation of Friendica in the given environment.
	 * - Creates `config/local.config.php`
	 * - Installs Database Structure
	 *
	 * @param ConfigCache $configCache The config cache with all config relevant information
	 *
	 * @return bool true if the config was created, otherwise false
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function createConfig(ConfigCache $configCache)
	{
		$basepath = $configCache->get('system', 'basepath');

		$tpl = Renderer::getMarkupTemplate('local.config.tpl');
		$txt = Renderer::replaceMacros($tpl, [
			'$dbhost'    => $configCache->get('database', 'hostname'),
			'$dbuser'    => $configCache->get('database', 'username'),
			'$dbpass'    => $configCache->get('database', 'password'),
			'$dbdata'    => $configCache->get('database', 'database'),

			'$phpath'    => $configCache->get('config', 'php_path'),
			'$adminmail' => $configCache->get('config', 'admin_email'),
			'$hostname'  => $configCache->get('config', 'hostname'),

			'$urlpath'   => $configCache->get('system', 'urlpath'),
			'$baseurl'   => $configCache->get('system', 'url'),
			'$sslpolicy' => $configCache->get('system', 'ssl_policy'),
			'$basepath'  => $basepath,
			'$timezone'  => $configCache->get('system', 'default_timezone'),
			'$language'  => $configCache->get('system', 'language'),
		]);

		$result = file_put_contents($basepath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'local.config.php', $txt);

		if (!$result) {
			$this->addCheck($this->l10n->t('The database configuration file "config/local.config.php" could not be written. Please use the enclosed text to create a configuration file in your web server root.'), false, false, htmlentities($txt, ENT_COMPAT, 'UTF-8'));
		}

		return $result;
	}

	/***
	 * Installs the DB-Scheme for Friendica
	 *
	 * @param string $basePath The base path of this application
	 *
	 * @return bool true if the installation was successful, otherwise false
	 * @throws Exception
	 */
	public function installDatabase($basePath)
	{
		$result = DBStructure::update($basePath, false, true, true);

		if ($result) {
			$txt = $this->l10n->t('You may need to import the file "database.sql" manually using phpmyadmin or mysql.') . EOL;
			$txt .= $this->l10n->t('Please see the file "INSTALL.txt".');

			$this->addCheck($txt, false, true, htmlentities($result, ENT_COMPAT, 'UTF-8'));

			return false;
		}

		return true;
	}

	/**
	 * Setup the default cache for a new installation
	 *
	 * @param ConfigCache $configCache The configuration cache
	 * @param string       $basePath    The determined basepath
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function setUpCache(ConfigCache $configCache, $basePath)
	{
		$configCache->set('config', 'php_path'  , $this->getPHPPath());
		$configCache->set('system', 'basepath'  , $basePath);
	}
}
