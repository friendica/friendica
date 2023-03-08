<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica\Console;

use Asika\SimpleConsole\Console;
use Friendica\App;
use Friendica\App\BaseURL;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Config\ValueObject\Cache;
use Friendica\Core\Installer;
use Friendica\Core\Theme;
use Friendica\Database\Database;
use Friendica\Util\BasePath;
use RuntimeException;

class AutomaticInstallation extends Console
{
	/** @var App\Mode */
	private $appMode;
	/** @var \Friendica\Core\Config\ValueObject\Cache */
	private $configCache;
	/** @var IManageConfigValues */
	private $config;
	/** @var Database */
	private $dba;

	protected function getHelp()
	{
		return <<<HELP
Installation - Install Friendica automatically
Synopsis
	bin/console autoinstall [-h|--help|-?] [-v] [-a] [-f]

Description
    Installs Friendica with data based on the local.config.php file or environment variables

Notes
    Not checking .htaccess/URL-Rewrite during CLI installation.

Options
    -h|--help|-?            Show help information
    -v                      Show more debug information.
    -a                      All setup checks are required (except .htaccess)
    -f|--file <config>      prepared config file (e.g. "config/local.config.php" itself) which will override every other config option - except the environment variables)
    -s|--savedb               Save the DB credentials to the file (if environment variables is used)
    -H|--dbhost <host>        The host of the mysql/mariadb database (env MYSQL_HOST)
    -p|--dbport <port>        The port of the mysql/mariadb database (env MYSQL_PORT)
    -s|--dbsocket <socket>    The socket of the mysql/mariadb database (env MYSQL_SOCKET)
    -d|--dbdata <database>    The name of the mysql/mariadb database (env MYSQL_DATABASE)
    -u|--dbuser <username>    The username of the mysql/mariadb database login (env MYSQL_USER or MYSQL_USERNAME)
    -P|--dbpass <password>    The password of the mysql/mariadb database login (env MYSQL_PASSWORD)
    -U|--url <url>            The full base URL of Friendica - f.e. 'https://friendica.local/sub' (env FRIENDICA_URL) 
    -B|--phppath <php_path>   The path of the PHP binary (env FRIENDICA_PHP_PATH)
    -b|--basepath <base_path> The basepath of Friendica (env FRIENDICA_BASE_PATH)
    -t|--tz <timezone>        The timezone of Friendica (env FRIENDICA_TZ)
    -L|--lang <language>      The language of Friendica (env FRIENDICA_LANG)
 
Environment variables
   MYSQL_HOST                  The host of the mysql/mariadb database (mandatory if mysql and environment is used)
   MYSQL_PORT                  The port of the mysql/mariadb database
   MYSQL_SOCKET                The socket of the mysql/mariadb database
   MYSQL_USERNAME|MYSQL_USER   The username of the mysql/mariadb database login (MYSQL_USERNAME is for mysql, MYSQL_USER for mariadb)
   MYSQL_PASSWORD              The password of the mysql/mariadb database login
   MYSQL_DATABASE              The name of the mysql/mariadb database
   FRIENDICA_URL               The full base URL of Friendica - f.e. 'https://friendica.local/sub'
   FRIENDICA_PHP_PATH          The path of the PHP binary - leave empty for auto detection
   FRIENDICA_BASE_PATH         The basepath of Friendica - leave empty for auto detection
   FRIENDICA_ADMIN_MAIL        The admin email address of Friendica (this email will be used for admin access)
   FRIENDICA_TZ                The timezone of Friendica
   FRIENDICA_LANG              The langauge of Friendica
   
Examples
	bin/console autoinstall -f 'input.config.php
		Installs Friendica with the prepared 'input.config.php' file

	bin/console autoinstall --savedb
		Installs Friendica with environment variables and saves them to the 'config/local.config.php' file

	bin/console autoinstall -H localhost -p 3365 -u user -P passwort1234 -d friendica -U https://friendica.fqdn
		Installs Friendica with a local mysql database with credentials
HELP;
	}

	public function __construct(App\Mode $appMode, Cache $configCache, IManageConfigValues $config, Database $dba, array $argv = null)
	{
		parent::__construct($argv);

		$this->appMode     = $appMode;
		$this->configCache = $configCache;
		$this->config      = $config;
		$this->dba         = $dba;
	}

	protected function doExecute(): int
	{
		// Initialise the app
		$this->out("Initializing setup...");

		$installer = new Installer();

		$configCache  = $this->configCache;
		$basePathConf = $configCache->get('system', 'basepath');
		$basepath     = new BasePath($basePathConf);
		$installer->setUpCache($configCache, $basepath->getPath());

		$this->out(" Complete!\n");

		// Check Environment
		$this->out("Checking environment...");

		$installer->resetChecks();

		if (!$this->runBasicChecks($installer, $configCache)) {
			$errorMessage = $this->extractErrors($installer->getChecks());
			throw new RuntimeException($errorMessage);
		}

		$this->out(" Complete!\n");

		// if a config file is set,
		$config_file = $this->getOption(['f', 'file']);

		if (!empty($config_file)) {
			$this->out("Loading config file '$config_file'...");
			if (!file_exists($config_file)) {
				throw new RuntimeException("ERROR: Config file does not exist.");
			}

			//append config file to the config cache
			$config = include($config_file);
			if (!is_array($config)) {
				throw new Exception('Error loading config file ' . $config_file);
			}
			$configCache->load($config, Cache::SOURCE_FILE);
		} else {
			// Creating config file
			$this->out("Creating config file...");

			$save_db = $this->getOption(['s', 'savedb'], false);

			$db_host = $this->getOption(['H', 'dbhost'], ($save_db) ? (getenv('MYSQL_HOST')) : Installer::DEFAULT_HOST);
			$db_port = $this->getOption(['p', 'dbport'], ($save_db) ? getenv('MYSQL_PORT') : null);
			$db_socket = $this->getOption(['s', 'dbsocket'], ($save_db) ? getenv('MYSQL_SOCKET') : null);
			$configCache->set('database', 'hostname', $db_host . (!empty($db_port) ? ':' . $db_port : ''));
			$configCache->set('database', 'database',
				$this->getOption(['d', 'dbdata'],
					($save_db) ? getenv('MYSQL_DATABASE') : ''));
			$configCache->set('database', 'username',
				$this->getOption(['u', 'dbuser'],
					($save_db) ? getenv('MYSQL_USER') . getenv('MYSQL_USERNAME') : ''));
			$configCache->set('database', 'password',
				$this->getOption(['P', 'dbpass'],
					($save_db) ? getenv('MYSQL_PASSWORD') : ''));

			$php_path = $this->getOption(['b', 'phppath'], !empty('FRIENDICA_PHP_PATH') ? getenv('FRIENDICA_PHP_PATH') : null);
			if (!empty($php_path)) {
				$configCache->set('config', 'php_path', $php_path);
			} else {
				$configCache->set('config', 'php_path', $installer->getPHPPath());
			}

			$configCache->set('config', 'admin_email',
				$this->getOption(['A', 'admin'],
					!empty(getenv('FRIENDICA_ADMIN_MAIL')) ? getenv('FRIENDICA_ADMIN_MAIL') : ''));
			$configCache->set('system', 'default_timezone',
				$this->getOption(['T', 'tz'],
					!empty(getenv('FRIENDICA_TZ')) ? getenv('FRIENDICA_TZ') : Installer::DEFAULT_TZ));
			$configCache->set('system', 'language',
				$this->getOption(['L', 'lang'],
					!empty(getenv('FRIENDICA_LANG')) ? getenv('FRIENDICA_LANG') : Installer::DEFAULT_LANG));

			$basepath = $this->getOption(['b', 'basepath'], !empty(getenv('FRIENDICA_BASE_PATH')) ? getenv('FRIENDICA_BASE_PATH') : null);
			if (!empty($basepath)) {
				$configCache->set('system', 'basepath', $basepath);
			}

			$url = $this->getOption(['U', 'url'], !empty(getenv('FRIENDICA_URL')) ? getenv('FRIENDICA_URL') : null);

			if (empty($url)) {
				$this->out('The Friendica URL has to be set during CLI installation.');
				return 1;
			} else {
				$configCache->set('system', 'url', $url);
			}

			$installer->createConfig($configCache);
		}

		$this->out(" Complete!\n");

		// Check database connection
		$this->out("Checking database...");

		$installer->resetChecks();

		if (!$installer->checkDB($this->dba)) {
			$errorMessage = $this->extractErrors($installer->getChecks());
			throw new RuntimeException($errorMessage);
		}

		$this->out(" Complete!\n");

		// Install database
		$this->out("Inserting data into database...\n");

		$installer->resetChecks();

		if (!$installer->installDatabase()) {
			$errorMessage = $this->extractErrors($installer->getChecks());
			throw new RuntimeException($errorMessage);
		}

		if (!empty($config_file) && $config_file != 'config' . DIRECTORY_SEPARATOR . 'local.config.php') {
			// Copy config file
			$this->out("Copying config file...");
			if (!copy($config_file, $basePathConf . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'local.config.php')) {
				throw new RuntimeException("ERROR: Saving config file failed. Please copy '$config_file' to '" . $basePathConf . "'" . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "local.config.php' manually.\n");
			}
		}

		$this->out(" Complete!\n");

		// Install theme
		$this->out("Installing theme");
		if (!empty($this->config->get('system', 'theme'))) {
			Theme::install($this->config->get('system', 'theme'));
			$this->out(" Complete\n");
		} else {
			$this->out(" Theme setting is empty. Please check the file 'config/local.config.php'\n");
		}

		$this->out("\nInstallation is finished");

		return 0;
	}

	/**
	 * @param Installer                                $installer   The Installer instance
	 * @param \Friendica\Core\Config\ValueObject\Cache $configCache The config cache
	 *
	 * @return bool true if checks were successfully, otherwise false
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private function runBasicChecks(Installer $installer, Cache $configCache)
	{
		$checked = true;

		$installer->resetChecks();
		if (!$installer->checkFunctions()) {
			$checked = false;
		}
		if (!$installer->checkImagick()) {
			$checked = false;
		}
		if (!$installer->checkLocalIni()) {
			$checked = false;
		}
		if (!$installer->checkSmarty3()) {
			$checked = false;
		}
		if (!$installer->checkKeys()) {
			$checked = false;
		}

		$php_path = $configCache->get('config', 'php_path');

		if (!$installer->checkPHP($php_path, true)) {
			$checked = false;
		}

		$this->out(" NOTICE: Not checking .htaccess/URL-Rewrite during CLI installation.\n");

		return $checked;
	}

	/**
	 * @param array $results
	 *
	 * @return string
	 */
	private function extractErrors($results)
	{
		$errorMessage      = '';
		$allChecksRequired = $this->getOption('a') !== null;

		foreach ($results as $result) {
			if (($allChecksRequired || $result['required'] === true) && $result['status'] === false) {
				$errorMessage .= "--------\n";
				$errorMessage .= $result['title'] . ': ' . $result['help'] . "\n";
			}
		}

		return $errorMessage;
	}
}
